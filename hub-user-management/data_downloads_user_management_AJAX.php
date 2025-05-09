<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include_once(__DIR__ ."/../projects.php");
include_once(__DIR__ . "/../classes/HubDataDownloadsUsers.php");

$option = $_REQUEST['option'];
if($option == "remove") {
    $message = 'D';
}else if($option == "add"){
    $message = 'N';
}
$checked_values = explode(",",$_REQUEST['checked_values_user']);
foreach ($checked_values as $key => $userId) {
    if($option == "remove") {
        $module->getDataDownloadsUsersHandler()->removeUserFromDataDownloads($userId);
    }else if($option == "add"){
        $missing_values = explode(",",$_REQUEST['checked_values_missing_user']);
        $usernames = explode(",",$_REQUEST['usernames']);
        $module->getDataDownloadsUsersHandler()->addUserToDataDownloads($userId,$usernames[$key],$missing_values[$key]);
    }
}

echo json_encode(array(
                     'status' => 'success',
                    'message' => $message,
                 ));
?>