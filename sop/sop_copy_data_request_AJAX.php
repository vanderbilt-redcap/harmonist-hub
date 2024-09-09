<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once dirname(dirname(__FILE__))."/projects.php";

$record_id = (int)$_REQUEST['id'];

$RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', array("record_id" => $record_id));
$sop = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP,$pidsArray['SOP'])[0];

$RecordSetConcepts = \REDCap::getData($pidsArray['HARMONIST'], 'array', array("record_id" => $sop['sop_concept_id']));
$concept_title = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConcepts,$pidsArray['HARMONIST'])[0]['concept_title'];

$date = new \DateTime();
$sop_created_dt = $date->format('Y-m-d H:i:s');

$Proj = new \Project($pidsArray['SOP']);
$event_id = $Proj->firstEventId;
$record = $module->framework->addAutoNumberedRecord($pidsArray['SOP']);
$sop_name = $record.". Data Request for ".$sop['sop_concept_id'].", ".$concept_title;

$arraySOP[$record][$event_id]['sop_name'] = $sop_name;
$arraySOP[$record][$event_id]['sop_status'] = "0";//DRAFT
$arraySOP[$record][$event_id]['sop_hubuser'] = $sop['sop_hubuser'];
$arraySOP[$record][$event_id]['sop_concept_id'] = $sop['sop_concept_id'];
$arraySOP[$record][$event_id]['sop_visibility'] = "1";
$arraySOP[$record][$event_id]['sop_active'] = "1";
$arraySOP[$record][$event_id]['sop_tablefields'] = $sop['sop_tablefields'];
$arraySOP[$record][$event_id]['sop_downloaders'] = $sop['downloaders'];
$arraySOP[$record][$event_id]['sop_inclusion'] = $sop['sop_inclusion'];
$arraySOP[$record][$event_id]['sop_exclusion'] = $sop['sop_exclusion'];
$arraySOP[$record][$event_id]['sop_notes'] = $sop['sop_notes'];
$arraySOP[$record][$event_id]['sop_due_d'] = $sop['sop_due_d'];
$arraySOP[$record][$event_id]['sop_creator'] = $sop['sop_creator'];
$arraySOP[$record][$event_id]['sop_creator2'] = $sop['sop_creator2'];
$arraySOP[$record][$event_id]['sop_datacontact'] = $sop['sop_datacontact'];
$arraySOP[$record][$event_id]['sop_extrapdf'] = $sop['sop_extrapdf'];
$arraySOP[$record][$event_id]['sop_finalpdf'] = $sop['sop_finalpdf'];
$arraySOP[$record][$event_id]['sop_downloaders'] = $sop['sop_downloaders'];
$arraySOP[$record][$event_id]['sop_downloaders_dummy'] = $sop['sop_downloaders_dummy'];
$arraySOP[$record][$event_id]['dataformat_prefer'] = $sop['dataformat_prefer'];
$arraySOP[$record][$event_id]['dataformat_notes'] = $sop['dataformat_notes'];
$arraySOP[$record][$event_id]['sop_created_dt'] = $sop_created_dt;
$arraySOP[$record][$event_id]['sop_updated_dt'] = $sop_created_dt;

$results = \Records::saveData($pidsArray['SOP'], 'array', $arraySOP,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);

echo json_encode($record);