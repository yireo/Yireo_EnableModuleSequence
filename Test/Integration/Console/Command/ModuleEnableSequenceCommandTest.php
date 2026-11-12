<?php
declare(strict_types=1);

namespace Yireo\EnableModuleSequence\Test\Integration\Console\Command;

use Magento\Framework\Console\CommandListInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Yireo\EnableModuleSequence\Console\Command\ModuleEnableSequenceCommand;
use Yireo\EnableModuleSequence\Test\Integration\Stub\ModuleEnableCommandStub;

class ModuleEnableSequenceCommandTest extends TestCase
{
    public function testCommandIsRegisteredInCommandList(): void
    {
        $commands = Bootstrap::getObjectManager()->get(CommandListInterface::class)->getCommands();
        $command = $commands['yireo_enablemodulesequence_module_enable_sequence_command'] ?? null;

        $this->assertInstanceOf(ModuleEnableSequenceCommand::class, $command);
        $this->assertSame('module:sequence', $command->getName());
    }

    public function testExecuteEnablesModuleAfterItsSequence(): void
    {
        $enableCommand = $this->getEnableCommand();
        $commandTester = $this->getCommandTester($enableCommand);
        $commandTester->execute(['module' => ['Magento_Catalog']]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertSame('Magento_Catalog', end($enableCommand->modules));
        $this->assertContains('Magento_Store', $enableCommand->modules);
        $this->assertLessThan(
            array_search('Magento_Catalog', $enableCommand->modules),
            array_search('Magento_Store', $enableCommand->modules)
        );
    }

    public function testExecuteWarnsAboutModulesThatAreNotPresent(): void
    {
        $enableCommand = $this->getEnableCommand();
        $commandTester = $this->getCommandTester($enableCommand);
        $commandTester->execute(['module' => ['Magento_Catalog', 'Foo_NonExistent']]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('Module "Foo_NonExistent" is not present', $commandTester->getDisplay());
        $this->assertContains('Magento_Catalog', $enableCommand->modules);
        $this->assertNotContains('Foo_NonExistent', $enableCommand->modules);
    }

    public function testExecuteSucceedsWhenNoModuleIsPresent(): void
    {
        $enableCommand = $this->getEnableCommand();
        $commandTester = $this->getCommandTester($enableCommand);
        $commandTester->execute(['module' => ['Foo_NonExistent']]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('Module "Foo_NonExistent" is not present', $commandTester->getDisplay());
        $this->assertSame([], $enableCommand->modules);
    }

    private function getEnableCommand(): ModuleEnableCommandStub
    {
        return new ModuleEnableCommandStub();
    }

    private function getCommandTester(Command $enableCommand): CommandTester
    {
        $command = Bootstrap::getObjectManager()->create(ModuleEnableSequenceCommand::class);
        $application = new Application();
        $application->add($enableCommand);
        $application->add($command);

        return new CommandTester($command);
    }
}
