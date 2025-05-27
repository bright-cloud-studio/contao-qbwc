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
        // if our date is X days before end of month
        $days_before = 7;
        // The number of days left in the month
        $how_many_days = date('t') - date('j');

        // Add a log entry so we know things are going as expected
        \Controller::log('GAI: (' . $how_many_days . ') days remaining until the end of the month', __CLASS__ . '::' . __FUNCTION__, 'GENERAL');

        // If today is Weekly Reminder Day!
        if($days_before == $how_many_days) {
            
            // add Log that it is the right day!
            \Controller::log('GAI: Weekly Reminder email will go out today!', __CLASS__ . '::' . __FUNCTION__, 'GENERAL');
            
            // get all of the Members
            $options = [
                'order' => 'id ASC'
            ];
            $members = MemberModel::findBy('disable', '', $options);
            
            foreach($members as $member) {

                // Get the email
                $objNotification = \NotificationCenter\Model\Notification::findByPk(9);
                if (null !== $objNotification) {
                    
                    // Sender info
                    $arrTokens['sender_name'] = 'Global Assessments, Inc';
                    $arrTokens['sender_address'] = 'billing@globalassessmentsinc.com';
                    $arrTokens['reply_to_address'] = 'billing@globalassessmentsinc.com';
                    
                    // Recipient info
                    $arrTokens['recipient_name'] = $member->firstname . " " . $member->lastname;
                    $arrTokens['recipient_email'] = $member->email;
                    $arrTokens['recipient_cc'] = 'ed@globalassessmentsinc.com';

                    // Send out the email using our tokens
                    $objNotification->send($arrTokens);

                     \Controller::log('GAI: Weekly Reminder email sent to ' . $member->firstname . " " . $member->lastname, __CLASS__ . '::' . __FUNCTION__, 'GENERAL');
                }
            }
        }

        // If today is Last Day Reminder Day!
        if($how_many_days == 0) {
            
            // add Log that it is the right day!
            \Controller::log('GAI: Last Day Reminder email will go out today!', __CLASS__ . '::' . __FUNCTION__, 'GENERAL');
            
            // get all of the Members
            $options = [
                'order' => 'id ASC'
            ];
            $members = MemberModel::findBy('disable', '', $options);
            
            foreach($members as $member) {

                // Get the email
                $objNotification = \NotificationCenter\Model\Notification::findByPk(10);
                if (null !== $objNotification) {
                    
                    // Sender info
                    $arrTokens['sender_name'] = 'Global Assessments, Inc';
                    $arrTokens['sender_address'] = 'billing@globalassessmentsinc.com';
                    $arrTokens['reply_to_address'] = 'billing@globalassessmentsinc.com';
                    
                    // Recipient info
                    $arrTokens['recipient_name'] = $member->firstname . " " . $member->lastname;
                    $arrTokens['recipient_email'] = $member->email;
                    $arrTokens['recipient_cc'] = 'ed@globalassessmentsinc.com';

                    // Send out the email using our tokens
                    $objNotification->send($arrTokens);

                     \Controller::log('GAI: Last Day Reminder Email sent to ' . $member->firstname . " " . $member->lastname, __CLASS__ . '::' . __FUNCTION__, 'GENERAL');
                }
            }
        }
        
    }
    
    public function importPsychologists(): void
    {
        
        // Log entry to confirm this is working
        //\Controller::log('GAI: Syncing Psychologists data with Sheets', __CLASS__ . '::' . __FUNCTION__, 'GENERAL');
        
        // Create a client connection to Google
        $client = new Google\Client();
        $client->setAuthConfig($_SERVER['DOCUMENT_ROOT'] . '/key.json');
        $client->addScope(Google\Service\Sheets::SPREADSHEETS);
        $service = new \Google_Service_Sheets($client);
        $spreadsheetId = '1PEJN5ZGlzooQrtIEdeo4_nZH73W0aJTUbRIoibzl3Lo';
        
        $range = 'Psychologists';
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();
        
        // Connect to Contao's database
        $dbh = new \mysqli("localhost", "globalassinc_user", "Z2rc^wQ}98TS9mtl5y", "globalassinc_contao_4_13");
        if ($dbh->connect_error) {
            die("Connection failed: " . $dbh->connect_error);
        }
        
        // Loop through our Psychologists
        $counter = 0;
        foreach($values as $entry) {
            
            // first entry is always the table headers
            if($counter >= 1) {
                
                // Try getting an entry with this psychologist's name
                $query = "SELECT * FROM tl_psychologists WHERE name='".$entry[1]."'";
                $result = $dbh->query($query);
                $rowcount=mysqli_num_rows($result);
                
                if($rowcount > 0) {
                    
                    // we got a result, let check for changes
                    $has_changes = false;
                    while($row = $result->fetch_assoc()) {
                        if($row['invoices'] != $entry[0]) { $has_changes = true; }
                        if($row['address'] != $entry[2]) { $has_changes = true; }
                        if($row['address_2'] != $entry[3]) { $has_changes = true; }
                        if($row['city'] != $entry[4]) { $has_changes = true; }
                        if($row['state'] != $entry[5]) { $has_changes = true; }
                        if($row['zip'] != $entry[6]) { $has_changes = true; }
                        if($row['email'] != $entry[7]) { $has_changes = true; }
                        if($row['last_month_processed'] != $entry[8]) { $has_changes = true; }
                        if($row['price_tier'] != $entry[9]) { $has_changes = true; }
                    }
                    
                    // if there is changes detected, update the existing psychologist
                    if($has_changes) {
                        \Controller::log('GAI: Updating existing psychologist ("'.$entry[1].'")', __CLASS__ . '::' . __FUNCTION__, 'GENERAL');
                        $query = "UPDATE tl_psychologists SET tstamp='".time()."', invoices='".$entry[0]."', name='".$entry[1]."', address='".$entry[2]."', address_2='".$entry[3]."', city='".$entry[4]."', state='".$entry[5]."', zip='".$entry[6]."', email='".$entry[7]."', last_month_processed='".$entry[8]."', price_tier='".$entry[9]."' WHERE name='".$entry[1]."'";
                        $result = $dbh->query($query);
                    }
                    
                } else {
                    
                    // no psychologist was found, lets create a new one
                    \Controller::log('GAI: Adding new psychologist ("'.$entry[1].'")', __CLASS__ . '::' . __FUNCTION__, 'GENERAL');
                    $query = "INSERT INTO tl_psychologists (tstamp, invoices, name, address, address_2, city, state, zip, email, last_month_processed, price_tier)
                              VALUES ('".time()."', '".$entry[0]."', '".$entry[1]."', '".$entry[2]."', '".$entry[3]."', '".$entry[4]."', '".$entry[5]."', '".$entry[6]."', '".$entry[7]."', '".$entry[8]."', '".$entry[9]."' )";
                    $result = $dbh->query($query);
                }
            }
            $counter++;
        }
    }
    
    public function importServices(): void
    {
        
        // Log entry to confirm this is working
        //\Controller::log('GAI: Syncing Services data with Sheets', __CLASS__ . '::' . __FUNCTION__, 'GENERAL');
        
        // Create a client connection to Google
        $client = new Google\Client();
        $client->setAuthConfig($_SERVER['DOCUMENT_ROOT'] . '/key.json');
        $client->addScope(Google\Service\Sheets::SPREADSHEETS);
        $service = new \Google_Service_Sheets($client);
        $spreadsheetId = '1PEJN5ZGlzooQrtIEdeo4_nZH73W0aJTUbRIoibzl3Lo';
        
        $range = 'Services';
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();
        
        // Connect to Contao's database
        $dbh = new \mysqli("localhost", "globalassinc_user", "Z2rc^wQ}98TS9mtl5y", "globalassinc_contao_4_13");
        if ($dbh->connect_error) {
            die("Connection failed: " . $dbh->connect_error);
        }
        
        // Loop through our Psychologists
        $counter = 0;
        foreach($values as $entry) {
            
            // first entry is always the table headers
            if($counter >= 1) {
                
                // Try getting an entry with this psychologist's name
                $query = "SELECT * FROM tl_services WHERE service_code='".$entry[0]."'";
                $result = $dbh->query($query);
                $rowcount=mysqli_num_rows($result);
                
                if($rowcount > 0) {
                    
                    // we got a result, let check for changes
                    $has_changes = false;
                    while($row = $result->fetch_assoc()) {
                        if($row['name'] != $entry[1]) { $has_changes = true; }
                        if($row['psychologist_tier_1'] != $entry[2]) { $has_changes = true; }
                        if($row['psychologist_tier_2'] != $entry[3]) { $has_changes = true; }
                        if($row['psychologist_tier_3'] != $entry[4]) { $has_changes = true; }
                        if($row['psychologist_tier_4'] != $entry[5]) { $has_changes = true; }
                        if($row['psychologist_tier_5'] != $entry[6]) { $has_changes = true; }
                        if($row['psychologist_tier_6'] != $entry[7]) { $has_changes = true; }
                        if($row['psychologist_tier_7'] != $entry[8]) { $has_changes = true; }
                        if($row['psychologist_tier_8'] != $entry[9]) { $has_changes = true; }
                        if($row['psychologist_tier_9'] != $entry[10]) { $has_changes = true; }
                        if($row['psychologist_tier_10'] != $entry[11]) { $has_changes = true; }
                        if($row['school_tier_1'] != $entry[12]) { $has_changes = true; }
                        if($row['school_tier_2'] != $entry[13]) { $has_changes = true; }
                        if($row['school_tier_3'] != $entry[14]) { $has_changes = true; }
                    }
                    
                    // if there is changes detected, update the existing psychologist
                    if($has_changes) {
                        \Controller::log('GAI: Updating existing service ("'.$entry[1].'")', __CLASS__ . '::' . __FUNCTION__, 'GENERAL');
                        $query = "UPDATE tl_services SET tstamp='".time()."', service_code='".$entry[0]."', name='".$entry[1]."', psychologist_tier_1='".$entry[2]."', psychologist_tier_2='".$entry[3]."', psychologist_tier_3='".$entry[4]."', psychologist_tier_4='".$entry[5]."', psychologist_tier_5='".$entry[6]."', psychologist_tier_6='".$entry[7]."', psychologist_tier_7='".$entry[8]."', psychologist_tier_8='".$entry[9]."', psychologist_tier_9='".$entry[10]."', psychologist_tier_10='".$entry[11]."', school_tier_1='".$entry[12]."', school_tier_2='".$entry[13]."', school_tier_3='".$entry[14]."' WHERE service_code='".$entry[0]."'";
                        $result = $dbh->query($query);
                    }
                    
                } else {
                    
                    // no psychologist was found, lets create a new one
                    \Controller::log('GAI: Adding new service ("'.$entry[1].'")', __CLASS__ . '::' . __FUNCTION__, 'GENERAL');
                    $query = "INSERT INTO tl_services (tstamp, service_code, name, psychologist_tier_1, psychologist_tier_2, psychologist_tier_3, psychologist_tier_4, psychologist_tier_5, psychologist_tier_6, psychologist_tier_7, psychologist_tier_8, psychologist_tier_9, psychologist_tier_10, school_tier_1, school_tier_2, school_tier_3)
                              VALUES ('".time()."', '".$entry[0]."', '".$entry[1]."', '".$entry[2]."', '".$entry[3]."', '".$entry[4]."', '".$entry[5]."', '".$entry[6]."', '".$entry[7]."', '".$entry[8]."', '".$entry[9]."', '".$entry[10]."', '".$entry[11]."', '".$entry[12]."', '".$entry[13]."', '".$entry[14]."' )";
                    $result = $dbh->query($query);
                }
            }
            $counter++;
        }
    }

}
