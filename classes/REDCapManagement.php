<?php
namespace Vanderbilt\HarmonistHubExternalModule;


class REDCapManagement {
    const DEFAULT_EMAIL_ADDRESS = "harmonist@vumc.org";

    public static function getProjectsConstantsArray(){
        $projects_array = array(28=>'SETTINGS', 0=>'DATAMODEL',1=>'CODELIST',29=>'DATAMODELMETADATA',2=>'HARMONIST',3=>'RMANAGER',4=>'COMMENTSVOTES',5=>'SOP',6=>'SOPCOMMENTS',
            7=>'REGIONS',8=>'PEOPLE',9=>'GROUP', 10=>'FAQ',11=>'HOME',12=>'DATAUPLOAD',13=>'DATADOWNLOAD',
            14=>'JSONCOPY',15=>'METRICS',16=>'DATAAVAILABILITY',17=>'PROJECTSSTUDIES',18=>'DATATOOLMETRICS',19=>'DATATOOLUPLOADSECURITY',
            20=>'FAQDATASUBMISSION',21=>'CHANGELOG',22=>'FILELIBRARY',23=>'FILELIBRARYDOWN',24=>'NEWITEMS',25=>'ABOUT',26=>'EXTRAOUTPUTS',
            27=>'TBLCENTERREVISED', 41=>'DATADOWNLOADUSERS');

        return $projects_array;
    }

    public static function getProjectsTitlesArray(){
        $projects_array_title= array(0=>'Data Model (0A)',1=>'Code Lists (0B)',2=>'Concept Sheets (1)',3=>'Request Manager (2)',
            4=>'Comments and Votes (2B)',5=>'Data Requests (3)',6=>'Data Request Comments (3B)', 7=>'Research Groups (4)',8=>'People (5)',
            9=>'Working Groups (6)', 10=>'Hub FAQ (7)',11=>'Homepage Content (8)',12=>'Data Upload Log (9)',13=>'Data Download Log (10)',
            14=>'Data Model JSON (11)',15=>'Metrics (12)',16=>'Data Availability (13)',17=>'Projects and Studies (14)',
            18=>'Toolkit Usage Metrics (15)',19=>'External Tool Security (16)',20=>'Toolkit FAQ (17)', 21=>'Changelog (18)',
            22=>'File Library (19)',23=>'File Library Log (20)',24=>'News Items (21)',25=>'About (22)',26=>'Extra Outputs (23)',
            27=>'Consortium Site List (24)',28=>'Settings (99)',29=>'Toolkit Metadata (0C)' ,41=>'Data Download Users');

        return $projects_array_title;
    }

    public static function getSurveyConstantsArray(){
        $projects_array = array(
            31=>'CONCEPTLINK',
            32=>'REQUESTLINK',
            33=>'SURVEYLINK',
            34=>'SURVEYLINKSOP',
            35=>'SURVEYPERSONINFO',
            37=>'SURVEYFILELIBRARY',
            38=>'SURVEYNEWS',
            39=>'SURVEYTBLCENTERREVISED',
            40=>'DATARELEASEREQUEST');

        return $projects_array;
    }

    public static function getCronNeededConstants(){
        return ['METRICS', 'SETTINGS', 'PEOPLE', 'HARMONIST', 'COMMENTSVOTES', 'RMANAGER', 'DATAUPLOAD', 'DATADOWNLOAD', 'EXTRAOUTPUTS', 'REGIONS', 'SOP', 'DATAMODEL'];
    }

    public static function getExtraConstantsArray(){
        return ['DES'];
    }

    public static function getProjectConstantsArrayWithoutDeactivatedProjects(){
        $projects_array = self::getProjectsConstantsArray();
        $settings = \REDCap::getData($pidsArray['SETTINGS'], 'json-array', null)[0];

        $deactivatedConstants = array();
        if($settings['deactivate_toolkit___1'] == '1'){
            array_push($deactivatedConstants,'DATATOOLUPLOADSECURITY');
            array_push($deactivatedConstants,'DATATOOLMETRICS');
        }
        if($settings['deactivate_datahub___1'] == '1'){
            array_push($deactivatedConstants,'DATAUPLOAD');
            array_push($deactivatedConstants,'DATADOWNLOAD');
            array_push($deactivatedConstants,'SOP');
            array_push($deactivatedConstants,'SOPCOMMENTS');
        }
        if($settings['deactivate_tblcenter___1'] == '1'){
            array_push($deactivatedConstants,'TBLCENTERREVISED');
        }

        foreach ($deactivatedConstants as $deactivated){
            foreach ($projects_array as $index => $constant){
                if($constant == $deactivated){
                    unset($projects_array[$index]);
                }
            }
        }
        return $projects_array;
    }

    public static function getPIDsArray($project_id, $option = ""){
        $projects_array = array_merge(self::getProjectsConstantsArray(),self::getSurveyConstantsArray());
        $pidsArray = array();
        foreach ($projects_array as $constant){
            $pid = \REDCap::getData($project_id, 'json-array', null,array('project_id'),null,null,false,false,false,"[project_constant]='".$constant."'")[0]['project_id'];
            if($pid !== ""){
                $pidsArray[$constant] = $pid;
            }
        }
        $pidsArray['PROJECTS'] = $project_id;

        if($option == "cron"){
            foreach (self::getCronNeededConstants() as $constant_id){
                if(!array_key_exists($constant_id,$pidsArray) || (array_key_exists($constant_id,$pidsArray) && empty($pidsArray[$constant_id]))){
                    return null;
                }
            }
        }
        return $pidsArray;
    }

    public static function getProjectsRepeatableArray(){
        $projects_array_repeatable = array(
            0=>array(0=>array('status'=>1,'instrument'=>'variable_metadata','params'=>'[variable_name]')),
            1=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
            2=>array(
                0=>array('status'=>1,'instrument'=>'participants','params'=>'[person_role], [person_link]'),
                1=>array('status'=>1,'instrument'=>'admin_update','params'=>'[adminupdate_d]'),
                2=>array('status'=>1,'instrument'=>'project_update_survey','params'=>'[update_d]'),
                3=>array('status'=>1,'instrument'=>'outputs','params'=>'[output_year], [output_type], [output_venue]'),
                4=>array('status'=>1,'instrument'=>'linked_documents','params'=>'')
            ),
            3=>array(0=>array('status'=>1,'instrument'=>'dashboard_voting_status','params'=>'[responding_region]')),
            4=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
            5=>array(0=>array('status'=>1,'instrument'=>'region_participation_status','params'=>'[data_region], [data_response_status]')),
            6=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
            7=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
            8=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
            9=>array(0=>array('status'=>1,'instrument'=>'meetings','params'=>'[meeting_d]')),
            10=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
            11=>array(0=>array('status'=>1,'instrument'=>'quick_links_section','params'=>'[links_sectionhead]')),
            12=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
            13=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
            14=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
            15=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
            16=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
            17=>array(
                0=>array('status'=>1,'instrument'=>'participating_sites','params'=>'[site_name]([site_location])'),
                1=>array('status'=>1,'instrument'=>'study_documents','params'=>'[studyfile_desc],[studyfile_date'),
                2=>array('status'=>1,'instrument'=>'restricted_files_datasets','params'=>'[datafile_name],[datafile_date]')
            ),
            18=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
            19=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
            20=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
            21=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
            22=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
            23=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
            24=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
            25=>array(0=>array('status'=>1,'instrument'=>'about_members','params'=>'')),
            26=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
            27=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
            28=>array(0=>array('status'=>0,'instrument'=>'','params'=>''))
        );

        return $projects_array_repeatable;
    }

