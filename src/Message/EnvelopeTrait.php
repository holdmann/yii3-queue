<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

trait EnvelopeTrait
{
    /**
     * @var \Yiisoft\Queue\Message\MessageInterface
     */
    private $message;

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
                self::ENVELOPE_STACK_KEY => array_merge(
                    $this->message->getMetadata()[self::ENVELOPE_STACK_KEY] ?? [],
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
