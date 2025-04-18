<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

trait EnvelopeTrait
{
    private MessageInterface $message;

    /**
     * A mirror of {@see MessageInterface::fromData()}
     */
    abstract public static function fromMessage(MessageInterface $message): self;

    /**
     * @param mixed $data
     */
    public static function fromData(string $handlerName, $data, array $metadata = []): MessageInterface
    {
        return static::fromMessage(Message::fromData($handlerName, $data, $metadata));
    }

    public function getMessage(): MessageInterface
    {
        return $this->message;
    }

    public function withMessage(MessageInterface $message): self
    {
        $instance = clone $this;
        $instance->message = $message;

        return $instance;
    }

    public function getHandlerName(): string
    {
        return $this->message->getHandlerName();
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->message->getData();
    }

    public function getMetadata(): array
    {
        return array_merge(
            $this->message->getMetadata(),
            [
                EnvelopeInterface::ENVELOPE_STACK_KEY => array_merge(
                    $this->message->getMetadata()[EnvelopeInterface::ENVELOPE_STACK_KEY] ?? [],
                    [self::class],
                ),
            ],
            $this->getEnvelopeMetadata(),
        );
    }

    public function getEnvelopeMetadata(): array
    {
        return [];
    }
}
