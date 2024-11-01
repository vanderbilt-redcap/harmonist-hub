<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include_once(__DIR__ ."/../projects.php");
include_once(__DIR__ . "/../classes/HubUpdates.php");

$option = $_REQUEST['option'];
$message = "";
if($option == "update"){
    ProjectData::checkIfSurveysAreActivated($module, $pidsArray, true);
    $message = "&message=V";
}

header("location:".$module->getUrl('hub-updates/index.php').$message);
?>