    public static function getProjectsSurveysArray(){
        $projects_array_surveys = array(
            2=>array(
                0=>'concept_sheet',
                1=>'participants',
                2=>'admin_update',
                3=>'quarterly_update_survey',
                4=>'outputs',
                5=>'linked_documents'
            ),
            3=>array(
                0=>'request',
                1=>'admin_review',
                2=>'finalization_of_request',
                3=>'final_docs_request_survey',
                4=>'tracking_number_assignment_survey'
            ),
            4=>array(
                0=>'comments_and_votes'
            ),
            5=>array(
                0=>'data_specification',
                1=>'dhwg_review_request',
                2=>'finalization_of_data_request',
                3=>'data_call_closure'
            ),
            6=>array(
                0=>'sop_comments'
            ),
            8=>array(
                0=>'person_information',
                1=>'user_profile'
            ),
            11=>array(
                0=>'deadlines',
                1=>'announcements'
            ),
            22=>array(
                0=>'file_information'
            ),
            24=>array(
                0=>'news_item'
            ),
            26=>array(
                0=>'output_record'
            ),
            27=>array(
                0=>'tblcenter'
            )
        );

        return $projects_array_surveys;
    }

	public static function getProjectsShowArray() {
        $projects_array_show = array(0=>'1',1=>'1',2=>'1',3=>'1',4=>'1',5=>'0',6=>'1',
            7=>'1',8=>'1',9=>'1', 10=>'1',11=>'1',12=>'1',13=>'1',
            14=>'0',15=>'0',16=>'0',17=>'1',18=>'0',19=>'0',
            20=>'0',21=>'1',22=>'0',23=>'0',24=>'0',25=>'0',26=>'0',27=>'0',28=>'1');
        return $projects_array_show;
    }

    public static function getCustomRecordLabelArray() {
        $custom_record_label_array = array(0=>"[table_name]",1=>"[list_name]",2=>'<span style=\'color:#[dashboard_color]\'><b>[concept_id]</b> [contact_link]</span>',
            3=>'[contact_name], [request_type] (Due: [due_d])',4=>"[request_id], [response_person]",5=>'[sop_hubuser]',6=>'',
            7=>'([region_name], [region_code])',8=>'[firstname] [lastname]',9=>'[group_abbr], [group_name]', 10=>'[help_question]',
            11=>'',12=>'',13=>'[download_id], [downloader_id]', 14=>'[type]',15=>'',16=>'[available_variable], [available_status]',17=>'',
            18=>'[action_ts], [action_step]',19=>'', 20=>'',21=>'',22=>'',23=>'',24=>'',25=>'',
            26=>'<span style=\'color:#[dashboard_color]\'><b>([producedby_region:value]) [output_year] [output_type]</b> | [output_title]', 27=>'([name])',28=>'');

        return $custom_record_label_array;
    }

    public static function getProjectsHooksArray() {
        $projects_array_hooks = array(0=>'1',1=>'1',2=>'1',3=>'1',4=>'1',5=>'1',6=>'1',
            7=>'0',8=>'1',9=>'0', 10=>'0',11=>'1',12=>'0',13=>'0',
            14=>'0',15=>'0',16=>'0',17=>'0',18=>'0',19=>'0',
            20=>'0',21=>'0',22=>'0',23=>'0',24=>'0',25=>'0',26=>'0',27=>'1',28=>'0',30=>'1',41=>'1');
        return $projects_array_hooks;
    }

    public static function getProjectsSurveyHashArray() {
        $projects_array_surveys_hash = array(
            2=>array('constant'=>'CONCEPTLINK','instrument' => 'concept_sheet'),
            3=>array('constant'=>'REQUESTLINK','instrument' => 'request'),
            4=>array('constant'=>'SURVEYLINK','instrument' => 'comments_and_votes'),
            6=>array('constant'=>'SURVEYLINKSOP','instrument' => 'sop_comments'),
            8=>array('constant'=>'SURVEYPERSONINFO','instrument' => 'person_information'),
            22=>array('constant'=>'SURVEYFILELIBRARY','instrument' => 'file_information'),
            24=>array('constant'=>'SURVEYNEWS','instrument' => 'news_item'),
            27=>array('constant'=>'SURVEYTBLCENTERREVISED','instrument' => 'tblcenter')
        );
        return $projects_array_surveys_hash;
    }

	public static function getChoices($metadata) {
		$choicesStrs = array();
		$multis = array("checkbox", "dropdown", "radio");
		foreach ($metadata as $row) {
			if (in_array($row['field_type'], $multis) && $row['select_choices_or_calculations']) {
				$choicesStrs[$row['field_name']] = $row['select_choices_or_calculations'];
			} else if ($row['field_type'] == "yesno") {
				$choicesStrs[$row['field_name']] = "0,No|1,Yes";
			} else if ($row['field_type'] == "truefalse") {
				$choicesStrs[$row['field_name']] = "0,False|1,True";
			}
		}
		$choices = array();
		foreach ($choicesStrs as $fieldName => $choicesStr) {
		    $choices[$fieldName] = self::getRowChoices($choicesStr);
		}
		return $choices;
	}

    public static function getRowChoices($choicesStr) {
        $choicePairs = preg_split("/\s*\|\s*/", $choicesStr);
        $choices = array();
        foreach ($choicePairs as $pair) {
            $a = preg_split("/\s*,\s*/", $pair);
            if (count($a) == 2) {
                $choices[$a[0]] = $a[1];
            } else if (count($a) > 2) {
                $a = preg_split("/,/", $pair);
                $b = array();
                for ($i = 1; $i < count($a); $i++) {
                    $b[] = $a[$i];
                }
                $choices[trim($a[0])] = implode(",", $b);
            }
        }
        return $choices;
    }

    /*
     * Looks for the fields to compare in each row of metadata.
     */
    public static function getMetadataFieldsToScreen() {
        return array("required_field", "form_name", "field_type", "identifier", "branching_logic", "section_header", "field_annotation");
    }

    public static function hasMetadataChanged($oldValue, $newValue, $metadataField) {
        if ($metadataField == "field_annotation" && self::isJSON($oldValue)) {
            return FALSE;
        }
        if (isset($oldValue) && isset($newValue) && ($oldValue != $newValue)) {
            return TRUE;
        }
        return FALSE;
    }

    public static function arraysEqual($ary1, $ary2) {
        if (!isset($ary1) || !isset($ary2)) {
            return FALSE;
        }
        if (!is_array($ary1) || !is_array($ary2)) {
            return FALSE;
        }
        foreach ([$ary1 => $ary2, $ary2 => $ary1] as $aryA => $aryB) {
            foreach ($aryA as $key => $valueA) {
                if (!isset($aryB[$key])) {
                    return FALSE;
                }
                $valueB = $aryB[$key];
                if ($valueA !== $valueB) {
                    return FALSE;
                }
            }
        }
        return TRUE;
    }

