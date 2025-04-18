<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Adapter;

use BackedEnum;
use InvalidArgumentException;
use Yiisoft\Queue\ChannelNormalizer;
use Yiisoft\Queue\JobStatus;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Worker\WorkerInterface;
use Yiisoft\Queue\Message\IdEnvelope;

final class SynchronousAdapter implements AdapterInterface
{
    private WorkerInterface $worker;
    private QueueInterface $queue;
    private array $messages = [];
    private int $current = 0;
    private string $channel;

    /**
     * @param string|\BackedEnum $channel
     */
    public function __construct(
        WorkerInterface $worker,
        QueueInterface $queue,
        $channel = QueueInterface::DEFAULT_CHANNEL
    ) {
        $this->worker = $worker;
        $this->queue = $queue;
        $this->channel = ChannelNormalizer::normalize($channel);
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
            return JobStatus::DONE;
        }

        if (isset($this->messages[$id])) {
            return JobStatus::WAITING;
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
     * @param string|\BackedEnum $channel
     */
    public function withChannel($channel): self
    {
        $channel = ChannelNormalizer::normalize($channel);

        if ($channel === $this->channel) {
            return $this;
        }

        $new = clone $this;
        $new->channel = $channel;
        $new->messages = [];

        return $new;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }
}
