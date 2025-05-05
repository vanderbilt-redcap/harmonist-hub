<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include_once(__DIR__ ."/../projects.php");
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
    <link type='text/css' href='<?=$module->getUrl('css/sortable-theme-bootstrap.css')?>' rel='stylesheet' media='screen' />
    <link type='text/css' href='<?=$module->getUrl('bootstrap-3.3.7/css/bootstrap.min.css')?>' rel='stylesheet' media='screen' />
    <link type='text/css' href='<?=$module->getUrl('css/style.css')?>' rel='stylesheet' media='screen' />
    <link type='text/css' href='<?=$module->getUrl('css/tabs-steps-menu.css')?>' rel='stylesheet' media='screen' />
    <link type='text/css' href='<?=$module->getUrl('css/styles_user_management.css')?>' rel='stylesheet' media='screen' />

    <script type="text/javascript" src="<?=$module->getUrl('js/selectAll.js')?>"></script>
    <script type="text/javascript" src="<?=$module->getUrl('js/data_downloads_user_management.js')?>"></script>

    <script>
        $(document).ready(function () {
            let urlRemove = <?=json_encode($module->getUrl('hub-user-management/data_downloads_user_management_AJAX.php'))?>;
            $('#remove_success_user_management').click(function (event) {
                return removeUserFromDataDownloads(urlRemove);
            });
        });
    </script>
    <style>
        #selectUserListDataTable thead {
            display: none;
        }
        #selectUserListDataTable {
            width: 100%;
        }
    </style>
</head>
<body>
<?php
if(isset( $_REQUEST['message'] )) {
    echo '<div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;" id="succMsgContainer">'.$module->getMessageHandler()->fetchMessage('dataDownloadsUser',$_REQUEST['message']).'</div>';
}
?>
<?php if (!empty($module->getDataDownloadsUsersHandler()->getErrorUserList())){ ?>
    <div class="container" style="margin-top: 10px">
        <div class="alert alert-warning col-md-12">
            <div style="float: left;">There are users that need to be reviewed due to permission issues.</div>
            <form method="POST" action="<?=$module->getUrl('hub-user-management/error_user_list.php') . '&redcap_csrf_token=' . $module->getCSRFToken()?>" class="" id="resolved_list">
                <div class="float-right"><button type="submit" name="option" value="update" class="btn btn-warning" style="display: block;margin-right: 10px;">Manage Users</button></div>
            </form>
        </div>
    </div>
<?php } ?>
<div style="padding-top:15px;padding-left:15px;padding-bottom: 60px;">
    <div>Here you will find a list of users that have the correct permissions to use Data Downloads.<br></div>
</div>
<?php include_once ("data_downloads_user_management_buttons.php")?>
<div class="container-fluid p-y-1"  style="margin-top:40px">
    <table id="selectUserListDataTable" class="table table-striped table-hover" style="border: 1px solid #dee2e6;" data-sortable>
        <thead>
            <tr>
                <th></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php
        $count = 0;
        foreach ($module->getDataDownloadsUsersHandler()->getSuccessUserList() as $index => $user) {
            $count++;
            $admin = "";
            if($user['harmonistadmin_y'] == "1"){
                $admin = "<span class='label label-approved'>Admin</span>";
            }
            $gotoredcap = htmlentities(APP_PATH_WEBROOT_ALL . "DataEntry/record_home.php?pid=" . $_GET['pid']."&id=".$user['record_id'], ENT_QUOTES);
            $name = $user['firstname']." ".$user['lastname'];
            $userData = $name." ".$user['region_code']." ".$admin;
            ?>
            <tr row="<?=$index?>" value="<?=$index?>" name="chkAll_parent_user">
                <td style="width: 5%;">
                    <input id="<?=$user['record_id']?>" value="<?=$user['record_id']?>" pid="<?=$count?>" user-data="<?=$userData;?>" onclick="selectData('<?= $index; ?>','user');" class='auto-submit' type="checkbox" name="chkAll_user" nameCheck='tablefields[]'>
                </td>
                <td>
                    <a data-toggle="collapse" href="#collapse<?=$index?>" id="<?='table_'.$index?>" class="label label-as-badge-square ">
                                                <span class="table_name" style="font-weight: normal;">
                                                    <span style="padding-right: 10px;"><?=$name;?> <?=$user['region_code'];?></span>
                                                    <?=$admin;?>
                                                </span>
                    </a>
                    <a href="<?=$gotoredcap?>" target="_blank" style="float: right;padding-right: 15px;color: #337ab7;font-weight: bold;">Go to REDCap</a>
                </td>
            </tr>
            <?php
        }
        ?>
        </tbody>
    </table>
</div>
<div id="dialogWarning" title="WARNING!" style="display:none;">
    <p>No users have been selected.</p>
</div>
<div id="removeUsersForm" title="WARNING!" style="display:none;">
    <p>Are you sure you want to remove these users?</p>
    <p>This will remove the user from the Data Downloads project and from the download secure data list.</p>
    <div id="user_remove_list"></div>
    <input type="hidden" id="checked_values_remove_user" name="checked_values_remove_user">
    <div class="modal-footer" style="padding-top: 30px;">
        <a class="btn btn-danger" id="remove_success_user_management"  name="remove_success_user_management">Remove User</a>
    </div>
</div>
<?php include_once (__DIR__ ."/../hub_spinner.php"); ?>
</body>
</html>
