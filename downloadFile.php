<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once "projects.php";
use Vanderbilt\HarmonistHubExternalModule\ProjectData;

$code = getCrypt($_REQUEST['code'],"d",$secret_key,$secret_iv);
$exploded = array();
parse_str($code, $exploded);

$filename = $exploded['file'];
$sname = $exploded['sname'];
$extension = pathinfo($filename, PATHINFO_EXTENSION);

$current_user = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array('record_id' => $exploded['pid']))[0];

if($module->getProjectId() == "203280"){
    echo "<pre> ".var_dump($exploded)."</pre>";
    echo "extension<pre> ".var_dump($extension)."</pre>";
}
if($current_user != "") {
    $record = $module->framework->addAutoNumberedRecord($pidsArray['FILELIBRARY']);
    $Proj = new \Project($pidsArray['FILELIBRARY']);
    $event_id = $Proj->firstEventId;
    $recordFileL = array();
    $recordFileL[$record][$event_id]['library_item_id'] = $exploded['id'];
    $recordFileL[$record][$event_id]['library_edoc'] = $exploded['edoc'];
    $recordFileL[$record][$event_id]['library_download_d'] = date('Y-m-d H:i:s');
    $recordFileL[$record][$event_id]['library_download_person'] = $exploded['pid'];
    $recordFileL[$record][$event_id]['library_download_region'] = $current_user['person_region'];
    $results = \Records::saveData($pidsArray['FILELIBRARY'], 'array', $recordFileL,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
    \Records::addRecordToRecordListCache($pidsArray['FILELIBRARY'], $record, 1);
}

header('Content-type: application/'.$extension);
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Content-Transfer-Encoding: binary');
header('Accept-Ranges: bytes');
@readfile($module->framework->getSafePath(EDOC_PATH.$sname, EDOC_PATH));
?>