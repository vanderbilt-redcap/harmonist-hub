<?php
define('NOAUTH',true);
require_once dirname(dirname(__FILE__))."/projects.php";

$record = $_REQUEST['record'];
$Proj = new \Project($pidsArray['SOP']);
$event_id = $Proj->firstEventId;

$arraySOP = array();
$arraySOP[$record][$event_id]['sop_visibility'] = "2";
$results = \Records::saveData($pidsArray['SOP'], 'array', $arraySOP,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
\Records::addRecordToRecordListCache($pidsArray['SOP'], $record,1);
?>