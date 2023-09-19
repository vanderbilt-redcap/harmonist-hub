<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once dirname(dirname(__FILE__))."/projects.php";

$record = htmlentities($_REQUEST['id'],ENT_QUOTES);

$dataformat_prefer = explode(',',htmlentities($_REQUEST['dataformat_prefer'],ENT_QUOTES));

#checkboxes
if(!isset($_REQUEST['sop_downloaders_dummy'])){
    $downDummy = "0";
}else{
    $downDummy = "1";
}

$Proj = new \Project($pidsArray['SOP']);
$event_id = $Proj->firstEventId;

$sop_inclusion = (htmlentities($_REQUEST['sop_inclusion'],ENT_QUOTES) == "") ? "<i>None</i>" : htmlentities($_REQUEST['sop_inclusion'],ENT_QUOTES);
$sop_exclusion = (htmlentities($_REQUEST['sop_exclusion'],ENT_QUOTES) == "") ? "<i>None</i>" : htmlentities($_REQUEST['sop_exclusion'],ENT_QUOTES);
$sop_notes = (htmlentities($_REQUEST['sop_notes'],ENT_QUOTES) == "") ? "<i>None</i>" : htmlentities($_REQUEST['sop_notes'],ENT_QUOTES);

$arraySOP = array();
$arraySOP[$record][$event_id]['sop_downloaders'] = htmlentities($_REQUEST['downloaders'],ENT_QUOTES);
$arraySOP[$record][$event_id]['sop_downloaders_dummy'][1] = $downDummy;
$arraySOP[$record][$event_id]['sop_inclusion'] = $sop_inclusion;
$arraySOP[$record][$event_id]['sop_exclusion'] = $sop_exclusion;
$arraySOP[$record][$event_id]['sop_notes'] = $sop_notes;
$arraySOP[$record][$event_id]['sop_due_d'] = htmlentities($_REQUEST['sop_due_d'],ENT_QUOTES);
$arraySOP[$record][$event_id]['sop_creator'] = htmlentities($_REQUEST['sop_creator'],ENT_QUOTES);
$arraySOP[$record][$event_id]['sop_creator_org'] = htmlentities($_REQUEST['sop_creator_org'],ENT_QUOTES);
$arraySOP[$record][$event_id]['sop_creator2'] = htmlentities($_REQUEST['sop_creator2'],ENT_QUOTES);
$arraySOP[$record][$event_id]['sop_creator2_org'] = htmlentities($_REQUEST['sop_creator2_org'],ENT_QUOTES);
$arraySOP[$record][$event_id]['sop_datacontact'] = htmlentities($_REQUEST['sop_datacontact'],ENT_QUOTES);
$arraySOP[$record][$event_id]['sop_datacontact_org'] = htmlentities($_REQUEST['sop_datacontact_org'],ENT_QUOTES);
$arraySOP[$record][$event_id]['dataformat_prefer'] = array(1 =>(htmlentities($_REQUEST['dataformat_prefer'],ENT_QUOTES) == "") ? array() : htmlentities($_REQUEST['dataformat_prefer'],ENT_QUOTES));//checkbox
$arraySOP[$record][$event_id]['dataformat_notes'] = htmlentities($_REQUEST['dataformat_notes'],ENT_QUOTES);

$date = new \DateTime();
$sop_updated_dt = $date->format('Y-m-d H:i:s');
$arraySOP[$record][$event_id]['sop_updated_dt'] = $sop_updated_dt;
$results = \Records::saveData($pidsArray['SOP'], 'array', $arraySOP,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
\Records::addRecordToRecordListCache($pidsArray['SOP'], $record,1);

$RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', array("record_id" => $record));
$data = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP)[0];
$data['sop_version_date'] = "Data Request Version: ".date('d F Y');
$data['sop_inclusion'] = filter_tags($sop_inclusion);
$data['sop_exclusion'] = filter_tags($sop_exclusion);
$data['sop_notes'] = filter_tags($sop_notes);

$date = new \DateTime(htmlentities($_REQUEST['sop_due_d']));
$data['sop_due_d_preview'] = $date->format('d F Y');

$data['selectConcept'] = $data['sop_concept_id'];

$RecordSetConcepts = \REDCap::getData($pidsArray['HARMONIST'], 'array', array("record_id" => $data['sop_concept_id']));
$concept = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConcepts)[0];
$data['sop_concept_id'] = $concept['concept_id'];
$data['sop_concept_title'] = $concept['concept_title'];

if($_REQUEST['sop_creator'] != ""){
    $RecordSetPeople = \REDCap::getData($pidsArray['PEOPLE'], 'array', array("record_id" => htmlentities($_REQUEST['sop_creator'],ENT_QUOTES)));
    $people = ProjectData::getProjectInfoArray($RecordSetPeople)[0];
    $data['sop_creator_name'] = $people['firstname'].' '.$people['lastname'];
    $data['sop_creator_email'] = $people['email'];
}

if($_REQUEST['sop_creator2'] != ""){
    $RecordSetPeople = \REDCap::getData($pidsArray['PEOPLE'], 'array', array("record_id" => htmlentities($_REQUEST['sop_creator2'],ENT_QUOTES)));
    $people = ProjectData::getProjectInfoArray($RecordSetPeople)[0];
    $data['sop_creator2_name'] = $people['firstname'].' '.$people['lastname'];
    $data['sop_creator2_email'] = $people['email'];
}

if($_REQUEST['sop_datacontact'] != "") {
    $RecordSetPeople = \REDCap::getData($pidsArray['PEOPLE'], 'array', array("record_id" => htmlentities($_REQUEST['sop_datacontact'],ENT_QUOTES)));
    $people = ProjectData::getProjectInfoArray($RecordSetPeople)[0];
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

echo json_encode($data);
?>