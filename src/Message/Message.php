<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

final class Message implements MessageInterface
{
    private string $handlerName;
    /**
     * @var mixed
     */
    private $data;
    /**
     * @var array
     */
    private array $metadata = [];
    /**
     * @param mixed $data Message data, encodable by a queue adapter
     * @param array $metadata Message metadata, encodable by a queue adapter
     * @param string|null $id Message id
     */
    public function __construct(string $handlerName, $data, array $metadata = [])
    {
        $this->handlerName = $handlerName;
        $this->data = $data;
        $this->metadata = $metadata;
    }

    /**
     * @param mixed $data
     */
    public static function fromData(string $handlerName, $data, array $metadata = []): MessageInterface
    {
        return new self($handlerName, $data, $metadata);
    }

    public function getHandlerName(): string
    {
        return $this->handlerName;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function withMetadata(array $metadata): self
    {
        $instance = clone $this;
        $instance->metadata = $metadata;

        return $instance;
    }
}
