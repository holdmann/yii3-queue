<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Cli;

class SignalLoop implements LoopInterface
{
    use SoftLimitTrait;

    /**
     * @psalm-suppress UndefinedConstant
     * @psalm-suppress MissingClassConstType
     */
    protected const SIGNALS_EXIT = [SIGHUP, SIGINT, SIGTERM];
    /**
     * @psalm-suppress UndefinedConstant
     * @psalm-suppress MissingClassConstType
     */
    protected const SIGNALS_SUSPEND = [SIGTSTP];
    /**
     * @psalm-suppress UndefinedConstant
     * @psalm-suppress MissingClassConstType
     */
    protected const SIGNALS_RESUME = [SIGCONT];
    /**
     * @var bool
     */
    protected $pause = false;
    /**
     * @var bool
     */
    protected $exit = false;
    /**
     * @var int
     */
    protected $memorySoftLimit = 0;

    /**
     * @param int $memorySoftLimit Soft RAM limit in bytes. The loop won't let you continue to execute the program if
     *     soft limit is reached. Zero means no limit.
     */
    public function __construct(int $memorySoftLimit = 0)
    {
        $this->memorySoftLimit = $memorySoftLimit;
        foreach (self::SIGNALS_EXIT as $signal) {
            pcntl_signal($signal, function () {
                return $this->exit = true;
            });
        }
        foreach (self::SIGNALS_SUSPEND as $signal) {
            pcntl_signal($signal, function () {
                return $this->pause = true;
            });
        }
        foreach (self::SIGNALS_RESUME as $signal) {
            pcntl_signal($signal, function () {
                return $this->pause = false;
            });
        }
    }

    /**
     * Checks signals state.
     *
     * {@inheritdoc}
     */
    public function canContinue(): bool
    {
        if ($this->memoryLimitReached()) {
            return false;
        }

        return $this->dispatchSignals();
    }

    protected function dispatchSignals(): bool
    {
        pcntl_signal_dispatch();

        // Wait for resume signal until loop is suspended
        while ($this->pause && !$this->exit) {
            usleep(10000);
            pcntl_signal_dispatch();
        }

        return !$this->exit;
    }

    protected function getMemoryLimit(): int
    {
        return $this->memorySoftLimit;
    }
}
