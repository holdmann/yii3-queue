<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Exception\AdapterConfiguration;

use InvalidArgumentException;
use Throwable;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\QueueFactory;

class ChannelIncorrectlyConfigured extends InvalidArgumentException implements FriendlyExceptionInterface
{
    /**
     * @var string
     */
    private $channel;

    /**
     * ChannelIncorrectlyConfigured constructor.
     *
     * @param mixed|object $definition
     * @param Throwable|null $previous
     */
    public function __construct(string $channel, $definition, int $code = 0, ?Throwable $previous = null)
    {
        $adapterClass = AdapterInterface::class;
        $realType = get_debug_type($definition);
        $message = "Channel \"$channel\" is not properly configured: definition must return $adapterClass, $realType returned";

        $this->channel = $channel;
        parent::__construct($message, $code, $previous);
    }

    public function getName(): string
    {
        return 'Incorrect queue channel configuration';
    }

    public function getSolution(): ?string
    {
        $factoryClass = QueueFactory::class;

        return <<<SOLUTION
            You tried to get a Queue object for channel "$this->channel" which is incorrectly configured.
            Please take a look to the documentation for the $factoryClass "\$definitions" constructor parameter.
            SOLUTION;
    }
}
