<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\FailureHandling\Implementation;

use InvalidArgumentException;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\Middleware\FailureHandling\MessageFailureHandlerInterface;
use Yiisoft\Queue\Middleware\FailureHandling\MiddlewareFailureInterface;
use Yiisoft\Queue\Middleware\Push\Implementation\DelayMiddlewareInterface;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureEnvelope;

/**
 * Failure strategy which resends the given message to a queue with an exponentially increasing delay.
 * The delay mechanism **must** be implemented by the used {@see AdapterInterface} implementation.
 */
final class ExponentialDelayMiddleware implements MiddlewareFailureInterface
{
    /**
     * @var string
     */
    private string $id;
    /**
     * @var int
     */
    private int $maxAttempts;
    /**
     * @var float
     */
    private float $delayInitial;
    /**
     * @var float
     */
    private float $delayMaximum;
    /**
     * @var float
     */
    private float $exponent;
    private DelayMiddlewareInterface $delayMiddleware;
    /**
     * @var QueueInterface|null
     */
    private ?QueueInterface $queue = null;
    public const META_KEY_ATTEMPTS = 'failure-strategy-exponential-delay-attempts';
    public const META_KEY_DELAY = 'failure-strategy-exponential-delay-delay';

    /**
     * @param string $id A unique id to differentiate two and more instances of this class
     * @param int $maxAttempts Maximum attempts count for this strategy with the given $id before it will give up
     * @param float $delayInitial The first delay period
     * @param float $delayMaximum The maximum delay period
     * @param float $exponent Message handling delay will be increased by this multiplication each time it fails
     * @param QueueInterface|null $queue
     */
    public function __construct(
        string $id,
        int $maxAttempts,
        float $delayInitial,
        float $delayMaximum,
        float $exponent,
        DelayMiddlewareInterface $delayMiddleware,
        ?QueueInterface $queue = null
    ) {
        $this->id = $id;
        $this->maxAttempts = $maxAttempts;
        $this->delayInitial = $delayInitial;
        $this->delayMaximum = $delayMaximum;
        $this->exponent = $exponent;
        $this->delayMiddleware = $delayMiddleware;
        $this->queue = $queue;
        if ($maxAttempts <= 0) {
            throw new InvalidArgumentException("maxAttempts parameter must be a positive integer, $this->maxAttempts given.");
        }

        if ($delayInitial <= 0) {
            throw new InvalidArgumentException("delayInitial parameter must be a positive float, $this->delayInitial given.");
        }

        if ($delayMaximum < $delayInitial) {
            throw new InvalidArgumentException("delayMaximum parameter must not be less then delayInitial, , $this->delayMaximum given.");
        }

        if ($exponent <= 0) {
            throw new InvalidArgumentException("exponent parameter must not be zero or less, $this->exponent given.");
        }
    }

    public function processFailure(
        FailureHandlingRequest $request,
        MessageFailureHandlerInterface $handler
    ): FailureHandlingRequest {
        $message = $request->getMessage();
        if ($this->suites($message)) {
            $envelope = new FailureEnvelope($message, $this->createNewMeta($message));
            $queue = $this->queue ?? $request->getQueue();
            $middlewareDefinitions = $this->delayMiddleware->withDelay($this->getDelay($envelope));
            $messageNew = $queue->push(
                $envelope,
                $middlewareDefinitions
            );

            return $request->withMessage($messageNew);
        }

        return $handler->handleFailure($request);
    }

    private function suites(MessageInterface $message): bool
    {
        return $this->maxAttempts > $this->getAttempts($message);
    }

    private function createNewMeta(MessageInterface $message): array
    {
        return [
            self::META_KEY_DELAY . "-$this->id" => $this->getDelay($message),
            self::META_KEY_ATTEMPTS . "-$this->id" => $this->getAttempts($message) + 1,
        ];
    }

    private function getAttempts(MessageInterface $message): int
    {
        return $message->getMetadata()[FailureEnvelope::FAILURE_META_KEY][self::META_KEY_ATTEMPTS . "-$this->id"] ?? 0;
    }

    private function getDelay(MessageInterface $message): float
    {
        $meta = $message->getMetadata()[FailureEnvelope::FAILURE_META_KEY] ?? [];
        $key = self::META_KEY_DELAY . "-$this->id";

        $delayOriginal = (float) ($meta[$key] ?? 0);
        if ($delayOriginal <= 0) {
            $delayOriginal = $this->delayInitial;
        }

        $result = $delayOriginal * $this->exponent;

        return min($result, $this->delayMaximum);
    }
}
