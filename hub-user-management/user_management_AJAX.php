<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include_once(__DIR__ ."/../projects.php");
include_once(__DIR__ . "/../classes/HubREDCapUsers.php");

$option = $_REQUEST['option'];
$checked_values = explode(",",$_REQUEST['checked_values_'.$option]);
$role_name = ($_REQUEST['user_role_name_'.$option] == "undefined")? null:$_REQUEST['user_role_name_'.$option];
$role_type = $_REQUEST['user_role_id_'.$option];
$user_list = explode(",", $_REQUEST['users_checked']);

$message = "";
if($option == "change_user_single" || $option == "remove_user_single") {
    $user_name = $_REQUEST['user_id_'.$option];
    $project_id = $_REQUEST['project_id'];
    $role_id = HubREDCapUsers::getUserRole($module, $role_name, $project_id, $_REQUEST['user_role_id_'.$option]);

    if($option == "remove_user_single") {
        HubREDCapUsers::removeUserFromProject($module, $project_id, $user_name, USERID, $pidsArray);
        $message = "D";
    }else{
        HubREDCapUsers::changeUserRole($module, $project_id, $user_name, $role_id, $pidsArray, $role_name, USERID);
        $message = "C";
    }
}else{
    $message = HubREDCapUsers::setUserChanges($module, $pidsArray, $option, $user_list, $checked_values, $role_name, $role_type);
}

echo json_encode(array(
    'status' => 'success',
    'message' => $message
));
?>