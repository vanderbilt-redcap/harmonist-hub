<?php
namespace Vanderbilt\HarmonistHubExternalModule;

use Exception;
use REDCap;
use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
include_once(__DIR__ . "/classes/REDCapManagement.php");
include_once(__DIR__ . "/classes/ArrayFunctions.php");
include_once(__DIR__ . "/classes/ProjectData.php");

require_once(dirname(__FILE__)."/vendor/autoload.php");

class HarmonistHubExternalModule extends AbstractExternalModule
{

    public function __construct()
    {
        parent::__construct();
    }

    #If it's not the main PID project hide the Harmonist Hub link
    public function redcap_module_link_check_display($project_id, $link) {
        $hub_projectname = $this->getProjectSetting('hub-projectname');

        if($hub_projectname != ""){
            $link['name'] = $hub_projectname." Hub";
        }else{
            return false;
        }

        return parent::redcap_module_link_check_display($project_id,$link);
    }

    function setPIDMapperProject($project_id){
        $hub_projectname = $this->getProjectSetting('hub-projectname');
        $newProjectTitle = strip_tags($hub_projectname." Hub: Parent Project (MAP)");
        $path = $this->framework->getModulePath()."csv/PID.csv";
        $custom_record_label = "[project_constant]: [project_id]";

        $this->framework->importDataDictionary($project_id,$path);
        $this->query("UPDATE redcap_projects SET custom_record_label = ? WHERE project_id = ?",[$custom_record_label,$project_id]);
        $this->query("UPDATE redcap_projects SET app_title = ? WHERE project_id = ?",[$newProjectTitle,$project_id]);
    }

    function addProjectToList($project_id, $eventId, $record, $fieldName, $value){
        $this->query("INSERT INTO redcap_data (project_id, event_id, record, field_name, value) VALUES (?, ?, ?, ?, ?)",
            [$project_id, $eventId, $record, $fieldName, $value]);
    }

