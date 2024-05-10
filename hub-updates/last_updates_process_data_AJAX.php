<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include_once(__DIR__ ."/../projects.php");
include_once(__DIR__ . "/../classes/HubUpdates.php");

$checked_values = $_REQUEST['checked_values'];
$option = $_REQUEST['option'];
$message = "";
if($option == "save")
{
    HubUpdates::updateDataDictionary($module, $pidsArray, $checked_values);
}else{
    $hub_updates_resolved_list = $module->getProjectSetting('hub-updates-resolved-list');
    $checked_values_array = explode(",",$checked_values);
    if(!empty($hub_updates_resolved_list)) {
        $hub_updates_resolved_list = explode(",", $hub_updates_resolved_list);

        if($option == "resolved")
        {
            $result = implode("," ,array_unique(array_merge($hub_updates_resolved_list, $checked_values_array)));
        }
        else if($option == "removed")
        {
            $hub_updates_resolved_list_final = $hub_updates_resolved_list;
            foreach($hub_updates_resolved_list as $key_resolved => $resolved_list){
                foreach($checked_values_array as $key_checked => $checked_list){
                    if($checked_list == $resolved_list){
                        unset($hub_updates_resolved_list_final[$key_resolved]);
                    }
                }
            }
            $result = implode(",", $hub_updates_resolved_list_final);
            $message = "&message=L";
        }
    }else if($option == "resolved"){
        $result = $checked_values;
    }
    $module->setProjectSetting('hub-updates-resolved-list',$result);
}

//Save the hub-updates to refresh the data
$allUpdates['data'] = HubUpdates::compareDataDictionary($module, $pidsArray);
$today = date("Y-m-d");
$allUpdates['timestamp'] = $today;
$total_updates = count($allUpdates['data']);
$allUpdates['total_updates'] = $total_updates;
$module->setProjectSetting('hub-updates', $allUpdates);

if(isset($checked_values) &&$option != "removed") {
    echo json_encode(array(
        'status' => 'success'
    ));
}else{
    header("location:".$module->getUrl('hub-updates/index.php').$message);
}
?>