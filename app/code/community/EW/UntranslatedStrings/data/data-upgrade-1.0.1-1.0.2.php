<?php
$exportDirectory = Mage::getBaseDir('var') . DS . 'export' . DS . 'ew_untranslatedstrings' . DS ;
mkdir($exportDirectory);
chmod($exportDirectory, 755);