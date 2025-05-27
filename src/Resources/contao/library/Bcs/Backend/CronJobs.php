<?php

namespace Bcs\Backend;

use Google;
use Contao\System;
use Contao\MemberModel;


class CronJobs extends System
{
    
    // If there are X $days_before the end of the month then send reminder emails to all psychologists
    public function sendReminderEmails(): void
    {
        $log = fopen($_SERVER['DOCUMENT_ROOT'] . '/../'.date('m_d_y').'_cron_log.txt', "a+") or die("Unable to open file!");
        fwrite($log, 'Cron Triggered! '. time() .'\n');
        fclose($log);
        
    }


}
