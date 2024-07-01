<?php
namespace Vanderbilt\HarmonistHubExternalModule;


class CopyJSON
{
    public static function hasJsoncopyBeenUpdated($module, $type, $settings, $pidsArray){
        $project_pid = "";
        if($type == '0a'){
            $project_pid = $pidsArray['DATAMODEL'];
        }else if($type == '0b'){
            $project_pid = $pidsArray['CODELIST'];
        }else if($type == '0c'){
            $project_pid = $pidsArray['DATAMODELMETADATA'];
        }
        if($project_pid != "") {
            #Check if the project has information
            $projectData = \REDCap::getData($project_pid, 'json-array',null, array('record_id'))[0];
            if (!empty($projectData)) {
                $jsoncopyPID = $pidsArray['JSONCOPY'];
                $q = $module->query("SELECT MAX(CAST(record as SIGNED)) as record FROM ".\Vanderbilt\HarmonistHubExternalModule\getDataTable($pidsArray[$jsoncopyPID])." WHERE project_id=? AND field_name=? and value=? order by record",[$jsoncopyPID,'type',$type]);
                $last_record = $q->fetch_assoc()['record'];

                $jsoncopy = \REDCap::getData($jsoncopyPID, 'json-array', array('record_id' => $last_record))[0];
                $today = date("Y-m-d");
                if ($jsoncopy["jsoncopy_file"] != "" && strtotime(date("Y-m-d", strtotime($jsoncopy['json_copy_update_d']))) == strtotime($today)) {
                    return true;
                } else {
                    self::checkAndUpdateJSONCopyProject($module, $type, $last_record, $jsoncopy, $settings, $pidsArray);
                    return true;
                }
            }
        }
        return false;
    }

    public static function createAndSaveJSONCron($module, $project_id){
        $dataModelPID = \REDCap::getData($project_id, 'json-array', null,array('project_id'),null,null,false,false,false,"[project_constant]='DATAMODEL'")[0]['project_id'];

        $RecordSetDataModel = \REDCap::getData($dataModelPID, 'array');
        $dataTable = getProjectInfoArrayRepeatingInstruments($RecordSetDataModel);
        $dataFormat = $module->getChoiceLabels('data_format', $dataModelPID);

        foreach ($dataTable as $data) {
            $jsonVarArrayAux = array();
            if($data['table_name'] != "") {
                foreach ($data['variable_order'] as $id => $value) {
                    if ($data['variable_name'][$id] != '') {
                        $url = $module->getUrl("browser.php")."&NOAUTH&pid=" . $_GET['pid'] . '&tid=' . $data['record_id'] . '&vid=' . $id . '&option=variableInfo';
                        $jsonVarArrayAux[trim($data['variable_name'][$id])] = array();
                        $variables_array = array(
                            "instance" => $id,
                            "description" => $data['description'][$id],
                            "description_extra" => $data['description_extra'][$id],
                            "code_list_ref" => $data['code_list_ref'][$id],
                            "data_format" => trim($dataFormat[$data['data_format'][$id]]),
                            "code_text" => $data['code_text'][$id],
                            "variable_link" => $url
                        );

                        $jsonVarArrayAux[$data['variable_name'][$id]] = $variables_array;
                    }
                }
                $jsonVarArray = $jsonVarArrayAux;
                $urltid = $module->getUrl("browser.php")."&NOAUTH&pid=" . $_GET['pid'] . '&tid=' . $data['record_id'] . '&option=variables';
                $jsonVarArray['table_link'] = $urltid;
                $jsonArray[trim($data['table_name'])] = $jsonVarArray;
            }
        }
        #we save the new JSON
        if(!empty($jsonArray)){
            self::saveJSONCopyVarSearch($module, $jsonArray, $project_id);
        }
    }

