<?php
define('NOAUTH',true);
require_once dirname(dirname(__FILE__))."/projects.php";

$record = $_REQUEST['id'];

$dataformat_prefer = explode(',',$_REQUEST['dataformat_prefer']);

#checkboxes
if(!isset($_REQUEST['sop_downloaders_dummy'])){
    $downDummy = "0";
}else{
    $downDummy = "1";
}

$projectSOP = new \Plugin\Project(IEDEA_SOP);
$recordSOP = \Plugin\Record::createRecordFromId($projectSOP, $record);
$recordSOP->updateDetails(["sop_downloaders" => $_REQUEST['downloaders']], true);
$recordSOP->updateDetails(["sop_downloaders_dummy" => [$downDummy]], true);
$recordSOP->updateDetails(["sop_inclusion" => $_REQUEST['sop_inclusion']], true);
$recordSOP->updateDetails(["sop_exclusion" => $_REQUEST['sop_exclusion']], true);
$recordSOP->updateDetails(["sop_notes" => $_REQUEST['sop_notes']], true);
$recordSOP->updateDetails(["sop_due_d" => $_REQUEST['sop_due_d']], true);
$recordSOP->updateDetails(["sop_creator" => $_REQUEST['sop_creator']], true);
$recordSOP->updateDetails(["sop_creator_org" => $_REQUEST['sop_creator_org']], true);
$recordSOP->updateDetails(["sop_creator2" => $_REQUEST['sop_creator2']], true);
$recordSOP->updateDetails(["sop_creator2_org" => $_REQUEST['sop_creator2_org']], true);
$recordSOP->updateDetails(["sop_datacontact" => $_REQUEST['sop_datacontact']], true);
$recordSOP->updateDetails(["sop_datacontact_org" => $_REQUEST['sop_datacontact_org']], true);
$recordSOP->updateDetails(["dataformat_prefer" => $dataformat_prefer], true);
$recordSOP->updateDetails(["dataformat_notes" => $_REQUEST['dataformat_notes']], true);

$date = new DateTime();
$sop_updated_dt = $date->format('Y-m-d H:i:s');
$recordSOP->updateDetails(["sop_updated_dt" => $sop_updated_dt], true);
\Records::addRecordToRecordListCache($projectSOP->getProjectId(), $recordSOP->getId(),$projectSOP->getArmNum());

$RecordSetSOP = new \Plugin\RecordSet($projectSOP, array("record_id" => $record));
$data = $RecordSetSOP->getDetails()[0];
$data['sop_version_date'] = "Data Request Version: ".date('d F Y');

$date = new DateTime($_REQUEST['sop_due_d']);
$data['sop_due_d_preview'] = $date->format('d F Y');

$data['selectConcept'] = $data['sop_concept_id'];

$projectConcepts = new \Plugin\Project(IEDEA_HARMONIST);
$RecordSetConcepts = new \Plugin\RecordSet($projectConcepts, array("record_id" => $data['sop_concept_id']));
$data['sop_concept_id'] = $RecordSetConcepts->getDetails()[0]['concept_id'];
$data['sop_concept_title'] = $RecordSetConcepts->getDetails()[0]['concept_title'];

$projectPeople = new \Plugin\Project(IEDEA_PEOPLE);
if($_REQUEST['sop_creator'] != ""){
    $RecordSetPeople = new \Plugin\RecordSet($projectPeople, array("record_id" => $_REQUEST['sop_creator']));
    $data['sop_creator_name'] = $RecordSetPeople->getDetails()[0]['firstname'].' '.$RecordSetPeople->getDetails()[0]['lastname'];
    $data['sop_creator_email'] = $RecordSetPeople->getDetails()[0]['email'];
}

if($_REQUEST['sop_creator2'] != ""){
    $RecordSetPeople2 = new \Plugin\RecordSet($projectPeople, array("record_id" => $_REQUEST['sop_creator2']));
    $data['sop_creator2_name'] = $RecordSetPeople2->getDetails()[0]['firstname'].' '.$RecordSetPeople2->getDetails()[0]['lastname'];
    $data['sop_creator2_email'] = $RecordSetPeople2->getDetails()[0]['email'];
}

if($_REQUEST['sop_datacontact'] != "") {
    $RecordSetPeople3 = new \Plugin\RecordSet($projectPeople, array("record_id" => $_REQUEST['sop_datacontact']));
    $data['sop_datacontact_name'] = $RecordSetPeople3->getDetails()[0]['firstname'] . ' ' . $RecordSetPeople3->getDetails()[0]['lastname'];
    $data['sop_datacontact_email'] = $RecordSetPeople3->getDetails()[0]['email'];
}

$dataformat_prefer_text = "";
if($data['dataformat_prefer'] != ""){
    $dataformat_prefer = \Plugin\Project::convertEnumToArray($projectSOP->getMetadata('dataformat_prefer')->getElementEnum());
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