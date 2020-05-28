<?php
namespace Vanderbilt\DataModelBrowserExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
?>
    <meta name="viewport" content="width=device-width, initial-scale=1">


    <script src="https://cdnjs.cloudflare.com/ajax/libs/iframe-resizer/3.6.2/iframeResizer.min.js" integrity="sha256-aYf0FZGWqOuKNPJ4HkmnMZeODgj3DVslnYf+8dCN9/k=" crossorigin="anonymous"></script>
    <script type="text/javascript" src="<?=$module->getUrl('js/jquery-3.3.1.min.js')?>"></script>
    <script type="text/javascript" src="<?=$module->getUrl('js/jquery-ui.min.js')?>"></script>
    <script type="text/javascript" src="<?=$module->getUrl('bootstrap-3.3.7/js/bootstrap.min.js')?>"></script>

    <script type="text/javascript" src="<?=$module->getUrl('js/jquery.tablesorter.min.js')?>"></script>
    <script type="text/javascript" src="<?=$module->getUrl('js/sortable.min.js')?>"></script>

    <script type="text/javascript" src="<?=$module->getUrl('js/jquery.dataTables.min.js')?>"></script>
    <script type="text/javascript" src="<?=$module->getUrl('js/dataTables.select.js')?>"></script>
    <script type="text/javascript" src="<?=$module->getUrl('js/dataTables.buttons.min.js')?>"></script>
    <script type="text/javascript" src="<?=$module->getUrl('js/buttons.flash.min.js')?>"></script>
    <script type="text/javascript" src="<?=$module->getUrl('js/buttons.html5.min.js')?>"></script>
    <script type="text/javascript" src="<?=$module->getUrl('js/buttons.print.min.js')?>"></script>
    <script type="text/javascript" src="<?=$module->getUrl('js/jszip.min.js')?>"></script>
    <script type="text/javascript" src="<?=$module->getUrl('js/pdfmake.min.js')?>"></script>
    <script type="text/javascript" src="<?=$module->getUrl('js/vfs_fonts.js')?>"></script>
    <script type="text/javascript" src="<?=$module->getUrl('js/tinymce4.8.3/jquery.tinymce.min.js')?>"></script>
    <script type="text/javascript" src="<?=$module->getUrl('js/tinymce4.8.3/tinymce.min.js')?>"></script>
    <script type="text/javascript" src="<?=$module->getUrl('js/Chart.min.js')?>"></script>
    <script type="text/javascript" src="<?=$module->getUrl('js/chartjs-plugin-labels.js')?>"></script>
    <script type='text/javascript' href='<?=$module->getUrl('manifest.json')?>'></script>

    <script type="text/javascript" src="<?=$module->getUrl('js/functions.js')?>"></script>


    <link type='text/css' href='<?=$module->getUrl('css/sortable-theme-bootstrap.css')?>' rel='stylesheet' media='screen' />
    <link type='text/css' href='<?=$module->getUrl('bootstrap-3.3.7/css/bootstrap.min.css')?>' rel='stylesheet' media='screen' />
    <link type='text/css' href='<?=$module->getUrl('css/style.css')?>' rel='stylesheet' media='screen' />
    <link type='text/css' href='<?=$module->getUrl('css/tabs-steps-menu.css')?>' rel='stylesheet' media='screen' />
    <link type='text/css' href='<?=$module->getUrl('css/jquery-ui.min.css')?>' rel='stylesheet' media='screen' />
    <link type='text/css' href='<?=$module->getUrl('js/fonts-awesome/css/font-awesome.min.css')?>' rel='stylesheet' media='screen' />

    <script>
        var startDDProjects_url = <?=json_encode($module->getUrl('startDDProjects.php'))?>;
        var pid = <?=json_encode($_GET['pid'])?>;
    </script>

    <?php if(array_key_exists('message',$_REQUEST) && $_REQUEST['message']=='S'){?>
        <div class="container" style="margin-top: 80px">
            <div class="alert alert-success col-md-12">
               Data Dictionary and projects successfully installed. To see the Project Ids go to the <a href="<?=APP_PATH_WEBROOT?>DataEntry/record_status_dashboard.php?pid=<?=$_REQUEST['pid']?>" target="_blank">Record Dashboard</a>.
            </div>
        </div>
    <?php } ?>

<?php

if(APP_PATH_WEBROOT[0] == '/'){
    $APP_PATH_WEBROOT_ALL = substr(APP_PATH_WEBROOT, 1);
}
define('APP_PATH_WEBROOT_ALL',APP_PATH_WEBROOT_FULL.$APP_PATH_WEBROOT_ALL);

$hub_projectname = $module->getProjectSetting('hub-projectname');
$hub_profile = $module->getProjectSetting('hub-profile');

