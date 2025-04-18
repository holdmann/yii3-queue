<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use BackedEnum;
use LogicException;
use Throwable;
use Yiisoft\Queue\ChannelNormalizer;

use function sprintf;

/**
 * Thrown when channel is not found.
 */
final class ChannelNotFoundException extends LogicException implements QueueProviderException
{
    /**
     * @param string|\BackedEnum $channel
     */
    public function __construct($channel, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(
            sprintf('Channel "%s" not found.', ChannelNormalizer::normalize($channel)),
            $code,
            $previous,
        );
    }
}
