<?php
namespace Vanderbilt\DataModelBrowserExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

$project_id = $_REQUEST['pid'];
$hub_projectname = $module->getProjectSetting('hub-projectname');
$hub_profile = $module->getProjectSetting('hub-profile');

$path = $module->framework->getModulePath()."csv/PID_data_dictionary.csv";
$module->framework->importDataDictionary($project_id,$path);
$custom_record_label = "[project_constant]: [project_id]";
$module->query("UPDATE redcap_projects SET custom_record_label = ? WHERE project_id = ?",[$custom_record_label,$project_id]);

$projects_array = array(0=>'DATAMODEL',1=>'CODELIST',2=>'HARMONIST',3=>'RMANAGER',4=>'COMMENTSVOTES',5=>'SOP',6=>'SOPCOMMENTS',
                        7=>'REGIONS',8=>'PEOPLE',9=>'GROUPS', 10=>'FAQ',11=>'HOME',12=>'DATAUPLOAD',13=>'DATADOWNLOAD',
                        14=>'JSONCOPY',15=>'METRICS',16=>'DATAAVAILABILITY',17=>'ISSUEREPORTING',18=>'DATATOOLMETRICS',19=>'DATATOOLUPLOADSECURITY',
                        20=>'FAQDATASUBMISSION',21=>'CHANGELOG',22=>'FILELIBRARY',23=>'FILELIBRARYDOWN',24=>'NEWITEMS',25=>'ABOUT',26=>'EXTRAOUTPUTS',27=>'TBLCENTERREVISED',28=>'SETTINGS');

$projects_array_surveys = array(
    2=>array(
        0=>'concept_sheet',
        1=>'participants',
        2=>'admin_update',
        3=>'quarterly_update_survey',
        4=>'outputs'
    ),
    3=>array(
        0=>'request',
        1=>'admin_review',
        2=>'finalization_of_request',
        3=>'final_docs_request_survey',
        4=>'mr_assignment_survey'
    ),
    4=>array(
        0=>'comments_and_votes'
    ),
    5=>array(
        0=>'data_specification',
        1=>'dhwg_review_request',
        2=>'finalization_of_data_request',
        3=>'data_call_closure'
    ),
    6=>array(
        0=>'sop_comments'
    ),
    8=>array(
        0=>'person_information',
        1=>'user_profile'
    ),
    11=>array(
        0=>'deadlines',
        1=>'announcements'
    ),
    17=>array(
        0=>'issue_report_survey'
    ),
    22>array(
        0=>'file_information'
    ),
    24>array(
        0=>'news_item'
    ),
    26>array(
        0=>'output_record'
    ),
    27>array(
        0=>'tblcenter'
    )
);


$projects_array_show = array(0=>'1',1=>'1',2=>'1',3=>'1',4=>'1',5=>'0',6=>'1',
    7=>'1',8=>'1',9=>'1', 10=>'1',11=>'1',12=>'1',13=>'1',
    14=>'0',15=>'0',16=>'0',17=>'1',18=>'0',19=>'0',
    20=>'0',21=>'1',22=>'0',23=>'0',24=>'0',25=>'0',26=>'0',27=>'0',28=>'1');

$projects_array_name = array(0=>'0A: Data Model',1=>'0B: Code Lists',2=>'1: Concept Sheets',3=>'2: Request Manager',4=>'2B: Comments and Votes',5=>'3: Data Specifications',6=>'3B: SOP Comments',
                        7=>'4: Regions',8=>'5: People',9=>'6: Groups', 10=>'7: FAQ',11=>'8: Homepage Content',12=>'9: Data Uploads',13=>'10: Data Download Logging',
                        14=>'11: Data Standards JSON Copy',15=>'12: Metrics',16=>'13: Data Availability Worksheet',17=>'14: Issue Reporting Survey',
                        18=>'15: Data Toolkit Usage Metrics',19=>'16: Toolkit Data Upload Security',20=>'17: FAQ Data Toolkit',
                        21=>'18: Changelog',22=>'19: File Library',23=>'20: File Library Download Logging',24=>'21: News Items',25=>'22: About',26=>'23: Extra Outputs',27=>'24: tblCENTER',28=>'99: Settings');

