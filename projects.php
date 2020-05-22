<?php

use Vanderbilt\Victrlib\Env;
# Define the environment: options include "DEV", "TEST" or "PROD"
if (is_file('/app001/www/redcap/plugins/victrlib/src/Env.php'))
    include_once('/app001/www/redcap/plugins/victrlib/src/Env.php');

if (class_exists("\\Vanderbilt\\Victrlib\\Env")) {

    if (Env::isProd()) {
        define("ENVIRONMENT", "PROD");
    } else if (Env::isStaging()) {
        define("ENVIRONMENT", "TEST");
    }else{
        define("ENVIRONMENT", "DEV");
    }
}
else {
    define("ENVIRONMENT", "DEV");
}

#Mapper Project
$project_id_main = ($project_id != '')?$project_id:$_GET['pid'];
define(ENVIRONMENT.'_IEDEA_PROJECTS', $project_id_main);

if(defined(ENVIRONMENT."_IEDEA_PROJECTS")) {
    define("IEDEA_PROJECTS", constant(ENVIRONMENT."_IEDEA_PROJECTS"));
}

if(APP_PATH_WEBROOT[0] == '/'){
    $APP_PATH_WEBROOT_ALL = substr(APP_PATH_WEBROOT, 1);
}
define('APP_PATH_WEBROOT_ALL',APP_PATH_WEBROOT_FULL.$APP_PATH_WEBROOT_ALL);
define('APP_PATH_PLUGIN',APP_PATH_WEBROOT_FULL."external_modules/".substr(__DIR__,strlen(dirname(__DIR__))+1));
define('APP_PATH_MODULE',APP_PATH_WEBROOT_FULL."modules/".substr(__DIR__,strlen(dirname(__DIR__))+1));

# Define the projects stored in MAPPER
$module->setProjectConstants(IEDEA_PROJECTS);
//$projects = \REDCap::getData(array('project_id'=>IEDEA_PROJECTS),'array');
//
//$linkedProjects = array();
//foreach ($projects as $event){
//    foreach ($event as $project) {
//        define(ENVIRONMENT . '_IEDEA_' . $project['project_constant'], $project['project_id']);
//        array_push($linkedProjects,"IEDEA_".$project['project_constant']);
//    }
//}
//
//# Define the environment for each project
//foreach($linkedProjects as $projectTitle) {
//    if(defined(ENVIRONMENT."_".$projectTitle)) {
//        define($projectTitle, constant(ENVIRONMENT."_".$projectTitle));
//
//    }
//}
$secret_key="";
$secret_iv="";
include_once __DIR__ ."/Passthru.php";
require_once (__DIR__ . '/vendor/autoload.php');
include_once(__DIR__ . "/email.php");
include_once("functions.php");

$RecordSetSettings = \REDCap::getData(IEDEA_SETTINGS, 'array', null);
$settings = getProjectInfoArray($RecordSetSettings)[0];

