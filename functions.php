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


?>