$custom_record_label_array = array(0=>"[table_name]",1=>"[list_name]",2=>'<span style=\'color:#[dashboard_color]\'><b>[concept_id]</b> [contact_link]</span>',
                        3=>'[contact_name], [request_type] (Due: [due_d])',4=>"[request_id], [response_person]",5=>'[sop_hubuser]',6=>'',
                        7=>'([region_name], [region_code])',8=>'[firstname] [lastname]',9=>'[group_abbr], [group_name]', 10=>'[help_question]',
                        11=>'',12=>'',13=>'[download_id], [downloader_id]', 14=>'[type]',15=>'',16=>'[available_variable], [available_status]',17=>'',
                        18=>'[action_ts], [action_step]',19=>'', 20=>'',21=>'',22=>'',23=>'',24=>'',25=>'',
                        26=>'<span style=\'color:#[dashboard_color]\'><b>([producedby_region:value]) [output_year] [output_type]</b> | [output_title]', 27=>'([name])',28=>'');

$projects_array_repeatable = array(
    0=>array(0=>array('status'=>1,'instrument'=>'variable_metadata','params'=>'[variable_name]')),
    1=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    2=>array(
        0=>array('status'=>1,'instrument'=>'participants','params'=>'[person_role], [person_link]'),
        1=>array('status'=>1,'instrument'=>'admin_update','params'=>'[adminupdate_d]'),
        2=>array('status'=>1,'instrument'=>'quarterly_update_survey','params'=>'[update_d]')
    ),
    3=>array(0=>array('status'=>1,'instrument'=>'dashboard_region_status','params'=>'[responding_region]')),
    4=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    5=>array(0=>array('status'=>1,'instrument'=>'region_participation_status','params'=>'[data_region], [data_response_status]')),
    6=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    7=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    8=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    9=>array(0=>array('status'=>1,'instrument'=>'meeting','params'=>'[meeting_d]')),
    10=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    11=>array(0=>array('status'=>1,'instrument'=>'quick_links_section','params'=>'[links_sectionhead]')),
    12=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    13=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    14=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    15=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    16=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    17=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    18=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    19=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    20=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    21=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    22=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    23=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    24=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    25=>array(0=>array('status'=>1,'instrument'=>'about_members','params'=>'')),
    26=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    27=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    28=>array(0=>array('status'=>0,'instrument'=>'','params'=>''))
);

$projects_array_hooks = array(0=>'1',1=>'1',2=>'1',3=>'1',4=>'1',5=>'1',6=>'1',
    7=>'0',8=>'1',9=>'0', 10=>'0',11=>'1',12=>'0',13=>'0',
    14=>'0',15=>'0',16=>'0',17=>'0',18=>'0',19=>'0',
    20=>'0',21=>'0',22=>'0',23=>'0',24=>'0',25=>'0',26=>'0',27=>'1',28=>'0');

$projects_array_module_seamlessiframe = array(0=>'0',1=>'0',2=>'0',3=>'0',4=>'0',5=>'0',6=>'0',
    7=>'0',8=>'0',9=>'0', 10=>'0',11=>'1',12=>'0',13=>'0',
    14=>'0',15=>'0',16=>'0',17=>'0',18=>'0',19=>'0',
    20=>'0',21=>'0',22=>'0',23=>'0',24=>'0',25=>'0',26=>'0',27=>'0',28=>'0');

$projects_array_surveys_hash = array(
    2=>array('constant'=>'CONCEPTLINK','instrument' => 'concept_sheet'),
    3=>array('constant'=>'REQUESTLINK','instrument' => 'request'),
    4=>array('constant'=>'SURVEYLINK','instrument' => 'comments_and_votes'),
    6=>array('constant'=>'SURVEYLINKSOP','instrument' => 'sop_comments'),
    8=>array('constant'=>'SURVEYPERSONINFO','instrument' => 'person_information'),
    17=>array('constant'=>'REPORTBUGSURVEY','instrument' => 'issue_report_survey'),
    22=>array('constant'=>'SURVEYFILELIBRARY','instrument' => 'file_information'),
    24=>array('constant'=>'SURVEYNEWS','instrument' => 'news_item'),
    27=>array('constant'=>'SURVEYTBLCENTERREVISED','instrument' => 'tblcenter')
);


