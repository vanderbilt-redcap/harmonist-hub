<?php
namespace Vanderbilt\HarmonistHubExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

if(APP_PATH_WEBROOT[0] == '/'){
    $APP_PATH_WEBROOT_ALL = substr(APP_PATH_WEBROOT, 1);
}
define('APP_PATH_WEBROOT_ALL',APP_PATH_WEBROOT_FULL.$APP_PATH_WEBROOT_ALL);

$hub_projectname = $module->getProjectSetting('hub-projectname');
$hub_profile = $module->getProjectSetting('hub-profile');
$pid = (int)$_GET['pid'];
$option = htmlentities($_REQUEST['option'],ENT_QUOTES);
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
            $isAdmin = false;
            if(defined('USERID')) {
                $UserRights = \REDCap::getUserRights(USERID)[USERID];
                if ($UserRights['user_rights'] == '1') {
                    $isAdmin = true;
                }
            }

            $dd_array = \REDCap::getDataDictionary('array');
            $data_array = \REDCap::getData($_GET['pid'], 'array');
            if (count($dd_array) == 1 && $isAdmin && !array_key_exists('project_constant', $dd_array) && !array_key_exists('project_id', $dd_array) || count($data_array) == 0) {
                //Do nothing
            } else {
                include_once("projects.php");
              /***
                 * Installation updates check to update new/missing variables
                 * -Data Dictionary Variables
                 * -Repeating Forms
                 */
                if($isAdmin && !array_key_exists('sout', $_REQUEST)) {
//                    $module->compareDataDictionaries();
//                    $module->compareRepeatingForms();
                }
                ?>
                <head>
                    <title><?=$settings['hub_name_title']?></title>
                    <meta charset="utf-8">
                    <meta http-equiv="X-UA-Compatible" content="IE=edge">
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    <meta name="description" content="">
                    <meta name="author" content="">
                    <meta http-equiv="Cache-control" content="public">
                    <meta name="theme-color" content="#fff">
                    <link rel="icon" href="<?=getFile($module, $pidsArray['PROJECTS'], $settings['hub_logo_favicon'],'favicon')?>">

                    <?php include_once("head_scripts.php");?>

                    <script type='text/javascript'>
                        $(document).ready(function() {
                            Sortable.init();
                            $('[data-toggle="tooltip"]').tooltip();

                            var CACHE_NAME = 'iedea-site-cache';
                            var urlsToCache = [
                                '/',
                                '/css/style.css',
                                '/js/base.js',
                                '/js/functions.js'
                            ];

                            self.addEventListener('install', function(event) {
                                // Perform install steps
                                event.waitUntil(
                                    caches.open(CACHE_NAME)
                                        .then(function(cache) {
                                            return cache.addAll(urlsToCache);
                                        })
                                );
                            });

                            var pageurloption = <?=json_encode($option)?>;
                            if(pageurloption != '') {
                                $('[option=' + pageurloption + ']').addClass('navbar-active');
                            }

                        } );
                    </script>

                    <style>
                        table thead .glyphicon {
                            color: blue;
                        }
                    </style>
                </head>
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
                #TOKEN
                if(!array_key_exists('token', $_REQUEST) && !array_key_exists('request', $_REQUEST) && !empty($_SESSION['token'][$settings['hub_name'].$pidsArray['PROJECTS']]) && !array_key_exists('option', $_REQUEST)){
                    #Login page
                }else if(empty($_SESSION['token'][$settings['hub_name'].$pidsArray['PROJECTS']])){
//                   session_write_close();
//                   session_name($settings['hub_name']);
//                   session_id($_COOKIE[$settings['hub_name']]);
                   session_start();
               }

                $token = "";
                if(defined("USERID") && !array_key_exists('token', $_REQUEST) && !array_key_exists('request', $_REQUEST) && ((array_key_exists('option', $_REQUEST) && $option === 'dnd')  || (array_key_exists('option', $_REQUEST) && $option === 'iut') || (array_key_exists('option', $_REQUEST) && $option === 'lgd' && array_key_exists('del', $_REQUEST) && $_REQUEST['del'] != ''))){
                    $_SESSION['token'] = array();
                    $_SESSION['token'][$settings['hub_name'].$pidsArray['PROJECTS']] = getToken(USERID, $pidsArray['PEOPLE']);
                    $token = $_SESSION['token'][$settings['hub_name'].$pidsArray['PROJECTS']];
                }else if(array_key_exists('token', $_REQUEST)  && !empty($_REQUEST['token']) && isTokenCorrect($_REQUEST['token'],$pidsArray['PEOPLE'])){
                    $token = $_REQUEST['token'];
                }else if(!empty($_SESSION['token'][$settings['hub_name'].$pidsArray['PROJECTS']])&& isTokenCorrect($_SESSION['token'][$settings['hub_name'].$pidsArray['PROJECTS']],$pidsArray['PEOPLE'])) {
                    $token = $_SESSION['token'][$settings['hub_name'].$pidsArray['PROJECTS']];
                }

                //Session OUT
                if(array_key_exists('sout', $_REQUEST)){
                    unset($_SESSION['token'][$settings['hub_name'].$pidsArray['PROJECTS']]);
                    unset($_SESSION[$settings['hub_name'].$pidsArray['PROJECTS']]);
                }

                if(array_key_exists('token', $_REQUEST)  && !empty($_REQUEST['token']) && isTokenCorrect($_REQUEST['token'],$pidsArray['PEOPLE'])) {
                    $_SESSION['token'][$settings['hub_name'].$pidsArray['PROJECTS']] = $_REQUEST['token'];
                }

                #OTHER DATA DISPLAYED ALWAYS OR OFTEN
                $hubData = new HubData($module, $settings['hub_name'].$pidsArray['PROJECTS'], $token, $pidsArray);
                $current_user = $hubData->getCurrentUser();
                $name = $current_user['firstname'].' '.$current_user['lastname'];
                $person_region = $hubData->getPersonRegion();
                $isAdmin = $hubData->getIsAdmin();

                if( array_key_exists('option', $_REQUEST) && $option === 'dfq'){
                    //No header
                }else{
                    include('hub_header.php');
                }
                ?>
                <div class="container" style="margin: 0 auto;float:none;min-height: 900px;">
                    <?php
                    if( array_key_exists('option', $_REQUEST) && $option === 'map' )
                    {
                        include('map/index.php');
                    }else if( !array_key_exists('token', $_REQUEST) && !array_key_exists('request', $_REQUEST) && empty($_SESSION['token'][$settings['hub_name'].$pidsArray['PROJECTS']])){
                        include('hub/hub_login.php');
                    }else if($current_user['active_y'] == "0"){
                        include('hub/hub_login.php');
                    }else if(!empty($_SESSION['token'][$settings['hub_name'].$pidsArray['PROJECTS']]) && isTokenCorrect($_SESSION['token'][$settings['hub_name'].$pidsArray['PROJECTS']],$pidsArray['PEOPLE'])){
                        if( !array_key_exists('option', $_REQUEST)){
                            include('hub/hub_home.php');
                        }
                        else if( array_key_exists('option', $_REQUEST) && $option === 'log')
                        {
                            include('hub/hub_changelog.php');
                        }else if( array_key_exists('option', $_REQUEST) && $option === 'iut')
                        {
                            include('hub/hub_unit_test.php');
                        } else if( array_key_exists('option', $_REQUEST) && $option === 'smn' && !$deactivate_datahub)
                        {
                            include('sop/sop_request_data.php');
                        } else if( array_key_exists('option', $_REQUEST) && $option === 'sra' && !$deactivate_datahub)
                        {
                            include('sop/sop_recent_activity.php');
                        }else if( array_key_exists('option', $_REQUEST) && $option === 'tbl' && !$deactivate_datahub && !$deactivate_tblcenter)
                        {
                            include('sop/sop_table_center.php');
                        }else if( array_key_exists('option', $_REQUEST) && $option === 'ofs' && !$deactivate_datahub)
                        {
                            include('sop/sop_document_library.php');
                        }
                        else if( array_key_exists('option', $_REQUEST) && $option === 'sop' && !$deactivate_datahub)
                        {
                            include('sop/sop_data_request_title.php');
                        }
                        else if( array_key_exists('option', $_REQUEST) && $option === 'dna' && !$deactivate_datahub)
                        {
                            include('sop/sop_news_archive.php');
                        }
                        else if( array_key_exists('option', $_REQUEST) && $option === 'ss1' && !$deactivate_datahub)
                        {
                            include('sop/sop_steps_menu.php');
                        }
                        else if( array_key_exists('option', $_REQUEST) && $option === 'ss5' && !$deactivate_datahub)
                        {
                            include('sop/sop_step_5.php');
                        }else if( array_key_exists('option', $_REQUEST) && $option === 'spr' && !$deactivate_datahub)
                        {
                            include('sop/sop_make_public_request_review.php');
                        }else if( array_key_exists('option', $_REQUEST) && $option === 'lgd' && !$deactivate_datahub)
                        {
                            include('sop/sop_data_activity_log.php');
                        }
                        else if( array_key_exists('option', $_REQUEST) && $option === 'cpt' )
                        {
                            include('hub/hub_concepts.php');
                        }
                        else if( array_key_exists('option', $_REQUEST) && $option === 'ttl' )
                        {
                            include('hub/hub_concept_title.php');
                        }
                        else if( array_key_exists('option', $_REQUEST) && $option === 'hub')
                        {
                            if(array_key_exists('record', $_REQUEST) && !empty($_REQUEST['record'])) {
                                include('hub/hub_request_title.php');
                            }else{
                                include('hub/hub_requests.php');
                            }
                        }else if( array_key_exists('option', $_REQUEST) && $option === 'adm')
                        {
                            if($isAdmin){
                                include('hub/hub_admin.php');
                            }else{
                                include('hub/hub_error_page.php');
                            }
                        }
                        else if( array_key_exists('option', $_REQUEST) && $option === 'usr'){
                            include('hub/hub_users.php');
                        }
                        else if( array_key_exists('option', $_REQUEST) && $option === 'mra')
                        {
                            include('hub/hub_my_requests_archive.php');
                        } else if( array_key_exists('option', $_REQUEST) && $option === 'mrr')
                        {
                            include('hub/hub_my_requests_archive_rejected.php');
                        }
                        else if( array_key_exists('option', $_REQUEST) && $option === 'hra')
                        {
                            include('hub/hub_recent_activity.php');
                        }else if( array_key_exists('option', $_REQUEST) && $option === 'upd' && !$deactivate_datahub)
                        {
                            include('sop/sop_submit_data.php');
                        }else if( array_key_exists('option', $_REQUEST) && $option === 'dat' && !$deactivate_datahub)
                        {
                            include('hub/hub_data.php');
                        }else if( array_key_exists('option', $_REQUEST) && $option === 'pdc' && !$deactivate_datahub)
                        {
                            include('sop/sop_data_call_archive.php');
                        }else if( array_key_exists('option', $_REQUEST) && $option === 'dnd' && !$deactivate_datahub && $settings['deactivate_datadown___1'] != "1")
                        {
                            include('sop/sop_retrieve_data.php');
                        }else if( array_key_exists('option', $_REQUEST) && $option === 'out')
                        {
                            include('hub/hub_publications.php');
                        }else if( array_key_exists('option', $_REQUEST) && $option === 'mts' && ($settings['deactivate_metrics___1'] != "1" || $isAdmin))
                        {
                            include('hub/hub_metrics_stats.php');
                        }else if( array_key_exists('option', $_REQUEST) && $option === 'mth' && ($settings['deactivate_datametrics___1'] != "1" || $isAdmin))
                        {
                            include('sop/sop_metrics_stats.php');
                        }else if( array_key_exists('option', $_REQUEST) && $option === 'faq')
                        {
                            include('faq/hub_faq.php');
                        }else if( array_key_exists('option', $_REQUEST) && $option === 'pro')
                        {
                            include('hub/hub_profile.php');
                        }else if( array_key_exists('option', $_REQUEST) && $option === 'bug')
                        {
                            include('hub/hub_report_bug.php');
                        }else if( array_key_exists('option', $_REQUEST) && $option === 'unf')
                        {
                            include('hub/hub_request_title.php');
                        }else if( array_key_exists('option', $_REQUEST) && $option === 'und' && !$deactivate_datahub)
                        {
                            include('sop/sop_data_request_title.php');
                        }else if( array_key_exists('option', $_REQUEST) && $option === 'cal' && $settings['calendar_active'][1] == "1")
                        {
                            include('hub/hub_calendar.php');
                        }else if( array_key_exists('option', $_REQUEST) && $option === 'abt')
                        {
                            include('hub/hub_about.php');
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
        }
    ?>
    </body>
</html>