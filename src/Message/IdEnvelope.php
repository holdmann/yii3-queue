<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

/**
 * ID envelope allows to identify a message.
 */
final class IdEnvelope implements EnvelopeInterface
{
    use EnvelopeTrait;

    public const MESSAGE_ID_KEY = 'yii-message-id';
    private MessageInterface $message;
    /**
     * @var string|int|null
     */
    private $id = null;
    /**
     * @param string|int|null $id
     */
    public function __construct(MessageInterface $message, $id = null)
    {
        $this->message = $message;
        $this->id = $id;
    }

    public static function fromMessage(MessageInterface $message): self
    {
        return new self($message, $message->getMetadata()[self::MESSAGE_ID_KEY] ?? null);
    }

    /**
     * @param string|int|null $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return string|int|null
     */
    public function getId()
    {
        return $this->id ?? $this->message->getMetadata()[self::MESSAGE_ID_KEY] ?? null;
    }

    private function getEnvelopeMetadata(): array
    {
        return [self::MESSAGE_ID_KEY => $this->getId()];
    }
}
