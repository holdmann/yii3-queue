<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\FailureHandling;

use Yiisoft\Queue\Message\EnvelopeInterface;
use Yiisoft\Queue\Message\EnvelopeTrait;
use Yiisoft\Queue\Message\MessageInterface;

final class FailureEnvelope implements EnvelopeInterface
{
    use EnvelopeTrait;
    /**
     * @var \Yiisoft\Queue\Message\MessageInterface
     */
    private $message;
    /**
     * @var mixed[]
     */
    private $meta = [];
    public function __construct(MessageInterface $message, array $meta = [])
    {
        $this->message = $message;
        $this->meta = $meta;
    }

    public function getMetadata(): array
    {
        return array_merge($this->message->getMetadata(), $this->meta);
    }
}
