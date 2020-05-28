<?php
define('NOAUTH',true);
require_once dirname(dirname(__FILE__))."/base.php";

$userid = $_REQUEST['userid'];
$option = $_REQUEST['option'];
$request_id = $_REQUEST['record'];

$projectRM = new \Plugin\Project(IEDEA_RMANAGER);
$recordRM = \Plugin\Record::createRecordFromId($projectRM, $request_id);
$RecordSetRM = new \Plugin\RecordSet($projectRM, array('request_id' => $request_id));
$follow_activity = $RecordSetRM->getDetails()[0]['follow_activity'];
$array_userid = explode(',',$follow_activity);

if($option == "0"){
    #UNFOLLOW
    if (($key = array_search($userid, $array_userid)) !== false) {
        unset($array_userid[$key]);
        $string_userid = implode(",",$array_userid);
        $recordRM->updateDetails(["follow_activity" => $string_userid], true);
    }
    $button = '<button onclick="follow_activity(\'1\',\''.$userid.'\',\''.$request_id.'\',\'hub/hub_request_follow_activity_AJAX.php\')" class="btn btn-default actionbutton"><i class="fa fa-plus-square"></i> <span class="hidden-xs">Follow Activity</span></button>';
}else if($option == "1"){
    #FOLLOW
    if($follow_activity == ''){
        $recordRM->updateDetails(["follow_activity" => $userid], true);
    }else if (!in_array($userid,$array_userid)) {
        array_push($array_userid,$userid);
        $string_userid = implode(",",$array_userid);
        $recordRM->updateDetails(["follow_activity" => $string_userid], true);
    }
    $button = '<button onclick="follow_activity(\'0\',\''.$userid.'\',\''.$request_id.'\',\'hub/hub_request_follow_activity_AJAX.php\')" class="btn btn-primary actionbutton"><i class="fa fa-check-square"></i> <span class="hidden-xs">Following</span></button>';
}
\Records::addRecordToRecordListCache($projectRM->getProjectId(), $recordRM->getId(),$projectRM->getArmNum());

echo json_encode($button);
?>