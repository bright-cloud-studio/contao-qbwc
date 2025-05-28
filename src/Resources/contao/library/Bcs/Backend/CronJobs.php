<?php

namespace Bcs\Backend;

use Contao\ModuleModel;
use Contao\System;

class CronJobs extends System
{
    
    // Runs every hour to see if we are still connected
    public function checkQuickbooksConnection(): void
    {
        $log = fopen($_SERVER['DOCUMENT_ROOT'] . '/../'.date('m_d_y').'_cron_log.txt', "a+") or die("Unable to open file!");
        fwrite($log, 'Cron Triggered! '. time() . PHP_EOL);
        fclose($log);
        
        
        
        // Get the minute threshold
        $mod = ModuleModel::findOneBy('minute_notification_threshold > ?', 0);
        if($mod) {
            
            fwrite($log, print_r($mod, true). PHP_EOL);
            
        }
        
        // Get the 'last_run'
        
        // See how many minutes have elapsed between 'last_run' and now
        
        // if greater minute threshold, send notification email
        
        
        
        
        
        
        
    }


}
