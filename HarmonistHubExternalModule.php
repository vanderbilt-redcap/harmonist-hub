<?php

namespace Vanderbilt\HarmonistHubExternalModule;

use DateTime;
use Exception;
use MetaData;
use REDCap;
use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
use ReflectionClass;

include_once(__DIR__ . "/classes/REDCapManagement.php");
include_once(__DIR__ . "/classes/ArrayFunctions.php");
include_once(__DIR__ . "/classes/ProjectData.php");
include_once(__DIR__ . "/classes/CopyJSON.php");
include_once(__DIR__ . "/classes/HubUpdates.php");
include_once(__DIR__ . "/functions.php");

require_once(dirname(__FILE__) . "/vendor/autoload.php");

class HarmonistHubExternalModule extends AbstractExternalModule
{

    #If it's not the main PID project hide the Harmonist Hub link
    public function redcap_module_link_check_display($project_id, $link)
    {
        #Do not show link unless we are in the main project
        $hub_mapper = $this->getProjectSetting('hub-mapper');
        if ($hub_mapper !== "" && $project_id != $hub_mapper) {
            return false;
        }
        if ($link['name'] == "Harmonist Hub") {
            $hub_projectname = $this->getProjectSetting('hub-projectname');
            $dd_array = REDCap::getDataDictionary('array');
            $data_array = REDCap::getData($project_id, 'array');

            #User rights
            $isAdmin = false;
            if (defined('USERID')) {
                $UserRights = REDCap::getUserRights(USERID)[USERID];
                if ($UserRights['user_rights'] == '1') {
                    $isAdmin = true;
                }
            }

            if ($hub_projectname != "" && ($project_id == $this->getProjectSetting(
                        'hub-mapper'
                    ) || $this->getProjectSetting('hub-mapper') === "")) {
                $link['name'] = $hub_projectname . " Hub";
            }

            if (count($dd_array) == 1 && $isAdmin && !array_key_exists(
                    'project_constant',
                    $dd_array
                ) && !array_key_exists('project_id', $dd_array) || count($data_array) == 0) {
                $link['url'] = $this->getUrl("installProjects.php");
            }
        } else {
            #User has no permissions to see Last Updates, do not show link
            if (!$this->getUser()->hasDesignRights($project_id)) {
                return false;
            }
            $hub_projectname = $this->getProjectSetting('hub-projectname');
            $hub_profile = $this->getProjectSetting('hub-profile');
            if ($hub_projectname == '' || $hub_profile == '') {
                #Fields are empty, project has not been installed yet, do not show link
                return false;
            }

            $dd_array = REDCap::getDataDictionary('array');
            $data_array = REDCap::getData($project_id, 'array');
            if (count($dd_array) == 1 && !array_key_exists('project_constant', $dd_array) && !array_key_exists(
                    'project_id',
                    $dd_array
                ) || count($data_array) == 0) {
                #The Data Dictionary is empty, do not show link
                return false;
            }

            if ($link['name'] == "Hub Updates") {
                $pidsArray = REDCapManagement::getPIDsArray($project_id);
                $hub_updates = $this->getProjectSetting('hub-updates');
                $today = date("Y-m-d");

                #Only save & check hub-updates once a day
                if (strtotime($hub_updates['timestamp']) < strtotime($today)) {
                    $allUpdates['data'] = HubUpdates::compareDataDictionary($this, $pidsArray);
                    $allUpdates['timestamp'] = $today;
                    $total_updates = count($allUpdates['data']);
                    $allUpdates['total_updates'] = $total_updates;
                    $this->setProjectSetting('hub-updates', $allUpdates);
                } else {
                    $total_updates = $hub_updates['total_updates'];
                }

                if ($total_updates > 0) {
                    $link['name'] .= " (" . $total_updates . ")";
                }
            }
        }
        return parent::redcap_module_link_check_display($project_id, $link);
    }

