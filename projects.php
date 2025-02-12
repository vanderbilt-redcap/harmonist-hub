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
include_once(__DIR__ . "/classes/UserEditConditions.php");
include_once(__DIR__ . "/classes/SecurityHandler.php");

REDCapManagement::getEnvironment();

if(!$isCron) {
    if($module == null) {
        $module = $this;
    }

    #Mapper Project
    $hub_mapper = $module->getProjectSetting('hub-mapper');
    $project_id_main = ($hub_mapper != '') ? $hub_mapper : (int)$_GET['pid'];

    #Get Projects ID's
    $pidsArray = REDCapManagement::getPIDsArray($project_id_main);

    if(APP_PATH_WEBROOT[0] == '/'){
        $APP_PATH_WEBROOT_ALL = substr(APP_PATH_WEBROOT, 1);
    }
    define('APP_PATH_WEBROOT_ALL',APP_PATH_WEBROOT_FULL.$APP_PATH_WEBROOT_ALL);
    define('APP_PATH_PLUGIN',APP_PATH_WEBROOT_FULL."external_modules/".substr(__DIR__,strlen(dirname(__DIR__))+1));
    $versionsByPrefix = $module->getEnabledModules($_GET['pid']);
    $app_path_module = APP_PATH_WEBROOT_FULL."modules/harmonist-hub_".$versionsByPrefix['harmonist-hub'];
    define('APP_PATH_MODULE',$app_path_module);
    define('DATEICON',APP_PATH_WEBROOT.'Resources/images/date.png');

    $secret_key = "";
    $secret_iv = "";
    $encrypt_path = $module->getSecurityHandler()->getCredentialsServerVars("ENCRYPTION");
    if($encrypt_path != null)
        require_once ($encrypt_path);

    $settings = $module->getSecurityHandler()->getSettingsData($pidsArray['SETTINGS']);

    $default_values = new ProjectData;
    $default_values_settings = $default_values->getDefaultValues($pidsArray['SETTINGS']);
}