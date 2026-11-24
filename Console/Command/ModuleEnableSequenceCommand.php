<?php
declare(strict_types=1);

namespace Yireo\EnableModuleSequence\Console\Command;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use SimpleXMLElement;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ModuleEnableSequenceCommand extends Command
{
    public function __construct(
        private readonly ComponentRegistrar $componentRegistrar,
        private readonly FileDriver $fileDriver,
        ?string $name = null
    ) {
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

        $this->addOption(
            'dry-run',
            'd',
            InputOption::VALUE_NONE,
            'Only list the modules that would be enabled'
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $moduleNames = $input->getArgument('module');

        $moduleSequence = [];
        foreach ($moduleNames as $moduleName) {
            if (!$this->isModulePresent($moduleName)) {
                $output->writeln(
                    '<comment>Module "'.OutputFormatter::escape($moduleName).'" is not present, skipping</comment>'
                );
                continue;
            }

            $this->collectModuleSequence($moduleName, $moduleSequence);
        }

        if (empty($moduleSequence)) {
            return Command::SUCCESS;
        }

        $application = $this->getApplication();
        if ($application === null || !$application->has('module:enable')) {
            $output->writeln('<error>Command "module:enable" is not available</error>');
            return Command::FAILURE;
        }

        $output->writeln('<info>Modules to be enabled:</info>');
        foreach ($moduleSequence as $moduleName) {
            $output->writeln(' - '.OutputFormatter::escape($moduleName));
        }

        if ($input->getOption('dry-run')) {
            return Command::SUCCESS;
        }

        if ($input->isInteractive() && !$this->confirm($input, $output)) {
            $output->writeln('<comment>Aborted, no modules were enabled</comment>');
            return Command::SUCCESS;
        }

        $enableInput = new ArrayInput([
            'module' => array_values($moduleSequence)
        ]);

        $enableInput->setInteractive(false);
        return $application->find('module:enable')->run($enableInput, $output);
    }

    private function confirm(InputInterface $input, OutputInterface $output): bool
    {
        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Enable these modules? [Y/n] ', true);

        return (bool)$questionHelper->ask($input, $output, $question);
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

        $configNode = $this->loadModuleXml($modulePath.'/etc/module.xml');
        if ($configNode === null) {
            return [];
        }

        $moduleSequence = [];
        if ($configNode->module->sequence) {
            foreach ($configNode->module->sequence->module as $sequenceModule) {
                $sequenceModuleName = (string)$sequenceModule['name'];

                // A module sequence is a soft dependency, so skip modules that are not installed
                if (!$this->isModulePresent($sequenceModuleName)) {
                    continue;
                }

                $moduleSequence[] = $sequenceModuleName;
            }
        }

        return $moduleSequence;
    }

    private function loadModuleXml(string $moduleXmlFile): ?SimpleXMLElement
    {
        try {
            if (!$this->fileDriver->isFile($moduleXmlFile)) {
                return null;
            }

            $contents = $this->fileDriver->fileGetContents($moduleXmlFile);
        } catch (FileSystemException $e) {
            return null;
        }

        $useInternalErrors = libxml_use_internal_errors(true);
        try {
            $configNode = simplexml_load_string($contents, SimpleXMLElement::class, LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($useInternalErrors);
        }

        if (!$configNode instanceof SimpleXMLElement || $configNode->getName() !== 'config') {
            return null;
        }

        return $configNode;
    }

    private function isModulePresent(string $moduleName): bool
    {
        return !empty($this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName));
    }
}
