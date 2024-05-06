<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include_once(__DIR__ ."/../projects.php");
include_once(__DIR__ . "/../classes/HubUpdates.php");

$checked_values = $_REQUEST['checked_values'];
$option = $_REQUEST['option'];

if($option == "save"){
    HubUpdates::updateDataDictionary($module, $pidsArray, $checked_values);
}else if($option == "resolved"){
    $hub_updates_resolved_list = $module->getProjectSetting('hub-updates-resolved-list');

    $checked_values = explode(",",$checked_values);
    $hub_updates_resolved_list = explode(",",$hub_updates_resolved_list);
    $result = implode("," ,array_unique(array_merge($hub_updates_resolved_list, $checked_values)));
    $module->setProjectSetting('hub-updates-resolved-list',$result);
//    $module->setProjectSetting('hub-updates-resolved-list',"");
}

//Save the updates to refresh the data
$hub_updates = $module->getProjectSetting('hub-updates');
$today = date("Y-m-d");

$allUpdates['data'] = HubUpdates::compareDataDictionary($module, $pidsArray);
$allUpdates['timestamp'] = $today;
$total_updates = count($allUpdates['data']);
$allUpdates['total_updates'] = $total_updates;
$module->setProjectSetting('hub-updates', $allUpdates);


echo json_encode(array(
    'status' => 'success'
));
?>