<?php
namespace Vanderbilt\HarmonistHubExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

if(!defined('APP_PATH_WEBROOT_ALL')) {
    if (APP_PATH_WEBROOT[0] == '/') {
        $APP_PATH_WEBROOT_ALL = substr(APP_PATH_WEBROOT, 1);
    }
    define('APP_PATH_WEBROOT_ALL', APP_PATH_WEBROOT_FULL . $APP_PATH_WEBROOT_ALL);
}

$hub_projectname = $module->getProjectSetting('hub-projectname');
$hub_profile = $module->getProjectSetting('hub-profile');
$pid = (int)$_GET['pid'];
?>
<!DOCTYPE html>
<html lang="en">
<?php
if(($hub_projectname == '' || $hub_profile == '') || (array_key_exists('message',$_REQUEST) && $_REQUEST['message']=='D')){?>
    <head>
        <?php include_once("head_scripts.php");?>
        <script>
            var startDDProjects_url = <?=json_encode($module->getUrl('startDDProjects.php'))?>;
            var indexPage_url = <?=json_encode($module->getUrl('index.php')."&NOAUTH")?>;
            var pid = <?=json_encode($pid)?>;
        </script>
    </head>
    <body>
<?php }
if($hub_projectname == '' || $hub_profile == ''){
    echo '  <div class="container" style="margin-top: 60px">  
                <div class="alert alert-danger col-md-12">
                    <div class="col-md-10">
                        To start the installation you need fill up the fields in the <a href="'.$module->escape(APP_PATH_WEBROOT_FULL."external_modules/manager/project.php?pid=".$pid).'" target="_blank">External Modules configuration settings</a>.
                    </div>
                 </div>
            </div>';
} else {
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
        ?>
    <head>
        <?php include_once("head_scripts.php");?>
        <script>
            var startDDProjects_url = <?=json_encode($module->getUrl('startDDProjects.php'))?>;
            var indexPage_url = <?=json_encode($module->getUrl('index.php')."&NOAUTH")?>;
            var pid = <?=json_encode($pid)?>;
        </script>
    </head>
    <body>
    <?php
    echo '  <div class="container" style="margin-top: 60px">  
                        <div class="alert alert-warning col-md-12">
                            <div class="col-md-10"><span class="pull-left">
                                The data dictionary for <strong>' . \REDCap::getProjectTitle() . '</strong> is empty.
                                <br/>Click the button to create the data dictionary and all related projects.</span>
                            </div>
                            <div class="col-md-2"><button id="installbtn" onclick="startDDProjects();$(\'#save_continue_4_spinner\').addClass(\'fa fa-spinner fa-spin\');" class="btn btn-primary pull-right"><span id="save_continue_4_spinner"></span> Create Projects & Data Dictionary</button></div>
                        </div>
                    </div>';
    }
}
?>
    </body>
</html>
