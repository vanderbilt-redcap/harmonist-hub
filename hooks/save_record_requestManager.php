<?php
use ExternalModules\ExternalModules;

$records = \REDCap::getData($project_id, 'array', array('request_id' => $record));
$request = getProjectInfoArray($records)[0];

$vanderbilt_emailTrigger = ExternalModules::getModuleInstance('vanderbilt_emailTrigger');

if(($request[$instrument.'_complete'] == '2' || $vanderbilt_emailTrigger->getEmailTriggerRequested()) && $instrument == 'request'){
    $data = \REDCap::getData($project_id, 'array',$record,$instrument.'_complete', null,null,false,false,true);

    $completion_time = ($request[$instrument.'_complete'] == '2')?$data[$record][$event_id][$instrument.'_timestamp']:"";
    if(empty($completion_time)){
       $date = new DateTime();
       $completion_time = $date->format('Y-m-d H:i:s');
    }

    $arrayRM = array(array('request_id' => $record,'requestopen_ts' => $completion_time));

    $recordsPeople = \REDCap::getData(IEDEA_PEOPLE, 'array', array('record_id' => $record),null,null,null,false,false,false,"[email] = '".$request['contact_email']."'");
    $people = getProjectInfoArray($recordsPeople)[0];

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

    $recordsRegions = \REDCap::getData(IEDEA_REGIONS, 'array');
    $regions = getProjectInfoArray($recordsRegions);
    foreach ($regions as $region){
        $instance = $region['record_id'];
        //only if it's the first time we save the info
        if(empty($request["region_response_status"][$instance])) {
            $array_repeat_instances = array();
            $aux = array();
            $aux['region_response_status'] = '0';
            $aux['responding_region'] = $region['record_id'];
            $aux['dashboard_region_status_complete'] = '1';

            $array_repeat_instances[$record]['repeat_instances'][$event_id]['dashboard_region_status'][$instance] = $aux;
            $results = \REDCap::saveData($project_id, 'array', $array_repeat_instances,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false, 1, false, '');
        }else{
            break;
        }
    }
    $jsonRM = json_encode($arrayRM);
    $results = \Records::saveData($project_id, 'json', $jsonRM,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
    \Records::addRecordToRecordListCache($project_id, $event_id,1);
}else if($instrument == 'mr_assignment_survey' && $request['mr_copy_ok'][1] == "1") {
    $RecordSetSettings = \REDCap::getData(IEDEA_SETTINGS, 'array', array('record_id' => '1'));
    $settings = getProjectInfoArray($RecordSetSettings)[0];

    $RecordSetConcepts = \REDCap::getData(IEDEA_HARMONIST, 'array', null,null,null,null,false,false,false,"[concept_id] = '".$request['mr_assigned']."'");
    $concept = getProjectInfoArrayRepeatingInstruments($RecordSetConcepts)[0];
    if (empty($concept)) {
        if($request['final_d'] != ""){
            $start_year = date("Y", strtotime($request['final_d']));
        }

        $last_update = date("Y-m-d H:i");
        $concept_id = $module->framework->addAutoNumberedRecord(IEDEA_HARMONIST);
        $arrayConcepts = array(array('record_id' => $concept_id));
        $arrayConcepts[0]['lastupdate_d'] = $last_update;
        $arrayConcepts[0]['concept_id'] = $request['mr_assigned'];
        $arrayConcepts[0]['concept_title'] = $request['request_title'];
        $arrayConcepts[0]['contact_link'] = $request['contactperson_id'];
        $arrayConcepts[0]['start_year'] = $start_year;
        $arrayConcepts[0]['ec_approval_d'] = $request['final_d'];
        $arrayConcepts[0]['wg_link'] = $request['wg_name'];
        $arrayConcepts[0]['wg_link'] = $request['wg2_name'];
        $arrayConcepts[0]['concept_sheet_complete'] = '2';

        if ($request['request_type'] == "5") {
            $arrayConcepts[0]['concept_speclabel'] = '1';
        }

        #Copy Documents
        $finalConcept_PDF = "<i>None</i>";
        if ($request['finalconcept_pdf'] != "") {
            $q = $module->query("SELECT doc_name,stored_name,doc_size,file_extension,mime_type FROM redcap_edocs_metadata WHERE doc_id=?",[$request['finalconcept_pdf']]);
            $docId = "";
            while ($row = $q->fetch_assoc()) {
                $finalConcept_PDF = $row['doc_name'];
                $storedName = date("YmdsH") . "_pid" . IEDEA_HARMONIST . "_" . getRandomIdentifier(6);
                $output = file_get_contents(EDOC_PATH . $row['stored_name']);
                $filesize = file_put_contents(EDOC_PATH . $storedName, $output);
                $q = $module->query("INSERT INTO redcap_edocs_metadata (stored_name,doc_name,doc_size,file_extension,mime_type,gzipped,project_id,stored_date) VALUES (?,?,?,?,?,?,?,?)",[$storedName,$row['doc_name'],$filesize,$row['file_extension'],$row['mime_type'],'0',IEDEA_HARMONIST,date('Y-m-d h:i:s')]);
                $docId = db_insert_id();

                $arrayConcepts[0]['concept_file'] = $docId;
            }
        }
        $finalConcept_DOC = "<i>None</i>";
        if ($request['finalconcept_doc'] != "") {
            $q = $module->query("SELECT doc_name,stored_name,doc_size,file_extension,mime_type FROM redcap_edocs_metadata WHERE doc_id=?",[$request['finalconcept_doc']]);
            $docId = "";
            while ($row = $q->fetch_assoc()) {
                $finalConcept_PDF = $row['doc_name'];
                $storedName = date("YmdsH") . "_pid" . IEDEA_HARMONIST . "_" . getRandomIdentifier(6);
                $output = file_get_contents(EDOC_PATH . $row['stored_name']);
                $filesize = file_put_contents(EDOC_PATH . $storedName, $output);
                $q = $module->query("INSERT INTO redcap_edocs_metadata (stored_name,doc_name,doc_size,file_extension,mime_type,gzipped,project_id,stored_date) VALUES (?,?,?,?,?,?,?,?)",[$storedName,$row['doc_name'],$filesize,$row['file_extension'],$row['mime_type'],'0',IEDEA_HARMONIST,date('Y-m-d h:i:s')]);
                $docId = db_insert_id();

                $arrayConcepts[0]['concept_word'] = $docId;
            }
        }

        $wgroup_name = "<i>None</i>";
        if ($request['wg_name'] != "") {
            $RecordSetGroups = \REDCap::getData(IEDEA_GROUP, 'array', array('record_id' => $request['wg_name']));
            $wgroup = getProjectInfoArray($RecordSetGroups)[0];
            $wgroup_name = $wgroup['group_name'] . "(" . $wgroup['group_abbr'] . ")";
        }

        $wgroup2_name = "<i>None</i>";
        if ($request['wg2_name'] != "") {
            $RecordSetGroups = \REDCap::getData(IEDEA_GROUP, 'array', array('record_id' => $request['wg2_name']));
            $wgroup = getProjectInfoArray($RecordSetGroups)[0];
            $wgroup_name = $wgroup['group_name'] . "(" . $wgroup['group_abbr'] . ")";
        }

        $message = "<div>Dear Administrator,</div>" .
            "<div>A new concept sheet <b>" . $request['mr_assigned'] . "</b> has been created in the Hub.</div><br/>" .
            "<div><ul><li><b>Active:</b> Y</li><li><b>Last Update:</b> " . $last_update . "</li><li><b>Concept ID:</b> " . $request['mr_assigned'] . "</li><li><b>Concept Title:</b> " . $request['request_title'] . "</li>" .
            "<li><b>Contact Link:</b> " . getPeopleName($request['contactperson_id']) . "</li><li><b>Start Year:</b> " . $start_year . "</li><li><b>EC Approval Date:</b> " . $request['final_d'] . "</li>" .
            "<li><b>WG Link:</b> " . $wgroup_name . "</li><li><b>WG2 Link:</b> " . $wgroup2_name . "</li><li><b>Concept File:</b> " . $finalConcept_PDF . "</li><li><b>Concept Word:</b> " . $finalConcept_DOC . "</li></ul></div><br/>";

        $link = APP_PATH_WEBROOT_ALL . "DataEntry/record_home.php?pid=" . IEDEA_HARMONIST . "&arm=1&id=" . $concept['record_id'];
        $message .= "<div>Click <a href='" . $link . "' target='_blank'>this link</a> to see the concept sheet.</div>";
        if($settings['hub_email_new_conceptsheet'] != "") {
            $emails = explode(';', $settings['hub_email_new_conceptsheet']);
            foreach ($emails as $email) {
                sendEmail($email, $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], "New concept sheet " . $request['mr_assigned'] . " created in the Hub", $message, $concept_id);
            }
        }

        $json = json_encode($arrayConcepts);
        $results = \Records::saveData(IEDEA_HARMONIST, 'json', $json,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
    }else{
        $link = APP_PATH_WEBROOT_ALL . "DataEntry/record_home.php?pid=" . IEDEA_HARMONIST . "&arm=1&id=" . $concept['record_id'];

        $wgroup_name = "<i>None</i>";
        if ($request['wg_name'] != "") {
            $RecordSetGroups = \REDCap::getData(IEDEA_GROUP, 'array', array('record_id' => $request['wg_name']));
            $wgroup = getProjectInfoArray($RecordSetGroups)[0];
            $wgroup_name = $wgroup['group_name'] . "(" . $wgroup['group_abbr'] . ")";
        }

        $wgroup2_name = "<i>None</i>";
        if ($request['wg2_name'] != "") {
            $RecordSetGroups = \REDCap::getData(IEDEA_GROUP, 'array', array('record_id' => $request['wg2_name']));
            $wgroup = getProjectInfoArray($RecordSetGroups)[0];
            $wgroup_name = $wgroup['group_name'] . "(" . $wgroup['group_abbr'] . ")";
        }

        #Documents
        $finalConcept_PDF = "<i>None</i>";
        if ($request['finalconcept_pdf'] != "") {
            $q = $module->query("SELECT doc_name,stored_name,doc_size,file_extension,mime_type FROM redcap_edocs_metadata WHERE doc_id=?",[$request['finalconcept_pdf']]);
            $docId = "";
            while ($row = $q->fetch_assoc()) {
                $finalConcept_PDF = $row['doc_name'];
            }
        }
        $finalConcept_DOC = "<i>None</i>";
        if ($request['finalconcept_doc'] != "") {
            $q = $module->query("SELECT doc_name,stored_name,doc_size,file_extension,mime_type FROM redcap_edocs_metadata WHERE doc_id=?",[$request['finalconcept_doc']]);
            $docId = "";
            while ($row = $q->fetch_assoc()) {
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
            "<li><b>Contact Link:</b> " . getPeopleName($concept['contact_link']) . "</li><li><b>Start Year:</b> " . $start_year . "</li><li><b>EC Approval Date:</b> " . $concept['ec_approval_d'] . "</li>" .
            "<li><b>WG Link:</b> " . $wgroup_name . "</li><li><b>WG2 Link:</b> " . $wgroup2_name . "</li><li><b>Concept File:</b> " . $finalConcept_PDF . "</li><li><b>Concept Word:</b> " . $finalConcept_DOC . "</li></ul></div><br/>".
            "<div>Click <a href='" . $link . "' target='_blank'>this link</a> to see the existing concept sheet.</div><br/>" ;

        if($settings['hub_email_new_conceptsheet'] != "") {
            $emails = explode(';', $settings['hub_email_new_conceptsheet']);
            foreach ($emails as $email) {
                sendEmail($email, $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], "Failed to create concept sheet " . $concept['concept_id'] . " in the Hub", $message, $concept['record_id']);
            }
        }
    }
}

#We save the date for Recenty Finalized Requests table
if(($request['finalize_y'] != "" && ($request['request_type'] != '1' && $request['request_type'] != '5')) || ($request['finalize_y'][0] == "2" && ($request['request_type'] == '1' || $request['request_type'] == '5')) || ($request['mr_assigned'] != "" && $request['finalconcept_doc'] != "" && $request['finalconcept_pdf'] != "")) {
    $arrayRM = array(array('record_id' => $record));
    $arrayRM[0]['workflowcomplete_d'] = date("Y-m-d");
    $json = json_encode($arrayRM);
    $results = \Records::saveData(IEDEA_RMANAGER, 'json', $json,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
}
?>
