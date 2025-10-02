<?php
declare(strict_types=1);

namespace Yireo\EnableModuleSequence\Console\Command;

use Magento\Framework\Component\ComponentRegistrar;
use SimpleXMLElement;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;

class ModuleEnableSequenceCommand extends Command
{
    public function __construct(
        private readonly ComponentRegistrar $componentRegistrar,
        ?string $name = null)  {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('module:enable:sequence');
        $this->setDescription('Enable a module and its module sequence at once');

        $this->addArgument(
            'module',
            InputArgument::REQUIRED,
            'Name of the module'
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $moduleName = $input->getArgument('module');
        $moduleSequence = $this->getModuleSequence($moduleName);
        $moduleSequence[] = $moduleName;

        $cmd = $this->getApplication()->find('module:enable');

        $input = new ArrayInput([
            'module' => $moduleSequence
        ]);

        $input->setInteractive(false);
        return $cmd->run($input, $output);
    }

    private function getModuleSequence(string $moduleName): array
    {
        $modulePath = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName);
        if (empty($modulePath)) {
            return [];
        }

        $moduleXmlFile = $modulePath.'/etc/module.xml';

        $configNode = simplexml_load_file($moduleXmlFile);
        $moduleSequence = [];
        if ($configNode->module->sequence) {
            foreach ($configNode->module->sequence->module as $sequenceModule) {
                $moduleSequence[] = (string)$sequenceModule['name'];
            }
        }

        return $moduleSequence;
    }
}
