<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use BackedEnum;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\QueueInterface;

/**
 * Queue provider that only changes the channel name of the base queue.
 * It can be useful when your queues used the same adapter.
 */
final class PrototypeQueueProvider implements QueueProviderInterface
{
    /**
     * @var QueueInterface
     * @readonly
     */
    private QueueInterface $baseQueue;
    /**
     * @readonly
     */
    private AdapterInterface $baseAdapter;
    /**
     * @param QueueInterface $baseQueue Base queue to use for creating queues.
     */
    public function __construct(QueueInterface $baseQueue, AdapterInterface $baseAdapter)
    {
        $this->baseQueue = $baseQueue;
        $this->baseAdapter = $baseAdapter;
    }

    /**
     * @param string|\BackedEnum $channel
     */
    public function get($channel): QueueInterface
    {
        return $this->baseQueue->withAdapter($this->baseAdapter->withChannel($channel));
    }

    /**
     * @param string|\BackedEnum $channel
     */
    public function has($channel): bool
    {
        return true;
    }
}
