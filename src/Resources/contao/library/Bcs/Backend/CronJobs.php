<?php

namespace Bcs\Backend;

use Contao\ModuleModel;
use Contao\System;

class CronJobs extends System
{
    
    // Runs every hour to see if we are still connected
    public function checkQuickbooksConnection(): void
    {
        
        // Set the default time zone for this script, so the time matches our server
        date_default_timezone_set("America/New_York");
        
        // dev log
        $log = fopen($_SERVER['DOCUMENT_ROOT'] . '/../'.date('m_d_y').'_cron_log.txt', "a+") or die("Unable to open file!");
        fwrite($log, 'Cron Triggered! '. time() . PHP_EOL);
        
        // Get our custom module
        $mod = ModuleModel::findBy(['tl_module.minute_notification_threshold > ?'], [0]);
        if($mod) {
            
            // calculate time difference
            $differenceInSeconds = abs(time() - $mod->last_run);
            $differenceInMinutes = round($differenceInSeconds / 60);
            
            // If our difference in minutes is greater than our threshold, time to reach out
            if($differenceInMinutes > $mod->minute_notification_threshold) {
                
                fwrite($log, 'Sending Notification Email!' . PHP_EOL);

                // Always set content-type when sending HTML email
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                
                // More headers
                $headers .= 'From: <notifications@microcutusa.com>' . "\r\n";
                $headers .= 'Cc: mark@brightcloudstudio.com, jeff@microcutusa.com' . "\r\n";
                
                $sub = "[Microcut] Check Quickbooks Connection!";
                
                $message = "4
                    <html>
                    <head>
                    <title>Microcut USA</title>
                    </head>
                    <body>
                        <p>We have crossed the time threshold since our last Quickbooks update. Check the connection to make sure it is still online!</p>
                    </body>
                    </html>
                    ";
                    
                //mail($addr, $sub, $message, $headers);

            } else {
                fwrite($log, 'Threshold not crossed!' . PHP_EOL);
            }
            
        }

        fclose($log);
        
    }



}
