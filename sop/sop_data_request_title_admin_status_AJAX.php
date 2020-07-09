<?php
define('NOAUTH',true);
require_once dirname(dirname(__FILE__))."/projects.php";

$record_id = $_REQUEST['record_id'];

$RecordSetSOP = \REDCap::getData(IEDEA_SOP, 'array', array("record_id" => $record_id));
$sop = getProjectInfoArrayRepeatingInstruments($RecordSetSOP)[0];

$ProjSOP = new \Project(IEDEA_SOP);
$event_id_sop = $ProjSOP->firstEventId;

$ProjSOPComments = new \Project(IEDEA_SOPCOMMENTS);
$event_id_sopcomments = $ProjSOPComments->firstEventId;

$result = "";
$region_vote_values = explode(',',$_REQUEST['region_vote_values']);
$all_votes_completed = true;
foreach ($region_vote_values as $votes_info){
    $region = explode('_',$votes_info)[0];
    $vote = (explode('_',$votes_info)[1] == "0")? "":explode('_',$votes_info)[1];

    if($sop['data_response_status'][$region] != $vote && $vote != ""){
        $date = new DateTime();
        $timestamp = $date->format('Y-m-d H:i:s');

        $array_repeat_instances = array();
        $arraySOP = array();
        $arraySOP['data_response_status'] = $vote;
        $arraySOP['region_update_ts'] = $timestamp;
        $arraySOP['region_complete_ts'] = $timestamp;
        $array_repeat_instances[$record_id]['repeat_instances'][$event_id_sop]['region_participation_status'][$region] = $arraySOP;
        $results = \REDCap::saveData(IEDEA_SOP, 'array', $array_repeat_instances,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false, 1, false, '');
        \Records::addRecordToRecordListCache(IEDEA_SOP, $record_id, 1);

        $record_sopcomments = $module->framework->addAutoNumberedRecord(IEDEA_SOPCOMMENTS);
        $recordSOPComments = array();
        $recordSOPComments[$record_sopcomments][$event_id_sopcomments]['sop_id'] = $record_id;
        $recordSOPComments[$record_sopcomments][$event_id_sopcomments]['response_person'] = $_REQUEST['user'];
        $recordSOPComments[$record_sopcomments][$event_id_sopcomments]['response_region'] = $region;
        $recordSOPComments[$record_sopcomments][$event_id_sopcomments]['responsecomplete_ts'] = $timestamp;

        $RecordSetRegionsUp = \REDCap::getData(IEDEA_REGIONS, 'array', array('record_id' => $_REQUEST['region']));
        $region_code = getProjectInfoArray($RecordSetRegionsUp)[0]['region_code'];
        $recordSOPComments[$record_sopcomments][$event_id_sopcomments]['response_regioncode'] = $region_code;

        $RecordSetRegionsUp = \REDCap::getData(IEDEA_REGIONS, 'array', array('record_id' => $region));
        $region_comment = getProjectInfoArray($RecordSetRegionsUp)[0]['region_code'];
        $comment = "Status submitted for region (".$region_comment.") by Admin";
        $recordSOPComments[$record_sopcomments][$event_id_sopcomments]['comments'] = $comment;
        $recordSOPComments[$record_sopcomments][$event_id_sopcomments]['sop_comments_complete'] = "2";

        $results = \Records::saveData(IEDEA_SOPCOMMENTS, 'array', $recordSOPComments,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
        \Records::addRecordToRecordListCache(IEDEA_SOP, $record_sopcomments, 1);
    }
    if(explode('_',$votes_info)[1] == "0"){
        $all_votes_completed = false;
    }
}

//if($all_votes_completed){
//    if($sop['sop_closed_y'][0] != "1") {
//        $recordSOP->updateDetails(["sop_closed_y" => [0 => "1"]], true);
//        $recordSOP->updateDetails(["sop_closed_d" => date('Y-m-d H:i:s')], true);
//    }
//}

?>