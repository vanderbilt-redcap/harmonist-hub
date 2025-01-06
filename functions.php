<?php
namespace Vanderbilt\HarmonistHubExternalModule;
use Carbon\Carbon;
use Vanderbilt\HarmonistHubExternalModule\ArrayFunctions;
use Vanderbilt\HarmonistHubExternalModule\ProjectData;

require_once 'vendor/autoload.php';
require_once(dirname(__FILE__)."/classes/ArrayFunctions.php");
require_once(dirname(__FILE__)."/classes/ProjectData.php");


/**
 * Function that searches the file name in the database, parses it and returns an array with the content
 * @param $DocID, the id of the document
 * @return array, the generated array with the data
 */
function parseCSVtoArray($module, $DocID){
    $sqlTableCSV = $module->query("SELECT * FROM `redcap_edocs_metadata` WHERE doc_id = ?",[$DocID]);
    $csv = array();
    while ($rowTableCSV = $sqlTableCSV->fetch_assoc()) {
        $csv = createArrayFromCSV(EDOC_PATH,$rowTableCSV['stored_name']);
    }
    return $csv;
}

/**
 * Function that parses de CSV file to an Array
 * @param $filepath, the path of the file
 * @param $filename, the file name
 * @return array, the generated array with the CSV data
 */
function createArrayFromCSV($filepath,$filename, $addHeader = false){
    $file = $filepath.$filename;
    $csv = array_map('str_getcsv', file($file));
    #Remove hidden characters in file
    $csv[0][0] = trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $csv[0][0]));
    $csv[0][1] = trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $csv[0][1]));
    array_walk($csv, function(&$a) use ($csv) {
        $a = array_combine($csv[0], $a);
    });
    if($addHeader){
        # remove column header
        array_shift($csv);
    }

    return $csv;
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

