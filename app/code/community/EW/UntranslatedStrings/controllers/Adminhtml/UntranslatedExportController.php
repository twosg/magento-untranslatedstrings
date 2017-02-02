<?php

class EW_UntranslatedStrings_Adminhtml_UntranslatedExportController extends Mage_Adminhtml_Controller_Action {

    public function exportAction()
    {
        
        try {
            Mage::getModel('ew_untranslatedstrings/export_allStrings')->exportAllReportedStrings();
        } catch(Mage_Core_Exception $e) {
            Mage::throwException($e->getMessage());
//            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        Mage::getSingleton('adminhtml/session')->addSuccess('CSV Files with untranslated strings were exported.');
//        Mage::app()->getResponse()->setRedirect(Mage::helper('adminhtml')->getUrl('adminhtml'));
        $this->_redirect('adminhtml/system_config/edit/section/dev');

    }

}