<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\FailureHandling;

use Throwable;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\QueueInterface;

final class FailureHandlingRequest
{
    /**
     * @var \Yiisoft\Queue\Message\MessageInterface
     */
    private $message;
    /**
     * @var \Throwable
     */
    private $exception;
    /**
     * @var \Yiisoft\Queue\QueueInterface
     */
    private $queue;
    public function __construct(MessageInterface $message, Throwable $exception, QueueInterface $queue)
    {
        $this->message = $message;
        $this->exception = $exception;
        $this->queue = $queue;
    }

    /**
     * @return MessageInterface
     */
    public function getMessage(): MessageInterface
    {
        return $this->message;
    }

    public function getException(): Throwable
    {
        return $this->exception;
    }

    public function getQueue(): QueueInterface
    {
        return $this->queue;
    }

    public function withMessage(MessageInterface $message): self
    {
        $instance = clone $this;
        $instance->message = $message;

        return $instance;
    }

    public function withException(Throwable $exception): self
    {
        $instance = clone $this;
        $instance->exception = $exception;

        return $instance;
    }

    public function withQueue(QueueInterface $queue): self
    {
        $instance = clone $this;
        $instance->queue = $queue;

        return $instance;
    }
}
