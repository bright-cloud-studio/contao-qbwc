<?php

/**
* Bright Cloud Studio's Contao QBWC
*
* Copyright (C) 2024-2025 Bright Cloud Studio
*
* @package    bright-cloud-studio/contao-qbwc
* @link       https://www.brightcloudstudio.com/
* @license    http://opensource.org/licenses/lgpl-3.0.html
**/

namespace Bcs\Module;

use Contao\Config;

class ModReceiveData extends \Contao\Module
{
    
    // This function is called on page load. This is our hook into Quickbooks Web Connector, as that will point to the page this module is on
    protected function compile()
	{
        $user = Config::get('qbwc_username');
        $pass = Config::get('qbwc_password');

        
        // Map QuickBooks actions to handler functions
        $map = array(
            QUICKBOOKS_QUERY_INVENTORYITEM => array( '_quickbooks_inventory_request', '_quickbooks_inventory_response' ),
            //QUICKBOOKS_IMPORT_ITEM => array( '_quickbooks_item_import_request', '_quickbooks_item_import_response' ),
        );

        
        // This is entirely optional, use it to trigger actions when an error is returned by QuickBooks
        $errmap = array(
            500 => '_quickbooks_error_e500_notfound', 			// Catch errors caused by searching for things not present in QuickBooks
            1 => '_quickbooks_error_e500_notfound', 
            '*' => '_quickbooks_error_catchall', 				// Catch any other errors that might occur
        );


        // An array of callback hooks
        $hooks = array(
            // call this whenever a successful login occurs
            QuickBooks_WebConnector_Handlers::HOOK_LOGINSUCCESS => '_quickbooks_hook_loginsuccess',
        );

        
	}
}
