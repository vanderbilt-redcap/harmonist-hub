<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include_once(__DIR__ ."/../projects.php");
include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
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
        $(function () {
            $("body").tooltip({ selector: '[data-toggle=tooltip]', placement: 'right' , trigger: 'hover', html: true});
        })
        $(document).ready(function () {
            let urlUserManagament = <?=json_encode($module->getUrl('hub-user-management/data_downloads_user_management_AJAX.php'))?>;
            $('#remove_error_user_management').click(function (event) {
                return manageUserFromDataDownloads(urlUserManagament,'remove');
            });
            $('#add_error_user_management').click(function (event) {
                $('#msgUserList').attr('display','none');
                let errors = false;
                $("#addUsersForm input:not([type=hidden])").each(function (el) {
                    if($('#'+this.id).val() == undefined || $('#'+this.id).val() == ""){
                        $('#'+this.id).addClass("error");
                        errors = true;
                    }
                });
                if(errors){
                    $('#msgUserList').show();
                }else{
                    return manageUserFromDataDownloads(urlUserManagament,'add');
                }
            });
        });

        function checkUserName(user_id){
            let term = $('#user-name-'+user_id).val();
            $.ajax({
                method: "POST",
                url: <?=json_encode($module->getUrl('hub-user-management/getUserInfoAutocomplete_AJAX.php'))?>,
                dataType: "json",
                data: {
                    term: term
                }
            }).done(function(response) {
                $('#user-list-'+user_id).show();
                var lists = '';
                $.each(response, function(key, user) {
                    lists += "<div class='autocomplete-items' onclick='addUserName(\"" + user.value + "\",\"" + user_id + "\")'><a onclick='addUserName(\"" + user.value + "\",\"" + user_id + "\")'>" +  user.label + "</a></div>";
                });
                $('#user-list-'+user_id).html(lists);
            });
        }

        function addUserName(value, id){
            $("#user-list-"+id).html("");
            $("#user-name-"+id).val(value);
        }
    </script>
    <style>
        #selectUserListDataTable thead {
            display: none;
        }
        #selectUserListDataTable {
            width: 100%;
        }
        table.dataTable tbody tr.odd td {
            background-color: #ffffff !important;
        }
        table.dataTable tbody tr.even td {
            background-color: #ffffff !important;
        }
        table.dataTable tbody tr.rowSelected{
            background-color: #e6f5ff !important;
        }
        table.dataTable tbody tr.rowSelected td{
            background-color: #e6f5ff !important;
        }
        .tooltip-inner {
            text-align: left;
            max-width: 100%;
            padding-top:10px;
        }
    </style>
</head>
<body>
<?php
if(isset( $_REQUEST['message'] )) {
    echo '<div class="container-fluid p-y-1"><div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;" id="succMsgContainer">'.$module->getMessageHandler()->fetchMessage('dataDownloadsUser',$_REQUEST['message']).'</div></div>';
}
?>
<div style="padding-top:15px;padding-left:15px;padding-bottom: 60px;">
    Here you will find a list of users that are missing the correct permissions to use Data Downloads.<br>
    Click on each user to see the missing permissiong.
</div>
<?php
$show = true;
include_once ("data_downloads_user_management_buttons.php");
?>
<div class="container-fluid p-y-1">
    <table id="selectUserListDataTable" data-sortable style="border: none;">
        <thead>
        <tr>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php
        $count = 0;
        $data = $module->getDataDownloadsUsersHandler()->getErrorUserList();
//        ArrayFunctions::array_sort_by_column($data, 'lastname',SORT_ASC);
        foreach ($data as $index => $user) {
            $count++;
            $admin = "";
            if($user->getHarmonistadminY() == "1"){
                $admin = "<span class='label label-approved'>Admin</span>";
            }
            $personDataEntryLink = $module->getDataDownloadsUsersHandler()->getDatEntryLink($user->getRecordId(),$_GET['pid']);
            $name = $user->getFirstname()." ".$user->getLastname();
            $userData = $name." ".$user->getRegionCode()." ".$admin;

            $usernameMissing = false;
            foreach($user->getErrorPermissionList() as $index => $errorText) {
                if($index == "usernameMissing"){
                    unset($user->getErrorPermissionList()['usernameMissing']);
                    $usernameMissing = true;
                    break;
                }
            }
            $errorArrayList = implode(";", $user->getErrorPermissionList());
            ?>
            <tr>
                <td style="padding-bottom: 0;padding-top: 0;">
                    <div style="padding-top: 5px;">
                        <div class="panel-heading">
                            <h3 class="panel-title">
                                <table class="table table-striped table-hover" style="margin-bottom:0px; border: 1px solid #dee2e6;font-size: 13px;" data-sortable>
                                    <tr row="<?=$index?>" value="<?=$index?>" name="chkAll_parent_user">
                                        <td style="width: 1%;">
                                            <input id="<?=$user->getRecordId()?>" value="<?=$user->getRecordId()?>" pid="<?=$count?>" user-data="<?=$userData;?>" user-name="<?=$user->getRedcapName();?>" username-missing="<?=$usernameMissing?>" onclick="selectData('<?= $index; ?>','user');" class='auto-submit' type="checkbox" name="chkAll_user" nameCheck='tablefields[]'>
                                        </td>
                                        <td>
                                            <a data-toggle="collapse" href="#collapse<?=$user->getRecordId()?>" id="<?='table_'.$index?>" class="label label-as-badge-square ">
                                                <span class="table_name" style="font-weight: normal;">
                                                    <span style="padding-right: 10px;"><?=$name;?> <?=$user->getRegionCode();?></span>
                                                    <?=$admin;?>
                                                </span>
                                            </a>
                                            <a href="<?=$personDataEntryLink?>" target="_blank" style="float: right;padding-right: 15px;color: #337ab7;font-weight: bold;">View Record</a>
                                        </td>
                                    </tr>
                                </table>
                            </h3>
                        </div>
                        <div id="collapse<?=$user->getRecordId()?>" class="table-responsive panel-collapse collapse" aria-expanded="true">
                            <table style="width: 100%;margin-top: 5px;">
                                <tr style="padding:8px 30px;">
                                    <td>
                                        <ul id="<?="error-list-".$user->getRecordId()?>" error-data="<?=$errorArrayList?>" username-missing="<?=$usernameMissing?>">
                                        <?php foreach ($user->getErrorPermissionList() as $errorType => $error){
                                            if($errorType != "usernameMissing"){?>
                                                <li><?=$error;?></li>
                                           <?php }
                                            } ?>
                                        </ul>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
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
        <a class="btn btn-danger" id="remove_error_user_management"  name="remove_error_user_management">Remove User</a>
    </div>
</div>

<div id="addUsersForm" title="Add Users" style="display:none;">
    <div class="alert alert-danger fade in col-md-12" style="display:none;" id="msgUserList">Username can't be blank.</div>
    <p>Are you sure you want to add these users?</p>
    <div id="user_add_list"></div>
    <input type="hidden" id="checked_values_add_user" name="checked_values_add_user">
    <input type="hidden" id="checked_values_missing_user" name="checked_values_missing_user">
    <div class="modal-footer" style="padding-top: 30px;">
        <a class="btn btn-success" id="add_error_user_management"  name="add_error_user_management">Add</a>
    </div>
</div>
<?php include_once (__DIR__ ."/../hub_spinner.php"); ?>
</body>
</html>
<?php include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';?>

