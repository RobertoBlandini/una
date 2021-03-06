<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Developer Developer
 * @ingroup     UnaModules
 *
 * @{
 */

class BxDevTemplate extends BxDolModuleTemplate
{
    function __construct(&$oConfig, &$oDb)
    {
        parent::__construct($oConfig, $oDb);

        $this->addStudioCss(array('main.css'));
    }

    function displayPageContent($sPage, $oContent)
    {
        $this->addStudioCss($oContent->getPageCss(), false, false);
        $this->addStudioJs($oContent->getPageJs(), false, false);

        $sMenu = $oContent->getPageMenu();
        $sContent = $oContent->getPageJsCode() . $oContent->getPageCode();
        if(in_array($sPage, array(BX_DEV_TOOLS_SETTINGS)) || empty($sMenu)) {
            $this->addStudioInjection('injection_body_style', 'text', ' bx-dev-page-body-single');
            return $sContent;
        }

        $this->addStudioInjection('injection_body_style', 'text', ' bx-dev-page-body-columns');
        return $this->parseHtmlByName('page_content.html', array(
            'page_menu_code' => $sMenu,
            'page_main_code' => $sContent
        ));
    }
}

/** @} */
