<?php
define('NOAUTH',true);
require_once dirname(dirname(__FILE__))."/projects.php";

$record = $_REQUEST['index_modal_delete'];

$Proj = new \Project(IEDEA_SOP);
$event_id = $Proj->firstEventId;

$arraySOP = array();
$arraySOP[$record][$event_id]['sop_active'] = "0";
$results = \Records::saveData(IEDEA_SOP, 'array', $arraySOP,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
\Records::addRecordToRecordListCache(IEDEA_SOP, $record,1);
?>