    public static function saveJSONCopyVarSearch($module, $jsonArray, $project_id){
        $settingsPID = \REDCap::getData($project_id, 'json-array', null,array('project_id'),null,null,false,false,false,"[project_constant]='DATAMODEL'")[0]['project_id'];

        #create and save file with json
        $filename = "jsoncopy_file_variable_search_".date("YmdsH").".txt";
        $storedName = date("YmdsH")."_pid".$settingsPID."_".\Vanderbilt\HarmonistHubExternalModule\getRandomIdentifier(6).".txt";

        $file = fopen(EDOC_PATH.$storedName,"wb");
        fwrite($file,json_encode($jsonArray,JSON_FORCE_OBJECT));
        fclose($file);

        $output = file_get_contents($module->getSafePath(EDOC_PATH.$storedName,EDOC_PATH));
        $filesize = file_put_contents(EDOC_PATH.$storedName, $output);

        //Save document on DB
        $q = $module->query("INSERT INTO redcap_edocs_metadata (stored_name,doc_name,doc_size,file_extension,mime_type,gzipped,project_id,stored_date) VALUES(?,?,?,?,?,?,?,?)",
            [$storedName,$filename,$filesize,'txt','application/octet-stream','0',$settingsPID,date('Y-m-d h:i:s')]);
        $docId = db_insert_id();

        //Add document DB ID to project
        $Proj = new \Project($settingsPID);
        $event_id = $Proj->firstEventId;
        $json = json_encode(array(array('record_id' => 1, 'des_variable_search' => $docId)));
        $results = \Records::saveData($settingsPID, 'json', $json,'normal', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
        \Records::addRecordToRecordListCache($settingsPID, 1,$event_id);
    }

    public static function checkAndUpdateJSONCopyProject($module, $type, $last_record, $jsoncopy, $settings, $pidsArray){
        if($jsoncopy["jsoncopy_file"] != ""){
            $q = $module->query("SELECT stored_name,doc_name,doc_size,mime_type FROM redcap_edocs_metadata WHERE doc_id=?",[$jsoncopy["jsoncopy_file"]]);
            while ($row = $q->fetch_assoc()) {
                $path = $module->getSafePath(EDOC_PATH.$row['stored_name'],EDOC_PATH);
                $output = file_get_contents($path);
                $last_array = json_decode($output,true);
                $new_array = call_user_func_array("self::createProject".strtoupper($type)."JSON",array($module, $pidsArray));
                $result_prev = ArrayFunctions::array_diff_assoc_recursive($last_array,$new_array);
                $result = ArrayFunctions::array_diff_assoc_recursive($new_array,$last_array);
            }
        }else{
            $new_array = call_user_func_array("self::createProject".strtoupper($type)."JSON",array($module, $pidsArray));
            $result = $new_array;
            $result_prev = "";
        }
        if(!empty($result_prev) || !empty($result)) {
            # Save the JSON
            $record = self::saveJSONCopy($type, $new_array, $module, $pidsArray['JSONCOPY'], $last_record);

            if ($last_record == "") {
                $last_record = "<i>None</i>";
            }
            if (!empty($last_record)) {
                $environment = "";
                if (ENVIRONMENT == 'TEST') {
                    $environment = " " . ENVIRONMENT;
                }

                $sender = $settings['accesslink_sender_email'];
                if ($settings['accesslink_sender_email'] == "") {
                    $sender = "noreply.harmonist@vumc.org";
                }

                $link = APP_PATH_WEBROOT_ALL . "DataEntry/record_home.php?pid=" . $pidsArray['JSONCOPY'] . "&arm=1&id=" . $record;
                $subject = "Changes in the DES " . strtoupper($type) . " detected ";
                $message = "<div>The following changes have been detected in the DES " . strtoupper($type) . " and a new record #" . $record . " has been created:</div><br/>" .
                    "<div>Last record: " . $last_record . "</div><br/>" .
                    "<div>To see the record <a href='" . $link . "'>click here</a></div><br/>" .
                    "<ul><pre>" . print_r($result, true) . "</pre>" .
                    "<span style='color:#777'><pre><em>" . print_r($result_prev, true) . "</em></pre></ul></span>";

                if ($settings['hub_subs_0a0b'] != "") {
                    $emails = explode(';', $settings['hub_subs_0a0b']);
                    foreach ($emails as $email) {
                        \REDCap::email($email, $sender, $subject . $environment, $message, "", "", $settings['accesslink_sender_name']);
                    }
                }
            }
        }
        return null;
    }

    /**
     * Function that creates a JSON copy of the Harmonist 0A: Data Model
     * @return string , the JSON
     */
    public static function createProject0AJSON($module, $pidsArray){
        $dataFormat = $module->getChoiceLabels('data_format', $pidsArray['DATAMODEL']);
        $dataTablerecords = \REDCap::getData($pidsArray['DATAMODEL'], 'array');
        $dataTable = ProjectData::getProjectInfoArrayRepeatingInstruments($dataTablerecords, null, "json");
        foreach ($dataTable as $data) {
            $jsonVarArray['variables'] = array();
            foreach ($data['variable_order'] as $id => $value) {
                if($data['variable_name'][$id] != ''){
                    $has_codes = 'N';
                    if($data['has_codes'][$id] == '1')
                        $has_codes = 'Y';

                    $code_list_ref = $data['code_list_ref'][$id];
                    if($data['code_list_ref'][$id] == ''){
                        $code_list_ref = 'NULL';
                    }

                    $jsonVarArray['variables'][trim($data['variable_name'][$id])] = array();
                    $variables_array  = array(
                        "data_format" => trim($dataFormat[$data['data_format'][$id]]),
                        "variable_status" => $data['variable_status'][$id],
                        "description" => htmlentities($data['description'][$id]),
                        "variable_required" => $data['variable_required'][$id][1],
                        "variable_key" => $data['variable_key'][$id][1],
                        "variable_deprecated_d" => $data['variable_deprecated_d'][$id],
                        "variable_replacedby" => htmlentities($data['variable_replacedby'][$id]),
                        "variable_splitdate_m" => htmlentities($data['variable_splitdate_m'][$id]),
                        "variable_splitdate_d" => htmlentities($data['variable_splitdate_d'][$id]),
                        "variable_splitdate_y" => $data['variable_splitdate_y'][$id][1],
                        "variable_deprecatedinfo" => htmlentities($data['variable_deprecatedinfo'][$id]),
                        "has_codes" => $has_codes,
                        "code_list_ref" => $code_list_ref,
                        "variable_order" => $data['variable_order'][$id],
                        "variable_missingaction" => $data['variable_missingaction'][$id][1],
                        "variable_reportcomplete" => $data['variable_reportcomplete'][$id][1],
                        "variable_indexid" => $data['variable_indexid'][$id][1]
                    );
                    $jsonVarArray['variables'][$data['variable_name'][$id]] = $variables_array;
                }
            }
            $jsonVarArray['table_required'] = $data['table_required'][1];
            $jsonVarArray['table_category'] = $data['table_category'];
            $jsonVarArray['table_order'] = $data['table_order'];
            $jsonArray[trim($data['table_name'])] = $jsonVarArray;
        }

        return $jsonArray;
    }

    /**
     * Function that creates a JSON copy of the Harmonist 0B: Code List
     * @return string, the JSON
     */
    public static function createProject0BJSON($module, $pidsArray){
        $dataTable = \REDCap::getData($pidsArray['CODELIST'], 'json-array');
        foreach ($dataTable as $data) {
            $jsonArray[$data['record_id']] = array();
            if ($data['code_format'] == '1') {
                $jsonVarContentArray  = array();
                $codeOptions = explode(" | ", $data['code_list']);
                foreach ($codeOptions as $option) {
                    list($key, $val) = explode("=", htmlentities($option));
                    $jsonVarContentArray[htmlentities(trim($key))] = htmlentities(trim($val));
                }

            }else if($data['code_format'] == '3'){
                $jsonVarContentArray  = array();
                $csv = \Vanderbilt\HarmonistHubExternalModule\parseCSVtoArray($module, $data['code_file']);
                foreach ($csv as $header=>$content){
                    if($header != 0){
                        //Convert to UTF-8 to avoid weird characters
                        $value = mb_convert_encoding(htmlentities($content['Definition']), 'UTF-8','HTML-ENTITIES');
                        $jsonVarContentArray[trim($content['Code'])] = htmlentities(trim($value));
                    }
                }
            }
            $jsonArray[$data['record_id']]=$jsonVarContentArray;
        }

        return $jsonArray;
    }

    /**
     * Function that creates a JSON copy of the Harmonist 0C: Data Model Metadata
     * @return string, the JSON
     */
    public static function createProject0CJSON($module, $pidsArray){
        $dataTable = \REDCap::getData($pidsArray['DATAMODELMETADATA'], 'json-array')[0];
        $jsonArray = array();
        $jsonArray['project_name'] = $dataTable['project_name'];
        $jsonArray['datamodel_name'] = $dataTable['datamodel_name'];
        $jsonArray['datamodel_abbrev'] = $dataTable['datamodel_abbrev'];
        $jsonArray['datamodel_url_y'] = $dataTable['datamodel_url_y'];
        $jsonArray['datamodel_url'] = $dataTable['datamodel_url'];
        $jsonArray['hub_y'] = $dataTable['hub_y'];
        $jsonArray['sd_ext'] = $dataTable['sd_ext'];
        $jsonArray['ed_ext'] = $dataTable['ed_ext'];
        $jsonArray['date_approx_y'] = $dataTable['date_approx_y'];
        $jsonArray['date_approx'] = $dataTable['date_approx'];
        $jsonArray['n_age_groups'] = $dataTable['n_age_groups'];
        $jsonArray['age_1_lower'] = $dataTable['age_1_lower'];
        $jsonArray['age_1_upper'] = $dataTable['age_1_upper'];
        $jsonArray['age_2_lower'] = $dataTable['age_2_lower'];
        $jsonArray['age_2_upper'] = $dataTable['age_2_upper'];
        $jsonArray['age_3_lower'] = $dataTable['age_3_lower'];
        $jsonArray['age_3_upper'] = $dataTable['age_3_upper'];
        $jsonArray['age_4_lower'] = $dataTable['age_4_lower'];
        $jsonArray['age_4_upper'] = $dataTable['age_4_upper'];
        $jsonArray['age_5_lower'] = $dataTable['age_5_lower'];
        $jsonArray['age_5_upper'] = $dataTable['age_5_upper'];
        $jsonArray['age_6_lower'] = $dataTable['age_6_lower'];
        $jsonArray['age_6_upper'] = $dataTable['age_6_upper'];

        $jsonArray = \Vanderbilt\HarmonistHubExternalModule\getTableJsonName($pidsArray['DATAMODEL'], $dataTable['index_tablename'],'index_tablename',$jsonArray);
        $jsonArray = \Vanderbilt\HarmonistHubExternalModule\getTableJsonName($pidsArray['DATAMODEL'], $dataTable['group_tablename'],'group_tablename',$jsonArray);
        $jsonArray = \Vanderbilt\HarmonistHubExternalModule\getTableJsonName($pidsArray['DATAMODEL'], $dataTable['height_table'],'height_table',$jsonArray);

        $jsonArray = \Vanderbilt\HarmonistHubExternalModule\getTableVariableJsonName($pidsArray['DATAMODEL'], $dataTable['patient_id_var'],'patient_id_var',$jsonArray);
        $jsonArray = \Vanderbilt\HarmonistHubExternalModule\getTableVariableJsonName($pidsArray['DATAMODEL'], $dataTable['default_group_var'],'default_group_var',$jsonArray);
        $jsonArray = \Vanderbilt\HarmonistHubExternalModule\getTableVariableJsonName($pidsArray['DATAMODEL'], $dataTable['birthdate_var'],'birthdate_var',$jsonArray);
        $jsonArray = \Vanderbilt\HarmonistHubExternalModule\getTableVariableJsonName($pidsArray['DATAMODEL'], $dataTable['death_date_var'],'death_date_var',$jsonArray);
        $jsonArray = \Vanderbilt\HarmonistHubExternalModule\getTableVariableJsonName($pidsArray['DATAMODEL'], $dataTable['age_date_var'],'age_date_var',$jsonArray);
        $jsonArray = \Vanderbilt\HarmonistHubExternalModule\getTableVariableJsonName($pidsArray['DATAMODEL'], $dataTable['enrol_date_var'],'enrol_date_var',$jsonArray);
        $jsonArray = \Vanderbilt\HarmonistHubExternalModule\getTableVariableJsonName($pidsArray['DATAMODEL'], $dataTable['height_var'],'height_var',$jsonArray);
        $jsonArray = \Vanderbilt\HarmonistHubExternalModule\getTableVariableJsonName($pidsArray['DATAMODEL'], $dataTable['height_date'],'height_date',$jsonArray);
        $jsonArray = \Vanderbilt\HarmonistHubExternalModule\getTableVariableJsonName($pidsArray['DATAMODEL'], $dataTable['height_units'],'height_units',$jsonArray);

        #save files data
        $jsonArray['project_logo_100_40'] = base64_encode(file_get_contents(\Vanderbilt\HarmonistHubExternalModule\getFile($module, $pidsArray['PROJECTS'], $dataTable['project_logo_100_40'],'pdf')));
        $jsonArray['project_logo_50_20'] = base64_encode(file_get_contents(\Vanderbilt\HarmonistHubExternalModule\getFile($module, $pidsArray['PROJECTS'], $dataTable['project_logo_50_20'],'pdf')));
        $jsonArray['sample_dataset'] = base64_encode(file_get_contents(\Vanderbilt\HarmonistHubExternalModule\getFile($module, $pidsArray['PROJECTS'], $dataTable['sample_dataset'],'pdf')));

        return $jsonArray;
    }

    /**
     * Function that saves the JSON copy in the database adding the last version number
     */
    public static function saveJSONCopy($type, $jsonArray, $module, $jsoncopyPID, $last_record){
        #create and save file with json
        $filename = "jsoncopy_file_".$type."_".date("YmdsH").".txt";
        $storedName = date("YmdsH")."_pid".$jsoncopyPID."_".getRandomIdentifier(6).".txt";

        $file = fopen(EDOC_PATH.$storedName,"wb");
        fwrite($file,json_encode($jsonArray,JSON_FORCE_OBJECT));
        fclose($file);

        $output = file_get_contents(EDOC_PATH.$storedName);
        $filesize = file_put_contents(EDOC_PATH.$storedName, $output);
        //Save document on DB
        $q = $module->query("INSERT INTO redcap_edocs_metadata (stored_name,doc_name,doc_size,file_extension,mime_type,gzipped,project_id,stored_date) VALUES(?,?,?,?,?,?,?,?)",
            [$storedName,$filename,$filesize,'txt','application/octet-stream','0',$jsoncopyPID,date('Y-m-d h:i:s')]);
        $docId = db_insert_id();

        #we check the version
        $lastversion = self::returnJSONCopyVersion($type, $jsoncopyPID, $last_record);
        #save the project
        $Proj = new \Project($jsoncopyPID);
        $event_id = $Proj->firstEventId;
        $record_id = $module->framework->addAutoNumberedRecord($jsoncopyPID);
        $json = json_encode(array(array('record_id'=>$record_id, 'type'=>$type,'jsoncopy_file'=>$docId,'jsoncopy' => json_encode($jsonArray,JSON_FORCE_OBJECT),'json_copy_update_d'=>date("Y-m-d H:i:s"),"version" => $lastversion)));
        $results = \REDCap::saveData($jsoncopyPID, 'json', $json,'normal', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);

        return $record_id;
    }

    /**
     * Function that returns the version of the JSON Copy project
     * @param $type, the project type
     * @return int|string, the version
     */
    public static function returnJSONCopyVersion($type, $jsoncopyID, $record_id){
        $datatype = \REDCap::getData($jsoncopyID, 'json-array',  array("record" => $record_id),array('version'),null,null,false,false,false,"[type]='".$type."'")[0];
        $data = array();
        if(empty($datatype)){
            $lastversion = '0';
        }else{
            #we get the last version
            $lastversion = $datatype['version'];
        }
        $lastversion = $lastversion + 1;

        return $lastversion;
    }
}
?>