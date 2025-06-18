<?php
namespace Vanderbilt\HarmonistHubExternalModule;
use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
include_once(__DIR__ . "/classes/HubREDCapUsers.php");

if(APP_PATH_WEBROOT[0] == '/'){
    $APP_PATH_WEBROOT_ALL = substr(APP_PATH_WEBROOT, 1);
}
define('APP_PATH_WEBROOT_ALL',APP_PATH_WEBROOT_FULL.$APP_PATH_WEBROOT_ALL);

$project_id = (int)$_REQUEST['pid'];
$hub_projectname = $module->getProjectSetting('hub-projectname');
$hub_profile = $module->getProjectSetting('hub-profile');
#hardcoded value for now.
$hub_profile =  "solo";
$userPermission = $module->getProjectSetting('user-permission',$project_id);
$module->setProjectSetting('hub-mapper',$project_id);

#PID MAPPER
$module->setPIDMapperProject($project_id);

$projects_array = REDCapManagement::getProjectsConstantsArray();
$projects_titles_array = REDCapManagement::getProjectsTitlesArray();
$projects_array_repeatable = REDCapManagement::getProjectsRepeatableArray();
$projects_array_surveys = REDCapManagement::getProjectsSurveysArray();
$projects_array_module_emailalerts = REDCapManagement::getProjectsModuleEmailAlertsArray($module, $hub_projectname);
$projects_array_module_getpmid = REDCapManagement::getProjectsModuleGetPMIDArray();
$projects_array_show = REDCapManagement::getProjectsShowArray();
$custom_record_label_array = REDCapManagement::getCustomRecordLabelArray();
$projects_array_hooks = REDCapManagement::getProjectsHooksArray();
$projects_array_surveys_hash = REDCapManagement::getProjectsSurveyHashArray();

