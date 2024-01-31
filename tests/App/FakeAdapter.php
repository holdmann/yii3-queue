<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\App;

use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Enum\JobStatus;
use Yiisoft\Queue\Message\MessageInterface;

final class FakeAdapter implements AdapterInterface
{
    /**
     * @var mixed[]
     */
    public $pushMessages = [];
    /**
     * @var string
     */
    public $channel = 'default';

    public function push(MessageInterface $message): MessageInterface
    {
        $this->pushMessages[] = $message;

        return $message;
    }

    public function runExisting(callable $handlerCallback): void
    {
        //skip
    }

    /**
     * @param string|int $id
     */
    public function status($id): JobStatus
    {
        //skip
    }

    public function subscribe(callable $handlerCallback): void
    {
        //skip
    }

    public function withChannel(string $channel): AdapterInterface
    {
        $instance = clone $this;
        $instance->pushMessages = [];
        $instance->channel = $channel;

        return $instance;
    }
}
