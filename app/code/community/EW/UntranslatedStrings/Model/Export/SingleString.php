<?php

/**
 * Class EW_UntranslatedStrings_Model_Export_SingleString
 */
class EW_UntranslatedStrings_Model_Export_SingleString extends EW_UntranslatedStrings_Model_Export
{

    protected $stringData;

    protected function _construct() {
        parent::_construct();
        if (!$this->autoCreateCsvFiles == 0) {
            return;
        }
    }

    /**
     * Process saving untranslated string into file
     *
     * @param array $stringData
     */
    public function prepareSingleStringExport($stringData) {
        $this->locale = $stringData['locale'];
        $this->string = $stringData['string'];
        $this->module = $stringData['module'];
        if (!$this->mergeCsvFiles) { // Process accordingly to configuration
            $this->exportFileName = $this->module . '.csv';
        }
        $this->translationFile = $this->exportDir . DS . $this->locale . DS . $this->exportFileName;
        $this->checkExportDirsFiles($this->locale);
    }

    /**
     * Save untranslated string to file.
     *
     * @param array $stringData
     *
     * @throws Mage_Core_Exception
     */
    public function exportSingleString($stringData) {

        if (!$this->helper->isEnabled() || !$this->autoCreateCsvFiles) {
            return;
        }
        $this->stringData = $stringData;
        $this->prepareSingleStringExport($stringData);
        try {
            if (!$this->isStringExcluded()) {
                file_put_contents(
                    $this->translationFile,
                    '"' . $this->string . '","' . $this->string . '"' . PHP_EOL,
                    FILE_APPEND
                );
            }
        } catch (Exception $e) {
            Mage::throwException('Can not update or create untranslated strings file.');
        }
    }

}
