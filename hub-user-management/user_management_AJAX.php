<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include_once(__DIR__ ."/../projects.php");
include_once(__DIR__ . "/../classes/HubREDCapUsers.php");

$checked_values = explode(",",$_REQUEST['checked_values']);
$user_list = explode(",",$_REQUEST['user_list_textarea']);


?>