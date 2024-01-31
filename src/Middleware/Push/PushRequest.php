<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push;

use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Message\MessageInterface;

final class PushRequest
{
    /**
     * @var \Yiisoft\Queue\Message\MessageInterface
     */
    private $message;
    /**
     * @var \Yiisoft\Queue\Adapter\AdapterInterface|null
     */
    private $adapter;
    public function __construct(MessageInterface $message, ?AdapterInterface $adapter)
    {
        $this->message = $message;
        $this->adapter = $adapter;
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
}
