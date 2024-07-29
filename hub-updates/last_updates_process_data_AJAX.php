<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include_once(__DIR__ ."/../projects.php");
include_once(__DIR__ . "/../classes/HubUpdates.php");

$checked_values = $_REQUEST['checked_values'];
$option = $_REQUEST['option'];

$page_to_return = "index";
if($option == "dates"){
    $checked_values = $_REQUEST['checked_values_dates'];
    $option = "resolved";
    $page_to_return = "resolved_list";
}
$message = "";
if($option == "save")
{
    HubUpdates::updateDataDictionary($module, $pidsArray, $checked_values);
}else if($option == "resolved" || $option == "removed"){

    $hub_updates_resolved_list = $module->getProjectSetting('hub-updates-resolved-list');
    $checked_values_array = explode(",",$checked_values);
    $hub_updates_resolved_list = explode(",", $hub_updates_resolved_list);

    if($option == "resolved")
    {
        if(!empty($hub_updates_resolved_list)) {
            $result = trim(implode("," ,array_unique(array_merge($hub_updates_resolved_list, $checked_values_array))),",");
        }else{
            $result = $checked_values;
        }

        #Update dates on checked values
        $hub_updates_resolved_list_last_updated = $module->getProjectSetting('hub-updates-resolved-list-last-updated');
        $hub_updates_dates = [];
        foreach ($checked_values_array as $resolved_checked) {
            $hub_updates_resolved = explode("-", $resolved_checked);
            $constant = $hub_updates_resolved[0];
            $var_name = $hub_updates_resolved[1];
            if(!array_key_exists($constant, $hub_updates_dates)){
                $hub_updates_dates[$constant] = [];
            }
            $hub_updates_dates[$constant][$var_name]['date'] = date("F d Y H:i:s");
            if (defined('USERID')) {
                $hub_updates_dates[$constant][$var_name]['user'] = USERID;
            }
        }
        #Merged updated checked values with old data (not updated)
        if(is_array($hub_updates_resolved_list_last_updated) && !empty($hub_updates_resolved_list_last_updated)){
            foreach ($hub_updates_resolved_list_last_updated as $constant => $field_data){
                $field_found = false;
                foreach ($field_data as $field_name => $data){
                    foreach ($hub_updates_dates[$constant] as $field_name_checked => $data_checked){
                        if($field_name == $field_name_checked){
                            $field_found = true;
                        }
                    }
                    if(!$field_found){
                        if(!array_key_exists($constant, $hub_updates_dates)){
                            $hub_updates_dates[$constant] = [];
                        }
                        $hub_updates_dates[$constant][$field_name] = $hub_updates_resolved_list_last_updated[$constant][$field_name];
                        if($hub_updates_dates[$constant][$field_name]['date'] === ""){
                            $hub_updates_dates[$constant][$field_name]['date'] = date("F d Y H:i:s");
                        }
                    }
                }
            }
        }
        $module->setProjectSetting('hub-updates-resolved-list-last-updated', $hub_updates_dates);
        $message = "&message=R";
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
        $result = trim(implode(",", $hub_updates_resolved_list_final),",");
        $message = "&message=L";
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

if(isset($checked_values) && $option != "removed" && $_REQUEST['option'] != "dates") {
    echo json_encode(array(
        'status' => 'success'
    ));
}else{
    header("location:".$module->getUrl('hub-updates/'.$page_to_return.'.php').$message);
}
?>