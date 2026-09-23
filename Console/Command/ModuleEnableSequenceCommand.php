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
        $this->setName('module:sequence');
        $this->setDescription('Enable a module and its module sequence at once');

        $this->addArgument(
            'module',
            InputArgument::REQUIRED | InputArgument::IS_ARRAY,
            'Name of one or more modules'
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $moduleNames = $input->getArgument('module');

        $moduleSequence = [];
        foreach ($moduleNames as $moduleName) {
            $this->collectModuleSequence($moduleName, $moduleSequence);
        }

        $cmd = $this->getApplication()->find('module:enable');

        $enableInput = new ArrayInput([
            'module' => array_values($moduleSequence)
        ]);

        $enableInput->setInteractive(false);
        return $cmd->run($enableInput, $output);
    }

    /**
     * Add the given module and all of its sequence modules (recursively) to the collected list,
     * with each dependency listed before the module depending upon it.
     *
     * @param string $moduleName
     * @param array $collected Collected module names, keyed by module name
     * @return void
     */
    private function collectModuleSequence(string $moduleName, array &$collected): void
    {
        if (isset($collected[$moduleName])) {
            return;
        }

        // Claim the module upfront, so that a circular sequence does not cause endless recursion
        $collected[$moduleName] = $moduleName;

        foreach ($this->getModuleSequence($moduleName) as $sequenceModule) {
            $this->collectModuleSequence($sequenceModule, $collected);
        }

        // Move the module after its own dependencies
        unset($collected[$moduleName]);
        $collected[$moduleName] = $moduleName;
    }

    private function getModuleSequence(string $moduleName): array
    {
        $modulePath = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName);
        if (empty($modulePath)) {
            return [];
        }

        $moduleXmlFile = $modulePath.'/etc/module.xml';
        if (!is_file($moduleXmlFile)) {
            return [];
        }

        $configNode = simplexml_load_file($moduleXmlFile);
        if (!$configNode instanceof SimpleXMLElement) {
            return [];
        }

        $moduleSequence = [];
        if ($configNode->module->sequence) {
            foreach ($configNode->module->sequence->module as $sequenceModule) {
                $moduleSequence[] = (string)$sequenceModule['name'];
            }
        }

        return $moduleSequence;
    }
}
