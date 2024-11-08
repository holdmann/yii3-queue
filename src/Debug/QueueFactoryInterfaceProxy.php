<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Debug;

use Yiisoft\Queue\QueueFactoryInterface;
use Yiisoft\Queue\QueueInterface;

final class QueueFactoryInterfaceProxy implements QueueFactoryInterface
{
    private QueueFactoryInterface $queueFactory;
    private QueueCollector $collector;
    public function __construct(QueueFactoryInterface $queueFactory, QueueCollector $collector)
    {
        $this->queueFactory = $queueFactory;
        $this->collector = $collector;
    }
    public function get(string $channel = self::DEFAULT_CHANNEL_NAME): QueueInterface
    {
        $queue = $this->queueFactory->get($channel);

        return new QueueDecorator($queue, $this->collector);
    }
}