if($hub_projectname == '' || $hub_profile == ''){
    echo '  <div class="container" style="margin-top: 60px">  
                <div class="alert alert-danger col-md-12">
                    <div class="col-md-10">
                        To start the installation you need fill up the fields in the <a href="'.APP_PATH_WEBROOT_FULL."external_modules/manager/project.php?pid=".$_GET['pid'].'" target="_blank">External Modules configuration settings</a>.
                    </div>
                 </div>
            </div>';
}else {
    #User rights
    $UserRights = \REDCap::getUserRights(USERID)[USERID];
    $isAdmin = false;
    if ($UserRights['user_rights'] == '1') {
        $isAdmin = true;
    }

    $dd_array = \REDCap::getDataDictionary('array');
    $data_array = \REDCap::getData($_GET['pid'], 'array');
    if (count($dd_array) == 1 && $isAdmin && !array_key_exists('project_constant', $dd_array) && !array_key_exists('project_id', $dd_array) || count($data_array) == 0) {
        echo '  <div class="container" style="margin-top: 60px">  
                    <div class="alert alert-warning col-md-12">
                        <div class="col-md-10"><span class="pull-left">
                            The data dictionary for <strong>' . \REDCap::getProjectTitle() . '</strong> is empty.
                            <br/>Click the button to create the data dictionary and all related projects.</span>
                        </div>
                        <div class="col-md-2"><a href="#" onclick="startDDProjects();$(\'#save_continue_4_spinner\').addClass(\'fa fa-spinner fa-spin\');" class="btn btn-primary pull-right"><span id="save_continue_4_spinner"></span> Create Projects & Data Dictionary</a></div>
                    </div>
                </div>';
    } else {
        include_once("projects.php");
        $settings = \REDCap::getData(array('project_id' => IEDEA_SETTINGS), 'array')[1][$module->framework->getEventId(IEDEA_SETTINGS)];


        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <meta name="description" content="">
            <meta name="author" content="">
            <meta http-equiv="Cache-control" content="public">
            <meta name="theme-color" content="#fff">
            <link rel="icon" href="<?=getFile($module,$settings['hub_logo_favicon'],'url')?>">

            <title><?= $settings['des_doc_title'] ?></title>

            <script type='text/javascript'>
                var app_path_webroot = '<?=APP_PATH_WEBROOT?>';
                var app_path_webroot_full = '<?=APP_PATH_WEBROOT_FULL?>';
                var app_path_images = '<?=APP_PATH_IMAGES?>';
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

                    var pageurloption = <?=json_encode($_REQUEST['option'])?>;
                    $('[option='+pageurloption+']').addClass('navbar-active');

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
            if($settings['deactivate_datahub'][0] == "1"){
                $deactivate_datahub = true;
            }
            $deactivate_tblcenter = false;
            if($settings['deactivate_tblcenter'][0] == "1"){
                $deactivate_tblcenter = true;
            }

            $deactivate_toolkit = false;
            if($settings['deactivate_toolkit'][0] == "1"){
                $deactivate_toolkit = true;
            }

            #TOKEN
            session_write_close();
            session_module_name("IEDEA_user");
            session_name("IEDEA");
            // server should keep session data for AT LEAST 2 days
            ini_set('session.cookie_lifetime', 172800);
            session_set_cookie_params(172800);
            session_start();

            $token = "";
            if( !array_key_exists('token', $_REQUEST) && !array_key_exists('request', $_REQUEST) && ((array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'dnd') || (array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'lgd' && array_key_exists('del', $_REQUEST) && $_REQUEST['del'] != ''))){
                $_SESSION['token'] = array();
                $_SESSION['token'][$settings['hub_name'].constant(ENVIRONMENT.'_IEDEA_PROJECTS')] = getToken(USERID);
                $token = $_SESSION['token'][$settings['hub_name'].constant(ENVIRONMENT.'_IEDEA_PROJECTS')];
            }else if(array_key_exists('token', $_REQUEST)  && !empty($_REQUEST['token']) && isTokenCorrect($_REQUEST['token'])){
                $token = $_REQUEST['token'];
            }else if(!empty($_SESSION['token'][$settings['hub_name'].constant(ENVIRONMENT.'_IEDEA_PROJECTS')])&& isTokenCorrect($_SESSION['token'][$settings['hub_name'].constant(ENVIRONMENT.'_IEDEA_PROJECTS')])) {
                $token = $_SESSION['token'][$settings['hub_name'].constant(ENVIRONMENT.'_IEDEA_PROJECTS')];
            }

            if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'dfq'){
                //No header
            }else{
                include('hub_header.php');
            }
            ?>
            <div class="container" style="margin: 0 auto;float:none;min-height: 900px;">
                <?php
                //Session OUT
                if(array_key_exists('sout', $_REQUEST)){
                    unset($_SESSION['token'][$settings['hub_name'].constant(ENVIRONMENT.'_IEDEA_PROJECTS')]);
                }

                if(array_key_exists('token', $_REQUEST)  && !empty($_REQUEST['token']) && isTokenCorrect($_REQUEST['token'])) {
                    $_SESSION['token'][$settings['hub_name'].constant(ENVIRONMENT.'_IEDEA_PROJECTS')] = $_REQUEST['token'];
                }

                if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'map' )
                {
                    include('map/index.php');
                }else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'dfq')
                {
                    include('faq/datatoolkit_faq.php');
                }else if( !array_key_exists('token', $_REQUEST) && !array_key_exists('request', $_REQUEST) && empty($_SESSION['token'][$settings['hub_name'].constant(ENVIRONMENT.'_IEDEA_PROJECTS')])){
                    include('hub/hub_login.php');
                }else if($current_user['active_y'] == "0"){
                    include('hub/hub_login.php');
                }else if(!empty($_SESSION['token'][$settings['hub_name'].constant(ENVIRONMENT.'_IEDEA_PROJECTS')]) && isTokenCorrect($_SESSION['token'][$settings['hub_name'].constant(ENVIRONMENT.'_IEDEA_PROJECTS')])){
                    if( !array_key_exists('option', $_REQUEST)){
                        include('hub/hub_home.php');
                    }
                    else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'log')
                    {
                        include('hub/hub_changelog.php');
                    } else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'smn' && !$deactivate_datahub)
                    {
                        include('sop/sop_request_data.php');
                    } else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'gac')
                    {
                        include('hub/getActiveConceptsAndAuthors.php');
                    } else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'sra' && !$deactivate_datahub)
                    {
                        include('sop/sop_recent_activity.php');
                    }else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'tbl' && !$deactivate_datahub && !$deactivate_tblcenter)
                    {
                        include('sop/sop_table_center.php');
                    }else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'ofs' && !$deactivate_datahub)
                    {
                        include('sop/sop_document_library.php');
                    }
                    else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'sop' && !$deactivate_datahub)
                    {
                        include('sop/sop_title.php');
                    }
                    else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'fsa' && !$deactivate_datahub)
                    {
                        include('sop/sop_final_archive.php');
                    }
                    else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'dna' && !$deactivate_datahub)
                    {
                        include('sop/sop_news_archive.php');
                    }
                    else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'ss1' && !$deactivate_datahub)
                    {
                        include('sop/sop_steps_menu.php');
                    }
                    else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'ss5' && !$deactivate_datahub)
                    {
                        include('sop/sop_step_5.php');
                    }else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'spr' && !$deactivate_datahub)
                    {
                        include('sop/sop_make_public_request_review.php');
                    }else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'lgd' && !$deactivate_datahub)
                    {
                        include('sop/sop_data_activity_log.php');
                    }
                    else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'cpt' )
                    {
                        include('harmonist/concepts/concepts.php');
                    }
                    else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'ttl' )
                    {
                        include('harmonist/concepts/concepts_title.php');
                    }
                    else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'cup' )
                    {
                        include('harmonist/concepts/concepts_update.php');
                    }else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'hub')
                    {
                        if(array_key_exists('record', $_REQUEST) && !empty($_REQUEST['record'])) {
                            include('hub/hub_request_title.php');
                        }else{
                            include('hub/hub_requests.php');
                        }
                    }else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'adm')
                    {
                        if($isAdmin){
                            include('hub/hub_admin.php');
                        }else{
                            include('hub/hub_error_page.php');
                        }
                    }
                    else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'usr'){
                        include('hub/hub_users.php');
                    }
                    else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'mra')
                    {
                        include('hub/hub_my_requests_archive.php');
                    } else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'mrr')
                    {
                        include('hub/hub_my_requests_archive_rejected.php');
                    }
                    else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'hra')
                    {
                        include('hub/hub_recent_activity.php');
                    }else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'upd' && !$deactivate_datahub)
                    {
                        include('sop/sop_submit_data.php');
                    }else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'dat' && !$deactivate_datahub)
                    {
                        include('hub/hub_data.php');
                    }else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'pdc' && !$deactivate_datahub)
                    {
                        include('sop/sop_data_call_archive.php');
                    }else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'uph' && !$deactivate_datahub)
                    {
                        include('hub/hub_data_upload_history.php');
                    }else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'dnd' && !$deactivate_datahub && $settings['deactivate_datadown'][0] != "1")
                    {
                        include('sop/sop_retrieve_data.php');
                    }else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'out')
                    {
                        include('hub/hub_publications.php');
                    }else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'mts' && ($settings['deactivate_metrics'][0] != "1" || $isAdmin))
                    {
                        include('hub/hub_metrics_stats.php');
                    }else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'mth' && ($settings['deactivate_datametrics'][0] != "1" || $isAdmin))
                    {
                        include('hub/hub_metrics_general_stats.php');
                    }else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'faq')
                    {
                        include('faq/hub_faq.php');
                    }else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'arc')
                    {
                        include('hub/hub_archive.php');
                    }else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'pro')
                    {
                        include('hub/hub_profile.php');
                    }else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'bug')
                    {
                        include('hub/hub_report_bug.php');
                    }else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'unf')
                    {
                        include('hub/hub_request_title.php');
                    }else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'und' && !$deactivate_datahub)
                    {
                        include('sop/sop_title.php');
                    }else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'cal' && $settings['calendar_active'][0] == "1")
                    {
                        include('hub/hub_calendar.php');
                    }else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'abt')
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
        </body>
        </html>
        <?php
    }
}
?>