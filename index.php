<?php
namespace Vanderbilt\DataModelBrowserExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
?>
    <meta name="viewport" content="width=device-width, initial-scale=1">


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

    <script type="text/javascript" src="<?=$module->getUrl('js/functions.js')?>"></script>

    <link type='text/css' href='<?=$module->getUrl('manifest.json')?>' rel='stylesheet' media='screen' />
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
            <?php include('header.php'); ?>
            <?php include('navbar.php'); ?>
        </head>
        <body>
        <?php
//        include ("crontasks/cron_publications.php");
        ?>
        <br/>
        </body>
        </html>
        <?php
    }
}
?>