<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include_once(__DIR__ ."/../projects.php");
include_once(__DIR__ . "/../classes/HubUpdates.php");

$option = $_REQUEST['option'];
$message = "";
if($option == "create"){
    foreach ($pidsArray as $constant => $project_id){
        ProjectData::updateThemeOnSurveys($module, $constant, $pidsArray);
    }
    $message = "&message=T";
}else if($option == "dismiss"){
    $module->setProjectSetting('hub-updates-show-theme-msg', "false");
}

header("location:".$module->getUrl('hub-updates/index.php').$message);
?>