<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    BaseGeneral Base classes for modules
 * @ingroup     UnaModules
 *
 * @{
 */

/**
 * Entry forms helper functions
 */
class BxBaseModGeneralFormsEntryHelper extends BxDolProfileForms
{
    protected $_oModule;

    public function __construct($oModule)
    {
        parent::__construct();
        $this->_oModule = $oModule;
    }

    public function getObjectStorage()
    {
        return BxDolStorage::getObjectInstance($this->_oModule->_oConfig->CNF['OBJECT_STORAGE']);
    }

    public function getObjectFormAdd ()
    {
        return BxDolForm::getObjectInstance($this->_oModule->_oConfig->CNF['OBJECT_FORM_ENTRY'], $this->_oModule->_oConfig->CNF['OBJECT_FORM_ENTRY_DISPLAY_ADD'], $this->_oModule->_oTemplate);
    }

    public function getObjectFormEdit ($sDisplay = false)
    {
    	$CNF = &$this->_oModule->_oConfig->CNF;

        if (false === $sDisplay)
            $sDisplay = $CNF['OBJECT_FORM_ENTRY_DISPLAY_EDIT'];

        return BxDolForm::getObjectInstance($CNF['OBJECT_FORM_ENTRY'], $sDisplay, $this->_oModule->_oTemplate);
    }

	public function getObjectFormView ($sDisplay = false)
    {
    	$CNF = &$this->_oModule->_oConfig->CNF;

        if (false === $sDisplay)
            $sDisplay = $CNF['OBJECT_FORM_ENTRY_DISPLAY_VIEW'];

        return BxDolForm::getObjectInstance($CNF['OBJECT_FORM_ENTRY'], $sDisplay, $this->_oModule->_oTemplate);
    }

    public function getObjectFormDelete ()
    {
        return BxDolForm::getObjectInstance($this->_oModule->_oConfig->CNF['OBJECT_FORM_ENTRY'], $this->_oModule->_oConfig->CNF['OBJECT_FORM_ENTRY_DISPLAY_DELETE'], $this->_oModule->_oTemplate);
    }

    public function viewDataEntry ($iContentId)
    {
        // get content data and profile info
        list ($oProfile, $aContentInfo) = $this->_getProfileAndContentData($iContentId);
        if (!$aContentInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

        // check access
        if (CHECK_ACTION_RESULT_ALLOWED !== ($sMsg = $this->_oModule->checkAllowedView($aContentInfo)))
            return MsgBox($sMsg);

        return $this->_oModule->_oTemplate->entryText($aContentInfo);
    }

    public function addData ($iProfile, $aValues)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        // check and display form
        $oForm = $this->getObjectFormAdd();
        if (!$oForm)
            return array('code' => 1, 'message' => '_sys_txt_error_occured');

        $oForm->aFormAttrs['method'] = BX_DOL_FORM_METHOD_SPECIFIC;
        $oForm->aParams['csrf']['disable'] = true;
        if(!empty($oForm->aParams['db']['submit_name']))
            $aValues[$oForm->aParams['db']['submit_name']] = $oForm->aInputs[$oForm->aParams['db']['submit_name']]['value'];

        $oForm->initChecker(array(), $aValues);
        if (!$oForm->isSubmittedAndValid())
            return array('code' => 2, 'message' => '_sys_txt_error_occured');

        // insert data into database
        $aValsToAdd = array ();
        if(isset($CNF['FIELD_AUTHOR']))
            $aValsToAdd[$CNF['FIELD_AUTHOR']] = $iProfile;

        $iContentId = $oForm->insert($aValsToAdd);
        if (!$iContentId) {
            if (!$oForm->isValid())
                return array('code' => 2, 'message' => '_sys_txt_error_occured');
            else
                return array('code' => 3, 'message' => '_sys_txt_error_entry_creation');
        }

        $sResult = $this->onDataAddAfter(BxDolProfile::getInstance($iProfile)->getAccountId(), $iContentId);
        if($sResult)
            return array('code' => 4, 'message' => $sResult);

        list ($oProfile, $aContentInfo) = $this->_getProfileAndContentData($iContentId);
        return array('code' => 0, 'message' => '', 'content' => $aContentInfo);
    }

    public function addDataForm ()
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        // check access
        if (CHECK_ACTION_RESULT_ALLOWED !== ($sMsg = $this->_oModule->checkAllowedAdd())) {
            $oProfile = BxDolProfile::getInstance();
            $aProfileInfo = $oProfile->getInfo();
            if ($aProfileInfo['type'] == 'system' && is_subclass_of($this->_oModule, 'BxBaseModProfileModule') && $this->_oModule->serviceActAsProfile()) // special check for system profile is needed, because of incorrect error message
                return MsgBox(_t('_sys_txt_access_denied'));
            else
                return MsgBox($sMsg);
        }

        // check and display form
        $oForm = $this->getObjectFormAdd();
        if (!$oForm)
            return MsgBox(_t('_sys_txt_error_occured'));

        $oForm->initChecker();

        if (!$oForm->isSubmittedAndValid())
            return $oForm->getCode();

        // insert data into database
        $aValsToAdd = array ();
        $iContentId = $oForm->insert ($aValsToAdd);
        if (!$iContentId) {
            if (!$oForm->isValid())
                return $oForm->getCode();
            else
                return MsgBox(_t('_sys_txt_error_entry_creation'));
        }

        $sResult = $this->onDataAddAfter (getLoggedId(), $iContentId);
        if ($sResult)
            return $sResult;

        // perform action
        $this->_oModule->checkAllowedAdd(true);

        // redirect
        list ($oProfile, $aContentInfo) = $this->_getProfileAndContentData($iContentId);
        $this->redirectAfterAdd($aContentInfo);
    }
    
    protected function redirectAfterAdd($aContentInfo)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;
        $this->_redirectAndExit('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $aContentInfo[$CNF['FIELD_ID']]);
    }

    public function editDataForm ($iContentId, $sDisplay = false)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        // get content data and profile info
        list ($oProfile, $aContentInfo) = $this->_getProfileAndContentData($iContentId);
        if (!$aContentInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

        // check access
        if (CHECK_ACTION_RESULT_ALLOWED !== ($sMsg = $this->_oModule->checkAllowedEdit($aContentInfo)))
            return MsgBox($sMsg);

        // check and display form
        $oForm = $this->getObjectFormEdit($sDisplay);
        if (!$oForm)
            return MsgBox(_t('_sys_txt_error_occured'));

        $aSpecificValues = array();        
        if (!empty($CNF['OBJECT_METATAGS'])) {
            $oMetatags = BxDolMetatags::getObjectInstance($CNF['OBJECT_METATAGS']);
            if ($oMetatags->locationsIsEnabled())
                $aSpecificValues = $oMetatags->locationGet($iContentId, empty($CNF['FIELD_LOCATION_PREFIX']) ? '' : $CNF['FIELD_LOCATION_PREFIX']);
        }
        $oForm->initChecker($aContentInfo, $aSpecificValues);

        if (!$oForm->isSubmittedAndValid())
            return $oForm->getCode();

        // update data in the DB
        $aTrackTextFieldsChanges = null;

        $this->onDataEditBefore ($aContentInfo[$CNF['FIELD_ID']], $aContentInfo, $aTrackTextFieldsChanges);

        if (!$oForm->update ($aContentInfo[$CNF['FIELD_ID']], array(), $aTrackTextFieldsChanges)) {
            if (!$oForm->isValid())
                return $oForm->getCode();
            else
                return MsgBox(_t('_sys_txt_error_entry_update'));
        }

        $sResult = $this->onDataEditAfter ($aContentInfo[$CNF['FIELD_ID']], $aContentInfo, $aTrackTextFieldsChanges, $oProfile, $oForm);
        if ($sResult)
            return $sResult;

        // perform action
        $this->_oModule->checkAllowedEdit($aContentInfo, true);

        // redirect
        $this->redirectAfterEdit($aContentInfo);
    }

    protected function redirectAfterEdit($aContentInfo)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;
        $this->_redirectAndExit('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $aContentInfo[$CNF['FIELD_ID']]);
    }

    public function deleteDataForm ($iContentId, $sDisplay = false)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if (false === $sDisplay)
            $sDisplay = $CNF['OBJECT_FORM_ENTRY_DISPLAY_DELETE'];

        // get content data and profile info
        list ($oProfile, $aContentInfo) = $this->_getProfileAndContentData($iContentId);
        if (!$aContentInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

        // check access
        if (CHECK_ACTION_RESULT_ALLOWED !== ($sMsg = $this->_oModule->checkAllowedDelete($aContentInfo)))
            return MsgBox($sMsg);

        // check and display form
        $oForm = BxDolForm::getObjectInstance($CNF['OBJECT_FORM_ENTRY'], $sDisplay, $this->_oModule->_oTemplate);
        if (!$oForm)
            return MsgBox(_t('_sys_txt_error_occured'));

        $oForm->initChecker($aContentInfo);

        if (!$oForm->isSubmittedAndValid())
            return $oForm->getCode();

        if ($sError = $this->deleteData($aContentInfo[$CNF['FIELD_ID']], $aContentInfo, $oProfile, $oForm))
            return MsgBox($sError);

        // perform action
        $this->_oModule->checkAllowedDelete($aContentInfo, true);

        // redirect
        $this->redirectAfterDelete($aContentInfo);
    }

    protected function redirectAfterDelete($aContentInfo)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;
        $this->_redirectAndExit($CNF['URL_HOME'], true, array(
            'account_id' => getLoggedId(),
            'profile_id' => bx_get_logged_profile_id(),
        ));
    }

    /**
     * Delete data entry
     * @param $iContentId entry id
     * @param $oForm optional content info array
     * @param $aContentInfo optional content info array
     * @param $oProfile optional content author profile
     * @return error string on error or empty string on success
     */
    public function deleteData ($iContentId, $aContentInfo = false, $oProfile = null, $oForm = null)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if (!$aContentInfo || !$oProfile)
            list ($oProfile, $aContentInfo) = $this->_getProfileAndContentData($iContentId);

        if (!$aContentInfo)
            return _t('_sys_txt_error_entry_is_not_defined');

        if (!$oForm)
            $oForm = BxDolForm::getObjectInstance($CNF['OBJECT_FORM_ENTRY'], $CNF['OBJECT_FORM_ENTRY_DISPLAY_DELETE'], $this->_oModule->_oTemplate);

        if (!$oForm->delete ($aContentInfo[$CNF['FIELD_ID']], $aContentInfo))
            return _t('_sys_txt_error_entry_delete');

        if ($sResult = $this->onDataDeleteAfter ($aContentInfo[$CNF['FIELD_ID']], $aContentInfo, $oProfile))
            return $sResult;

        // create an alert
        bx_alert($this->_oModule->getName(), 'deleted', $aContentInfo[$CNF['FIELD_ID']]);

        return '';
    }

    public function viewDataForm ($iContentId, $sDisplay = false)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        // get content data and profile info
        list ($oProfile, $aContentInfo) = $this->_getProfileAndContentData($iContentId);
        if (!$aContentInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

        // check access
        if ($sMsg = $this->_processPermissionsCheckForViewDataForm ($aContentInfo, $oProfile))
            return MsgBox($sMsg);

        // get form
        $oForm = $this->getObjectFormView($sDisplay);
        if (!$oForm)
            return MsgBox(_t('_sys_txt_error_occured'));

        // process metatags
        if (!empty($CNF['OBJECT_METATAGS'])) {
            $oMetatags = BxDolMetatags::getObjectInstance($CNF['OBJECT_METATAGS']);
            if ($oMetatags->keywordsIsEnabled()) {
                $aFields = $oMetatags->keywordsFields($aContentInfo, $CNF, $CNF['OBJECT_FORM_ENTRY_DISPLAY_VIEW']);
                $oForm->setMetatagsKeywordsData($iContentId, $aFields, $oMetatags);
            }
        }        

        // display profile
        $oForm->initChecker($aContentInfo);
        return $oForm->getCode();
    }

    protected function _processPermissionsCheckForViewDataForm ($aContentInfo, $oProfile)
    {
        if (CHECK_ACTION_RESULT_ALLOWED !== ($sMsg = $this->_oModule->checkAllowedView($aContentInfo)))
            return $sMsg;

        return '';
    }

    public function onDataDeleteAfter ($iContentId, $aContentInfo, $oProfile)
    {
        return '';
    }

    public function onDataEditBefore ($iContentId, $aContentInfo, &$aTrackTextFieldsChanges)
    {
    }

    public function onDataEditAfter ($iContentId, $aContentInfo, $aTrackTextFieldsChanges, $oProfile, $oForm)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if (!empty($CNF['OBJECT_METATAGS'])) { // && isset($aTrackTextFieldsChanges['changed_fields'][$CNF['FIELD_TEXT']])) { // TODO: check if aTrackTextFieldsChanges works 
            list ($oProfile, $aContentInfo) = $this->_getProfileAndContentData($iContentId);
            $oMetatags = BxDolMetatags::getObjectInstance($CNF['OBJECT_METATAGS']);
            if ($oMetatags->keywordsIsEnabled())
                $oMetatags->keywordsAddAuto($aContentInfo[$CNF['FIELD_ID']], $aContentInfo, $CNF, $CNF['OBJECT_FORM_ENTRY_DISPLAY_EDIT']);
            if ($oMetatags->locationsIsEnabled())
                $oMetatags->locationsAddFromForm($aContentInfo[$CNF['FIELD_ID']], empty($CNF['FIELD_LOCATION_PREFIX']) ? '' : $CNF['FIELD_LOCATION_PREFIX']);
        }

        return '';
    }

    public function onDataAddAfter ($iAccountId, $iContentId)
    {
        $this->_processMetas($iAccountId, $iContentId);

        return '';
    }

    protected function _processMetas($iAccountId, $iContentId)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if (!empty($CNF['OBJECT_METATAGS'])) {
            list ($oProfile, $aContentInfo) = $this->_getProfileAndContentData($iContentId);
            $oMetatags = BxDolMetatags::getObjectInstance($CNF['OBJECT_METATAGS']);
            if ($oMetatags->keywordsIsEnabled() && $aContentInfo)
                $oMetatags->keywordsAddAuto($aContentInfo[$CNF['FIELD_ID']], $aContentInfo, $CNF, $CNF['OBJECT_FORM_ENTRY_DISPLAY_ADD']);
            if ($oMetatags->locationsIsEnabled() && $aContentInfo)
                $oMetatags->locationsAddFromForm($aContentInfo[$CNF['FIELD_ID']], $CNF['FIELD_LOCATION_PREFIX']);
        }
    }
}

/** @} */
