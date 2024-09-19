<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include_once(__DIR__ ."/../projects.php");
include_once(__DIR__ . "/../classes/HubREDCapUsers.php");

$checked_values = explode(",",$_REQUEST['checked_values']);
$user_list = explode(",",$_REQUEST['user_list_textarea']);

print_array($user_list);
print_array($module->getProjectSetting('user-permission',16));
foreach ($checked_values as $project_id){
    $fields_rights = "project_id, username, design, user_rights, data_export_tool, reports, graphical, data_logging, data_entry";
    $instrument_names = \REDCap::getInstrumentNames(null,$project_id);
    #Data entry [$instrument,$status] -> $status: 0 NO ACCESS, 1 VIEW & EDIT, 2 READ ONLY
    $data_entry = "[".implode(',1][',array_keys($instrument_names)).",1]";
//    foreach ($userPermission as $user){
//        if($user != null && $user != USERID) {
//            $module->query("INSERT INTO redcap_user_rights (" . $fields_rights . ")
//                    VALUES (?,?,?,?,?,?,?,?,?)",
//                [$project_id_new, $user, 1, 1, 1, 1, 1, 1, $data_entry]);
//        }
//    }
}
?>