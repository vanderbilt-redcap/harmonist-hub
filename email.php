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
        //datacore@vumc.org;
        \REDCap::email('eva.bascompte.moragas@vumc.org;harmonist@vumc.org', 'harmonist@vumc.org',"Mailer Error:".
            $action_description, "Mailer Error: the email could not be sent in project ".$pid." record #".$record_id.
            "<br>Email To:".$to."<br>Email From (".$fromName.$environment."):".$from."<br>Email subject:".$subject."<br>Email To:".$to);
    } else {
        //Add some logs
        $changes_made = "[record_id]:".$record_id.", [email]: ".$to;
        \REDCap::logEvent($action_description,$changes_made,NULL,null,null,$pid);
    }
}
?>