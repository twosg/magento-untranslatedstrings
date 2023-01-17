<?php

/**
 * Class EW_UntranslatedStrings_Model_Export_AllStrings
 */
class EW_UntranslatedStrings_Model_Export_AllStrings extends EW_UntranslatedStrings_Model_Export
{
    /**
     * @var array with prepared strings by locale
     */
    protected $preparedStrings = array();
    /**
     * @var Varien_Data_Collection collection of untranslated strings
     */
    protected $collection;
    /**
     * @var array all locales that are used
     */
    protected $locales;
    /**
     * @var string All strings to save them once
     */
    protected $stringToSave;

    /**
     * @throws Mage_Core_Exception
     */
    public function _construct() {
        parent::_construct();
        $this->collection = Mage::getModel('ew_untranslatedstrings/string')->getCollection()->load();
        foreach (Mage::app()->getStores() as $store) {
            /** @var $store Mage_Core_Model_Store */
            $locale = $store->getLocale();
            $this->locales[$locale] = $locale;
        }

        if ($this->collection->count() == 0) {
            Mage::throwException('No untranslated strings logged to be exported.');
        }

    }

    /**
     * Save untranslated string to file.
     *
     * @throws Mage_Core_Exception
     */
    public function exportAllReportedStrings() {

        $this->prepareBatchExport();

        foreach ($this->preparedStrings as $locale) {
            foreach ($locale as $module) {
                foreach ($module as $string) {
                    $this->string = $string['text'];
                    $this->locale = $string['locale'];
                    $this->module = $string['module'];
                    $file = $this->exportDir . DS . $this->locale . DS;

                    if (!$this->mergeCsvFiles) { // Process accordingly to configuration
                        $file .= $this->module . '.csv';
                    } elseif ($this->mergeCsvFiles) {
                        $file .= 'ew_untranslatedstrings.csv';
                    }

                    $this->translationFile = $file;
                    $this->checkExportDirsFiles($this->locale);
                    $this->prepareString();

                    $this->processSeparateFile($file);
                }
            }
            $this->processMergedFile();
        }
    }

    /**
     * Process translation to single file for each module.
     *
     * @param string $file
     *
     * @throws Mage_Core_Exception
     */
    public function processSeparateFile($file) {
        if (!$this->mergeCsvFiles) { // Process accordingly to configuration
            try {
                $this->saveStringsToFile($file);
                $this->stringToSave = '';
            } catch (Exception $e) {
                Mage::throwException($e->getMessage());
            }
        }
    }

    /**
     * Process merged files.
     *
     * @throws Mage_Core_Exception
     */
    public function processMergedFile() {
        if ($this->mergeCsvFiles) { // Process accordingly to configuration
            try {
                $file = $this->exportDir . DS . 'ew_untranslatedstrings.csv';
                $this->saveStringsToFile($file);
                $this->stringToSave = '';
            } catch (Exception $e) {
                Mage::throwException($e->getMessage());
            }
        }
    }

    /**
     * Save strings to file
     *
     * @param string $file full path to file
     *
     * @return void
     */
    public function saveStringsToFile($file) {
        if ($this->stringToSave !== '') {
            file_put_contents(
                $file,
                $this->stringToSave
            );
        }
    }

    /**
     * Prepare text to save with multiple strings and line breaking inside.
     *
     * @throws Mage_Core_Exception
     */
    public function prepareString() {
        $this->checkExportDirsFiles($this->locale);
        try {
            if (!$this->isStringExcluded()) {
                $this->stringToSave .= '"' . $this->string . '","' . $this->string . '"' . PHP_EOL;
            }
        } catch (Exception $e) {
            Mage::throwException('Can not update or create untranslated strings file.');
        }
    }

    /**
     * Prepare collection for export by creating an array
     * which key's are locale.
     */
    public function prepareBatchExport() {
        foreach ($this->collection as $string) {
            /** @var $string EW_UntranslatedStrings_Model_String */
            $this->preparedStrings[$string->getLocale()][$string->getTranslationModule()][] = array(
                'text'   => $string->getUntranslatedString(),
                'module' => $string->getTranslationModule(),
                'locale' => $string->getLocale(),

            );
        }
    }


}