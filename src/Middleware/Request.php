<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware;

use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\QueueInterface;

// TODO: fix later
//final class Request
class Request
{
    protected ?QueueInterface $queue = null;
    public function __construct(private MessageInterface $message, private ?AdapterInterface $adapter)
    {
    }

    public function getMessage(): MessageInterface
    {
        return $this->message;
    }

    public function getAdapter(): ?AdapterInterface
    {
        return $this->adapter;
    }

    public function withMessage(MessageInterface $message): self
    {
        $instance = clone $this;
        $instance->message = $message;

        return $instance;
    }

    public function withAdapter(AdapterInterface $adapter): self
    {
        $instance = clone $this;
        $instance->adapter = $adapter;

        return $instance;
    }

    public function withQueue(QueueInterface $queue): self
    {
        $instance = clone $this;
        $instance->queue = $queue;

        return $instance;
    }
}
