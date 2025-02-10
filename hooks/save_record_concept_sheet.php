<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include_once(__DIR__ ."/../projects.php");
include_once(__DIR__ ."/../functions.php");
use ExternalModules\ExternalModules;

#Get Projects ID's
$hub_mapper = $this->getProjectSetting('hub-mapper');
$pidsArray = REDCapManagement::getPIDsArray($hub_mapper);

if($instrument == 'concept_sheet'){
    $regions = \REDCap::getData($pidsArray['REGIONS'], 'json-array');
    foreach ($regions as $region){
        $instance = $region['record_id'];
        //only if it's the first time we save the info
        if(empty($concept[$record]['repeat_instances']['writing_group_by_research_group'][$instance])) {
            $array_repeat_instances = array();
            $aux = array();
            $aux['gmember_role'] = $region['record_id'];
            $aux['writing_group_by_research_group_complete'] = '1';

            $array_repeat_instances[$record]['repeat_instances'][$event_id]['writing_group_by_research_group'][$instance] = $aux;
            $results = \REDCap::saveData($project_id, 'array', $array_repeat_instances,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false, 1, false, '');
            \REDCap::logEvent("Create Writing Group Instance\nConcept Sheet", $region['region_name']." (".$region['region_code'].")", null, $record, $event_id, $project_id);
        }else{
            break;
        }
    }
}
?>
