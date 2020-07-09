<?php
define('NOAUTH',true);
require_once dirname(dirname(__FILE__))."/projects.php";

$checked_values = $_REQUEST['checked_values'];
$record = $_REQUEST['id'];

$Proj = new \Project(IEDEA_SOP);
$event_id = $Proj->firstEventId;

$arraySOP = array();
$arraySOP[$record][$event_id]['sop_tablefields'] = $checked_values;

$date = new DateTime();
$sop_updated_dt = $date->format('Y-m-d H:i:s');
$arraySOP[$record][$event_id]['sop_updated_dt'] = $sop_updated_dt;
$results = \Records::saveData(IEDEA_SOP, 'array', $arraySOP,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
\Records::addRecordToRecordListCache(IEDEA_SOP, $record,1);

$RecordSetSOP = \REDCap::getData(IEDEA_SOP, 'array', array("record_id" => $record));
$data = getProjectInfoArrayRepeatingInstruments($RecordSetSOP)[0];
echo json_encode($data);
?>