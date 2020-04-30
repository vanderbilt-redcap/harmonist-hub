<?php
/**
 * Function that returns the info array from a specific project
 * @param $project, the project id
 * @param $info_array, array that contains the conditionals
 * @param string $type, if its single or a multidimensional array
 * @return array, the info array
 */
function getProjectInfoArray($records){
    $array = array();
    foreach ($records as $event) {
        foreach ($event as $data) {
            array_push($array,$data);
        }
    }

    return $array;
}
function getCrypt($string, $action = 'e',$secret_key="",$secret_iv="" ) {
    $output = false;
    $encrypt_method = "AES-256-CBC";
    $key = hash( 'sha256', $secret_key );
    $iv = substr( hash( 'sha256', $secret_iv ), 0, 16 );

    if( $action == 'e' ) {
        $output = base64_encode( openssl_encrypt( $string, $encrypt_method, $key, 0, $iv ) );
    }
    else if( $action == 'd' ){
        $output = openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv );
    }

    return $output;
}

function loadImg($imgEdoc,$secret_key,$secret_iv,$default,$option=""){
    $img = $default;
    if($imgEdoc != ''){
        $sql = "SELECT stored_name,doc_name,doc_size FROM redcap_edocs_metadata WHERE doc_id='" . db_escape($imgEdoc)."'";
        $q = db_query($sql);

        while ($row = db_fetch_assoc($q)) {
            if($option == 'pdf'){
                $img = EDOC_PATH.$row['stored_name'];
            }else{
                $img = 'downloadFile.php?code='.getCrypt("sname=".$row['stored_name']."&file=". urlencode($row['doc_name']),'e',$secret_key,$secret_iv);
            }
        }
    }
    return $img;
}

function getFile($module,$edoc, $type){
    $file = "#";
    if($edoc != ""){
        $q = $module->query("SELECT stored_name,doc_name,doc_size,mime_type FROM redcap_edocs_metadata WHERE doc_id=?",[$edoc]);
        while ($row = $q->fetch_assoc()) {
            $url = 'downloadFile.php?sname=' . $row['stored_name'] . '&file=' . urlencode($row['doc_name']);
            $base64 = base64_encode(file_get_contents(EDOC_PATH.$row['stored_name']));
            if($type == "img"){
                $file = '<br/><div class="inside-panel-content"><img src="data:'.$row['mime_type'].';base64,' . $base64. '" style="display: block; margin: 0 auto;"></div>';
            }else if($type == "logo"){
                $file = '<img src="data:'.$row['mime_type'].';base64,' . $base64. '" style="padding-bottom: 30px;width: 450px;">';
            }else if($type == "imgpdf"){
                $file = '<div style="max-width: 450px;height: 500px;"><img src="data:'.$row['mime_type'].';base64,' . $base64. '" style="display: block; margin: 0 auto;width:450px;height: 450px;"></div>';
            }else if($type == "url") {
                $file = $module->getUrl($url);
            }else{
                $file = '<br/><div class="inside-panel-content"><a href="'.$module->getUrl($url,true).'" target="_blank"><span class="fa fa-file-o"></span> ' . $row['doc_name'] . '</a></div>';
            }
        }
    }
    return $file;
}

/**
 * function that returns the link to the file with the designed icon
 * @param $edoc
 * @return string
 */
function getFileLink($module,$edoc, $option, $outer="",$secret_key,$secret_iv,$user,$lid){
    $file_row = "";
    if($edoc != "" and is_numeric($edoc)){
        $file_row = '';
        $q = $module->query("SELECT stored_name,doc_name,doc_size,file_extension FROM redcap_edocs_metadata WHERE doc_id=?",[$edoc]);
        while ($row = $q->fetch_assoc()) {
            $name = urlencode($row['doc_name']);
            if($outer == 0){
                $file_url = APP_PATH_PLUGIN."/downloadFile.php?code=".getCrypt("sname=".$row['stored_name']."&file=". $name."&edoc=".$edoc."&pid=".$user."&id=".$lid,'e',$secret_key,$secret_iv);
            }else{
                $file_url = "downloadFile.php?code=".getCrypt("sname=".$row['stored_name']."&file=". $name."&edoc=".$edoc."&pid=".$user."&id=".$lid,'e',$secret_key,$secret_iv);
            }

            if($option == ''){
                $icon = getFaIconFile($row['file_extension']);
                $file_row = "<i class='fa ".$icon."' aria-hidden='true'></i> <a href='".$file_url."' target='_blank'>".$row['doc_name']."</a>";
            }else{
                $file_row = "<a href='".$file_url."' target='_blank' title='".$row['doc_name']."'>".getFaIconFile($row['file_extension'])."</a>";
            }
        }
    }
    return $file_row;
}

