<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Consume\Support;

use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\Consume\MessageHandlerConsumeInterface;
use Yiisoft\Queue\Middleware\Consume\MiddlewareConsumeInterface;
use Yiisoft\Queue\Middleware\Consume\ConsumeRequest;

final class TestMiddleware implements MiddlewareConsumeInterface
{
    /**
     * @var string
     */
    private $message = 'New middleware test data';
    public function __construct(string $message = 'New middleware test data')
    {
        $this->message = $message;
    }

    public function processConsume(ConsumeRequest $request, MessageHandlerConsumeInterface $handler): ConsumeRequest
    {
        return $request->withMessage(new Message('test', $this->message));
    }
}
