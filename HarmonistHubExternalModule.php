<?php
namespace Vanderbilt\HarmonistHubExternalModule;

use Exception;
use REDCap;

//require_once 'vendor/autoload.php';

class HarmonistHubExternalModule extends \ExternalModules\AbstractExternalModule
{

    public function __construct()
    {
        parent::__construct();
    }

    #If it's not the main PID project hide the Harmonist Hub link
    public function redcap_module_link_check_display($project_id, $link ) {
        $hub_projectname = $this->getProjectSetting('hub-projectname');
        $dd_array = \REDCap::getDataDictionary('array');
        if(count($dd_array) > 1 && !array_key_exists('project_constant', $dd_array)){
            return false;
        }else if($hub_projectname != ""){
            $link['name'] = $hub_projectname." Hub";
        }
        return parent::redcap_module_link_check_display($project_id,$link);
    }

    function addProjectToList($project_id, $eventId, $record, $fieldName, $value){
        $this->query("INSERT INTO redcap_data (project_id, event_id, record, field_name, value) VALUES (?, ?, ?, ?, ?)",
            [$project_id, $eventId, $record, $fieldName, $value]);
    }
}

?>