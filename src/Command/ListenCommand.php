<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Queue\QueueFactory;
use Yiisoft\Queue\QueueFactoryInterface;

final class ListenCommand extends Command
{
    protected static $defaultName = 'queue:listen';
    protected static $defaultDescription = 'Listens the queue and executes messages as they come. Needs to be stopped manually.';
    private QueueFactoryInterface $queueFactory;

    public function __construct(QueueFactoryInterface $queueFactory)
    {
        $this->queueFactory = $queueFactory;
        parent::__construct();
    }

    public function configure(): void
    {
        $this->addArgument(
            'channel',
            InputArgument::OPTIONAL,
            'Queue channel name to connect to',
            QueueFactory::DEFAULT_CHANNEL_NAME
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->queueFactory
            ->get($input->getArgument('channel'))
            ->listen();

        return 0;
    }
}
