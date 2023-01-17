<?php

class EW_UntranslatedStrings_Model_Core_Translate extends Mage_Core_Model_Translate
{
    const REGISTRY_KEY = 'ew_untranslatedstrings_string_buffer';

    /** @var array */
    protected $_localesToCheck = null;

    protected $_allowMatchingKeyValuePairs = false;
    protected $_allowLooseDevModuleMode = false;
    protected $_themeStore = null;
    protected $_model = null;
    /**
     * Allow matching key / value translation pairs
     * when loading translations?
     *
     * @return bool
     */
    public function getAllowMatchingKeyValuePairs() {
        return $this->_allowMatchingKeyValuePairs;
    }

    /**
     * Set if matching key / value translation pairs
     * allowed when loading translations.
     *
     * @param bool $allow
     */
    public function setAllowMatchingKeyValuePairs($allow) {
        $this->_allowMatchingKeyValuePairs = (bool)$allow;
    }

    /**
     * Use native "Not allow use translation not related to module"
     * check when loading translations?
     *
     * @return bool
     */
    public function getAllowLooseDevModuleMode() {
        return $this->_allowLooseDevModuleMode;
    }

    /**
     * Set if native "Not allow use translation not related to module"
     * behavior used when loading translations.
     *
     * @param bool $allow
     */
    public function setAllowLooseDevModuleMode($allow) {
        $this->_allowLooseDevModuleMode = (bool)$allow;
    }

    /**
     * Get locales to check and store on local variable
     *
     * @return array
     */
    protected function _getLocalesToCheck() {
        if(is_null($this->_localesToCheck)) {
            $this->_localesToCheck = Mage::helper('ew_untranslatedstrings')->getCheckLocales();
        }

        return $this->_localesToCheck;
    }

    /**
     * If called before init(), override the theme context
     * from which translations will be loaded.
     *
     * @param int $storeId
     */
    public function setThemeContext($storeId) {
        $this->_themeStore = $storeId;
    }


    /**
     * @param string $text
     * @param string $code
     *
     * @return bool
     */
    public function hasTranslation($text, $code) {
        if (array_key_exists($code, $this->getData()) || array_key_exists($text, $this->getData())) {
            return true;
        }
        return false;
    }

    /**
     * Evaluate translated text and code and determine
     * if they are untranslated.
     *
     * @param string $text
     * @param string $code
     */
    protected function _checkTranslatedString($text, $code) {
        Varien_Profiler::start(__CLASS__ . '::' . __FUNCTION__);
        Varien_Profiler::start(EW_UntranslatedStrings_Helper_Data::PROFILER_KEY);

        //loop locale(s) and find gaps
        $untranslatedPhrases = array();
        $this->_model = Mage::getModel('ew_untranslatedstrings/string');
        $createFiles = (bool)Mage::getStoreConfig('dev/translate/untranslated_create_files');
        foreach($this->_getLocalesToCheck() as $locale) {
            if(!Mage::helper('ew_untranslatedstrings')->isTranslated($text,$code,$locale)) {
                $untranslatedPhrases[] = array(
                    'text' => $text,
                    'code' => $code,
                    'locale' => $locale
                );
                if($createFiles) {
                    $string = $this->_model->load($code, 'translation_code');
                    if(!$string) {
                        $module = explode('::', $code)[0];
                        Mage::dispatchEvent('ew_untranslatedstrings_string_found', array(
                            'string' => $text,
                            'module' => $module,
                            'locale' => $locale
                        ));
                    }
                }
            }
        }
        $this->_storeUntranslated($untranslatedPhrases);

        Varien_Profiler::stop(EW_UntranslatedStrings_Helper_Data::PROFILER_KEY);
        Varien_Profiler::stop(__CLASS__ . '::' . __FUNCTION__);
    }

    /**
     * Check for translation gap before returning
     *
     * @param string $text
     * @param string $code
     * @return string
     */
    protected function _getTranslatedString($text, $code)
    {
        if(Mage::helper('ew_untranslatedstrings')->isEnabled()) {
            $this->_checkTranslatedString($text, $code);
        }

        return parent::_getTranslatedString($text, $code);
    }

