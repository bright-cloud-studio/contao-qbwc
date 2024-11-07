<?php


// Require the Quickbooks Framework
require_once 'QuickBooks.php';

// A username and password you'll use in: 
//	a) Your .QWC file
//	b) The Web Connector
//	c) The QuickBooks framework
// 	NOTE: This has *no relationship* with QuickBooks usernames, Windows usernames, etc. 
// 	It is *only* used for the Web Connector and SOAP server! 
$user = 'microcut';
$pass = 'cFsyppHd068zlkqm';

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

/* HOOK - Successful Login
function _quickbooks_hook_loginsuccess($requestID, $user, $hook, &$err, $hook_data, $callback_config)
{
	// Do something whenever a successful login occurs...
}
*/

// A pure-PHP SOAP server (no PHP ext/soap extension required, also makes debugging easier)
$soapserver = QUICKBOOKS_SOAPSERVER_BUILTIN;

// See http://www.php.net/soap
$soap_options = array();

// See the comments in the QuickBooks/Server/Handlers.php file
$handler_options = array(
    //'authenticate' => ' *** YOU DO NOT NEED TO PROVIDE THIS CONFIGURATION VARIABLE TO USE THE DEFAULT AUTHENTICATION METHOD FOR THE DRIVER YOU'RE USING (I.E.: MYSQL) *** '
	//'authenticate' => 'your_function_name_here', 
	//'authenticate' => array( 'YourClassName', 'YourStaticMethod' ),
	'deny_concurrent_logins' => false, 
	'deny_reallyfast_logins' => false, 
);		

// See the comments in the QuickBooks/Driver/<YOUR DRIVER HERE>.php file ( i.e. 'Mysql.php', etc. )
$driver_options = array(
    //'max_log_history' => 1024,	// Limit the number of quickbooks_log entries to 1024
    //'max_queue_history' => 64, 	// Limit the number of *successfully processed* quickbooks_queue entries to 64
);

$callback_options = array();

if ( !QuickBooks_Utilities::initialized($dsn))
{
	// Initialize creates the neccessary database schema for queueing up requests and logging
	QuickBooks_Utilities::initialize($dsn);
	
	// This creates a username and password which is used by the Web Connector to authenticate
	QuickBooks_Utilities::createUser($dsn, $user, $pass);

}

// Initialize the queue
QuickBooks_WebConnector_Queue_Singleton::initialize($dsn);


namespace Bcs\Backend;

use Contao\Backend;
use Contao\Image;
use Contao\Input;
use Contao\DataContainer;
use Contao\StringUtil;


class QuickbooksBackend extends Backend
{

    
    
    // Define Variables
    public function defineVariables() {
        
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
        
    }
    
    // Enable error logging
    public function enableErrorLogging() { 
        error_reporting(2147483647);
        ini_set('display_errors', 0);
        ini_set("log_errors", 1);
        ini_set("error_log", dirname(__FILE__)."/error.log");
        define('VERBOSE_LOGGING_MODE',true);

        // Logging level
        //$log_level = QUICKBOOKS_LOG_NORMAL;
        //$log_level = QUICKBOOKS_LOG_VERBOSE;
        //$log_level = QUICKBOOKS_LOG_DEBUG;				
        $log_level = QUICKBOOKS_LOG_DEVELOP;		// Use this level until you're sure everything works!!!
        
	}

    // Set the Time Zone
    public function setTimeZone() {
        // If the function to set the timezone exists, set it to EST
        if (function_exists('date_default_timezone_set'))
        {
            date_default_timezone_set('America/New_York');
        }
    }


