<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once(dirname(__FILE__)."/classes/REDCapManagement.php");
require_once(dirname(__FILE__)."/projects.php");

$missingFields = $_POST['fields'];
$deletionRegEx = "/___delete$/";

$projects_array = REDCapManagement::getProjectsContantsArray();
foreach ($projects_array as $index => $constant) {
    $metadata = array();
    $project_id = constant("IEDEA_".$constant);
    $metadata["destination"] = \REDCap::getDataDictionary($project_id, 'array', false);
    $metadata["origin"] = $module->dataDictionaryCSVToMetadataArray($module->framework->getModulePath()."csv/".$constant.".csv", '');

    if ($metadata['destination']) {
        $fieldLabels = array();
        foreach ($metadata as $type => $md) {
            $fieldLabels[$type] = REDCapManagement::getLabels($md);
        }

        try {
            $feedback = REDCapManagement::mergeMetadataAndUpload($module, $metadata['origin'], $metadata['destination'], $missingFields, $deletionRegEx, $project_id);
        } catch (\Exception $e) {
            $feedback = array("Exception" => $e->getMessage());
            echo json_encode($feedback);
        }
    }
}

?>