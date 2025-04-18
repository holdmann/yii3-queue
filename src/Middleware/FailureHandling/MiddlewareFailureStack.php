<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\FailureHandling;

use Closure;

final class MiddlewareFailureStack implements MessageFailureHandlerInterface
{
    /**
     * @var Closure[]
     */
    private array $middlewares;
    /**
     * @var MessageFailureHandlerInterface
     */
    private MessageFailureHandlerInterface $finishHandler;
    /**
     * Contains a stack of middleware wrapped in handlers.
     * Each handler points to the handler of middleware that will be processed next.
     *
     * @var MessageFailureHandlerInterface|null stack of middleware
     */
    private ?MessageFailureHandlerInterface $stack = null;

    /**
     * @param Closure[] $middlewares Middlewares.
     * @param MessageFailureHandlerInterface $finishHandler Fallback handler
     * events.
     */
    public function __construct(array $middlewares, MessageFailureHandlerInterface $finishHandler)
    {
        $this->middlewares = $middlewares;
        $this->finishHandler = $finishHandler;
    }

    public function handleFailure(FailureHandlingRequest $request): FailureHandlingRequest
    {
        if ($this->stack === null) {
            $this->build();
        }

        /** @psalm-suppress PossiblyNullReference */
        return $this->stack->handleFailure($request);
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
    private function wrap(Closure $middlewareFactory, MessageFailureHandlerInterface $handler): MessageFailureHandlerInterface
    {
        return new class ($middlewareFactory, $handler) implements MessageFailureHandlerInterface {
            private Closure $middlewareFactory;
            private MessageFailureHandlerInterface $handler;
            private ?MiddlewareFailureInterface $middleware = null;

            public function __construct(Closure $middlewareFactory, MessageFailureHandlerInterface $handler)
            {
                $this->middlewareFactory = $middlewareFactory;
                $this->handler = $handler;
            }

            public function handleFailure(FailureHandlingRequest $request): FailureHandlingRequest
            {
                if ($this->middleware === null) {
                    $this->middleware = ($this->middlewareFactory)();
                }

                return $this->middleware->processFailure($request, $this->handler);
            }
        };
    }
}
