<?php
namespace Vanderbilt\HarmonistHubExternalModule;
use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

$project_id = (int)$_REQUEST['pid'];
$hub_projectname = $module->getProjectSetting('hub-projectname');
$hub_profile = $module->getProjectSetting('hub-profile');
#hardcoded value for now.
$hub_profile =  "solo";
$userPermission = $module->getProjectSetting('user-permission',$project_id);
$module->setProjectSetting('hub-mapper',$project_id);

#PID MAPPER
$module->setPIDMapperProject($project_id);

$projects_array = REDCapManagement::getProjectsContantsArray();
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
            if(SERVER_NAME == 'redcap.vanderbilt.edu' || SERVER_NAME == "redcap.vumc.org") {
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
                \Vanderbilt\HarmonistHubExternalModule\sendEmail("harmonist@vumc.org", "noreply.harmonist@vumc.org", "noreply.harmonist@vumc.org", $subject, $message, "Not in database","New DataTolkit setup needed",$project_id_new);
            }
        }
        #Add the Default values or they get deleted with the saved new record
        ProjectData::installDefault($module,$project_id_new,$rowtype['event_id'],1);
        \Records::addRecordToRecordListCache($project_id_new, $record,1);
    }else if($name == "HOME"){
        $pidHome = $project_id_new;
    }
    #Add Repeatable projects
    foreach($projects_array_repeatable[$index] as $repeat_event){
        if($repeat_event['status'] == 1){
            $q = $module->query("SELECT b.event_id FROM  redcap_events_arms a LEFT JOIN redcap_events_metadata b ON(a.arm_id = b.arm_id) where a.project_id = ?",[$project_id_new]);
            while ($row = $q->fetch_assoc()) {
                $event_id = $row['event_id'];
                $module->query("INSERT INTO redcap_events_repeat (event_id, form_name, custom_repeat_form_label) VALUES (?, ?, ?)",[$event_id,$repeat_event['instrument'],$repeat_event['params']]);
            }
        }
    }
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
    $fields_rights = "project_id, username, design, user_rights, data_export_tool, reports, graphical, data_logging, data_entry";
    $instrument_names = \REDCap::getInstrumentNames(null,$project_id_new);
    #Data entry [$instrument,$status] -> $status: 0 NO ACCESS, 1 VIEW & EDIT, 2 READ ONLY
    $data_entry = "[".implode(',1][',array_keys($instrument_names)).",1]";
    foreach ($userPermission as $user){
        if($user != null && $user != USERID) {
            $module->query("INSERT INTO redcap_user_rights (" . $fields_rights . ")
                    VALUES (?,?,?,?,?,?,?,?,?)",
                [$project_id_new, $user, 1, 1, 1, 1, 1, 1, $data_entry]);
        }
    }

    \Records::addRecordToRecordListCache($project_id, $record,1);
    $record++;

    #We create the surveys
    if(array_key_exists($index,$projects_array_surveys)){
        $module->query("UPDATE redcap_projects SET surveys_enabled = ? WHERE project_id = ?",["1",$project_id_new]);
        foreach ($projects_array_surveys[$index] as $survey){
            $formName = ucwords(str_replace("_"," ",$survey));
            $module->query("INSERT INTO redcap_surveys (project_id,form_name,survey_enabled,save_and_return,save_and_return_code_bypass,edit_completed_response,title) VALUES (?,?,?,?,?,?,?)",[$project_id_new,$survey,1,1,1,1,$formName]);
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

#We must clear the project cache so our updates are pulled from the DB.
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
    $aux['links_link1'] = 'https://redcap.vumc.org/surveys/?s='.$RequestLinkPid;
    $aux['links_text2'] = 'Add Hub user';
    $aux['links_link2'] = 'https://redcap.vumc.org/surveys/?s='.$surveyPersonInfoPid;

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

#We must clear the project cache so our updates are pulled from the DB.
$module->clearProjectCache();

#Upload SQL fields to projects
$projects_array_sql = array(
    $pidsArray['DATAMODEL']=>array(
        'variable_replacedby' => array (
            'query' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM [data-table:".$pidsArray['DATAMODEL']."] WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM [data-table:".$pidsArray['DATAMODEL']."] WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '1',
            'label' => ""
        ),
        'variable_splitdate_m' => array (
            'query' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM [data-table:".$pidsArray['DATAMODEL']."] WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM [data-table:".$pidsArray['DATAMODEL']."] WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '1',
            'label' => ""
        ),
        'variable_splitdate_d' => array (
            'query' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM [data-table:".$pidsArray['DATAMODEL']."] WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM [data-table:".$pidsArray['DATAMODEL']."] WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '1',
            'label' => ""
        ),
        'code_list_ref' =>  array (
            'query' => "select record, value from [data-table:".$pidsArray['CODELIST']."] where project_id = ".$pidsArray['CODELIST']." and field_name = 'list_name' order by value asc",
            'autocomplete' => '0',
            'label' => ""
        )
    ),
    $pidsArray['HARMONIST']=>array(
        'contact_link' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM [data-table:".$pidsArray['PEOPLE']."] a  WHERE a.project_id=".$pidsArray['PEOPLE']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '1',
            'label' => ""
        ),
        'contact2_link' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM [data-table:".$pidsArray['PEOPLE']."] a  WHERE a.project_id=".$pidsArray['PEOPLE']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '1',
            'label' => ""
        ),
        'wg_link' => array (
            'query' => "SELECT a.record, CONCAT( max(if(a.field_name = 'group_name', a.value, '')), ' (', max(if(a.field_name = 'group_abbr', a.value, '')), ') ' ) as value FROM [data-table:".$pidsArray['GROUP']."] a WHERE a.project_id=".$pidsArray['GROUP']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'wg2_link' => array (
            'query' => "SELECT a.record, CONCAT( max(if(a.field_name = 'group_name', a.value, '')), ' (', max(if(a.field_name = 'group_abbr', a.value, '')), ') ' ) as value FROM [data-table:".$pidsArray['GROUP']."] a WHERE a.project_id=".$pidsArray['GROUP']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'lead_region' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM [data-table:".$pidsArray['REGIONS']."] a  WHERE a.project_id=".$pidsArray['REGIONS']." GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
            ),
        'person_link' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM [data-table:".$pidsArray['PEOPLE']."] a  WHERE a.project_id=".$pidsArray['PEOPLE']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        )
     ),
    $pidsArray['RMANAGER']=>array(
        'assoc_concept' => array (
            'query' => "SELECT a.record, CONCAT(a.value, ' | ', b.value) FROM (SELECT record, value FROM [data-table:".$pidsArray['HARMONIST']."] WHERE project_id = ".$pidsArray['HARMONIST']." AND field_name = 'concept_id') a JOIN (SELECT record, value FROM [data-table:".$pidsArray['HARMONIST']."] where project_id = ".$pidsArray['HARMONIST']." and field_name = 'concept_title') b ON b.record=a.record ORDER BY a.value DESC, b.value ",
            'autocomplete' => '1',
            'label' => ""
        ),
        'wg_name' => array (
            'query' => "select record.record as record, CONCAT( max(if(group_name.field_name = 'group_name',group_name.value, '')), ' (', max(if(group_abbr.field_name = 'group_abbr', group_abbr.value, '')), ') ' ) as value from [data-table:".$pidsArray['GROUP']."] record left join [data-table:".$pidsArray['GROUP']."] active_y on active_y.project_id = ".$pidsArray['GROUP']." and active_y.record = record.value and active_y.field_name = 'active_y' and active_y.value ='Y' left join [data-table:".$pidsArray['GROUP']."] group_abbr on group_abbr.project_id = ".$pidsArray['GROUP']." and group_abbr.record = record.value and group_abbr.field_name = 'group_abbr' left join [data-table:".$pidsArray['GROUP']."] group_name on group_name.project_id = ".$pidsArray['GROUP']." and group_name.record = record.value and group_name.field_name = 'group_name' where record.field_name = 'record_id' and record.record=active_y.record and record.project_id = ".$pidsArray['GROUP']." group by record.value ORDER BY record.value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'wg2_name' => array (
            'query' => "select record.record as record, CONCAT( max(if(group_name.field_name = 'group_name',group_name.value, '')), ' (', max(if(group_abbr.field_name = 'group_abbr', group_abbr.value, '')), ') ' ) as value from [data-table:".$pidsArray['GROUP']."] record left join [data-table:".$pidsArray['GROUP']."] active_y on active_y.project_id = ".$pidsArray['GROUP']." and active_y.record = record.value and active_y.field_name = 'active_y' and active_y.value ='Y' left join [data-table:".$pidsArray['GROUP']."] group_abbr on group_abbr.project_id = ".$pidsArray['GROUP']." and group_abbr.record = record.value and group_abbr.field_name = 'group_abbr' left join [data-table:".$pidsArray['GROUP']."] group_name on group_name.project_id = ".$pidsArray['GROUP']." and group_name.record = record.value and group_name.field_name = 'group_name' where record.field_name = 'record_id' and record.record=active_y.record and record.project_id = ".$pidsArray['GROUP']." group by record.value ORDER BY record.value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'wg3_name' => array (
            'query' => "select record.record as record, CONCAT( max(if(group_name.field_name = 'group_name',group_name.value, '')), ' (', max(if(group_abbr.field_name = 'group_abbr', group_abbr.value, '')), ') ' ) as value from [data-table:".$pidsArray['GROUP']."] record left join [data-table:".$pidsArray['GROUP']."] active_y on active_y.project_id = ".$pidsArray['GROUP']." and active_y.record = record.value and active_y.field_name = 'active_y' and active_y.value ='Y' left join [data-table:".$pidsArray['GROUP']."] group_abbr on group_abbr.project_id = ".$pidsArray['GROUP']." and group_abbr.record = record.value and group_abbr.field_name = 'group_abbr' left join [data-table:".$pidsArray['GROUP']."] group_name on group_name.project_id = ".$pidsArray['GROUP']." and group_name.record = record.value and group_name.field_name = 'group_name' where record.field_name = 'record_id' and record.record=active_y.record and record.project_id = ".$pidsArray['GROUP']." group by record.value ORDER BY record.value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'wg4_name' => array (
            'query' => "select record.record as record, CONCAT( max(if(group_name.field_name = 'group_name',group_name.value, '')), ' (', max(if(group_abbr.field_name = 'group_abbr', group_abbr.value, '')), ') ' ) as value from [data-table:".$pidsArray['GROUP']."] record left join [data-table:".$pidsArray['GROUP']."] active_y on active_y.project_id = ".$pidsArray['GROUP']." and active_y.record = record.value and active_y.field_name = 'active_y' and active_y.value ='Y' left join [data-table:".$pidsArray['GROUP']."] group_abbr on group_abbr.project_id = ".$pidsArray['GROUP']." and group_abbr.record = record.value and group_abbr.field_name = 'group_abbr' left join [data-table:".$pidsArray['GROUP']."] group_name on group_name.project_id = ".$pidsArray['GROUP']." and group_name.record = record.value and group_name.field_name = 'group_name' where record.field_name = 'record_id' and record.record=active_y.record and record.project_id = ".$pidsArray['GROUP']." group by record.value ORDER BY record.value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'contact_region' => array (
            'query' => "select record.record as record, CONCAT( max(if(region_name.field_name = 'region_name',region_name.value, '')), ' (', max(if(region_code.field_name = 'region_code', region_code.value, '')), ') ' ) as value from [data-table:".$pidsArray['REGIONS']."] record left join [data-table:".$pidsArray['REGIONS']."] activeregion_y on activeregion_y.project_id = ".$pidsArray['REGIONS']." and activeregion_y.record = record.value and activeregion_y.field_name = 'activeregion_y' and activeregion_y.value ='1' left join [data-table:".$pidsArray['REGIONS']."] region_code on region_code.project_id = ".$pidsArray['REGIONS']." and region_code.record = record.value and region_code.field_name = 'region_code' left join [data-table:".$pidsArray['REGIONS']."] region_name on region_name.project_id = ".$pidsArray['REGIONS']." and region_name.record = record.value and region_name.field_name = 'region_name' where record.field_name = 'record_id' and record.record=activeregion_y.record and record.project_id = ".$pidsArray['REGIONS']." group by record.value ORDER BY region_name.value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'contactperson_id' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM [data-table:".$pidsArray['PEOPLE']."] a  WHERE a.project_id=".$pidsArray['PEOPLE']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '1',
            'label' => "Contact Person:
                                  <span style='font-weight:lighter;'>[contact_name], <a href='mailto:[contact_email]'>[contact_email]</a></span>

                                    If blank, map this person to official Hub Members List
                                    <div style='font-weight:lighter;font-style:italic'>(Find the name above in the official list. If it doesn't exist, you can add this person (<a href='".APP_PATH_WEBROOT_ALL."DataEntry/record_home.php?pid=".$pidsArray['PEOPLE']."'>via REDCap</a>) or list their PI's name instead.)</div>"
        ),
        'reviewer_id' => array (
            'query' => "SELECT a.record, CONCAT(a.value, ' ', b.value) as value FROM (SELECT record, value FROM [data-table:".$pidsArray['PEOPLE']."] WHERE project_id = ".$pidsArray['PEOPLE']." AND field_name = 'firstname') a JOIN (SELECT record, value FROM [data-table:".$pidsArray['PEOPLE']."] where project_id = ".$pidsArray['PEOPLE']." and field_name = 'lastname') b ON b.record=a.record JOIN (SELECT record, value from [data-table:".$pidsArray['PEOPLE']."] where project_id = ".$pidsArray['PEOPLE']." and field_name = 'harmonistadmin_y' and value = 1) c ON c.record=a.record ORDER BY a.value, b.value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'responding_region' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM [data-table:".$pidsArray['REGIONS']."] a  WHERE a.project_id=".$pidsArray['REGIONS']."  GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'finalizer_id' => array (
            'query' => "SELECT a.record, CONCAT(a.value, ' ', b.value) as value FROM (SELECT record, value FROM [data-table:".$pidsArray['PEOPLE']."] WHERE project_id = ".$pidsArray['PEOPLE']." AND field_name = 'firstname') a JOIN (SELECT record, value FROM [data-table:".$pidsArray['PEOPLE']."] where project_id = ".$pidsArray['PEOPLE']." and field_name = 'lastname') b ON b.record=a.record JOIN (SELECT record, value from [data-table:".$pidsArray['PEOPLE']."] where project_id = ".$pidsArray['PEOPLE']." and field_name = 'harmonistadmin_y' and value = 1) c ON c.record=a.record ORDER BY a.value, b.value",
            'autocomplete' => '0',
            'label' => ""
        )
    ),
    $pidsArray['COMMENTSVOTES']=>array(
        'response_person' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM [data-table:".$pidsArray['PEOPLE']."] a  WHERE a.project_id=".$pidsArray['PEOPLE']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'response_region' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM [data-table:".$pidsArray['REGIONS']."] a  WHERE a.project_id=".$pidsArray['REGIONS']."  GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        )
    ),
    $pidsArray['SOP']=>array(
        'sop_hubuser' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM [data-table:".$pidsArray['PEOPLE']."] a  WHERE a.project_id=".$pidsArray['PEOPLE']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'sop_creator' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM [data-table:".$pidsArray['PEOPLE']."] a  WHERE a.project_id=".$pidsArray['PEOPLE']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'sop_creator2' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM [data-table:".$pidsArray['PEOPLE']."] a  WHERE a.project_id=".$pidsArray['PEOPLE']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'sop_datacontact' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM [data-table:".$pidsArray['PEOPLE']."] a  WHERE a.project_id=".$pidsArray['PEOPLE']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'sop_concept_id' => array (
            'query' => "SELECT a.record, CONCAT(a.value, ' | ', b.value) FROM (SELECT record, value FROM [data-table:".$pidsArray['HARMONIST']."] WHERE project_id = ".$pidsArray['HARMONIST']." AND field_name = 'concept_id') a JOIN (SELECT record, value FROM [data-table:".$pidsArray['HARMONIST']."] where project_id = ".$pidsArray['HARMONIST']." and field_name = 'concept_title') b ON b.record=a.record ORDER BY a.value, b.value",
            'autocomplete' => '1',
            'label' => ""
        ),
        'sop_finalize_person' => array (
            'query' => "SELECT DISTINCT a.record, CONCAT(a.value, ' ', b.value) AS VALUE FROM [data-table:".$pidsArray['PEOPLE']."] a LEFT JOIN [data-table:".$pidsArray['PEOPLE']."] b on b.project_id = ".$pidsArray['PEOPLE']." and b.record = a.record and b.field_name = 'lastname' LEFT JOIN [data-table:".$pidsArray['PEOPLE']."] c on c.project_id = ".$pidsArray['PEOPLE']." and c.record = a.record WHERE a.field_name = 'firstname' and a.project_id = ".$pidsArray['PEOPLE']." and ((c.field_name = 'harmonist_perms' AND c.value = '1') OR (c.field_name = 'harmonistadmin_y' AND c.value = '1')) ORDER BY a.value, b.value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'data_region' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM [data-table:".$pidsArray['REGIONS']."] a  WHERE a.project_id=".$pidsArray['REGIONS']." GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        )
    ),
    $pidsArray['SOPCOMMENTS']=>array(
        'response_person' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM [data-table:".$pidsArray['PEOPLE']."] a  WHERE a.project_id=".$pidsArray['PEOPLE']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'response_region' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM [data-table:".$pidsArray['REGIONS']."] a  WHERE a.project_id=".$pidsArray['REGIONS']."  GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        )
    ),
    $pidsArray['PEOPLE']=>array(
        'person_region' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM [data-table:".$pidsArray['REGIONS']."] a  WHERE a.project_id=".$pidsArray['REGIONS']."  GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        )
    ),
    $pidsArray['DATAUPLOAD']=>array(
        'data_assoc_concept' => array (
            'query' => "SELECT a.record, CONCAT(a.value, ' | ', b.value) FROM (SELECT record, value FROM [data-table:".$pidsArray['HARMONIST']."] WHERE project_id = ".$pidsArray['HARMONIST']." AND field_name = 'concept_id') a JOIN (SELECT record, value FROM [data-table:".$pidsArray['HARMONIST']."] where project_id = ".$pidsArray['HARMONIST']." and field_name = 'concept_title') b ON b.record=a.record ORDER BY a.value, b.value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'data_assoc_request' => array (
            'query' => "SELECT a.record, CONCAT(a.value, ' | ', b.value) FROM (SELECT record, value FROM [data-table:".$pidsArray['SOP']."] WHERE project_id = ".$pidsArray['SOP']." AND field_name = 'record_id') a JOIN (SELECT record, value FROM [data-table:".$pidsArray['SOP']."] where project_id = ".$pidsArray['SOP']." and field_name = 'sop_name') b ON b.record=a.record ORDER BY a.value, b.value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'data_upload_person' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM [data-table:".$pidsArray['PEOPLE']."] a  WHERE a.project_id=".$pidsArray['PEOPLE']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'data_upload_region' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM [data-table:".$pidsArray['REGIONS']."] a  WHERE a.project_id=".$pidsArray['REGIONS']."  GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'deletion_hubuser' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM [data-table:".$pidsArray['PEOPLE']."] a  WHERE a.project_id=".$pidsArray['PEOPLE']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        )
    ),
    $pidsArray['DATADOWNLOAD']=>array(
        'downloader_assoc_concept' => array (
            'query' => "SELECT a.record, CONCAT(a.value, ' | ', b.value) FROM (SELECT record, value FROM [data-table:".$pidsArray['HARMONIST']."] WHERE project_id = ".$pidsArray['HARMONIST']." AND field_name = 'concept_id') a JOIN (SELECT record, value FROM [data-table:".$pidsArray['HARMONIST']."] where project_id = ".$pidsArray['HARMONIST']." and field_name = 'concept_title') b ON b.record=a.record ORDER BY a.value, b.value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'downloader_id' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM [data-table:".$pidsArray['PEOPLE']."] a  WHERE a.project_id=".$pidsArray['PEOPLE']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'downloader_region' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM [data-table:".$pidsArray['REGIONS']."] a  WHERE a.project_id=".$pidsArray['REGIONS']."  GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'download_id' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'record_id', a.value, NULL)),    ' (',   max(if(a.field_name = 'responsecomplete_ts', a.value, NULL)),   ') ' ) as value  FROM [data-table:".$pidsArray['DATAUPLOAD']."] a  WHERE a.project_id=".$pidsArray['DATAUPLOAD']."  GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        )
    ),
    $pidsArray['DATAAVAILABILITY']=>array(
        'available_table' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'table_name', a.value, NULL)),    ' () ' ) as value  FROM [data-table:".$pidsArray['DATAMODEL']."] a  WHERE a.project_id=".$pidsArray['DATAMODEL']."  GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'available_variable' => array (
            'query' => "SELECT CONCAT(a.record, '|', b.instance), CONCAT(a.value, ' | ', b.value) FROM (SELECT record,value FROM [data-table:".$pidsArray['DATAMODEL']."] WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM [data-table:".$pidsArray['DATAMODEL']."] WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '0',
            'label' => ""
        )
    ),
    $pidsArray['DATATOOLMETRICS']=>array(
        'userregion_id' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM [data-table:".$pidsArray['REGIONS']."] a  WHERE a.project_id=".$pidsArray['REGIONS']."  GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        )
    ),
    $pidsArray['FILELIBRARY']=>array(
        'file_uploader' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM [data-table:".$pidsArray['PEOPLE']."] a  WHERE a.project_id=".$pidsArray['PEOPLE']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '1',
            'label' => ""
        )
    ),
    $pidsArray['FILELIBRARYDOWN']=>array(
        'library_download_person' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM [data-table:".$pidsArray['PEOPLE']."] a  WHERE a.project_id=".$pidsArray['PEOPLE']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'library_download_region' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM [data-table:".$pidsArray['REGIONS']."] a  WHERE a.project_id=".$pidsArray['REGIONS']."  GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        )
    ),
    $pidsArray['NEWITEMS']=>array(
        'news_person' =>  array (
            'query' => "SELECT DISTINCT a.record, CONCAT(a.value, ' ', b.value) AS  VALUE  FROM [data-table:".$pidsArray['PEOPLE']."] a  LEFT JOIN [data-table:".$pidsArray['PEOPLE']."] b on b.project_id = ".$pidsArray['PEOPLE']." and b.record = a.record and b.field_name = 'lastname'  LEFT JOIN [data-table:".$pidsArray['PEOPLE']."] c on c.project_id = ".$pidsArray['PEOPLE']." and c.record = a.record  WHERE a.field_name = 'firstname' and a.project_id = ".$pidsArray['PEOPLE']." and ((c.field_name = 'harmonist_perms' AND c.value = '9') OR (c.field_name = 'harmonistadmin_y' AND c.value = '1'))  ORDER BY     a.value,      b.value",
            'autocomplete' => '0',
            'label' => ""
        )
    ),
    $pidsArray['EXTRAOUTPUTS']=>array(
        'lead_region' =>  array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM [data-table:".$pidsArray['REGIONS']."] a  WHERE a.project_id=".$pidsArray['REGIONS']." GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        )
    ),
    $pidsArray['DATAMODELMETADATA']=>array(
        'index_tablename' =>  array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'table_name', a.value, NULL)),    '  ' ) as value  FROM [data-table:".$pidsArray['DATAMODEL']."] a  WHERE a.project_id=".$pidsArray['DATAMODEL']."  GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'patient_id_var' =>  array (
            'query' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM [data-table:".$pidsArray['DATAMODEL']."] WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM [data-table:".$pidsArray['DATAMODEL']."] WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '0',
            'label' => ""
        ),
        'default_group_var' =>  array (
            'query' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM [data-table:".$pidsArray['DATAMODEL']."] WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM [data-table:".$pidsArray['DATAMODEL']."] WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '0',
            'label' => ""
        ),
        'group_tablename' =>  array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'table_name', a.value, NULL)),    '  ' ) as value  FROM [data-table:".$pidsArray['DATAMODEL']."] a  WHERE a.project_id=".$pidsArray['DATAMODEL']."  GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'birthdate_var' =>  array (
            'query' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM [data-table:".$pidsArray['DATAMODEL']."] WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM [data-table:".$pidsArray['DATAMODEL']."] WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '0',
            'label' => ""
        ),
        'death_date_var' =>  array (
            'query' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM [data-table:".$pidsArray['DATAMODEL']."] WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM [data-table:".$pidsArray['DATAMODEL']."] WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '0',
            'label' => ""
        ),
        'age_date_var' =>  array (
            'query' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM [data-table:".$pidsArray['DATAMODEL']."] WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM [data-table:".$pidsArray['DATAMODEL']."] WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '0',
            'label' => ""
        ),
        'enrol_date_var' =>  array (
            'query' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM [data-table:".$pidsArray['DATAMODEL']."] WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM [data-table:".$pidsArray['DATAMODEL']."] WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '0',
            'label' => ""
        ),
        'height_table' =>  array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'table_name', a.value, NULL)),    '  ' ) as value  FROM [data-table:".$pidsArray['DATAMODEL']."] a  WHERE a.project_id=".$pidsArray['DATAMODEL']."  GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'height_var' =>  array (
            'query' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM [data-table:".$pidsArray['DATAMODEL']."] WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM [data-table:".$pidsArray['DATAMODEL']."] WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '0',
            'label' => ""
        ),
        'height_date' =>  array (
            'query' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM [data-table:".$pidsArray['DATAMODEL']."] WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM [data-table:".$pidsArray['DATAMODEL']."] WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '0',
            'label' => ""
        ),
        'height_units' =>  array (
            'query' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM [data-table:".$pidsArray['DATAMODEL']."] WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM [data-table:".$pidsArray['DATAMODEL']."] WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '0',
            'label' => ""
        )
    ),
    $pidsArray['PROJECTSSTUDIES']=>array(
        'study_concept' => array (
            'query' => "SELECT a.record, CONCAT(a.value, ' | ', b.value) FROM (SELECT record, value FROM [data-table:".$pidsArray['HARMONIST']."] WHERE project_id = ".$pidsArray['HARMONIST']." AND field_name = 'concept_id') a JOIN (SELECT record, value FROM [data-table:".$pidsArray['HARMONIST']."] where project_id = ".$pidsArray['HARMONIST']." and field_name = 'concept_title') b ON b.record=a.record ORDER BY a.value DESC, b.value ",
            'autocomplete' => '1',
            'label' => ""
        ),
        'study_wg' => array (
            'query' => "SELECT a.record, CONCAT( max(if(a.field_name = 'group_name', a.value, '')), ' (', max(if(a.field_name = 'group_abbr', a.value, '')), ') ' ) as value FROM [data-table:".$pidsArray['GROUP']."] a WHERE a.project_id=".$pidsArray['GROUP']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '1',
            'label' => ""
        ),
        'topfile1' => array (
            'query' => "SELECT value FROM (SELECT record,value, IFNULL(instance,1) as instance FROM [data-table:".$pidsArray['PROJECTSSTUDIES']."] WHERE project_id=".$pidsArray['PROJECTSSTUDIES']." AND field_name = 'studyfile_desc' AND record = [record-name]) as value ORDER BY value, instance",
            'autocomplete' => '1',
            'label' => ""
        ),
        'topfile2' => array (
            'query' => "SELECT value FROM (SELECT record,value, IFNULL(instance,1) as instance FROM [data-table:".$pidsArray['PROJECTSSTUDIES']."] WHERE project_id=".$pidsArray['PROJECTSSTUDIES']." AND field_name = 'studyfile_desc' AND record = [record-name]) as value ORDER BY value, instance",
            'autocomplete' => '1',
            'label' => ""
        ),
        'topfile3' => array (
            'query' => "SELECT value FROM (SELECT record,value, IFNULL(instance,1) as instance FROM [data-table:".$pidsArray['PROJECTSSTUDIES']."] WHERE project_id=".$pidsArray['PROJECTSSTUDIES']." AND field_name = 'studyfile_desc' AND record = [record-name]) as value ORDER BY value, instance",
            'autocomplete' => '1',
            'label' => ""
        ),
        'topfile4' => array (
            'query' => "SELECT value FROM (SELECT record,value, IFNULL(instance,1) as instance FROM [data-table:".$pidsArray['PROJECTSSTUDIES']."] WHERE project_id=".$pidsArray['PROJECTSSTUDIES']." AND field_name = 'studyfile_desc' AND record = [record-name]) as value ORDER BY value, instance",
            'autocomplete' => '1',
            'label' => ""
        )
    )
);

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
