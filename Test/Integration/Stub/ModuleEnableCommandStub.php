<?php
declare(strict_types=1);

namespace Yireo\EnableModuleSequence\Test\Integration\Stub;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ModuleEnableCommandStub extends Command
{
    public array $modules = [];

    protected function configure(): void
    {
        $this->setName('module:enable');
        $this->addArgument('module', InputArgument::IS_ARRAY);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->modules = $input->getArgument('module');
        return Command::SUCCESS;
    }
}
