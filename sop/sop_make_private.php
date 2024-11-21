<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once dirname(dirname(__FILE__))."/projects.php";

$record = $_REQUEST['record'];

$Proj = new \Project($pidsArray['SOP']);
$event_id = $Proj->firstEventId;

$arraySOP = array();
$arraySOP[$record][$event_id]['sop_visibility'] = "1";
$results = \Records::saveData($pidsArray['SOP'], 'array', $arraySOP,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
\Records::addRecordToRecordListCache($pidsArray['SOP'], $record,1);

$RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', array('record_id' => $record));
$sop = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP,$pidsArray['SOP'])[0];

$date = new \DateTime();
$completion_time = $date->format('Y-m-d H:i:s');

$ProjSOPComments = new \Project($pidsArray['SOPCOMMENTS']);
$event_id_sopcomments = $ProjSOPComments->firstEventId;
$record_sopcomments = $module->framework->addAutoNumberedRecord($pidsArray['SOPCOMMENTS']);

$arraySOPComments = array();
$arraySOPComments[$record_sopcomments][$event_id_sopcomments]['other_action'] = "0";
$arraySOPComments[$record_sopcomments][$event_id_sopcomments]['responsecomplete_ts'] = $completion_time;
$arraySOPComments[$record_sopcomments][$event_id_sopcomments]['sop_id'] = $sop['record_id'];
$arraySOPComments[$record_sopcomments][$event_id_sopcomments]['response_person'] = $sop['sop_hubuser'];
$arraySOPComments[$record_sopcomments][$event_id_sopcomments]['comments'] = "Data Call made PRIVATE";
$arraySOPComments[$record_sopcomments][$event_id_sopcomments]['comment_ver'] = "0";

$person_region = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array("record_id" => $sop['sop_hubuser']),array('person_region'))[0]['person_region'];
$arraySOP[$record_sopcomments][$event_id_sopcomments]['response_region'] = $person_region;

$regioncode = \REDCap::getData($pidsArray['REGIONS'], 'json-array', array("record_id" => $person_region),array('region_code'))[0]['region_code'];
if(!empty($regioncode)){
    $arraySOP[$record_sopcomments][$event_id_sopcomments]['response_regioncode'] = $regioncode;
}

$results = \Records::saveData($pidsArray['SOPCOMMENTS'], 'array', $arraySOPComments,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
\Records::addRecordToRecordListCache($pidsArray['SOPCOMMENTS'], $record,1);
error_log(json_encode($results,JSON_PRETTY_PRINT))
?>