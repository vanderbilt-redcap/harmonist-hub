<?php
namespace Vanderbilt\HarmonistHubExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

require_once (__DIR__ . '/vendor/autoload.php');
include_once(__DIR__ . "/email.php");
include_once(__DIR__ . "/functions.php");
include_once(__DIR__ . "/classes/REDCapManagement.php");
include_once(__DIR__ . "/classes/ArrayFunctions.php");
include_once(__DIR__ . "/classes/ProjectData.php");
include_once(__DIR__ . "/classes/HubData.php");
include_once(__DIR__ . "/classes/ExcelFunctions.php");
include_once(__DIR__ . "/classes/CopyJSON.php");
include_once(__DIR__ . "/classes/UserEditConditions.php");

REDCapManagement::getEnvironment();

#Mapper Project
$project_id_main = ($project_id != '')?$project_id:(int)$_GET['pid'];
error_log("runCronDataUploadNotification - projects - 3 project_id_main:".$project_id_main);
#Get Projects ID's
$pidsArray = REDCapManagement::getPIDsArray($project_id_main);
error_log("runCronDataUploadNotification - projects - 4");

if(APP_PATH_WEBROOT[0] == '/'){
    $APP_PATH_WEBROOT_ALL = substr(APP_PATH_WEBROOT, 1);
}
define('APP_PATH_WEBROOT_ALL',APP_PATH_WEBROOT_FULL.$APP_PATH_WEBROOT_ALL);
define('APP_PATH_PLUGIN',APP_PATH_WEBROOT_FULL."external_modules/".substr(__DIR__,strlen(dirname(__DIR__))+1));
define('APP_PATH_MODULE',APP_PATH_WEBROOT_FULL."modules/".substr(__DIR__,strlen(dirname(__DIR__))+1));
define('DATEICON',APP_PATH_WEBROOT.'Resources/images/date.png');

//$projects = \REDCap::getData(array('project_id'=>$pidsArray['PROJECTS']),'array');

//$secret_key="";
//$secret_iv="";
if(ENVIRONMENT != "DEV") {
    require_once "/app001/credentials/Harmonist-Hub/" . $project_id_main . "_down_crypt.php";
}

if($module == null && !$isCron)
    $module = $this;
error_log("runCronDataUploadNotification - projects - 5");
$settings = \REDCap::getData($pidsArray['SETTINGS'], 'json-array', null)[0];
if(!empty($settings)){
    $settings = $module->escape($settings);
}else{
    $settings = htmlspecialchars($settings,ENT_QUOTES);
}
error_log("runCronDataUploadNotification - projects - 6");
#Escape name just in case they add quotes
if(!empty($settings["hub_name"])) {
    $settings["hub_name"] = addslashes($settings["hub_name"]);
}
error_log("runCronDataUploadNotification - projects - 7");
#Sanitize text title and descrition for pages
$settings = ProjectData::sanitizeALLVariablesFromInstrument($module,$pidsArray['SETTINGS'],array(0=>"harmonist_text"),$settings);
error_log("runCronDataUploadNotification - projects - 8");
$default_values = new ProjectData;
$default_values_settings = $default_values->getDefaultValues($pidsArray['SETTINGS']);
error_log("runCronDataUploadNotification - projects - 9");