function getFaIconFile($file_extension){
    $icon = "fa-file-o";
    if(strtolower($file_extension) == '.pdf' || strtolower($file_extension) == 'pdf'){
        $icon = "fa-file-pdf-o";
    }else if(strtolower($file_extension) == '.doc' || strtolower($file_extension) == 'doc'){
        $icon = "fa-file-word-o";
    }else if(strtolower($file_extension) == '.pptx' || strtolower($file_extension) == 'pptx'){
        $icon = "fa-file-powerpoint-o";
    }else if(strtolower($file_extension) == '.xlsx' || strtolower($file_extension) == 'xlsx'){
        $icon = "fa-file-excel-o";
    }

    return "<i class='fa ".$icon."' aria-hidden='true'></i> ";
}

function getRandomIdentifier($length = 6) {
    $output = "";
    $startNum = pow(32,5) + 1;
    $endNum = pow(32,6);
    while($length > 0) {

        # Generate a number between 32^5 and 32^6, then convert to a 6 digit string
        $randNum = mt_rand($startNum,$endNum);
        $randAlphaNum = numberToBase($randNum,32);

        if($length >= 6) {
            $output .= $randAlphaNum;
        }
        else {
            $output .= substr($randAlphaNum,0,$length);
        }
        $length -= 6;
    }

    return $output;
}

function numberToBase($number, $base) {
    $newString = "";
    while($number > 0) {
        $lastDigit = $number % $base;
        $newString = convertDigit($lastDigit, $base).$newString;
        $number -= $lastDigit;
        $number /= $base;
    }

    return $newString;
}

function convertDigit($number, $base) {
    if($base > 192) {
        chr($number);
    }
    else if($base == 32) {
        $stringArray = "ABCDEFGHJLKMNPQRSTUVWXYZ23456789";

        return substr($stringArray,$number,1);
    }
    else {
        if($number < 192) {
            return chr($number + 32);
        }
        else {
            return "";
        }
    }
}

function getPeopleName($people_id,$option=""){
    if(!empty($people_id)){
        $recordsPeople = \REDCap::getData(IEDEA_PEOPLE, 'array', array('record_id' => $people_id));
        $people = getProjectInfoArray($recordsPeople)[0];
        $name = trim($people['firstname'].' '.$people['lastname']);
        if($option == "email"){
            $name = '<a href="mailto:'.$people['email'].'">'.trim($people['firstname'].' '.$people['lastname']).'</a>';
        }
        return $name;
    }
    return "";
}

