<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once dirname(dirname(__FILE__))."/projects.php";

$record = $_REQUEST['upload_record'];
$assoc_concept = $_REQUEST['assoc_concept'];
$user = $_REQUEST['user'];

$concept_sheet = '';
$concept_title = '';
if(!empty($assoc_concept)){
    $RecordSetConcepts = \REDCap::getData($pidsArray['HARMONIST'], 'array', array('record_id' => $assoc_concept));
    $concept = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConcepts)[0];
    $concept_sheet = $concept['concept_id'];
    $concept_title = $concept['concept_title'];
}

$RecordSetPeopleDown = \REDCap::getData($pidsArray['PEOPLE'], 'array', array('record_id' => $user));
$upload_user = ProjectData::getProjectInfoArray($RecordSetPeopleDown)[0];

$token = \Vanderbilt\HarmonistHubExternalModule\getRandomIdentifier(12);

$Proj = new \Project($pidsArray['DATATOOLUPLOADSECURITY']);
$event_id = $Proj->firstEventId;
$recordSecurity = array();

#Don't skip unchanged values
$recordSecurity[$token][$event_id]['record_id'] = $token;
$recordSecurity[$token][$event_id]['uploadtoken_ts'] = date('Y-m-d H:i:s');
$recordSecurity[$token][$event_id]['tokenexpiration_ts'] = date('Y-m-d', strtotime("+".$settings['uploadtokenexpiration_ts']." day"));
$recordSecurity[$token][$event_id]['uploadhub_token'] = $upload_user['access_token'];

$recordSecurity[$token][$event_id]['datacall_id'] = $record;
$recordSecurity[$token][$event_id]['uploadconcept_mr'] = $concept_sheet;
$recordSecurity[$token][$event_id]['uploadconcept_title'] = $concept_title;
$recordSecurity[$token][$event_id]['uploadconcept_id'] = $assoc_concept;
$recordSecurity[$token][$event_id]['uploaduser_id'] = $user;
$recordSecurity[$token][$event_id]['uploaduser_email'] = $upload_user['email'];
$recordSecurity[$token][$event_id]['uploaduser_firstname'] = $upload_user['firstname'];
$recordSecurity[$token][$event_id]['uploaduser_lastname'] = $upload_user['lastname'];
$recordSecurity[$token][$event_id]['uploadregion_id'] = $upload_user['person_region'];

$results = \Records::saveData($pidsArray['DATATOOLUPLOADSECURITY'], 'array', $recordSecurity,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
\Records::addRecordToRecordListCache($pidsArray['DATATOOLUPLOADSECURITY'], $token, 1);

$tokendt = \Vanderbilt\HarmonistHubExternalModule\getCrypt($token,'e',$secret_key,$secret_iv);

echo json_encode($tokendt);
?>