    function setPIDMapperProject($project_id)
    {
        $hub_projectname = $this->getProjectSetting('hub-projectname');
        $newProjectTitle = strip_tags($hub_projectname . " Hub: Parent Project (MAP)");
        $path = $this->framework->getModulePath() . "csv/PID.csv";
        $custom_record_label = "[project_constant]: [project_id]";

        $this->framework->importDataDictionary($project_id, $path);
        $this->query(
            "UPDATE redcap_projects SET custom_record_label = ? WHERE project_id = ?",
            [$custom_record_label, $project_id]
        );
        $this->query("UPDATE redcap_projects SET app_title = ? WHERE project_id = ?", [$newProjectTitle, $project_id]);
    }

    function addProjectToList($project_id, $eventId, $record, $fieldName, $value)
    {
        $this->query(
            "INSERT INTO " . getDataTable(
                $project_id
            ) . " (project_id, event_id, record, field_name, value) VALUES (?, ?, ?, ?, ?)",
            [$project_id, $eventId, $record, $fieldName, $value]
        );
    }

    function createProjectAndImportDataDictionary($value_constant, $project_title)
    {
        $project_id = $this->framework->createProject($project_title, 0);
        $path = $this->framework->getModulePath() . "csv/" . $value_constant . ".csv";
        $this->framework->importDataDictionary($project_id, $path);

        return $project_id;
    }

    function redcap_save_record($project_id, $record, $instrument, $event_id)
    {
        echo '<script>';
        include_once("js/iframe.js");
        echo '</script>';

        #Get Projects ID's
        $hub_mapper = $this->getProjectSetting('hub-mapper');
        $pidsArray = REDCapManagement::getPIDsArray($hub_mapper);
        try {
            #Depending on the project we add one hook or another
            if ($project_id == $pidsArray['SOP']) {
                include_once("hooks/save_record_SOP.php");
            } else {
                if ($project_id == $pidsArray['RMANAGER']) {
                    include_once("hooks/save_record_requestManager.php");
                } else {
                    if ($project_id == $pidsArray['COMMENTSVOTES']) {
                        include_once("hooks/save_record_commentsAndVotes.php");
                    } else {
                        if ($project_id == $pidsArray['SOPCOMMENTS']) {
                            include_once("hooks/save_record_SOP_comments.php");
                        }
                    }
                }
            }
            echo '<script>';
            include_once("js/iframe.js");
            echo '</script>';
        } catch (Throwable $e) {
            REDCap::email(
                'eva.bascompte.moragas@vumc.org',
                REDCapManagement::DEFAULT_EMAIL_ADDRESS,
                "Hook Error",
                $e->getMessage()
            );
        }
    }

    function redcap_survey_acknowledgement_page($project_id, $record, $instrument, $event_id)
    {
        #Get Projects ID's
        $hub_mapper = $this->getProjectSetting('hub-mapper');
        $pidsArray = REDCapManagement::getPIDsArray($hub_mapper);
        try {
            #Depending on the project que add one hook or another
            if ($project_id == $pidsArray['SOP'] && $instrument == 'dhwg_review_request') {
                include_once("sop/sop_make_public_request_AJAX.php?record=" . $record);
                echo '<script>parent.location.href = ' . json_encode(
                        $this->getUrl(
                            "index.php"
                        ) . "&NOAUTH&pid=" . $pidsArray['PROJECTS'] . "&option=smn&record='.$record.'&message=P"
                    ) . '</script>';
            } else {
                echo '<script>';
                include_once("js/iframe.js");
                echo '</script>';
            }
        } catch (Throwable $e) {
            REDCap::email(
                'eva.bascompte.moragas@vumc.org',
                REDCapManagement::DEFAULT_EMAIL_ADDRESS,
                "Hook Error",
                $e->getMessage()
            );
        }
    }

