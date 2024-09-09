<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once dirname(dirname(__FILE__))."/projects.php";

$checked_values = $_REQUEST['checked_values'];
$record = $_REQUEST['id'];

$Proj = new \Project($pidsArray['SOP']);
$event_id = $Proj->firstEventId;

$arraySOP = array();
$arraySOP[$record][$event_id]['sop_tablefields'] = $checked_values;

$date = new \DateTime();
$sop_updated_dt = $date->format('Y-m-d H:i:s');
$arraySOP[$record][$event_id]['sop_updated_dt'] = $sop_updated_dt;
$results = \Records::saveData($pidsArray['SOP'], 'array', $arraySOP,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
\Records::addRecordToRecordListCache($pidsArray['SOP'], $record,1);

$RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', array("record_id" => $record));
$data = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP,$pidsArray['SOP'])[0];
echo json_encode($module->escape($data));
?>