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
	    
	    // Require the library code
		require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/consolibyte/quickbooks/QuickBooks.php';
	    
        $user = Config::get('qbwc_username');
        $pass = Config::get('qbwc_password');

        // The prefix for the mysql tables
        define('QUICKBOOKS_DRIVER_SQL_MYSQL_PREFIX', 'myqb_');
        
        // Configuration parameter for the quickbooks_config table, used to keep track of the last time the QuickBooks sync ran
        define('QB_QUICKBOOKS_CONFIG_LAST', 'last');

        // Configuration parameter for the quickbooks_config table, used to keep track of the timestamp for the current iterator
        define('QB_QUICKBOOKS_CONFIG_CURR', 'curr');

        // Maximum number of customers/invoices returned at a time when doing the import
        define('QB_QUICKBOOKS_MAX_RETURNED', 1000);

        // Related to Purchase Orders
        define('QB_PRIORITY_PURCHASEORDER', 4);

        // Request priorities, items sync first
        define('QB_PRIORITY_ITEM', 3);

        // MySQL login details
        $dsn = 'mysql://root:root@localhost/quickbooks_import';
        define('QB_QUICKBOOKS_DSN',$dsn);
        

        
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
            \QuickBooks_WebConnector_Handlers::HOOK_LOGINSUCCESS => '_quickbooks_hook_loginsuccess',
        );


        $soapserver = QUICKBOOKS_SOAPSERVER_BUILTIN;

        $soap_options = array();
        
        $handler_options = array(
        	'deny_concurrent_logins' => false, 
        	'deny_reallyfast_logins' => false, 
        );
        
        $driver_options = array();
        
        $callback_options = array();
        
        /*
        if(!\QuickBooks_Utilities::initialized($dsn))
        {
        	// Initialize creates the neccessary database schema for queueing up requests and logging
        	\QuickBooks_Utilities::initialize($dsn);
        	
        	// This creates a username and password which is used by the Web Connector to authenticate
        	\QuickBooks_Utilities::createUser($dsn, $user, $pass);
        
        }
        
        // Initialize the queue
        \QuickBooks_WebConnector_Queue_Singleton::initialize($dsn);
        */
        
        

        
	}
    
}
