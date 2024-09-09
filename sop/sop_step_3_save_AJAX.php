<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once dirname(dirname(__FILE__))."/projects.php";

$record = htmlentities($_REQUEST['id'],ENT_QUOTES);

#checkboxes
if(!isset($_REQUEST['sop_downloaders_dummy'])){
    $downDummy = "0";
}else{
    $downDummy = "1";
}

$Proj = new \Project($pidsArray['SOP']);
$event_id = $Proj->firstEventId;

$sop_inclusion = $module->escape(($_REQUEST['sop_inclusion'] == "") ? "<i>None</i>" : $_REQUEST['sop_inclusion']);
$sop_exclusion = $module->escape(($_REQUEST['sop_exclusion'] == "") ? "<i>None</i>" : $_REQUEST['sop_exclusion']);
$sop_notes = $module->escape(($_REQUEST['sop_notes'] == "") ? "<i>None</i>" : $_REQUEST['sop_notes']);
$dataformat_notes = ($_REQUEST['dataformat_notes'] == "") ? "<i>None</i>" : $_REQUEST['dataformat_notes'];

$arraySOP = array();
$arraySOP[$record][$event_id]['sop_downloaders'] = htmlentities($_REQUEST['downloaders'],ENT_QUOTES);
$arraySOP[$record][$event_id]['sop_downloaders_dummy___1'] = $downDummy;
$arraySOP[$record][$event_id]['sop_inclusion'] = $sop_inclusion;
$arraySOP[$record][$event_id]['sop_exclusion'] = $sop_exclusion;
$arraySOP[$record][$event_id]['sop_notes'] = $sop_notes;
$arraySOP[$record][$event_id]['dataformat_notes'] = $sop_notes;
$arraySOP[$record][$event_id]['dataformat_notes'] = $dataformat_notes;
$arraySOP[$record][$event_id]['sop_due_d'] = htmlentities($_REQUEST['sop_due_d'],ENT_QUOTES);
$arraySOP[$record][$event_id]['sop_creator'] = htmlentities($_REQUEST['sop_creator'],ENT_QUOTES);
$arraySOP[$record][$event_id]['sop_creator_org'] = htmlentities($_REQUEST['sop_creator_org'],ENT_QUOTES);
$arraySOP[$record][$event_id]['sop_creator2'] = htmlentities($_REQUEST['sop_creator2'],ENT_QUOTES);
$arraySOP[$record][$event_id]['sop_creator2_org'] = htmlentities($_REQUEST['sop_creator2_org'],ENT_QUOTES);
$arraySOP[$record][$event_id]['sop_datacontact'] = htmlentities($_REQUEST['sop_datacontact'],ENT_QUOTES);
$arraySOP[$record][$event_id]['sop_datacontact_org'] = htmlentities($_REQUEST['sop_datacontact_org'],ENT_QUOTES);
$arraySOP[$record][$event_id]['dataformat_notes'] = htmlentities($_REQUEST['dataformat_notes'],ENT_QUOTES);

$dataformat_prefer_labels = $module->escape($module->getChoiceLabels('dataformat_prefer', $pidsArray['SOP']));
foreach($dataformat_prefer_labels as $dataformat_index => $value){
    $arraySOP[$record][$event_id]['dataformat_prefer___'.$dataformat_index] = "0";
}
$dataformat_prefer = "";
if(!empty($_REQUEST['dataformat_prefer'])){
    $dataformat_prefer = explode(',',htmlentities($_REQUEST['dataformat_prefer'],ENT_QUOTES));
    foreach ($dataformat_prefer as $dataformat) {
        $arraySOP[$record][$event_id]['dataformat_prefer___' . $dataformat] = "1";
    }
}

$date = new \DateTime();
$sop_updated_dt = $date->format('Y-m-d H:i:s');
$arraySOP[$record][$event_id]['sop_updated_dt'] = $sop_updated_dt;
$results = \Records::saveData($pidsArray['SOP'], 'array', $arraySOP,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
\Records::addRecordToRecordListCache($pidsArray['SOP'], $record,1);

$RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', array("record_id" => $record));
$data = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP,$pidsArray['SOP'])[0];
$data['sop_version_date'] = "Data Request Version: ".date('d F Y');
$data['sop_inclusion'] = filter_tags($sop_inclusion);
$data['sop_exclusion'] = filter_tags($sop_exclusion);
$data['sop_notes'] = filter_tags($sop_notes);
$data['dataformat_notes'] = filter_tags($dataformat_notes);

$date = new \DateTime(htmlentities($_REQUEST['sop_due_d']));
$data['sop_due_d_preview'] = $date->format('d F Y');

$data['selectConcept'] = $data['sop_concept_id'];

$RecordSetConcepts = \REDCap::getData($pidsArray['HARMONIST'], 'array', array("record_id" => $data['sop_concept_id']));
$concept = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConcepts,$pidsArray['HARMONIST'])[0];
$data['sop_concept_id'] = $concept['concept_id'];
$data['sop_concept_title'] = $concept['concept_title'];

if($_REQUEST['sop_creator'] != ""){
    $people = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array("record_id" => htmlentities($_REQUEST['sop_creator'],ENT_QUOTES)),array('record_id','firstname','lastname'))[0];
    $data['sop_creator_name'] = $people['firstname'].' '.$people['lastname'];
    $data['sop_creator_email'] = $people['email'];
}

if($_REQUEST['sop_creator2'] != ""){
    $people = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array("record_id" => htmlentities($_REQUEST['sop_creator2'],ENT_QUOTES)),array('record_id','firstname','lastname'))[0];
    $data['sop_creator2_name'] = $people['firstname'].' '.$people['lastname'];
    $data['sop_creator2_email'] = $people['email'];
}

if($_REQUEST['sop_datacontact'] != "") {
    $people = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array("record_id" => htmlentities($_REQUEST['sop_datacontact'],ENT_QUOTES)),array('record_id','firstname','lastname'))[0];
    $data['sop_datacontact_name'] = $people['firstname'].' '.$people['lastname'];
    $data['sop_datacontact_email'] = $people['email'];
}

$dataformat_prefer_text = "";
if($data['dataformat_prefer'] != ""){
    $dataformat_prefer = $module->getChoiceLabels('dataformat_prefer', $pidsArray['SOP']);
    foreach($dataformat_prefer as $dataid => $dataformat){
        foreach($data['dataformat_prefer'] as $dataf) {
            if($dataf == $dataid){
                $dataformat_prefer_text .= $dataformat.", ";
            }
        }
    }
    $data['dataformat_prefer_text']=rtrim($dataformat_prefer_text,", ");
}

echo json_encode($module->escape($data));
?>