    function redcap_survey_page_top($project_id)
    {
        #Get Projects ID's
        $hub_mapper = $this->getProjectSetting('hub-mapper');
        $pidsArray = REDCapManagement::getPIDsArray($hub_mapper);

        echo "<script>
            $(document).ready(function() {
                window.onbeforeunload = null;
                //Hide save and return button for all surveys
                $('[name=submit-btn-savereturnlater]').hide();
            });
        </script>";

        #Add to all projects needed
        if ($project_id == $pidsArray['HARMONIST']) {
            echo "<script>
                $(document).ready(function() {
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
        } else {
            if (array_key_exists('modal', $_REQUEST)) {
                echo "<script>
                    $(document).ready(function() {
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

    function cronMethod($cronAttributes)
    {
        //Only perform actions between 12am and 6am for crons that update at night
        if ($cronAttributes['cron_name'] != 'cron_upload_pending_data_set_data' && $cronAttributes['cron_name'] != 'cron_data_upload_notification' && $cronAttributes['cron_name'] != 'cron_req_finalized_notification') {
            $hourRange = 6;
            if (date('G') > $hourRange) {
                // Only perform actions between 12am and 6am.
                return;
            }
            $lastRunSettingName = 'last-cron-run-time-' . $cronAttributes['cron_name'];
            $lastRun = empty($this->getSystemSetting($lastRunSettingName)) ? $this->getSystemSetting(
                $lastRunSettingName
            ) : 0;
            $hoursSinceLastRun = (time() - $lastRun) / 60 / 60;
            if ($hoursSinceLastRun < $hourRange) {
                // We're already run recently
                return;
            }
            if ($cronAttributes['cron_name'] == 'cron_monthly_digest') {
                if (date('w', strtotime(date('Y-m-d'))) !== '1') {
                    //It's not Monday
                    return;
                }
                $firstMondayDate = new DateTime(date('Y-m-j'));
                $firstMondayMonth = date(
                    "j",
                    strtotime(
                        $firstMondayDate->modify('first monday of this month')->format('Y-m-j')
                    )
                );
                if ($firstMondayMonth != date('j')) {
                    // We only want it to send on the first Monday of the month
                    return;
                }
            }
        }

        //Perform cron actions here
        if (APP_PATH_WEBROOT[0] == '/') {
            $APP_PATH_WEBROOT_ALL = substr(APP_PATH_WEBROOT, 1);
        }
        define('APP_PATH_WEBROOT_ALL', APP_PATH_WEBROOT_FULL . $APP_PATH_WEBROOT_ALL);
        $isCron = true;
        foreach ($this->getProjectsWithModuleEnabled() as $project_id) {
            $hub_mapper = $this->getProjectSetting('hub-mapper', $project_id);
            $disable_crons = $this->getProjectSetting('disable-crons', $hub_mapper);
            if (!$disable_crons) {
                if($hub_mapper == "2747") {
                    error_log(
                        "IeDEA HUB: cron_upload_pending_data_set_data on PID=MAPPER: " . $project_id . " = " . $hub_mapper
                    );
                }
                if (is_numeric($project_id) && $project_id == $hub_mapper) {
                    #Get Projects ID's
                    $pidsArray = REDCapManagement::getPIDsArray($project_id, "cron");

                    if (!empty($pidsArray) && is_array($pidsArray) && $pidsArray['SETTINGS'] !== "") {
                        $settings = REDCap::getData($pidsArray['SETTINGS'], 'json-array', null)[0];
                        if (!empty($settings)) {
                            try {
                                if($cronAttributes['cron_name'] == 'cron_upload_pending_data_set_data'){
                                    error_log("IeDEA HUB: cron_upload_pending_data_set_data on PID: ".$project_id);
                                    error_log("IeDEA HUB: cron_upload_pending_data_set_data on deactivate_datadown___1: ".$settings['deactivate_datadown___1']);
                                    error_log("IeDEA HUB: cron_upload_pending_data_set_data on deactivate_datahub___1: ".$settings['deactivate_datahub___1']);
                                }
                                #CRONS
                                if ($cronAttributes['cron_name'] == 'cron_metrics' && $settings['deactivate_metrics_cron___1'] !== "1") {
                                    include("crontasks/cron_metrics.php");
                                } elseif ($cronAttributes['cron_name'] == 'cron_delete' && ($settings['deactivate_datadown___1'] !== "1" || $settings['deactivate_datahub___1'] !== "1")) {
                                    include("crontasks/cron_delete_AWS.php");
                                } elseif ($cronAttributes['cron_name'] == 'cron_data_upload_expiration_reminder' && ($settings['deactivate_datadown___1'] !== "1" || $settings['deactivate_datahub___1'] !== "1")) {
                                    include("crontasks/cron_data_upload_expiration_reminder.php");
                                } elseif ($cronAttributes['cron_name'] == 'cron_data_upload_notification' && ($settings['deactivate_datadown___1'] !== "1" || $settings['deactivate_datahub___1'] !== "1")) {
                                    include("crontasks/cron_data_upload_notification.php");
                                } elseif ($cronAttributes['cron_name'] == 'cron_monthly_digest') {
                                    //Every First Monday of the Month
                                    include("crontasks/cron_monthly_digest.php");
                                } elseif ($cronAttributes['cron_name'] == 'cron_req_finalized_notification') {
                                    include("crontasks/cron_req_finalized_notification.php");
                                } elseif ($cronAttributes['cron_name'] == 'cron_publications') {
                                    include("crontasks/cron_publications.php");
                                } elseif ($cronAttributes['cron_name'] == 'cron_json') {
                                    include("crontasks/cron_json.php");
                                } elseif ($cronAttributes['cron_name'] == 'cron_upload_pending_data_set_data' && ($settings['deactivate_datadown___1'] !== "1" || $settings['deactivate_datahub___1'] !== "1")) {
                                    error_log("IeDEA HUB: cron_upload_pending_data_set_data");
                                    include("crontasks/cron_upload_pending_data_set_data.php");
                                }
                            } catch (Throwable $e) {
                                REDCap::email(
                                    'eva.bascompte.moragas@vumc.org',
                                    'harmonist@vumc.org',
                                    "Cron Error",
                                    $e->getMessage()
                                );
                            }
                        }
                    }
                }
            }
        }
        if ($cronAttributes['cron_name'] != 'cron_data_upload_notification' && $cronAttributes['cron_name'] != 'cron_req_finalized_notification') {
            $this->setSystemSetting($lastRunSettingName, time());
        }
    }

    function hook_every_page_before_render($project_id = null)
    {
        if (PAGE == "ProjectSetup/index.php") {
            echo "<script type='text/javascript' src='" . $this->getUrl('js/jquery-3.7.1.min.js') . "'></script>\n";
            echo "
            <style>
                .chklisttext {
                    font-size: 13px;
                }
            </style>
            <script>
                //$(document).ready(function() { $('.chklist.round:eq(6)').hide(); });
            </script>\n";
        }
    }

    function compareDataDictionaries()
    {
        $projects_array = REDCapManagement::getProjectConstantsArrayWithoutDeactivatedProjects();
        $var_replace_type = array(0 => "additions", 1 => "changed", 3 => "missing");

        foreach ($var_replace_type as $type) {
            ${$type} = array();
            ${"alert_text_" . $type} = "";
        }
        foreach ($projects_array as $index => $constant) {
            $metadata = array();
            $metadata["destination"] = REDCap::getDataDictionary(constant("IEDEA_" . $constant), 'array', false);
            $metadata["origin"] = $this->dataDictionaryCSVToMetadataArray(
                $this->framework->getModulePath() . "csv/" . $constant . ".csv"
            );

            $iedea_contant = constant("IEDEA_" . $constant);
            $deletionRegEx = "/___delete$/";

            foreach ($var_replace_type as $type) {
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
                } else {
                    if ($choices["origin"][$field] && $choices["destination"][$field] && !REDCapManagement::arraysEqual(
                            $choices["origin"][$field],
                            $choices["destination"][$field]
                        )) {
                        array_push($missing[$iedea_contant], $field);
                        array_push($changed[$iedea_contant], $field);
                    } else {
                        foreach ($metadataFields as $metadataField) {
                            if (REDCapManagement::hasMetadataChanged(
                                $indexedMetadata["origin"][$field][$metadataField],
                                $indexedMetadata["destination"][$field][$metadataField],
                                $metadataField
                            )) {
                                array_push($missing[$iedea_contant], $field);
                                array_push($changed[$iedea_contant], $field);
                                break; // metadataFields loop
                            }
                        }
                    }
                }
            }

            foreach ($var_replace_type as $type) {
                if (empty(${$type}[$iedea_contant])) {
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
                        ${"alert_text_" . $type} .= "<ul><li>" . $title . ": <strong>" . implode(
                                ", ",
                                ${$type}[$iedea_constant]
                            ) . "</strong></li></ul>";
                    }
                }
            }

            echo "<script>var missing = " . json_encode($missing) . ";</script>\n";
            echo "<div id='metadataWarning' class='install-metadata-box install-metadata-box-danger'>
                        <i class='fa fa-exclamation-circle' aria-hidden='true'></i> An upgrade in your Data Dictionary exists. <a href='javascript:;' onclick='installMetadata(missing," . json_encode(
                    $this->getUrl("installMetadata.php?NOAUTH&pid=" . $pidsArray['PROJECTS'])
                ) . ")'>Click here to install.</a>
                        <ul><li>The following fields will be added: " . (empty($alert_text_additions) ? "<i>None</i>" : $alert_text_additions) . "</li>
                        <li>The following fields will be changed: " . (empty($alert_text_changed) ? "<i>None</i>" : $alert_text_changed) . "</li></ul>
                    </div>";
        }
    }

    function compareRepeatingForms()
    {
        $projects_array_repeatable = REDCapManagement::getProjectsRepeatableArray();
        $projects_array = REDCapManagement::getProjectConstantsArrayWithoutDeactivatedProjects();

        $alert = array();
        foreach ($projects_array as $index => $constant) {
            $project_id = constant("IEDEA_" . $constant);
            $alert[$project_id] = array();
            foreach ($projects_array_repeatable[$index] as $repeat_event) {
                if ($repeat_event['status'] == 1) {
                    $found = false;
                    $q = $this->query(
                        "SELECT b.event_id FROM  redcap_events_arms a LEFT JOIN redcap_events_metadata b ON(a.arm_id = b.arm_id) where a.project_id = ?",
                        [$project_id]
                    );
                    while ($row = $q->fetch_assoc()) {
                        $event_id = $row['event_id'];
                        $qEvent = $this->query(
                            "SELECT custom_repeat_form_label FROM  redcap_events_repeat where  event_id= ? AND form_name=?",
                            [$event_id, $repeat_event['instrument']]
                        );
                        if ($qEvent->num_rows > 0) {
                            $found = true;
                            while ($row_event = $qEvent->fetch_assoc()) {
                                if ($repeat_event['params'] != $row_event['custom_repeat_form_label']) {
                                    #params are different add new ones
                                    $alert[$project_id][$repeat_event['instrument']] = $repeat_event['params'];
                                }
                            }
                        }
                    }
                    if (!$found) {
                        #New Instrument
                        $alert[$project_id][$repeat_event['instrument']] = $repeat_event['params'];
                    }
                }
            }
            if (empty($alert[$project_id])) {
                unset($alert[$project_id]);
            }
        }

        if (count($alert) > 0) {
            echo "<script>var forms = " . json_encode($alert) . ";</script>\n";
            echo "<div id='formsWarning' class='install-metadata-box install-metadata-box-danger'>
                        <i class='fa fa-exclamation-circle' aria-hidden='true'></i> New Repeatable Forms were found <a href='javascript:;' onclick='installRepeatingForms(forms," . json_encode(
                    $this->getUrl("installRepeatingForms.php") . "&NOAUTH&pid=" . $pidsArray['PROJECTS']
                ) . ")'>Click here to install.</a>";
            foreach ($alert as $project_id => $repeat) {
                $title = $this->framework->getProject($project_id)->getTitle();
                echo "<ul>
                        <li><em>" . $title . "</em></li>";
                foreach ($repeat as $instrument => $params) {
                    echo "<ul>
                        <li><strong>" . $instrument . "</strong>: " . $params . "</li>
                        </ul>";
                }
                echo "</ul>";
            }
            echo "</div>";
        }
    }

    function dataDictionaryCSVToMetadataArray($csvFilePath, $returnType = null)
    {
        $dd_column_var = array(
            "0" => "field_name",
            "1" => "form_name",
            "2" => "section_header",
            "3" => "field_type",
            "4" => "field_label",
            "5" => "select_choices_or_calculations",
            "6" => "field_note",
            "7" => "text_validation_type_or_show_slider_number",
            "8" => "text_validation_min",
            "9" => "text_validation_max",
            "10" => "identifier",
            "11" => "branching_logic",
            "12" => "required_field",
            "13" => "custom_alignment",
            "14" => "question_number",
            "15" => "matrix_group_name",
            "16" => "matrix_ranking",
            "17" => "field_annotation"
        );

        // Set up array to switch out Excel column letters
        $cols = MetaData::getCsvColNames();

        // Extract data from CSV file and rearrange it in a temp array
        $newdata_temp = array();
        $i = 1;

        // Set commas as default delimiter (if can't find comma, it will revert to tab delimited)
        $delimiter = ",";
        $removeQuotes = false;

        if (($handle = fopen($csvFilePath, "rb")) !== false) {
            // Loop through each row
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                // Skip row 1
                if ($i == 1) {
                    ## CHECK DELIMITER
                    // Determine if comma- or tab-delimited (if can't find comma, it will revert to tab delimited)
                    $firstLine = implode(",", $row);
                    // If we find X number of tab characters, then we can safely assume the file is tab delimited
                    $numTabs = 6;
                    if (substr_count($firstLine, "\t") > $numTabs) {
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
                    if (strpos(strtolower($row[2]), "units") !== false) {
                        return false;
                    }
                    continue;
                }
                if ($returnType == null) {
                    // Loop through each row and create array
                    $json_aux = array();
                    foreach ($row as $key => $value) {
                        $json_aux[$dd_column_var[$key]] = $value;
                    }
                    $newdata_temp[$json_aux['field_name']] = $json_aux;
                } else {
                    if ($returnType == 'array') {
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
                }
                $i++;
            }
            fclose($handle);
        } else {
            // ERROR: File is missing
            throw new Exception("ERROR. File is missing!");
        }

        // If file was tab delimited, then check if it left an empty row on the end (typically happens)
        if ($delimiter == "\t" && $newdata_temp['A'][$i - 1] == "") {
            // Remove the last row from each column
            foreach (array_keys($newdata_temp) as $this_col) {
                unset($newdata_temp[$this_col][$i - 1]);
            }
        }

        // Return array with data dictionary values
        return $newdata_temp;
    }

    public function clearProjectCache()
    {
        $this->setPrivateVariable('project_cache', [], 'Project');
    }

    protected function setPrivateVariable($name, $value, $target = null)
    {
        $class = new ReflectionClass($target);
        $property = $class->getProperty($name);
        $property->setAccessible(true);

        return $property->setValue($this, $value);
    }
}
?>
