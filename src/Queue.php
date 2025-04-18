<?php

declare(strict_types=1);

namespace Yiisoft\Queue;

use Psr\Log\LoggerInterface;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Exception\AdapterConfiguration\AdapterNotConfiguredException;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Push\AdapterPushHandler;
use Yiisoft\Queue\Middleware\Push\MessageHandlerPushInterface;
use Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Push\PushRequest;
use Yiisoft\Queue\Worker\WorkerInterface;
use Yiisoft\Queue\Message\IdEnvelope;

final class Queue implements QueueInterface
{
    private WorkerInterface $worker;
    private LoopInterface $loop;
    private LoggerInterface $logger;
    private PushMiddlewareDispatcher $pushMiddlewareDispatcher;
    private ?AdapterInterface $adapter = null;
    /**
     * @var array|array[]|callable[]|MiddlewarePushInterface[]|string[]
     */
    private array $middlewareDefinitions;
    private AdapterPushHandler $adapterPushHandler;

    /**
     * @param \Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface|callable|mixed[]|string ...$middlewareDefinitions
     */
    public function __construct(
        WorkerInterface $worker,
        LoopInterface $loop,
        LoggerInterface $logger,
        PushMiddlewareDispatcher $pushMiddlewareDispatcher,
        ?AdapterInterface $adapter = null,
        ...$middlewareDefinitions
    ) {
        $this->worker = $worker;
        $this->loop = $loop;
        $this->logger = $logger;
        $this->pushMiddlewareDispatcher = $pushMiddlewareDispatcher;
        $this->adapter = $adapter;
        $this->middlewareDefinitions = $middlewareDefinitions;
        $this->adapterPushHandler = new AdapterPushHandler();
    }

    public function getChannel(): string
    {
        $this->checkAdapter();
        return $this->adapter->getChannel();
    }

    /**
     * @param \Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface|callable|mixed[]|string ...$middlewareDefinitions
     */
    public function push(
        MessageInterface $message,
        ...$middlewareDefinitions
    ): MessageInterface {
        $this->checkAdapter();
        $this->logger->debug(
            'Preparing to push message with handler name "{handlerName}".',
            ['handlerName' => $message->getHandlerName()]
        );

        $request = new PushRequest($message, $this->adapter);
        $message = $this->pushMiddlewareDispatcher
            ->dispatch($request, $this->createPushHandler(...$middlewareDefinitions))
            ->getMessage();

        /** @var string $messageId */
        $messageId = $message->getMetadata()[IdEnvelope::MESSAGE_ID_KEY] ?? 'null';
        $this->logger->info(
            'Pushed message with handler name "{handlerName}" to the queue. Assigned ID #{id}.',
            ['handlerName' => $message->getHandlerName(), 'id' => $messageId]
        );

        return $message;
    }

    public function run(int $max = 0): int
    {
        $this->checkAdapter();

        $this->logger->debug('Start processing queue messages.');
        $count = 0;

        $handlerCallback = function (MessageInterface $message) use (&$max, &$count): bool {
            if (($max > 0 && $max <= $count) || !$this->handle($message)) {
                return false;
            }
            $count++;

            return true;
        };

        $this->adapter->runExisting($handlerCallback);

        $this->logger->info(
            'Processed {count} queue messages.',
            ['count' => $count]
        );

        return $count;
    }

    public function listen(): void
    {
        $this->checkAdapter();

        $this->logger->info('Start listening to the queue.');
        $this->adapter->subscribe(fn (MessageInterface $message) => $this->handle($message));
        $this->logger->info('Finish listening to the queue.');
    }

    /**
     * @param string|int $id
     */
    public function status($id): JobStatus
    {
        $this->checkAdapter();
        return $this->adapter->status($id);
    }

    public function withAdapter(AdapterInterface $adapter): self
    {
        $new = clone $this;
        $new->adapter = $adapter;

        return $new;
    }

    /**
     * @param \Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface|callable|mixed[]|string ...$middlewareDefinitions
     */
    public function withMiddlewares(...$middlewareDefinitions): self
    {
        $instance = clone $this;
        $instance->middlewareDefinitions = $middlewareDefinitions;

        return $instance;
    }

    /**
     * @param \Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface|callable|mixed[]|string ...$middlewareDefinitions
     */
    public function withMiddlewaresAdded(...$middlewareDefinitions): self
    {
        $instance = clone $this;
        $instance->middlewareDefinitions = array_merge(array_values($instance->middlewareDefinitions), array_values($middlewareDefinitions));

        return $instance;
    }

    private function handle(MessageInterface $message): bool
    {
        $this->worker->process($message, $this);

        return $this->loop->canContinue();
    }

    /**
     * @psalm-assert AdapterInterface $this->adapter
     */
    private function checkAdapter(): void
    {
        if ($this->adapter === null) {
            throw new AdapterNotConfiguredException();
        }
    }

    /**
     * @param \Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface|callable|mixed[]|string ...$middlewares
     */
    private function createPushHandler(...$middlewares): MessageHandlerPushInterface
    {
        return new class (
            $this->adapterPushHandler,
            $this->pushMiddlewareDispatcher,
            array_merge($this->middlewareDefinitions, $middlewares)
        ) implements MessageHandlerPushInterface {
            private AdapterPushHandler $adapterPushHandler;
            private PushMiddlewareDispatcher $dispatcher;
            private array $middlewares;
            public function __construct(AdapterPushHandler $adapterPushHandler, PushMiddlewareDispatcher $dispatcher, array $middlewares)
            {
                $this->adapterPushHandler = $adapterPushHandler;
                $this->dispatcher = $dispatcher;
                /**
                 * @var array|array[]|callable[]|MiddlewarePushInterface[]|string[]
                 */
                $this->middlewares = $middlewares;
            }

            public function handlePush(PushRequest $request): PushRequest
            {
                return $this->dispatcher
                    ->withMiddlewares($this->middlewares)
                    ->dispatch($request, $this->adapterPushHandler);
            }
        };
    }
}
