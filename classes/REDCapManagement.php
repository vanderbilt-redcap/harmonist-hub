<?php
namespace Vanderbilt\HarmonistHubExternalModule;


class REDCapManagement {

    public static function getProjectsContantsArray(){
        $projects_array = array(28=>'SETTINGS', 0=>'DATAMODEL',1=>'CODELIST',29=>'DATAMODELMETADATA',2=>'HARMONIST',3=>'RMANAGER',4=>'COMMENTSVOTES',5=>'SOP',6=>'SOPCOMMENTS',
            7=>'REGIONS',8=>'PEOPLE',9=>'GROUP', 10=>'FAQ',11=>'HOME',12=>'DATAUPLOAD',13=>'DATADOWNLOAD',
            14=>'JSONCOPY',15=>'METRICS',16=>'DATAAVAILABILITY',17=>'ISSUEREPORTING',18=>'DATATOOLMETRICS',19=>'DATATOOLUPLOADSECURITY',
            20=>'FAQDATASUBMISSION',21=>'CHANGELOG',22=>'FILELIBRARY',23=>'FILELIBRARYDOWN',24=>'NEWITEMS',25=>'ABOUT',26=>'EXTRAOUTPUTS',
            27=>'TBLCENTERREVISED');

        return $projects_array;
    }

    public static function getProjectsTitlesArray(){
        $projects_array_title= array(0=>'Data Model (0A)',1=>'Code Lists (0B)',2=>'Concept Sheets (1)',3=>'Request Manager (2)',
            4=>'Comments and Votes (2B)',5=>'Data Requests (3)',6=>'Data Request Comments (3B)', 7=>'Research Groups (4)',8=>'People (5)',
            9=>'Working Groups (6)', 10=>'Hub FAQ (7)',11=>'Homepage Content (8)',12=>'Data Upload Log (9)',13=>'Data Download Log (10)',
            14=>'Data Model JSON (11)',15=>'Metrics (12)',16=>'Data Availability (13)',17=>'Issue Reporting (14)',
            18=>'Toolkit Usage Metrics (15)',19=>'Toolkit Upload Security (16)',20=>'Toolkit FAQ (17)', 21=>'Changelog (18)',
            22=>'File Library (19)',23=>'File Library Log (20)',24=>'News Items (21)',25=>'About (22)',26=>'Extra Outputs (23)',
            27=>'Consortium Site List (24)',28=>'Settings (99)',29=>'Toolkit Metadata (0C)');

        return $projects_array_title;
    }

    public static function getSurveyContantsArray(){
        $projects_array = array(
            30=>'ANALYTICS',
            31=>'CONCEPTLINK',
            32=>'REQUESTLINK',
            33=>'SURVEYLINK',
            34=>'SURVEYLINKSOP',
            35=>'SURVEYPERSONINFO',
            36=>'REPORTBUGSURVEY',
            37=>'SURVEYFILELIBRARY',
            38=>'SURVEYNEWS',
            39=>'SURVEYTBLCENTERREVISED');

        return $projects_array;
    }

    public static function getProjectConstantsArrayWithoutDeactivatedProjects(){
        $projects_array = self::getProjectsContantsArray();
        $RecordSetSettings = \REDCap::getData($pidsArray['SETTINGS'], 'array', null);
        $settings = ProjectData::getProjectInfoArray($RecordSetSettings)[0];

        $deactivatedConstants = array();
        if($settings['deactivate_toolkit'][1] == '1'){
            array_push($deactivatedConstants,'DATATOOLUPLOADSECURITY');
            array_push($deactivatedConstants,'DATATOOLMETRICS');
        }
        if($settings['deactivate_datahub'][1] == '1'){
            array_push($deactivatedConstants,'DATAUPLOAD');
            array_push($deactivatedConstants,'DATADOWNLOAD');
            array_push($deactivatedConstants,'SOP');
            array_push($deactivatedConstants,'SOPCOMMENTS');
        }
        if($settings['deactivate_tblcenter'][1] == '1'){
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

    public static function getPIDsArray($project_id){
        $projects_array = array_merge(self::getProjectsContantsArray(),self::getSurveyContantsArray());
        $pidsArray = array();
        foreach ($projects_array as $constant){
            $RecordSetHarmonist = \REDCap::getData($project_id, 'array', null,null,null,null,false,false,false,"[project_constant]='".$constant."'");
            $pid = ProjectData::getProjectInfoArray($RecordSetHarmonist)[0]['project_id'];
            if($pid != ""){
                $pidsArray[$constant] = $pid;
            }
        }
        $pidsArray['PROJECTS'] = $project_id;
        return $pidsArray;
    }

    public static function getProjectsRepeatableArray(){
        $projects_array_repeatable = array(
            0=>array(0=>array('status'=>1,'instrument'=>'variable_metadata','params'=>'[variable_name]')),
            1=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
            2=>array(
                0=>array('status'=>1,'instrument'=>'participants','params'=>'[person_role], [person_link]'),
                1=>array('status'=>1,'instrument'=>'admin_update','params'=>'[adminupdate_d]'),
                2=>array('status'=>1,'instrument'=>'quarterly_update_survey','params'=>'[update_d]'),
                3=>array('status'=>1,'instrument'=>'project_update_survey','params'=>'[project_update_survey]'),
                4=>array('status'=>1,'instrument'=>'outputs','params'=>'[outputs]')
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
            17=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
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
        if(preg_match("/vanderbilt.edu/i", SERVER_NAME)){
            #Other institutions
            define("ENVIRONMENT", "PROD");
        }else if (SERVER_NAME == "redcap.vanderbilt.edu") {
            define("ENVIRONMENT", "PROD");
        }else  if (SERVER_NAME == "redcaptest.vanderbilt.edu") {
            define("ENVIRONMENT", "TEST");
        }else {
            define("ENVIRONMENT", "DEV");
        }
    }
}
