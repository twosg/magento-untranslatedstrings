<?php

/**
 * Class EW_UntranslatedStrings_Model_Export
 */
class EW_UntranslatedStrings_Model_Export extends Mage_Core_Model_Abstract
{

    /**
     * Is export enabled in configuration
     *
     * @var bool $autoCreateCsvFiles
     */
    protected $autoCreateCsvFiles;
    /**
     * Is merging files into one for locale enabled
     *
     * @var bool $mergeCsvFiles
     */
    protected $mergeCsvFiles;

    /**
     *  Export directory path
     *
     * @var string $exportDir
     */
    protected $exportDir;

    /**
     *  Export file name
     *
     * @var string $exportFileName
     */
    protected $exportFileName;
    /**
     *  Translation file path
     *
     * @var string $translationFile
     */
    protected $translationFile;

    /**
     * Untranslated string
     *
     * @var string $string
     */
    protected $string;

    /**
     * Locale for which untranslated string is exported
     *
     * @var string $locale
     */
    protected $locale;

    /**
     *  Module which echo'ed the untranslated string
     *
     * @var string $module
     */
    protected $module;

    /**
     * Helper property to avoid calling Mage::helper()
     *
     * @var EW_UntranslatedStrings_Helper_Data
     */
    protected $helper;


    /**
     * _construct method.
     *
     * @return void
     */
    protected function _construct() {
        parent::_construct();
        $this->helper = Mage::helper('ew_untranslatedstrings');

        $this->autoCreateCsvFiles = $this->helper->isAutoCreateEnabled();
        $this->mergeCsvFiles = $this->helper->isMergingEnabled();

        $this->exportDir = Mage::getBaseDir('var') . DS . 'export' . DS . 'ew_untranslatedstrings'; // add locale to it
        $this->exportFileName = 'ew_untranslatedstrings.csv';

    }


    /**
     * Checks if export directories and files exists
     *
     * @param string $locale Files directory per locale
     */
    public function checkExportDirsFiles($locale = '') {

        if (!is_dir($this->exportDir)) {
            mkdir($this->exportDir, 0777, true);
        }

        if (!is_dir($this->exportDir . DS . $locale)) {
            mkdir($this->exportDir . DS . $locale, 0777, true);
        }
        if (!file_exists($this->translationFile)) {
            touch($this->translationFile);
            chmod($this->translationFile, 0777);
        }
    }

    /**
     * Checks if string was excluded by a pattern in config.
     *
     * @return bool Is string excluded from being logged.
     */
    public function isStringExcluded() {
        $patterns = Mage::helper('ew_untranslatedstrings')->getExcludePattens();
        $excluded = false;
        foreach ($patterns as $pattern) {
            if (preg_match('/' . $pattern . '/', $this->module . '::' . $this->string)) {
                $excluded = true;
                break;
            }
        }
        if ($excluded) {
            return true;
        }

        return false;
    }
}