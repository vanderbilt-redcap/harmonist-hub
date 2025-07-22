<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once "projects.php";
use Vanderbilt\HarmonistHubExternalModule\ProjectData;

$code = getCrypt($_REQUEST['code'],"d",$secret_key,$secret_iv);
$exploded = array();
parse_str($code, $exploded);

$filename = "";
if(array_key_exists('file',$exploded)){
    $filename = $exploded['file'];
}
$pid = "";
$current_user = "";
if(array_key_exists('pid',$exploded)){
    $pid = $exploded['pid'];
    $current_user = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array('record_id' => $pid))[0];
}
$sname = "";
if(array_key_exists('sname',$exploded)){
    $sname = $exploded['sname'];
}
$extension = pathinfo($filename, PATHINFO_EXTENSION);

if($current_user != "") {
    $record = $module->framework->addAutoNumberedRecord($pidsArray['FILELIBRARYDOWN']);
    $Proj = new \Project($pidsArray['FILELIBRARYDOWN']);
    $event_id = $Proj->firstEventId;
    $recordFileL = array();
    $recordFileL[$record][$event_id]['library_item_id'] = arrayKeyExistsReturnValue($exploded,['id']);
    $recordFileL[$record][$event_id]['library_edoc'] = arrayKeyExistsReturnValue($exploded,['edoc']);
    $recordFileL[$record][$event_id]['library_download_d'] = date('Y-m-d H:i:s');
    $recordFileL[$record][$event_id]['library_download_person'] = arrayKeyExistsReturnValue($exploded,['pid']);
    $recordFileL[$record][$event_id]['library_download_region'] = $current_user['person_region'];
    $results = \Records::saveData($pidsArray['FILELIBRARYDOWN'], 'array', $recordFileL,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
}
header('Content-type: application/'.$extension);
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Content-Transfer-Encoding: binary');
header('Accept-Ranges: bytes');
@readfile($module->framework->getSafePath(EDOC_PATH.$sname, EDOC_PATH));


?>