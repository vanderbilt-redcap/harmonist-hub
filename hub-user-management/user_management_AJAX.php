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
    $role_id = $_REQUEST['user_role_'.$option];
    $role_name = $_REQUEST['user_role_name_'.$option];
    $projects_titles_array = REDCapManagement::getProjectsTitlesArray();

    //print_array($module->getProjectSetting('user-permission',16));
    $email_users = [];
    foreach ($user_list as $user_name) {
        $q = $module->query("SELECT user_email FROM redcap_user_information WHERE username = ?", [$user_name]);
        if($row = $q->fetch_assoc()){
            $email_users[$user_name]['email'] =  $row['user_email'];
            $email_users[$user_name]['text'] =  '';
        }
    }
    foreach ($checked_values as $project_id) {
        foreach ($user_list as $user_name) {
//            HubREDCapUsers::addUserToProject($module, $project_id, $user_name, $role_id, USERID, $pidsArray, $role_name);
            $email_users[$user_name]['text'] .=  "<div>PID #".$project_id." - ".$module->framework->getProject($pidsArray[array_search($project_id, $pidsArray)])->getTitle()."</div>";
        }
    }
    foreach ($email_users as $user_name => $data){
//        \REDCap::email($data['email'],'harmonist@vumc.org',"You have been added to a ".$settings['hub_name']." Hub Project", $data['text']));
        error_log("You have been added to a ".$settings['hub_name']." Hub Project");
        error_log($data['text']);
    }
    $message = "A";
}else if($option == "remove_user") {
    $user_list = explode(",", $_REQUEST['users_checked']);
    foreach ($checked_values as $project_id) {
        foreach ($user_list as $user_name) {
//            HubREDCapUsers::removeUserFromProject($module, $project_id, $user_name, USERID, $pidsArray);
        }
    }
    $message = "D";
}else if($option == "change_user") {

    $message = "C";
}

echo json_encode(array(
    'status' => 'success',
    'message' => $message
));
?>