<?php
namespace Vanderbilt\HarmonistHubExternalModule;

use Exception;
use REDCap;
use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

require_once(dirname(__FILE__)."/classes/REDCapManagement.php");
require_once(dirname(__FILE__)."/projects.php");

class HarmonistHubExternalModule extends AbstractExternalModule
{

    public function __construct()
    {
        parent::__construct();
    }

    #If it's not the main PID project hide the Harmonist Hub link
    public function redcap_module_link_check_display($project_id, $link ) {
        $hub_projectname = $this->getProjectSetting('hub-projectname');
        $dd_array = \REDCap::getDataDictionary('array');

        if($hub_projectname != ""){
            $link['name'] = $hub_projectname." Hub";
        }else{
            return false;
        }

        return parent::redcap_module_link_check_display($project_id,$link);
    }

    function addProjectToList($project_id, $eventId, $record, $fieldName, $value){
        $this->query("INSERT INTO redcap_data (project_id, event_id, record, field_name, value) VALUES (?, ?, ?, ?, ?)",
            [$project_id, $eventId, $record, $fieldName, $value]);
    }

    function createProjectAndImportDataDictionary($value_constant,$project_title)
    {
        $project_id = $this->framework->createProject($project_title." (".ucfirst(strtolower($value_constant)).")", 0);
        $path = $this->framework->getModulePath()."csv/".$value_constant.".csv";
        $this->framework->importDataDictionary($project_id,$path);

        return $project_id;
    }

    function redcap_save_record($project_id,$record,$instrument,$event_id){
        echo '<script>';
        include_once("js/iframe.js");
        echo '</script>';
    }

    function redcap_survey_acknowledgement_page($project_id, $record, $instrument, $event_id){
        //        $hub_mapper = $this->getProjectSetting('hub-mapper');
//        $hub_mapper = 366;
//        $this->setProjectConstants($hub_mapper);

        #Depending on the project que add one hook or another
        if($project_id == IEDEA_SOP && $instrument == 'dhwg_review_request') {
            include_once("sop/sop_make_public_request_AJAX.php?record=".$record);
            echo '<script>parent.location.href = '.json_encode($this->getUrl("index.php?pid=".IEDEA_PROJECTS."&option=smn&record='.$record.'&message=P")).'</script>';
        }else{
            if($project_id == IEDEA_SOP){
                include_once("hooks/save_record_SOP.php");
            }else if($project_id == IEDEA_RMANAGER){
                include_once("hooks/save_record_requestManager.php");
            }else if($project_id == IEDEA_COMMENTSVOTES){
                include_once("hooks/save_record_commentsAndVotes.php");
            }else if($project_id == IEDEA_SOPCOMMENTS){
                include_once("hooks/save_record_SOP_comments.php");
            }else if($project_id == IEDEA_DATAMODEL){
                checkAndUpdatJSONCopyProject($this, '0a');
            }else if($project_id == IEDEA_CODELIST) {
                checkAndUpdatJSONCopyProject($this, '0b');
            }
            echo '<script>';
            include_once("js/iframe.js");
            echo '</script>';
        }
    }

    function hook_every_page_top($project_id){
//        $hub_mapper = $this->getProjectSetting('hub-mapper');
//        $hub_mapper = 366;
//        $this->setProjectConstants($hub_mapper);

        #Add to all projects needed
        if($project_id == IEDEA_HARMONIST){
            if($_REQUEST['s'] != "" || $_REQUEST['sq'] != ""){
                echo "<script>
                    $(document).ready(function() {
                        $('[name=submit-btn-savereturnlater]').hide();
                        $('#return_code_completed_survey_div').hide();
                        $('#surveytitlelogo').hide();
                        $('.bubbleInfo').hide();
                        $('#two_factor_verification_code_btn span').show();
                        $('body').css('background-color','#fff');
                        $('[name=submit-btn-saverecord]').text('Submit');
                        $('.questionnum ').hide();

                        //For Queue Surveys
                        $('table#table-survey_queue .hidden').removeClass('hidden').hide().show('fade');
                        $('.wrap a').parent().parent().parent().parent().hide();
                        $( 'span:contains(\'Close survey queue\')' ).parent().parent().hide();
                        $( 'span:contains(\'Close survey\')' ).parent().parent().hide();
                    });
                </script>";
            }
        }else {
            if ($project_id == IEDEA_TBLCENTERREVISED || $project_id == IEDEA_SOPCOMMENTS || $project_id == IEDEA_HOME || $project_id == IEDEA_COMMENTSVOTES || $project_id == IEDEA_RMANAGER || ($project_id == IEDEA_PEOPLE && $_REQUEST['s'] != IEDEA_SURVEYPERSONINFO) || $project_id == IEDEA_SOP && $_REQUEST['s'] != IEDEA_DATARELEASEREQUEST) {
                echo "<script>
                    $(document).ready(function() {
                        $('[name=submit-btn-savereturnlater]').hide();
                        $('#return_code_completed_survey_div').hide();
                        $('#surveytitlelogo').hide();
                        $('.bubbleInfo').hide();
                        $('#pagecontent span.ui-button-text').hide();
                        $('#two_factor_verification_code_btn span').show();
                        $('body').css('background-color','#fff');
                        $('[name=submit-btn-saverecord]').text('Submit');
                    });
                </script>";
            }
            echo "<script>
                $(document).ready(function() {
                    $('.questionnum ').hide();
                });
            </script>";
        }
    }

