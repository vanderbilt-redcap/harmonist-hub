<?php
namespace Vanderbilt\HarmonistHubExternalModule;

use Exception;
use REDCap;

//require_once(__DIR__ ."/projects.php");

class HarmonistHubExternalModule extends \ExternalModules\AbstractExternalModule
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
        $path = $this->framework->getModulePath()."csv/".$value_constant."_data_dictionary.csv";
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
        $hub_mapper = 366;
        $this->setProjectConstants($hub_mapper);

        #Depending on the project que add one hook or another
        if($project_id == IEDEA_SOP && $instrument == 'dhwg_review_request') {
            include_once("sop/sop_make_public_request_AJAX.php?record=".$record);
            echo '<script>parent.location.href = '.json_encode($this->getUrl("index.php?pid=".$hub_mapper."&option=smn&record='.$record.'&message=P")).'</script>';
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
        $hub_mapper = 366;
        $this->setProjectConstants($hub_mapper);

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
}

?>