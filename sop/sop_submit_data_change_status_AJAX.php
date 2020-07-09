<?php
define('NOAUTH',true);
require_once dirname(dirname(__FILE__))."/projects.php";

$record = $_REQUEST['status_record'];
$data_response_notes = $_REQUEST['data_response_notes'];
$status = $_REQUEST['status'];
$region = $_REQUEST['region'];

$Proj = new \Project(IEDEA_SOP);
$event_id = $Proj->firstEventId;

$array_repeat_instances = array();
$arraySOP = array();
$arraySOP['data_response_status'] = $status;
$arraySOP['data_response_notes'] = $data_response_notes;
$arraySOP['region_update_ts'] = date("Y-m-d H:i:s");

if($status == "2"){
    $arraySOP['region_complete_ts'] = date("Y-m-d H:i:s");
}
$array_repeat_instances[$record]['repeat_instances'][$event_id]['region_participation_status'][$region] = $arraySOP;
$results = \REDCap::saveData(IEDEA_SOP, 'array', $array_repeat_instances,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false, 1, false, '');
\Records::addRecordToRecordListCache(IEDEA_SOP, $record, 1);
?>