<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    API API to the UNA backend
 * @ingroup     UnaModules
 *
 * @{
 */

class BxApiModule extends BxDolModule
{
    function __construct(&$aModule)
    {
        parent::__construct($aModule);
    }

    public function serviceGetPublicServices()
    {
        $a = parent::serviceGetPublicServices();
        return array_merge($a, array (
            'Test' => '',
            'GetPage' => '',
        ));
    }

    /**
     * @page public_api API Public
     * @section public_api_api_test /m/oauth2/com/test
     * 
     * Test method to check public API
     * 
     * **HTTP Method:** 
     * `GET`
     *
     * **Request params:**
     * n/a
     *
     * **Response (success):**
     * @code
     * {  
     *    "result": "Test passed."
     * }
     * @endcode
     */
    public function serviceTest()
    {
        return array('result' => 'Test passed.');
    }

    /**
     * @page public_api API Public
     * @section public_api_api_get_page /m/oauth2/com/get_page
     * 
     * Get page with cells and blocks as array
     * 
     * **HTTP Method:** 
     * `POST`
     *
     * **Request params:**
     * - `uri` - page URI
     *
     * **Response (success):**
     * @code
     * {  
     *     "id": "123", // page ID
     *     "layout": "5", // page layout, 5 is simplest page with one cell
     *     "module": "system", // module which this page 
     *     "title": "Test page", // page title
     *     "type": 1, // page type, 1 is for default page with header and footer
     *     "uri": "test", // page URI, which is part of page URL
     *     "elements": {
     *         "cell_1": [
     *             {
     *                 "content": "test content", // block content, it can be array as well
     *                 "designbox_id": "11", // block design, such as padding, border, title
     *                 "hidden_on": "", // not empty block need to be hidden on mobile, or desktop
     *                 "id": "321",
     *                 "module": "system", // module name this block is related to
     *                 "order": "1", // block order
     *                 "title": "Test block", // block title
     *                 "type": "raw" // block type
     *             }
     *      ]
     * }
     * @endcode
     *
     * **Response (error):**
     * @code
     * {  
     *    "error":"short error description here",
     *    "error_description":"long error description here"
     * }
     * @endcode
     */
    public function serviceGetPage()
    {
        $sUri = bx_get('uri');
        $oPage = BxDolPage::getObjectInstanceByURI($sUri);
        if (!$oPage) {
            return array(
                'code' => 404,
                'error' => 'Not Found',
                'desc' => 'This page doesn\'t exist',
            );
        }
        if (!$oPage->isVisiblePage()) {
            return array(
                'code' => 403,
                'error' => 'Forbidden',
                'desc' => 'This page requires special right to be viewed',
            );
        }

        return $oPage->getPage ();
    }
}

/** @} */
