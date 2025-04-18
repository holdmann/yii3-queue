<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\App;

use Exception;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\JobStatus;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface;
use Yiisoft\Queue\QueueInterface;

final class DummyQueue implements QueueInterface
{
    private string $channel;
    public function __construct(string $channel)
    {
        $this->channel = $channel;
    }

    /**
     * @param string|mixed[]|callable|\Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface ...$middlewareDefinitions
     */
    public function push(
        MessageInterface $message,
        ...$middlewareDefinitions
    ): MessageInterface {
        return $message;
    }

    public function run(int $max = 0): int
    {
        throw new Exception('`run()` method is not implemented yet.');
    }

    public function listen(): void
    {
    }

    /**
     * @param string|int $id
     */
    public function status($id): JobStatus
    {
        throw new Exception('`status()` method is not implemented yet.');
    }

    public function withAdapter(AdapterInterface $adapter): QueueInterface
    {
        throw new Exception('`withAdapter()` method is not implemented yet.');
    }

    public function getChannel(): string
    {
        return $this->channel;
    }
}
