<?php

/**
* @copyright  Bright Cliud Studio
* @author     Bright Cloud Studio
* @package    Contao Quickbooks Web Connector
* @license    LGPL-3.0+
* @see	       https://github.com/bright-cloud-studio/contao-qbwc
*/

/* Front End modules */
$GLOBALS['FE_MOD']['gai']['mod_receive_data'] = 'Bcs\Module\ModReceiveData';


/* Cron Jobs */
$GLOBALS['TL_CRON']['minutely'][] = ['Bcs\Backend\CronJobs', 'checkQuickbooksConnection'];
