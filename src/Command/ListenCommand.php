<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Queue\Provider\QueueProviderInterface;
use Yiisoft\Queue\QueueInterface;

final class ListenCommand extends Command
{
    /**
     * @readonly
     */
    private QueueProviderInterface $queueProvider;
    public function __construct(
        QueueProviderInterface $queueProvider
    ) {
        $this->queueProvider = $queueProvider;
        parent::__construct();
    }
    public function configure(): void
    {
        $this->addArgument(
            'channel',
            InputArgument::OPTIONAL,
            'Queue channel name to connect to',
            QueueInterface::DEFAULT_CHANNEL,
        );
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->queueProvider
            ->get($input->getArgument('channel'))
            ->listen();

        return 0;
    }
}
