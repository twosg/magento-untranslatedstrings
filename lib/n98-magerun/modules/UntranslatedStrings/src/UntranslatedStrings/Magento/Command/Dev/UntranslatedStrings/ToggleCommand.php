<?php

namespace UntranslatedStrings\Magento\Command\Dev\UntranslatedStrings;

use N98\Magento\Command\AbstractMagentoCommand;
use N98\Util\Console\Helper\ParameterHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ToggleCommand extends AbstractMagentoCommand
{
    protected $_configPath;

    protected function configure()
    {
        $this->setName('dev:untranslated-strings:toggle')
            ->setDescription('Toggle untranslated strings logging [EW_UntranslatedStrings]');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return mixed
     */
    protected function _initStore(InputInterface $input, OutputInterface $output)
    {
        /** @var ParameterHelper $parameterHelper */
        $parameterHelper = $this->getHelper('parameter');
        return $parameterHelper->askStore($input, $output, 'store', $this->withAdminStore);
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->initMagento()) {
            $this->_configPath = \EW_UntranslatedStrings_Helper_Data::CONFIG_PATH_ENABLED;

            $store = $this->_initStore($input, $output);

            if (\Mage::getStoreConfig($this->_configPath, $store)) {
                $newStatus = 0;
            } else {
                $newStatus = 1;
            }

            \Mage::getConfig()->saveConfig($this->_configPath, $newStatus, 'stores', $store->getId())->cleanCache();

            $output->writeln('<comment>Untranslated strings logging is ' . ($newStatus ? 'enabled' : 'disabled') . ' for ' . $store->getName() . ' [' . $store->getCode() . ']</comment>');
        }
    }

}
