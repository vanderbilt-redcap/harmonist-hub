<?php
namespace Vanderbilt\HarmonistHubExternalModule;

require_once 'vendor/autoload.php';
include_once(__DIR__ . "/classes/REDCapManagement.php");

use Vanderbilt\HarmonistHubExternalModule\REDCapManagement;

function sendEmail($to, $from, $fromName, $subject, $message, $record_id, $action_description="", $pid="", $cc=""){
    if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
        if ($from == "") {
            $from = REDCapManagement::DEFAULT_EMAIL_ADDRESS;
        }

        REDCapManagement::getEnvironment();
        $environment = "";
        if (defined('ENVIRONMENT') && (ENVIRONMENT == 'DEV' || ENVIRONMENT == 'TEST')) {
            $environment = " " . ENVIRONMENT;
        }

        #We use the message class so the emails get recorded in the Email Logging section in REDCap
        $email = new \Message($pid, $record_id);
        $email->setTo($to);
        if ($cc != '') $email->setCc($cc);
        $email->setFrom($from);
        $email->setFromName($fromName . $environment);
        $email->setSubject($subject);
        $email->setBody($message);

        $send = $email->send();

        if (!$send) {
            \REDCap::email(REDCapManagement::DEFAULT_EMAIL_ADDRESS, REDCapManagement::DEFAULT_EMAIL_ADDRESS, "Mailer Error:" .
                $action_description, "Mailer Error (send = " . $send . "): the email could not be sent in project " . $pid . " record #" . $record_id .
                "<br><br>To: " . $to . "<br>CC: " . $cc . "<br>From (" . $fromName . $environment . "): " . $from . "<br>Subject: " . $subject .
                "<br>Message: <br>" . $message);
        } else {
            //Add some logs
            $changes_made = "[record_id]:" . $record_id . ", [email]: " . $to;
            \REDCap::logEvent($action_description, $changes_made, NULL, null, null, $pid);
        }
    }
}
?>