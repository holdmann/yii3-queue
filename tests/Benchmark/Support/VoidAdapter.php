<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Benchmark\Support;

use InvalidArgumentException;
use RuntimeException;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Enum\JobStatus;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Message\MessageSerializerInterface;

final class VoidAdapter implements AdapterInterface
{
    /**
     * @var string A serialized message
     */
    public string $message;
    private MessageSerializerInterface $serializer;

    public function __construct(MessageSerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function runExisting(callable $handlerCallback): void
    {
        $handlerCallback($this->serializer->unserialize($this->message));
    }

    /**
     * @param int|string $id
     */
    public function status($id): JobStatus
    {
        throw new InvalidArgumentException();
    }

    public function push(MessageInterface $message): MessageInterface
    {
        $this->serializer->serialize($message);

        return new IdEnvelope($message, 1);
    }

    public function subscribe(callable $handlerCallback): void
    {
        throw new RuntimeException('Method is not implemented');
    }

    public function withChannel(string $channel): AdapterInterface
    {
        throw new RuntimeException('Method is not implemented');
    }
}