$pidHome = "";
$record = 1;
foreach ($projects_array as $index=>$name){
    $project_title = $hub_projectname." Hub: ".$projects_titles_array[$index];
    $project_id_new = $module->createProjectAndImportDataDictionary($name,$project_title);
    $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'record_id', $record);
    $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'project_id', $project_id_new);
    $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'project_constant', $name);
    $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'project_show_y', $projects_array_show[$index]);
    $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'project_info_complete', 2);

    if($custom_record_label_array[$index] != ''){
        $module->query("UPDATE redcap_projects SET custom_record_label = ? WHERE project_id = ?",[$custom_record_label_array[$index],$project_id_new]);
    }
    if($name == 'SETTINGS'){
        #Create first record
        $qtype = $module->query("SELECT b.event_id FROM  redcap_events_arms a LEFT JOIN redcap_events_metadata b ON(a.arm_id = b.arm_id) where a.project_id =?",[$project_id_new]);
        $rowtype = $qtype->fetch_assoc();
        $module->addProjectToList($project_id_new, $rowtype['event_id'], 1, 'record_id', 1);

        if($hub_profile == 'solo'){
            $module->addProjectToList($project_id_new, $rowtype['event_id'], 1, 'deactivate_datahub', 1);
            $module->addProjectToList($project_id_new, $rowtype['event_id'], 1, 'deactivate_datadown', 1);
            $module->addProjectToList($project_id_new, $rowtype['event_id'], 1, 'deactivate_tblcenter', 1);
            $module->addProjectToList($project_id_new, $rowtype['event_id'], 1, 'deactivate_toolkit', 1);
        }else if($hub_profile == 'basic'){
            $module->addProjectToList($project_id_new, $rowtype['event_id'], 1, 'deactivate_datadown', 1);
            $module->addProjectToList($project_id_new, $rowtype['event_id'], 1, 'deactivate_toolkit', 1);
        }else if($hub_profile == 'all'){
            #We show everything
            if(SERVER_NAME == "redcap.vumc.org") {
                #We send an email with a list of things to set up only in the Vardebilt server
                $subject = "Data Toolkit activation request for " . $hub_projectname . " Hub";
                $message = "<div>Dear Administrator,</div><br/>" .
                    $message = "<div>A new request has been enabled to activate the Data Toolkit for <strong>" . $hub_projectname . " Hub </strong>(<em>PID " . $project_id . "</em>)</div><br/>" .
                        "<div>The following elements need to be enabled:</div>" .
                        "<ul>" .
                        "<li>Data Toolkit</li>" .
                        "<li>AWS Bucket name</li>" .
                        "<li>AWS Bucket Credentials</li>" .
                        "</ul>";
                \Vanderbilt\HarmonistHubExternalModule\sendEmail(REDCapManagement::DEFAULT_EMAIL_ADDRESS, "noreply.harmonist@vumc.org", "noreply.harmonist@vumc.org", $subject, $message, "Not in database","New DataTolkit setup needed",$project_id_new);
            }
        }
        #Add the Default values or they get deleted with the saved new record
        ProjectData::installDefault($module,$project_id_new,$rowtype['event_id'],1);
        \Records::addRecordToRecordListCache($project_id_new, $record,1);
    }else if($name == "HOME"){
        $pidHome = $project_id_new;
    }
    #Add Repeatable projects
    REDCapManagement::addRepeatableInstrument($module, $projects_array_repeatable[$index], $project_id_new);

    #Enable External Modules in projects
    if($projects_array_hooks[$index] == '1') {
        #enable current module to activate hooks
        $module->enableModule($project_id_new, "harmonist-hub");
        $module->setProjectSetting('hub-mapper',$project_id, $project_id_new);
    }

    if(array_key_exists($index,$projects_array_module_emailalerts)){
        #Email Alerts
        $module->enableModule($project_id_new,"vanderbilt_emailTrigger");
        $othermodule = ExternalModules::getModuleInstance("vanderbilt_emailTrigger");
        foreach ($projects_array_module_emailalerts[$index] as $setting_name => $setting_value){
            if($setting_name == "email-text"){
                $setting_value = str_replace("___project_id_new", $project_id_new, $setting_value);
            }
            $othermodule->setProjectSetting($setting_name, $setting_value, $project_id_new);
        }
    }

    if(array_key_exists($index,$projects_array_module_getpmid)){
        #Get PMID Details
        $module->enableModule($project_id_new,"get-pmid-details");
        $othermodule = ExternalModules::getModuleInstance("get-pmid-details");
        foreach ($projects_array_module_getpmid[$index] as $setting_name => $setting_value){
            $othermodule->setProjectSetting($setting_name, $setting_value, $project_id_new);
        }
    }

    #ADD USER PERMISSIONS
    $user_roles = HubREDCapUsers::getAllRoles($module, $project_id_new);
    foreach ($userPermission as $user){
        if($user != null && $user != USERID) {
            HubREDCapUsers::addUserToProject($module, $project_id_new, $user, $user_roles[HubREDCapUsers::HUB_ROLE_USER], "Harmonist Installation Process", $project_id, HubREDCapUsers::HUB_ROLE_USER);
        }
    }

    \Records::addRecordToRecordListCache($project_id, $record,1);
    $record++;

    #We create the surveys
    if(array_key_exists($index,$projects_array_surveys)){
        #Create Hub theme
        $theme_id = ProjectData::getThemeId($module);

        $module->query("UPDATE redcap_projects SET surveys_enabled = ? WHERE project_id = ?",["1",$project_id_new]);
        foreach ($projects_array_surveys[$index] as $survey){
            $formName = ucwords(str_replace("_"," ",$survey));
            $module->query("INSERT INTO redcap_surveys (project_id,form_name,survey_enabled,save_and_return,save_and_return_code_bypass,edit_completed_response,title,theme) VALUES (?,?,?,?,?,?,?,?)",[$project_id_new,$survey,1,1,1,1,$formName,$theme_id]);
            $surveyId = db_insert_id();
            $hash = $module->generateUniqueRandomSurveyHash();
            $Proj = new \Project($project_id_new);
            $event_id = $Proj->firstEventId;

            $module->query("INSERT INTO redcap_surveys_participants (survey_id,hash,event_id) VALUES (?,?,?)",[$surveyId,$hash,$event_id]);

            if($index != 1 && (array_key_exists($index,$projects_array_surveys_hash) && $survey == $projects_array_surveys_hash[$index]['instrument'])){
                $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'record_id', $record);
                $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'project_id', $hash);
                $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'project_constant', $projects_array_surveys_hash[$index]['constant']);
                $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'project_show_y', 0);
                $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'project_info_complete', 2);

                \Records::addRecordToRecordListCache($project_id, $record,1);
                $record++;
            }
        }
    }
}

