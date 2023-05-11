<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include_once(__DIR__ ."/../projects.php");
include_once(__DIR__ ."/../functions.php");
use ExternalModules\ExternalModules;

#Get Projects ID's
$hub_mapper = $this->getProjectSetting('hub-mapper');
$pidsArray = REDCapManagement::getPIDsArray($hub_mapper);

$RecordSetComment = \REDCap::getData($project_id, 'array', array('record_id' => $record));
$comment = ProjectData::getProjectInfoArray($RecordSetComment)[0];

$vanderbilt_emailTrigger = ExternalModules::getModuleInstance('vanderbilt_emailTrigger');

if(($comment[$instrument.'_complete'] == '2' || $vanderbilt_emailTrigger->getEmailTriggerRequested()) && $instrument == 'sop_comments'){
    $data = \REDCap::getData($project_id, 'array',$record,$instrument.'_complete', null,null,false,false,true);

    $completion_time = ($comment[$instrument.'_complete'] == '2')?$data[$record][$event_id][$instrument.'_timestamp']:"";
    if(empty($completion_time)){
        $date = new \DateTime();
        $completion_time = $date->format('Y-m-d H:i:s');
    }

    $arrayCV = array();
    $arrayCV[$record][$event_id]['responsecomplete_ts'] = $completion_time;

    $recordsRegions = \REDCap::getData($pidsArray['REGIONS'], 'array', array('record_id' => $comment['response_region']));
    $regions = ProjectData::getProjectInfoArray($recordsRegions)[0];
    if(!empty($regions)){
        $arrayCV[$record][$event_id]['response_regioncode'] = $regions['region_code'];
    }
    $results = \Records::saveData($project_id, 'array', $arrayCV,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
    \Records::addRecordToRecordListCache($project_id, $record,1);

    $RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', array('record_id' => $comment['sop_id']));
    $sop = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP)[0];
    if(!empty($sop)){
        if($sop['follow_activity'] != ''){
            $RecordSetSettings = \REDCap::getData($pidsArray['SETTINGS'], 'array');
            $settings = ProjectData::getProjectInfoArray($RecordSetSettings)[0];

            $array_userid = explode(',',$sop['follow_activity']);
            foreach ($array_userid as $user_id){
                $RecordSetEmail = \REDCap::getData($pidsArray['PEOPLE'], 'array', array('record_id' => $user_id));
                $people = ProjectData::getProjectInfoArray($RecordSetEmail)[0];

                $RecordSetContact = \REDCap::getData($pidsArray['PEOPLE'], 'array', array('record_id' => $sop['sop_datacontact']));
                $data_contact = ProjectData::getProjectInfoArray($RecordSetContact)[0];

                $sender_email = "noreply.harmonist@vumc.org";
                if($settings['accesslink_sender_email'] != ""){
                    $sender_email = $settings['accesslink_sender_email'];
                }
                $environment = "";
                if(ENVIRONMENT == 'DEV' || ENVIRONMENT == 'TEST') {
                    $environment = " " . ENVIRONMENT." - ";
                }

                $name = \Vanderbilt\HarmonistHubExternalModule\getPeopleName($pidsArray['PEOPLE'], $comment['response_person'],"");

                $comment_time ="";
                if(!empty($completion_time)){
                    $dateComment = new DateTime($completion_time);
                    $dateComment->modify("+1 hours");
                    $comment_time = $dateComment->format("Y-m-d H:i:s");
                }

                $gd_files = "<ol>";
                if(!empty($comment['revised_file'])){
                    $gd_files .= "<li>".\Vanderbilt\HarmonistHubExternalModule\getFileLink($this, $pidsArray['PROJECTS'], $comment['revised_file'],'',0,$secret_key,$secret_iv,$people['record_id'],"")."</li>";
                }
                else{
                    $gd_files .= "<li><i>None</i></li>";
                }

                $gd_files .= "</ol>";

                $request_type_label = $this->getChoiceLabels('request_type', $pidsArray['RMANAGER']);

                $url = $this->getUrl("index.php")."&NOAUTH&token=".$people['access_token']."&option=sop&record=".$comment['sop_id']."&pid=".$pidsArray['PROJECTS'];

                $subject = $environment." ".$settings['hub_name']." Data Request #".$sop['record_id']." feedback posted: ".$name.", ".$comment_time;

                $message = '<h2>Feedback Posted on '.$request_type_label[$sop['request_type']].' Data Request  #'.$sop['record_id'].'</h2>
                                <p>A new comment or file for the following '.$settings["hub_name"].' data request has been posted on the Hub.</p>
                                <p><strong>Data Request Title:</strong>&nbsp; <a href="'.$url.'">'.$sop['sop_name'].'</a>
                                <br /><strong>Contact Person:</strong>&nbsp; '.$data_contact['firstname'].' '.$data_contact['lastname'].', '.$data_contact['email'].'</p>
                                <h2>Feedback</h2>
                                <p><strong>Comments:</strong>&nbsp;'.nl2br($comment['comments']).'</p>
                                <p><strong>Uploaded Files:</strong></p>
                                '.$gd_files.'
                                <p>&nbsp;</p>
                                <p><span style="color: #999999; font-size: 11px;">This email has been automatically generated by the '.$settings["hub_name"].' Hub system (<a href="'.$settings["hub_organization"].'">'.$settings["hub_organization"].'</a>). You are receiving this email because you signed up to follow this Data Request. If someone incorrectly submitted this request on your behalf or if you believe you received this email in error, please contact <a href="mailto:'.$settings["hub_contact_email"].'">'.$settings["hub_contact_email"].'</a>.</span></p>
                                <p><span style="color: #999999; font-size: 11px;">Want to stop following this Data Request? Here\'s a <a href="'.$this->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&token='.$people['access_token'].'&option=und&record='.$sop['record_id'].'">quick link to visit the Hub</a>.</span></p>
                                ';

                \Vanderbilt\HarmonistHubExternalModule\sendEmail(strtolower($people['email']), $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], $subject, $message,$people['record_id'],"New data request feedback posted",$pidsArray['SOP']);
            }
        }
    }
}
?>
