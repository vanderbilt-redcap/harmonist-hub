<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEmail($to, $sender_email, $sender_name, $subject, $message,$record_id){

    if($sender_email == ""){
        $sender_email = " harmonist@vumc.org";
    }

    $environment = "";
    if(ENVIRONMENT == 'DEV' || ENVIRONMENT == 'TEST'){
        $environment = " ".ENVIRONMENT;
    }

    $mail = new PHPMailer(true);

    $mail = setFrom($mail, $sender_email, $sender_name.$environment);
    $mail->addAddress($to);

    $mail->CharSet = 'UTF-8';
    $mail->Subject = $subject;
    $mail->IsHTML(true);
    $mail->Body = $message;


    //DKIM to make sure the email does not go into spam folder
    $privatekeyfile = 'dkim_private.key';
    //Make a new key pair
    //(2048 bits is the recommended minimum key length -
    //gmail won't accept less than 1024 bits)
    $pk = openssl_pkey_new(
        array(
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA
        )
    );
    openssl_pkey_export_to_file($pk, $privatekeyfile);
    $mail->DKIM_private = $privatekeyfile;
    $mail->DKIM_selector = 'PHPMailer';
    $mail->DKIM_passphrase = ''; //key is not encrypted
    if (!$mail->send()) {
        \REDCap::email('datacore@vumc.org', 'harmonist@vumc.org',"Mailer Error", "Mailer Error:".$mail->ErrorInfo." in project ".IEDEA_PEOPLE);

    } else {
        //Add some logs
        $action_description = "Review Hub Access Sent";
        $changes_made = "Email Link Sent to [record_id]:".$record_id.", [email]: ".$to;
        \REDCap::logEvent($action_description,$changes_made,NULL,null,null,IEDEA_PEOPLE);

    }
    unlink($privatekeyfile);
    // Clear all addresses and attachments for next loop
    $mail->clearAddresses();
    $mail->clearAttachments();
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