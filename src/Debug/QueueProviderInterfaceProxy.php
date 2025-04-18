<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Debug;

use BackedEnum;
use Yiisoft\Queue\Provider\QueueProviderInterface;
use Yiisoft\Queue\QueueInterface;

final class QueueProviderInterfaceProxy implements QueueProviderInterface
{
    /**
     * @readonly
     */
    private QueueProviderInterface $queueProvider;
    /**
     * @readonly
     */
    private QueueCollector $collector;
    public function __construct(QueueProviderInterface $queueProvider, QueueCollector $collector)
    {
        $this->queueProvider = $queueProvider;
        $this->collector = $collector;
    }

    /**
     * @param string|\BackedEnum $channel
     */
    public function get($channel): QueueInterface
    {
        $queue = $this->queueProvider->get($channel);
        return new QueueDecorator($queue, $this->collector);
    }

    /**
     * @param string|\BackedEnum $channel
     */
    public function has($channel): bool
    {
        return $this->queueProvider->has($channel);
    }
}
