<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push;

use Closure;

final class MiddlewarePushStack implements MessageHandlerPushInterface
{
    /**
     * @var Closure[]
     */
    private array $middlewares;
    /**
     * @var MessageHandlerPushInterface
     */
    private MessageHandlerPushInterface $finishHandler;
    /**
     * Contains a stack of middleware wrapped in handlers.
     * Each handler points to the handler of middleware that will be processed next.
     *
     * @var MessageHandlerPushInterface|null stack of middleware
     */
    private ?MessageHandlerPushInterface $stack = null;

    /**
     * @param Closure[] $middlewares Middlewares.
     * @param MessageHandlerPushInterface $finishHandler Fallback handler
     * events.
     */
    public function __construct(array $middlewares, MessageHandlerPushInterface $finishHandler)
    {
        $this->middlewares = $middlewares;
        $this->finishHandler = $finishHandler;
    }

    public function handlePush(PushRequest $request): PushRequest
    {
        if ($this->stack === null) {
            $this->build();
        }

        /** @psalm-suppress PossiblyNullReference */
        return $this->stack->handlePush($request);
    }

    private function build(): void
    {
        $handler = $this->finishHandler;

        foreach ($this->middlewares as $middleware) {
            $handler = $this->wrap($middleware, $handler);
        }

        $this->stack = $handler;
    }

    /**
     * Wrap handler by middlewares.
     */
    private function wrap(Closure $middlewareFactory, MessageHandlerPushInterface $handler): MessageHandlerPushInterface
    {
        return new class ($middlewareFactory, $handler) implements MessageHandlerPushInterface {
            private Closure $middlewareFactory;
            private MessageHandlerPushInterface $handler;
            private ?MiddlewarePushInterface $middleware = null;

            public function __construct(Closure $middlewareFactory, MessageHandlerPushInterface $handler)
            {
                $this->middlewareFactory = $middlewareFactory;
                $this->handler = $handler;
            }

            public function handlePush(PushRequest $request): PushRequest
            {
                if ($this->middleware === null) {
                    $this->middleware = ($this->middlewareFactory)();
                }

                return $this->middleware->processPush($request, $this->handler);
            }
        };
    }
}
