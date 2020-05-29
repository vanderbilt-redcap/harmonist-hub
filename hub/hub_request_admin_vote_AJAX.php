<?php
define('NOAUTH',true);
require_once dirname(dirname(__FILE__))."/projects.php";
$request_id = $_REQUEST['request_id'];

$RecordSetRM = \REDCap::getData(IEDEA_RMANAGER, 'array', array('request_id' => $request_id));
$request = getProjectInfoArrayRepeatingInstruments($RecordSetRM)[0];

$Proj = new \Project(IEDEA_RMANAGER);
$event_id_RM = $Proj->firstEventId;
$recordRM = array();
$recordRM[$request_id][$event_id_RM]["request_id"] = $request_id;

$region_vote_values = explode(',',$_REQUEST['region_vote_values']);
$all_votes_completed = true;
foreach ($region_vote_values as $votes_info){
    $region = explode('_',$votes_info)[0];
    $vote = (explode('_',$votes_info)[1] == "none")? "":explode('_',$votes_info)[1];

    if($request['region_vote_status'][$region] != $vote && $vote != ""){
        $date = new DateTime();
        $timestamp = $date->format('Y-m-d H:i:s');
        if ($region != "") {
            $recordRM[$request_id]['repeat_instances'][$event_id_RM]['dashboard_region_status'][$region]['region_vote_status'] = $vote;
            $recordRM[$request_id]['repeat_instances'][$event_id_RM]['dashboard_region_status'][$region]['region_response_status'] = "2";
            $recordRM[$request_id]['repeat_instances'][$event_id_RM]['dashboard_region_status'][$region]['region_update_ts'] = $timestamp;
            $recordRM[$request_id]['repeat_instances'][$event_id_RM]['dashboard_region_status'][$region]['region_close_ts'] = $timestamp;
        } else {
            $recordRM[$request_id][$event_id_RM]['region_vote_status'] = $vote;
            $recordRM[$request_id][$event_id_RM]['region_response_status'] = "2";
            $recordRM[$request_id][$event_id_RM]['region_update_ts'] = $timestamp;
            $recordRM[$request_id][$event_id_RM]['region_close_ts'] = $timestamp;
        }

        $ProjC = new \Project(IEDEA_COMMENTSVOTES);
        $event_id_comments = $ProjC->firstEventId;
        $comments_id = $module->framework->addAutoNumberedRecord(IEDEA_COMMENTSVOTES);
        $comments = array();
        $comments[$comments_id][$event_id_comments]['vote_now'] = "1";
        $comments[$comments_id][$event_id_comments]['pi_vote'] = $vote;
        $comments[$comments_id][$event_id_comments]['request_id'] = $request_id;
        $comments[$comments_id][$event_id_comments]['response_person'] = $_REQUEST['user'];
        $comments[$comments_id][$event_id_comments]['response_region'] = $region;
        $comments[$comments_id][$event_id_comments]['response_pi_level'] = $_REQUEST['pi_level'];
        $comments[$comments_id][$event_id_comments]['responsecomplete_ts'] = $timestamp;

        $RecordSetRegion = \REDCap::getData(IEDEA_REGIONS, 'array', array('record_id' => $_REQUEST['region']));
        $regions = getProjectInfoArrayRepeatingInstruments($RecordSetRegion)[0];
        $comments[$comments_id][$event_id_comments]['response_regioncode'] = $regions['region_code'];

        $RecordSetRegionComment = \REDCap::getData(IEDEA_REGIONS, 'array', array('record_id' => $region));
        $regions_comment = getProjectInfoArrayRepeatingInstruments($RecordSetRegionComment)[0];
        $comment = "Vote submitted for region (".$regions_comment['region_code'].") by Admin";
        $comments[$comments_id][$event_id_comments]['comments'] = $comment;
        $comments[$comments_id][$event_id_comments]['comments_and_votes_complete'] = "2";

        #Copy votes to Vote Outcomes (temporary)
        $region_code = strtolower($regions_comment['region_code']);
        $recordRM[$request_id][$event_id_RM]["vote_".$region_code] = $vote;

        $results = \Records::saveData(IEDEA_COMMENTSVOTES, 'array', $comments,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
        \Records::addRecordToRecordListCache(IEDEA_RMANAGER, $event_id_RM,1);
        \Records::addRecordToRecordListCache(IEDEA_COMMENTSVOTES, $event_id_comments,1);

    }
    if(explode('_',$votes_info)[1] == "none"){
        $all_votes_completed = false;
    }
}
if($all_votes_completed){
    if($request['detected_complete'][0] != "1") {
        $recordRM[$request_id][$event_id_RM]["detected_complete"] = array(0 => "1");//checkbox
        $recordRM[$request_id][$event_id_RM]["detected_complete_ts"] = date('Y-m-d H:i:s');
    }
}
$results = \Records::saveData(IEDEA_RMANAGER, 'array', $recordRM,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
?>