function getFile($module, $project_id, $edoc, $type){
    $file = "#";
    if($edoc != ""){
        $q = $module->query("SELECT stored_name,doc_name,doc_size,mime_type FROM redcap_edocs_metadata WHERE doc_id=?",[$edoc]);
        while ($row = $q->fetch_assoc()) {
            $url = 'downloadFile.php?NOATUH&sname=' . $row['stored_name'] . '&file=' . urlencode($row['doc_name']);
            $base64 = base64_encode(file_get_contents(EDOC_PATH.$row['stored_name']));
            if($type == "img"){
                $file = '<br/><div class="inside-panel-content"><img src="data:'.$row['mime_type'].';base64,' . $base64. '" style="display: block; margin: 0 auto;"></div>';
            }else if($type == "logo"){
                $file = '<img src="data:'.$row['mime_type'].';base64,' . $base64. '" style="padding-bottom: 30px;width: 450px;">';
            }else if($type == "src") {
                $file = 'data:' . $row['mime_type'] . ';base64,' . $base64;
            }else if($type == "pdf") {
                $file = EDOC_PATH.$row['stored_name'];
            }else if($type == "imgpdf"){
                $file = '<div style="max-width: 450px;height: 500px;"><img src="data:'.$row['mime_type'].';base64,' . $base64. '" style="display: block; margin: 0 auto;width:450px;height: 450px;"></div>';
            }else if($type == "url") {
                $file = $module->getUrl($url);
            }else if($type = "favicon") {
                $download = getCrypt("sname=".$row['stored_name']."&file=". urlencode($row['doc_name'])."&edoc=".$edoc,'e',"","");
                $file = $module->getUrl("downloadFile.php")."&NOAUTH&pid=".$project_id."&code=".$download;
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
function getFileLink($module, $project_id, $edoc, $option, $outer="",$secret_key,$secret_iv,$user,$lid){
    $file_row = "";
    if($edoc != "" and is_numeric($edoc)){
        $file_row = '';
        $q = $module->query("SELECT stored_name,doc_name,doc_size,file_extension FROM redcap_edocs_metadata WHERE doc_id=?",[$edoc]);
        while ($row = $q->fetch_assoc()) {
            $name = urlencode($row['doc_name']);

            $download = getCrypt("sname=".$row['stored_name']."&file=". $name."&edoc=".$edoc."&pid=".$user."&id=".$lid,'e',$secret_key,$secret_iv);
            $file_url = $module->getUrl("downloadFile.php")."&NOAUTH&pid=".$module->escape($project_id)."&code=".$module->escape($download);

            if($option == ''){
                $icon = getFaIconFile($row['file_extension']);
                $file_row = $icon."<a href='".$file_url."' target='_blank'>".$module->escape($row['doc_name'])." ".ProjectData::formatBytes($row['doc_size'])."</a>";
            }else{
                $file_row = "<a href='".$file_url."' target='_blank' title='".$module->escape($row['doc_name'])."'>".getFaIconFile($row['file_extension'])." ".ProjectData::formatBytes($row['doc_size'])."</a>";
            }
        }
    }
    return $file_row;
}

function getOtherFilesLink($module, $edoc,$id,$user,$secret_key,$secret_iv,$other_title){
    $file_row = $other_title;
    if($edoc != "" and is_numeric($edoc)){
        $q = $module->query("SELECT stored_name,doc_name,doc_size,file_extension FROM redcap_edocs_metadata WHERE doc_id=?",[$edoc]);
        while ($row = $q->fetch_assoc()) {
            $name = urlencode($row['doc_name']);
            $download = getCrypt("sname=".$row['stored_name']."&file=". $name."&edoc=".$edoc."&id=".$id."&pid=".$user,'e',$secret_key,$secret_iv);
            $file_url = $module->getUrl("downloadFile.php")."&NOAUTH&code=".$download;


            $file_row = "<a href='".$file_url."'>".getFaIconFile($row['file_extension']).$other_title."</a>";
        }
    }
    return $file_row;
}

function getFaIconFile($file_extension){
    $icon = "fa-file-o";
    if(strtolower($file_extension) == '.pdf' || strtolower($file_extension) == 'pdf'){
        $icon = "fa-file-pdf-o";
    }else if(strtolower($file_extension) == '.doc' || strtolower($file_extension) == 'doc' ||
        strtolower($file_extension) == '.docx' || strtolower($file_extension) == 'docx'){
        $icon = "fa-file-word-o";
    }else if(strtolower($file_extension) == '.pptx' || strtolower($file_extension) == 'pptx' ||
        strtolower($file_extension) == '.ppt' || strtolower($file_extension) == 'ppt'){
        $icon = "fa-file-powerpoint-o";
    }else if(strtolower($file_extension) == '.xlsx' || strtolower($file_extension) == 'xlsx'){
        $icon = "fa-file-excel-o";
    }else if(strtolower($file_extension) == '.html' || strtolower($file_extension) == 'html'){
        $icon = "fa-file-code-o";
    }else if(strtolower($file_extension) == '.png' || strtolower($file_extension) == 'png' ||
        strtolower($file_extension) == '.jpeg' || strtolower($file_extension) == 'jpeg' ||
        strtolower($file_extension) == '.gif' || strtolower($file_extension) == 'gif' ||
        strtolower($file_extension) == '.tiff' || strtolower($file_extension) == 'tiff' ||
        strtolower($file_extension) == '.bmp' || strtolower($file_extension) == 'bmp' ||
        strtolower($file_extension) == '.jpg' || strtolower($file_extension) == 'jpg'){
        $icon = "fa-file-image-o";
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
function isTokenCorrect($token,$pidPeople){
    $people = \REDCap::getData($pidPeople, 'json-array', null,array('token_expiration_d'),null,null,false,false,false,"[access_token] = '".$token."'")[0];
    if(!empty($people)){
        if(strtotime($people['token_expiration_d']) > strtotime(date('Y-m-d'))){
            return true;
        }
    }
    return false;
}

function getToken($userid,$pidPeople){
    $people = \REDCap::getData($pidPeople, 'json-array', null,array('access_token'),null,null,false,false,false,"[redcap_name] = '".$userid."'")[0];
    if(!empty($people)){
        return $people['access_token'];
    }
}

function getReqAssocConceptLink($module, $pidsArray, $assoc_concept, $option=""){
    if(!empty($assoc_concept)){
        $concepts = \REDCap::getData($pidsArray['HARMONIST'], 'json-array', array('record_id' => $assoc_concept),array('concept_id','concept_title'))[0];
        $concept_sheet = $concepts['concept_id'];
        $concept_title = ($option == '1') ? ', '.$concepts['concept_title'] : "";

        return '<a href="'.$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=ttl&record='.$assoc_concept.'" target="_blank">'.$concept_sheet.$concept_title.'</a>';
    }
}

function getPeopleName($pidPeople, $people_id, $option=""){
    if(!empty($people_id) && !empty($pidPeople)){
        $people = \REDCap::getData($pidPeople, 'json-array', array('record_id' => $people_id),array('firstname','lastname','email'))[0];
        $name = trim($people['firstname'].' '.$people['lastname']);
        if($option == "email"){
            $name = '<a href="mailto:'.$people['email'].'">'.trim($people['firstname'].' '.$people['lastname']).'</a>';
        }
        return $name;
    }
    return "";
}

function getTableVariableJsonName($project_id,$data,$varName,$jsonArray){
    if($data != ""){
        if($data != ""){
            $variable = explode(":",$data);
            $dataTableDataModelRecords = \REDCap::getData($project_id, 'array',array('record_id' => $variable[0]));
            $tableData = ProjectData::getProjectInfoArrayRepeatingInstruments($dataTableDataModelRecords,$project_id);
            $jsonArray[$varName] = $tableData[0]['table_name'].":".$tableData[0]['variable_name'][$variable[1]];
        }
    }
    return $jsonArray;
}

function getTableJsonName($project_id,$data,$varName,$jsonArray){
    if($data != ""){
        $tableData = \REDCap::getData($project_id, 'json-array',array('record_id' => $data),array('table_name'))[0];
        $jsonArray[$varName] = $tableData['table_name'];
    }
    return $jsonArray;
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
        if(is_array($req['region_response_status'])) {
            if ($req['finalize_y'] == "" && ($req['region_response_status'][$instance] == 0 || $req['region_response_status'][$instance] == 1)) {
                $number++;
            }
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

/**
 * Function that returns the HTML header for the requests
 * @param $regions
 * @return string
 */
function getRequestHeader($hubData, $vote_grid, $option, $type=""){
    $current_user = $hubData->getCurrentUser();
    $person_region = $hubData->getPersonRegion();
    $regions = $hubData->getAllRegions();
    $isAdmin = $current_user['is_admin'];

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
    if($option != '2' && $type != 'home'){
        $small_screen_class = 'hidden-sm hidden-xs';
        if ($vote_grid == '2' || ($isAdmin && $vote_grid == "0")) {
            $header_region .= '<th class="request_grid_icon ' . $small_screen_class . '" style="width:150px" data-sortable="false">' . $person_region['region_code'] . '</th>';
        } else {
            foreach ($regions as $region) {
                if ($vote_grid == "0" && !$isAdmin) {
                    $instance = $current_user['person_region'];
                } else {
                    $instance = $region['record_id'];
                }

                $count_regions++;
                if ($current_user['person_region'] == $instance) {
                    $small_screen_class = '';
                } else {
                    $small_screen_class = 'hidden-sm hidden-xs';
                }

                if (
                    ($vote_grid == "0" && $current_user['person_region'] == $region['record_id'])
                    || ($vote_grid == "1")
                ) {
                    $header_region .= '<th class="request_grid_icon ' . $small_screen_class . '" data-sortable="false">' . $region['region_code'] . '</th>';
                    if ($instance == $current_user['person_region'] && $vote_grid != "0" && $option == '0') {
                        $header_colgroup .= '<col class="active">';
                    } else {
                        $header_colgroup .= '<col>';
                    }
                    if(($vote_grid == "0" && $current_user['person_region'] == $region['record_id']))
                        break;
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

    if($type != 'home') {
        if ($option == "1") {
            $row_text = 'Final Status';
        } else {
            $row_text = 'Actions';
        }
        $header .= '<th class="request_grid_actions" data-sortable="false">'.$row_text.'</th></tr></thead>';
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
    if (($req['region_response_status'][$instance] == "2" || $req['finalize_y'] != "") && !empty($req['due_d'])) {
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
function showPendingRequest($comments, $current_region, $request){
    foreach ($comments as $comment) {
        if ($comment['vote_now'] == "0" &&
            $comment['response_region'] == $current_region &&
            (!array_key_exists('finalize_y', $request) || $request['finalize_y'] == "")
        ) {
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

function getMixVotesHTML($commentReq, $region_vote_status,$region_response_status,$region_id,$req,$small_screen_class){
    $mix = false;
    foreach ($commentReq as $comment){
        if($comment['response_region'] == $region_id && $region_vote_status != $comment['pi_vote'] && !empty($comment['pi_vote']) && array_key_exists('region_vote_status',$req) && $region_vote_status != ""){
            $mix = true;
            break;
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
function getPublicVotesHTML($vote,$small_screen_class){
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
function getRequestHTML($module, $hubData, $pidsArray, $req, $commentReq, $request_type_label, $option, $vote_visibility, $vote_grid, $req_type){
    $current_user = $hubData->getCurrentUser();
    $isAdmin = $current_user['is_admin'];
    $person_region = $hubData->getPersonRegion();
    $regions = $hubData->getAllRegions();
    $class = "nowrap";

    if($option == '0'){
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

    $closing_parenthesis = "";
    $current_req .= $req['mr_temporary'];
    if($req['assoc_concept'] != ""){
        if($req['mr_temporary'] != "") {
            $current_req .= " (";
            $closing_parenthesis = ")";
        }
        $current_req .= getReqAssocConceptLink($module, $pidsArray, $req['assoc_concept'], "").$closing_parenthesis;
    }

    if($req_type != 'home'){
        $current_req .= '</td>
                    <td>'.$req['contact_name'].'</td>';
    }

    $text = "";
    if ($req['revision_counter_total'] != '') {
        $comment_time ="";
        foreach ($commentReq as $comment) {
            if($comment['revision_counter'] == $req['revision_counter_total'] && !empty($comment['responsecomplete_ts'])){
                $dateComment = new \DateTime($comment['responsecomplete_ts']);
                $dateComment->modify("+1 hours");
                $comment_time = ": ".$dateComment->format("Y-m-d H:i");
                break;
            }
        }
        $text = "<div class='request_revision_text'>revision <span style='font-size:12px'>".$req['revision_counter_total'].$comment_time."</span></div>";
    }

    $type = '';
    if($option == '2') {
        $type = '&type=r';
    }
    $current_req .= '<td '.$width[2].' class="hidden-xs"><a href="'.$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=hub&record=' . $req['request_id'] . $type.'">'.$text.$req['request_title'].'</a></td>';

    $current_req_region = '';
    if($option != '2') {
        if($req_type != 'home') {
            if ($vote_grid == '2' || ($vote_grid == '0' && $isAdmin)) {
                $current_req_region = getRequestVoteIcon($commentReq, $current_req_region, $vote_grid, $current_user['person_region'], $person_region['record_id'], $vote_visibility, $req, $current_user);
            } else {
                foreach ($regions as $region) {
                    $current_req_region = getRequestVoteIcon($commentReq, $current_req_region, $vote_grid, $current_user['person_region'], $region['record_id'], $vote_visibility, $req, $current_user);
                    if ($vote_grid == "0") {
                        break;
                    }
                }
            }
            $current_req .= $current_req_region;
        }

        $view_all_votes = "";
        if ($vote_grid == '2' || ($vote_grid == '0' && $isAdmin)) {
            $url = $module->getUrl("hub/hub_requests_view_all_votes_AJAX.php")."&NOAUTH";
            $view_all_votes = '<div><a href="#" onclick="viewAllVotes(' . $req['request_id'] . ',\''.$url.'\');" class="btn btn-success btn-xs" style="margin-bottom: 7px;"><span class="fa fa-folder-open"></span> All votes</a></div>';
        }
        if ($vote_visibility == '3') {
            $url = $module->getUrl("hub/hub_requests_view_mixed_votes_AJAX.php")."&NOAUTH";
            $view_all_votes .= '<div><a href="#" onclick="viewMixedVotes(' . $req['request_id'] . ',' . $current_user['person_region'].',\''.$url.'\');" class="btn btn-success btn-xs" style="margin-bottom: 7px;"><span class="fa fa-folder-open"></span> Vote Details</a></div>';
        }
        if ($option == 0) {
            if ($req_type == 'archive') {
                $current_req .= '<td ' . $width[0] . '>';
                if ($req['finalize_y'] != "") {
                    $request_finalize_y_label = $module->getChoiceLabels('finalize_y', $pidsArray['RMANAGER']);
                    $current_req .= $request_finalize_y_label[$req['finalize_y']] . "<br><span style='font-size: 12px'>" . $req['final_d'] . "</span>";
                } else {
                    $current_req .= "<em>None</em>";
                }
            } else if ($req_type != 'home') {
                if ($current_user['harmonist_regperm'] == 1) {
                    $current_req .= '<td ' . $width[0] . '>' . $view_all_votes . '<div><a href="'.$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=hub&record=' . $req['request_id'] . '" class="btn btn-primary btn-xs"><span class="fa fa-eye"></span> View</a></div>';
                } else {
                    $current_req .= '<td ' . $width[0] . '>' . $view_all_votes . '<div><a href="'.$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=hub&record=' . $req['request_id'] . '" class="btn btn-primary btn-xs"><span class="fa ' . $button_icon . '"></span> ' . $button_text . '</a></div>';
                }
            }
        } else {
            $current_req .= '<td ' . $width[0] . '>' . $view_all_votes . '<div><a href="'.$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=hub&record=' . $req['request_id'] . '" class="btn btn-default btn-xs actionbutton"><span class="fa fa-eye"></span> View/Edit</a></div>';
        }
    }else {
        $reviewer = '';
        if ($req['reviewer_id'] != ''){
            $reviewer = getPeopleName($pidsArray['PEOPLE'], $req['reviewer_id'],"");
            if ($reviewer != '') {
                $reviewer = ' by ' . $reviewer;
            }
        }

        if($req['approval_y'] == '0'){
            $current_req .= '<td width="150px"><strong>Rejected</strong>'.$reviewer.'</td>';
        }else if($req['approval_y'] == '9') {
            $current_req .= '<td width="150px"><strong>Deactivated</strong>'.$reviewer.'</td>';
        }else{
            $current_req .= '<td width="150px"><em>Unspecified</em></td>';
        }

        $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['RMANAGER'], $req['request_id'], "request", "");
        $survey_link = $module->escape(APP_PATH_WEBROOT_FULL . "/surveys/?s=".$passthru_link['hash']);
        $current_req .=  '<td><div><a href="'.$survey_link.'" class="btn btn-primary btn-xs actionbutton" target="_blank"><i class="fa fa-eye fa-fw" aria-hidden="true"></i> Check Submission</a></div>';

        $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['RMANAGER'], $req['request_id'], "admin_review", "");
        $survey_link = $module->escape(APP_PATH_WEBROOT_FULL . "/surveys/?s=".$passthru_link['hash']);

        $current_req .=  '<div><a href="#" onclick="editIframeModal(\'hub_process_survey\',\'redcap-edit-frame-admin\',\''.$survey_link.'\');" class="btn btn-success btn-xs open-codesModal" style="margin-top: 7px;"><i class="fa fa-calendar fa-fw" aria-hidden="true"></i> Change Status</a></div>';
    }

    if(($req['contactperson_id'] == $current_user['record_id'] || ($current_user['person_region'] == $req['contact_region'] && $current_user['harmonist_regperm'] == 3)) && $req_type != 'archive' && $req_type != 'home'){
        $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['RMANAGER'], $req['request_id'], "request", "");
        $survey_link = $module->escape(APP_PATH_WEBROOT_FULL . "/surveys/?s=".$passthru_link['hash']);

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
function getArchiveHTML($module,$pidsArray,$req,$request_type_label,$person_region, $vote_visibility){

    $class = "nowrap";
    if (strtotime($req['due_d']) < strtotime(date('Y-m-d'))){
        $class = "overdue";
    }

    $current_req = '<tr>
                    <td><span class="'.$class.'">'.$req['due_d'].'</span></td>
                    <td>
                        <strong>'.$request_type_label[$req['request_type']].'</strong><br>';

    $current_req .= getReqAssocConceptLink($module, $pidsArray,$req['assoc_concept'],"");

    $region = \REDCap::getData($pidsArray['REGIONS'], 'json-array', array('record_id' => $req['contact_region']),null,array('region_code'),null,false,false,false,"[showregion_y] = 1")[0];

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
            $current_req_region .= getPublicVotesHTML($req['region_vote_status'][$instance],'');
        }else{
            $current_req_region .= getPrivateVotesHTML($req['region_response_status'][$instance],'');
        }
    }

    $current_req .= $current_req_region;

    $current_req .= '<td><div><a href="'.$module->getUrl('index.php').'&NOAUTH&record='.$req['request_id'].'&option=hub'.'" class="btn btn-default btn-xs actionbutton">View/Edit</a></div>';
    $current_req .= '</td>';

    return $current_req;
}

function getRequestVoteIcon($commentReq, $current_req_region, $vote_grid, $person_region ,$record_id, $vote_visibility, $req, $current_user){
    if($vote_grid == "0"){
        $instance = $person_region;
    }else{
        $instance = $record_id;
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
        $current_req_region .= getMixVotesHTML($commentReq, $req['region_vote_status'][$instance],$req['region_response_status'][$instance], $record_id, $req, $small_screen_class);
    }else{
        if ($req['region_response_status'][$instance] == "2") {
            //PUBLIC VOTES
            $current_req_region .= getPublicVotesHTML($req['region_vote_status'][$instance],$small_screen_class);
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
function getHomeRequestHTML($module, $hubData, $pidsArray, $req, $comment, $request_type_label, $option, $vote_visibility, $vote_grid, $request_duration, $type){
    //Only open requests
    $current_user = $hubData->getCurrentUser();
    if (($req['contactperson_id'] == $current_user['record_id'] && !empty($req['due_d'])) || $request_duration == "none"){
        $extra_days = ' + ' . $request_duration . " days";
        $due_date_time = date('Y-m-d', strtotime($req['due_d'] . $extra_days));
        $today = date('Y-m-d');
        if ((strtotime($due_date_time) > strtotime($today))|| $request_duration == "none") {
            return getRequestHTML($module, $hubData, $pidsArray, $req, $comment, $request_type_label, $option, $vote_visibility, $vote_grid, $type);
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
        $q = $module->query("SELECT stored_name,doc_name,doc_size,stored_date FROM redcap_edocs_metadata WHERE doc_id=?",[$edoc]);
        while ($row = $q->fetch_assoc()) {
            if($datetime == ""){
                $datetime = $row['stored_date'];
            }
            $file_row = "<td><a href='".$module->getUrl('downloadFile.php').'&NOAUTH&code='. getCrypt("sname=" . $row['stored_name'] . "&file=" . urlencode($row['doc_name']) . "&edoc=" . $edoc . "&pid=" . $user . "&id=" . $lid, 'e', $secret_key, $secret_iv). "' target='_blank'>" . $row['doc_name'] . "</a></td>";
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

function getDataCallHeader($pidRegions, $person_region, $vote_grid, $option=""){
    $regions = \REDCap::getData($pidRegions, 'json-array', null,null,null,null,false,false,false,"[showregion_y] = '1'");
    ArrayFunctions::array_sort_by_column($regions, 'region_code');

    $header_colgroup = "<colgroup><col><col><col>";
    $header_region = "";
    if($vote_grid == '2' || $vote_grid == '0') {
        $my_region = \REDCap::getData($pidRegions, 'json-array', array('record_id' => $person_region),array('region_code'))[0]['region_code'];
        $header_region .= '<th class="request_grid_icon hidden-sm hidden-xs" style="width:40px" data-sortable="false">' . $my_region . '</th>';
    }else {
        foreach ($regions as $region) {
            $instance = $region['record_id'];
            $header_region .= '<th class="request_grid_icon" data-sortable="false" style="width:40px;text-align: center">' . $region['region_code'] . '</th>';
            if ($instance == $person_region) {
                $header_colgroup .= '<col class="active">';
            } else {
                $header_colgroup .= '<col>';
            }
        }
    }
    $text = "Actions";
    if($option == "1"){
        $text = "Final Status";
    }

    $header_colgroup .= '<col></colgroup>';
    if($vote_grid == '2' || $vote_grid == '0') {
        $header = $header_colgroup . '<thead>
                    <tr>
                        <th class="sorted_class" style="width:100px" data-sorted="true" data-sorted-direction="descending">Due Date</th>
                        <th class="sorted_class" style="width:721px">Data Request Details</th>
                        <th style="width:168px">Data Contact</th>' .
            $header_region . '
                        <th class="" data-sortable="false" data-sorted="false" style="width:107px">' . $text . '</th>
                    </tr>
                    </thead>';
    }else {
        $header = $header_colgroup . '<thead>
                    <tr>
                        <th class="sorted_class" style="width:90px" data-sorted="true" data-sorted-direction="descending">Due Date</th>
                        <th class="sorted_class" style="width:504px">Data Request Details</th>
                        <th style="width:168px">Data Contact</th>' .
            $header_region . '
                        <th class="" data-sortable="false" data-sorted="false" style="width:96px">' . $text . '</th>
                    </tr>
                    </thead>';
    }
    return $header;
}

function getDataCallRow($module, $pidsArray, $sop,$isAdmin,$current_user,$secret_key,$secret_iv,$vote_grid,$type,$harmonist_perm=""){
    $status_type = $module->getChoiceLabels('data_response_status', $pidsArray['SOP']);

    $data =  "<tr>";
    $array_dates = getNumberOfDaysLeftButtonHTML($sop['sop_due_d'], '', 'float:right', '0');

    $people = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array('record_id' => $sop['sop_datacontact']),array('person_region','email','firstname','lastname'))[0];
    $region_code = \REDCap::getData($pidsArray['REGIONS'], 'json-array', array('record_id' => $people['person_region']),array('region_code'))[0]['region_code'];

    $contact_person = "";
    if($people != ""){
        $contact_person = "<a href='mailto:" . $people['email'] . "'>" . $people['firstname'] . " " . $people['lastname'] . "</a> (" . $region_code . ")";
    }

    $concept = \REDCap::getData($pidsArray['HARMONIST'], 'json-array', array('record_id' => $sop['sop_concept_id']),array('concept_id','concept_title'))[0];
    $concept_id = $concept['concept_id'];
    $concept_title = $concept['concept_title'];

    $regions = \REDCap::getData($pidsArray['REGIONS'], 'json-array', null,null,null,null,false,false,false,"[showregion_y] = '1'");
    ArrayFunctions::array_sort_by_column($regions, 'region_code');
    $status_row = "";
    $current_region_status = "";
    $url = "";
    $buttons = '';
    $width='';
    if($vote_grid == '2' || $vote_grid == '0') {
        $status = $sop['data_response_status'][$current_user['person_region']];
        $status_row .= "<td style='text-align: center'>";
        $status_icons = getDataCallStatusIcons($status);
        $status_row .= $status_icons."</td>";
    }else {
        foreach ($regions as $region) {
            $status = $sop['data_response_status'][$region['record_id']];
            $status_text = $status_type[$sop['data_response_status'][$current_user['person_region']]];
            if ($sop['data_response_status'][$current_user['person_region']] == "") {
                $status_text = $status_type[1];
            }

            $status_row .= "<td style='text-align: center'>";
            $status_icons = getDataCallStatusIcons($status);
            if ($region['record_id'] == $current_user['person_region']) {
                $current_region_status = htmlentities($status_icons . '<span class="status-text"> ' . $status_text . '</span>');
            }
            $status_row .= $status_icons . "</td>";
        }
    }
    $button_votes = "";
    if($vote_grid == '2') {
        $button_votes = '<div><a href="#" onclick="viewAllVotesData(' . $sop['record_id'] . ');" class="btn btn-success btn-xs" style="margin-bottom: 7px;"><span class="fa fa-folder-open"></span> All votes</a></div>';
    }
    if ($type == "s" || $type == "a") {
        if ($type == "a") {
            $width = "style='100px'";
            $buttons = "<div><em>None</em></div>";
            if ($sop['sop_finalize_y'] != "" || ($sop['sop_closed_y'] != "" && $sop['sop_closed_y'] != "1")) {
                if ($sop['sop_finalize_y'] != "") {
                    $buttons = "<div>Started</div>";
                    if ($sop['sop_final_d'] != "") {
                        $buttons .= "<div>" . $sop['sop_final_d'] . "</div>";
                    }
                }
                if ($sop['sop_closed_y'] != "" && $sop['sop_closed_y'] == "1") {
                    $buttons = "<div style='color: green;font-weight: bold;'>Completed</div>";
                    if ($sop['sop_closed_d'] != "") {
                        $buttons .= "<div>" . $sop['sop_closed_d'] . "</div>";
                    }
                }
            }
        } else {
            $buttons = '<div><a href="#" onclick="confirmDataUpload(\'' . $sop['sop_concept_id'] . '\',\'' . $current_user['record_id'] . '\',\'' . $concept_id . '\',\'' . $sop['record_id'] . '\');" class="btn btn-primary btn-xs">Upload Data</a></div>';
            if ($current_user['allowgetdata_y___1'] == "1" || $current_user['harmonistadmin_y'] == '1') {
                $buttons .= '<div style="padding-top: 8px"><a href="#" onclick="changeStatus(\'' . $current_region_status . '\',\'' . $sop['record_id'] . '\',\'' . $current_user['person_region'] . '\',\'' . htmlspecialchars($sop['data_response_notes'][$current_user['person_region']]) . '\',\'' . $sop['region_update_ts'][$current_user['person_region']] . '\',\'modal-data-change-status\')" class="btn btn-default btn-xs">Change Status</a></div>';
            }
        }

        $url = "&type=s";

        $data .= "<td><div style='text-align: center'>" . $array_dates['text'] . "</div><div>" . $array_dates['button'] . "</div></td>";
    } else if ($type == "p") {
        if ($isAdmin || $harmonist_perm || $sop['sop_hubuser'] == $current_user['record_id'] || $sop['sop_creator'] == $current_user['record_id'] || $sop['sop_creator2'] == $current_user['record_id'] || $sop['sop_datacontact'] == $current_user['record_id']) {
            $buttons .= '<div><a href="'.$module->getUrl('index.php').'&NOAUTH&pid=' . $pidsArray['PROJECTS'] . '&option=ss1&record=' . $sop['record_id'] . '&step=3'.'" class="btn btn-primary btn-xs " target="_blank" style="color:#fff"><i class="fa fa-edit" aria-hidden="true"></i> Edit</a></div>';
        }
        if ($isAdmin || $harmonist_perm) {
            $buttons .= '<div style="padding-top: 8px"><a href="#" onclick="confirmMakePrivate(\'' . $sop['record_id'] . '\')" class="btn btn-default btn-xs"><i class="fa fa-thumb-tack" aria-hidden="true"></i> Make private</a></div>';
        }

        $status_row = "<td style='width: 149px'><div>" . $sop['sop_updated_dt'] . "</div></td>";
    } else if ($type == 'm') {
        $buttons = '';
        if ($isAdmin || $harmonist_perm || $sop['sop_hubuser'] == $current_user['record_id'] || $sop['sop_creator'] == $current_user['record_id'] || $sop['sop_creator2'] == $current_user['record_id'] || $sop['sop_datacontact'] == $current_user['record_id']) {
            $buttons .= '<div><a href="'.$module->getUrl('index.php').'&NOAUTH&pid=' . $pidsArray['PROJECTS'] . '&option=ss1&record=' . $sop['record_id'] . '&step=3'.'" class="btn btn-primary btn-xs " target="_blank" style="color:#fff"><i class="fa fa-edit" aria-hidden="true"></i> Edit</a></div>';
        }

        if ($sop['sop_visibility'] == '2') {
            $sop_visibility = '<span class="badge badge-pill badge-public">Public</span>';

            $buttons .= '<div><a href="#" onclick="confirmMakePrivate(\'' . $sop['record_id'] . '\')" class="btn btn-default btn-xs open-codesModal" style="margin-top: 7px;"><i class="fa fa-thumb-tack" aria-hidden="true"></i> Make private</a></div>';
        } else if ($sop['sop_visibility'] == '1') {
            $sop_visibility = '<span class="badge badge-pill badge-private">Private</span>';

            $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['SOP'], $sop['record_id'], "dhwg_review_request", "");
            $survey_link = $module->escape(APP_PATH_WEBROOT_FULL . "/surveys/?s=".$passthru_link['hash']);

            $buttons .= '<div><a href="#" onclick="editIframeModal(\'sop-make-public\',\'redcap-edit-frame-make-public\',\'' . $survey_link . '\');" class="btn btn-success btn-xs open-codesModal" style="margin-top: 7px;"><i class="fa fa-paper-plane" aria-hidden="true"></i> Send for Review</a></div>';
        }
        $buttons .= '<div><a href="#" onclick="deleteDataRequest(\'' . $sop['record_id'] . '\')" style="cursor: pointer;margin-top: 7px;" class="btn btn-danger btn-xs" title="Delete Data Request"><i class="fa fa-trash-o" aria-hidden="true"></i> Delete</a></div></td>';
        $contact_person = $sop['sop_created_dt'];
        $status_row = "<td style='width: 149px'><div>" . $sop['sop_updated_dt'] . "</div></td>";
    }


    $file_data ='';
    if($sop['sop_finalpdf'] != ""){
        $file_data = " | ".getFileLink($module, $pidsArray['PROJECTS'], $sop['sop_finalpdf'],'1','',$secret_key,$secret_iv,$current_user['record_id'],"");
    }

    $data .=    "<td><div><strong>" . $concept_id . "</strong> ".$sop_visibility."</div><div>" . $concept_title . "</div><div><em>Draft ID: ".$sop['record_id']."</em></div><div></div><a href='".$module->getUrl("index.php")."&NOAUTH&pid=".$pidsArray['PROJECTS']."&option=sop&record=".$sop['record_id'].$url."'>Data Request </a> | <a href='".$module->getUrl("index.php?pid=".$pidsArray['PROJECTS']."&option=ttl&record=".$sop['sop_concept_id'])."'>".$concept_id." Concept</a>".$file_data."</td>" .
        "<td style='width:168px'>" . $contact_person . "</td>" .
        $status_row.
        "<td ".$width.">" . $button_votes.$buttons . "</td>" .
        "</tr>";

    return $data;
}
function getDataCallConceptsHeader($pidRegions, $person_region,$vote_grid){
    $regions = \REDCap::getData($pidRegions, 'json-array', null,null,null,null,false,false,false,"[showregion_y] = 1");
    ArrayFunctions::array_sort_by_column($regions, 'region_code');

    $header_colgroup = "<colgroup><col><col><col><col>";
    $header_region = "";

    if($vote_grid == '2' || $vote_grid == '0') {
        $my_region = \REDCap::getData($pidRegions, 'json-array', array('record_id' => $person_region),array('region_code'))[0]['region_code'];
        $header_region .= '<th class="request_grid_icon hidden-sm hidden-xs" style="width:150px" data-sortable="false">' . $my_region . '</th>';
    }else{
        foreach ($regions as $region) {
            $instance = $region['record_id'];

            if ($person_region == $instance) {
                $small_screen_class = '';
            }else{
                $small_screen_class = 'hidden-sm hidden-xs';
            }


            $header_region .= '<th class="request_grid_icon ' . $small_screen_class . '" data-sortable="false" style="width:40px;text-align: center">' . $region['region_code'] . '</th>';
            if ($instance == $person_region) {
                $header_colgroup .= '<col class="active">';
            } else {
                $header_colgroup .= '<col>';
            }
        }
    }


    $header_colgroup .= '</colgroup>';
    $header = $header_colgroup.'<thead>
                    <tr>
                        <th class="sorted_class" style="width:90px" data-sorted="true" data-sorted-direction="descending">Due Date</th>
                        <th class="sorted_class" style="width:504px">Data Request Details</th>
                        <th class="sorted_class" style="width:100px">Status</th>
                        <th style="width:268px">Data Contact</th>'.$header_region;
    if($vote_grid == '2') {
        $header .= '<th class="sorted_class">Actions</th>';
    }
    $header .= '</tr></thead>';
    return $header;
}

function getDataCallConceptsRow($module, $pidsArray, $sop, $isAdmin, $current_user, $secret_key, $secret_iv, $vote_grid, $concept_record, $option = ""){
    $data =  "<tr>";
    $array_dates = getNumberOfDaysLeftButtonHTML($sop['sop_due_d'], '', 'float:right', '3');

    $people = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array('record_id' => $sop['sop_datacontact']),array('email','firstname','lastname','person_region'))[0];
    $region_code = \REDCap::getData($pidsArray['REGIONS'], 'json-array', array('record_id' => $people['person_region']),array('region_code'))[0]['region_code'];

    $contact_person = "<em>Unknown</em>";
    if($people != ""){
        $contact_person = "<a href='mailto:" . $people['email'] . "'>" . $people['firstname'] . " " . $people['lastname'] . "</a> (" . $region_code . ")";
    }

    $regions = \REDCap::getData($pidsArray['REGIONS'], 'json-array', null,null,null,null,false,false,false,"[showregion_y] = 1");
    ArrayFunctions::array_sort_by_column($regions, 'region_code');

    $status_row = "";
    $view_all_votes = "";
    if($vote_grid == '2' || $vote_grid == '0') {
        $my_region = \REDCap::getData($pidsArray['REGIONS'], 'json-array', array('record_id' => $current_user['person_region']),array('record_id'))[0]['record_id'];

        $status = $sop['data_response_status'][$my_region];
        $status_row .= "<td style='text-align: center'>";
        $status_icons = getDataCallStatusIcons($status);
        $status_row .= $status_icons."</td>";

        if($vote_grid == '2') {
            $view_all_votes = '<td><div><a href="#" onclick="viewAllVotesData(' . $sop['record_id'] . ');" class="btn btn-success btn-xs" style="margin-bottom: 7px;"><span class="fa fa-folder-open"></span> All votes</a></div></td>';
        }
    }else{
        foreach ($regions as $region) {
            if ($current_user['person_region'] == $region['record_id']) {
                $small_screen_class = '';
            }else{
                $small_screen_class = 'hidden-sm hidden-xs';
            }

            $status = $sop['data_response_status'][$region['record_id']];
            $status_row .= "<td style='text-align: center' class='".$small_screen_class."'>";
            $status_icons = getDataCallStatusIcons($status);
            $status_row .= $status_icons."</td>";
        }
    }

    $data .= "<td><div style='text-align: center'>" . $array_dates['text'] . "</div><div>" . $array_dates['button'] . "</div></td>";

    if($option == "1"){
        $RecordSetConceptSheets = \REDCap::getData($pidsArray['HARMONIST'], 'array', array('record_id' => $concept_record));
        $data_sopfile = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConceptSheets,$pidsArray['HARMONIST'])[0]['datasop_file'];

        $details = "<div><em>Historic (pre-Hub) Data Request";
        if($data_sopfile != ""){
            $details .= ": ".getFileLink($module, $pidsArray['PROJECTS'], $data_sopfile,'1','',$secret_key,$secret_iv,$current_user['record_id'],"");
        }
        $details .= "</em></div>";

        $sop_status = "<em>Unknown</em>";
    }else{
        if($sop['sop_status'] == "1"){
            if ($sop['sop_closed_y'] != '1') {
                $sop_closed_y = '<span class="label label-as-badge label-retrieve">Open</span>';
            } else if ($sop['sop_closed_y'] == '1') {
                $sop_closed_y = '<span class="label label-as-badge label-default_dark">Closed</span>';
            }
        }

        $status = 'badge-draft';
        if ($sop['sop_status'] == '1') {
            $status = 'badge-final';
        }

        $sop_status = $module->getChoiceLabels('sop_status', $pidsArray['SOP']);
        $sop_status = '<span class="label label-as-badge '.$status.'">'. $sop_status[$sop['sop_status']].'</span>&nbsp;&nbsp;';

        $url = "&type=s";
        $details = "<div><em>Draft ID: ".$sop['record_id']."</em></div><div><a href='".$module->getUrl("index.php?option=sop&record=".$sop['record_id'].$url)."'>Data Request </a></div>";
    }


    $data .=  "<td>".$details."</td>" .
        "<td width='200px'>".$sop_status.$sop_closed_y."</td>" .
        "<td style='width:168px'>" . $contact_person . "</td>" .
        $status_row.
        $view_all_votes.
        "</tr>";

    return $data;
}

function getDataCallStatusIcons($status){
    switch($status){
        case "0": $status_icons ='<span class="label label-default_light" title="Not Started"><i class="fa-label-legend status fa fa-times text-default_light" status="'.$status.'"></i></span>';break;
        case "1": $status_icons ='<span class="label label-warning" title="Partial Data"><i class="fa-label-legend status fa fa-wrench" status="'.$status.'"></i></span>';break;
        case "2": $status_icons ='<span class="label label-approved" title="Complete Data"><i class="fa-label-legend status fa fa-check" status="'.$status.'"></i></span>';break;
        case "3": $status_icons ='<span class="label label-default" title="Data Not Available"><i class="fa-label-legend status fa fa-ban" status="'.$status.'"></i></span>';break;
        case "4": $status_icons ='<span class="label label-default" title="Region Not Requested"><i class="fa-label-legend status fa fa-times" status="'.$status.'"></i></span>';break;
        case "9": $status_icons ='<span class="label label-other" title="Other Status"><i class="fa-label-legend status fa fa-question" status="'.$status.'"></i></span>';break;
        default: $status_icons ='<span class="label label-default_light" title="Not Started"><i class="fa-label-legend status fa fa-times text-default_light" status="0"></i></span>';
    }
    return $status_icons;
}

function getTBLCenterUpdatePercentRegions($TBLCenter,$regions,$pastlastreview_dur){
    if(!is_array($regions)){
        $total_centers = 0;
        $total_centers_updated = 0;
        foreach ($TBLCenter as $center){
            if($center['region'] == $regions && ($center['drop_center'] == '' || !in_array($center['drop_center'],$center))){
                $total_centers++;
                if(strtotime($center['last_reviewed_d']) != "" && strtotime($center['last_reviewed_d']) >= strtotime(date('Y-m-d', strtotime("-".$pastlastreview_dur." day")))){
                    $total_centers_updated++;
                }
            }
        }
        //percentage no decimals
        if($total_centers != 0) {
            $region_array = number_format((float)($total_centers_updated / $total_centers * 100), 0, '.', '');
        }
    }else{
        $region_array = array();
        foreach ($regions as $region){
            $total_centers = 0;
            $total_centers_updated = 0;
            foreach ($TBLCenter as $center){
                if($center['region'] == $region['region_code'] && ($center['drop_center'] == '' || !in_array($center['drop_center'],$center))){
                    $total_centers++;
                    if(strtotime($center['last_reviewed_d']) != "" && strtotime($center['last_reviewed_d']) >= strtotime(date('Y-m-d', strtotime("-".$pastlastreview_dur." day")))){
                        $total_centers_updated++;
                    }
                }
            }
            //percentage no decimals
            if($total_centers != 0) {
                $region_array[$region['region_code']] = number_format((float)($total_centers_updated / $total_centers * 100), 0, '.', '');
            }
        }
    }
    return $region_array;
}

function getTBLCenterUpdatePercentLabel($region_tbl_percent){
    $icon = '<i class="fa fa-clock-o"></i>';
    $color = 'label-notupdated';
    if($region_tbl_percent >= 80){
        $icon = '<i class="fa fa-smile-o"></i>';
        $color = 'label-updated';
    }else if($region_tbl_percent <= 79 && $region_tbl_percent >= 50){
        $color = 'label-semiupdated';
    }
    $region_tbl_label = '<span class="badge '.$color.'" style="float: right;">'.$icon.' '.$region_tbl_percent.'%</span>';
    return $region_tbl_label;
}

function adjustColorLightenDarken($color_code,$percentage_adjuster = 0) {
    $percentage_adjuster = round($percentage_adjuster/100,2);
    if(is_array($color_code)) {
        $r = $color_code["r"] - (round($color_code["r"])*$percentage_adjuster);
        $g = $color_code["g"] - (round($color_code["g"])*$percentage_adjuster);
        $b = $color_code["b"] - (round($color_code["b"])*$percentage_adjuster);

        return array("r"=> round(max(0,min(255,$r))),
            "g"=> round(max(0,min(255,$g))),
            "b"=> round(max(0,min(255,$b))));
    }
    else if(preg_match("/#/",$color_code)) {
        $hex = str_replace("#","",$color_code);
        $r = (strlen($hex) == 3)? hexdec(substr($hex,0,1).substr($hex,0,1)):hexdec(substr($hex,0,2));
        $g = (strlen($hex) == 3)? hexdec(substr($hex,1,1).substr($hex,1,1)):hexdec(substr($hex,2,2));
        $b = (strlen($hex) == 3)? hexdec(substr($hex,2,1).substr($hex,2,1)):hexdec(substr($hex,4,2));
        $r = round($r - ($r*$percentage_adjuster));
        $g = round($g - ($g*$percentage_adjuster));
        $b = round($b - ($b*$percentage_adjuster));

        return "#".str_pad(dechex( max(0,min(255,$r)) ),2,"0",STR_PAD_LEFT)
            .str_pad(dechex( max(0,min(255,$g)) ),2,"0",STR_PAD_LEFT)
            .str_pad(dechex( max(0,min(255,$b)) ),2,"0",STR_PAD_LEFT);

    }
}

function searchTBLMissingFields($center){
    $fields = array(0=>'program', 1=>'geocode_lat', 2=>'geocode_lon', 3=>'rural', 4=>'level', 5=>'open_d',
        6=>'close_d', 7=>'nondes_website', 8=>'region', 9=>'country', 10=>'name');
    $missing = '';
    foreach ($fields as $field){
        if(!in_array($center[$field],$center)){
            $missing .= $field.", ";
        }
    }
    return rtrim($missing, ', ');
}

/**
 * Function that searches the armID from a project and returns the data
 * @param $projectID
 * @return array|mixed
 */
function getTablesInfo($module, $pidDataModel){
    $q = $module->query("SELECT * FROM `redcap_events_arms` WHERE project_id = ?",[$pidDataModel]);
    $dataTable = array();
    while ($row = $q->fetch_assoc()){
        $qTable = $module->query("SELECT * FROM `redcap_events_metadata` WHERE arm_id = ?",[$row['arm_id']]);
        while ($rowTable = $qTable->fetch_assoc()){
            $dataTable = generateTableArray($module, $pidDataModel, $dataTable);
        }
    }
    return $dataTable;
}

/**
 * Function that generates an array with the table name and event information
 * @param $event_id, the event identificator
 * @param $projectID, the project we want to search in
 * @param $dataTable, the array we are going to fill up
 * @return mixed, the array $dataTable we are going to fill up
 */
function generateTableArray($module, $pidDataModel, $dataTable){
    $dataFormat = $module->getChoiceLabels('data_format', $pidDataModel);
    $RecordSetTable = \REDCap::getData($pidDataModel, 'array', null);
    $recordsTable = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetTable,$pidDataModel);
    $dataTable['data_format_label'] = $dataFormat;
    foreach( $recordsTable as $data ){
        #we sort the variables by value and keep key
        asort($data['variable_order']);

        if(!empty($data['record_id'])){//Variables
            $dataTable[$data['record_id']] = $data;
        }
    }
    #We order the tables
    ArrayFunctions::array_sort_by_column($dataTable, "table_order");

    return $dataTable;
}

/**
 * Table list with anchor links
 * @param $dataTable
 * @return string
 */
function generateRequestedTablesList($dataTable){
    $requested_tables = "<ol>";
    foreach ($dataTable as $data) {
        if (!empty($data['record_id'])) {
            foreach ($data['variable_order'] as $id=>$value) {
                $record_varname_header = empty($id) ? $data['record_id'] . '_1' : $data['record_id'] . '_' . $id;
                $requested_tables .= "<li record_id='". $record_varname_header ."'  style='display:none;'><a href='#anchor_".$data['record_id']."'>".$data["table_name"]."</a></li>";
                break;
            }
        }
    }
    $requested_tables .= "</ol>";
    return $requested_tables;
}

/**
 * Table list with anchor links for the PDF
 * @param $dataTable
 * @return string
 */
function generateRequestedTablesList_pdf($dataTable,$fieldsSelected){
    $fieldsSelected = explode(',',$fieldsSelected);
    $requested_tables = "<ol>";
    foreach ($dataTable as $data) {
        if (!empty($data['record_id'])) {
            foreach ($fieldsSelected as $field) {
                $recordID = explode("_",$field);
                $field = $recordID[0];
                if ($data['record_id'] == $field) {
                    $requested_tables .= "<li><a href='#anchor_" . $data['record_id'] . "' style='text-decoration:none'>" . $data["table_name"] . "</a></li>";
                    break;
                }
            }
        }
    }
    $requested_tables .= "</ol>";
    return $requested_tables;
}

/**
 * Function that creates HTML tables with the Tables and Variables information to print on screen after the information has been selected
 * @param $dataTable, Tables and Variables information
 * @return string, the html content
 */
function generateTablesHTML_steps($pidCodeList,$dataTable){
    $tableHtml = "";
    foreach ($dataTable as $data) {
        if (!empty($data['record_id'])) {
            $found = false;
            foreach ($data['variable_order'] as $id=>$value) {
                $record_varname = !array_key_exists($id,$data['variable_name'])?$data['variable_name']['']:$data['variable_name'][$id];
                $record_varname_id = empty($id) ? $data['record_id'] . '_1' : $data['record_id'] . '_' . $id;
                #We add the new Header table tags
                if($found == false){
                    $table_draft_text= "";
                    if (array_key_exists('table_status', $data) ) {
                        $table_draft_text = ($data['table_status'] == 0) ?'<span style="color: red;font-style: italic">(DRAFT)</span>':"";
                    }

                    $record_varname_header = empty($id) ? $data['record_id'] . '_1' : $data['record_id'] . '_' . $id;

                    $htmlHeader = '<div class="panel panel-default preview" style="display:none;" record_id="'. $record_varname_header .'"><div class="panel-heading" style="display:none;" record_id="'. $record_varname_header .'" parent_table_header="'.$data['record_id'].'"><span style="font-size:16px"><strong><a href="http://redcap.vumc.org/plugins/iedea/des/index.php?tid='.$data['record_id'].'&page=variables"  name="anchor_'.$data['record_id'].'" target="_blank" style="text-decoration:none" class="label label-as-badge des-'.$data['table_category'].'">'.$data["table_name"].'</span></a> '.$table_draft_text.'</strong> - '.$data['table_definition'];
                    if (array_key_exists('text_top', $data) && !empty($data['text_top']) && $data['text_top'] != ""){
                        $htmlHeader .= '<div  style="border-color: white;font-style: italic;display:none" parent_table_header="'. $data['record_id'] .'">'.$data["text_top"].'</div>';
                    }
                    $htmlHeader .= '</div><table class="table table_requests sortable-theme-bootstrap preview_table step_4_table" parent_table="'.$data['record_id'].'" id="PreviewTable">
                    <tr style="display:none;" parent_table_header="'.$data['record_id'].'">
                        <td style="padding: 5px;width:20%"><strong>Field</strong></td>
                        <td style="padding: 5px;width:20%"><strong>Format</strong></td>
                        <td style="padding: 5px"><strong>Description</strong></td>
                    </tr>';
                    $found = true;
                    $tableHtml .= $htmlHeader;
                }

                $variable_status = "";
                $variable_text = "";
                if (array_key_exists('variable_status', $data) && array_key_exists($id, $data['variable_status'])) {
                    if($data['variable_status'][$id] == "0"){//DRAFT
                        $variable_status = "background-color: #ffffe6;";
                        $variable_text = "<span style='color:red;font-weight:bold'>DRAFT</span><br/>";
                    }else if($data['variable_status'][$id] == "2"){//DEPRECATED
                        $variable_status = "display:none;";
                        $variable_text = "<span style='color:red;font-weight:bold'>DEPRECATED</span><br/>";
                    }
                }

                //If Deprecated, don't show the variable
                if ($data['variable_status'][$id] != "2") {
                    #We add the Content rows
                    $tableHtml .= '<tr record_id="' . $record_varname_id . '" style="' . $variable_status . 'display:none;">
                            <td style="padding: 5px"><a href="http://redcap.vumc.org/plugins/iedea/des/index.php?tid=' . $data['record_id'] . '&vid=' . $id . '&page=variableInfo" target="_blank" style="text-decoration:none">' . $record_varname . '</a></td>
                            <td style="width:160px;padding: 5px">';


                    $dataFormat = $dataTable['data_format_label'][$data['data_format'][$id]];
                    if ($data['has_codes'][$id] == '0') {
                        if (!empty($data['code_text'][$id])) {
                            $dataFormat .= "<br/>" . $data['code_text'][$id];
                        }
                    } else if ($data['has_codes'][$id] == '1') {
                        if (!empty($data['code_list_ref'][$id])) {
                            $codeformat = \REDCap::getData($pidCodeList, 'json-array',array('record_id' => $data['code_list_ref'][$id]),array('code_format','code_list'));

                            if ($codeformat['code_format'] == '1') {
                                $codeOptions = empty($codeformat['code_list']) ? $data['code_text'][$id] : explode(" | ", $codeformat['code_list']);
                                if (!empty($codeOptions[0])) {
                                    $dataFormat .= "<div style='padding-left:15px'>";
                                }
                                foreach ($codeOptions as $option) {
                                    $dataFormat .= $option . "<br/>";
                                }
                                if (!empty($codeOptions[0])) {
                                    $dataFormat .= "</div>";
                                }
                            } else if ($codeformat['code_format'] == '3') {
                                $dataFormat = "Numeric<br/>";
                            }
                        }
                    }

                    $description = empty($data["description"][$id]) ? $data["description"][''] : $data["description"][$id];
                    if (!empty($data['description_extra'][$id])) {
                        $description .= "<br/><em>" . $data['description_extra'][$id] . "</em>";
                    }

                    $tableHtml .= $dataFormat . '</td>
                    <td style="padding: 5px">' . $variable_text . $description . '</td>
                </tr>';
                }
            }
            if($found) {
                $tableHtml .= '</table><span record_id="'. $record_varname_id .'" style="display:none"></span></div>';
                if (array_key_exists('text_bottom', $data) && !empty($data['text_bottom']) && $data['text_bottom'] != ""){
                    $tableHtml .= '<div  style="border-color: white;font-style: italic;display:none;" parent_table="'. $data['record_id'] .'">'.$data["text_bottom"].'</div>';
                }
            }
        }
    }
    return $tableHtml;
}

/**
 * Function that creates HTML tables with the Tables and Variables information to print on the PDF after the information has been selected
 * @param $dataTable, Tables and Variables information
 * @param $fieldsSelected, the selected fields
 * @return string, the html content
 */
function generateTablesHTML_pdf($module, $pidCodeList, $dataTable,$fieldsSelected){
    $fieldsSelected = explode(',',$fieldsSelected);
    $tableHtml = "";
    $table_counter = 0;
    foreach ($dataTable as $data) {
        if (!empty($data['record_id'])) {
            $found = false;
            $htmlCodes = '';
            foreach ($fieldsSelected as $field) {
                $recordID = explode("_",$field);
                $field = $recordID[0];
                $id = ($recordID[1] == '1')? '':$recordID[1];
                if ($data['record_id'] == $field) {
                    $record_varname = !array_key_exists($id,$data['variable_name'])?$data['variable_name']['']:$data['variable_name'][$id];
                    #We add the new Header table tags
                    if($found == false){
                        $table_draft= "background-color: #f0f0f5";
                        $table_draft_tdcolor= "background-color: lightgray";
                        $table_draft_text= "";

                        switch ($data['table_category']){
                            case 'main': $table_draft = "background-color: #FFC000"; break;
                            case 'labs': $table_draft = "background-color: #9cce77"; break;
                            case 'dis': $table_draft = "background-color: #87C1E9"; break;
                            case 'meds': $table_draft = "background-color: #FB8153"; break;
                            case 'preg': $table_draft = "background-color: #D7AEFF"; break;
                            case 'meta': $table_draft = "background-color: #BEBEBE"; break;
                            default:$table_draft = "background-color: #f0f0f5"; break;
                        }
                        if (array_key_exists('table_status', $data) ) {
                            if($data['table_status'] == 0){
                                $table_draft = "background-color: #ffffcc;";
                            }
                            $table_draft_tdcolor = ($data['table_status'] == 0) ? "background-color: #999999;" : "background-color: lightgray";
                            $table_draft_text = ($data['table_status'] == 0) ?'<span style="color: red;font-style: italic">(DRAFT)</span>':"";
                        }

                        $breakLine = '';
                        if($table_counter >0){
                            $breakLine = '<div style="page-break-before: always;"></div>';
                        }
                        $table_counter++;

                        $htmlHeader = $breakLine.'<p style="'.$table_draft.'"><span style="font-size:16px"><strong><a href="http://redcap.vumc.org/plugins/iedea/des/index.php?tid='.$data['record_id'].'&page=variables" name="anchor_'.$data['record_id'].'" target="_blank" style="text-decoration:none">'.$data["table_name"].'</a></span> '.$table_draft_text.'</strong> - '.$data['table_definition'].'</p>';
                        if (array_key_exists('text_top', $data) && !empty($data['text_top']) && $data['text_top'] != ""){
                            $htmlHeader .= '<div  style="border-color: white;font-style: italic">'.$data["text_top"].'</div>';
                        }
                        $htmlHeader .= '<table border ="1px" style="border-collapse: collapse;width: 100%;">
                        <tr style="'.$table_draft_tdcolor.'">
                            <td style="padding: 5px;width:30%">Field</td>
                            <td style="padding: 5px">Format</td>
                            <td style="padding: 5px">Description</td>
                        </tr>';
                        $found = true;
                        $tableHtml .= $htmlHeader;
                    }

                    $variable_status = "";
                    $variable_text = "";
                    if (array_key_exists('variable_status', $data) && array_key_exists($id, $data['variable_status'])) {
                        if($data['variable_status'][$id] == "0"){//DRAFT
                            $variable_status = "style='background-color: #ffffe6;'";
                            $variable_text = "<span style='color:red;font-weight:bold'>DRAFT</span><br/>";
                        }else if($data['variable_status'][$id] == "2"){//DEPRECATED
                            $variable_status = "style='display:none'";
                            $variable_text = "<span style='color:red;font-weight:bold'>DEPRECATED</span><br/>";
                        }
                    }

                    #We add the Content rows
                    $tableHtml .='<tr record_id="'.$data["record_id"].'" '.$variable_status.'>
                                <td style="padding: 5px"><a href="http://redcap.vumc.org/plugins/iedea/des/index.php?tid='.$data['record_id'].'&vid='.$id.'&page=variableInfo" target="_blank" style="text-decoration:none">'.$record_varname.'</a></td>
                                <td style="width:160px;padding: 5px">';

                    $dataFormat = $dataTable['data_format_label'][$data['data_format'][$id]];
                    if ($data['has_codes'][$id] == '0') {
                        if (!empty($data['code_text'][$id])) {
                            $dataFormat .= "<br/>".$data['code_text'][$id];
                        }
                    } else if ($data['has_codes'][$id] == '1') {
                        if(!empty($data['code_list_ref'][$id])){
                            $codeformat = \REDCap::getData($pidCodeList, 'json-array',array('record_id' => $data['code_list_ref'][$id]),array('code_format','code_list','code_file'));

                            if ($codeformat['code_format'] == '1') {
                                $codeOptions = empty($codeformat['code_list']) ? $data['code_text'][$id] : explode(" | ", ProjectData::replaceSymbolsForPDF($codeformat['code_list']));
                                if (!empty($codeOptions[0])) {
                                    $dataFormat .= "<div style='padding-left:15px'>";
                                }
                                foreach ($codeOptions as $option) {
                                    $dataFormat .= $option . "<br/>";
                                }
                                if (!empty($codeOptions[0])) {
                                    $dataFormat .= "</div>";
                                }
                            } else if ($codeformat['code_format'] == '3') {
                                $dataFormat = "Numeric<br/>";
                                if (array_key_exists('code_file', $codeformat) && $data['codes_print'][$id] =='1') {
                                    $htmlCodes .= "<table  border ='0' style='width: 100%;display:none' record_id='".$record_varname."'><tr><td><strong>".$data['variable_name'][$id]." code list:</strong><br/></td></tr></table>".getHtmlCodesTable($module, $codeformat['code_file'], $htmlCodes,$record_varname);
                                }
                            }
                        }
                    }

                    $description = empty($data["description"][$id]) ? $data["description"][''] : $data["description"][$id];
                    if (!empty($data['description_extra'][$id])) {
                        $description .= "<br/><em>" . $data['description_extra'][$id] . "</em>";
                    }

                    $tableHtml .= $dataFormat . '</td>
                        <td style="padding: 5px">'.$variable_text.$description. '</td>
                    </tr>';
                }
            }
            if($found) {
                $tableHtml .= "</table><br/>";
                if (array_key_exists('text_bottom', $data) && !empty($data['text_bottom']) && $data['text_bottom'] != ""){
                    $tableHtml .= '<p  style="border-color: white;font-style: italic">'.$data["text_bottom"].'</p><br/>';
                }
            }
            if(!empty($htmlCodes))
                $tableHtml .= $htmlCodes.'<br/>';
        }
    }
    return $tableHtml;
}

/**
 * Function that parses the CVS file and transforms the content into a table
 * @param $code_file, the code in the db of the csv file
 * @param $htmlCodes, the html table with the content
 * @return string, the html table with the content
 */
function getHtmlCodesTable($module, $code_file, $htmlCodes, $id){
    $csv = parseCSVtoArray($module, $code_file);
    if(!empty($csv)) {
        $htmlCodes = '<table border="1px" style="border-collapse: collapse;display:none;" record_id="'. $id .'">';
        foreach ($csv as $header => $content) {
            $htmlCodes .= '<tr style="border: 1px solid #000;">';
            foreach ($content as $col => $value) {
                #Convert to UTF-8 to avoid weird characters
                $value = mb_convert_encoding($value,'UTF-8','HTML-ENTITIES');
                if ($header == 0) {
                    $htmlCodes .= '<td>' . $col . '</td>';
                } else {
                    $htmlCodes .= '<td>' . $value . '</td>';
                }
            }
            $htmlCodes .= '</tr>';
        }
        $htmlCodes .= '</table>';
    }
    return $htmlCodes;
}

/***METRICS***/
function getRegionalAndMR($pidExtraOutputs, $conceptsData,$type, $regionalmrdata,$startyear,$output_type){
    $currentYear = date("Y");
    $regionalmrdata['r'] = array();
    $regionalmrdata['mr'] = array();
    $regionalmrdata['mrw'] = array();
    $regionalmrdata['outputsAll'] = array();
    $concept_outputs_by_year = array();
    ${"years_label_regional_pubs_".$type} = array();

    if($startyear != "") {
        for ($year = $startyear; $year <= $currentYear; $year++) {
            array_push(${"years_label_regional_pubs_".$type}, $year);

            $RecordSetExtraOutputsSingleReg = \REDCap::getData($pidExtraOutputs, 'array', null, null, null, null, false, false, false, "[output_year] = '" . $year . "' AND [output_type] = '" . $output_type . "' AND [producedby_region] = '1'");
            array_push($regionalmrdata['r'], count(ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetExtraOutputsSingleReg,$pidExtraOutputs)));
            $RecordSetExtraOutMultipleReg = \REDCap::getData($pidExtraOutputs, 'array', null, null, null, null, false, false, false, "[output_year] = '" . $year . "' AND [output_type] = '" . $output_type . "' AND [producedby_region] = '2'");
            array_push($regionalmrdata['mrw'], count(ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetExtraOutMultipleReg,$pidExtraOutputs)));
            ${"outputs_mrw_" . $type} = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetExtraOutMultipleReg,$pidExtraOutputs);
            $RecordSetExtraOutputs = \REDCap::getData($pidExtraOutputs, 'array', null, null, null, null, false, false, false, "[output_year] = '" . $year . "' AND [output_type] = '" . $output_type . "'");
            array_push($regionalmrdata['outputsAll'], count(ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetExtraOutputs,$pidExtraOutputs)));

            $regionalmrdata['mr'][$year] = 0;
            foreach ($conceptsData as $concepts) {
                if (is_array($concepts['output_year'])) {
                    foreach ($concepts['output_year'] as $index => $output) {
                        if ($output == $year) {
                            if ($concepts['output_type'][$index] == '' || $concepts['output_type'][$index] == '1' && $type == 'manuscripts') {
                                $regionalmrdata['mr'][$year] += 1;
                            } else if ($concepts['output_type'][$index] == '2' && $type == 'abstracts') {
                                $regionalmrdata['mr'][$year] += 1;
                            }
                            if ($concepts['output_venue'][$index] != "") {
                                if ($concepts['output_type'][$index] == "1" && $type == 'manuscripts') {
                                    $concept_outputs_by_year[$year][$concepts['output_venue'][$index]] += 1;
                                } else if ($concepts['output_type'][$index] == "2" && $type == 'abstracts') {
                                    $concept_outputs_by_year[$year][$concepts['output_venue'][$index]] += 1;
                                }
                            }else{
                                $concept_outputs_by_year[$year]["<em>Unknown</em>"] += 1;
                            }
                        }
                    }
                }
            }
            foreach (${'outputs_mrw_' . $type} as $outmanu) {
                $concept_outputs_by_year[$year][$outmanu['output_venue']] += 1;
            }
            if (!array_key_exists($year, $concept_outputs_by_year)) {
                $concept_outputs_by_year[$year]['None'] = 0;
            }
        }
        krsort($concept_outputs_by_year);

        $regionalmrdata['mr'] = array_values($regionalmrdata['mr']);
        $regionalmrdata['outputs'] = $concept_outputs_by_year;
        $regionalmrdata['years'] = ${"years_label_regional_pubs_" . $type};
    }
    return $regionalmrdata;
}

function getDataRMRTable($concept_outputs_by_year,$type){
    ${"data_".$type} = "";
    ${"data_".$type."_total"} = 0;
    ${"data_".$type."_venue"} = array();

    foreach ($concept_outputs_by_year as $year=>$venue_year){
        ${'data_'.$type} .= "<tr><td style='text-align: center'>".$year."</td>";
        $total_out = 0;
        $total_venue = "";
        arsort($venue_year);
        foreach ($venue_year as $venue=>$total){
            $total_out += $total;
            if($venue == "None"){
                $total_venue = "<i>None</i>";
            }else{
                $total_venue .= $venue." (".$total."), ";
                ${'data_'.$type.'_venue'}[$venue] += $total;
            }

            ${'data_'.$type.'_total'} += $total;
        }
        ${'data_'.$type} .="<td style='text-align: center'>".$total_out."</td><td>".rtrim($total_venue,', ')."</td>";

        ${'data_'.$type} .= "</tr>";

        arsort(${'data_'.$type.'_venue'});
        $list = "";
        foreach(${'data_'.$type.'_venue'} as $venue=>$total){
            $list .= $venue." (".$total."), ";
        }
    }
    ${'data_'.$type} .= "<tr style='background-color: aliceblue'>
                    <td style='text-align: center'>Total</td>
                    <td style='text-align: center'>".${'data_'.$type.'_total'}."</td>
                    <td>".rtrim($list,", ")."</td>
                </tr>";

    $data = array();
    $data['content'] = ${"data_".$type};
    $data['total'] = ${"data_".$type."_total"};
    return $data;
}

function implode_key_and_value($array){
    $output = implode(', ', array_map(
        function ($v, $k) {return sprintf("%s (%s)", $k, $v);},
        $array,
        array_keys($array)
    ));
    return $output;
}

function startTest($encryptedCode, $secret_key, $secret_iv, $timestamp){
    $code = getCrypt($encryptedCode,"d",$secret_key,$secret_iv);
    if($code == "start_".$timestamp){
        return true;
    }
    return false;
}

function getConceptStatusIcon($value,$text)
{
    if ($value != "") {
        switch ($value) {
            case "1":
                $icon = "fa-search";
                break;
            case "2":
                $icon = "fa-wrench";
                break;
            case "3":
                $icon = "fa-paper-plane";
                break;
            case "4":
                $icon = "fa-check";
                break;
            case "5":
                $icon = "fa-check";
                break;
            default:
                $icon = "fa-times";
                break;
        }
        return '<a href="#" data-toggle="tooltip" title="' . $text . '" data-placement="top" class="custom-tooltip" style="vertical-align: -2px;"><span class="label concept_status_' . $value . '"><i class="fa ' . $icon . '" aria-hidden="true"></i></span></a>';
    }else{
            return "";
    }
}

function getGradientColor($scolor,$ecolor,$totalColors,$iteration){
    $startColor = hexdec($scolor);
    $endColor = hexdec($ecolor);

    $theColorBegin = (($startColor >= 0x000000) && ($startColor <= 0xffffff)) ? $startColor : 0x000000;
    $theColorEnd = (($endColor >= 0x000000) && ($endColor <= 0xffffff)) ? $endColor : 0xffffff;
    $theNumSteps = (($totalColors > 0) && ($totalColors < 256)) ? $totalColors : 16;

    $theR0 = ($theColorBegin & 0xff0000) >> 16;
    $theG0 = ($theColorBegin & 0x00ff00) >> 8;
    $theB0 = ($theColorBegin & 0x0000ff) >> 0;

    $theR1 = ($theColorEnd & 0xff0000) >> 16;
    $theG1 = ($theColorEnd & 0x00ff00) >> 8;
    $theB1 = ($theColorEnd & 0x0000ff) >> 0;

    $theR = interpolate($theR0, $theR1, $iteration, $theNumSteps);
    $theG = interpolate($theG0, $theG1, $iteration, $theNumSteps);
    $theB = interpolate($theB0, $theB1, $iteration, $theNumSteps);

    $theVal = ((($theR << 8) | $theG) << 8) | $theB;

    return sprintf("#%06X",$theVal);
}

function interpolate($pBegin, $pEnd, $pStep, $pMax) {
    if ($pBegin < $pEnd) {
        return (($pEnd - $pBegin) * ($pStep / $pMax)) + $pBegin;
    } else {
        return (($pBegin - $pEnd) * (1 - ($pStep / $pMax))) + $pEnd;
    }
}

function getDataTable($project_id){
    return method_exists('\REDCap', 'getDataTable') ? \REDCap::getDataTable($project_id) : "redcap_data";
}
?>
