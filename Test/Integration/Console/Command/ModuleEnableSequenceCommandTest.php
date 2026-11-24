<?php
declare(strict_types=1);

namespace Yireo\EnableModuleSequence\Test\Integration\Console\Command;

use Magento\Framework\Component\ComponentRegistrar;
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
    private const MALFORMED_MODULE = 'YireoTest_MalformedModuleXml';

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
        $commandTester->execute(['module' => ['Magento_Catalog']], ['interactive' => false]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertSame('Magento_Catalog', end($enableCommand->modules));
        $this->assertContains('Magento_Store', $enableCommand->modules);
        $this->assertLessThan(
            array_search('Magento_Catalog', $enableCommand->modules),
            array_search('Magento_Store', $enableCommand->modules)
        );
        $this->assertStringContainsString('Modules to be enabled:', $commandTester->getDisplay());
    }

    public function testExecuteWarnsAboutModulesThatAreNotPresent(): void
    {
        $enableCommand = $this->getEnableCommand();
        $commandTester = $this->getCommandTester($enableCommand);
        $commandTester->execute(['module' => ['Magento_Catalog', 'Foo_NonExistent']], ['interactive' => false]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('Module "Foo_NonExistent" is not present', $commandTester->getDisplay());
        $this->assertContains('Magento_Catalog', $enableCommand->modules);
        $this->assertNotContains('Foo_NonExistent', $enableCommand->modules);
    }

    public function testExecuteSucceedsWhenNoModuleIsPresent(): void
    {
        $enableCommand = $this->getEnableCommand();
        $commandTester = $this->getCommandTester($enableCommand);
        $commandTester->execute(['module' => ['Foo_NonExistent']], ['interactive' => false]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('Module "Foo_NonExistent" is not present', $commandTester->getDisplay());
        $this->assertSame([], $enableCommand->modules);
    }

    public function testExecuteEscapesFormattingTagsInModuleNames(): void
    {
        $enableCommand = $this->getEnableCommand();
        $commandTester = $this->getCommandTester($enableCommand);
        $commandTester->execute(['module' => ['</comment><error>Foo</error>']], ['interactive' => false]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString(
            'Module "</comment><error>Foo</error>" is not present',
            $commandTester->getDisplay()
        );
    }

    public function testDryRunListsModulesWithoutEnablingThem(): void
    {
        $enableCommand = $this->getEnableCommand();
        $commandTester = $this->getCommandTester($enableCommand);
        $commandTester->execute(['module' => ['Magento_Catalog'], '--dry-run' => true], ['interactive' => false]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString(' - Magento_Store', $commandTester->getDisplay());
        $this->assertStringContainsString(' - Magento_Catalog', $commandTester->getDisplay());
        $this->assertSame([], $enableCommand->modules);
    }

    public function testDecliningConfirmationDoesNotEnableModules(): void
    {
        $enableCommand = $this->getEnableCommand();
        $commandTester = $this->getCommandTester($enableCommand);
        $commandTester->setInputs(['no']);
        $commandTester->execute(['module' => ['Magento_Catalog']], ['interactive' => true]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('Aborted', $commandTester->getDisplay());
        $this->assertSame([], $enableCommand->modules);
    }

    public function testAcceptingConfirmationEnablesModules(): void
    {
        $enableCommand = $this->getEnableCommand();
        $commandTester = $this->getCommandTester($enableCommand);
        $commandTester->setInputs(['yes']);
        $commandTester->execute(['module' => ['Magento_Catalog']], ['interactive' => true]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertSame('Magento_Catalog', end($enableCommand->modules));
    }

    public function testExecuteFailsWhenModuleEnableCommandIsMissing(): void
    {
        $command = Bootstrap::getObjectManager()->create(ModuleEnableSequenceCommand::class);
        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['module' => ['Magento_Catalog']], ['interactive' => false]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('Command "module:enable" is not available', $commandTester->getDisplay());
    }

    public function testMalformedModuleXmlIsIgnored(): void
    {
        $this->registerMalformedModule();

        $enableCommand = $this->getEnableCommand();
        $commandTester = $this->getCommandTester($enableCommand);
        $commandTester->execute(['module' => [self::MALFORMED_MODULE]], ['interactive' => false]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertSame([self::MALFORMED_MODULE], $enableCommand->modules);
        $this->assertSame([], libxml_get_errors());
    }

    private function registerMalformedModule(): void
    {
        $componentRegistrar = Bootstrap::getObjectManager()->get(ComponentRegistrar::class);
        if ($componentRegistrar->getPath(ComponentRegistrar::MODULE, self::MALFORMED_MODULE)) {
            return;
        }

        $modulePath = sys_get_temp_dir().'/'.self::MALFORMED_MODULE.'_'.uniqid();
        mkdir($modulePath.'/etc', 0777, true);
        file_put_contents(
            $modulePath.'/etc/module.xml',
            '<config><module name="'.self::MALFORMED_MODULE.'"><sequence><module name="Magento_Store">'
        );

        ComponentRegistrar::register(ComponentRegistrar::MODULE, self::MALFORMED_MODULE, $modulePath);
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
