<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Integration\Support;

use Yiisoft\Queue\Message\MessageHandlerInterface;
use Yiisoft\Queue\Message\MessageInterface;

final class TestHandler implements MessageHandlerInterface
{
    public array $messagesProcessed = [];
    public function __construct(array $messagesProcessed = [])
    {
        $this->messagesProcessed = $messagesProcessed;
    }

    public function handle(MessageInterface $message): void
    {
        $this->messagesProcessed[] = $message->getData();
    }
}
