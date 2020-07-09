<?php
define('NOAUTH',true);
require_once dirname(dirname(__FILE__))."/projects.php";

$record = $_REQUEST['record'];

$Proj = new \Project(IEDEA_SOP);
$event_id = $Proj->firstEventId;

$arraySOP = array();
$arraySOP[$record][$event_id]['sop_visibility'] = "1";
$results = \Records::saveData(IEDEA_SOP, 'array', $arraySOP,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
\Records::addRecordToRecordListCache(IEDEA_SOP, $record,1);

$RecordSetSOP = \REDCap::getData(IEDEA_SOP, 'array', array('record_id' => $record));
$sop = getProjectInfoArrayRepeatingInstruments($RecordSetSOP)[0];

$date = new DateTime();
$completion_time = $date->format('Y-m-d H:i:s');

$ProjSOPComments = new \Project(IEDEA_SOPCOMMENTS);
$event_id_sopcomments = $ProjSOPComments->firstEventId;
$record_sopcomments = $module->framework->addAutoNumberedRecord(IEDEA_SOPCOMMENTS);

$arraySOPComments = array();
$arraySOPComments[$record_sopcomments][$event_id_sopcomments]['other_action'] = "0";
$arraySOPComments[$record_sopcomments][$event_id_sopcomments]['responsecomplete_ts'] = $completion_time;
$arraySOPComments[$record_sopcomments][$event_id_sopcomments]['sop_id'] = $sop['record_id'];
$arraySOPComments[$record_sopcomments][$event_id_sopcomments]['response_person'] = $sop['sop_hubuser'];
$arraySOPComments[$record_sopcomments][$event_id_sopcomments]['comments'] = "Data Call made PRIVATE";
$arraySOPComments[$record_sopcomments][$event_id_sopcomments]['comment_ver'] = "0";

$RecordSetPeople = \REDCap::getData(IEDEA_PEOPLE, 'array', array("record_id" => $sop['sop_hubuser']));
$person_region = getProjectInfoArray($RecordSetPeople)[0]['person_region'];
$arraySOP[$record_sopcomments][$event_id_sopcomments]['response_region'] = $person_region;

$RecordSetRegions = \REDCap::getData(IEDEA_REGIONS, 'array', array("record_id" => $person_region));
$regioncode = getProjectInfoArray($RecordSetRegions)[0]['region_code'];
if(!empty($regioncode)){
    $arraySOP[$record_sopcomments][$event_id_sopcomments]['response_regioncode'] = $regioncode;
}

$results = \Records::saveData(IEDEA_SOPCOMMENTS, 'array', $arraySOPComments,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
\Records::addRecordToRecordListCache(IEDEA_SOPCOMMENTS, $record,1);
?>