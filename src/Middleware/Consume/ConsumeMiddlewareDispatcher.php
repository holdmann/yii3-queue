<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Consume;

use Closure;

final class ConsumeMiddlewareDispatcher
{
    /**
     * Contains a middleware pipeline handler.
     *
     * @var MiddlewareConsumeStack|null The middleware stack.
     */
    private $stack;

    /**
     * @var array[]|callable[]|MiddlewareConsumeInterface[]|string[]
     */
    private $middlewareDefinitions;
    /**
     * @var \Yiisoft\Queue\Middleware\Consume\MiddlewareFactoryConsumeInterface
     */
    private $middlewareFactory;
    /**
     * @param mixed[]|callable|string|\Yiisoft\Queue\Middleware\Consume\MiddlewareConsumeInterface ...$middlewareDefinitions
     */
    public function __construct(MiddlewareFactoryConsumeInterface $middlewareFactory, ...$middlewareDefinitions)
    {
        $this->middlewareFactory = $middlewareFactory;
        $this->middlewareDefinitions = array_reverse($middlewareDefinitions);
    }

    /**
     * Dispatch request through middleware to get response.
     *
     * @param ConsumeRequest $request Request to pass to middleware.
     * @param MessageHandlerConsumeInterface $finishHandler Handler to use in case no middleware produced response.
     */
    public function dispatch(
        ConsumeRequest $request,
        MessageHandlerConsumeInterface $finishHandler
    ): ConsumeRequest {
        if ($this->stack === null) {
            $this->stack = new MiddlewareConsumeStack($this->buildMiddlewares(), $finishHandler);
        }

        return $this->stack->handleConsume($request);
    }

    /**
     * Returns new instance with middleware handlers replaced with the ones provided.
     * Last specified handler will be executed first.
     *
     * @param array[]|callable[]|MiddlewareConsumeInterface[]|string[] $middlewareDefinitions Each array element is:
     *
     * - A name of a middleware class. The middleware instance will be obtained from container executed.
     * - A callable with `function(ServerRequestInterface $request, RequestHandlerInterface $handler):
     *     ResponseInterface` signature.
     * - A "callable-like" array in format `[FooMiddleware::class, 'index']`. `FooMiddleware` instance will
     *   be created and `index()` method will be executed.
     * - A function returning a middleware. The middleware returned will be executed.
     *
     * For callables typed parameters are automatically injected using dependency injection container.
     *
     * @return self New instance of the {@see ConsumeMiddlewareDispatcher}
     */
    public function withMiddlewares(array $middlewareDefinitions): self
    {
        $instance = clone $this;
        $instance->middlewareDefinitions = array_reverse($middlewareDefinitions);

        // Fixes a memory leak.
        unset($instance->stack);
        $instance->stack = null;

        return $instance;
    }

    /**
     * @return bool Whether there are middleware defined in the dispatcher.
     */
    public function hasMiddlewares(): bool
    {
        return $this->middlewareDefinitions !== [];
    }

    /**
     * @return Closure[]
     */
    private function buildMiddlewares(): array
    {
        $middlewares = [];
        $factory = $this->middlewareFactory;

        foreach ($this->middlewareDefinitions as $middlewareDefinition) {
            $middlewares[] = static function () use ($factory, $middlewareDefinition) : MiddlewareConsumeInterface {
                return $factory->createConsumeMiddleware(
                    $middlewareDefinition
                );
            };
        }

        return $middlewares;
    }
}
