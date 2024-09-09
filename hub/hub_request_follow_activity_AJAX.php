<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once dirname(dirname(__FILE__))."/projects.php";

$userid = $_REQUEST['userid'];
$option = $_REQUEST['option'];
$request_id = $_REQUEST['record'];

$RecordSetRM = \REDCap::getData($pidsArray['RMANAGER'], 'array', array('request_id' => $request_id));
$follow_activity = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRM,$pidsArray['RMANAGER'])[0]['follow_activity'];
$array_userid = explode(',',$follow_activity);

$Proj = new \Project($pidsArray['RMANAGER']);
$event_id_RM = $Proj->firstEventId;
$recordRM = array();
if($option == "0"){
    #UNFOLLOW
    if (($key = array_search($userid, $array_userid)) !== false) {
        unset($array_userid[$key]);
        $string_userid = implode(",",$array_userid);
        $recordRM[$request_id][$event_id_RM]["follow_activity"] = $string_userid;
    }
    $button = '<button onclick="follow_activity(\'1\',\''.$userid.'\',\''.$request_id.'\',\''.$module->getUrl('hub/hub_request_follow_activity_AJAX.php').'\')" class="btn btn-default actionbutton"><i class="fa fa-plus-square"></i> <span class="hidden-xs">Follow Activity</span></button>';
}else if($option == "1"){
    #FOLLOW
    if($follow_activity == ''){
        $recordRM[$request_id][$event_id_RM]["follow_activity"] = $userid;
    }else if (!in_array($userid,$array_userid)) {
        array_push($array_userid,$userid);
        $string_userid = implode(",",$array_userid);
        $recordRM[$request_id][$event_id_RM]["follow_activity"] = $string_userid;
    }
    $button = '<button onclick="follow_activity(\'0\',\''.$userid.'\',\''.$request_id.'\',\''.$module->getUrl('hub/hub_request_follow_activity_AJAX.php').'\')" class="btn btn-primary actionbutton"><i class="fa fa-check-square"></i> <span class="hidden-xs">Following</span></button>';
}
$results = \Records::saveData($pidsArray['RMANAGER'], 'array', $recordRM,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
\Records::addRecordToRecordListCache($pidsArray['RMANAGER'], $request_id,1);

echo json_encode($button);
?>