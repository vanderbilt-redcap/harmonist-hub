<?php
use Carbon\Carbon;
require_once 'vendor/autoload.php';

function array_sort_by_column(&$arr, $col, $dir = SORT_ASC) {
    $sort_col = array();
    foreach ($arr as $key=> $row) {
        $sort_col[$key] = $row[$col];
    }
    array_multisort($sort_col, $dir, $arr);
}

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

function getProjectInfoArrayRepeatingInstruments($records,$filterLogic=null){
    $array = array();
    $index=0;
    foreach ($records as $record=>$record_array) {
        $found = false;
        foreach ($record_array as $event=>$data) {
            if($event == 'repeat_instances'){
                foreach ($data as $eventarray){
                    $datarepeat = array();
                    foreach ($eventarray as $instrument=>$instrumentdata){
                        $count = 0;
                        foreach ($instrumentdata as $instance=>$instancedata){
                            foreach ($instancedata as $field_name=>$value){
                                if(!array_key_exists($field_name,$array[$index])){
                                    $array[$index][$field_name] = array();
                                }

                                if($value != "" ){
                                    $datarepeat[$field_name][$instance] = $value;

                                    foreach ($filterLogic as $filterkey => $filtervalue){
                                        if($value == $filtervalue && $field_name == $filterkey){
                                            $found = true;
                                        }
                                    }
                                }

                            }
                            $count++;
                        }
                    }
                    foreach ($datarepeat as $field=>$datai){
                        if($array[$index][$field] == ""){
                            $array[$index][$field] = $datarepeat[$field];
                        }
                    }
                }
            }else{
                $array[$index] = $data;
                foreach ($data as $fname=>$fvalue) {
                    foreach ($filterLogic as $filterkey => $filtervalue){
                        if($fvalue == $filtervalue && $fname == $filterkey){
                            $found = true;
                        }
                    }
                }
            }
        }
        if(!$found && $filterLogic != null){
            unset($array[$index]);
        }

        $index++;
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
            }else if($type == "src"){
                $file = 'data:'.$row['mime_type'].';base64,' . $base64;
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

/**
 * Function that checks if the token is correct or not
 * @param $token
 * @return bool
 */
function isTokenCorrect($token){
    $projectPeople = \REDCap::getData(IEDEA_PEOPLE, 'array', null,null,null,null,false,false,false,"[access_token] = '".$token."'");
    $people = getProjectInfoArray($projectPeople)[0];
    if(!empty($people)){
        if(strtotime($people['token_expiration_d']) > strtotime(date('Y-m-d'))){
            return true;
        }
    }
    return false;
}

function getToken($userid){
    $projectPeople = \REDCap::getData(IEDEA_PEOPLE, 'array', null,null,null,null,false,false,false,"[redcap_name] = '".$userid."'");
    $people = getProjectInfoArray($projectPeople)[0];
    if(!empty($people)){
        return $people['access_token'];
    }
}

function getReqAssocConceptLink($module,$assoc_concept, $option=""){
    if(!empty($assoc_concept)){
        $RecordSetConceptSheets = \REDCap::getData(IEDEA_HARMONIST, 'array', array('record_id' => $assoc_concept));
        $concepts = getProjectInfoArray($RecordSetConceptSheets)[0];
        $concept_sheet = $concepts['concept_id'];
        $concept_title = $concepts['concept_title'];
        if($option == '1'){
            return '<a href="'.$module->getUrl('index.php?pid='.IEDEA_PROJECTS.'&option=ttl&record='.$assoc_concept).'" target="_blank">'.$concept_sheet.', '.$concept_title.'</a>';
        }else{
            return '<a href="'.$module->getUrl('index.php?pid='.IEDEA_PROJECTS.'&option=ttl&record='.$assoc_concept).'" target="_blank">'.$concept_sheet.'</a>';
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
    $results = \Records::saveData(IEDEA_JSONCOPY, 'json', $json,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
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

/**
 * Function that returns the number of open requests there are
 * @param $request
 * @param $instance
 * @return int
 */
function numberOfOpenRequest($request,$instance){
    $number=0;
    foreach ($request as $req) {
        if ($instance == 1) {
            $instance = '';
        }
        if ($req['finalize_y'] == "" && ($req['region_response_status'][$instance] == 0 || $req['region_response_status'][$instance] == 1)) {
            $number++;
        }
    }
    return $number;
}


/**
 * Function that returns the number of admin requests there are
 * @param $request
 * @param $instance
 * @return int
 */
function numberOfAdminRequest($request){
    $number=0;
    foreach ($request as $req) {
        if ($req['approval_y'] == '' || $req['approval_y'] == null) {
            $number++;
        }
    }
    return $number;
}

function hasUserPermissions($permissionlist, $value){
    $harmonist_perm = false;
    foreach ($permissionlist as $h_perm){
        if($h_perm == $value){
            $harmonist_perm = true;
        }
    }
    return $harmonist_perm;
}

/**
 * Function that returns the HTML header for the requests
 * @param $regions
 * @return string
 */
function getRequestHeader($regions, $person_region, $vote_grid, $option, $type=""){

    $header_colgroup = '<colgroup>
                    <col>
                    <col>
                    <col>
                    <col>';

    $due_date_style = 'request_grid_dued';
    if($option == '1' || $option == '2') {
        $due_date_style = 'request_grid_dued_home';
    }

    $header = '<thead>'.
        '<tr>'.
        '<th class="'.$due_date_style.' sorted_class" data-sorted="true" data-sorted-direction="descending">Due Date</th>'.
        '<th class="request_grid_reqtype sorted_class"><span style="display:block">Request</span><span>Type</span></th>';
    if($option == '0' || $type == "archive") {
        $header .= '<th class="request_grid_submittedby sorted_class"><span style="display:block">Submitted</span><span>By</span></th>';
    }
    $header_region = '';
    $count_regions = 0;
    $total_regions = count($regions);

    if($option != '2'){
        $small_screen_class = 'hidden-sm hidden-xs';
        if ($vote_grid == '2') {
            $RecordSetRegions = \REDCap::getData(IEDEA_REGIONS, 'array', array('record_id' => $person_region));
            $my_region = getProjectInfoArray($RecordSetRegions)[0]['region_code'];
            $header_region .= '<th class="request_grid_icon ' . $small_screen_class . '" style="width:150px" data-sortable="false">' . $my_region . '</th>';
        } else {
            foreach ($regions as $region) {
                if ($vote_grid == "0") {
                    $instance = $person_region;
                } else {
                    $instance = $region['record_id'];
                }

                $count_regions++;
                if ($person_region == $instance) {
                    $small_screen_class = '';
                } else {
                    $small_screen_class = 'hidden-sm hidden-xs';
                }

                if ($vote_grid == "0" && $person_region == $region['record_id']) {
                    $header_region .= '<th class="request_grid_icon ' . $small_screen_class . '" data-sortable="false">' . $region['region_code'] . '</th>';
                    if ($instance == $person_region && $vote_grid != "0" && $option == '0') {
                        $header_colgroup .= '<col class="active">';
                    } else {
                        $header_colgroup .= '<col>';
                    }
                    break;
                } else if ($vote_grid == "1") {
                    $header_region .= '<th class="request_grid_icon ' . $small_screen_class . '" data-sortable="false">' . $region['region_code'] . '</th>';
                    if ($instance == $person_region && $vote_grid != "0" && $option == '0') {
                        $header_colgroup .= '<col class="active">';
                    } else {
                        $header_colgroup .= '<col>';
                    }
                }
            }
        }
    }

    $title_width = 699-($count_regions*39);
    $header .= '<th class="sorted_class hidden-xs" style="width:'.$title_width.'px">Title</th>'.$header_region;

    $header_colgroup .= '<col></colgroup>';

    if($option == '2'){
        $header .= '<th class="request_grid_actions" data-sortable="false">Status</th>';
    }

    if($option == "1"){
        $header .= '<th class="request_grid_actions" data-sortable="false">Final Status</th></tr></thead>';
    }else{
        $header .= '<th class="request_grid_actions" data-sortable="false">Actions</th></tr></thead>';
    }

    return $header_colgroup.$header;
}

/**
 * Function that returns the HTML header for the archived requests
 * @param $regions
 * @return string
 */
function getArchiveHeader($region){

    $header_colgroup = '<colgroup>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    </colgroup>';

    $header = '<thead>'.
        '<tr>'.
        '<th class="archive_grid_dued sorted_class" data-sorted="true" data-sorted-direction="descending">Due Date</th>'.
        '<th class="archive_grid_reqtype sorted_class"><span style="display:block">Request</span><span>Type</span></th>'.
        '<th class="archive_grid_submittedby sorted_class"><span style="display:block">Submitted</span><span>By</span></th>'.
        '<th class="archive_grid_homeregion sorted_class"><span style="display:block">Home</span><span>Region</span></th>'.
        '<th class="archive_grid_title sorted_class">Title</th>'.
        '<th class="archive_grid_icon" data-sortable="false">' . $region . '</th>'.
        '<th class="archive_grid_actions" data-sortable="false">Actions</th></tr></thead>';

    return $header_colgroup.$header;
}

/**
 * Function that checks if we show an open request
 * @param $regions
 * @param $req
 * @param $current_user_region
 * @return bool
 */
function showOpenRequest($req,$instance){
    if ($instance == 1) {
        $instance = '';
    }
    if ($req['region_response_status'][$instance] != 2 && (!array_key_exists('finalize_y', $req) || $req['finalize_y'] == "")) {
        return true;
    }
    return false;
}

/**
 * Function that checks if we show a completed request
 * @param $settings
 * @param $regions
 * @param $req
 * @param $current_user_region
 * @return bool
 */
function showClosedRequest($settings,$req,$instance){
    if ($instance == 1) {
        $instance = '';
    }
    if (($req['region_response_status'][$instance] == 2 || $req['finalize_y'] != "") && !empty($req['due_d'])) {
        $extra_days = ' + ' . $settings['pastrequest_dur'] . " days";
        $due_date_time = date('Y-m-d', strtotime($req['due_d'] . $extra_days));
        $today = date('Y-m-d');
        if (strtotime($due_date_time) > strtotime($today)){
            return true;
        }
    }
    return false;
}

/**
 * Function that checks if we show a pending request
 * @param $request_id
 * @param $instance
 * @return bool
 */
function showPendingRequest($request_id, $req, $current_region){
    $RecordSetComment = \REDCap::getData(IEDEA_COMMENTSVOTES, 'array', array('request_id' => $request_id));
    $comments = getProjectInfoArray($RecordSetComment);
    foreach ($comments as $comment){
        if($comment['vote_now'] == "0" && $comment['response_region'] == $current_region && (!array_key_exists('finalize_y', $req) || $req['finalize_y'] == "")){
            return true;
        }
    }

    return false;
}

function hideRequestForNonVoters($settings,$req,$person_region){
    if (!empty($req['due_d'])) {
        $extra_days = ' + ' . $settings['pastrequest_dur'] . " days";
        $due_date_time = date('Y-m-d', strtotime($req['due_d'] . $extra_days));
        $today = date('Y-m-d');

        if($person_region['voteregion_y'] == "0" && strtotime($due_date_time) < strtotime($today)) {
            return true;
        }
    }
    return false;
}

/**
 * Function that returns the HTML code of the private votes
 * @param $region_response_status
 * @param $small_screen_class
 * @return string
 */
function getPrivateVotesHTML($region_response_status,$small_screen_class){
    $current_req_region ="";
    if ($region_response_status == 0) {
        //Not started
        $current_req_region .= '<td class="' . $small_screen_class . ' moz" style="text-align: center;"><span class="label label-default_light" title="Not Started"><i class="fa fa-times text-default_light" aria-hidden="true"></i></span></td>';
    } else if ($region_response_status == 1) {
        //In Progress
        $current_req_region .= '<td class="' . $small_screen_class . ' moz" style="text-align: center;"><span class="label label-warning" title="In Progress"><i class="fa fa-wrench" aria-hidden="true"></i></span></td>';
    } else if ($region_response_status == 2) {
        //Complete
        $current_req_region .= '<td class="' . $small_screen_class . ' moz" style="text-align: center;"><span class="label label-info" title="Complete"><i class="fa fa-check" aria-hidden="true"></i></span></td>';
    } else {
        $current_req_region .= '<td class="' . $small_screen_class . ' moz" style="text-align: center;"><span class="label label-default" title="Complete"><i class="fa fa-times" aria-hidden="true"></i></span></td>';
    }
    return $current_req_region;
}

function getMixVotesHTML($region_vote_status,$region_response_status,$region_id,$req,$small_screen_class){
    $RecordSetComments = \REDCap::getData(IEDEA_COMMENTSVOTES, 'array', array("request_id" => $req['request_id']),null,null,null,false,false,false,"[response_region] ='".$region_id."'");
    $votes = getProjectInfoArray($RecordSetComments);
    $mix = false;
    foreach ($votes as $vote){
        if($region_vote_status != $vote['pi_vote'] && array_key_exists('pi_vote',$vote) && array_key_exists('region_vote_status',$req) && $region_vote_status != ""){
            $mix = true;
        }
    }
    $current_req_region = "";
    if($mix){
        $current_req_region = '<td class="' . $small_screen_class . ' moz" style="text-align: center;"><span class="label label-default" title="Mix"><i class="fa fa-clone" aria-hidden="true"></i></span></td>';
    }else if($region_vote_status != "") {
        if ($region_vote_status == "0") {
            $current_req_region = '<td class="' . $small_screen_class . ' moz" style="text-align: center;"><span class="label label-notapproved" title="Not Approved"><i class="fa fa-times" aria-hidden="true"></i></span></td>';
        } else if ($region_vote_status == "1") {
            $current_req_region = '<td class="' . $small_screen_class . ' moz" style="text-align: center;"><span class="label label-approved" title="Approved"><i class="fa fa-check" aria-hidden="true"></i></span></td>';
        } else if ($region_vote_status == "9") {
            //Abstained
            $current_req_region .= '<td class="' . $small_screen_class . ' moz" style="text-align: center;"><span class="label label-default" title="Abstained"><i class="fa fa-ban" aria-hidden="true"></i></span></td>';
        } else {
            $current_req_region = '<td class="' . $small_screen_class . ' moz" style="text-align: center;"><span class="label label-default_light" title="Not Started"><i class="fa fa-times text-default_light" aria-hidden="true"></i></span></td>';
        }
    } else if ($region_response_status == "1") {
        //In Progress
        $current_req_region .= '<td class="' . $small_screen_class . ' moz" style="text-align: center;"><span class="label label-warning" title="In Progress"><i class="fa fa-wrench" aria-hidden="true"></i></span></td>';
    }else {
        $current_req_region = '<td class="' . $small_screen_class . ' moz" style="text-align: center;"><span class="label label-default_light" title="Not Started"><i class="fa fa-times text-default_light" aria-hidden="true"></i></span></td>';
    }
    return $current_req_region;
}

/**
 * Function that returns the HTML code of the public votes
 * @param $small_screen_class
 * @param $vote
 * @return string
 */
function getPublicVotesHTML($response_status,$vote,$small_screen_class){
    $current_req_region = "";
    if ($vote == "1") {
        //Approved
        $current_req_region .= '<td class="' . $small_screen_class . ' moz" style="text-align: center;"><span class="label label-approved" title="Approved"><i class="fa fa-check" aria-hidden="true"></i></span></td>';
    } else if ($vote == "0") {
        //Not Approved
        $current_req_region .= '<td class="' . $small_screen_class . ' moz" style="text-align: center;"><span class="label label-notapproved" title="Not Approved"><i class="fa fa-times" aria-hidden="true"></i></span></td>';
    } else if ($vote == "9") {
        //Abstained
        $current_req_region .= '<td class="' . $small_screen_class . ' moz" style="text-align: center;"><span class="label label-default" title="Abstained"><i class="fa fa-ban" aria-hidden="true"></i></span></td>';
    } else {
        $current_req_region .= '<td class="' . $small_screen_class . ' moz" style="text-align: center;"><span class="label label-default" title="Abstained"><i class="fa fa-ban" aria-hidden="true"></i></span></td>';
    }

    return $current_req_region;
}

/**
 * Function that returns the table row of either the completed or open request
 * @param $req
 * @param $regions
 * @param $request_type_label
 * @param $current_user
 * @param $option
 * @return string
 */
function getRequestHTML($module,$req,$regions,$request_type_label,$current_user, $option, $vote_visibility, $vote_grid, $req_type){
    $class = "nowrap";

    if($option == 0){
        if (strtotime($req['due_d']) < strtotime(date('Y-m-d'))){
            $class = "overdue";
        }
    }

    $width = "";
    $button_text = "Respond";
    $button_icon = "fa-share";
    if($req_type == 'home'){
        $width = array(0 => "width='70px'", 1 => "width='70px'", 2 => "width='70px'");
        $button_text = "View";
        $button_icon = "fa-eye";
    }else  if($req_type == 'archive'){
        $width = array(0 => "width='80px'", 1 => "width='150px'", 2 => "width='590px'");
        $button_text = "View";
        $button_icon = "fa-eye";
    }

    $current_req = '<tr>
                    <td '.$width[0].'><span class="'.$class.'">'.$req['due_d'].'</span></td>
                    <td '.$width[1].'>
                        <strong>'.$request_type_label[$req['request_type']].'</strong><br>';

    $current_req .= getReqAssocConceptLink($module,$req['assoc_concept'],"");

    if($req_type != 'home'){
        $current_req .= '</td>
                    <td>'.$req['contact_name'].'</td>';
    }

    $text = "";
    if ($req['revision_counter_total'] != '') {
        $RecordSetComments = \REDCap::getData(IEDEA_COMMENTSVOTES, 'array', array('request_id' => $req['request_id']),null,null,null,false,false,false,"[revision_counter] =".$req['revision_counter_total']);
        $comment = getProjectInfoArray($RecordSetComments)[0];

        $comment_time ="";
        if(!empty($comment['responsecomplete_ts'])){
            $dateComment = new DateTime($comment['responsecomplete_ts']);
            $dateComment->modify("+1 hours");
            $comment_time = ": ".$dateComment->format("Y-m-d H:i");
        }
        $text = "<div class='request_revision_text'>revision <span style='font-size:12px'>".$req['revision_counter_total'].$comment_time."</span></div>";
    }

    $type = '';
    if($option == '2') {
        $type = '&type=r';
    }
    $current_req .= '<td '.$width[2].' class="hidden-xs"><a href="'.$module->getUrl('index.php?option=hub&record=' . $req['request_id'] . $type).'">'.$text.$req['request_title'].'</a></td>';

    $current_req_region = '';
    if($option != '2') {
        if($vote_grid == '2'){
            $RecordSetMyRegion = \REDCap::getData(IEDEA_REGIONS, 'array', array('record_id' => $current_user['person_region']));
            $my_region = getProjectInfoArray($RecordSetMyRegion)[0];
            $current_req_region = getRequestVoteIcon($current_req_region,$vote_grid,$current_user['person_region'],$my_region['record_id'],$vote_visibility,$req,$current_user);

        }else{
            foreach ($regions as $region){
                $current_req_region = getRequestVoteIcon($current_req_region,$vote_grid,$current_user['person_region'],$region['record_id'],$vote_visibility,$req,$current_user);
                if($vote_grid == "0"){
                    break;
                }
            }
        }

        $current_req .= $current_req_region;

        $view_all_votes = "";
        if ($vote_grid == '2') {
            $url = $module->getUrl("hub/hub_requests_view_all_votes_AJAX.php");
            $view_all_votes = '<div><a href="#" onclick="viewAllVotes(' . $req['request_id'] . ',\''.$url.'\');" class="btn btn-success btn-xs" style="margin-bottom: 7px;"><span class="fa fa-folder-open"></span> All votes</a></div>';
        }
        if ($vote_visibility == '3') {
            $url = $module->getUrl("hub/hub_requests_view_mixed_votes_AJAX.php");
            $view_all_votes .= '<div><a href="#" onclick="viewMixedVotes(' . $req['request_id'] . ',' . $current_user['person_region'].',\''.$url.'\');" class="btn btn-success btn-xs" style="margin-bottom: 7px;"><span class="fa fa-folder-open"></span> Vote Details</a></div>';
        }
        if ($option == 0) {
            if ($req_type == 'archive' || $req_type == 'home') {
                $current_req .= '<td ' . $width[0] . '>';
                if ($req['finalize_y'] != "") {
                    $request_finalize_y_label = $module->getChoiceLabels('finalize_y', IEDEA_RMANAGER);
                    $current_req .= $request_finalize_y_label[$req['finalize_y']] . "<br><span style='font-size: 12px'>" . $req['final_d'] . "</span>";
                } else {
                    $current_req .= "<em>None</em>";
                }
            } else {
                if ($req_type != 'home') {
                    if ($current_user['harmonist_regperm'] == 1) {
                        $current_req .= '<td ' . $width[0] . '>' . $view_all_votes . '<div><a href="'.$module->getUrl('index.php?option=hub&record=' . $req['request_id']) . '" class="btn btn-primary btn-xs"><span class="fa fa-eye"></span> View</a></div>';
                    } else {
                        $current_req .= '<td ' . $width[0] . '>' . $view_all_votes . '<div><a href="'.$module->getUrl('index.php?option=hub&record=' . $req['request_id']) . '" class="btn btn-primary btn-xs"><span class="fa ' . $button_icon . '"></span> ' . $button_text . '</a></div>';
                    }
                }
            }
        } else {
            $current_req .= '<td ' . $width[0] . '>' . $view_all_votes . '<div><a href="'.$module->getUrl('index.php?option=hub&record=' . $req['request_id']) . '" class="btn btn-default btn-xs actionbutton"><span class="fa fa-eye"></span> View/Edit</a></div>';
        }
    }else {
        if ($req['reviewer_id'] != ''){
            $reviewer = getPeopleName(array('record_id' => $req['reviewer_id']),"");
            if ($reviewer != '') {
                $reviewer = ' by ' . $reviewer;
            }
        }else{
            $reviewer = '';
        }
        if($req['approval_y'] == '0'){
            $current_req .= '<td width="150px"><strong>Rejected</strong>'.$reviewer.'</td>';
        }else if($req['approval_y'] == '9') {
            $current_req .= '<td width="150px"><strong>Deactivated</strong>'.$reviewer.'</td>';
        }

        $passthru_link = $module->resetSurveyAndGetCodes(IEDEA_RMANAGER, $req['request_id'], "request", "");
        $survey_link = $module->getUrl('surveyPassthru.php?&surveyLink='.APP_PATH_SURVEY_FULL . "?s=".$passthru_link['hash']);
        $current_req .=  '<td><div><a href="'.$survey_link.'" class="btn btn-primary btn-xs actionbutton" target="_blank"><i class="fa fa-eye fa-fw" aria-hidden="true"></i> Check Submission</a></div>';

        $passthru_link = $module->resetSurveyAndGetCodes(IEDEA_RMANAGER, $req['request_id'], "admin_review", "");
        $survey_link = $module->getUrl('surveyPassthru.php?&surveyLink='.APP_PATH_SURVEY_FULL . "?s=".$passthru_link['hash']);

        $current_req .=  '<div><a href="#" onclick="editIframeModal(\'hub_process_survey\',\'redcap-edit-frame-admin\',\''.$survey_link.'\');" class="btn btn-success btn-xs open-codesModal" style="margin-top: 7px;"><i class="fa fa-calendar fa-fw" aria-hidden="true"></i> Change Status</a></div>';
    }

    if(($req['contactperson_id'] == $current_user['record_id'] || ($current_user['person_region'] == $req['contact_region'] && $current_user['harmonist_regperm'] == 3)) && $req_type != 'archive' && $req_type != 'home'){
        $passthru_link = $module->resetSurveyAndGetCodes(IEDEA_RMANAGER, $req['request_id'], "request", "");
        $survey_link = $module->getUrl('surveyPassthru.php?&surveyLink='.APP_PATH_SURVEY_FULL . "?s=".$passthru_link['hash']);

        $current_req .= '<div><a href="'.$survey_link.'" class="btn btn-default btn-xs actionbutton" target="_blank" style="margin-top: 7px;"><span class="fa fa-pencil"></span> '.$req_type.'Edit</a></div>';
    }
    $current_req .= '</td>';

    return $current_req;
}

/**
 * Function that returns the table row for archived requests
 * @param $req
 * @param $request_type_label
 * @param $person_region
 * @return string
 */
function getArchiveHTML($module,$req,$request_type_label,$person_region, $vote_visibility){

    $class = "nowrap";
    if (strtotime($req['due_d']) < strtotime(date('Y-m-d'))){
        $class = "overdue";
    }

    $current_req = '<tr>
                    <td><span class="'.$class.'">'.$req['due_d'].'</span></td>
                    <td>
                        <strong>'.$request_type_label[$req['request_type']].'</strong><br>';

    $current_req .= getReqAssocConceptLink($module,$req['assoc_concept'],"");

    $projectRegions = new \Plugin\Project(IEDEA_REGIONS);
    $RecordSetRegions = new \Plugin\RecordSet($projectRegions, array('showregion_y' => "1", 'record_id' => $req['contact_region']));
    $region = $RecordSetRegions->getDetails()[0];

    $current_req .= '</td>
                    <td>'.$req['contact_name'].'</td>
                    <td>'.$region['region_code'].'</td>
                    <td>'.$req['request_title'].'</td>';

    $current_req_region = '';

    $instance = $person_region;
    if($instance == 1){
        $instance = '';
    }

    if($vote_visibility == "" || $vote_visibility =="1") {
        //PRIVATE VOTES
        $current_req_region .= getPrivateVotesHTML($req['region_response_status'][$instance],'');
    }else{
        if ($req['region_response_status'][$instance] == "2") {
            //PUBLIC VOTES
            $current_req_region .= getPublicVotesHTML($req['region_response_status'][$instance],$req['region_vote_status'][$instance],'');
        }else{
            $current_req_region .= getPrivateVotesHTML($req['region_response_status'][$instance],'');
        }
    }

    $current_req .= $current_req_region;

    $current_req .= '<td><div><a href="index.php?option=hub&record=' . $req['request_id'] . '" class="btn btn-default btn-xs actionbutton">View/Edit</a></div>';
    $current_req .= '</td>';

    return $current_req;
}

function getRequestVoteIcon($current_req_region,$vote_grid,$person_region,$record_id,$vote_visibility,$req,$current_user){
    if($vote_grid == "0"){
        $instance = $person_region;
    }else{
        $instance = $record_id;
    }

    if($instance == 1){
        $instance = '';
    }

    $small_screen_class = 'hidden-sm  hidden-xs';
    if($current_user['person_region'] == $instance){
        $small_screen_class = '';
    }

    if($vote_visibility == "" || $vote_visibility =="1") {
        //PRIVATE VOTES
        $current_req_region .= getPrivateVotesHTML($req['region_response_status'][$instance], $small_screen_class);
    }else if($vote_visibility =="3"){
        //MIX VOTES
        $current_req_region .= getMixVotesHTML($req['region_vote_status'][$instance],$req['region_response_status'][$instance], $record_id, $req, $small_screen_class);
    }else{
        if ($req['region_response_status'][$instance] == "2") {
            //PUBLIC VOTES
            $current_req_region .= getPublicVotesHTML($req['region_response_status'][$instance],$req['region_vote_status'][$instance],$small_screen_class);
        }else{
            $current_req_region .= getPrivateVotesHTML($req['region_response_status'][$instance],$small_screen_class);
        }
    }
    return $current_req_region;
}

/**
 * Function that returns the table row for home requests (my requests)
 * @param $req
 * @param $regions
 * @param $request_type_label
 * @param $current_user
 * @param $option
 * @param $vote_visibility
 * @param $vote_grid
 * @return string
 */
function getHomeRequestHTML($module, $req, $regions, $request_type_label, $current_user, $option, $vote_visibility, $vote_grid, $request_duration, $type){
    //Only open requests
    if (($req['contactperson_id'] == $current_user['record_id'] && !empty($req['due_d'])) || $request_duration == "none"){
        $extra_days = ' + ' . $request_duration . " days";
        $due_date_time = date('Y-m-d', strtotime($req['due_d'] . $extra_days));
        $today = date('Y-m-d');
        if ((strtotime($due_date_time) > strtotime($today))|| $request_duration == "none") {
            return getRequestHTML($module, $req, $regions, $request_type_label, $current_user, $option, $vote_visibility, $vote_grid, $type);
        }
    }
}

/**
 * Function that returns an array of the date text and a button of number of days left given a dure date
 * @param $date_deadline, due date
 * @param $region_response_status
 * @param $button_style, some style css for the button
 * @param $date_option, if we show the date on numbers or with text
 * @return array
 */
function getNumberOfDaysLeftButtonHTML($date_deadline, $region_response_status,$button_style, $date_option, $due=""){
    if(!empty($date_deadline)) {
        $due_date_time = strtotime($date_deadline);
        if ($date_option == '1') {
            $due_date = date("d F Y", $due_date_time);
        }else if($date_option == '2'){
            $due_date = date("d M Y",$due_date_time);
        }else{
            $due_date = $date_deadline;
        }


        $datetime = strtotime($date_deadline);
        $today = strtotime(date("Y-m-d"));
        $interval = $datetime - $today;
        $days_passed = floor($interval / (60 * 60 * 24));

        $day_text = "days";
        $until_due = " until due";
        if($days_passed== 0){
            $day_text = "TODAY";
            $until_due = " is due";
        }else if($days_passed == 1){
            $day_text = "day";
        }

        if($datetime > $today){
            $number_days = "+".$days_passed." ".$day_text;
            $date_color_text = "text-approved";
            $date_color_button = "text-button-approved";
        }else if($datetime < $today){
            $number_days = $days_passed." ".$day_text;

            if($region_response_status == '2'){
                $date_color_text = "text-approved";
                $date_color_button = "text-button-approved";
            }else{
                $date_color_text = "text-error";
                $date_color_button = "text-button-error";
            }
        }else if($datetime == $today) {
            $number_days = $day_text;
            $date_color_text = "text-approved";
            $date_color_button = "text-button-approved";
        }

        if($date_option == "3"){
            $date_color_button = "label-retrieve";
            $date_color_text = "";
        }

        if($due == ""){
            $until_due = "";
        }else{
            $date_color_button = "label-retrieve";
        }

        $array_dates = array('text' => '<span class="'.$date_color_text.'">'.$due_date.'</span>','button' =>'<span class="label label-as-badge '.$date_color_button.'" style="'.$button_style.'">'.$number_days.$until_due.'</span>');
        return $array_dates;
    }else{
        return array('text' => '<em>Unknown</em>','button' =>'');
    }
}

/**
 * Function that returns the row of the requested files
 * @param $edoc
 * @param $contact_name
 * @param $text
 * @param $datetime
 * @return string
 */
function getFileRow($module,$edoc, $contact_name, $text, $datetime,$secret_key,$secret_iv,$user,$lid){
    $file_row = '';
    if($edoc != "") {
        $q = $module->query("SELECT stored_name,doc_name,doc_size FROM redcap_edocs_metadata WHERE doc_id=?",[$edoc]);
        while ($row = $q->fetch_assoc()) {
            $file_row = "<td><a href='downloadFile.php?code=" . getCrypt("sname=" . $row['stored_name'] . "&file=" . urlencode($row['doc_name']) . "&edoc=" . $edoc . "&pid=" . $user . "&id=" . $lid, 'e', $secret_key, $secret_iv) . "' target='_blank'>" . $row['doc_name'] . "</a></td>";
            $file_row .= "<td>" . $text . "</td>";
            $file_row .= "<td>" . $contact_name . "</td>";
            $file_row .= "<td>" . $datetime . "</td>";
            $file_row .= "<td>" . convertToReadableSize($row['doc_size']) . "</td>";
        }
    }
    return $file_row;
}

/**
 * Function that passes from bytes to KB
 * @param $size
 * @return string
 */
function convertToReadableSize($size){
    if($size <= 0){
        $base = $size;
    }else{
        $base = log($size) / log(1024);
    }

    $suffix = array(" bytes", " KB", " MB", " GB", " TB");
    $f_base = floor($base);
    return round(pow(1024, $base - floor($base)), 1) . $suffix[$f_base];
}

function getDateForHumans($date){
    $today = strtotime(date("Y-m-d H:i:s"));
    $comment_date = strtotime($date);
    $seconds = $today - $comment_date;
    $cn = Carbon::now()->subSeconds($seconds)->diffForHumans();
    return $cn;
}
?>