#SET USER PERMISSIONS AS READONLY ON MAIN PROJECT
$fields_rights = "data_entry";
$instrument_names = \REDCap::getInstrumentNames(null,$project_id);
#Data entry [$instrument,$status] -> $status: 0 NO ACCESS, 1 VIEW & EDIT, 2 READ ONLY
$data_entry = "[".implode(',2][',array_keys($instrument_names)).",2]";
foreach ($userPermission as $user){
    if($user != null && $user != USERID) {
        $module->query("UPDATE redcap_user_rights SET " . $fields_rights . " = ?
                    WHERE  project_id = ? AND username = ?",
            [$data_entry,$project_id,$user]);
    }
}

#Get Projects ID's
$pidsArray = REDCapManagement::getPIDsArray($project_id);

#We must clear the project cache so our hub-updates are pulled from the DB.
$module->clearProjectCache();

#Save instances in Homepage project
if($pidHome != ""){
    $Proj = new \Project($pidHome);
    $event_id = $Proj->firstEventId;

    #create the first record
    $module->addProjectToList($pidHome, $event_id, 1, 'record_id', 1);

    $RequestLinkPid = \REDCap::getData($project_id, 'json-array', null,array('project_id'),null,null,false,false,false,"[project_constant]='REQUESTLINK'")[0]['project_id'];
    $surveyPersonInfoPid = \REDCap::getData($project_id, 'json-array', null,array('project_id'),null,null,false,false,false,"[project_constant]='SURVEYPERSONINFO'")[0]['project_id'];

    $array_repeat_instances = array();
    $aux = array();
    $aux['links_sectionhead'] = "Hub Actions";
    $aux['links_sectionorder'] = '1';
    $aux['links_sectionicon'] = '1';
    $aux['links_text1'] = 'Create EC request';
    $aux['links_link1'] = APP_PATH_WEBROOT_FULL.'surveys/?s='.$RequestLinkPid;
    $aux['links_text2'] = 'Add Hub user';
    $aux['links_link2'] = APP_PATH_WEBROOT_FULL.'surveys/?s='.$surveyPersonInfoPid;

    $array_repeat_instances[1]['repeat_instances'][$event_id]['quick_links_section'][1] = $aux;
    $results = \REDCap::saveData($pidHome, 'array', $array_repeat_instances,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false, 1, false, '');

    $aux = array();
    $aux['links_sectionhead'] = "Harmonist";
    $aux['links_sectionorder'] = '5';
    $aux['links_sectionicon'] = '6';
    $aux['links_text1'] = 'About us';
    $aux['links_link1'] = $module->getUrl('index.php').'&NOAUTH&option=abt';
    $aux['links_text2'] = 'Report a bug';
    $aux['links_link2'] = $module->getUrl('index.php').'&NOAUTH&option=bug';
    $aux['links_stay2'] = array("1" => "1");

    $array_repeat_instances[1]['repeat_instances'][$event_id]['quick_links_section'][2] = $aux;
    $results = \REDCap::saveData($pidHome, 'array', $array_repeat_instances,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false, 1, false, '');
}

#Get Projects ID's
$pidsArray = REDCapManagement::getPIDsArray($project_id);

#We must clear the project cache so our hub-updates are pulled from the DB.
$module->clearProjectCache();

#Upload SQL fields to projects
$projects_array_sql = REDCapManagement::getProjectsSQLFieldsArray($pidsArray);

foreach ($projects_array_sql as $projectid=>$projects){
    foreach ($projects as $varid=>$options){
        foreach ($options as $optionid=>$value){
            if($optionid == 'query') {
                $module->query("UPDATE redcap_metadata SET element_enum = ? WHERE project_id = ? AND field_name=?",[$value,$projectid,$varid]);
            }
            if($optionid == 'autocomplete' && $value == '1'){
                $module->query("UPDATE redcap_metadata SET element_validation_type= ? WHERE project_id = ? AND field_name=?",["autocomplete",$projectid,$varid]);
            }
            if($optionid == 'label' && $value != "") {
                $module->query("UPDATE redcap_metadata SET element_label= ? WHERE project_id = ? AND field_name=?", [$value, $projectid, $varid]);
            }
        }
    }
}

echo json_encode(array(
        'status' =>'success'
    )
);
?>
