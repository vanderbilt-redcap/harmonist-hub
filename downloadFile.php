<?php
use Vanderbilt\HarmonistHubExternalModule\ProjectData;
require_once "projects.php";

$code = \Functions\getCrypt($_REQUEST['code'],"d",$secret_key,$secret_iv);
$exploded = array();
parse_str($code, $exploded);

$filename = $exploded['file'];
$sname = $exploded['sname'];

$RecordSetPeople = \REDCap::getData(IEDEA_PEOPLE, 'array', array('record_id' => $exploded['pid']));
$current_user = ProjectData::getProjectInfoArray($RecordSetPeople)[0];

if($current_user != "") {
    $record = $module->framework->addAutoNumberedRecord(IEDEA_FILELIBRARY);
    $Proj = new \Project(IEDEA_FILELIBRARY);
    $event_id = $Proj->firstEventId;
    $recordFileL = array();
    $recordFileL[$record][$event_id]['library_item_id'] = $exploded['id'];
    $recordFileL[$record][$event_id]['library_edoc'] = $exploded['edoc'];
    $recordFileL[$record][$event_id]['library_download_d'] = date('Y-m-d H:i:s');
    $recordFileL[$record][$event_id]['library_download_person'] = $exploded['pid'];
    $recordFileL[$record][$event_id]['library_download_region'] = $current_user['person_region'];
    $results = \Records::saveData(IEDEA_FILELIBRARY, 'array', $recordFileL,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
    \Records::addRecordToRecordListCache(IEDEA_FILELIBRARY, $record, 1);
}

header('Content-type: application/pdf');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Content-Transfer-Encoding: binary');
header('Accept-Ranges: bytes');
@readfile($module->framework->getSafePath($sname, EDOC_PATH));
?>