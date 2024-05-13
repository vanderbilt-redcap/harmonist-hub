<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once dirname(dirname(__FILE__))."/projects.php";
$request_id = htmlentities($_REQUEST['request_id'],ENT_QUOTES);

$RecordSetRM = \REDCap::getData($pidsArray['RMANAGER'], 'array', array('request_id' => $request_id));
$request = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRM)[0];

$Proj = new \Project($pidsArray['RMANAGER']);
$event_id_RM = $Proj->firstEventId;
$recordRM = array();
$recordRM[$request_id][$event_id_RM]["request_id"] = $request_id;

$region_vote_values = explode(',',htmlentities($_REQUEST['region_vote_values'],ENT_QUOTES));
$all_votes_completed = true;
foreach ($region_vote_values as $votes_info){
    $region = explode('_',$votes_info)[0];
    $vote = (explode('_',$votes_info)[1] == "none")? "":explode('_',$votes_info)[1];

    if($request['region_vote_status'][$region] != $vote && $vote != ""){
        $date = new \DateTime();
        $timestamp = $date->format('Y-m-d H:i:s');
        if ($region != "") {
            $recordRM[$request_id]['repeat_instances'][$event_id_RM]['dashboard_voting_status'][$region]['region_vote_status'] = $vote;
            $recordRM[$request_id]['repeat_instances'][$event_id_RM]['dashboard_voting_status'][$region]['region_response_status'] = "2";
            $recordRM[$request_id]['repeat_instances'][$event_id_RM]['dashboard_voting_status'][$region]['region_update_ts'] = $timestamp;
            $recordRM[$request_id]['repeat_instances'][$event_id_RM]['dashboard_voting_status'][$region]['region_close_ts'] = $timestamp;
        } else {
            $recordRM[$request_id][$event_id_RM]['region_vote_status'] = $vote;
            $recordRM[$request_id][$event_id_RM]['region_response_status'] = "2";
            $recordRM[$request_id][$event_id_RM]['region_update_ts'] = $timestamp;
            $recordRM[$request_id][$event_id_RM]['region_close_ts'] = $timestamp;
        }

        $ProjC = new \Project($pidsArray['COMMENTSVOTES']);
        $event_id_comments = $ProjC->firstEventId;
        $comments_id = $module->framework->addAutoNumberedRecord($pidsArray['COMMENTSVOTES']);
        $comments = array();
        $comments[$comments_id][$event_id_comments]['vote_now'] = "1";
        $comments[$comments_id][$event_id_comments]['pi_vote'] = $vote;
        $comments[$comments_id][$event_id_comments]['request_id'] = $request_id;
        $comments[$comments_id][$event_id_comments]['response_person'] = $_REQUEST['user'];
        $comments[$comments_id][$event_id_comments]['response_region'] = $region;
        $comments[$comments_id][$event_id_comments]['response_pi_level'] = $_REQUEST['pi_level'];
        $comments[$comments_id][$event_id_comments]['responsecomplete_ts'] = $timestamp;

        $RecordSetRegion = \REDCap::getData($pidsArray['REGIONS'], 'array', array('record_id' => $_REQUEST['region']));
        $regions = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRegion)[0];
        $comments[$comments_id][$event_id_comments]['response_regioncode'] = $regions['region_code'];

        $RecordSetRegionComment = \REDCap::getData($pidsArray['REGIONS'], 'array', array('record_id' => $region));
        $regions_comment = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRegionComment)[0];
        $comment = "Vote submitted for region (".$regions_comment['region_code'].") by Admin";
        $comments[$comments_id][$event_id_comments]['comments'] = $comment;
        $comments[$comments_id][$event_id_comments]['comments_and_votes_complete'] = "2";

        $results = \Records::saveData($pidsArray['COMMENTSVOTES'], 'array', $comments,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
        \Records::addRecordToRecordListCache($pidsArray['RMANAGER'], $event_id_RM,1);
        \Records::addRecordToRecordListCache($pidsArray['COMMENTSVOTES'], $event_id_comments,1);

    }
    if(explode('_',$votes_info)[1] == "none"){
        $all_votes_completed = false;
    }
}
if($all_votes_completed){
    if($request['detected_complete'][0] != "1") {
        $recordRM[$request_id][$event_id_RM]["detected_complete"] = array(0 => "1");//checkbox
        $recordRM[$request_id][$event_id_RM]["detected_complete_ts"] = date('Y-m-d H:i:s');
        \REDCap::logEvent("Comments and Votes Hook", "detected_complete(1) = checked", NULL, $request_id, $event_id_RM, $pidsArray['RMANAGER']);
    }
}
$results = \Records::saveData($pidsArray['RMANAGER'], 'array', $recordRM,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
?>