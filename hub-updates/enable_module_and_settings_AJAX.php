<?php

namespace Vanderbilt\HarmonistHubExternalModule;

include_once(__DIR__ . "/../projects.php");
include_once(__DIR__ . "/../classes/HubUpdates.php");

$pid = $_REQUEST['pid'];

ProjectData::checkIfModuleIsEnabledOnProjects($module, $pidsArray, $pid, true);
$message = "&message=E";

header("location:" . $module->getUrl('hub-updates/index.php') . $message);
?>

