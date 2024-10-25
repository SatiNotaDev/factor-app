<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Cache\CacheInterface;

class ClearCacheCommand extends Command
{
    protected static $defaultName = 'app:clear-cache';

    private $cache;

    public function __construct(CacheInterface $cache)
    {
        parent::__construct();
        $this->cache = $cache;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Clears the application cache.')
            ->setHelp('This command allows you to clear the application cache...');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->cache->clear();
        $output->writeln('Cache cleared successfully.');

        return Command::SUCCESS;
    }
}
