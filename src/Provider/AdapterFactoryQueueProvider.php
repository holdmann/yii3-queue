<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use BackedEnum;
use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Factory\StrictFactory;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\ChannelNormalizer;
use Yiisoft\Queue\QueueInterface;

use function array_key_exists;
use function sprintf;

/**
 * This queue provider create new queue objects based on adapter definitions.
 *
 * @see https://github.com/yiisoft/definitions/
 * @see https://github.com/yiisoft/factory/
 */
final class AdapterFactoryQueueProvider implements QueueProviderInterface
{
    /**
     * @var QueueInterface
     * @readonly
     */
    private QueueInterface $baseQueue;
    /**
     * @psalm-var array<string, QueueInterface|null>
     */
    private array $queues = [];

    /**
     * @readonly
     */
    private StrictFactory $factory;

    /**
     * @param QueueInterface $baseQueue Base queue for queues creation.
     * @param array $definitions Adapter definitions indexed by channel names.
     * @param ContainerInterface|null $container Container to use for dependencies resolving.
     * @param bool $validate If definitions should be validated when set.
     *
     * @psalm-param array<string, mixed> $definitions
     * @throws InvalidQueueConfigException
     */
    public function __construct(
        QueueInterface $baseQueue,
        array $definitions,
        ?ContainerInterface $container = null,
        bool $validate = true
    ) {
        $this->baseQueue = $baseQueue;
        try {
            $this->factory = new StrictFactory($definitions, $container, $validate);
        } catch (InvalidConfigException $exception) {
            throw new InvalidQueueConfigException($exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @param string|\BackedEnum $channel
     */
    public function get($channel): QueueInterface
    {
        $channel = ChannelNormalizer::normalize($channel);

        $queue = $this->getOrTryToCreate($channel);
        if ($queue === null) {
            throw new ChannelNotFoundException($channel);
        }

        return $queue;
    }

    /**
     * @param string|\BackedEnum $channel
     */
    public function has($channel): bool
    {
        $channel = ChannelNormalizer::normalize($channel);
        return $this->factory->has($channel);
    }

    /**
     * @throws InvalidQueueConfigException
     */
    private function getOrTryToCreate(string $channel): ?\Yiisoft\Queue\QueueInterface
    {
        if (array_key_exists($channel, $this->queues)) {
            return $this->queues[$channel];
        }

        if ($this->factory->has($channel)) {
            $adapter = $this->factory->create($channel);
            if (!$adapter instanceof AdapterInterface) {
                throw new InvalidQueueConfigException(
                    sprintf(
                        'Adapter must implement "%s". For channel "%s" got "%s" instead.',
                        AdapterInterface::class,
                        $channel,
                        get_debug_type($adapter),
                    ),
                );
            }
            $this->queues[$channel] = $this->baseQueue->withAdapter($adapter->withChannel($channel));
        } else {
            $this->queues[$channel] = null;
        }

        return $this->queues[$channel];
    }
}
