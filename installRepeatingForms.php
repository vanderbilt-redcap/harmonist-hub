<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once(dirname(__FILE__)."/classes/REDCapManagement.php");
require_once(dirname(__FILE__)."/projects.php");

$forms = $_POST['fields'];

foreach ($forms as $project_id => $repeat){
    $Proj = new \Project($project_id);
    $events = $Proj->eventsForms;
    foreach ($events as $event_id => $forms) {
        foreach ($repeat as $instrument => $params) {
            $found = false;
            foreach ($forms as $form) {
                if ($instrument == $form) {
                    $found = true;
                    $module->query("INSERT INTO redcap_events_repeat (event_id, form_name, custom_repeat_form_label) VALUES (?, ?, ?)", [$event_id, $instrument, $params]);
                }
            }
            if(!$found){
                $module->query("INSERT INTO redcap_events_repeat (event_id, form_name, custom_repeat_form_label) VALUES (?, ?, ?)", [$Proj->firstEventId, $instrument, $params]);
            }
        }
    }
}

?>