    function cronMethod($cronAttributes){
        if($cronAttributes['cron_name'] == 'cron_metrics'){
            include ("crontasks/cron_metrics.php");
        }else if($cronAttributes['cron_name'] == 'cron_delete'){
            include ("crontasks/cron_delete_AWS.php");
        }else if($cronAttributes['cron_name'] == 'cron_data_upload_expiration_reminder'){
            include ("crontasks/cron_data_upload_expiration_reminder.php");
        }else if($cronAttributes['cron_name'] == 'cron_data_upload_notification'){
            include ("crontasks/cron_data_upload_notification.php");
        }else if($cronAttributes['cron_name'] == 'cron_monthly_digest' && date('w', strtotime(date('Y-m-d'))) === '1'){
           //Every First Monday of the Month
            include ("crontasks/cron_monthly_digest.php");
        }else if($cronAttributes['cron_name'] == 'cron_publications'){
            include ("crontasks/cron_publications.php");
        }
    }

    function hook_every_page_before_render($project_id=null) {
        if (PAGE == "ProjectSetup/index.php") {
//            echo "<script src='".CareerDev::link("/js/jquery.min.js")."'></script>\n";   // change this line to ensure that jquery is included
            echo "
            <script>
                $(document).ready(function() { $('.chklist.round:eq(6)').hide(); });
            </script>\n";
        }
    }

    function setProjectConstants($project_id){
        # Define the projects stored in MAPPER
        $projects = \REDCap::getData(array('project_id'=>$project_id),'array');

        $linkedProjects = array();
        foreach ($projects as $event){
            foreach ($event as $project) {
                define(ENVIRONMENT . '_IEDEA_' . $project['project_constant'], $project['project_id']);
                array_push($linkedProjects,"IEDEA_".$project['project_constant']);
            }
        }

        # Define the environment for each project
        foreach($linkedProjects as $projectTitle) {
            if(defined(ENVIRONMENT."_".$projectTitle)) {
                define($projectTitle, constant(ENVIRONMENT."_".$projectTitle));

            }
        }
    }

