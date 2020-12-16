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

$originalPid = $_GET['pid'];
while($row = db_fetch_assoc($q)) {
    $project_id = $row['project_id'];
    if($project_id != "") {
        $_GET['pid'] = $project_id;
        error_log("createpdf - project_id:" . $project_id);

        $RecordSetConstants = \REDCap::getData($project_id, 'array', null,null,null,null,false,false,false,"[project_constant]='SETTINGS'");
        $settingsPID = getProjectInfoArray($RecordSetConstants)[0]['project_id'];
        if($settingsPID != "") {
            $settings = \REDCap::getData(array('project_id' => $settingsPID), 'array')[1][$this->framework->getEventId($settingsPID)];

            $hasJsoncopyBeenUpdated0a = AllCrons::hasJsoncopyBeenUpdated($module, '0a', $settings, $project_id);
            $hasJsoncopyBeenUpdated0b = AllCrons::hasJsoncopyBeenUpdated($module, '0b', $settings, $project_id);
            if ($hasJsoncopyBeenUpdated0a || $hasJsoncopyBeenUpdated0b) {
                AllCrons::createAndSavePDFCron($module, $settings, $project_id);
                AllCrons::createAndSaveJSONCron($module, $project_id);
            } else {
                AllCrons::checkIfJsonOrPDFBlank($module, $settings, $project_id);
            }
        }
    }
}
$_GET['pid'] = $originalPid;

?>