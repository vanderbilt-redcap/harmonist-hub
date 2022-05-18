<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once 'vendor/autoload.php';

function sendEmail($to, $from, $fromName, $subject, $message, $record_id, $action_description="", $pid="", $cc=""){
    if($from == ""){
        $from = " harmonist@vumc.org";
    }

    $environment = "";
    if(ENVIRONMENT == 'DEV' || ENVIRONMENT == 'TEST'){
        $environment = " ".ENVIRONMENT;
    }

    $send = \REDCap::email ($to,  $from, $subject,  $message ,  $cc ,  '' ,  $fromName.$environment);

    if (!$send) {
        \REDCap::email('datacore@vumc.org;harmonist@vumc.org', 'harmonist@vumc.org',"Mailer Error", "Mailer Error: the email could not be sent in project ".$pid);
    } else {
        //Add some logs
        $changes_made = "[record_id]:".$record_id.", [email]: ".$to;
        \REDCap::logEvent($action_description,$changes_made,NULL,null,null,$pid);
    }
}

function setFrom($mail, $sender, $sender_name){
    global $from_email;
    // Using the Universal From Email Address?
    $usingUniversalFrom = ($from_email != '');
    if(!empty($sender)){
        if(filter_var(trim($sender), FILTER_VALIDATE_EMAIL)) {
            // Set the From email for this message
            $this_from_email = (!$usingUniversalFrom ? $sender : $from_email);
            // From, Reply-To, and Return-Path. Also, set Display Name if possible.
            if ($sender_name == '""' || empty($sender_name)) {
                // If no Display Name, then use the Sender address as the Display Name if using Universal FROM address
                $fromDisplayName = $usingUniversalFrom ? $sender : "";
                $replyToDisplayName = '';
            } else {
                // Clean the defined display name
                $sender_name = str_replace('"', '', trim($sender_name));
                // If has a Display Name, then use the Sender address+real Display Name if using Universal FROM address
                $fromDisplayName = $usingUniversalFrom ? $sender_name." <".$sender.">" : $sender_name;
                $replyToDisplayName = $sender_name;
            }
            $mail->setFrom($this_from_email, $fromDisplayName, false);
            $mail->addReplyTo($sender, $replyToDisplayName);
            $mail->Sender = $sender; // Return-Path
        }else{
            \REDCap::email('datacore@vumc.org', 'harmonist@vumc.org',"Wrong recipient", "The email ".$sender." in Harmonist Hub EM does not exist");
        }
    }else{
        \REDCap::email('datacore@vumc.org', 'harmonist@vumc.org',"Sender is empty", "The email in Harmonist Hub EM is empty");
    }
    return $mail;
}
?>