    private static function isJSON($str) {
        json_decode($str);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    public static function getFieldsWithRegEx($metadata, $re, $removeRegex = FALSE) {
        $fields = array();
        foreach ($metadata as $row) {
            if (preg_match($re, $row['field_name'])) {
                if ($removeRegex) {
                    $field = preg_replace($re, "", $row['field_name']);
                } else {
                    $field = $row['field_name'];
                }
                array_push($fields, $field);
            }
        }
        return $fields;
    }

    public static function getRowsForFieldsFromMetadata($fields, $metadata) {
        $selectedRows = array();
        foreach ($metadata as $row) {
            if (in_array($row['field_name'], $fields)) {
                array_push($selectedRows, $row);
            }
        }
        return $selectedRows;
    }

    function downloadOneField($project_id, $field, $metadata) {
        $pk = getPk($metadata);
        $json  = \REDCap::getData($project_id, 'json', null,[$pk, $field]);
        $redcapData = json_decode($json, TRUE);
        $data = [];
        foreach ($redcapData as $row) {
            $data[$row[$pk]] = $row[$field];
        }
        return $data;
    }

    function getPk($metadata) {
        return $metadata[0]['field_name'];
    }

    public static function copyMetadataSettingsForField($module, $row, $metadata, &$upload, $project_id) {
        foreach ($metadata as $metadataRow) {
            if ($metadataRow['field_name'] == $row['field_name']) {
                # do not overwrite any settings in associative arrays
                foreach (self::getMetadataFieldsToScreen() as $rowSetting) {
                    if ($rowSetting == "select_choices_or_calculations") {
                        // merge
                        $rowChoices = self::getRowChoices($row[$rowSetting]);
                        $metadataChoices = self::getRowChoices($metadataRow[$rowSetting]);
                        $mergedChoices = $rowChoices;
                        foreach ($metadataChoices as $idx => $label) {
                            if (!isset($mergedChoices[$idx])) {
                                $mergedChoices[$idx] = $label;
                            } else if (isset($mergedChoices[$idx]) && ($mergedChoices[$idx] == $label)) {
                                # both have same idx/label - no big deal
                            } else {
                                # merge conflict => reassign all data values
                                $field = $row['field_name'];
                                $oldIdx = $idx;
                                $newIdx = max(array_keys($mergedChoices)) + 1;
                                $module->log("Merge conflict for field $field: Moving $oldIdx to $newIdx ($label)", $project_id);

                                $mergedChoices[$newIdx] = $label;
                                $values = self::downloadOneField($project_id, $field, $metadata);
                                $newRows = 0;
                                foreach ($values as $recordId => $value) {
                                    if ($value == $oldIdx) {
                                        if (isset($upload[$recordId])) {
                                            $upload[$recordId][$field] = $newIdx;
                                        } else {
                                            $upload[$recordId] = array("record_id" => $recordId, $field => $newIdx);
                                        }
                                        $newRows++;
                                    }
                                }
                                 $module->log("Uploading data $newRows rows for field $field", $project_id);
                            }
                        }
                        $pairedChoices = array();
                        foreach ($mergedChoices as $idx => $label) {
                            array_push($pairedChoices, "$idx, $label");
                        }
                        $row[$rowSetting] = implode(" | ", $pairedChoices);
                    } else if ($row[$rowSetting] != $metadataRow[$rowSetting]) {
                        if (!self::isJSON($row[$rowSetting]) || ($rowSetting != "field_annotation")) {
                            $row[$rowSetting] = $metadataRow[$rowSetting];
                        }
                    }
                }
                break;
            }
        }
        return $row;
    }

    public static function getLabels($metadata) {
        $labels = array();
        foreach ($metadata as $row) {
            $labels[$row['field_name']] = $row['field_label'];
        }
        return $labels;
    }

    private static function deleteRowsWithFieldName(&$metadata, $fieldName) {
        $newMetadata = array();
        foreach ($metadata as $row) {
            if ($row['field_name'] != $fieldName) {
                array_push($newMetadata, $row);
            }
        }
        $metadata = $newMetadata;
    }

    # if present, $fields contains the fields to copy over; if left as an empty array, then it attempts to install all fields
    # $deletionRegEx contains the regular expression that marks fields for deletion
    # places new metadata rows AFTER last match from $existingMetadata
    public static function mergeMetadataAndUpload($module, $originalMetadata, $newMetadata, $fields = array(), $deletionRegEx = "/___delete$/", $project_id) {
        $fieldsToDelete = self::getFieldsWithRegEx($newMetadata, $deletionRegEx, TRUE);
        $existingMetadata = $originalMetadata;

        if (empty($fields)) {
            $selectedRows = $newMetadata;
        } else {
            $selectedRows = self::getRowsForFieldsFromMetadata($fields, $newMetadata);
        }
        $upload = array();
        foreach ($selectedRows as $newRow) {
            if (!in_array($newRow['field_name'], $fieldsToDelete)) {
                $priorRowField = end($existingMetadata)['field_name'];
                foreach ($newMetadata as $row) {
                    if ($row['field_name'] == $newRow['field_name']) {
                        break;
                    } else {
                        $priorRowField = $row['field_name'];
                    }
                }
                if (self::atEndOfMetadata($priorRowField, $selectedRows, $newMetadata)) {
                    $priorRowField = end($originalMetadata)['field_name'];
                }

                $tempMetadata = array();
                $priorNewRowField = "";
                foreach ($existingMetadata as $row) {
                    if (!preg_match($deletionRegEx, $row['field_name']) && !in_array($row['field_name'], $fieldsToDelete)) {
                        if ($priorNewRowField != $row['field_name']) {
                            array_push($tempMetadata, $row);
                        }
                    }
                    if (($priorRowField == $row['field_name']) && !preg_match($deletionRegEx, $newRow['field_name'])) {
                        $newRow = self::copyMetadataSettingsForField($module, $newRow, $newMetadata, $upload, $project_id);

                        # delete already existing rows with same field_name
                        self::deleteRowsWithFieldName($tempMetadata, $newRow['field_name']);
                        array_push($tempMetadata, $newRow);
                        $priorNewRowField = $newRow['field_name'];
                    }
                }
                $existingMetadata = $tempMetadata;
            }
        }
        $metadataFeedback = $module->saveMetadata($project_id, $existingMetadata);
        return $metadataFeedback;
    }

    # returns TRUE if and only if fields in $newMetadata after $priorField are fields in $newRows
    private static function atEndOfMetadata($priorField, $newRows, $newMetadata) {
        $newFields = [];
        foreach ($newRows as $row) {
            $newFields[] = $row['field_name'];
        }

        $found = FALSE;
        foreach ($newMetadata as $row) {
            if ($found) {
                if (!in_array($row['field_name'], $newFields)) {
                    return FALSE;
                }
            } else if ($priorField == $row['field_name']) {
                $found = TRUE;
            }
        }
        return TRUE;
    }

    public static function getEnvironment(){
        if(preg_match("/vanderbilt.edu/i", SERVER_NAME) || preg_match("/vumc.org/i", SERVER_NAME)){
            #Other institutions
            define("ENVIRONMENT", "PROD");
        }else if (SERVER_NAME == "redcap.vanderbilt.edu" || SERVER_NAME == "redcap.vumc.org") {
            define("ENVIRONMENT", "PROD");
        }else  if (SERVER_NAME == "redcaptest.vanderbilt.edu" || SERVER_NAME == "redcaptest.vumc.org") {
            define("ENVIRONMENT", "TEST");
        }else {
            define("ENVIRONMENT", "DEV");
        }
    }

    public static function getProjectsModuleGetPMIDArray(){
        $projects_array_module_getpmid = array(
            2=> array("instrument-name" => "outputs"),
            26=> array("instrument-name" => "output_record")
        );
        return $projects_array_module_getpmid;
    }

    public static function getProjectsModuleEmailAlertsArray($module, $hub_projectname){
        $projects_array_module_emailalerts = array(
            3=> array(
                "datapipeEmail_var" => "[contact_email], Contact Email\n[cc_email1], CC Email 1\n[cc_email2], CC Email 2",
                "emailFromForm_var" => "",
                "emailSender_var" => $hub_projectname." Hub",
                "datapipe_var" => "[request_id], Request ID\n[request_type], Request Type\n[request_title], Request Title\n[request_conf], Conference\n[assoc_concept], Concept Tracking Number\n[wg_name], ".$hub_projectname." WG1\n[request_description], Request Desc\n[contact_email], Contact Email\n[contact_name], Contact Name\n[contact_region], Contact Region\n[reviewer_id], Reviewer ID\n[contactperson_id], Contact Person ID\n[due_d], Due Date\n[reviewer_id], Admin Reviewer Name\n[admin_review_notes], Admin Review Notes\n[approval_y], Admin Approval\n[admin_internal_notes], Admin Internal Notes\n[admin_noemail], No email\n[detected_complete], All votes complete\n[detected_complete_ts], Detected Complete TS\n[finalize_y], Finalized Status\n[final_d], Finalized Date\n[finalizer_id], Finalizing Person\n[custom_note], Custom Note to Author\n[author_doc], Author Final Doc\n[datarequest_type], Requested Data Types\n[mr_assigned], Assigned Tracking Number\n[finalconcept_doc], Final DOC\n[finalconcept_pdf], Final PDF\n[vote_ap], AP vote\n[vote_ca], CA vote\n[vote_cn], CN vote\n[vote_ea], EA vote\n[vote_na], NA vote\n[vote_sa], SA vote\n[vote_wa], WA vote",
                "surveyLink_var" => "[__SURVEYLINK_request],Request Survey\n[__SURVEYLINK_admin_review],Admin Review Survey\n[__SURVEYLINK_finalization_of_request], Finalization of Request\n[__SURVEYLINK_final_docs_request_survey],Final Docs Request\n[__SURVEYLINK_tracking_number_assignment_survey],Tracking Number Assignment Survey",
                "formLink_var" => "",
                "emailFailed_var" => self::DEFAULT_EMAIL_ADDRESS,
                "form-name" => array
                (
                    0 => "request",
                    1 => "admin_review",
                    2 => "request",
                    3 => "admin_review",
                    4 => "admin_review",
                    5 => "admin_review",
                    6 => "admin_review",
                    7 => "admin_review",
                    8 => "admin_review",
                    9 => "finalization_of_request",
                    10 => "admin_review",
                    11 => "finalization_of_request",
                    12 => "final_docs_request_survey",
                    13 => "tracking_number_assignment_survey",
                    14 => "tracking_number_assignment_survey"
                ),
                "form-name-event" => array
                (
                    4 => "",
                    5 => "",
                    3 => "",
                    2 => "",
                    0 => "",
                    1 => "",
                    7 => "",
                    6 => "",
                    8 => "",
                    9 => "",
                    10 => "",
                    11 => "",
                    12 => "",
                    13 => "",
                    14 => ""
                ),
                "email-from" => array
                (
                    4 => "noreply@fakemail.com, ".$hub_projectname." Hub",
                    0 => "noreply@fakemail.com, ".$hub_projectname." Hub",
                    1 => "noreply@fakemail.com, ".$hub_projectname." Hub",
                    2 => "noreply@fakemail.com, ".$hub_projectname." Hub",
                    3 => "noreply@fakemail.com, ".$hub_projectname." Hub",
                    5 => "noreply@fakemail.com, ".$hub_projectname." Hub",
                    6 => "noreply@fakemail.com, ".$hub_projectname." Hub",
                    7 => "noreply@fakemail.com, ".$hub_projectname." Hub",
                    8 => "noreply@fakemail.com, ".$hub_projectname." Hub",
                    9 => "noreply@fakemail.com, ".$hub_projectname." Hub",
                    10 => "noreply@fakemail.com, ".$hub_projectname." Hub",
                    11 => "noreply@fakemail.com, ".$hub_projectname." Hub",
                    12 => "noreply@fakemail.com, ".$hub_projectname." Hub",
                    13 => "noreply@fakemail.com, ".$hub_projectname." Hub",
                    14 => "noreply@fakemail.com, ".$hub_projectname." Hub"
                ),
                "email-to" => array
                (
                    0 => "[contact_email]",
                    1 => "[contact_email]",
                    2 => "noreply@fakemail.com",
                    3 => "noreply@fakemail.com",
                    4 => "noreply@fakemail.com",
                    5 => "noreply@fakemail.com",
                    6 => "noreply@fakemail.com",
                    7 => "noreply@fakemail.com",
                    8 => "noreply@fakemail.com",
                    9 => "noreply@fakemail.com",
                    10 => "noreply@fakemail.com",
                    11 => "[contact_email]",
                    12 => "noreply@fakemail.com",
                    13 => "[contact_email]",
                    14 => "noreply@fakemail.com"
                ),
                "email-cc" => array
                (
                    0 => "",
                    1 => "",
                    2 => "",
                    3 => "",
                    4 => "",
                    5 => "",
                    6 => "",
                    7 => "",
                    8 => "",
                    9 => "",
                    10 => "",
                    11 => "",
                    12 => "",
                    13 => "",
                    14 => ""
                ),
                "email-bcc" => array
                (
                    4 => "",
                    5 => "",
                    3 => "",
                    2 => "",
                    0 => "",
                    1 => "",
                    6 => "",
                    7 => "",
                    8 => "",
                    9 => "",
                    10 => "",
                    11 => "",
                    12 => "",
                    13 => "",
                    14 => ""
                ),
                "email-subject" => array
                (
                    0 => $hub_projectname." Request #[request_id] received: [request_type], [contact_name]",
                    1 => $hub_projectname." Request #[request_id] posted: [request_type], [contact_name]",
                    2 => $hub_projectname." Request #[request_id] needs Admin review: [request_type], [contact_name]",
                    3 => $hub_projectname." Request #[request_id] posted: [request_type], [contact_name]",
                    4 => $hub_projectname." Request #[request_id] deactivated: [request_type], [contact_name]",
                    5 => $hub_projectname." Request #[request_id] rejected: [request_type], [contact_name]",
                    6 => "New ".$hub_projectname."  Hub Request #[request_id]: [request_type], [request_title]",
                    7 => $hub_projectname." Request #[request_id] voting complete: [request_type], [contact_name]",
                    8 => $hub_projectname." Request #[request_id] voting incomplete: [request_type], [contact_name]",
                    9 => $hub_projectname." Request #[request_id] approved by [EXECUTIVE COMMITTEE NAME]: [request_type], [contact_name]",
                    10 => "[insert site name] vote needed: [request_type], [contact_name] (#[request_id])",
                    11 => $hub_projectname." Request #[request_id] post-approval final steps: [request_type], [contact_name]",
                    12 => "New [request_type] needs MR: Request #[request_id], [contact_name]",
                    13 => "New ".$hub_projectname." Concept approved: [mr_assigned], [contact_name]",
                    14 => "New ".$hub_projectname." Concept: [mr_assigned], [contact_name]"
                ),
                "email-text" => array
                (
                    0 => '<h2>New Submission</h2>
<p>Thank you for submitting a review request to the '.$hub_projectname.' [EXECUTIVE COMMITTEE NAME]. This email serves as your confirmation that the request has been submitted to the system. An '.$hub_projectname.' Hub Admin will review your request and contact you with any followup questions. You will be notified once the request is distributed to the '.$hub_projectname.' [EXECUTIVE COMMITTEE NAME].</p>
<p><strong>Request Tracking ID:</strong>&nbsp; [request_id]</p>
<p><strong>Review Type:</strong>&nbsp; [request_type]</p>
<p><strong>Contact Person:</strong>&nbsp; [contact_name], [contact_email]</p>
<p><strong>Request Title:</strong>&nbsp; [request_title]</p>
<p><strong>Description:</strong>&nbsp; [request_description]</p>
<p><span style="color: #000000;"><strong>Link to review/edit submission #[request_id]:</strong> </span>[__SURVEYLINK_request]</p>
<p>&nbsp;</p>
<p><span style="color: #999999; font-size: 11px;">This email has been automatically generated by the <a href="'.$module->getUrl('index.php')."&NOAUTH".'">'.$hub_projectname.' Hub system</a>. If someone incorrectly submitted this request on your behalf or if you believe you received this email in error, please contact <a href="mailto:'.$hub_projectname.'@fake.com">'.$hub_projectname.'@fake.com</a>.</span></p>',
                    1 => '<h2>Submission Posted</h2>
<p>Your request has been reviewed by <strong>[reviewer_id]</strong> and will now be displayed on the '.$hub_projectname.' Hub. This will begin the '.$hub_projectname.' [EXECUTIVE COMMITTEE NAME] review process.</p>
<p><strong><span style="color: #e74c3c;">Due Date Assigned</span>:</strong>&nbsp;[due_d]</p>
<p><strong>Request Tracking ID:</strong>&nbsp; [request_id]</p>
<p><strong>Review Type:</strong>&nbsp; [request_type]</p>
<p><strong>Contact Person:</strong>&nbsp; [contact_name], [contact_email]</p>
<p><strong>Request Title:</strong>&nbsp; [request_title]</p>
<p><strong>Description:</strong>&nbsp; [request_description]</p>
<p><strong>Public Admin Notes:</strong>&nbsp; [admin_review_notes]</p>
<p><span style="color: #000000;"><strong>Link to review/edit submission #[request_id]:</strong> </span>[__SURVEYLINK_request]</p>
<p>&nbsp;</p>
<p><span style="color: #999999; font-size: 11px;">This email has been automatically generated by the <a href="'.$module->getUrl('index.php')."&NOAUTH".'">'.$hub_projectname.' Hub system</a>. If someone incorrectly submitted this request on your behalf or if you believe you received this email in error, please contact <a href="mailto:'.self::DEFAULT_EMAIL_ADDRESS.'">'.self::DEFAULT_EMAIL_ADDRESS.'</a>.</span></p>',
                    2 => '<h2>New Submission</h2>
<p>A new review request has been submitted and requires admin review before posting to the '.$hub_projectname.' Hub.</p>
<p><strong>Request Tracking ID:</strong>&nbsp; [request_id]</p>
<p><strong>Review Type:</strong>&nbsp; [request_type]</p>
<p><strong>Contact Person:</strong>&nbsp; [contact_name], [contact_email]</p>
<p><strong>Request Title:</strong>&nbsp; [request_title]</p>
<p><strong>Description:</strong>&nbsp; [request_description]</p>
<h2>Actions</h2>
<p><strong>1. Link to <span style="color: #e74c3c;">review/edit submission #[request_id]</span>:</strong>&nbsp;<br />[__SURVEYLINK_request]</p>
<p><strong>2. Link to Hub Admin <span style="color: #16a085;">approval page</span>:</strong><br />[__SURVEYLINK_admin_review]</p>',
                    3 => '<h2>Submission Posted</h2>
<p>The following request has been reviewed by <strong>[reviewer_id]</strong> and will now be displayed on the '.$hub_projectname.' Hub.</p>
<p><strong><span style="color: #e74c3c;">Due Date Assigned</span>:</strong>&nbsp;[due_d]</p>
<p><strong>Request Tracking ID:</strong>&nbsp; [request_id]</p>
<p><strong>Review Type:</strong>&nbsp; [request_type]</p>
<p><strong>Contact Person:</strong>&nbsp; [contact_name], [contact_email]</p>
<p><strong>Request Title:</strong>&nbsp; [request_title]</p>
<p><strong>Description:</strong>&nbsp; [request_description]</p>
<p><strong>Public Admin Notes:</strong>&nbsp; [admin_review_notes]</p>
<p><strong>Internal Admin Notes:</strong>&nbsp; [admin_internal_notes]</p>
<h2>Reference Links</h2>
<p><strong>1. Link to <span style="color: #e74c3c;">review/edit submission #[request_id]</span>:</strong>&nbsp;<br />[__SURVEYLINK_request]</p>
<p><strong>2. Link to Hub Admin <span style="color: #16a085;">approval page</span>:</strong><br />[__SURVEYLINK_admin_review]</p>',
                    4 => '<h2>Submission Paused/Deactivated</h2>
<p>The following request has been paused or deactivated by <strong>[reviewer_id]</strong>. It will not appear on the '.$hub_projectname.' Hub.</p>
<p><strong>Request Tracking ID:</strong>&nbsp; [request_id]</p>
<p><strong>Review Type:</strong>&nbsp; [request_type]</p>
<p><strong>Contact Person:</strong>&nbsp; [contact_name], [contact_email]</p>
<p><strong>Request Title:</strong>&nbsp; [request_title]</p>
<p><strong>Description:</strong>&nbsp; [request_description]</p>
<p><strong>Public Admin Notes:</strong>&nbsp; [admin_review_notes]</p>
<p><strong>Internal Admin Notes:</strong>&nbsp; [admin_internal_notes]</p>
<h2>Reference Links</h2>
<p><strong>1. Link to <span style="color: #e74c3c;">review/edit submission #[request_id]</span>:</strong>&nbsp;<br />[__SURVEYLINK_request]</p>
<p><strong>2. Link to Hub Admin <span style="color: #16a085;">approval page</span>:</strong><br />[__SURVEYLINK_admin_review]</p>',
                    5 => '<h2>Submission Rejected</h2>
<p>The following request has been rejected by <strong>[reviewer_id]</strong>. It will not appear on the '.$hub_projectname.' Hub. <strong><span style="color: #e74c3c;">The Contact Person has not been notified.</span></strong></p>
<p><strong>Request Tracking ID:</strong>&nbsp; [request_id]</p>
<p><strong>Review Type:</strong>&nbsp; [request_type]</p>
<p><strong>Contact Person:</strong>&nbsp; [contact_name], [contact_email]</p>
<p><strong>Request Title:</strong>&nbsp; [request_title]</p>
<p><strong>Description:</strong>&nbsp; [request_description]</p>
<p><strong>Public Admin Notes:</strong>&nbsp; [admin_review_notes]</p>
<p><strong>Internal Admin Notes:</strong>&nbsp; [admin_internal_notes]</p>
<h2>Reference Links</h2>
<p><strong>1. Link to <span style="color: #e74c3c;">review/edit submission #[request_id]</span>:</strong>&nbsp;<br />[__SURVEYLINK_request]</p>
<p><strong>2. Link to Hub Admin <span style="color: #16a085;">approval page</span>:</strong><br />[__SURVEYLINK_admin_review]</p>',
                    6 => '<h2>New Request for Review</h2>
<p class="MsoNormal">Dear '.$hub_projectname.' Investigators,</p>
<p class="MsoNormal">A new '.$hub_projectname.' Hub requested has been posted for your review. Please use the link below to review, comment, and vote on this request.</p>
<p class="MsoNormal">Thank you!</p>
<p class="MsoNormal">'.$hub_projectname.' Hub Admins</p>
<p class="MsoNormal"></p>
<p><strong><span style="color: #e74c3c;">Due Date Assigned</span>:</strong> [due_d]</p>
<p><strong>Request Tracking ID:</strong> [request_id]</p>
<p><strong>Review Type:</strong> [request_type]</p>
<p><strong>Contact Person:</strong> [contact_name], [contact_email]</p>
<p><strong>Request Title:</strong> [request_title]</p>
<p><strong>Message from [contact_name]:</strong></p>
<p>[request_description]</p>
<p></p>
<p><strong>Link to review request: </strong><a href="'.APP_PATH_WEBROOT_FULL.'external_modules/?prefix=harmonist-hub&amp;page=index&amp;pid=___project_id_new&amp;NOAUTH&amp;option=hub&amp;record=[request_id]">https://redcap.vumc.org/external_modules/?prefix=harmonist-hub&amp;page=index&amp;pid=___project_id_new&amp;NOAUTH&amp;option=hub&amp;record=[request_id]</a></p>
<p></p>
<p><span style="color: #999999; font-size: 11px;">This email has been automatically generated by the '.$hub_projectname.' Hub system. If someone incorrectly submitted this request on your behalf or if you believe you received this email in error, please contact <a href="mailto:'.self::DEFAULT_EMAIL_ADDRESS.'">'.self::DEFAULT_EMAIL_ADDRESS.'</a>.</span></p>',
                    7 => '<h2>Voting Complete</h2>
<p>The following request has received all regional votes. Please take action below to finalize the request or respond to votes and comments.</p>
<p><strong><span style="color: #e74c3c;">Due Date Assigned</span>:</strong>&nbsp;[due_d]</p>
<p><strong>Request Tracking ID:</strong>&nbsp; [request_id]</p>
<p><strong>Review Type:</strong>&nbsp; [request_type]</p>
<p><strong>Contact Person:</strong>&nbsp; [contact_name], [contact_email]</p>
<p><strong>Request Title:</strong>&nbsp; [request_title]</p>
<h2>Summary of Votes</h2>
<p><strong>[REGION 1]:</strong>&nbsp; [vote_ap]</p>
<p><strong>[REGION 2]:</strong>&nbsp; [vote_ca]</p>
<p><strong>[REGION 3]:</strong>&nbsp; [vote_cn]</p>
<p><strong>[REGION 4]:</strong>&nbsp; [vote_ea]</p>
<p><strong>[REGION 5]:</strong>&nbsp; [vote_na]</p>
<p><strong>[REGION 6]:</strong>&nbsp; [vote_sa]</p>
<p><strong>[REGION 7]:</strong>&nbsp; [vote_wa]</p>
<h2>Actions</h2>
<p><strong>1. Link to <span style="color: #16a085;">Finalize Request page</span>:</strong><br />[__SURVEYLINK_finalization_of_request]</p>',
                    8 => '<h2>Voting Incomplete</h2>
<p>The following request is due today but has not received all votes.&nbsp;</p>
<p><strong><span style="color: #e74c3c;">Due Date Assigned</span>:</strong>&nbsp;[due_d]</p>
<p><strong>Request Tracking ID:</strong>&nbsp; [request_id]</p>
<p><strong>Review Type:</strong>&nbsp; [request_type]</p>
<p><strong>Contact Person:</strong>&nbsp; [contact_name], [contact_email]</p>
<p><strong>Request Title:</strong>&nbsp; [request_title]</p>
<h2>Summary of Votes</h2>
<p><strong>[REGION 1]:</strong>&nbsp; [vote_ap]</p>
<p><strong>[REGION 2]:</strong>&nbsp; [vote_ca]</p>
<p><strong>[REGION 3]:</strong>&nbsp; [vote_cn]</p>
<p><strong>[REGION 4]:</strong>&nbsp; [vote_ea]</p>
<p><strong>[REGION 5]:</strong>&nbsp; [vote_na]</p>
<p><strong>[REGION 6]:</strong>&nbsp; [vote_sa]</p>
<p><strong>[REGION 7]:</strong>&nbsp; [vote_wa]</p>
<h2>Actions</h2>
<p><strong>1. Link to <span style="color: #31708f;">Visit Request page</span>:</strong><br /><a href="'.$module->getUrl('index.php').'&NOAUTH&option=hub&amp;record=[request_id]'.'">'.$module->getUrl('index.php?option=hub&amp;record=[request_id]').'</a></p>
<p><strong>2. Link to <span style="color: #16a085;">Finalize Request page</span>:</strong><br />[__SURVEYLINK_finalization_of_request]</p>',
                    9 => '<h2>Request Approved by '.$hub_projectname.' [EXECUTIVE COMMITTEE NAME]</h2>
<p>Dear [contact_name],</p>
<p>We are pleased to confirm approval of your '.$hub_projectname.' [request_type], <strong>[request_title]</strong>. The '.$hub_projectname.' [EXECUTIVE COMMITTEE NAME] approval date is [final_d].</p>
<p><strong>Next Steps</strong></p>
<ol>
<li>Please check the '.$hub_projectname.' Hub for <a href="'.$module->getUrl('index.php').'&NOAUTH&option=hub&amp;record=[request_id]'.'">any comments or queries from the regions</a> and incorporate further revisions into your document.</li>
<li>Remove all comments and tracked changes from the document.</li>
<li>Upload a final version of your [request_type] for logging at the link below.&nbsp;</li>
</ol>
<p>[__SURVEYLINK_final_docs_request_survey]</p>
<p>Please contact [HUB ADMIN CONTACT] or [HUB ADMIN CONTACT2] if you have any questions.</p>
<p>Thank you for participating in '.$hub_projectname.' research.</p>
<hr />
<p><em>Additional review notes (optional):</em></p>
<p>[custom_note]</p>',
                    10 => '<h2>Vote Due: [insert site name]</h2>
<p><em>*you can duplicate this alert for each site you would like to send a reminder e-mail to, delete this message after updating.</em></p>
<p>The following request is due <strong>in 1 day</strong> but has not received your site vote.</p>
<p><strong><span style="color: #e74c3c;">Due Date Assigned</span>:</strong> [due_d]</p>
<p><strong>Request Tracking ID:</strong> [request_id]</p>
<p><strong>Review Type:</strong> [request_type]</p>
<p><strong>Contact Person:</strong> [contact_name], [contact_email]</p>
<p><strong>Request Title:</strong> [request_title]</p>
<p><strong>[insert site name] Vote Status:</strong> [region_vote_status][X] <em>*you will need to find the record ID for the site you wish to send this e-mail, and adjust the X to that number, delete this message after updating.</em></p>
<p>&nbsp;</p>
<h2>Actions</h2>
<p><strong>1. Review and <span style="color: #31708f;">Vote on Request</span>:</strong><br><a href="'.APP_PATH_WEBROOT_FULL.'external_modules/?prefix=harmonist-hub&page=index&pid=___project_id_new&NOAUTH&option=hub&record=[request_id]">https://redcap.vumc.org/external_modules/?prefix=harmonist-hub&page=index&pid=___project_id_new&NOAUTH&option=hub&record=[request_id]</a></p>
<p></p>
<p>This email has been automatically generated by the '.$hub_projectname.' Hub system. If someone incorrectly submitted this request on your behalf or if you believe you received this email in error, please contact '.self::DEFAULT_EMAIL_ADDRESS.'.</p>',
                    11 => '<h2>Final Documents Requested</h2>
<p>Dear [contact_name],</p>
<p>We are pleased to confirm approval of your '.$hub_projectname.' concept,&nbsp;<strong>[request_title]</strong>. The '.$hub_projectname.' [EXECUTIVE COMMITTEE NAME] approval date is <strong>[final_d]</strong>.</p>
<p><strong>Next Steps</strong></p>
<ol>
<li style="padding-bottom: 5px;">Please check the '.$hub_projectname.' Hub for <a href="'.APP_PATH_WEBROOT_FULL.'plugins/iedea/index.php?option=hub&amp;record=[request_id]">any changes requested by the Executive Comitee</a> and incorporate further revisions into your concept sheet.</li>
<li style="padding-bottom: 5px;">Remove all comments and tracked changes from the document.</li>
<li style="padding-bottom: 5px;"><strong><span style="color: #e74c3c;">Upload a final version of your concept</span></strong> to the '.$hub_projectname.' Hub using the link below. <strong>This will trigger all subsequent steps for your project.</strong></li>
</ol>
<p>&nbsp;</p>
<p>[__SURVEYLINK_final_docs_request_survey]</p>
<p>&nbsp;</p>
<p>After you have uploaded the final version of your concept, the '.$hub_projectname.' concept sheet management team will <strong>assign a concept tracking number</strong> and your concept will be logged on the Hub. You <strong>must have a tracking number before requesting '.$hub_projectname.' data.</strong>&nbsp;Once your tracking number has been assigned, you will receive a notification email with the tracking number and next steps for your project. Please contact&nbsp;[HUB ADMIN CONTACT]&nbsp;or&nbsp;[HUB ADMIN CONTACT2]&nbsp;if you have any questions.</p>
<p>This email is scheduled to repeat as a reminder&nbsp;<strong>every 7 days</strong> until documents have been uploaded.&nbsp;</p>
<p>Thank you for participating in '.$hub_projectname.' research.</p>
<p>&nbsp;</p>
<hr />
<p><em>Additional review notes (if needed):</em></p>
<p>[custom_note]</p>
<hr />
<p>&nbsp;</p>
<p><span style="color: #999999; font-size: 11px;">This email has been automatically generated by the <a href="'.$module->getUrl('index.php')."&NOAUTH".'">'.$hub_projectname.' Hub system</a>. If someone incorrectly submitted this request on your behalf or if you believe you received this email in error, please contact <a href="mailto:'.self::DEFAULT_EMAIL_ADDRESS.'">'.self::DEFAULT_EMAIL_ADDRESS.'</a>.</span></p>',
                    12 => '<h2>'.$hub_projectname.' Concept Needs Tracking Number</h2>
<p>Dear [HUB ADMIN CONTACT],</p>
<p>The following '.$hub_projectname.' concept has been approved by the [EXECUTIVE COMMITTEE NAME] on [final_d].</p>
<p><strong>[request_title]</strong> (Request ID: [request_id])</p>
<p>[contact_name], <a href="mailto:[contact_email]">[contact_email]</a></p>
<p>The author\'s final documents are attached. Please follow the link below to assign a tracking number and update the documents.</p>
<p>This email <strong>will repeat every 7 days</strong> as a reminder.</p>
<p>&nbsp;</p>
<h2>Actions</h2>
<p><strong>1. Link to <span style="color: #e74c3c;">view author submission (optional)</span>:</strong>&nbsp;<br />[__SURVEYLINK_final_docs_request_survey]</p>
<p><strong>2. Link to <span style="color: #16a085;">assign tracking number</span>:</strong><br />[__SURVEYLINK_tracking_number_assignment_survey]</p>
<p>&nbsp;</p>
<p><span style="color: #999999; font-size: 11px;">This email has been automatically generated by the <a href="'.$module->getUrl('index.php').'&NOAUTH'.'">'.$hub_projectname.' Hub system</a>. If someone incorrectly submitted this request on your behalf or if you believe you received this email in error, please contact <a href="mailto:'.self::DEFAULT_EMAIL_ADDRESS.'">'.self::DEFAULT_EMAIL_ADDRESS.'</a>.</span></p>',
                    13 => '<h2>'.$hub_projectname.' Tracking Number Assigned</h2>
<p>Dear [contact_name],</p>
<p>Your '.$hub_projectname.' concept, <strong>[request_title]</strong>, has been assigned the following tracking number:</p>
<p><strong>[mr_assigned]</strong></p>
<p>A listing for your new concept will be available soon on the Concepts page of the '.$hub_projectname.' Hub (<a href="'.$module->getUrl('index.php').'&NOAUTH'.'">'.$module->getUrl('index.php').'</a>).</p>
<p><strong>Next Steps</strong></p>
<ol>
<li style="padding-bottom: 5px;"><strong>Data:</strong> The '.$hub_projectname.' regional data leads are cc\'d if you are requesting either '.$hub_projectname.' patient-level or site assessment data. Please follow up with them to develop an official Data Request or receive access to existing datasets, as stated in your [EXECUTIVE COMMITTEE NAME]-approved concept sheet.</li>
<li style="padding-bottom: 5px;"><strong>Collaborating Authors:</strong> We recommend including regional representatives on your writing team at an early stage of the project (don\'t wait until the end.) If you need to identify regional collaborators, send your request to [HUB ADMIN CONTACT], who can forward it to the regional coordinators.</li>
<li style="padding-bottom: 5px;"><strong>Updates:</strong> You will be automatically subscribed to an email survey that will request a brief project update (2-3 sentences) related to this concept every 90 days. These hub-updates will be shared with the [EXECUTIVE COMMITTEE NAME] and logged in the overall '.$hub_projectname.' project tracker.</li>
<li style="padding-bottom: 5px;"><strong>Abstracts and Publications:</strong> All resulting abstracts and publications will need review and approval from your collaborating working group (if applicable) and the '.$hub_projectname.' [EXECUTIVE COMMITTEE NAME]. [EXECUTIVE COMMITTEE NAME] turnaround times are approximately 1+ weeks for abstracts and 2+ weeks for manuscripts. Actual dates are set by the '.$hub_projectname.' admin team. Please plan ahead for any conference and journal deadlines.</li>
</ol>
<p>Thank you for participating in '.$hub_projectname.' research.</p>
<p>&nbsp;</p>
<p><span style="color: #999999; font-size: 11px;">This email has been automatically generated by the <a href="'.$module->getUrl('index.php').'&NOAUTH'.'">'.$hub_projectname.' Hub system</a>. If someone incorrectly submitted this request on your behalf or if you believe you received this email in error, please contact <a href="mailto:'.self::DEFAULT_EMAIL_ADDRESS.'">'.self::DEFAULT_EMAIL_ADDRESS.'</a>.</span></p>',
                    14 => '<h2>New '.$hub_projectname.' Concept: [mr_assigned]</h2>
<p>The following new '.$hub_projectname.' concept has been approved by the '.$hub_projectname.' [EXECUTIVE COMMITTEE NAME]:</p>
<p><strong>[mr_assigned]:&nbsp;</strong><em>[request_title]</em></p>
<p>&nbsp;</p>
<p>The main project contact is <strong>[contact_name]</strong> (<a href="mailto:[contact_email]">[contact_email]</a>).</p>
<p>The finalized concept sheet PDF is attached (if available). Project hub-updates will be tracked on the <a href="'.$module->getUrl('index.php').'&NOAUTH'.'">'.$hub_projectname.' Hub</a>. Please archive the document or distribute for review in your regions.</p>
<p>&nbsp;</p>
<p><span style="color: #999999; font-size: 11px;">This email has been automatically generated by the <a href="'.$module->getUrl('index.php').'&NOAUTH'.'">'.$hub_projectname.' Hub system</a>. If you believe you received this email in error, please contact <a href="mailto:'.self::DEFAULT_EMAIL_ADDRESS.'">'.self::DEFAULT_EMAIL_ADDRESS.'</a>.</span></p>',
                    ),
                "email-attachment-variable" => array
                (
                    0 => "",
                    1 => "",
                    2 => "",
                    3 => "",
                    4 => "",
                    5 => "",
                    6 => "",
                    7 => "",
                    8 => "",
                    9 => "",
                    10 => "",
                    11 => "",
                    12 => "[author_doc]",
                    13 => "",
                    14 => "[finalconcept_pdf]"
                ),
                "email-repetitive" => array
                (
                    0 => 0,
                    1 => 0,
                    2 => 0,
                    3 => 0,
                    4 => 0,
                    5 => 0,
                    6 => 0,
                    7 => 0,
                    8 => 0,
                    9 => 0,
                    10 => 0,
                    11 => 0,
                    12 => 0,
                    13 => 0,
                    14 => 0
                ),
                "email-deleted" => array
                (
                    0 => 0,
                    1 => 0,
                    2 => 0,
                    3 => 0,
                    4 => 0,
                    5 => 0,
                    6 => 0,
                    7 => 0,
                    8 => 0,
                    9 => 0,
                    10 => 0,
                    11 => 0,
                    12 => 0,
                    13 => 0,
                    14 => 0
                ),
                "email-deactivate" => array
                (
                    0 => 1,
                    1 => 1,
                    2 => 1,
                    3 => 1,
                    4 => 1,
                    5 => 1,
                    6 => 1,
                    7 => 1,
                    8 => 1,
                    9 => 1,
                    10 => 1,
                    11 => 1,
                    12 => 1,
                    13 => 1,
                    14 => 1
                ),
                "email-condition" => array
                (
                    0 => "",
                    1 => "[approval_y]=1 and [admin_noemail(1)] <> '1'",
                    2 => "",
                    3 => "[approval_y]=1",
                    4 => "[approval_y]=9",
                    5 => "[approval_y]=0",
                    6 => "[approval_y]=1 and [admin_noemail] <> '1'",
                    7 => "[approval_y] = '1'",
                    8 => "([due_d] <> \"\") and ([finalize_y] = \"\") and ([approval_y] = 1)",
                    9 => "[finalize_y]=1 and [request_type]<>1 and [request_type]<>5 and [finalize_noemail]<>'1'",
                    10 => "([due_d] <> \"\") and ([finalize_y] = \"\") and ([approval_y] = 1)",
                    11 => "[finalize_y]=1 and ([request_type]=1 or [request_type]=5) and [finalize_noemail]<>'1'",
                    12 => "[author_doc]<>\"\" and [finaldocs_noemail] <> '1'",
                    13 => "[mr_assigned] <> \"\" and [mr_noemail(1)] <> '1'",
                    14 => "[mr_assigned] <> \"\" and [mr_noemail(2)] <> '1'"
                ),
                "email-incomplete" => array
                (
                    0 => 0,
                    1 => 0,
                    2 => 0,
                    3 => 0,
                    4 => 0,
                    5 => 0,
                    6 => 0,
                    7 => 0,
                    8 => 0,
                    9 => 0,
                    10 => 0,
                    11 => 0,
                    12 => 0,
                    13 => 0,
                    14 => 0
                ),
                "cron-send-email-on" => array
                (
                    0 => "now",
                    7 => "calc",
                    8 => "calc",
                    9 => "now",
                    1 => "now",
                    2 => "now",
                    3 => "now",
                    4 => "now",
                    5 => "now",
                    6 => "now",
                    10 => "calc",
                    11 => "calc",
                    12 => "calc",
                    13 => "now",
                    14 => "now"
                ),
                "cron-send-email-on-field" => array
                (
                    0 => "",
                    7 => "sum(if([vote_ap] <> \"\", 1, 0), if([vote_ca] <> \"\", 1, 0), if([vote_cn] <> \"\", 1, 0), if([vote_ea] <> \"\", 1, 0), if([vote_na] <> \"\", 1, 0), if([vote_sa] <> \"\", 1, 0), if([vote_wa] <> \"\", 1, 0)) = '7' and [finalize_y] = ''",
                    8 => "(sum(if([vote_ap] <> \"\", 1, 0), if([vote_ca] <> \"\", 1, 0), if([vote_cn] <> \"\", 1, 0), if([vote_ea] <> \"\", 1, 0), if([vote_na] <> \"\", 1, 0), if([vote_sa] <> \"\", 1, 0), if([vote_wa] <> \"\", 1, 0)) < '7') and (datediff( [due_d], 'today', 'd', 'ymd', true) = 0) and ([due_d] <> \"\") and ([finalize_y] = \"\")",
                    9 => "",
                    1 => "",
                    2 => "",
                    3 => "",
                    4 => "",
                    5 => "",
                    6 => "",
                    10 => "([region_vote_status][10] = \"\") and (datediff([due_d], \"today\", \"d\", \"ymd\", true) = -1) and ([due_d] <> \"\") and ([finalize_y] = \"\")",
                    11 => "[finalize_y]=1 and ([request_type]=1 or [request_type]=5) and [finalize_noemail]<>'1'",
                    12 => "[author_doc]<>\"\" and [finaldocs_noemail] <> '1'",
                    13 => "",
                    14 => ""
                ),
                "cron-repeat-for" => array
                (
                    0 => 0,
                    7 => 0,
                    8 => 0,
                    9 => 0,
                    1 => 0,
                    2 => 0,
                    3 => 0,
                    4 => 0,
                    5 => 0,
                    6 => 0,
                    10 => 0,
                    11 => 7,
                    12 => 7,
                    13 => 0,
                    14 => 0
                ),
                "cron-queue-expiration-date" => array
                (
                    0 => "never",
                    7 => "cond",
                    8 => "cond",
                    9 => "never",
                    1 => "never",
                    2 => "never",
                    3 => "never",
                    4 => "never",
                    5 => "never",
                    6 => "never",
                    10 => "cond",
                    11 => "cond",
                    12 => "cond",
                    13 => "never",
                    14 => "never"
                ),
                "cron-queue-expiration-date-field" => array
                (
                    0 => "[finalize_y] <> \"\"",
                    7 => "[finalize_y] <> \"\"",
                    8 => "([finalize_y] <> \"\") or ([approval_y] = 9) or ([approval_y] = 0)",
                    9 => "",
                    1 => "",
                    2 => "",
                    3 => "",
                    4 => "",
                    5 => "",
                    6 => "",
                    10 => "[finalize_y] <> \"\" or [approval_y] = 9 or [approval_y] = 0",
                    11 => "[author_doc] <> '' or [mr_assigned] <> \"\"",
                    12 => "[mr_assigned] <> \"\"",
                    13 => "",
                    14 => ""
                ),
                "alert-id" => array
                (
                    0 => 0,
                    1 => 1,
                    2 => 2,
                    3 => 3,
                    4 => 4,
                    5 => 5,
                    6 => 6,
                    7 => 7,
                    8 => 8,
                    9 => 9,
                    10 => 10,
                    11 => 11,
                    12 => 12,
                    13 => 13,
                    14 => 14,
                    16 => 16,
                    17 => 17,
                    18 => 18,
                    19 => 19
                ),
                "alert-name" => array(
                    0 => "To Author: confirmation of initial submission",
                    1 => "To Author: Admin posting confirmed",
                    2 => "To Admin: heads-up of new request",
                    3 => "To Admin: request posted to Hub",
                    4 => "To Admin: request deactivated (not posted to Hub)",
                    5 => "To Admin: request rejected (not posted to Hub)",
                    6 => "To Consortium: New request for review",
                    7 => "To Admins: notify voting complete",
                    8 => "To Admins: alert voting incomplete by due_d",
                    9 => "To Admin: non-concept approved by EC",
                    10 => "To Site: alert voting incomplete by due date",
                    11 => "To Author: concept or fast-track approved by EC",
                    12 => "To Tracking Number Team: Assign Tracking Number",
                    13 => "To Author and Admins: Tracking Number Assigned",
                    14 => "To PMs: notification of new concept"
                )
            )
        );
        return $projects_array_module_emailalerts;
    }

    public static function addRepeatableInstrument($module, $projects_array_repeatable, $project_id_new): void
    {
        foreach($projects_array_repeatable as $repeat_event){
            if($repeat_event['status'] == 1){
                $q = $module->query("SELECT b.event_id FROM redcap_events_arms a LEFT JOIN redcap_events_metadata b ON(a.arm_id = b.arm_id) where a.project_id = ?",[$project_id_new]);
                while ($row = $q->fetch_assoc()) {
                    $event_id = $row['event_id'];
                    $q_repeat_data = $module->query("SELECT event_id, form_name, custom_repeat_form_label FROM redcap_events_repeat WHERE event_id = ? AND form_name=?",[$event_id,$repeat_event['instrument']]);
                    if(empty($q_repeat_data->fetch_assoc())){
                        $module->query("INSERT INTO redcap_events_repeat (event_id, form_name, custom_repeat_form_label) VALUES (?, ?, ?)",[$event_id,$repeat_event['instrument'],$repeat_event['params']]);
                    }
                }
            }
        }
    }

    public static function createSurveys($module, $projects_array_surveys, $index, $project_id_new): void
    {
        if(array_key_exists($index,$projects_array_surveys)){
            $module->query("UPDATE redcap_projects SET surveys_enabled = ? WHERE project_id = ?",["1",$project_id_new]);
            foreach ($projects_array_surveys[$index] as $survey){
                $formName = ucwords(str_replace("_"," ",$survey));
                $q = $module->query("SELECT project_id,form_name,survey_enabled FROM redcap_surveys WHERE project_id = ? AND form_name = ?",[$project_id_new,$survey]);
                $row = $q->fetch_assoc();
                if(empty($row)){
                    $module->query("INSERT INTO redcap_surveys (project_id,form_name,survey_enabled,save_and_return,save_and_return_code_bypass,edit_completed_response,title) VALUES (?,?,?,?,?,?,?)",[$project_id_new,$survey,1,1,1,1,$formName]);

                    $surveyId = db_insert_id();
                    $hash = $module->generateUniqueRandomSurveyHash();
                    $Proj = new \Project($project_id_new);
                    $event_id = $Proj->firstEventId;
                    $module->query("INSERT INTO redcap_surveys_participants (survey_id,hash,event_id) VALUES (?,?,?)",[$surveyId,$hash,$event_id]);
                }
            }
        }
    }
}
