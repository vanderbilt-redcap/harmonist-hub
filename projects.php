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

REDCapManagement::getEnvironment();

#Mapper Project
$project_id_main = ($project_id != '')?$project_id:(int)$_GET['pid'];
#Get Projects ID's
$pidsArray = REDCapManagement::getPIDsArray($project_id_main);

if(APP_PATH_WEBROOT[0] == '/'){
    $APP_PATH_WEBROOT_ALL = substr(APP_PATH_WEBROOT, 1);
}
define('APP_PATH_WEBROOT_ALL',APP_PATH_WEBROOT_FULL.$APP_PATH_WEBROOT_ALL);
define('APP_PATH_PLUGIN',APP_PATH_WEBROOT_FULL."external_modules/".substr(__DIR__,strlen(dirname(__DIR__))+1));
define('APP_PATH_MODULE',APP_PATH_WEBROOT_FULL."modules/".substr(__DIR__,strlen(dirname(__DIR__))+1));
define('DATEICON',APP_PATH_WEBROOT.'Resources/images/date.png');

//$projects = \REDCap::getData(array('project_id'=>$pidsArray['PROJECTS']),'array');

$secret_key="";
$secret_iv="";

if($module == null)
    $module = $this;

$settings = \REDCap::getData($pidsArray['SETTINGS'], 'json-array', null)[0];
if(!empty($settings)){
    $settings = $module->escape($settings);
}else{
    $settings = htmlspecialchars($settings,ENT_QUOTES);
}

#Escape name just in case they add quotes
if(!empty($settings["hub_name"])) {
    $settings["hub_name"] = addslashes($settings["hub_name"]);
}
#Sanitize text title and descrition for pages
$settings = ProjectData::sanitizeALLVariablesFromInstrument($module,$pidsArray['SETTINGS'],array(0=>"harmonist_text"),$settings);

$default_values = new ProjectData;
$default_values_settings = $default_values->getDefaultValues($pidsArray['SETTINGS']);