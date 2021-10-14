<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once(dirname(__FILE__)."/projects.php");

use ExternalModules\ExternalModules;

$pidHome = $_REQUEST['pidHome'];
#Add Default Harmonist Quick Links
if($pidHome != ""){
    $Proj = new \Project($pidHome);
    $event_id = $Proj->firstEventId;

    #create the first record
    $module->addProjectToList($pidHome, $event_id, 1, 'record_id', 1);

    $array_repeat_instances = array();
    $aux = array();
    $aux['links_sectionhead'] = "Hub Actions";
    $aux['links_sectionorder'] = '1';
    $aux['links_sectionicon'] = '1';
    $aux['links_text1'] = 'Create EC request';
    $aux['links_link1'] = 'https://redcap.vanderbilt.edu/surveys/?s='.IEDEA_REQUESTLINK;
    $aux['links_text2'] = 'Add Hub user';
    $aux['links_link2'] = 'https://redcap.vanderbilt.edu/surveys/?s='.IEDEA_SURVEYPERSONINFO;

    $array_repeat_instances[1]['repeat_instances'][$event_id]['quick_links_section'][1] = $aux;
    $results = \REDCap::saveData($pidHome, 'array', $array_repeat_instances,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false, 1, false, '');

    $aux = array();
    $aux['links_sectionhead'] = "Harmonist";
    $aux['links_sectionorder'] = '5';
    $aux['links_sectionicon'] = '6';
    $aux['links_text1'] = 'About us';
    $aux['links_link1'] = 'index.php?option=abt';
    $aux['links_text2'] = 'Report a bug';
    $aux['links_link2'] = 'index.php?option=bug';
    $aux['links_stay2'] = array("1" => "1");

    $array_repeat_instances[1]['repeat_instances'][$event_id]['quick_links_section'][2] = $aux;
    $results = \REDCap::saveData($pidHome, 'array', $array_repeat_instances,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false, 1, false, '');
}
?>