function checkAndUpdatJSONCopyProject($module, $type){
    if(ENVIRONMENT == "DEV"){
        $qtype = $this->query("SELECT MAX(record) as record FROM redcap_data WHERE project_id=? AND field_name=? and value=? order by record",[IEDEA_JSONCOPY,'type',$type]);
    }else{
        $qtype = $this->query("SELECT MAX(CAST(record AS Int)) as record FROM redcap_data WHERE project_id=? AND field_name=? and value=? order by record",[IEDEA_JSONCOPY,'type',$type]);
    }

    $rowtype = $qtype->db_fetch_assoc();
    $RecordSetCopy = \REDCap::getData(IEDEA_JSONCOPY, 'array', array('record_id' => $rowtype['record']));
    $jsoncocpy = getProjectInfoArray($RecordSetCopy)[0];

    if($jsoncocpy["jsoncopy_file"] != ""){
        $RecordSetSettings = \REDCap::getData(IEDEA_SETTINGS, 'array', array('record_id' => '1'));
        $settings = getProjectInfoArray($RecordSetSettings)[0];

        $q = $this->query("SELECT stored_name,doc_name,doc_size,mime_type FROM redcap_edocs_metadata WHERE doc_id=?",[$jsoncocpy["jsoncopy_file"]]);

        while ($row = $q->fetch_assoc()) {
            $path = EDOC_PATH.$row['stored_name'];
            $strJsonFileContents = file_get_contents($path);
            $last_array = json_decode($strJsonFileContents, true);
            $array_data = call_user_func_array($module, "createProject".strtoupper($type)."JSON",array($this));
            $new_array = json_decode($array_data['jsonArray'],true);

            $result_prev = array_filter_empty(multi_array_diff($last_array,$new_array));
            $result = array_filter_empty(multi_array_diff($new_array,$last_array));

            if(!empty($result_prev)){
                $id = saveJSONCopy($type, $new_array);

                $link = APP_PATH_WEBROOT_ALL . "DataEntry/record_home.php?pid=" . IEDEA_JSONCOPY . "&arm=1&id=" . $id;

                $subject = "Changes in the DES ".strtoupper($type)." detected ";
                $message = "<div>The following changes have been detected in the DES ".strtoupper($type)." and a new record has been created:</div><br/>".
                    "<div>Last record:". $rowtype['record']."</div><br/>".
                    "<div>To see the record <a href='".$link."'>click here</a></div><br/>".
                    "<ul><pre>".print_r($result,true)."</pre>".
                    "<span style='color:#777'><pre><em>".print_r($result_prev,true)."</em></pre></ul></span>";

                if($settings['hub_subs_0a0b'] != "") {
                    $emails = explode(';', $settings['hub_subs_0a0b']);
                    foreach ($emails as $email) {
                        sendEmail($email, $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], $subject, $message, "");
                    }
                }
            }
        }
    }
}

/**
 * Function that creates a JSON copy of the Harmonist 0A: Data Model
 * @return string , the JSON
 */
function createProject0AJSON($module, $save=""){
    $dataFormat = $module->getChoiceLabels('data_format', IEDEA_DATAMODEL);
    $dataTablerecords = \REDCap::getData(IEDEA_DATAMODEL, 'array');
    $dataTable = getProjectInfoArray($dataTablerecords);
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
                    "description" => $data['description'][$id],
                    "variable_required" => $data['variable_required'][$id][0],
                    "variable_key" => $data['variable_key'][$id][0],
                    "variable_deprecated_d" => $data['variable_deprecated_d'][$id],
                    "variable_replacedby" => $data['variable_replacedby'][$id],
                    "variable_deprecatedinfo" => $data['variable_deprecatedinfo'][$id],
                    "has_codes" => $has_codes,
                    "code_list_ref" => $code_list_ref,
                    "variable_order" => $data['variable_order'][$id],
                    "variable_missingaction" => $data['variable_missingaction'][$id]
                );
                $jsonVarArray['variables'][$data['variable_name'][$id]] = $variables_array;
            }
        }
        $jsonVarArray['table_required'] = $data['table_required'][0];
        $jsonVarArray['table_category'] = $data['table_category'];
        $jsonVarArray['table_order'] = $data['table_order'];
        $jsonArray[trim($data['table_name'])] = $jsonVarArray;
    }

    #we save the new JSON
    if(!empty($jsonArray) && $save == ""){
        saveJSONCopy($module, '0a', $jsonArray);
    }

    return json_encode($jsonArray,JSON_FORCE_OBJECT);
}

/**
 * Function that creates a JSON copy of the Harmonist 0A: Data Model
 * @return string, the JSON
 */
