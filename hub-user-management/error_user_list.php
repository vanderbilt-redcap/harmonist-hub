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

    <script>
        $(document).ready(function () {
            $('#selectErrorUserListDataTable').dataTable({
                "bPaginate": false,
                "bLengthChange": false,
                "bFilter": true,
                "bInfo": false
            });

            //when any of the filters is called upon change datatable data
            $('#admin_only, #selectErrorUserListDataTable_filter').change( function() {
                $searchTerm = "";
                if( $('#admin_only').is(":checked")){
                    $searchTerm = "Admin";
                }
                var table = $('#selectErrorUserListDataTable').DataTable();
                table.columns().search($searchTerm).draw();
            } );

            $('#selectErrorUserListDataTable_filter').insertAfter($('#admin_wrapper'));
            $('#selectErrorUserListDataTable_filter').css("padding-left", "25%");
            $('#selectErrorUserListDataTable_filter').css("margin-top", "10px");
            $('#selectErrorUserListDataTable_filter').css("float", "left");

            $('#remove_user').click(function () {
                $('user_remove_list').html("");

                let checked_values = [];
                $("input[nameCheck='tablefields[]']:checked").each(function() {
                    checked_values.push($(this).val());
                });

                if(checked_values.length != 0) {
                    $('#checked_values_remove_user').val(checked_values);
                    let users_info = "<ul>";
                    checked_values.forEach((user_id) => {
                        users_info += "<li >"+$('#'+user_id).attr("user-name")+"</li>";
                    });
                    users_info += "<ul>";
                    $('#user_remove_list').html(users_info);
                    $("#removeUsersForm").dialog({
                        width: 700,
                        modal: true,
                        enableRemoteModule: true
                    }).prev(".ui-dialog-titlebar").css("background", "#f8d7da").css("color", "#721c24")

                }else{
                    $("#dialogWarning").dialog({modal:true, width:300}).prev(".ui-dialog-titlebar").css("background","#f8d7da").css("color","#721c24");
                }
                return false;
            });

            $('#remove_user_single_management').submit(function (event) {
                $("#dialogWarningDelete").dialog('close');
                $("#hubUsersSpinner").dialog({modal:true, width:400});
                let url = <?=json_encode($module->getUrl('hub-user-management/data_downloads_user_management_AJAX.php'))?>;
                let id = $(this).attr('id');
                let option = id.replace('_management','');
                let data = "&checked_values_remove_user="+$("#checked_values_remove_user").val() + "&option="+option;
                $.ajax({
                    type: "POST",
                    url: url,
                    data: data,
                    error: function (xhr, status, error) {
                        alert(xhr.responseText);
                    },
                    success: function (result) {
                        var message = jQuery.parseJSON(result)['message'];
                        window.location = getMessageLetterUrl(window.location.href, message);
                        $("#hubUsersSpinner").dialog('close');
                    }
                });
                return false;
            });
        });

        function getMessageLetterUrl(url, letter){
            if (url.substring(url.length-1) == "#")
            {
                url = url.substring(0, url.length-1);
            }

            if(url.match(/(&message=)([A-Z]{1})/)){
                url = url.replace( /(&message=)([A-Z]{1})/, "&message="+letter );
            }else{
                url = url + "&message="+letter;
            }
            return url;
        }
    </script>
    <style>
        #selectErrorUserListDataTable thead {
            display: none;
        }
        #selectErrorUserListDataTable {
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
    </style>
</head>
<body>
<?php
if(isset( $_REQUEST['message'] )) {
    echo '<div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;" id="succMsgContainer">'.$module->getMessageHandler()->fetchMessage('dataDownloadsUser',$_REQUEST['message']).'</div>';
}
?>
<div style="padding-top:15px;padding-left:15px;padding-bottom: 60px;">
    Here you will find a list of users that are missing the correct permissions to use Data Downloads.<br>
    Click on each user to see the missing permissiong.
</div>

<div id="btn_wrapper" class="container-fluid p-y-1" style="float:left;">
    <div id="selectAll_wrapper" style="float:left;margin-top: 10px;margin-left: 10px;">
        <input type="checkbox" id="ckb_user" name="chkAll_user" onclick="checkAll('user');" style="cursor: pointer;">
        <a href="#" style="cursor: pointer;font-size: 14px;font-weight: normal;" onclick="checkAllText('user');">Select All</a>
    </div>
    <div id="admin_wrapper" style="float:left;padding-left: 15px;
    padding-top: 11px;">
        <input type="checkbox" id="admin_only" name="admin_only" style="cursor: pointer;float: left;margin-right: 5px;box-shadow: none;">
        <label for="admin_only">Admins Only</label>
    </div>
    <button type="button" class="btn btn-danger float-right btnClassConfirm" id="remove_user" style="margin-right: 10px;">Remove User</button>
</div>
<div class="container-fluid p-y-1">
    <table id="selectErrorUserListDataTable" data-sortable style="border: none;">
        <thead>
        <tr>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php
        $count = 0;
        foreach ($module->getDataDownloadsUsersHandler()->getErrorUserList() as $index => $user) {
            $count++;
            $admin = "";
            if($user['harmonistadmin_y'] == "1"){
                $admin = "<span class='label label-approved'>Admin</span>";
            }
            $gotoredcap = htmlentities(APP_PATH_WEBROOT_ALL . "DataEntry/record_home.php?pid=" . $_GET['pid']."&id=".$user['record_id'], ENT_QUOTES);
            $name = $user['firstname']." ".$user['lastname'];
            ?>
            <tr>
                <td style="padding-bottom: 0;padding-top: 0;">
                    <div style="padding-top: 5px;">
                        <div class="panel-heading">
                            <h3 class="panel-title">
                                <table class="table table-striped table-hover" style="margin-bottom:0px; border: 1px solid #dee2e6;font-size: 13px;" data-sortable>
                                    <tr row="<?=$index?>" value="<?=$index?>" name="chkAll_parent_user">
                                        <td style="width: 1%;">
                                            <input id="<?=$user['record_id']?>" value="<?=$user['record_id']?>" pid="<?=$count?>" user-name="<?=$name;?>" onclick="selectData('<?= $index; ?>','user');" class='auto-submit' type="checkbox" name="chkAll_user" nameCheck='tablefields[]'>
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
                                </table>
                            </h3>
                        </div>
                        <div id="collapse<?=$index?>" class="table-responsive panel-collapse collapse" aria-expanded="true">
                            <table style="width: 100%;margin-top: 5px;">
                                <tr style="padding:8px 30px;">
                                    <td>
                                        <ul>
                                        <?php foreach ($user['error_permission_list'] as $error){?>
                                            <li><?=$error;?></li>
                                        <?php } ?>
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
    <form method="POST" action="" id="remove_user_single_management">
        <p>Are you sure you want to remove these users?</p>
        <p>This will remove the user from the Data Downloads project and from the download secure data list.</p>
        <div id="user_remove_list"></div>
        <input type="hidden" id="checked_values_remove_user" name="checked_values_remove_user">
        <div class="modal-footer" style="padding-top: 30px;">
            <a onclick="$('#remove_user_single_management').closest('form').submit();" class="btn btn-danger" name="btnConfirm">Remove User</a>
        </div>
    </form>
</div>
<div style="display:none" title="Updating..." id="hubUsersSpinner">
    <div style="padding-top: 20px">
        <div class="alert alert-success">
            <em class="fa fa-spinner fa-spin"></em> Please wait until the process finishes.
        </div>
    </div>
</div>
</body>
</html>
<?php include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';?>

