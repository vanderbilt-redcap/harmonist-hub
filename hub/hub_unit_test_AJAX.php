<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once dirname(dirname(__FILE__))."/projects.php";

$settings = \REDCap::getData($pidsArray['SETTINGS'], 'json-array', null, array('hub_name'))[0];

$timestamp = strtotime(date("Y-m-d H:i:s"));
$_SESSION[$settings['hub_name'].$pidsArray['PROJECTS']."_unit_test_timestamp"] = $timestamp;
$codeCrypt = \Vanderbilt\HarmonistHubExternalModule\getCrypt("start_".$timestamp,'e',$secret_key,$secret_iv);

echo json_encode($codeCrypt);
?>