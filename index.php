<?php

namespace Vanderbilt\HarmonistHubExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

if (APP_PATH_WEBROOT[0] == '/') {
    $APP_PATH_WEBROOT_ALL = substr(APP_PATH_WEBROOT, 1);
}
define('APP_PATH_WEBROOT_ALL', APP_PATH_WEBROOT_FULL . $APP_PATH_WEBROOT_ALL);

$hub_projectname = $module->getProjectSetting('hub-projectname');
$hub_profile = $module->getProjectSetting('hub-profile');
print_array("IN0");
$pid = $module->getSecurityHandler()->getProjectId();
print_array("IN00");
$option = $module->getSecurityHandler()->getRequestOption();
$is_authorized_and_has_rights = false;
print_array("IN");
if ($module->getSecurityHandler()->isAuthorizedPage()) {
    print_array("IN2");
    $pidsArray = $module->getSecurityHandler()->getPidsArray();
    $settings = $module->getSecurityHandler()->getSettingsData();
    if ($settings['deactivate_datahub___1'] != "1" && !empty(
        $_SESSION[SecurityHandler::SESSION_TOKEN_STRING][$module->getSecurityHandler()->getTokenSessionName()]
        )) {
        print_array("IN3");
        $is_authorized_and_has_rights = true;
        if ($option === 'lge') {
            include('sop_data_activity_log_delete.php');
        } elseif ($option === 'dnd' && $settings['deactivate_datahub___1'] != "1") {
            print_array($pidsArray);
            include('sop_retrieve_data.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<?php
if($hub_projectname != '' && $hub_profile != ''){
        if(array_key_exists('message',$_REQUEST) && $_REQUEST['message']=='DD'){?>
            <div class="container" style="margin-top: 80px">
                <div class="alert alert-success col-md-12">
                    Data Dictionary and projects successfully installed. To see the Project Ids go to the <a href="<?=APP_PATH_WEBROOT?>DataEntry/record_status_dashboard.php?pid=<?=$pid?>" target="_blank">Record Dashboard</a>.
                </div>
            </div>
        <?php }

            #User rights
            $isAdminInstall = false;
            if(defined('USERID')) {
                $UserRights = \REDCap::getUserRights(USERID)[USERID];
                if ($UserRights['user_rights'] == '1') {
                    $isAdminInstall = true;
                }
            }

            $dd_array = \REDCap::getDataDictionary('array');
            $data_array = \REDCap::getData($_GET['pid'], 'array');
            if (count($dd_array) == 1 && $isAdminInstall && !array_key_exists('project_constant', $dd_array) && !array_key_exists('project_id', $dd_array) || count($data_array) == 0) {
                //Do nothing
            } else {
                include_once("projects.php");
                include("hub_html_head.php");
                ?>
                <body>
                <?php
                $deactivate_datahub = false;
                if($settings['deactivate_datahub___1'] == "1"){
                    $deactivate_datahub = true;
                }
                $deactivate_tblcenter = false;
                if($settings['deactivate_tblcenter___1'] == "1"){
                    $deactivate_tblcenter = true;
                }

                $deactivate_toolkit = false;
                if($settings['deactivate_toolkit___1'] == "1"){
                    $deactivate_toolkit = true;
                }

                $token = $module->getSecurityHandler()->getTokenSession();

                if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'dfq'){
                    //No header
                }else{
                    include('hub_header.php');
                }
                ?>
                <div class="container" style="margin: 0 auto;float:none;min-height: 900px;">
                    <?php
                    if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'map' )
                    {
                        include('map/index.php');
                    }else if( !array_key_exists(SecurityHandler::SESSION_TOKEN_STRING, $_REQUEST) && !array_key_exists('request', $_REQUEST) && empty($_SESSION[SecurityHandler::SESSION_TOKEN_STRING][$module->getSecurityHandler()->getTokenSessionName()])){
                        include('hub/hub_login.php');
                    }else if($current_user['active_y'] == "0"){
                        include('hub/hub_login.php');
                    }else if(!empty($_SESSION[SecurityHandler::SESSION_TOKEN_STRING][$module->getSecurityHandler()->getTokenSessionName()]) && $module->getSecurityHandler()->isTokenCorrect($_SESSION[SecurityHandler::SESSION_TOKEN_STRING][$module->getSecurityHandler()->getTokenSessionName()])){
                        if( !array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST)){
                            include('hub/hub_home.php');
                        }
                        else if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'log')
                        {
                            include('hub/hub_changelog.php');
                        }else if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'smn' && !$deactivate_datahub)
                        {
                            include('sop/sop_request_data.php');
                        } else if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'sra' && !$deactivate_datahub)
                        {
                            include('sop/sop_recent_activity.php');
                        }else if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'tbl' && !$deactivate_datahub && !$deactivate_tblcenter)
                        {
                            include('sop/sop_table_center.php');
                        }else if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'ofs' && !$deactivate_datahub)
                        {
                            include('sop/sop_document_library.php');
                        }
                        else if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'sop' && !$deactivate_datahub)
                        {
                            include('sop/sop_data_request_title.php');
                        }
                        else if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'dna' && !$deactivate_datahub)
                        {
                            include('sop/sop_news_archive.php');
                        }
                        else if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'ss1' && !$deactivate_datahub)
                        {
                            include('sop/sop_steps_menu.php');
                        }
                        else if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'ss5' && !$deactivate_datahub)
                        {
                            include('sop/sop_step_5.php');
                        }else if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'spr' && !$deactivate_datahub)
                        {
                            include('sop/sop_make_public_request_review.php');
                        }else if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'lgd' && !$deactivate_datahub)
                        {
                            include('sop/sop_data_activity_log.php');
                        } else if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'cpt' )
                        {
                            include('hub/hub_concepts.php');
                        }
                        else if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'ttl' )
                        {
                            include('hub/hub_concept_title.php');
                        }
                        else if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'hub')
                        {
                            if(array_key_exists('record', $_REQUEST) && !empty($_REQUEST['record'])) {
                                include('hub/hub_request_title.php');
                            }else{
                                include('hub/hub_requests.php');
                            }
                        }else if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'adm')
                        {
                            if($isAdmin){
                                include('hub/hub_admin.php');
                            }else{
                                include('hub/hub_error_page.php');
                            }
                        }
                        else if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'usr'){
                            include('hub/hub_users.php');
                        }
                        else if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'mra')
                        {
                            include('hub/hub_my_requests_archive.php');
                        } else if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'mrr')
                        {
                            include('hub/hub_my_requests_archive_rejected.php');
                        }
                        else if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'hra')
                        {
                            include('hub/hub_recent_activity.php');
                        }else if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'upd' && !$deactivate_datahub)
                        {
                            include('sop/sop_submit_data.php');
                        }else if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'dat' && !$deactivate_datahub)
                        {
                            include('hub/hub_data.php');
                        }else if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'pdc' && !$deactivate_datahub)
                        {
                            include('sop/sop_data_call_archive.php');
                        }else if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'out')
                        {
                            include('hub/hub_publications.php');
                        }else if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'mts' && ($settings['deactivate_metrics___1'] != "1" || $isAdmin))
                        {
                            include('hub/hub_metrics_stats.php');
                        }else if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'mth' && ($settings['deactivate_datametrics___1'] != "1" || $isAdmin))
                        {
                            include('sop/sop_metrics_stats.php');
                        }else if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'faq')
                        {
                            include('faq/hub_faq.php');
                        }else if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'pro')
                        {
                            include('hub/hub_profile.php');
                        }else if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'bug')
                        {
                            include('hub/hub_report_bug.php');
                        }else if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'unf')
                        {
                            include('hub/hub_request_title.php');
                        }else if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'und' && !$deactivate_datahub)
                        {
                            include('sop/sop_data_request_title.php');
                        }else if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'cal' && $settings['calendar_active'][1] == "1")
                        {
                            include('hub/hub_calendar.php');
                        }else if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $option === 'abt')
                        {
                            include('hub/hub_about.php');
                        }else if( array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $_REQUEST[SecurityHandler::SESSION_OPTION_STRING] === 'dab' && !$deactivate_toolkit)
                        {
                            include('sop/sop_explore_data.php');
                        }
                        else{
                            include('hub/hub_home.php');
                        }

                    }else{
                        echo "<script>$(document).ready(function() { $('#hub_error_message').show(); $('#hub_error_message').html('<strong>This Access Link has expired. </strong> <br />Please request a new Access Link below.');});</script>";
                        include('hub/hub_login.php');
                    }
                    ?>
                </div>
                <?php include('hub_footer.php'); ?>
                <br/>
                <?php
            }
        }elseif(!$is_authorized_and_has_rights){
            include("hub_html_head.php");
            $pidsArray = $module->getSecurityHandler()->getPidsArray();
            $settings = $module->getSecurityHandler()->getSettingsData();

            include('hub_header.php');
            ?><body>
                <div class="container" style="margin: 0 auto;float:none;min-height: 900px;">
                    <?php include('hub/hub_login.php');?>
                </div>
                <br/>
                <?php include('hub_footer.php'); ?>
                <?php
        }
    ?>
    </body>
</html>