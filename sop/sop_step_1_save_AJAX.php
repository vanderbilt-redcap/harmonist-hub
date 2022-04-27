<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once dirname(dirname(__FILE__))."/projects.php";

$selectConcept = htmlentities($_REQUEST['selectConcept'],ENT_QUOTES);
$option = htmlentities($_REQUEST['option'],ENT_QUOTES);
$record = htmlentities($_REQUEST['id'],ENT_QUOTES);
$save_option = htmlentities(['save_option'],ENT_QUOTES);
$sop_hubuser = htmlentities($_REQUEST['sop_hubuser'],ENT_QUOTES);

$RecordSetConcepts = \REDCap::getData($pidsArray['HARMONIST'], 'array', array("record_id" => $selectConcept));
$concepts = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConcepts)[0];
$contact_link = $concepts['contact_link'];
$concept_id = $concepts['concept_id'];
$concept_title = $concepts['concept_title'];

$date = new \DateTime();
$sop_created_dt = $date->format('Y-m-d H:i:s');
$data_select = "";
if($option == "1" && $save_option == ""){
    #NEW SOP
    $Proj = new \Project($pidsArray['SOP']);
    $event_id = $Proj->firstEventId;
    $record = $module->framework->addAutoNumberedRecord($pidsArray['SOP']);
    $save_option = $record;

    $arraySOP = array();
    $arraySOP[$record][$event_id]['sop_status'] = "0";//DRAFT
    $arraySOP[$record][$event_id]['sop_hubuser'] = "0";
    $arraySOP[$record][$event_id]['sop_concept_id'] = $sop_hubuser;
    $arraySOP[$record][$event_id]['sop_visibility'] = $selectConcept;
    $arraySOP[$record][$event_id]['sop_active'] = "1";
    $arraySOP[$record][$event_id]['sop_status'] = "1";

    $sop_name = $recordSOP.". Data Request for ".$concept_id.", ".$concept_title;
    $arraySOP[$record][$event_id]['sop_name'] = $sop_name;
    $arraySOP[$record][$event_id]['sop_created_dt'] = $sop_created_dt;
    $arraySOP[$record][$event_id]['sop_updated_dt'] = $sop_created_dt;

    $RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', null,null,null,null,false,false,false,"[sop_active] = 1 AND [sop_status] = 0 AND [sop_hubuser] = '".$sop_hubuser."'");
    $sop_drafts = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP);
    if (!empty($sop_drafts)) {
        $data_select = '<select class="form-control" name="selectSOP_3" id="selectSOP_3" onchange="checkStep(1);checkConcept();">
            <option value="">Select draft</option>';

        foreach ($sop_drafts as $draft){
            if($draft['sop_active'] == '1') {
                $RecordSetConcepts = \REDCap::getData($pidsArray['HARMONIST'], 'array', array("record_id" => $draft['sop_concept_id']));
                $concept_id = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConcepts)[0]['concept_id'];
                if($draft['record_id'] == $record){
                    $data_select .=  "<option value='" . $draft['record_id'] . "' concept='" . $draft['sop_concept_id'] . "' concept_id='" . $concept_id . "' selected>" . $draft['sop_name'] . "</option>";
                }else{
                    $data_select .=  "<option value='" . $draft['record_id'] . "' concept='" . $draft['sop_concept_id'] . "' concept_id='" . $concept_id . "'>" . $draft['sop_name'] . "</option>";
                }

            }
        }
        $data_select .=  "</select>";
    }

}else if($option == "2"){
    #LOAD TEMPLATE
    $RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', array("record_id" => $record));
    $sop = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP)[0];

    $Proj = new \Project($pidsArray['SOP']);
    $event_id = $Proj->firstEventId;
    $record = $module->framework->addAutoNumberedRecord($pidsArray['SOP']);
    $save_option = $record;

    $arraySOP = array();
    $arraySOP[$record][$event_id]['sop_status'] = "0";//DRAFT
    $arraySOP[$record][$event_id]['sop_hubuser'] = $sop['sop_hubuser'];
    $arraySOP[$record][$event_id]['sop_concept_id'] = $sop['sop_concept_id'];
    $arraySOP[$record][$event_id]['sop_tablefields'] = $sop['sop_tablefields'];
    $arraySOP[$record][$event_id]['sop_downloaders'] = $sop['downloaders'];
    $arraySOP[$record][$event_id]['sop_inclusion'] = $sop['sop_inclusion'];
    $arraySOP[$record][$event_id]['sop_exclusion'] = $sop['sop_exclusion'];
    $arraySOP[$record][$event_id]['sop_notes'] = $sop['sop_notes'];
    $arraySOP[$record][$event_id]['sop_creator'] = $sop['sop_creator'];
    $arraySOP[$record][$event_id]['sop_creator2'] = $sop['sop_creator2'];
    $arraySOP[$record][$event_id]['sop_datacontact'] = $sop['sop_datacontact'];
    $arraySOP[$record][$event_id]['sop_extrapdf'] = $sop['sop_extrapdf'];
    $arraySOP[$record][$event_id]['sop_finalpdf'] = $sop['sop_finalpdf'];
    $arraySOP[$record][$event_id]['sop_visibility'] = "1";
    $arraySOP[$record][$event_id]['sop_active'] = "1";
    $arraySOP[$record][$event_id]['dataformat_prefer'] = $sop['dataformat_prefer'];
    $arraySOP[$record][$event_id]['dataformat_notes'] = $sop['dataformat_notes'];

    $sop_name = $record.". Data Request for ".$concept_id.", ".$concept_title;
    $arraySOP[$record][$event_id]['sop_name'] = $sop_name;
    $arraySOP[$record][$event_id]['sop_created_dt'] = $sop_created_dt;
    $arraySOP[$record][$event_id]['sop_updated_dt'] = $sop_created_dt;

}else{
    #LOAD DRAFT
    if($option == "1"){
        $record = $save_option;
    }else{
        $save_option = $record;
    }
    $RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', array("record_id" => $record));
    $sop = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP)[0];

    $RecordSetConcepts = \REDCap::getData($pidsArray['HARMONIST'], 'array', array("record_id" => $sop['sop_concept_id']));
    $concept_id = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConcepts)[0]['concept_id'];
    if($selectConcept != ""){
        //UPDATE SOP
        $Proj = new \Project($pidsArray['SOP']);
        $event_id = $Proj->firstEventId;

        $arraySOP = array();
        $arraySOP[$record][$event_id]['sop_concept_id'] = $selectConcept;
        $sop_name = $record.". Data Request for ".$concept_id.", ".$concept_title;
        $arraySOP[$record][$event_id]['sop_name'] = $sop_name;
        $arraySOP[$record][$event_id]['sop_updated_dt'] = $sop_created_dt;
        $arraySOP[$record][$event_id]['sop_due_d'] = $sop['sop_due_d'];;
    }

}
$results = \Records::saveData($pidsArray['SOP'], 'array', $arraySOP,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
\Records::addRecordToRecordListCache($pidsArray['SOP'], $record,1);

$RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', array("record_id" => $record));
$data = ProjectData::getProjectInfoArray($RecordSetSOP)[0];

$data['select'] = $data_select;
$data['save_option'] = $save_option;

$date = new \DateTime($sop['sop_due_d']);
$data['sop_due_d_preview'] = $date->format('d F Y');

$data['concept_id'] = $concept_id;

#Load information for STEP4
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


$sop_concept_id = $data['sop_concept_id'];

$RecordSetConcepts = \REDCap::getData($pidsArray['HARMONIST'], 'array', array("record_id" => $data['sop_concept_id']));
$concept = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConcepts)[0];
$data['sop_concept_id'] = $concept['concept_id'];
$data['sop_concept_title'] = $concept['concept_title'];

