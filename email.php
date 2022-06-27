<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once 'vendor/autoload.php';
include_once(__DIR__ . "/classes/REDCapManagement.php");

function sendEmail($to, $from, $fromName, $subject, $message, $record_id, $action_description="", $pid="", $cc=""){
    if($from == ""){
        $from = " harmonist@vumc.org";
    }

    REDCapManagement::getEnvironment();
    $environment = "";
    if(ENVIRONMENT == 'DEV' || ENVIRONMENT == 'TEST'){
        $environment = " ".ENVIRONMENT;
    }

    $send = \REDCap::email ($to,  $from, $subject, "");//  $message,  $cc ,  '' ,  $fromName.$environment);

    if (!$send) {
        //datacore@vumc.org;
        \REDCap::email('eva.bascompte.moragas@vumc.org;harmonist@vumc.org', 'harmonist@vumc.org',"Mailer Error:".
            $action_description, "Mailer Error (send = ".$send."): the email could not be sent in project ".$pid." record #".$record_id.
            "<br><br>To: ".$to."<br>CC: ".$cc."<br>From (".$fromName.$environment."): ".$from."<br>Subject: ".$subject.
            "<br>Message: <br>".$message);
    } else {
        //Add some logs
        $changes_made = "[record_id]:".$record_id.", [email]: ".$to;
        \REDCap::logEvent($action_description,$changes_made,NULL,null,null,$pid);
    }
}
?>