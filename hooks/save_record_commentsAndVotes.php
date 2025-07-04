<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include_once(__DIR__ ."/../projects.php");
include_once(__DIR__ ."/../functions.php");
use ExternalModules\ExternalModules;

#Get Projects ID's
$hub_mapper = $this->getProjectSetting('hub-mapper');
$pidsArray = REDCapManagement::getPIDsArray($hub_mapper);

$comment = \REDCap::getData($project_id, 'json-array', array('record_id' => $record))[0];
$vanderbilt_emailTrigger = ExternalModules::getModuleInstance('vanderbilt_emailTrigger');
if(($comment[$instrument.'_complete'] == '2' || $vanderbilt_emailTrigger->getEmailTriggerRequested()) && $instrument == 'comments_and_votes'){
    $data = \REDCap::getData($project_id, 'array',$record,$instrument.'_complete', null,null,false,false,true);

    $completion_time = ($comment[$instrument.'_complete'] == '2')?$data[$record][$event_id][$instrument.'_timestamp']:"";
    if(empty($completion_time)){
        $date = new \DateTime();
        $completion_time = $date->format('Y-m-d H:i:s');
    }

    $arrayCV = array();
    $arrayCV[$record][$event_id]['responsecomplete_ts'] = $completion_time;
    $regions = \REDCap::getData($pidsArray['REGIONS'], 'json-array', array('record_id' => $comment['response_region']))[0];
    if(!empty($regions)){
        $arrayCV[$record][$event_id]['response_regioncode'] = $regions['region_code'];
    }

    $RecordSetRM = \REDCap::getData($pidsArray['RMANAGER'], 'array', array('request_id' => $comment['request_id']));
    $request = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRM,$pidsArray['RMANAGER'])[0];
    if(!empty($request)){
        $arrayCV[$record][$event_id]['request_type'] = $request['request_type'];
        $all_votes_completed = true;
        foreach ($request['responding_region'] as $instanceId => $resp_region){
            if($resp_region == $comment['response_region']){

                $array_repeat_instances = array();
                $aux = array();
                $aux['responding_region'] = $comment['response_region'];
                $aux['region_update_ts'] = $completion_time;

                if($comment['pi_vote'] != ""){
                    //Complete
                    $aux['region_response_status'] = '2';
                    $aux['region_vote_status'] = $comment['pi_vote'];
                    $date = new \DateTime();
                    $aux['region_close_ts'] = $date->format('Y-m-d H:i:s');
                }else if($request['region_response_status'][$instanceId] != '2'){
                    //Progress
                    $aux['region_response_status'] = '1';
                }

                $Proj = new \Project($pidsArray['RMANAGER']);
                $event_id_RM = $Proj->firstEventId;
                $array_repeat_instances[$comment['request_id']]['repeat_instances'][$event_id_RM]['dashboard_voting_status'][$instanceId] = $aux;
                $results = \REDCap::saveData($pidsArray['RMANAGER'], 'array', $array_repeat_instances,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false, 1, false, '');
                break;
            }

            $voting_region = \REDCap::getData($pidsArray['REGIONS'], 'json-array', array('record_id' => $resp_region),array('voteregion_y'))[0];
            if(array_key_exists('voteregion_y',$voting_region) && $voting_region['voteregion_y'] == "1" && array_key_exists('region_vote_status',$voting_region) && (!array_key_exists($instanceId,$voting_region['region_vote_status']) ||  (array_key_exists($instanceId,$voting_region['region_vote_status']) && $request['region_vote_status'][$instanceId] == ""))){
                $all_votes_completed = false;
            }
        }
        #If all votes complete we check the checkbox
        if($all_votes_completed){
            if($request['detected_complete'][1] != "1") {
                $Proj = new \Project($pidsArray['RMANAGER']);
                $event_id_RM = $Proj->firstEventId;
                $arrayRM = array();
                $arrayRM[$comment['request_id']][$event_id_RM]['detected_complete'] = array(1=>"1");//checkbox
                $arrayRM[$comment['request_id']][$event_id_RM]['detected_complete_ts'] = date('Y-m-d H:i:s');
                $results = \Records::saveData($pidsArray['RMANAGER'], 'array', $arrayRM,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
                \REDCap::logEvent("Comments and Votes Hook", "detected_complete(1) = checked", NULL, $comment['request_id'], $event_id_RM, $pidsArray['RMANAGER']);
            }
        }

        //We update the Revision File counter
        if($comment['author_revision_y'] == '1' && $comment['revision_counter'] == ''){
            if($request['revision_counter_total'] == '' || $request['revision_counter_total'] == '0'){
                $revision_counter_total = 1;
            }else{
                $revision_counter_total = $request['revision_counter_total'] + 1;
            }
            $arrayComment = array(array('record_id' => $comment['record_id'], 'revision_counter' => $revision_counter_total));
            $jsonComment = json_encode($arrayComment);
            $results = \Records::saveData($pidsArray['RMANAGER'], 'json', $jsonComment,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);

            $arrayRM = array(array('request_id' => $comment['request_id'],'revision_counter_total' => $revision_counter_total));
            $jsonRM = json_encode($arrayRM);
            $results = \Records::saveData($pidsArray['RMANAGER'], 'json', $jsonRM,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);

            \Records::addRecordToRecordListCache($pidsArray['RMANAGER'], $comment['request_id'],1);
            \Records::addRecordToRecordListCache($project_id, $record,1);
        }

        //Info for the email sent
        $arrayCV[$record][$event_id]['contact_email'] = $request['contact_email'];
        $arrayCV[$record][$event_id]['request_title'] = $request['request_title'];
        $arrayCV[$record][$event_id]['contactnotification_y'] = array(1=>($request['contactnotification_y'][1] == "")?"0":"1");//checkbox
        $results = \Records::saveData($project_id, 'array', $arrayCV,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
        if($request['follow_activity'] != ''){
            $settings = \REDCap::getData($pidsArray['SETTINGS'], 'json-array')[0];

            $request_type_label = $this->getChoiceLabels('request_type', $pidsArray['RMANAGER']);

            $array_userid = explode(',',$request['follow_activity']);
            foreach ($array_userid as $user_id){
                $people = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array('record_id' =>$user_id))[0];

                $environment = "";
                if(ENVIRONMENT == 'TEST') {
                    $environment = " " . ENVIRONMENT." - ";
                }

                $name = getPeopleName($pidsArray['PEOPLE'], $comment['response_person'],"");

                $comment_time ="";
                if(!empty($completion_time)){
                    $dateComment = new \DateTime($completion_time);
                    $dateComment->modify("+1 hours");
                    $comment_time = $dateComment->format("Y-m-d H:i:s");
                }

                $gd_files = "<ol>";
                if(!empty($comment['revised_file'])){
                    $gd_files .= "<li>".getFileLink($this, $pidsArray['PROJECTS'], $comment['revised_file'],'',0,$secret_key,$secret_iv,$people['record_id'],"")."</li>";
                }
                else{
                    $gd_files .= "<li><i>None</i></li>";
                }

                if(!empty($comment['extra_revfile1'])){
                    $gd_files .= "<li>".getFileLink($this, $pidsArray['PROJECTS'], $project_id, $comment['extra_revfile1'],'',0,$secret_key,$secret_iv,$people['record_id'],"")."</li>";
                }
                if(!empty($comment['extra_revfile2'])){
                    $gd_files .= "<li>".getFileLink($this, $pidsArray['PROJECTS'], $comment['extra_revfile2'],'',0,$secret_key,$secret_iv,$people['record_id'],"")."</li>";
                }
                $gd_files .= "</ol>";


                /*** GROUP DISCUSION ***/
                $text = "";
                if ($comment['author_revision_y'] == '1' && $comment['revision_counter'] != '') {
                    $text = "<div class='request_revision_text'>revision ".$comment['revision_counter']."</div>";
                }
                $comment_vote = "<i>None</i>";
                if($comment['pi_vote'] != ''){
                    if ($comment['pi_vote'] == "1") {
                        //Approved
                        $comment_vote = '<img src="'.APP_PATH_MODULE.'/img/vote_approved.jpg" alt="Approved">&nbsp;&nbsp;<span style="color:#5cb85c;">Approved</span>';
                    } else if ($comment['pi_vote'] == "0") {
                        //Not Approved
                        $comment_vote = '<img src="'.APP_PATH_MODULE.'/img/vote_notapproved.jpg" alt="Not Approved">&nbsp;&nbsp;<span style="color:#e74c3c">Not Approved</span>';
                    } else if ($comment['pi_vote'] == "9") {
                        //Complete
                        $comment_vote = '<img src="'.APP_PATH_MODULE.'/img/vote_abstained.jpg" alt="Abstained">&nbsp;&nbsp;<span  style="color:#8c8c8c">Abstained</span>';
                    } else {
                        $comment_vote = '<img src="'.APP_PATH_MODULE.'/img/vote_abstained.jpg" alt="Abstained">&nbsp;&nbsp;<span  style="color:#8c8c8c">Abstained</span>';
                    }
                }

                $url = $this->getUrl("index.php")."&NOAUTH&option=hub&pid=".$pidsArray['PROJECTS'].'&record='.$request['request_id'];

                $subject = $environment." ".$settings['hub_name']." Request #".$request['request_id']." feedback posted: ".$name.", ".$comment_time;

                $hub_organization = "";
                if(!empty($settings["hub_organization"])){
                    $hub_organization =' (<a href="'.$settings["hub_organization"].'">'.$settings["hub_organization"].'</a>)';
                }

                $hub_name_req_email = "";
                if(array_key_exists('hub_name_req_email',$settings) && !empty($settings["hub_name_req_email"])){
                    $hub_name_req_email = $settings["hub_name_req_email"];
                }

                $message = '<h2>Feedback Posted on '.$request_type_label[$request['request_type']].' Request  #'.$request['request_id'].'</h2>
                            <p>A new comment, file, or vote for the following '.$hub_name_req_email.' request has been posted on the Hub.</p>
                            <p><strong>Request Title:</strong>&nbsp; <a href="'.$url.'">'.$request['request_title'].'</a>
                            <br /><strong>Contact Person:</strong>&nbsp; '.$request['contact_name'].', '.$request['contact_email'].'</p>  
                            <h2>Feedback</h2>
                            <p><strong>Feedback provided by:</strong>&nbsp;'.$name.'</p>
                            <p><strong>Comments:</strong>&nbsp;'.nl2br($comment['comments']).'</p>
                            <p><strong>Vote:</strong>&nbsp; '.$comment_vote.'</p>
                            <p><strong>Writing group nominee(s):</strong>&nbsp; '.$comment['writing_group'].'</p>
                            <p><strong>Uploaded Files:</strong></p>
                            '.$gd_files.'
                            <p>&nbsp;</p>
                            <p><span style="color: #999999; font-size: 11px;">This email has been automatically generated by the '.$settings["hub_name"].' Hub system'.$hub_organization.'. You are receiving this email because you signed up to follow this Request. If someone incorrectly submitted this request on your behalf or if you believe you received this email in error, please contact <a href="mailto:'.$settings["hub_contact_email"].'">'.$settings["hub_contact_email"].'</a>.</span></p>
                            <p><span style="color: #999999; font-size: 11px;">Want to stop following this Request? Here\'s a <a href="'.$this->getUrl('index.php').'&NOAUTH&option=unf&record='.$request['request_id'].'">quick link to visit the Hub and unfollow this request</a>.</span></p>
                            ';
                sendEmail(strtolower($people['email']), $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], $subject, $message,$people['record_id'],"New request feedback posted", $pidsArray['RMANAGER']);
            }
        }
    }
}
?>