    /**
     * Rewrite to allow optional key = value in data
     * as well as optionally disabling developer mode check
     *
     * @param array $data
     * @param string $scope
     * @param bool $forceReload
     *
     * @return Mage_Core_Model_Translate
     */
    protected function _addData($data, $scope, $forceReload=false)
    {
        foreach ($data as $key => $value) {
            // BEGIN EDIT: conditionally exclude matching key value pairs
            if(!$this->getAllowMatchingKeyValuePairs()) {
                if ($key === $value) {
                    continue;
                }
            }
            // END EDIT
            $key    = $this->_prepareDataString($key);
            $value  = $this->_prepareDataString($value);
            if ($scope && isset($this->_dataScope[$key]) && !$forceReload ) {
                /**
                 * Checking previous value
                 */
                $scopeKey = $this->_dataScope[$key] . self::SCOPE_SEPARATOR . $key;
                if (!isset($this->_data[$scopeKey])) {
                    if (isset($this->_data[$key])) {
                        $this->_data[$scopeKey] = $this->_data[$key];
                        /**
                         * Not allow use translation not related to module
                         */
                        if (Mage::getIsDeveloperMode()) {
                            // BEGIN EDIT: conditionally exclude module mismatch translations
                            if(!$this->getAllowLooseDevModuleMode()) {
                                unset($this->_data[$key]);
                            }
                            // END EDIT
                        }
                    }
                }
                $scopeKey = $scope . self::SCOPE_SEPARATOR . $key;
                $this->_data[$scopeKey] = $value;
            }
            else {
                $this->_data[$key]     = $value;
                $this->_dataScope[$key]= $scope;
            }
        }
        return $this;
    }

    /**
     * Scrub phrases against excluded phrase patterns
     *
     * @param array $phrases
     * @return array
     */
    protected function _scrubExcludedPhrases(array $phrases) {
        /** @var $patterns array */
        $patterns = Mage::helper('ew_untranslatedstrings')->getExcludePattens();

        if(empty($patterns)) { //quick short circuit if feature not used
            return $phrases;
        }

        $scrubbedPhrases = array();
        foreach($phrases as $phrase) {
            $excluded = false;

            foreach($patterns as $pattern) {
                if(preg_match('/' . $pattern . '/', $phrase['code'])) {
                    $excluded = true;
                    break;
                }
            }

            if(!$excluded) {
                $scrubbedPhrases[] = $phrase;
            }
        }

        return $scrubbedPhrases;
    }

    /**
     * Store phrases to be found and written later
     *
     * @param array $phrases
     */
    protected function _storeUntranslated(array $phrases) {
        $phrases = $this->_scrubExcludedPhrases($phrases);
        foreach($phrases as $phrase) {
            $locale = $phrase['locale'];

            //get array of all locales from registry or create new
            $strings = array();
            if(Mage::registry(self::REGISTRY_KEY)) {
                $strings = Mage::registry(self::REGISTRY_KEY);
                Mage::unregister(self::REGISTRY_KEY); //we're going to set it again in a minute
            }

            //get locale specific section of registry array
            $localeStrings = isset($strings[$locale]) ? $strings[$locale] : array();

            $text = $phrase['text'];
            $code = $phrase['code'];

            $codeParts = explode(Mage_Core_Model_Translate::SCOPE_SEPARATOR, $code);
            $module = $codeParts[0];

            //add new entry
            $localeStrings[] = array(
                'code' => $code,
                'module' => $module,
                'text' => $text,
                'store_id' => Mage::app()->getStore()->getId(),
                'locale' => $locale,
                'url' => Mage::helper('core/url')->getCurrentUrl()
            );

            $strings[$locale] = $localeStrings; //update "big" array


            //whether new or just augmented, set registry key again
            Mage::register(self::REGISTRY_KEY, $strings);
        }
    }

    /**
     * Get theme translation file. If override store set,
     * get file from that store's theme. Otherwise, get current
     * design package's translation file.
     *
     * @return string
     */
    protected function _getThemeTranslationFile() {
        if(!is_null($this->_themeStore)) {
            // Start store emulation process
            $appEmulation = Mage::getSingleton('core/app_emulation');
            $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($this->_themeStore);

            /* @var $design Mage_Core_Model_Design_Package */
            $design = Mage::getModel('core/design_package');
            $file = $design->getLocaleFileName('translate.csv');

            // Stop store emulation process
            $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);

            return $file;
        }

        //fallback to default behavior
        return Mage::getDesign()->getLocaleFileName('translate.csv');
    }

    /**
     * Rewrite to allow theme to be specified
     *
     * @param bool $forceReload
     * @return Mage_Core_Model_Translate
     */
    protected function _loadThemeTranslation($forceReload = false)
    {
        // BEGIN EDIT: call _getThemeTranslationFile() to get theme translate file path
        $file = $this->_getThemeTranslationFile();
        // END EDIT
        $this->_addData($this->_getFileData($file), false, $forceReload);
        return $this;
    }
}