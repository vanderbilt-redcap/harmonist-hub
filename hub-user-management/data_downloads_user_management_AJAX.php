<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include_once(__DIR__ ."/../projects.php");
include_once(__DIR__ . "/../classes/HubDataDownloadsUsers.php");

$checked_values = explode(",",$_REQUEST['checked_values_remove_user']);
foreach ($checked_values as $user_id) {
    $module->getDataDownloadsUsersHandler()->removeUserFromDataDownloads($user_id);
}

echo json_encode(array(
                     'status' => 'success',
                    'message' => 'D',
                 ));

?>