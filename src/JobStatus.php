<?php

declare(strict_types=1);

namespace Yiisoft\Queue;

class JobStatus
{
    public const WAITING = 1;
    public const RESERVED = 2;
    public const DONE = 3;
    public function key(): string
    {
        switch ($this) {
            case self::WAITING:
                return 'waiting';
            case self::RESERVED:
                return 'reserved';
            case self::DONE:
                return 'done';
        }
    }
}
