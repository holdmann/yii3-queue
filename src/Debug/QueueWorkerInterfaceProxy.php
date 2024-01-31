<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Debug;

use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Worker\WorkerInterface;

final class QueueWorkerInterfaceProxy implements WorkerInterface
{
    /**
     * @var \Yiisoft\Queue\Worker\WorkerInterface
     */
    private $worker;
    /**
     * @var \Yiisoft\Queue\Debug\QueueCollector
     */
    private $collector;
    public function __construct(WorkerInterface $worker, QueueCollector $collector)
    {
        $this->worker = $worker;
        $this->collector = $collector;
    }
    public function process(MessageInterface $message, QueueInterface $queue): MessageInterface
    {
        $this->collector->collectWorkerProcessing($message, $queue);
        return $this->worker->process($message, $queue);
    }
}
