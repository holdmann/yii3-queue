<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Debug;

use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Enum\JobStatus;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface;
use Yiisoft\Queue\QueueInterface;

final class QueueDecorator implements QueueInterface
{
    /**
     * @var \Yiisoft\Queue\QueueInterface
     */
    private $queue;
    /**
     * @var \Yiisoft\Queue\Debug\QueueCollector
     */
    private $collector;
    public function __construct(QueueInterface $queue, QueueCollector $collector)
    {
        $this->queue = $queue;
        $this->collector = $collector;
    }
    /**
     * @param string|int $id
     */
    public function status($id): JobStatus
    {
        $result = $this->queue->status($id);
        $this->collector->collectStatus($id, $result);

        return $result;
    }

    /**
     * @param string|mixed[]|callable|\Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface ...$middlewareDefinitions
     */
    public function push(
        MessageInterface $message,
        ...$middlewareDefinitions
    ): MessageInterface {
        $message = $this->queue->push($message, ...$middlewareDefinitions);
        $this->collector->collectPush($this->queue->getChannelName(), $message, ...$middlewareDefinitions);
        return $message;
    }

    public function run(int $max = 0): int
    {
        return $this->queue->run($max);
    }

    public function listen(): void
    {
        $this->queue->listen();
    }

    public function withAdapter(AdapterInterface $adapter): QueueInterface
    {
        return new self($this->queue->withAdapter($adapter), $this->collector);
    }

    public function getChannelName(): string
    {
        return $this->queue->getChannelName();
    }

    public function withChannelName(string $channel): QueueInterface
    {
        $new = clone $this;
        $new->queue = $this->queue->withChannelName($channel);
        return $new;
    }
}
