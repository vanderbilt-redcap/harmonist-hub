<?php
namespace Vanderbilt\HarmonistHubExternalModule;
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
    <script type="text/javascript" src="<?=$module->getUrl('js/jquery.dataTables.min.js')?>"></script>
    <script type="text/javascript" src="<?=$module->getUrl('js/selectAll.js')?>"></script>

    <link type='text/css' href='<?=$module->getUrl('css/sortable-theme-bootstrap.css')?>' rel='stylesheet' media='screen' />
    <link type='text/css' href='<?=$module->getUrl('bootstrap-3.3.7/css/bootstrap.min.css')?>' rel='stylesheet' media='screen' />
    <link type='text/css' href='<?=$module->getUrl('css/style.css')?>' rel='stylesheet' media='screen' />
    <link type='text/css' href='<?=$module->getUrl('css/tabs-steps-menu.css')?>' rel='stylesheet' media='screen' />
    <link type='text/css' href='<?=$module->getUrl('css/styles_user_management.css')?>' rel='stylesheet' media='screen' />

    <script>
        $(document).ready(function () {
            $('#selectSuccessUserListDataTable').dataTable({
                "bPaginate": false,
                "bLengthChange": false,
                "bFilter": true,
                "bInfo": false,
                "fnDrawCallback": function(oSettings) {
                    $('#selectAllDiv').prependTo($('#selectSuccessUserListDataTable_wrapper'));
                }
            });
        });

    </script>
    <style>
        #selectSuccessUserListDataTable thead {
            display: none;
        }
        #selectSuccessUserListDataTable {
            width: 100%;
        }
        /*#selectSuccessUserListDataTable .rowSelected {*/
        /*    background-color: #e6f5ff !important;*/
        /*}*/
    </style>
</head>
<body>
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
<div style="padding-top:15px;padding-left:15px">
    Here you will find a list of users that have the correct permissions to use Data Downloads.<br>
</div>
<div id="selectAllDiv" style="float: left;padding-top: 10px;">
    <input type="checkbox" id="ckb_user" name="chkAll_user" onclick="checkAll('user');" style="cursor: pointer;">
    <a href="#" style="cursor: pointer;font-size: 14px;font-weight: normal;" onclick="checkAllText('user');">Select All</a>
</div>
<div class="container-fluid p-y-1"  style="margin-top:40px">
    <table id="selectSuccessUserListDataTable" class="table table-striped table-hover" style="border: 1px solid #dee2e6;" data-sortable>
        <thead>
            <tr>
                <th></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php
        $count = 0;
//        print_array($hubDataDownloadsUsers->getErrorUserList());
        foreach ($module->getDataDownloadsUsersHandler()->getSuccessUserList() as $index => $user) {
            $count++;
            $admin = "";
            if($user['harmonistadmin_y'] == "1"){
                $admin = '<span class="label label-approved">Admin</span>';
            }
            ?>
            <tr row="<?=$index?>" value="<?=$index?>" name="chkAll_parent_user">
                <td style="width: 5%;">
                    <input value="<?=$index?>" id="<?=$index?>" onclick="selectData('<?= $index; ?>','user');" class='auto-submit' type="checkbox" name="chkAll_user" nameCheck='tablefields[]'>
                </td>
                <td>
                    <?=$user['firstname']." ".$user['lastname'];?> <?=$user['region_code'];?> <?=$admin;?>
                </td>
            </tr>
            <?php
        }
        ?>
        </tbody>
    </table>
</div>
</body>
</html>