    public function hook_login_success($requestID, $user, $hook, &$err, $hook_data, $callback_config) {
        // For new users, we need to set up a few things

    	// Fetch the queue instance
    	$Queue = QuickBooks_WebConnector_Queue_Singleton::getInstance();
    	$date = '1983-01-02 12:01:01';
    	
    	// Set up the invoice imports
    	if (!_quickbooks_get_last_run($user, QUICKBOOKS_QUERY_INVENTORYITEM))
    	{
    		// And write the initial sync time
    		_quickbooks_set_last_run($user, QUICKBOOKS_QUERY_INVENTORYITEM, $date);
    	}
    
    
        // ... and for items
    	/*if (!_quickbooks_get_last_run($user, QUICKBOOKS_IMPORT_ITEM))
    	{
    		_quickbooks_set_last_run($user, QUICKBOOKS_IMPORT_ITEM, $date);
    	}*/
    
    
    	$check_s = 'SELECT COUNT(quickbooks_queue_id) as active_queries FROM myqb_queue WHERE qb_action = \'ItemInventoryQuery\' AND dequeue_datetime IS NULL AND enqueue_datetime > \''.date('Y-m-d H:i:s',strtotime('-24 hours')).'\' ';
    	$check_q = mysql_query($check_s);
    	$check_row = mysql_fetch_assoc($check_q);
    	if( $check_row['active_queries'] == 0 ){
    		$Queue->enqueue(QUICKBOOKS_QUERY_INVENTORYITEM, null, QB_PRIORITY_ITEM);	
    	}	
    
    	/*$check_s = 'SELECT COUNT(quickbooks_queue_id) as active_queries FROM myqb_queue WHERE qb_action = \'ItemImportQuery\' AND dequeue_datetime IS NULL AND enqueue_datetime > \''.date('Y-m-d H:i:s',strtotime('-24 hours')).'\' ';
    	$check_q = mysql_query($check_s);
    	$check_row = mysql_fetch_assoc($check_q);
    	if( $check_row['active_queries'] == 0 ){
    		$Queue->enqueue(QUICKBOOKS_IMPORT_ITEM, null, QB_PRIORITY_ITEM);	
    	}	*/
    }


    public function initialize() {
        // Create a new server and tell it to handle the requests
        // __construct($dsn_or_conn, $map, $errmap = array(), $hooks = array(), $log_level = QUICKBOOKS_LOG_NORMAL, $soap = QUICKBOOKS_SOAPSERVER_PHP, $wsdl = QUICKBOOKS_WSDL, $soap_options = array(), $handler_options = array(), $driver_options = array(), $callback_options = array()
        $Server = new QuickBooks_WebConnector_Server($dsn, $map, $errmap, $hooks, $log_level, $soapserver, QUICKBOOKS_WSDL, $soap_options, $handler_options, $driver_options, $callback_options);
        $response = $Server->handle(true, true);

        // If you wanted, you could do something with $response here for debugging
        //if( VERBOSE_LOGGING_MODE ){
        	$fp = fopen(dirname(__FILE__).'/log_quickbooks.log', 'a+');
        	fwrite($fp, "\n" . '-- ' . date('Y-m-d H:i:s') . ' --' . "\n" );
        	fwrite($fp, $response);
        	fclose($fp);
        //}
        
    }

    public function _quickbooks_inventory_request($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale) {
    	// Iterator support (break the result set into small chunks)
    	$attr_iteratorID = '';
    	$attr_iterator = ' iterator="Start" ';
    	if (empty($extra['iteratorID']))
    	{
    		// This is the first request in a new batch
    		$last = _quickbooks_get_last_run($user, $action);
    		_quickbooks_set_last_run($user, $action);			// Update the last run time to NOW()
    		
    		// Set the current run to $last
    		_quickbooks_set_current_run($user, $action, $last);
    	}
    	else
    	{
    		// This is a continuation of a batch
    		$attr_iteratorID = ' iteratorID="' . $extra['iteratorID'] . '" ';
    		$attr_iterator = ' iterator="Continue" ';
    		
    		$last = _quickbooks_get_current_run($user, $action);
    	}
    	
    	// Build the request
    	/*$xml = '<?xml version="1.0" encoding="utf-8"?>
    		<?qbxml version="' . $version . '"?>
    		<QBXML>
    			<QBXMLMsgsRq onError="stopOnError">
    				<ItemQueryRq ' . $attr_iterator . ' ' . $attr_iteratorID . ' requestID="' . $requestID . '">
    					<MaxReturned>1000</MaxReturned>
    				</ItemQueryRq>	
    			</QBXMLMsgsRq>
    		</QBXML>';*/
    	$xml = '<?xml version="1.0" encoding="utf-8"?>
    		<?qbxml version="' . $version . '"?>
    		<QBXML>
    			<QBXMLMsgsRq onError="stopOnError">
    				<ItemQueryRq ' . $attr_iterator . ' ' . $attr_iteratorID . ' requestID="' . $requestID . '">
    					<MaxReturned>1000</MaxReturned>
    					<IncludeRetElement>Name</IncludeRetElement>
    					<IncludeRetElement>QuantityOnHand</IncludeRetElement>
    				</ItemQueryRq>	
    			</QBXMLMsgsRq>
    		</QBXML>';
    	if( VERBOSE_LOGGING_MODE ) {
    		$fp = fopen(dirname(__FILE__).'/quickbooks-nw.log', 'a+');
    		fwrite($fp, 'Extra: '.var_export($extra, true));
    		$xmlObj = XMLReader::xml($xml);
    
    		// You must to use it
    		$xmlObj->setParserProperty(XMLReader::VALIDATE, true);
    		$XMLstatus = $xmlObj->isValid() ? 'Valid XML' : 'Invalid XML';
    		fwrite($fp, 'XMLStatus: '.$XMLstatus . "\n");
    		fwrite($fp, $xml);
    		fclose($fp);
    	}
    
    	return $xml;
    }

