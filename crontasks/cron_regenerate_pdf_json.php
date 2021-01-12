<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once(dirname(dirname(__FILE__))."/classes/AllCrons.php");
require_once(dirname(dirname(__FILE__))."/vendor/autoload.php");
include_once(__DIR__ ."/../projects.php");

if(APP_PATH_WEBROOT[0] == '/'){
    $APP_PATH_WEBROOT_ALL = substr(APP_PATH_WEBROOT, 1);
}
define('APP_PATH_WEBROOT_ALL',APP_PATH_WEBROOT_FULL.$APP_PATH_WEBROOT_ALL);

foreach ($this->getProjectsWithModuleEnabled() as $project_id){
    error_log("Generate PDF - project_id:" . $project_id);

    $RecordSetConstants = \REDCap::getData($project_id, 'array', null,null,null,null,false,false,false,"[project_constant]='SETTINGS'");
    $settingsPID = getProjectInfoArray($RecordSetConstants)[0]['project_id'];
    if($settingsPID != "") {
        $settings = \REDCap::getData(array('project_id' => $settingsPID), 'array')[1][$this->framework->getEventId($settingsPID)];

        if ($settings['des_pdf_regenerate'][1] == '1') {
            AllCrons::createAndSavePDFCron($module, $settings, $project_id);
            AllCrons::createAndSaveJSONCron($module, $project_id);

            #Uncheck variable
            $Proj = new \Project($settingsPID);
            $event_id = $Proj->firstEventId;
            $arrayRM = array();
            $arrayRM[1][$event_id]['des_pdf_regenerate'] = array(1 => "");//checkbox
            $results = \Records::saveData($settingsPID, 'array', $arrayRM, 'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
            \Records::addRecordToRecordListCache($settingsPID, 1, $event_id);
        }
    }
}
?>