<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include_once(__DIR__ ."/../projects.php");
include_once(__DIR__ . "/../classes/HubREDCapUsers.php");

$option = $_REQUEST['option'];
$checked_values = explode(",",$_REQUEST['checked_values_'.$option]);
error_log($option);
$message = "";
if($option == "add_user") {
    $user_list = explode(",", $_REQUEST['user_list_textarea']);
    $role_id = $_REQUEST['user_role_id_'.$option];
    $role_name = $_REQUEST['user_role_name_'.$option];

    $email_users = HubREDCapUsers::gerUsersEmail($module, $user_list);
    foreach ($checked_values as $project_id) {
        foreach ($user_list as $user_name) {
            HubREDCapUsers::addUserToProject($module, $project_id, $user_name, $role_id, USERID, $pidsArray, $role_name);
            $email_users[$user_name]['text'] .=  "<div>PID #".$project_id." - ".$module->framework->getProject($pidsArray[array_search($project_id, $pidsArray)])->getTitle()."</div>";
        }
    }
    foreach ($email_users as $user_name => $data){
        \REDCap::email($data['email'],'harmonist@vumc.org',"You have been added to a ".$settings['hub_name']." Hub Project", $data['text']);
    }
    $message = "A";
}else if($option == "remove_user") {
    $user_list = explode(",", $_REQUEST['users_checked']);
    foreach ($checked_values as $project_id) {
        foreach ($user_list as $user_name) {
            HubREDCapUsers::removeUserFromProject($module, $project_id, $user_name, USERID, $pidsArray);
        }
    }
    $message = "D";
}else if($option == "change_user_single" || $option == "remove_user_single") {
    $user_name = $_REQUEST['user_id_'.$option];
    $role_id = $_REQUEST['user_role_id_'.$option];
    $role_name = $_REQUEST['user_role_name_'.$option];
    $project_id = $_REQUEST['project_id'];

    if($option == "remove_user_single") {
        HubREDCapUsers::removeUserFromProject($module, $project_id, $user_name, USERID, $pidsArray);
        $message = "D";
    }else{
        HubREDCapUsers::changeUserRole($module, $project_id, $user_name, $role_id, $pidsArray, $role_name, USERID);
        $message = "C";
    }
}

echo json_encode(array(
    'status' => 'success',
    'message' => $message
));
?>