    function compareDataDictionaries(){
        $projects_array = REDCapManagement::getProjectsContantsArray();
        $var_replace_type = array(0 => "additions", 1 => "changed", 3 => "missing");

        foreach ($var_replace_type as $type){
            ${$type} = array();
            ${"alert_text_".$type} = "";
        }
        foreach ($projects_array as $index => $constant) {
            $metadata = array();
            $metadata["destination"] = \REDCap::getDataDictionary(constant("IEDEA_".$constant), 'array', false);
            $metadata["origin"] = $this->dataDictionaryCSVToMetadataArray($this->framework->getModulePath()."csv/".$constant.".csv");

            $iedea_contant = constant("IEDEA_".$constant);
            $deletionRegEx = "/___delete$/";

            foreach ($var_replace_type as $type){
                ${$type}[$iedea_contant] = array();
            }

            $fieldList = array();
            $indexedMetadata = array();
            $choices = array();
            foreach ($metadata as $type => $metadataRows) {
                $choices[$type] = REDCapManagement::getChoices(json_decode($metadata[$type], true));
                $fieldList[$type] = array();
                $indexedMetadata[$type] = array();
                foreach ($metadataRows as $row) {
                    $fieldList[$type][$row['field_name']] = $row['select_choices_or_calculations'];
                    $indexedMetadata[$type][$row['field_name']] = $row;
                }
            }

            $metadataFields = REDCapManagement::getMetadataFieldsToScreen();
            foreach ($fieldList["origin"] as $field => $choiceStr) {
                if (!isset($fieldList["destination"][$field])) {
                    array_push($missing[$iedea_contant], $field);
                    if (!preg_match($deletionRegEx, $field)) {
                        array_push($additions[$iedea_contant], $field);
                    }
                } else if ($choices["origin"][$field] && $choices["destination"][$field] && !REDCapManagement::arraysEqual($choices["origin"][$field], $choices["destination"][$field])) {
                    array_push($missing[$iedea_contant], $field);
                    array_push($changed[$iedea_contant], $field);
                } else {
                    foreach ($metadataFields as $metadataField) {
                        if (REDCapManagement::hasMetadataChanged($indexedMetadata["origin"][$field][$metadataField], $indexedMetadata["destination"][$field][$metadataField], $metadataField)) {
                            array_push($missing[$iedea_contant], $field);
                            array_push($changed[$iedea_contant], $field);
                            break; // metadataFields loop
                        }
                    }
                }
            }

            foreach ($var_replace_type as $type){
                if (empty(${$type}[$iedea_contant])){
                    unset(${$type}[$iedea_contant]);
                }
            }
        }

        if (count($additions) + count($changed) > 0) {
            foreach ($projects_array as $index => $constant) {
                foreach ($var_replace_type as $type) {
                    $iedea_constant = constant("IEDEA_" . $constant);
                    if (!empty(${$type}[$iedea_constant])) {
                        $title = $this->framework->getProject($iedea_constant)->getTitle();
                        ${"alert_text_".$type} .= "<ul><li>" . $title . ": <strong>" . implode(", ", ${$type}[$iedea_constant]) . "</strong></li></ul>";
                    }
                }
            }

            echo "<script>var missing = ".json_encode($missing).";</script>\n";
            echo "<div id='metadataWarning' class='install-metadata-box install-metadata-box-danger'>
                        <i class='fa fa-exclamation-circle' aria-hidden='true'></i> An upgrade in your Data Dictionary exists. <a href='javascript:;' onclick='installMetadata(missing,".json_encode($this->getUrl("installMetadata.php?pid=".IEDEA_PROJECTS)).");'>Click here to install.</a>
                        <ul><li>The following fields will be added: ".(empty($alert_text_additions) ? "<i>None</i>" : $alert_text_additions)."</li>
                        <li>The following fields will be changed: ".(empty($alert_text_changed) ? "<i>None</i>" : $alert_text_changed)."</li></ul>
                    </div>";
        }
    }

    function compareRepeatingForms(){
        $projects_array_repeatable = REDCapManagement::getProjectsRepeatableArray();
        $projects_array = REDCapManagement::getProjectsContantsArray();

        $alert = array();
        foreach ($projects_array as $index => $constant) {
            $project_id = constant("IEDEA_" . $constant);
            $alert[$project_id] = array();
            foreach ($projects_array_repeatable[$index] as $repeat_event) {
                if ($repeat_event['status'] == 1) {
                    $found = false;
                    $q = $this->query("SELECT b.event_id FROM  redcap_events_arms a LEFT JOIN redcap_events_metadata b ON(a.arm_id = b.arm_id) where a.project_id = ?", [$project_id]);
                    while ($row = $q->fetch_assoc()) {
                        $event_id = $row['event_id'];
                        $qEvent = $this->query("SELECT custom_repeat_form_label FROM  redcap_events_repeat where  event_id= ? AND form_name=?", [$event_id,$repeat_event['instrument']]);
                        if($qEvent->num_rows > 0){
                            $found = true;
                            while ($row_event = $qEvent->fetch_assoc()) {
                                if($repeat_event['params'] != $row_event['custom_repeat_form_label']) {
                                    #params are different add new ones
                                    $alert[$project_id][$repeat_event['instrument']] = $repeat_event['params'];
                                }
                            }
                        }
                    }
                    if(!$found){
                        #New Instrument
                        $alert[$project_id][$repeat_event['instrument']] = $repeat_event['params'];
                    }
                }
            }
            if(empty($alert[$project_id])){
                unset($alert[$project_id]);
            }
        }

        if(count($alert) > 0){
            echo "<script>var forms = ".json_encode($alert).";</script>\n";
            echo "<div id='formsWarning' class='install-metadata-box install-metadata-box-danger'>
                        <i class='fa fa-exclamation-circle' aria-hidden='true'></i> New Repeatable Forms were found <a href='javascript:;' onclick='installRepeatingForms(forms,".json_encode($this->getUrl("installRepeatingForms.php?pid=".IEDEA_PROJECTS)).");'>Click here to install.</a>";
            foreach ($alert as $project_id => $repeat){
                $title = $this->framework->getProject($project_id)->getTitle();
                echo "<ul>
                        <li><em>".$title."</em></li>";
                foreach ($repeat as $instrument => $params){
                    echo "<ul>
                        <li><strong>".$instrument."</strong>: ".$params."</li>
                        </ul>";
                }
                echo "</ul>";
            }
            echo "</div>";
        }
    }
}

?>