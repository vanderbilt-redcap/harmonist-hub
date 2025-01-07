<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include_once(__DIR__ ."/../projects.php");
include_once(__DIR__ ."/../functions.php");
use ExternalModules\ExternalModules;

#Get Projects ID's
$hub_mapper = $this->getProjectSetting('hub-mapper');
$pidsArray = REDCapManagement::getPIDsArray($hub_mapper);

$requestData = \REDCap::getData($project_id, 'array', array('request_id' => $record),null,null,false,false,false,true);
$request = $requestData[$record][$event_id];

$vanderbilt_emailTrigger = ExternalModules::getModuleInstance('vanderbilt_emailTrigger');
if(($request[$instrument.'_complete'] == '2' || $vanderbilt_emailTrigger->getEmailTriggerRequested()) && $instrument == 'request'){
    $data = \REDCap::getData($project_id, 'json-array',$record,array($instrument.'_complete',$instrument.'_timestamp'), null,false,false,false,true)[0];

    $completion_time = $data[$instrument.'_timestamp'];
    if(empty($completion_time)){
       $date = new \DateTime();
       $completion_time = $date->format('Y-m-d H:i:s');
    }

    $arrayRM = array(array('request_id' => $record,'requestopen_ts' => $completion_time));

    $people = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', null,array('record_id','firstname','lastname','active_y'),null,null,false,false,false,"lower([email]) = '".strtolower($request['contact_email'])."'")[0];
    if(!empty($people)){
        $arrayRM[0]['contactperson_id'] = $people['record_id'];
        $arrayRM[0]['request_contact_display'] = $people['firstname']." ".$people['lastname'];

        #Update Follow activity
        if($request['contactnotification_y'][1] == "1" && $people['active_y'] == "1"){
            $array_userid = explode(',',$request['follow_activity']);
            if($request['follow_activity'] == ''){
                $arrayRM[0]['follow_activity'] = $people['record_id'];
            }else if (!in_array($people['record_id'],$array_userid)) {
                array_push($array_userid,$people['record_id']);
                $string_userid = implode(",",$array_userid);
                $arrayRM[0]['follow_activity'] = $string_userid;
            }
        }
    }

    $regions = \REDCap::getData($pidsArray['REGIONS'], 'json-array');
    foreach ($regions as $region){
        $instance = $region['record_id'];
        //only if it's the first time we save the info
        if(empty($requestData[$record]['repeat_instances']['dashboard_voting_status'][$instance])) {
            $array_repeat_instances = array();
            $aux = array();
            $aux['region_response_status'] = '0';
            $aux['responding_region'] = $region['record_id'];
            $aux['dashboard_voting_status_complete'] = '1';

            $array_repeat_instances[$record]['repeat_instances'][$event_id]['dashboard_voting_status'][$instance] = $aux;
            $results = \REDCap::saveData($project_id, 'array', $array_repeat_instances,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false, 1, false, '');
            \REDCap::logEvent("Create Research Group Instance\nRequest Manager", $region['region_name']." (".$region['region_code'].")", null, $record, $event_id, $project_id);
        }else{
            break;
        }
    }
    $jsonRM = json_encode($arrayRM);
    $results = \Records::saveData($project_id, 'json', $jsonRM,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
}else if($instrument == 'tracking_number_assignment_survey' && $request['mr_copy_ok'][1] == "1") {
    $settings = \REDCap::getData($pidsArray['SETTINGS'], 'json-array', array('record_id' => '1'))[0];
    $RecordSetConcepts = \REDCap::getData($pidsArray['HARMONIST'], 'array', null,null,null,null,false,false,false,"[concept_id] = '".$request['mr_assigned']."'");
    $concept = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConcepts,$pidsArray['HARMONIST'])[0];
    if (empty($concept)) {
        if($request['final_d'] != ""){
            $start_year = date("Y", strtotime($request['final_d']));
        }
        $last_update = date("Y-m-d H:i");
        $concept_id = $this->framework->addAutoNumberedRecord($pidsArray['HARMONIST']);
        $arrayConcepts = array(array('record_id' => $concept_id));
        $arrayConcepts[0]['lastupdate_d'] = $last_update;
        $arrayConcepts[0]['active_y'] = "Y";
        $arrayConcepts[0]['concept_id'] = $request['mr_assigned'];
        $arrayConcepts[0]['concept_title'] = $request['request_title'];
        $arrayConcepts[0]['contact_link'] = $request['contactperson_id'];
        $arrayConcepts[0]['start_year'] = $start_year;
        $arrayConcepts[0]['ec_approval_d'] = $request['final_d'];
        $arrayConcepts[0]['wg_link'] = $request['wg_name'];
        $arrayConcepts[0]['wg2_link'] = $request['wg2_name'];
        $arrayConcepts[0]['wg3_link'] = $request['wg3_name'];
        $arrayConcepts[0]['wg4_link'] = $request['wg4_name'];
        $arrayConcepts[0]['concept_sheet_complete'] = '2';

        if ($request['request_type'] == "5") {
            $arrayConcepts[0]['concept_speclabel'] = '1';
        }

        #Copy Documents
        $finalConcept_PDF = "<i>None</i>";
        if ($request['finalconcept_pdf'] != "") {
            $q = $this->query("SELECT doc_name,stored_name,doc_size,file_extension,mime_type FROM redcap_edocs_metadata WHERE doc_id=?",[$request['finalconcept_pdf']]);
            $docId = "";
            while ($row = db_fetch_assoc($q)) {
                $finalConcept_PDF = $row['doc_name'];
                $storedName = date("YmdsH") . "_pid" . $pidsArray['HARMONIST'] . "_" . getRandomIdentifier(6);
                $output = file_get_contents($this->getSafePath(EDOC_PATH.$row['stored_name'],EDOC_PATH));
                $filesize = file_put_contents(EDOC_PATH . $storedName, $output);
                $q = $this->query("INSERT INTO redcap_edocs_metadata (stored_name,doc_name,doc_size,file_extension,mime_type,gzipped,project_id,stored_date) VALUES (?,?,?,?,?,?,?,?)",[$storedName,$row['doc_name'],$filesize,$row['file_extension'],$row['mime_type'],'0',$pidsArray['HARMONIST'],date('Y-m-d h:i:s')]);
                $docId = db_insert_id();

                $arrayConcepts[0]['concept_file'] = $docId;
            }
        }
        $finalConcept_DOC = "<i>None</i>";
        if ($request['finalconcept_doc'] != "") {
            $q = $this->query("SELECT doc_name,stored_name,doc_size,file_extension,mime_type FROM redcap_edocs_metadata WHERE doc_id=?",[$request['finalconcept_doc']]);
            $docId = "";
            while ($row = db_fetch_assoc($q)) {
                $finalConcept_DOC = $row['doc_name'];
                $storedName = date("YmdsH") . "_pid" . $pidsArray['HARMONIST'] . "_" . getRandomIdentifier(6);
                $output = file_get_contents($this->getSafePath(EDOC_PATH.$row['stored_name'],EDOC_PATH));
                $filesize = file_put_contents(EDOC_PATH . $storedName, $output);
                $q = $this->query("INSERT INTO redcap_edocs_metadata (stored_name,doc_name,doc_size,file_extension,mime_type,gzipped,project_id,stored_date) VALUES (?,?,?,?,?,?,?,?)",[$storedName,$row['doc_name'],$filesize,$row['file_extension'],$row['mime_type'],'0',$pidsArray['HARMONIST'],date('Y-m-d h:i:s')]);
                $docId = db_insert_id();

                $arrayConcepts[0]['concept_word'] = $docId;
            }
        }

        $wgroup_name = "<i>None</i>";
        if ($request['wg_name'] != "") {
            $wgroup = \REDCap::getData($pidsArray['GROUP'], 'json-array', array('record_id' => $request['wg_name']),array('group_name','group_abbr'))[0];
            $wgroup_name = $wgroup['group_name'] . "(" . $wgroup['group_abbr'] . ")";
        }

        $wgroup2_name = "<i>None</i>";
        if ($request['wg2_name'] != "") {
            $wgroup = \REDCap::getData($pidsArray['GROUP'], 'json-array', array('record_id' => $request['wg2_name']),array('group_name','group_abbr'))[0];
            $wgroup2_name = $wgroup['group_name'] . "(" . $wgroup['group_abbr'] . ")";
        }

        $message = "<div>Dear Administrator,</div>" .
            "<div>A new concept sheet <b>" . $request['mr_assigned'] . "</b> has been created in the Hub.</div><br/>" .
            "<div><ul><li><b>Active:</b> Y</li><li><b>Last Update:</b> " . $last_update . "</li><li><b>Concept ID:</b> " . $request['mr_assigned'] . "</li><li><b>Concept Title:</b> " . $request['request_title'] . "</li>" .
            "<li><b>Contact Link:</b> " . getPeopleName($pidsArray['PEOPLE'], $request['contactperson_id']) . "</li><li><b>Start Year:</b> " . $start_year . "</li><li><b>EC Approval Date:</b> " . $request['final_d'] . "</li>" .
            "<li><b>WG Link:</b> " . $wgroup_name . "</li><li><b>WG2 Link:</b> " . $wgroup2_name . "</li><li><b>Concept File:</b> " . $finalConcept_PDF . "</li><li><b>Concept Word:</b> " . $finalConcept_DOC . "</li></ul></div><br/>";

        $link = APP_PATH_WEBROOT_ALL . "DataEntry/record_home.php?pid=" . $pidsArray['HARMONIST'] . "&arm=1&id=" . $concept_id;
        $message .= "<div>Click <a href='" . $link . "' target='_blank'>this link</a> to see the concept sheet.</div>";
        if($settings['hub_email_new_conceptsheet'] != "") {
            $emails = explode(';', $settings['hub_email_new_conceptsheet']);
            foreach ($emails as $email) {
                sendEmail($email, $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], "New concept sheet " . $request['mr_assigned'] . " created in the Hub", $message, $concept_id,"New concept sheet created",$pidsArray['HARMONIST']);
            }
        }

        $json = json_encode($arrayConcepts);
        $results = \Records::saveData($pidsArray['HARMONIST'], 'json', $json,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
    }else{
        $link = APP_PATH_WEBROOT_ALL . "DataEntry/record_home.php?pid=" . $pidsArray['HARMONIST'] . "&arm=1&id=" . $concept['record_id'];

        $wgroup_name = "<i>None</i>";
        if ($request['wg_name'] != "") {
            $wgroup = \REDCap::getData($pidsArray['GROUP'], 'json-array', array('record_id' => $request['wg_name']),array('group_name','group_abbr'))[0];
            $wgroup_name = $wgroup['group_name'] . "(" . $wgroup['group_abbr'] . ")";
        }

        $wgroup2_name = "<i>None</i>";
        if ($request['wg2_name'] != "") {
            $wgroup = \REDCap::getData($pidsArray['GROUP'], 'json-array', array('record_id' => $request['wg2_name']),array('group_name','group_abbr'))[0];
            $wgroup2_name = $wgroup['group_name'] . "(" . $wgroup['group_abbr'] . ")";
        }

        #Documents
        $finalConcept_PDF = "<i>None</i>";
        if ($request['finalconcept_pdf'] != "") {
            $q = $this->query("SELECT doc_name,stored_name,doc_size,file_extension,mime_type FROM redcap_edocs_metadata WHERE doc_id=?",[$request['finalconcept_pdf']]);
            $docId = "";
            while ($row = db_fetch_assoc($q)) {
                $finalConcept_PDF = $row['doc_name'];
            }
        }
        $finalConcept_DOC = "<i>None</i>";
        if ($request['finalconcept_doc'] != "") {
            $q = $this->query("SELECT doc_name,stored_name,doc_size,file_extension,mime_type FROM redcap_edocs_metadata WHERE doc_id=?",[$request['finalconcept_doc']]);
            $docId = "";
            while ($row = db_fetch_assoc($q)) {
                $finalConcept_DOC = $row['doc_name'];
            }
        }

        if($concept['ec_approval_d'] != ""){
            $start_year = date("Y", strtotime($concept['ec_approval_d']));
        }
        $message = "<div>Dear Administrator,</div>" .
            "<div>The request to create the concept sheet <b>".$concept['concept_id']."</b> in the Hub <span style='color: red'>has failed</span>.</div><br/>" .
            "<div><span style='color: red'>This concept already exists.</span></div><br/>" .
            "<div>Existing concept sheet data:</div><br/>" .
            "<div><ul><li><b>Active:</b> ".$concept['active_y']."</li><li><b>Last Update:</b> " . $concept['lastupdate_d'] . "</li><li><b>Concept ID:</b> " . $concept['concept_id'] . "</li><li><b>Concept Title:</b> " . $concept['concept_title'] . "</li>" .
            "<li><b>Contact Link:</b> " . getPeopleName($pidsArray['PEOPLE'], $concept['contact_link']) . "</li><li><b>Start Year:</b> " . $start_year . "</li><li><b>EC Approval Date:</b> " . $concept['ec_approval_d'] . "</li>" .
            "<li><b>WG Link:</b> " . $wgroup_name . "</li><li><b>WG2 Link:</b> " . $wgroup2_name . "</li><li><b>Concept File:</b> " . $finalConcept_PDF . "</li><li><b>Concept Word:</b> " . $finalConcept_DOC . "</li></ul></div><br/>".
            "<div>Click <a href='" . $link . "' target='_blank'>this link</a> to see the existing concept sheet.</div><br/>" ;

        if($settings['hub_email_new_conceptsheet'] != "") {
            $emails = explode(';', $settings['hub_email_new_conceptsheet']);
            foreach ($emails as $email) {
                sendEmail($email, $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], "Failed to create concept sheet " . $concept['concept_id'] . " in the Hub", $message, $concept['record_id'],"New concept sheet FAILED",$pidsArray['HARMONIST']);
            }
        }
    }
}else if($instrument == 'admin_review' && $request['mr_temporary'] != "") {
    $arrayRM = array(array('request_id' => $record));
    $arrayRM[0]['mr_assigned'] = $request['mr_temporary'];
    $json = json_encode($arrayRM);
    $results = \Records::saveData($pidsArray['RMANAGER'], 'json', $json,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
}

#We save the date for Recently Finalized Requests table
if(($request['finalize_y'] != "" && ($request['request_type'] != '1' && $request['request_type'] != '5')) || ($request['finalize_y'] == "2" && ($request['request_type'] == '1' || $request['request_type'] == '5')) || ($request['mr_assigned'] != "" && $request['finalconcept_doc'] != "" && $request['finalconcept_pdf'] != "")) {
    $arrayRM = array(array('request_id' => $record));
    $arrayRM[0]['workflowcomplete_d'] = date("Y-m-d");
    $json = json_encode($arrayRM);
    $results = \Records::saveData($pidsArray['RMANAGER'], 'json', $json,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
}
?>