    public function _quickbooks_inventory_response( $requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents) {
    
    	//$fp = fopen(dirname(__FILE__).'/quickbooks-nw.log', 'a+');
    	if( VERBOSE_LOGGING_MODE) $fp = fopen(dirname(__FILE__).'/new-log.log', 'a+');
    	if( VERBOSE_LOGGING_MODE) fwrite($fp, $xml);
    	//fwrite($fp, print_r($idents,true));
    	if (!empty($idents['iteratorRemainingCount']))
    	{
    		// Queue up another request
    		
    		$Queue = QuickBooks_WebConnector_Queue_Singleton::getInstance();
    		$Queue->enqueue(QUICKBOOKS_QUERY_INVENTORYITEM, null, QB_PRIORITY_ITEM, array( 'iteratorID' => $idents['iteratorID'] ));
    	}
    	
    	// Import all of the records
    	$errnum = 0;
    	$errmsg = '';
    	$Parser = new QuickBooks_XML_Parser($xml);
    	if ($Doc = $Parser->parse($errnum, $errmsg))
    	{
    		$Root = $Doc->getRoot();
    		$List = $Root->getChildAt('QBXML/QBXMLMsgsRs/ItemQueryRs');
    		fwrite( $fp, "Doc Parsed, about to import inventory (".count($List->children()).")\n" );
    
    		foreach ($List->children() as $Item)
    		{
    			$type = substr(substr($Item->name(), 0, -3), 4);
    			$ret = $Item->name();
    			
    			$arr = array(
    				'Name' => $Item->getChildDataAt($ret . ' Name'),
    				'QuantityOnHand' => $Item->getChildDataAt($ret . ' QuantityOnHand'), 
    			);
    
    			$upd_s = 'UPDATE product SET inventory = \''.mysql_real_escape_string($arr['QuantityOnHand']).'\' WHERE productedp = \''.mysql_real_escape_string($arr['Name']).'\'';
    			$upd_q = mysql_query($upd_s);
    			if( VERBOSE_LOGGING_MODE)
                    fwrite( $fp, $upd_s."\n" );
    		}
    	}

    	if(VERBOSE_LOGGING_MODE)
            fclose($fp);    
    	return true;
    	
    }






    // Build a request to import customers already in QuickBooks into our application
    public function _quickbooks_item_import_request($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale) {
    	
        // Iterator support (break the result set into small chunks)
    	$attr_iteratorID = '';
    	$attr_iterator = ' iterator="Start" ';
    	if (empty($extra['iteratorID']))
    	{
    		// This is the first request in a new batch
    		$last = _quickbooks_get_last_run($user, $action);
    		_quickbooks_set_last_run($user, $action);			// Update the last run time to NOW()
    		
    		// Set the current run to $last
    		_quickbooks_set_current_run($user, $action, $last);
    	}
    	else
    	{
    		// This is a continuation of a batch
    		$attr_iteratorID = ' iteratorID="' . $extra['iteratorID'] . '" ';
    		$attr_iterator = ' iterator="Continue" ';
    		
    		$last = _quickbooks_get_current_run($user, $action);
    	}
    	
    	// Build the request
    	$xml = '<?xml version="1.0" encoding="utf-8"?>
    		<?qbxml version="' . $version . '"?>
    		<QBXML>
    			<QBXMLMsgsRq onError="stopOnError">
    				<ItemQueryRq ' . $attr_iterator . ' ' . $attr_iteratorID . ' requestID="' . $requestID . '">
    					<MaxReturned>' . QB_QUICKBOOKS_MAX_RETURNED . '</MaxReturned>
    				</ItemQueryRq>	
    			</QBXMLMsgsRq>
    		</QBXML>';
    
    	if( VERBOSE_LOGGING_MODE ) {
    		$fp = fopen(dirname(__FILE__).'/quickbooks-nw-inventory.log', 'a+');
    		fwrite($fp, 'Extra: '.var_export($extra, true));
    		$xmlObj = XMLReader::xml($xml);
    
    		// You must to use it
    		$xmlObj->setParserProperty(XMLReader::VALIDATE, true);
    		$XMLstatus = $xmlObj->isValid() ? 'Valid XML' : 'Invalid XML';
    		fwrite($fp, 'XMLStatus: '.$XMLstatus . "\n");
    		fwrite($fp, $xml);
    		fclose($fp);
    	}
    		
    	return $xml;
    }