$record = 1;
foreach ($projects_array as $index=>$name){
    $project_title = $hub_projectname." Hub ".$projects_array_name[$index];
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
        }else if($hub_profile == 'basic'){

        }else if($hub_profile == 'all'){

        }

        \Records::addRecordToRecordListCache($project_id_new, $record,1);
    }

    foreach($projects_array_repeatable[$index] as $repeat_event){
        if($repeat_event['status'] == 1){
            $q = $module->query("SELECT b.event_id FROM  redcap_events_arms a LEFT JOIN redcap_events_metadata b ON(a.arm_id = b.arm_id) where a.project_id = ?",[$project_id_new]);
            while ($row = $q->fetch_assoc()) {
                $event_id = $row['event_id'];
                $module->query("INSERT INTO redcap_events_repeat (event_id, form_name, custom_repeat_form_label) VALUES (?, ?, ?)",[$event_id,$repeat_event['instrument'],$repeat_event['params']]);
            }
        }
    }

    #enable modules in projects
    if($projects_array_hooks[$index] == '1') {
        #enable current module to activate hooks
        $module->enableModule($project_id_new, "");
    }
    if($projects_array_module_seamlessiframe[$index] == '1'){
        #enable modules to certain projects
        $module->enableModule($project_id_new,"seamless-iframes-module");
        $othermodule = ExternalModules::getModuleInstance("seamless-iframes-module");
        $othermodule->setProjectSetting("allowed-url-prefixes", APP_PATH_WEBROOT_FULL."external_modules/?prefix=harmonist-hub&page=index?pid=".$project_id, $project_id_new);
    }

    \Records::addRecordToRecordListCache($project_id, $record,1);
    $record++;

   #we create the surveys
    if(array_key_exists($index,$projects_array_surveys)){
        foreach ($projects_array_surveys[$index] as $survey){
            $formName = ucwords(str_replace("_"," ",$survey));
            $module->query("INSERT INTO redcap_surveys (project_id,form_name,survey_enabled,save_and_return,save_and_return_code_bypass,edit_completed_response,title) VALUES (?,?,?,?,?)",[$project_id_new,$survey,1,1,1,$formName]);
        }
    }
    if(array_key_exists($index,$projects_array_surveys_hash)){
        $hash = $module->getPublicSurveyHash($project_id_new);

        $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'record_id', $record);
        $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'project_id', $hash);
        $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'project_constant', $projects_array_surveys_hash[$index]['constant']);
        $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'project_show_y', 0);
        $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'project_info_complete', 2);

        \Records::addRecordToRecordListCache($project_id, $record,1);
        $record++;
    }


}
#Upload SQL fields to projects
include_once("projects.php");

