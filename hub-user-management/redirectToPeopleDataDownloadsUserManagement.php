<?php
namespace Vanderbilt\HarmonistHubExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
use Vanderbilt\HarmonistHubExternalModule\REDCapManagement;

#Redirect to The People's project to the Data Downloads User Management page
$hub_mapper = $module->getProjectSetting('hub-mapper');
$pidsArray = REDCapManagement::getPIDsArray($hub_mapper);
$url = preg_replace('/pid=(\d+)/', "pid=".$pidsArray['PEOPLE'],$module->getUrl('hub-user-management/data_downloads_user_management.php'));

header("Location: " . $url);