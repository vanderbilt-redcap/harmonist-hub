<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include_once(__DIR__ ."/../projects.php");
include_once(__DIR__ . "/../classes/HubREDCapUsers.php");

$users = HubREDCapUsers::getUserInfoAutocomplete($module, $_REQUEST['term']);
echo $users;
?>