    /**
     * Handle a 500 not found error from QuickBooks
     * Instead of returning empty result sets for queries that don't find any 
     * records, QuickBooks returns an error message. This handles those error 
     * messages, and acts on them by adding the missing item to QuickBooks. 
     */
    public function _quickbooks_error_e500_notfound($requestID, $user, $action, $ID, $extra, &$err, $xml, $errnum, $errmsg)
    {
    	$Queue = QuickBooks_WebConnector_Queue_Singleton::getInstance();
    	
    	if ($action == QUICKBOOKS_QUERY_INVENTORYITEM)
    	{
    		return true;
    	}
    	
    	return true;
    }






    /**
     * Catch any errors that occur
     * @param string $requestID			
     * @param string $action
     * @param mixed $ID
     * @param mixed $extra
     * @param string $err
     * @param string $xml
     * @param mixed $errnum
     * @param string $errmsg
     * @return void
     */
    public function _quickbooks_error_catchall($requestID, $user, $action, $ID, $extra, &$err, $xml, $errnum, $errmsg)
    {
    	$message = '';
    	$message .= 'Request ID: ' . $requestID . "\r\n";
    	$message .= 'User: ' . $user . "\r\n";
    	$message .= 'Action: ' . $action . "\r\n";
    	$message .= 'ID: ' . $ID . "\r\n";
    	$message .= 'Extra: ' . print_r($extra, true) . "\r\n";
    	//$message .= 'Error: ' . $err . "\r\n";
    	$message .= 'Error number: ' . $errnum . "\r\n";
    	$message .= 'Error message: ' . $errmsg . "\r\n";
    	if( VERBOSE_LOGGING_MODE ){
    		$fp = fopen(dirname(__FILE__).'/quickbooks-nw.log', 'a+');
    		fwrite($fp, $message);
    		fclose($fp);
    	}
    }


    // Get the last date/time the QuickBooks sync ran
    public function _quickbooks_get_last_run($user, $action)
    {
    	$type = null;
    	$opts = null;
    	return QuickBooks_Utilities::configRead(QB_QUICKBOOKS_DSN, $user, md5(__FILE__), QB_QUICKBOOKS_CONFIG_LAST . '-' . $action, $type, $opts);
    }


    // Set the last date/time the QuickBooks sync ran to NOW
    public function _quickbooks_set_last_run($user, $action, $force = null)
    {
    	$value = date('Y-m-d') . 'T' . date('H:i:s');
    	
    	if ($force)
    	{
    		$value = date('Y-m-d', strtotime($force)) . 'T' . date('H:i:s', strtotime($force));
    	}
    	
    	return QuickBooks_Utilities::configWrite(QB_QUICKBOOKS_DSN, $user, md5(__FILE__), QB_QUICKBOOKS_CONFIG_LAST . '-' . $action, $value);
    }


    // Set the current run time
    public function _quickbooks_set_current_run($user, $action, $force = null)
    {
    	$value = date('Y-m-d') . 'T' . date('H:i:s');
    	
    	if ($force)
    	{
    		$value = date('Y-m-d', strtotime($force)) . 'T' . date('H:i:s', strtotime($force));
    	}
    	
    	return QuickBooks_Utilities::configWrite(QB_QUICKBOOKS_DSN, $user, md5(__FILE__), QB_QUICKBOOKS_CONFIG_CURR . '-' . $action, $value);	
    }
    

    // Get the current run time
    public function _quickbooks_get_current_run($user, $action)
    {
    	$type = null;
    	$opts = null;
    	return QuickBooks_Utilities::configRead(QB_QUICKBOOKS_DSN, $user, md5(__FILE__), QB_QUICKBOOKS_CONFIG_CURR . '-' . $action, $type, $opts);	
    }


    // public function functionName() { 
    // }
    
}
