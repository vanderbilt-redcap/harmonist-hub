<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once dirname(dirname(__FILE__))."/projects.php";

$record = $_REQUEST['id'];

$dataformat_prefer = explode(',',$_REQUEST['dataformat_prefer']);

#checkboxes
if(!isset($_REQUEST['sop_downloaders_dummy'])){
    $downDummy = "0";
}else{
    $downDummy = "1";
}

$Proj = new \Project(IEDEA_SOP);
$event_id = $Proj->firstEventId;

$arraySOP = array();
$arraySOP[$record][$event_id]['sop_downloaders'] = $_REQUEST['downloaders'];
$arraySOP[$record][$event_id]['sop_downloaders_dummy'] = $downDummy;
$arraySOP[$record][$event_id]['sop_inclusion'] = $_REQUEST['sop_inclusion'];
$arraySOP[$record][$event_id]['sop_exclusion'] = $_REQUEST['sop_exclusion'];
$arraySOP[$record][$event_id]['sop_notes'] = $_REQUEST['sop_notes'];
$arraySOP[$record][$event_id]['sop_due_d'] = $_REQUEST['sop_due_d'];
$arraySOP[$record][$event_id]['sop_creator'] = $_REQUEST['sop_creator'];
$arraySOP[$record][$event_id]['sop_creator_org'] = $_REQUEST['sop_creator_org'];
$arraySOP[$record][$event_id]['sop_creator2'] = $_REQUEST['sop_creator2'];
$arraySOP[$record][$event_id]['sop_creator2_org'] = $_REQUEST['sop_creator2_org'];
$arraySOP[$record][$event_id]['sop_datacontact'] = $_REQUEST['sop_datacontact'];
$arraySOP[$record][$event_id]['sop_datacontact_org'] = $_REQUEST['sop_datacontact_org'];
$arraySOP[$record][$event_id]['dataformat_prefer'] = $_REQUEST['dataformat_prefer'];
$arraySOP[$record][$event_id]['dataformat_notes'] = $_REQUEST['dataformat_notes'];

$date = new \DateTime();
$sop_updated_dt = $date->format('Y-m-d H:i:s');
$arraySOP[$record][$event_id]['sop_updated_dt'] = $sop_updated_dt;
$results = \Records::saveData(IEDEA_SOP, 'array', $arraySOP,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
\Records::addRecordToRecordListCache(IEDEA_SOP, $record,1);

$RecordSetSOP = \REDCap::getData(IEDEA_SOP, 'array', array("record_id" => $record));
$data = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP)[0];
$data['sop_version_date'] = "Data Request Version: ".date('d F Y');

$date = new \DateTime($_REQUEST['sop_due_d']);
$data['sop_due_d_preview'] = $date->format('d F Y');

$data['selectConcept'] = $data['sop_concept_id'];

$RecordSetConcepts = \REDCap::getData(IEDEA_HARMONIST, 'array', array("record_id" => $data['sop_concept_id']));
$concept = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConcepts)[0];
$data['sop_concept_id'] = $concept['concept_id'];
$data['sop_concept_title'] = $concept['concept_title'];

if($_REQUEST['sop_creator'] != ""){
    $RecordSetPeople = \REDCap::getData(IEDEA_PEOPLE, 'array', array("record_id" => $_REQUEST['sop_creator']));
    $people = ProjectData::getProjectInfoArray($RecordSetPeople)[0];
    $data['sop_creator_name'] = $people['firstname'].' '.$people['lastname'];
    $data['sop_creator_email'] = $people['email'];
}

if($_REQUEST['sop_creator2'] != ""){
    $RecordSetPeople = \REDCap::getData(IEDEA_PEOPLE, 'array', array("record_id" => $_REQUEST['sop_creator2']));
    $people = ProjectData::getProjectInfoArray($RecordSetPeople)[0];
    $data['sop_creator2_name'] = $people['firstname'].' '.$people['lastname'];
    $data['sop_creator2_email'] = $people['email'];
}

if($_REQUEST['sop_datacontact'] != "") {
    $RecordSetPeople = \REDCap::getData(IEDEA_PEOPLE, 'array', array("record_id" => $_REQUEST['sop_datacontact']));
    $people = ProjectData::getProjectInfoArray($RecordSetPeople)[0];
    $data['sop_datacontact_name'] = $people['firstname'].' '.$people['lastname'];
    $data['sop_datacontact_email'] = $people['email'];
}

$dataformat_prefer_text = "";
if($data['dataformat_prefer'] != ""){
    $dataformat_prefer = $module->getChoiceLabels('dataformat_prefer', IEDEA_SOP);
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