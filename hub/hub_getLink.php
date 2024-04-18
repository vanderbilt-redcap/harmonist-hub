<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once dirname(dirname(__FILE__))."/projects.php";

$module->log("HUB: " . $pidsArray['PROJECTS'] . " - GET LINK");

$settings = \REDCap::getData($pidsArray['SETTINGS'], 'json-array', null, array('hub_name','accesslink_dur','accesslink_sender_email','accesslink_sender_name','hub_contact_email'))[0];

$result = "";
$current_record = $_POST['record'];
$current_option = $_POST['option'];
$email = $_REQUEST['email'];
$options = array(0=>"map",1=>"sop",2=>"ss1",3=>"cpt",4=>"ttl",5=>"pup",6=>"cup",7=>"bug",8=>"hub",9=>"adm",10=>"hra",
    11=>"upd",12=>"ups",13=>"uph",14=>"dnd",15=>"out",16=>"abt",17=>"faq",18=>"arc",19=>"pro",20=>"smn",
    21=>"gac",22=>"sra",23=>"tbl",24=>"ofs",25=>"fsa",26=>"dna",27=>"ss5",28=>"spr",29=>"lgd",30=>"usr",
    31=>"mra",32=>"mrr",33=>"dat",34=>"pdc",35=>"mts",36=>"mth",37=>"unf",38=>"und",39=>"cal");

if(!empty($_REQUEST['email'])) {
    $module->log("HUB: " . $pidsArray['PROJECTS'] . " - link requested for ".$email);
    $people = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', null,array('record_id','harmonist_regperm','active_y','email','first_ever_login_d'),null,null,false,false,false,"lower([email]) ='".strtolower($email)."'")[0];
    if(strtolower($people['email']) == strtolower($email) && $people['harmonist_regperm'] !='0' && $people['harmonist_regperm'] != NULL && $people['active_y'] == '1'){
        $arrayLogin = array(array('record_id' => $people['record_id']));
        $module->log("HUB: " . $pidsArray['PROJECTS'] . " - Email found in database. Proceeding to send link");
        $token = \Vanderbilt\HarmonistHubExternalModule\getRandomIdentifier(12);
        $send_option = "";
        if(!empty($current_option)){
            foreach ($options as $option){
                if($current_option == $option){
                    $send_option = "&option=".$current_option;
                    break;
                }
            }
        }

        $send_record = "";
        if(is_numeric($current_record)){
            $send_record = "&record=".$current_record;
        }

        $url = $module->getUrl("index.php")."&NOAUTH&token=".$token.$send_option.$send_record."&pid=".$pidsArray['PROJECTS'];
        $message = "<html>Here is your link to access the ".$settings['hub_name']." Hub:<br/><a href='".$url."'>".$url."</a><br/><br/><span style='color:#e74c3c'>**This link is unique to you and should not be forwarded to others.</span><br/>".
            "This link will expire in ".$settings['accesslink_dur']." days. You can request a new link at any time, which will invalidate the old link. If you are logging into the Hub from a public computer, please remember to log out of the Hub to invalidate the link.</html>";


        $environment = "";
        if(ENVIRONMENT == 'TEST'){
            $environment = " ".ENVIRONMENT;
        }

        \Vanderbilt\HarmonistHubExternalModule\sendEmail(strtolower($people['email']), $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], $settings['hub_name']." Hub Access Link".$environment, $message,$people['record_id'],"Review Hub Access Sent",$pidsArray['PEOPLE']);
        $module->log("HUB: " . $pidsArray['PROJECTS'] . " - Token sent");
        #Default to 7 days if empty
        if($settings['accesslink_dur'] == ""){
            $settings['accesslink_dur'] = 7;
        }

        $arrayLogin[0]['access_token'] = $token;
        $arrayLogin[0]['token_expiration_d'] = date('Y-m-d', strtotime("+".$settings['accesslink_dur']." day"));
        $arrayLogin[0]['last_requested_token_d'] = date('Y-m-d H:i:s');
        if($people['first_ever_login_d'] == ""){
            $arrayLogin[0]['first_ever_login_d'] = date('Y-m-d H:i:s');
        }

        $json = json_encode($arrayLogin);
        $results = \Records::saveData($pidsArray['PEOPLE'], 'json', $json,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
        \Records::addRecordToRecordListCache($pidsArray['PEOPLE'], $people['record_id'],1);
    }else if($people == "" || strtolower($people['email']) != strtolower($email)){
        $message = "<html>This email address does not exist in the Hub.<br><br>".
                    "Your email address may not be registered in the system or you may be registered under a different email. Please e-mail ".$settings['hub_contact_email']." to confirm.</html>";

        $environment = "";
        if(ENVIRONMENT == 'TEST'){
            $environment = " ".ENVIRONMENT;
        }
        \Vanderbilt\HarmonistHubExternalModule\sendEmail(strtolower($email), $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], "Access Denied for ".$settings['hub_name']." Hub".$environment, $message,"Not in database","Access denied",$pidsArray['PEOPLE']);
    }
}


echo json_encode($result);
?>
