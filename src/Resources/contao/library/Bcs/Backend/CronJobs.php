<?php

namespace Bcs\Backend;

use Contao\System;

class CronJobs extends System
{
    
    // Runs every hour to see if we are still connected
    public function checkQuickbooksConnection(): void
    {
        $log = fopen($_SERVER['DOCUMENT_ROOT'] . '/../'.date('m_d_y').'_cron_log.txt', "a+") or die("Unable to open file!");
        fwrite($log, 'Cron Triggered! '. time() .'\n');
        fclose($log);   
    }


}
