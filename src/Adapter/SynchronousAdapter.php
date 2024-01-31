<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Adapter;

use InvalidArgumentException;
use Yiisoft\Queue\Enum\JobStatus;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\QueueFactory;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Worker\WorkerInterface;
use Yiisoft\Queue\Message\IdEnvelope;

final class SynchronousAdapter implements AdapterInterface
{
    /**
     * @var mixed[]
     */
    private $messages = [];
    /**
     * @var int
     */
    private $current = 0;
    /**
     * @var \Yiisoft\Queue\Worker\WorkerInterface
     */
    private $worker;
    /**
     * @var \Yiisoft\Queue\QueueInterface
     */
    private $queue;
    /**
     * @var string
     */
    private $channel = QueueFactory::DEFAULT_CHANNEL_NAME;
    public function __construct(WorkerInterface $worker, QueueInterface $queue, string $channel = QueueFactory::DEFAULT_CHANNEL_NAME)
    {
        $this->worker = $worker;
        $this->queue = $queue;
        $this->channel = $channel;
    }

    public function __destruct()
    {
        $this->runExisting(function (MessageInterface $message): bool {
            $this->worker->process($message, $this->queue);

            return true;
        });
    }

    public function runExisting(callable $handlerCallback): void
    {
        $result = true;
        while (isset($this->messages[$this->current]) && $result === true) {
            $result = $handlerCallback($this->messages[$this->current]);
            unset($this->messages[$this->current]);
            $this->current++;
        }
    }

    /**
     * @param string|int $id
     */
    public function status($id): JobStatus
    {
        $id = (int) $id;

        if ($id < 0) {
            throw new InvalidArgumentException('This adapter IDs start with 0.');
        }

        if ($id < $this->current) {
            return JobStatus::done();
        }

        if (isset($this->messages[$id])) {
            return JobStatus::waiting();
        }

        throw new InvalidArgumentException('There is no message with the given ID.');
    }

    public function push(MessageInterface $message): MessageInterface
    {
        $key = count($this->messages) + $this->current;
        $this->messages[] = $message;

        return new IdEnvelope($message, $key);
    }

    public function subscribe(callable $handlerCallback): void
    {
        $this->runExisting($handlerCallback);
    }

    /**
     * @return $this
     */
    public function withChannel(string $channel): \Yiisoft\Queue\Adapter\AdapterInterface
    {
        if ($channel === $this->channel) {
            return $this;
        }

        $new = clone $this;
        $new->channel = $channel;
        $new->messages = [];

        return $new;
    }
}