$projects_array_sql = array(
    IEDEA_DATAMODEL=>array(
        'variable_replacedby' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM redcap_data WHERE project_id=".IEDEA_DATAMODEL." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM redcap_data WHERE project_id=".IEDEA_DATAMODEL." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
        'code_list_ref' => "select record, value from redcap_data where project_id = ".IEDEA_CODELIST." and field_name = 'list_name' order by value asc"
    ),
    IEDEA_HARMONIST=>array(
        'contact_link' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_PEOPLE." GROUP BY a.record ORDER BY value",
        'wg_link' => "SELECT a.record, CONCAT( max(if(a.field_name = 'group_name', a.value, '')), ' (', max(if(a.field_name = 'group_abbr', a.value, '')), ') ' ) as value FROM redcap_data a WHERE a.project_id=".IEDEA_GROUPS." GROUP BY a.record ORDER BY value",
        'wg2_link' => "SELECT a.record, CONCAT( max(if(a.field_name = 'group_name', a.value, '')), ' (', max(if(a.field_name = 'group_abbr', a.value, '')), ') ' ) as value FROM redcap_data a WHERE a.project_id=".IEDEA_GROUPS." GROUP BY a.record ORDER BY value",
        'lead_region' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_REGIONS." GROUP BY a.record  ORDER BY value",
        'person_link' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_PEOPLE." GROUP BY a.record ORDER BY value"
    ),
    IEDEA_RMANAGER=>array(
        'assoc_concept' => "SELECT a.record, CONCAT(a.value, ' | ', b.value) FROM (SELECT record, value FROM redcap_data WHERE project_id = ".IEDEA_HARMONIST." AND field_name = 'concept_id') a JOIN (SELECT record, value FROM redcap_data where project_id = ".IEDEA_HARMONIST." and field_name = 'concept_title') b ON b.record=a.record ORDER BY a.value DESC, b.value ",
        'contact_region' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_REGIONS."  GROUP BY a.record  ORDER BY value",
        'contactperson_id' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_PEOPLE." GROUP BY a.record ORDER BY value",
        'reviewer_id' => "SELECT a.record, CONCAT(a.value, ' ', b.value) as value FROM (SELECT record, value FROM redcap_data WHERE project_id = ".IEDEA_PEOPLE." AND field_name = 'firstname') a JOIN (SELECT record, value FROM redcap_data where project_id = ".IEDEA_PEOPLE." and field_name = 'lastname') b ON b.record=a.record JOIN (SELECT record, value from redcap_data where project_id = ".IEDEA_PEOPLE." and field_name = 'harmonistadmin_y' and value = 1) c ON c.record=a.record ORDER BY a.value, b.value",
        'responding_region' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_REGIONS."  GROUP BY a.record  ORDER BY value",
        'finalizer_id' => "SELECT a.record, CONCAT(a.value, ' ', b.value) as value FROM (SELECT record, value FROM redcap_data WHERE project_id = ".IEDEA_PEOPLE." AND field_name = 'firstname') a JOIN (SELECT record, value FROM redcap_data where project_id = ".IEDEA_PEOPLE." and field_name = 'lastname') b ON b.record=a.record JOIN (SELECT record, value from redcap_data where project_id = ".IEDEA_PEOPLE." and field_name = 'harmonistadmin_y' and value = 1) c ON c.record=a.record ORDER BY a.value, b.value",
        'mr_existing' => "SELECT a.record, CONCAT(a.value, ' | ', b.value) FROM (SELECT record, value FROM redcap_data WHERE project_id = ".IEDEA_HARMONIST." AND field_name = 'concept_id') a JOIN (SELECT record, value FROM redcap_data where project_id = ".IEDEA_HARMONIST." and field_name = 'concept_title') b ON b.record=a.record ORDER BY a.value, b.value"
    ),
    IEDEA_COMMENTSVOTES=>array(
        'response_person' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_PEOPLE." GROUP BY a.record ORDER BY value",
        'response_region' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_REGIONS."  GROUP BY a.record  ORDER BY value"
    ),
    IEDEA_SOP=>array(
        'sop_hubuser' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_PEOPLE." GROUP BY a.record ORDER BY value",
        'sop_creator' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_PEOPLE." GROUP BY a.record ORDER BY value",
        'sop_creator2' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_PEOPLE." GROUP BY a.record ORDER BY value",
        'sop_datacontact' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_PEOPLE." GROUP BY a.record ORDER BY value",
        'sop_concept_id' => "SELECT a.record, CONCAT(a.value, ' | ', b.value) FROM (SELECT record, value FROM redcap_data WHERE project_id = ".IEDEA_HARMONIST." AND field_name = 'concept_id') a JOIN (SELECT record, value FROM redcap_data where project_id = ".IEDEA_HARMONIST." and field_name = 'concept_title') b ON b.record=a.record ORDER BY a.value, b.value",
        'sop_finalize_person' => "SELECT DISTINCT a.record, CONCAT(a.value, ' ', b.value) AS VALUE FROM redcap_data a LEFT JOIN redcap_data b on b.project_id = ".IEDEA_PEOPLE." and b.record = a.record and b.field_name = 'lastname' LEFT JOIN redcap_data c on c.project_id = ".IEDEA_PEOPLE." and c.record = a.record WHERE a.field_name = 'firstname' and a.project_id = ".IEDEA_PEOPLE." and ((c.field_name = 'harmonist_perms' AND c.value = '1') OR (c.field_name = 'harmonistadmin_y' AND c.value = '1')) ORDER BY a.value, b.value",
        'data_region' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_REGIONS." GROUP BY a.record  ORDER BY value"
    ),
    IEDEA_SOPCOMMENTS=>array(
        'response_person' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_PEOPLE." GROUP BY a.record ORDER BY value",
        'response_region' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_REGIONS."  GROUP BY a.record  ORDER BY value"
    ),
    IEDEA_PEOPLE=>array(
        'person_region' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_REGIONS."  GROUP BY a.record  ORDER BY value"
    ),
    IEDEA_DATAUPLOAD=>array(
        'data_assoc_concept' => "SELECT a.record, CONCAT(a.value, ' | ', b.value) FROM (SELECT record, value FROM redcap_data WHERE project_id = ".IEDEA_HARMONIST." AND field_name = 'concept_id') a JOIN (SELECT record, value FROM redcap_data where project_id = ".IEDEA_HARMONIST." and field_name = 'concept_title') b ON b.record=a.record ORDER BY a.value, b.value",
        'data_assoc_request' => "SELECT a.record, CONCAT(a.value, ' | ', b.value) FROM (SELECT record, value FROM redcap_data WHERE project_id = ".IEDEA_SOP." AND field_name = 'record_id') a JOIN (SELECT record, value FROM redcap_data where project_id = ".IEDEA_SOP." and field_name = 'sop_name') b ON b.record=a.record ORDER BY a.value, b.value",
        'data_upload_person' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_PEOPLE." GROUP BY a.record ORDER BY value",
        'data_upload_region' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_REGIONS."  GROUP BY a.record  ORDER BY value",
        'deletion_hubuser' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_PEOPLE." GROUP BY a.record ORDER BY value"
    ),
    IEDEA_DATADOWNLOAD=>array(
        'downloader_assoc_concept' => "SELECT a.record, CONCAT(a.value, ' | ', b.value) FROM (SELECT record, value FROM redcap_data WHERE project_id = ".IEDEA_HARMONIST." AND field_name = 'concept_id') a JOIN (SELECT record, value FROM redcap_data where project_id = ".IEDEA_HARMONIST." and field_name = 'concept_title') b ON b.record=a.record ORDER BY a.value, b.value",
        'downloader_id' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_PEOPLE." GROUP BY a.record ORDER BY value",
        'downloader_region' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_REGIONS."  GROUP BY a.record  ORDER BY value",
        'download_id' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'record_id', a.value, NULL)),    ' (',   max(if(a.field_name = 'responsecomplete_ts', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_DATAUPLOAD."  GROUP BY a.record  ORDER BY value"
    ),
    IEDEA_DATAAVAILABILITY=>array(
        'available_table' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'table_name', a.value, NULL)),    ' () ' ) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_DATAMODEL."  GROUP BY a.record  ORDER BY value",
        'available_variable' => "SELECT CONCAT(a.record, '|', b.instance), CONCAT(a.value, ' | ', b.value) FROM (SELECT record,value FROM redcap_data WHERE project_id=".IEDEA_DATAMODEL." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM redcap_data WHERE project_id=".IEDEA_DATAMODEL." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance"
    ),
    IEDEA_DATATOOLMETRICS=>array(
        'userregion_id' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_REGIONS."  GROUP BY a.record  ORDER BY value"
    ),
    IEDEA_FILELIBRARY=>array(
        'file_uploader' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_PEOPLE." GROUP BY a.record ORDER BY value"
    ),
    IEDEA_FILELIBRARYDOWN=>array(
        'library_download_person' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_PEOPLE." GROUP BY a.record ORDER BY value",
        'library_download_region' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_REGIONS."  GROUP BY a.record  ORDER BY value"
    ),
    IEDEA_NEWITEMS=>array(
        'news_person' => "SELECT DISTINCT a.record, CONCAT(a.value, ' ', b.value) AS  VALUE  FROM redcap_data a  LEFT JOIN redcap_data b on b.project_id = ".IEDEA_PEOPLE." and b.record = a.record and b.field_name = 'lastname'  LEFT JOIN redcap_data c on c.project_id = ".IEDEA_PEOPLE." and c.record = a.record  WHERE a.field_name = 'firstname' and a.project_id = ".IEDEA_PEOPLE." and ((c.field_name = 'harmonist_perms' AND c.value = '9') OR (c.field_name = 'harmonistadmin_y' AND c.value = '1'))  ORDER BY     a.value,      b.value"
    ),
    IEDEA_EXTRAOUTPUTS=>array(
        'lead_region' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_REGIONS." GROUP BY a.record  ORDER BY value"
    )
);

foreach ($projects_array_sql as $projectid=>$project){
    foreach ($project as $var=>$sql){
        $module->query("UPDATE redcap_metadata SET element_enum = ? WHERE project_id = ? AND field_name=?",[$sql,$projectid,$var]);
    }
}


echo json_encode(array(
        'status' =>'success'
    )
);
?>
