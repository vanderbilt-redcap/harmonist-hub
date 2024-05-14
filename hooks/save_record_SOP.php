<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include_once(__DIR__ ."/../projects.php");
include_once(__DIR__ ."/../functions.php");
use ExternalModules\ExternalModules;

#Get Projects ID's
$hub_mapper = $this->getProjectSetting('hub-mapper');
$pidsArray = REDCapManagement::getPIDsArray($hub_mapper);

$sop = \REDCap::getData($project_id, 'json-array', array('record_id' => $record))[0];

$data = \REDCap::getData($project_id, 'array',$record,$instrument.'_complete', null,null,false,false,true);
$completion_time = ($sop[$instrument.'_complete'] == '2')?$data[$record][$event_id][$instrument.'_timestamp']:"";
if(empty($completion_time)){
    $date = new \DateTime();
    $completion_time = $date->format('Y-m-d H:i:s');
}

$comments_DCStarted = \REDCap::getData($pidsArray['SOPCOMMENTS'], 'json-array', array('sop_id' => $record),null,null,null,false,false,false,"[other_action] = 3")[0];
$comments_DCCompleted = \REDCap::getData($pidsArray['SOPCOMMENTS'], 'json-array', array('sop_id' => $record),null,null,null,false,false,false,"[other_action] = 4")[0];

if(($instrument == 'finalization_of_data_request' && $comments_DCStarted == "" && $sop['sop_finalize_y'][1] == '1') || ($instrument == 'dhwg_review_request') || ($instrument == 'data_call_closure' && $comments_DCCompleted == "" && $sop['sop_closed_y'][1] != "" && $sop['sop_closed_y'] == "1")){
    $recordsPeople = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array('record_id' => $sop['sop_hubuser']),null,array('person_region'),null,false,false,false,null)[0]['person_region'];

    $arrayComments = array(array('record_id' => $this->framework->addAutoNumberedRecord($pidsArray['SOPCOMMENTS']),'responsecomplete_ts' => $completion_time, 'sop_id' => $sop['record_id'], 'response_region' => $person_region, 'response_person' => $sop['sop_hubuser']));

    $regions = \REDCap::getData($pidsArray['REGIONS'], 'json-array', array('record_id' => $person_region),array('region_code'),null,null,false,false,false,null)[0];
    if(!empty($regions)) {
        $arrayComments[0]['response_regioncode'] = $regions['region_code'];
    }

    if($instrument == 'finalization_of_data_request'){

        $arraySOP = array(array('record_id' => $record, 'sop_status' => "1"));//FINAL

        $arrayComments[0]['other_action'] = "3";
        $arrayComments[0]['comments'] = "Data Call started";
        $arrayComments[0]['comment_ver'] = "1";
        $arrayComments[0]['response_person'] = $sop['sop_finalize_person'];

        $q = $this->query("SELECT doc_name,stored_name,doc_size,file_extension,mime_type FROM redcap_edocs_metadata WHERE doc_id=?",[$sop['sop_finalpdf']]);
        $docId = "";
        while ($row = $q->fetch_assoc()) {
            $storedName = date("YmdsH")."_pid".$pidsArray['HARMONIST']."_".\Vanderbilt\HarmonistHubExternalModule\getRandomIdentifier(6);
            $output = file_get_contents($this->getSafePath(EDOC_PATH.$row['stored_name'],EDOC_PATH));
            $filesize = file_put_contents($this->getSafePath(EDOC_PATH.$storedName,EDOC_PATH), $output);
            $q = $this->query("INSERT INTO redcap_edocs_metadata (stored_name,doc_name,doc_size,file_extension,mime_type,gzipped,project_id,stored_date) VALUES (?,?,?,?,?,?,?,?)",[$storedName,$row['doc_name'],$filesize,$row['file_extension'],$row['mime_type'],'0',$pidsArray['HARMONIST'],date('Y-m-d h:i:s')]);
            $docId = db_insert_id();

            $jsonConcepts = json_encode(array(array('record_id' => $sop['sop_concept_id'], 'datasop_file' => $docId)));
            $results = \Records::saveData($project_id, 'json', $jsonConcepts,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
        }

        $jsonSOP = json_encode($arraySOP);
        $results = \Records::saveData($project_id, 'json', $jsonSOP,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);

    }else if($instrument == 'data_call_closure'){
        $arrayComments[0]['other_action'] = "4";
        $arrayComments[0]['comments'] = "Data Call completed";
        $arrayComments[0]['comment_ver'] = "1";
    }else if($instrument == 'dhwg_review_request') {
        $jsonSOP = json_encode(array(array('record_id' => $record, 'sop_visibility' => "2")));//PUBLIC
        $results = \Records::saveData($project_id, 'json', $jsonSOP,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);

        $arrayComments[0]['other_action'] = "1";
        $arrayComments[0]['comments'] = "Data Call made PUBLIC";
        $arrayComments[0]['comment_ver'] = "0";
    }

    $json = json_encode($arrayComments);
    $results = \Records::saveData($pidsArray['SOPCOMMENTS'], 'json', $json,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
    $recordcomment = array_pop(array_reverse($results['ids']));

    \Records::addRecordToRecordListCache($pidsArray['SOPCOMMENTS'], $recordcomment,1);
    \Records::addRecordToRecordListCache($project_id, $record,1);
}
?>