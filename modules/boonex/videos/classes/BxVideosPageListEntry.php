<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Videos Videos
 * @ingroup     UnaModules
 *
 * @{
 */

/**
 * List entry page
 */
class BxVideosPageListEntry extends BxBaseModTextPageBrowse
{
    
    protected $_iListId;
    
    public function __construct($aObject, $oTemplate = false)
    {
        $this->MODULE = 'bx_videos';
        parent::__construct($aObject, $oTemplate);
       
        $CNF = &$this->_oModule->_oConfig->CNF;
        $this->_iListId = null;
        if(bx_get('list_id') === false)
            return false; 
        $this->_iListId = (int)bx_get('list_id');
       
        $oFavorite = BxDolFavorite::getObjectInstance($CNF['OBJECT_FAVORITES'], 0, true);
        $aList = $oFavorite->getQueryObject()->getList(array('type' => 'id', 'list_id' => $this->_iListId));   
        if($this->_iListId == 0){
            $aList['title'] = _t('_sys_txt_default_favorite_list');
        }
        $this->addMarkers(array(
            'title' => $aList['title']
        ));
    }
    
    public function getCode ()
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        $oPrivacy = BxDolPrivacy::getObjectInstance($CNF['OBJECT_PRIVACY_LIST_VIEW']);
        echo $this->_iListId;
        if ($this->_iListId > 0){
            if (!$oPrivacy->check($this->_iListId)){
                $this->_oTemplate->displayAccessDenied('');
                exit;
            }
        }
        return parent::getCode ();
    }
}

/** @} */
