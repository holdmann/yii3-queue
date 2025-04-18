<?php

declare(strict_types=1);

namespace Yiisoft\Queue;

use BackedEnum;

/**
 * @internal
 */
final class ChannelNormalizer
{
    /**
     * @param string|\BackedEnum $channel
     */
    public static function normalize($channel): string
    {
        return $channel instanceof BackedEnum ? (string) $channel->value : $channel;
    }
}
