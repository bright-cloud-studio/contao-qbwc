<?php

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
    }
    
    // Enable error logging
    public function enableErrorLogging() { 
        error_reporting(2147483647);
        ini_set('display_errors', 0);
        ini_set("log_errors", 1);
        ini_set("error_log", dirname(__FILE__)."/error.log");
        define('VERBOSE_LOGGING_MODE',true);
	}

    // Set the Time Zone
    public function setTimeZone() {
        // If the function to set the timezone exists, set it to EST
        if (function_exists('date_default_timezone_set'))
        {
            date_default_timezone_set('America/New_York');
        }
    }








    
    public function functionName() {
        
    }
    
}
