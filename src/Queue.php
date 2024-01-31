<?php

declare(strict_types=1);

namespace Yiisoft\Queue;

use Psr\Log\LoggerInterface;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Enum\JobStatus;
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
    /**
     * @var array|array[]|callable[]|MiddlewarePushInterface[]|string[]
     */
    private $middlewareDefinitions;
    /**
     * @var \Yiisoft\Queue\Middleware\Push\AdapterPushHandler
     */
    private $adapterPushHandler;
    /**
     * @var \Yiisoft\Queue\Worker\WorkerInterface
     */
    private $worker;
    /**
     * @var \Yiisoft\Queue\Cli\LoopInterface
     */
    private $loop;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    /**
     * @var \Yiisoft\Queue\Middleware\Push\PushMiddlewareDispatcher
     */
    private $pushMiddlewareDispatcher;
    /**
     * @var \Yiisoft\Queue\Adapter\AdapterInterface|null
     */
    private $adapter;
    /**
     * @var string
     */
    private $channelName = QueueFactoryInterface::DEFAULT_CHANNEL_NAME;

    /**
     * @param \Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface|callable|mixed[]|string ...$middlewareDefinitions
     */
    public function __construct(
        WorkerInterface $worker,
        LoopInterface $loop,
        LoggerInterface $logger,
        PushMiddlewareDispatcher $pushMiddlewareDispatcher,
        ?AdapterInterface $adapter = null,
        string $channelName = QueueFactoryInterface::DEFAULT_CHANNEL_NAME,
        ...$middlewareDefinitions
    ) {
        $this->worker = $worker;
        $this->loop = $loop;
        $this->logger = $logger;
        $this->pushMiddlewareDispatcher = $pushMiddlewareDispatcher;
        $this->adapter = $adapter;
        $this->channelName = $channelName;
        $this->middlewareDefinitions = $middlewareDefinitions;
        $this->adapterPushHandler = new AdapterPushHandler();
    }

    public function getChannelName(): string
    {
        return $this->channelName;
    }

    /**
     * @param \Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface|callable|mixed[]|string ...$middlewareDefinitions
     */
    public function push(
        MessageInterface $message,
        ...$middlewareDefinitions
    ): MessageInterface {
        $this->logger->debug(
            'Preparing to push message with handler name "{handlerName}".',
            ['handlerName' => $message->getHandlerName()]
        );

        $request = new PushRequest($message, $this->adapter);
        $message = $this->pushMiddlewareDispatcher
            ->dispatch($request, $this->createPushHandler($middlewareDefinitions))
            ->getMessage();

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

        /** @psalm-suppress PossiblyNullReference */
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
        /** @psalm-suppress PossiblyNullReference */
        $this->adapter->subscribe(function (MessageInterface $message) {
            return $this->handle($message);
        });
        $this->logger->info('Finish listening to the queue.');
    }

    /**
     * @param string|int $id
     */
    public function status($id): JobStatus
    {
        $this->checkAdapter();

        /** @psalm-suppress PossiblyNullReference */
        return $this->adapter->status($id);
    }

    /**
     * @return $this
     */
    public function withAdapter(AdapterInterface $adapter): \Yiisoft\Queue\QueueInterface
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
        $item0Unpacked = array_values($instance->middlewareDefinitions);
        $item1Unpacked = array_values($middlewareDefinitions);
        $instance->middlewareDefinitions = array_merge($item0Unpacked, $item1Unpacked);

        return $instance;
    }

    /**
     * @return $this
     */
    public function withChannelName(string $channel): \Yiisoft\Queue\QueueInterface
    {
        $instance = clone $this;
        $instance->channelName = $channel;

        return $instance;
    }

    private function handle(MessageInterface $message): bool
    {
        $this->worker->process($message, $this);

        return $this->loop->canContinue();
    }

    private function checkAdapter(): void
    {
        if ($this->adapter === null) {
            throw new AdapterNotConfiguredException();
        }
    }

    private function createPushHandler(array $middlewares): MessageHandlerPushInterface
    {
        return new class (
            $this->adapterPushHandler,
            $this->pushMiddlewareDispatcher,
            array_merge($this->middlewareDefinitions, $middlewares)
        ) implements MessageHandlerPushInterface {
            /**
             * @var \Yiisoft\Queue\Middleware\Push\AdapterPushHandler
             */
            private $adapterPushHandler;
            /**
             * @var \Yiisoft\Queue\Middleware\Push\PushMiddlewareDispatcher
             */
            private $dispatcher;
            /**
             * @var mixed[]
             */
            private $middlewares;
            public function __construct(AdapterPushHandler $adapterPushHandler, PushMiddlewareDispatcher $dispatcher, array $middlewares)
            {
                $this->adapterPushHandler = $adapterPushHandler;
                $this->dispatcher = $dispatcher;
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
