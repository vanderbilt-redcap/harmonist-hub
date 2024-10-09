<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once dirname(dirname(__FILE__))."/projects.php";

$user = getCrypt($_REQUEST['code'],'d',$secret_key,$secret_iv);
$upload_user = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array('record_id' => $user),array('access_token','email','firstname','lastname','person_region'))[0];

$token = getRandomIdentifier(12);

$settings = \REDCap::getData($pidsArray['SETTINGS'], 'json-array', null,array('uploadtokenexpiration_ts'))[0];

$Proj = new \Project($pidsArray['DATATOOLUPLOADSECURITY']);
$event_id = $Proj->firstEventId;
$recordSecurity = array();

#Don't skip unchanged values
$recordSecurity[$token][$event_id]['record_id'] = $token;
$recordSecurity[$token][$event_id]['ext_toolname'] = "data";
$recordSecurity[$token][$event_id]['uploadtoken_ts'] = date('Y-m-d H:i:s');
$recordSecurity[$token][$event_id]['tokenexpiration_ts'] = date('Y-m-d', strtotime("+".$settings['uploadtokenexpiration_ts']." day"));
$recordSecurity[$token][$event_id]['uploadhub_token'] = $upload_user['access_token'];
$recordSecurity[$token][$event_id]['uploaduser_id'] = $user;
$recordSecurity[$token][$event_id]['uploaduser_email'] = $upload_user['email'];
$recordSecurity[$token][$event_id]['uploaduser_firstname'] = $upload_user['firstname'];
$recordSecurity[$token][$event_id]['uploaduser_lastname'] = $upload_user['lastname'];
$recordSecurity[$token][$event_id]['uploadregion_id'] = $upload_user['person_region'];

$results = \Records::saveData($pidsArray['DATATOOLUPLOADSECURITY'], 'array', $recordSecurity,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
\Records::addRecordToRecordListCache($pidsArray['DATATOOLUPLOADSECURITY'], $token, 1);

$tokendt = getCrypt($token,'e',$secret_key,$secret_iv);

echo json_encode($tokendt);
?>