<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Support;

use Yiisoft\Queue\Message\MessageInterface;

final class TestMessage implements MessageInterface
{
    /**
     * @param mixed $data
     */
    public static function fromData(string $handlerName, $data, array $metadata = []): MessageInterface
    {
        return new self();
    }

    public function getHandlerName(): string
    {
        return 'test';
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return null;
    }

    public function getMetadata(): array
    {
        return [];
    }
}