if($data['sop_creator'] != ""){
    $RecordSetPeople = \REDCap::getData($pidsArray['PEOPLE'], 'array', array("record_id" => $data['sop_creator']));
    $people = ProjectData::getProjectInfoArray($RecordSetPeople)[0];
    $data['sop_creator_name'] = $people['firstname'].' '.$people['lastname'];
    $data['sop_creator_email'] = $people['email'];
}

if($data['sop_creator2'] != ""){
    $RecordSetPeople = \REDCap::getData($pidsArray['PEOPLE'], 'array', array("record_id" => $data['sop_creator2']));
    $people = ProjectData::getProjectInfoArray($RecordSetPeople)[0];
    $data['sop_creator2_name'] = $people['firstname'].' '.$people['lastname'];
    $data['sop_creator2_email'] = $people['email'];
}

if($data['sop_datacontact'] != "") {
    $RecordSetPeople = \REDCap::getData($pidsArray['PEOPLE'], 'array', array("record_id" => $data['sop_datacontact']));
    $people = ProjectData::getProjectInfoArray($RecordSetPeople)[0];
    $data['sop_datacontact_name'] = $people['firstname'].' '.$people['lastname'];
    $data['sop_datacontact_email'] = $people['email'];
}

if($data['sop_hubuser'] != "") {
    $RecordSetPeople = \REDCap::getData($pidsArray['PEOPLE'], 'array', array("record_id" => $data['sop_hubuser']));
    $sop_hubuser_region = ProjectData::getProjectInfoArray($RecordSetPeople)[0]['person_region'];
    $data['sopCreator_region'] = $sop_hubuser_region;
}

//Load From discuss data
if($selectConcept == "" && $option == "1" && $save_option != "") {
    $data['optradio'] = '3';
    $data['sop_discuss'] = $data['record_id'];
    $data['selectConcept'] = $sop_concept_id;
}
echo json_encode($data);
?>