    function createProjectAndImportDataDictionary($value_constant,$project_title)
    {
        $project_id = $this->framework->createProject($project_title, 0);
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
        $hub_mapper = $this->getProjectSetting('hub-mapper');
        $this->setProjectConstants($hub_mapper);

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
            }
            echo '<script>';
            include_once("js/iframe.js");
            echo '</script>';
        }
    }

    function redcap_survey_page_top($project_id){
        $hub_mapper = $this->getProjectSetting('hub-mapper');
        $this->setProjectConstants($hub_mapper);

        #Add to all projects needed
        if($project_id == IEDEA_HARMONIST){
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
        if(APP_PATH_WEBROOT[0] == '/'){
            $APP_PATH_WEBROOT_ALL = substr(APP_PATH_WEBROOT, 1);
        }
        define('APP_PATH_WEBROOT_ALL',APP_PATH_WEBROOT_FULL.$APP_PATH_WEBROOT_ALL);

        foreach ($this->getProjectsWithModuleEnabled() as $project_id){
            $RecordSetConstants = \REDCap::getData($project_id, 'array', null,null,null,null,false,false,false,"[project_constant]='SETTINGS'");
            $settingsPID = ProjectData::getProjectInfoArray($RecordSetConstants)[0]['project_id'];
            error_log($settingsPID);
            if($settingsPID != "") {
                $settings = \REDCap::getData(array('project_id' => $settingsPID), 'array')[1][$this->framework->getEventId($settingsPID)];

                #Get Projects ID's
                $pidsArray = REDCapManagement::getPIDsArray($project_id);

                if(!empty($pidsArray)) {
                    #CRONS
                    if ($cronAttributes['cron_name'] == 'cron_metrics') {
                        include("crontasks/cron_metrics.php");
                    } else if ($cronAttributes['cron_name'] == 'cron_delete') {
                        include("crontasks/cron_delete_AWS.php");
                    } else if ($cronAttributes['cron_name'] == 'cron_data_upload_expiration_reminder') {
                        include("crontasks/cron_data_upload_expiration_reminder.php");
                    } else if ($cronAttributes['cron_name'] == 'cron_data_upload_notification') {
                        include("crontasks/cron_data_upload_notification.php");
                    } else if ($cronAttributes['cron_name'] == 'cron_monthly_digest' && date('w', strtotime(date('Y-m-d'))) === '1') {
                        //Every First Monday of the Month
                        include("crontasks/cron_monthly_digest.php");
                    } else if ($cronAttributes['cron_name'] == 'cron_publications') {
                        include("crontasks/cron_publications.php");
                    }
                }
            }
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
        $projects_array = REDCapManagement::getProjectConstantsArrayWithoutDeactivatedProjects();
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
        $projects_array = REDCapManagement::getProjectConstantsArrayWithoutDeactivatedProjects();

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

    function createpdf(){
        if(APP_PATH_WEBROOT[0] == '/'){
            $APP_PATH_WEBROOT_ALL = substr(APP_PATH_WEBROOT, 1);
        }
        define('APP_PATH_WEBROOT_ALL',APP_PATH_WEBROOT_FULL.$APP_PATH_WEBROOT_ALL);

        foreach ($this->getProjectsWithModuleEnabled() as $project_id){
            error_log("createpdf - project_id:" . $project_id);

            $RecordSetConstants = \REDCap::getData($project_id, 'array', null,null,null,null,false,false,false,"[project_constant]='SETTINGS'");
            $settingsPID = getProjectInfoArray($RecordSetConstants)[0]['project_id'];
            if($settingsPID != "") {
                $settings = \REDCap::getData(array('project_id' => $settingsPID), 'array')[1][$this->framework->getEventId($settingsPID)];

                $hasJsoncopyBeenUpdated0a = $this->hasJsoncopyBeenUpdated('0a', $settings, $project_id);
                $hasJsoncopyBeenUpdated0b = $this->hasJsoncopyBeenUpdated('0b', $settings, $project_id);
                if ($hasJsoncopyBeenUpdated0a || $hasJsoncopyBeenUpdated0b) {
                    $this->createAndSavePDFCron($settings, $project_id);
                    $this->createAndSaveJSONCron($project_id);
                } else {
                    $this->checkIfJsonOrPDFBlank($settings, $project_id);
                }
            }
        }
    }

    function regeneratepdf(){
        if(APP_PATH_WEBROOT[0] == '/'){
            $APP_PATH_WEBROOT_ALL = substr(APP_PATH_WEBROOT, 1);
        }
        define('APP_PATH_WEBROOT_ALL',APP_PATH_WEBROOT_FULL.$APP_PATH_WEBROOT_ALL);

        require_once(dirname(__FILE__)."/vendor/autoload.php");
        foreach ($this->getProjectsWithModuleEnabled() as $project_id){
            error_log("Generate PDF - project_id:" . $project_id);

            $RecordSetConstants = \REDCap::getData($project_id, 'array', null,null,null,null,false,false,false,"[project_constant]='SETTINGS'");
            $settingsPID = getProjectInfoArray($RecordSetConstants)[0]['project_id'];
            if($settingsPID != "") {
                $settings = \REDCap::getData(array('project_id' => $settingsPID), 'array')[1][$this->framework->getEventId($settingsPID)];

                if ($settings['des_pdf_regenerate'][1] == '1') {
                    $this->createAndSavePDFCron($settings, $project_id);
                    $this->createAndSaveJSONCron($project_id);

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

    function hasJsoncopyBeenUpdated($type,$settings, $project_id){
        $RecordSetConstants = \REDCap::getData($project_id, 'array', null,null,null,null,false,false,false,"[project_constant]='JSONCOPY'");
        $jsoncopyPID = getProjectInfoArray($RecordSetConstants)[0]['project_id'];
        if(ENVIRONMENT == "DEV"){
            $qtype = $this->query("SELECT MAX(record) as record FROM redcap_data WHERE project_id=? AND field_name=? and value=? order by record",[$jsoncopyPID,'type',$type]);
        }else{
            $qtype = $this->query("SELECT MAX(CAST(record AS Int)) as record FROM redcap_data WHERE project_id=? AND field_name=? and value=? order by record",[$jsoncopyPID,'type',$type]);
        }
        $rowtype = $qtype->fetch_assoc();

        $RecordSetJsonCopy = \REDCap::getData($jsoncopyPID, 'array', array('record_id' => $rowtype['record']));
        $jsoncopy = getProjectInfoArray($RecordSetJsonCopy)[0];
        $today = date("Y-m-d");
        if($jsoncopy["jsoncopy_file"] != "" && strtotime(date("Y-m-d",strtotime($jsoncopy['json_copy_update_d']))) == strtotime($today)){
            return true;
        }else if(empty($jsoncopy) || strtotime(date("Y-m-d",strtotime($jsoncopy['json_copy_update_d']))) == "" || !array_key_exists('json_copy_update_d',$jsoncopy) || !array_key_exists('des_pdf',$settings) || $settings['des_pdf'] == ""){
            $this->checkAndUpdateJSONCopyProject($type, $rowtype['record'], $jsoncopy, $settings, $project_id);
            return true;
        }
        return false;
    }

    function checkIfJsonOrPDFBlank($settings, $project_id){
        if($settings['des_pdf'] == "" || !array_key_exists('des_pdf',$settings)){
            $this->createAndSavePDFCron($settings ,$project_id);
        }
        if($settings['des_variable_search'] == "" || !array_key_exists('des_variable_search',$settings)){
            $this->createAndSaveJSONCron($project_id);
        }
    }

    function createAndSavePDFCron($settings, $project_id){
        error_log("cron - createAndSavePDFCron");

        $RecordSetConstants = \REDCap::getData($project_id, 'array', null,null,null,null,false,false,false,"[project_constant]='DATAMODEL'");
        $dataModelPID = getProjectInfoArray($RecordSetConstants)[0]['project_id'];

        $RecordSetConstants = \REDCap::getData($project_id, 'array', null,null,null,null,false,false,false,"[project_constant]='SETTINGS'");
        $settingsPID = getProjectInfoArray($RecordSetConstants)[0]['project_id'];

        $RecordSetDataModel = \REDCap::getData($dataModelPID, 'array');
        $dataTable = getProjectInfoArrayRepeatingInstruments($RecordSetDataModel);

        if(!empty($dataTable)) {
            $tableHtml = generateTablesHTML_pdf($this, $dataTable,false,false, $project_id, $dataModelPID);
        }

        #FIRST PAGE
        $first_page = "<tr><td align='center'>";
        $first_page .= "<p><span style='font-size: 16pt;font-weight: bold;'>".$settings['des_pdf_title']."</span></p>";
        $first_page .= "<p><span style='font-size: 16pt;font-weight: bold;'>".$settings['des_pdf_subtitle']."</span></p><br/>";
        $first_page .= "<p><span style='font-size: 14pt;font-weight: bold;'>Version: ".date('d F Y')."</span></p><br/>";
        $first_page .= "<p><span style='font-size: 14pt;font-weight: bold;'>".$settings['des_pdf_text']."</span></p><br/>";
        $first_page .= "<span style='font-size: 12pt'>";
        $first_page .= "</span></td></tr></table>";

        #SECOND PAGE
        $second_page = "<p><span style='font-size: 12pt'>".$tableHtml[1]."</span></p>";

        $page_num = '<style>.footer .page-number:after { content: counter(page); } .footer { position: fixed; bottom: 0px;color:grey }a{text-decoration: none;}</style>';

        $img = getFile($this, $settings['des_pdf_logo'],'pdf');

        $html_pdf = "<html><head><meta http-equiv='Content-Type' content='text/html' charset='UTF-8' /><style>* { font-family: DejaVu Sans, sans-serif; }</style></head><body style='font-family:\"Calibri\";font-size:10pt;'>".$page_num
            ."<div class='footer' style='left: 590px;'><span class='page-number'>Page </span></div>"
            ."<div class='mainPDF'><table style='width: 100%;'><tr><td align='center'><img src='".$img."' style='width:200px;padding-bottom: 30px;'></td></tr></table></div>"
            ."<div class='mainPDF' id='page_html_style'><table style='width: 100%;'>".$first_page."<div style='page-break-before: always;'></div>"
            ."<div class='mainPDF'>".$second_page."<div style='page-break-before: always;'></div>"
            ."<p><span style='font-size:16pt'><strong>DES Tables</strong></span></p>"
            .$tableHtml[0]
            ."</div></div>"
            . "</body></html>";

        $filename = $settings['des_wkname']."_DES_".date("Y-m-d_hi",time());
        //SAVE PDF ON DB
        $reportHash = $filename;
        $storedName = md5($reportHash);

        //DOMPDF
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html_pdf);
        $dompdf->setPaper('A4', 'portrait');
        ob_start();
        $dompdf->render();
        #Download option
        $output = $dompdf->output();
        $filesize = file_put_contents(EDOC_PATH.$storedName, $output);

        #Save document on DB
        $q = $this->query("INSERT INTO redcap_edocs_metadata (stored_name,mime_type,doc_name,doc_size,file_extension,gzipped,project_id,stored_date) VALUES(?,?,?,?,?,?,?,?)",
            [$storedName,'application/octet-stream',$reportHash.".pdf",$filesize,'.pdf','0',$settingsPID,date('Y-m-d h:i:s')]);
        $docId = db_insert_id();

        #Add document DB ID to project
        $Proj = new \Project($settingsPID);
        $event_id = $Proj->firstEventId;
        $json = json_encode(array(array('record_id' => 1, 'des_update_d' => date("Y-m-d H:i:s"),'des_pdf'=>$docId)));
        $results = \Records::saveData($settingsPID, 'json', $json,'normal', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
        \Records::addRecordToRecordListCache($settingsPID, 1,$event_id);

        if($settings['des_pdf_notification_email'] != "") {
            $link = $this->getUrl("downloadFile.php?sname=".$storedName."&file=". $filename.".pdf");
            $goto = APP_PATH_WEBROOT_ALL . "DataEntry/index.php?pid=".$settingsPID."&page=pdf&id=1";

            $q = $this->query("select app_title from redcap_projects where project_id = ? limit 1",[$settingsPID]);
            $row = $q->fetch_assoc();
            $project_title = $row['app_title'];

            $subject = "New PDF Generated in ".$settings['des_doc_title'];
            $message = "<div>Changes have been detected and a new PDF has been generated in ".$project_title.".</div><br/>".
                "<div>You can <a href='".$link."'>download the pdf</a> or <a href='".$goto."'>go to the settings project</a>.</div><br/>";

            $environment = "";
            if(ENVIRONMENT == 'DEV' || ENVIRONMENT == 'TEST'){
                $environment = " - ".ENVIRONMENT;
            }
            $sender = $settings['accesslink_sender_email'];
            if($settings['accesslink_sender_email'] == ""){
                $sender = "noreply@vumc.org";
            }

            $attachments = array(
                $filename.".pdf" => EDOC_PATH.$storedName
            );

            $emails = explode(';', $settings['des_pdf_notification_email']);
            foreach ($emails as $email) {
                \REDCap::email($email, $sender, $subject.$environment, $message,"","",$settings['accesslink_sender_name'],$attachments);
            }
        }
    }

    function createAndSaveJSONCron($project_id){
        error_log("createpdf - createAndSaveJSONCron");
        $RecordSetConstants = \REDCap::getData($project_id, 'array', null,null,null,null,false,false,false,"[project_constant]='DATAMODEL'");
        $dataModelPID = getProjectInfoArray($RecordSetConstants)[0]['project_id'];

        $RecordSetDataModel = \REDCap::getData($dataModelPID, 'array');
        $dataTable = getProjectInfoArrayRepeatingInstruments($RecordSetDataModel);
        $dataFormat = $this->getChoiceLabels('data_format', $dataModelPID);

        foreach ($dataTable as $data) {
            $jsonVarArrayAux = array();
            if($data['table_name'] != "") {
                foreach ($data['variable_order'] as $id => $value) {
                    if ($data['variable_name'][$id] != '') {
                        $url = $this->getUrl("browser.php?pid=" . $_GET['pid'] . '&tid=' . $data['record_id'] . '&vid=' . $id . '&option=variableInfo');
                        $jsonVarArrayAux[trim($data['variable_name'][$id])] = array();
                        $variables_array = array(
                            "instance" => $id,
                            "description" => $data['description'][$id],
                            "description_extra" => $data['description_extra'][$id],
                            "code_list_ref" => $data['code_list_ref'][$id],
                            "data_format" => trim($dataFormat[$data['data_format'][$id]]),
                            "code_text" => $data['code_text'][$id],
                            "variable_link" => $url
                        );
                        $jsonVarArrayAux[$data['variable_name'][$id]] = $variables_array;
                    }
                }
                $jsonVarArray = $jsonVarArrayAux;
                $urltid = $this->getUrl("browser.php?pid=" . $_GET['pid'] . '&tid=' . $data['record_id'] . '&option=variables');
                $jsonVarArray['table_link'] = $urltid;
                $jsonArray[trim($data['table_name'])] = $jsonVarArray;
            }
        }
        #we save the new JSON
        if(!empty($jsonArray)){
            $this->saveJSONCopyVarSearch($jsonArray, $project_id);
        }
    }

    function saveJSONCopyVarSearch($jsonArray, $project_id){
        error_log("createpdf - saveJSONCopyVarSearch");
        $RecordSetConstants = \REDCap::getData($project_id, 'array', null,null,null,null,false,false,false,"[project_constant]='DATAMODEL'");
        $settingsPID = getProjectInfoArray($RecordSetConstants)[0]['project_id'];

        #create and save file with json
        $filename = "jsoncopy_file_variable_search_".date("YmdsH").".txt";
        $storedName = date("YmdsH")."_pid".$settingsPID."_".getRandomIdentifier(6).".txt";

        $file = fopen(EDOC_PATH.$storedName,"wb");
        fwrite($file,json_encode($jsonArray,JSON_FORCE_OBJECT));
        fclose($file);

        $output = file_get_contents(EDOC_PATH.$storedName);
        $filesize = file_put_contents(EDOC_PATH.$storedName, $output);

        //Save document on DB
        $q = $this->query("INSERT INTO redcap_edocs_metadata (stored_name,doc_name,doc_size,file_extension,mime_type,gzipped,project_id,stored_date) VALUES(?,?,?,?,?,?,?,?)",
            [$storedName,$filename,$filesize,'txt','application/octet-stream','0',$settingsPID,date('Y-m-d h:i:s')]);
        $docId = db_insert_id();

        //Add document DB ID to project
        $Proj = new \Project($settingsPID);
        $event_id = $Proj->firstEventId;
        $json = json_encode(array(array('record_id' => 1, 'des_variable_search' => $docId)));
        $results = \Records::saveData($settingsPID, 'json', $json,'normal', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
        \Records::addRecordToRecordListCache($settingsPID, 1,$event_id);
    }

    function checkAndUpdateJSONCopyProject($type, $last_record, $jsoncocpy, $settings, $project_id){
        $RecordSetConstants = \REDCap::getData($project_id, 'array', null,null,null,null,false,false,false,"[project_constant]='JSONCOPY'");
        $jsoncopyPID = getProjectInfoArray($RecordSetConstants)[0]['project_id'];

        if($jsoncocpy["jsoncopy_file"] != ""){
            $q = $this->query("SELECT stored_name,doc_name,doc_size,mime_type FROM redcap_edocs_metadata WHERE doc_id=?",[$jsoncocpy["jsoncopy_file"]]);
            while ($row = $q->fetch_assoc()) {
                $path = EDOC_PATH.$row['stored_name'];
                $strJsonFileContents = file_get_contents($path);
                $last_array = json_decode($strJsonFileContents, true);
                $array_data = call_user_func_array("createProject".strtoupper($type)."JSON",array($this, $project_id));
                $new_array = json_decode($array_data['jsonArray'],true);
                $result_prev = array_filter_empty(multi_array_diff($last_array,$new_array));
                $result = array_filter_empty(multi_array_diff($new_array,$last_array));
                $record = $array_data['record_id'];
            }
        }else{
            $array_data = call_user_func_array("createProject".strtoupper($type)."JSON",array($this, $project_id));
            $result = json_decode($array_data['jsonArray'],true);
            $result_prev = "";
            $record = $array_data['record_id'];
        }

        if($last_record == ""){
            $last_record = "<i>None</i>";
        }

        if(!empty($record)){
            $environment = "";
            if(ENVIRONMENT == 'DEV' || ENVIRONMENT == 'TEST'){
                $environment = " ".ENVIRONMENT;
            }

            $sender = $settings['accesslink_sender_email'];
            if($settings['accesslink_sender_email'] == ""){
                $sender = "noreply@vumc.org";
            }

            $link = APP_PATH_WEBROOT_ALL . "DataEntry/record_home.php?pid=" . $jsoncopyPID . "&arm=1&id=" . $record;
            $subject = "Changes in the DES ".strtoupper($type)." detected ";
            $message = "<div>The following changes have been detected in the DES ".strtoupper($type)." and a new record #".$record." has been created:</div><br/>".
                "<div>Last record: ". $last_record."</div><br/>".
                "<div>To see the record <a href='".$link."'>click here</a></div><br/>".
                "<ul><pre>".print_r($result,true)."</pre>".
                "<span style='color:#777'><pre><em>".print_r($result_prev,true)."</em></pre></ul></span>";

            if($settings['des_0a0b_email'] != "") {
                $emails = explode(';', $settings['des_0a0b_email']);
                foreach ($emails as $email) {
                    \REDCap::email($email, $sender, $subject.$environment, $message,"","",$settings['accesslink_sender_name']);
                }
            }
        }
        return null;
    }
}

?>