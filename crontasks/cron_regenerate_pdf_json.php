<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once(dirname(dirname(__FILE__))."/classes/AllCrons.php");
include_once(__DIR__ ."/../projects.php");


$sql="SELECT s.project_id FROM redcap_external_modules m, redcap_external_module_settings s WHERE m.external_module_id = s.external_module_id AND s.value = 'true' AND (m.directory_prefix = 'data-model-browser') AND s.`key` = 'enabled'";
$q = $this->query($sql);

if(APP_PATH_WEBROOT[0] == '/'){
    $APP_PATH_WEBROOT_ALL = substr(APP_PATH_WEBROOT, 1);
}
define('APP_PATH_WEBROOT_ALL',APP_PATH_WEBROOT_FULL.$APP_PATH_WEBROOT_ALL);

require_once(dirname(__FILE__)."/vendor/autoload.php");
$originalPid = $_GET['pid'];
while($row = db_fetch_assoc($q)) {
    $project_id = $row['project_id'];
    if($project_id != "") {
        $_GET['pid'] = $project_id;
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
}
$_GET['pid'] = $originalPid;
?>