function createProject0BJSON($module, $save=""){
    $dataTablerecords = \REDCap::getData(IEDEA_CODELIST, 'array');
    $dataTable = getProjectInfoArray($dataTablerecords);
    foreach ($dataTable as $data) {
        $jsonArray[$data['record_id']] = array();
        if ($data['code_format'] == '1') {
            $jsonVarContentArray  = array();
            $codeOptions = explode(" | ", $data['code_list']);
            foreach ($codeOptions as $option) {
                list($key, $val) = explode("=", $option);
                $jsonVarContentArray[trim($key)] = trim($val);
            }

        }else if($data['code_format'] == '3'){
            $jsonVarContentArray  = array();
            $csv = parseCSVtoArray($data['code_file']);
            foreach ($csv as $header=>$content){
                if($header != 0){
                    //Convert to UTF-8 to avoid weird characters
                    $value = mb_convert_encoding($content['Definition'], 'UTF-8','HTML-ENTITIES');
                    $jsonVarContentArray[trim($content['Code'])] = trim($value);
                }
            }
        }
        $jsonArray[$data['record_id']]=$jsonVarContentArray;
    }

    #we save the new JSON
    if(!empty($jsonArray) && $save == ""){
        saveJSONCopy($module,'0b', $jsonArray);
    }

    return json_encode($jsonArray,JSON_FORCE_OBJECT);
}

/**
 * Function that saves the JSON copy in the database adding the last version number
 * @param $type, the project  type
 * @param $jsonArray, the json data
 */
function saveJSONCopy($module, $type, $jsonArray){
    #save the project
    $jsoncopy_id = $module->framework->addAutoNumberedRecord(IEDEA_JSONCOPY);
    $jsoncopy = array(array('record_id' => $jsoncopy_id));
    $jsoncopy[0]['jsoncopy'] = json_encode($jsonArray,JSON_FORCE_OBJECT);
    $jsoncopy[0]['type'] = $type;

    #create and save file with json
    $filename = "jsoncopy_file_".$type."_".date("YmdsH").".txt";
    $storedName = date("YmdsH")."_pid".IEDEA_JSONCOPY."_".getRandomIdentifier(6).".txt";

    $file = fopen(EDOC_PATH.$storedName,"wb");
    fwrite($file,json_encode($jsonArray,JSON_FORCE_OBJECT));
    fclose($file);

    $output = file_get_contents(EDOC_PATH.$storedName);
    $filesize = file_put_contents(EDOC_PATH.$storedName, $output);

    $q = $module->query("INSERT INTO redcap_edocs_metadata (stored_name,doc_name,doc_size,file_extension,mime_type,gzipped,project_id,stored_date) VALUES (?,?,?,?,?,?,?,?)",[$storedName,$filename,$filesize,'txt','application/octet-stream','0',IEDEA_JSONCOPY,date('Y-m-d h:i:s')]);
    $docId = db_insert_id();
    $jsoncopy[0]['jsoncopy_file'] = $docId;
    $jsoncopy[0]['json_copy_update_d'] = date("Y-m-d H:i:s");

    #we check the version
    $data = returnJSONCopyVersion($type);
    $lastversion = $data['lastversion'] + 1;
    $jsoncopy[0]['version'] = $lastversion;

    $json = json_encode($jsoncopy);
    $results = \Records::saveData(IEDEA_JSONCOPY, 'json', $json,'normal', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
    \Records::addRecordToRecordListCache(IEDEA_JSONCOPY, $jsoncopy_id,1);

    return $jsoncopy_id;
}

/**
 * Function that returns the version of the JSON Copy project
 * @param $type, the project type
 * @return int|string, the version
 */
function returnJSONCopyVersion($type){
    $records = \REDCap::getData(IEDEA_JSONCOPY, 'array',null,null,null,null,false,false,false,"[type] = ".$type);
    $datatype = getProjectInfoArray($records);
    $lastversion = 0;
    $record_id = 0;
    $data = array();
    if(empty($datatype)){
        $lastversion = '0';
    }else{
        #we get the last version
        foreach($datatype as $data)
        {
            if($data['version'] > $lastversion)
            {
                $lastversion = $data['version'];
                $record_id = $data['record_id'];
            }
        }
    }
    $data['lastversion'] = $lastversion;
    $data['id'] = $record_id;

    return $data;
}
?>