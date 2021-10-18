<?php
namespace Vanderbilt\HarmonistHubExternalModule;

use Exception;
use REDCap;
use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
include_once(__DIR__ . "/classes/REDCapManagement.php");
include_once(__DIR__ . "/classes/ArrayFunctions.php");
include_once(__DIR__ . "/classes/ProjectData.php");
include_once(__DIR__ . "functions.php");

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

        if($hub_projectname != "" && ($_REQUEST['pid'] == $this->getProjectSetting('hub-mapper') || $this->getProjectSetting('hub-mapper') == "")){
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
//        $this->framework->importDataDictionary($project_id,$path);
        $this->importDataDictionary($project_id,$path);

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

        try {
            #Depending on the project que add one hook or another
            if ($project_id == IEDEA_SOP && $instrument == 'dhwg_review_request') {
                include_once("sop/sop_make_public_request_AJAX.php?record=" . $record);
                echo '<script>parent.location.href = ' . json_encode($this->getUrl("index.php?pid=" . IEDEA_PROJECTS . "&option=smn&record='.$record.'&message=P")) . '</script>';
            } else {
                if ($project_id == IEDEA_SOP) {
                    include_once("hooks/save_record_SOP.php");
                } else if ($project_id == IEDEA_RMANAGER) {
                    error_log("IN");
                    include_once("hooks/save_record_requestManager.php");
                } else if ($project_id == IEDEA_COMMENTSVOTES) {
                    include_once("hooks/save_record_commentsAndVotes.php");
                } else if ($project_id == IEDEA_SOPCOMMENTS) {
                    include_once("hooks/save_record_SOP_comments.php");
                }
                echo '<script>';
                include_once("js/iframe.js");
                echo '</script>';
            }
        }catch (Throwable $e) {
            \REDCap::email('eva.bascompte.moragas@vumc.org', 'harmonist@vumc.org', "Hook Error", $e->getMessage());
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
            if ($project_id == IEDEA_TBLCENTERREVISED || $project_id == IEDEA_SOPCOMMENTS || $project_id == IEDEA_HOME || $project_id == IEDEA_COMMENTSVOTES || ($project_id == IEDEA_RMANAGER && $_REQUEST['s'] != IEDEA_REQUESTLINK) || ($project_id == IEDEA_PEOPLE && $_REQUEST['s'] != IEDEA_SURVEYPERSONINFO) || ($project_id == IEDEA_SOP && $_REQUEST['s'] != IEDEA_DATARELEASEREQUEST)) {
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

            if($settingsPID != "") {
                $settings = \REDCap::getData(array('project_id' => $settingsPID), 'array')[1][$this->framework->getEventId($settingsPID)];

                #Get Projects ID's
                $pidsArray = REDCapManagement::getPIDsArray($project_id);

                if(!empty($pidsArray) && !empty($settings)) {
                    try {
                        #CRONS
                        if ($cronAttributes['cron_name'] == 'cron_metrics' && $settings['deactivate_metrics_cron'][1] != "1") {
                            include("crontasks/cron_metrics.php");
                        } else if ($cronAttributes['cron_name'] == 'cron_delete' && ($settings['deactivate_datadown'][1] != "1" || $settings['deactivate_datahub'][1] != "1")) {
                            include("crontasks/cron_delete_AWS.php");
                        } else if ($cronAttributes['cron_name'] == 'cron_data_upload_expiration_reminder' && ($settings['deactivate_datadown'][1] != "1" || $settings['deactivate_datahub'][1] != "1")) {
                            include("crontasks/cron_data_upload_expiration_reminder.php");
                        } else if ($cronAttributes['cron_name'] == 'cron_data_upload_notification' && ($settings['deactivate_datadown'][1] != "1" || $settings['deactivate_datahub'][1] != "1")) {
                           include("crontasks/cron_data_upload_notification.php");
                        } else if ($cronAttributes['cron_name'] == 'cron_monthly_digest' && date('w', strtotime(date('Y-m-d'))) === '1') {
                            //Every First Monday of the Month
                            include("crontasks/cron_monthly_digest.php");
                        } else if ($cronAttributes['cron_name'] == 'cron_publications') {
                            include("crontasks/cron_publications.php");
                        } else if ($cronAttributes['cron_name'] == 'cron_json') {
                            include("crontasks/cron_json.php");
                        }
                    } catch (Throwable $e) {
                        \REDCap::email('eva.bascompte.moragas@vumc.org', 'harmonist@vumc.org',"Cron Error", $e->getMessage());
                    }
                }
            }
        }
    }

    function hook_every_page_before_render($project_id=null) {
        if (PAGE == "ProjectSetup/index.php") {
            echo "<script type='text/javascript' src='".$this->getUrl('js/jquery-3.3.1.min.js')."'></script>\n";
            echo "
            <style>
                .chklisttext {
                    font-size: 13px;
                }
            </style>
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

    /****CHANGE LATER TO EM FUNCTIONS WHEN FIXED*****/
    function importDataDictionary($project_id,$path){
        $dictionary_array = $this->dataDictionaryCSVToMetadataArray($path, 'array');
//        $dictionary_array = \Desgin::excel_to_array($path);


        //Return warnings and errors from file (and fix any correctable errors)
        list ($errors_array, $warnings_array, $dictionary_array) = \MetaData::error_checking($dictionary_array);
        // Save data dictionary in metadata table
        $sql_errors = $this->saveMetadataCSV($dictionary_array,$project_id);
//        $sql_errors = \MetaData::save_metadata($dictionary_array,false,false,$project_id);

        // Display any failed queries to Super Users, but only give minimal info of error to regular users
        if (count($sql_errors) > 0) {
            throw new Exception("There was an error importing ".$path." Data Dictionary");
        }
    }



    function dataDictionaryCSVToMetadataArray($csvFilePath, $returnType = null)
    {
        $dd_column_var = array("0" => "field_name", "1" => "form_name","2" => "section_header", "3" => "field_type",
            "4" => "field_label", "5" => "select_choices_or_calculations","6" => "field_note", "7" => "text_validation_type_or_show_slider_number",
            "8" => "text_validation_min", "9" => "text_validation_max","10" => "identifier", "11" => "branching_logic",
            "12" => "required_field", "13" => "custom_alignment","14" => "question_number", "15" => "matrix_group_name",
            "16" => "matrix_ranking", "17" => "field_annotation"
        );

        // Set up array to switch out Excel column letters
        $cols = \MetaData::getCsvColNames();

        // Extract data from CSV file and rearrange it in a temp array
        $newdata_temp = array();
        $i = 1;

        // Set commas as default delimiter (if can't find comma, it will revert to tab delimited)
        $delimiter 	  = ",";
        $removeQuotes = false;

        if (($handle = fopen($csvFilePath, "rb")) !== false)
        {
            // Loop through each row
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false)
            {
                // Skip row 1
                if ($i == 1)
                {
                    ## CHECK DELIMITER
                    // Determine if comma- or tab-delimited (if can't find comma, it will revert to tab delimited)
                    $firstLine = implode(",", $row);
                    // If we find X number of tab characters, then we can safely assume the file is tab delimited
                    $numTabs = 6;
                    if (substr_count($firstLine, "\t") > $numTabs)
                    {
                        // Set new delimiter
                        $delimiter = "\t";
                        // Fix the $row array with new delimiter
                        $row = explode($delimiter, $firstLine);
                        // Check if quotes need to be replaced (added via CSV convention) by checking for quotes in the first line
                        // If quotes exist in the first line, then remove surrounding quotes and convert double double quotes with just a double quote
                        $removeQuotes = (substr_count($firstLine, '"') > 0);
                    }
                    // Increment counter
                    $i++;
                    // Check if legacy column Field Units exists. If so, tell user to remove it (by returning false).
                    // It is no longer supported but old values defined prior to 4.0 will be preserved.
                    if (strpos(strtolower($row[2]), "units") !== false)
                    {
                        return false;
                    }
                    continue;
                }
                if($returnType == null){
                    // Loop through each row and create array
                    $json_aux = array();
                    foreach ($row as $key => $value){
                        $json_aux[$dd_column_var[$key]] = $value;
                    }
                    $newdata_temp[$json_aux['field_name']] = $json_aux;
                }else if($returnType == 'array'){
                    // Loop through each column in this row
                    for ($j = 0; $j < count($row); $j++) {
                        // If tab delimited, compensate sightly
                        if ($delimiter == "\t") {
                            // Replace characters
                            $row[$j] = str_replace("\0", "", $row[$j]);
                            // If first column, remove new line character from beginning
                            if ($j == 0) {
                                $row[$j] = str_replace("\n", "", ($row[$j]));
                            }
                            // If the string is UTF-8, force convert it to UTF-8 anyway, which will fix some of the characters
                            if (function_exists('mb_detect_encoding') && mb_detect_encoding($row[$j]) == "UTF-8") {
                                $row[$j] = utf8_encode($row[$j]);
                            }
                            // Check if any double quotes need to be removed due to CSV convention
                            if ($removeQuotes) {
                                // Remove surrounding quotes, if exist
                                if (substr($row[$j], 0, 1) == '"' && substr($row[$j], -1) == '"') {
                                    $row[$j] = substr($row[$j], 1, -1);
                                }
                                // Remove any double double quotes
                                $row[$j] = str_replace("\"\"", "\"", $row[$j]);
                            }
                        }
                        // Add to array
                        $newdata_temp[$cols[$j + 1]][$i] = $row[$j];
                    }
                }
                $i++;
            }
            fclose($handle);
        } else {
            // ERROR: File is missing
            throw new Exception("ERROR. File is missing!");
        }

        // If file was tab delimited, then check if it left an empty row on the end (typically happens)
        if ($delimiter == "\t" && $newdata_temp['A'][$i-1] == "")
        {
            // Remove the last row from each column
            foreach (array_keys($newdata_temp) as $this_col)
            {
                unset($newdata_temp[$this_col][$i-1]);
            }
        }

        // Return array with data dictionary values
        return $newdata_temp;

    }

    // Save metadata when in DD array format
    private function saveMetadataCSV($dictionary_array, $project_id, $appendFields=false, $preventLogging=false)
    {
        $status = 0;
        $Proj = new \Project($project_id);

        // If project is in production, do not allow instant editing (draft the changes using metadata_temp table instead)
        $metadata_table = ($status > 0) ? "redcap_metadata_temp" : "redcap_metadata";

        // DEV ONLY: Only run the following actions (change rights level, events designation) if in Development
        if ($status < 1)
        {
            // If new forms are being added, give all users "read-write" access to this new form
            $existing_form_names = array();
            if (!$appendFields) {
                $results = $this->query("select distinct form_name from ".$metadata_table." where project_id = ?",[$project_id]);
                while ($row = $results->fetch_assoc()) {
                    $existing_form_names[] = $row['form_name'];
                }
            }
            $newforms = array();
            foreach (array_unique($dictionary_array['B']) as $new_form) {
                if (!in_array($new_form, $existing_form_names)) {
                    //Add rights for EVERY user for this new form
                    $newforms[] = $new_form;
                    //Add all new forms to redcap_events_forms table
                    $this->query("insert into redcap_events_forms (event_id, form_name) select m.event_id, ?
                                                              from redcap_events_arms a, redcap_events_metadata m
                                                              where a.project_id = ? and a.arm_id = m.arm_id",[$new_form,$project_id]);

                }
            }
            if(!empty($newforms)){
                //Add new forms to rights table
                $data_entry = "[".implode(",1][",$newforms).",1]";
                $this->query("update redcap_user_rights set data_entry = concat(data_entry,?) where project_id = ? ",[$data_entry,$project_id]);
            }

            //Also delete form-level user rights for any forms deleted (as clean-up)
            if (!$appendFields) {
                foreach (array_diff($existing_form_names, array_unique($dictionary_array['B'])) as $deleted_form) {
                    //Loop through all 3 data_entry rights level states to catch all instances
                    for ($i = 0; $i <= 2; $i++) {
                        $deleted_form_sql = '['.$deleted_form.','.$i.']';
                        $this->query("update redcap_user_rights set data_entry = replace(data_entry,?,'') where project_id = ? ",[$deleted_form_sql,$project_id]);
                    }
                    //Delete all instances in redcap_events_forms
                    $this->query("delete from redcap_events_forms where event_id in
							(select m.event_id from redcap_events_arms a, redcap_events_metadata m, redcap_projects p where a.arm_id = m.arm_id
							and p.project_id = a.project_id and p.project_id = ?) and form_name = ?",[$project_id,$deleted_form]);
                }
            }

            ## CHANGE FOR MULTIPLE SURVEYS????? (Should we ALWAYS assume that if first form is a survey that we should preserve first form as survey?)
            // If using first form as survey and form is renamed in DD, then change form_name in redcap_surveys table to the new form name
            if (!$appendFields && isset($Proj->forms[$Proj->firstForm]['survey_id']))
            {
                $columnB = $dictionary_array['B'];
                $newFirstForm = array_shift(array_unique($columnB));
                unset($columnB);
                // Do not rename in table if the new first form is ALSO a survey (assuming it even exists)
                if ($newFirstForm != '' && $Proj->firstForm != $newFirstForm && !isset($Proj->forms[$newFirstForm]['survey_id']))
                {
                    // Change form_name of survey to the new first form name
                    $this->query("update redcap_surveys set form_name = ? where survey_id = ?",[$newFirstForm,$Proj->forms[$Proj->firstForm]['survey_id']]);
                }
            }
        }

        // Build array of existing form names and their menu names to try and preserve any existing menu names
        $q = $this->query("select form_name, form_menu_description from $metadata_table where project_id = ? and form_menu_description is not null",[$project_id]);
        $existing_form_menus = array();
        while ($row = $q->fetch_assoc()) {
            $existing_form_menus[$row['form_name']] = $row['form_menu_description'];
        }

        // Before wiping out current metadata, obtain values in table not contained in data dictionary to preserve during carryover (e.g., edoc_id)
        $q = $this->query("select field_name, edoc_id, edoc_display_img, stop_actions, field_units, video_url, video_display_inline
				from $metadata_table where project_id = ?
				and (edoc_id is not null or stop_actions is not null or field_units is not null or video_url is not null)",[$project_id]);
        $extra_values = array();
        while ($row = $q->fetch_assoc())
        {
            if (!empty($row['edoc_id'])) {
                // Preserve edoc values
                $extra_values[$row['field_name']]['edoc_id'] = $row['edoc_id'];
                $extra_values[$row['field_name']]['edoc_display_img'] = $row['edoc_display_img'];
            }
            if ($row['stop_actions'] != "") {
                // Preserve stop_actions value
                $extra_values[$row['field_name']]['stop_actions'] = $row['stop_actions'];
            }
            if ($row['field_units'] != "") {
                // Preserve field_units value (no longer included in data dictionary but will be preserved if defined before 4.0)
                $extra_values[$row['field_name']]['field_units'] = $row['field_units'];
            }
            if ($row['video_url'] != "") {
                // Preserve video_url value
                $extra_values[$row['field_name']]['video_url'] = $row['video_url'];
                $extra_values[$row['field_name']]['video_display_inline'] = $row['video_display_inline'];
            }
        }

        // Determine if we need to replace ALL fields or append to existing fields
        if ($appendFields) {
            // Only append new fields to existing metadata (as opposed to replacing them all)
            $q = $this->query("select max(field_order)+1 from $metadata_table where project_id = ?",[$project_id]);
            $field_order = $q;
        } else {
            // Default field order value
            $field_order = 1;
            // Delete all instances of metadata for this project to clean out before adding new
            $this->query("delete from $metadata_table where project_id = ?", [$project_id]);
        }

        // Capture any SQL errors
        $sql_errors = array();
        // Create array to keep track of form names for building form_menu_description logic
        $form_names = array();
        // Set up exchange values for replacing legacy back-end values
        $convertValType = array("integer"=>"int", "number"=>"float");
        $convertFldType = array("notes"=>"textarea", "dropdown"=>"select", "drop-down"=>"select");

        // Loop through data dictionary array and save into metadata table
        foreach (array_keys($dictionary_array['A']) as $i)
        {
            // If this is the first field of a form, generate form menu description for upcoming form
            // If form menu description already exists, it may have been customized, so keep old value
            $form_menu = "";
            if (!in_array($dictionary_array['B'][$i], $form_names)) {
                if (isset($existing_form_menus[$dictionary_array['B'][$i]])) {
                    // Use existing value if form existed previously
                    $form_menu = $existing_form_menus[$dictionary_array['B'][$i]];
                } else {
                    // Create menu name on the fly
                    $form_menu = ucwords(str_replace("_", " ", $dictionary_array['B'][$i]));
                }
            }
            // Deal with hard/soft validation checktype for text fields
            $valchecktype = ($dictionary_array['D'][$i] == "text") ? "'soft_typed'" : "NULL";
            // Swap out Identifier "y" with "1"
            $dictionary_array['K'][$i] = (strtolower(trim($dictionary_array['K'][$i])) == "y") ? "1" : "NULL";
            // Swap out Required Field "y" with "1"	(else "0")
            $dictionary_array['M'][$i] = (strtolower(trim($dictionary_array['M'][$i])) == "y") ? "1" : "'0'";
            // Format multiple choices
            if ($dictionary_array['F'][$i] != "" && $dictionary_array['D'][$i] != "calc" && $dictionary_array['D'][$i] != "slider" && $dictionary_array['D'][$i] != "sql") {
                $dictionary_array['F'][$i] = str_replace(array("|","\n"), array("\\n"," \\n "), $dictionary_array['F'][$i]);
            }
            // Do replacement of front-end values with back-end equivalents
            if (isset($convertFldType[$dictionary_array['D'][$i]])) {
                $dictionary_array['D'][$i] = $convertFldType[$dictionary_array['D'][$i]];
            }
            if ($dictionary_array['H'][$i] != "" && $dictionary_array['D'][$i] != "slider") {
                // Replace with legacy/back-end values
                if (isset($convertValType[$dictionary_array['H'][$i]])) {
                    $dictionary_array['H'][$i] = $convertValType[$dictionary_array['H'][$i]];
                }
            } elseif ($dictionary_array['D'][$i] == "slider" && $dictionary_array['H'][$i] != "" && $dictionary_array['H'][$i] != "number") {
                // Ensure sliders only have validation type of "" or "number" (to display number value or not)
                $dictionary_array['H'][$i] = "";
            }
            // Make sure question_num is 10 characters or less
            if (strlen($dictionary_array['O'][$i]) > 10) $dictionary_array['O'][$i] = substr($dictionary_array['O'][$i], 0, 10);
            // Swap out Matrix Rank "y" with "1" (else "0")
            $dictionary_array['Q'][$i] = (strtolower(trim($dictionary_array['Q'][$i])) == "y") ? "1" : "'0'";
            // Remove any hex'ed double-CR characters in field labels, etc.
            $dictionary_array['E'][$i] = str_replace("\x0d\x0d", "\n\n", $dictionary_array['E'][$i]);
            $dictionary_array['C'][$i] = str_replace("\x0d\x0d", "\n\n", $dictionary_array['C'][$i]);
            $dictionary_array['F'][$i] = str_replace("\x0d\x0d", "\n\n", $dictionary_array['F'][$i]);
            // Insert edoc_id and slider display values that should be preserved
            $edoc_id 		  = isset($extra_values[$dictionary_array['A'][$i]]['edoc_id']) ? $extra_values[$dictionary_array['A'][$i]]['edoc_id'] : NULL;
            $edoc_display_img = isset($extra_values[$dictionary_array['A'][$i]]['edoc_display_img']) ? $extra_values[$dictionary_array['A'][$i]]['edoc_display_img'] : "0";
            $stop_actions 	  = isset($extra_values[$dictionary_array['A'][$i]]['stop_actions']) ? $extra_values[$dictionary_array['A'][$i]]['stop_actions'] : "";
            $field_units	  = isset($extra_values[$dictionary_array['A'][$i]]['field_units']) ? $extra_values[$dictionary_array['A'][$i]]['field_units'] : "";
            $video_url	  	  = isset($extra_values[$dictionary_array['A'][$i]]['video_url']) ? $extra_values[$dictionary_array['A'][$i]]['video_url'] : "";
            $video_display_inline = isset($extra_values[$dictionary_array['A'][$i]]['video_display_inline']) ? $extra_values[$dictionary_array['A'][$i]]['video_display_inline'] : "0";

            $sql = "insert into $metadata_table (project_id, field_name, form_name, field_units, element_preceding_header, "
                . "element_type, element_label, element_enum, element_note, element_validation_type, element_validation_min, "
                . "element_validation_max, field_phi, branching_logic, element_validation_checktype, form_menu_description, "
                . "field_order, field_req, edoc_id, edoc_display_img, custom_alignment, stop_actions, question_num, "
                . "grid_name, grid_rank, misc, video_url, video_display_inline) values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

            $q = $this->query($sql,
                [
                    $project_id,
                    $this->checkNull($dictionary_array['A'][$i]),
                    $this->checkNull($dictionary_array['B'][$i]),
                    $this->checkNull($field_units),
                    $this->checkNull($dictionary_array['C'][$i]),
                    $this->checkNull($dictionary_array['D'][$i]),
                    $this->checkNull($dictionary_array['E'][$i]),
                    $this->checkNull($dictionary_array['F'][$i]),
                    $this->checkNull($dictionary_array['G'][$i]),
                    $this->checkNull($dictionary_array['H'][$i]),
                    $this->checkNull($dictionary_array['I'][$i]),
                    $this->checkNull($dictionary_array['J'][$i]),
                    $dictionary_array['K'][$i],
                    $this->checkNull($dictionary_array['L'][$i]),
                    $valchecktype,
                    $this->checkNull($form_menu),
                    $field_order,
                    $dictionary_array['M'][$i],
                    $edoc_id,
                    $edoc_display_img,
                    $this->checkNull($dictionary_array['N'][$i]),
                    $this->checkNull($stop_actions),
                    $this->checkNull($dictionary_array['O'][$i]),
                    $this->checkNull($dictionary_array['P'][$i]),
                    $dictionary_array['Q'][$i],
                    $this->checkNull(isset($dictionary_array['R']) ? $dictionary_array['R'][$i] : null),
                    $this->checkNull($video_url),
                    "'".$video_display_inline."'"
                ]
            );
            //Insert into table
            if ($q) {
                // Increment field order
                $field_order++;
            } else {
                //Log this error
                $sql_errors[] = $sql;
            }


            //Add Form Status field if we're on the last field of a form
            if (isset($dictionary_array['B'][$i]) && $dictionary_array['B'][$i] != $dictionary_array['B'][$i+1]) {
                $form_name = $dictionary_array['B'][$i];
                $q = $this->insertFormStatusField($metadata_table, $project_id, $form_name, $field_order);
                //Insert into table
                if ($q) {
                    // Increment field order
                    $field_order++;
                } else {
                    //Log this error
                    // $sql_errors[] = $sql;
                }
            }

            //Add form name to array for later checking for form_menu_description
            $form_names[] = $dictionary_array['B'][$i];

        }

        // Logging
        if (!$appendFields && !$preventLogging) {
            \Logging::logEvent("",$metadata_table,"MANAGE",$project_id,"project_id = ".$project_id,"Upload data dictionary");
        }
        // Return any SQL errors
        return $sql_errors;
    }

    public function clearProjectCache(){
        $this->setPrivateVariable('project_cache', [], 'Project');
    }

    protected function setPrivateVariable($name, $value, $target = null)
    {
        $class = new \ReflectionClass($target);
        $property = $class->getProperty($name);
        $property->setAccessible(true);

        return $property->setValue($this->getReflectionClass(), $value);
    }

    protected function getReflectionClass()
    {
        return $this;
    }
}

?>