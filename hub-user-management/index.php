<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include_once(__DIR__ ."/../projects.php");
include_once(__DIR__ ."/../classes/HubREDCapUsers.php");

$projects_array = REDCapManagement::getProjectsConstantsArray();
$projects_titles_array = REDCapManagement::getProjectsTitlesArray();
$hub_name = $settings['hub_name']." Hub";
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
            $(function () {
                $('[data-toggle="tooltip"]').tooltip()
            })

            var sButton = null;
            var $form = $('#user_list');
            var $submitButtons = $form.find('.btnClassConfirm');

            $submitButtons.click(function(event) {
                sButton = this;
            });

            $('#user_list').submit(function (event) {
                //Clean up autocomplete
                $('#new_username').val("");
                $('#user-list').html("");

                if (null === sButton) {
                    sButton = $submitButtons[0];
                }
                console.log(sButton.name)
                let checked_values = [];
                $("input[nameCheck='tablefields[]']:checked").each(function() {
                    checked_values.push($(this).val());
                });

                if(checked_values.length != 0) {
                    //List of projects selected
                    let projects_titles_array = <?=json_encode($projects_titles_array)?>;
                    let hub_name = <?=json_encode($hub_name)?>;
                    let project_info = "<ul>";
                    if (checked_values.length == projects_titles_array.length) {
                        project_info += "<li><span style='color:red;'>ALL REDCap projects have been selected.</span></li>";
                    } else {
                        checked_values.forEach((project_id) => {
                            let gotoREDCap = <?=json_encode(APP_PATH_WEBROOT_ALL . "Design/data_dictionary_codebook.php?pid=")?>;
                            project_info += "<li><a href='" + gotoREDCap + project_id + "' target='_blank'>" + hub_name + ": " + projects_titles_array[$("#" + project_id).attr("pid")] + "</a></li>";
                        });
                    }
                    project_info += "</ul>";
                    $('#projectsSelected_'+sButton.name).html(project_info);
                    $('#checked_values_'+sButton.name).val(checked_values);

                    if(sButton.name == "add_user") {
                        $("#addUsersForm").dialog({
                            width: 700,
                            modal: true,
                            enableRemoteModule: true
                        });
                    }else if(sButton.name == "remove_user") {
                        let users_info = "<ul>";
                        let user_removal_list = [];
                        checked_values.forEach((project_id) => {
                            let user_val = $('#user_'+project_id).attr('user_value');
                            let user_name = $('#user_'+project_id).text();
                            if(!user_removal_list.includes(user_val)){
                                user_removal_list.push(user_val);
                                users_info += "<li><input type='checkbox' nameCheckUser='users[]' value='"+user_val+"'><span style='padding-left: 5px'>"+user_name+"</span></li>";
                            }
                        });
                        users_info += "<ul>";
                        $('#user_remove_list').html(users_info);
                        $("#removeUsersForm").dialog({
                            width: 700,
                            modal: true,
                            enableRemoteModule: true
                        }).prev(".ui-dialog-titlebar").css("background", "#f8d7da").css("color", "#721c24")
                    }
                }else{
                    $("#dialogWarning").dialog({modal:true, width:300}).prev(".ui-dialog-titlebar").css("background","#f8d7da").css("color","#721c24");
                }
                return false;
            });
            $('#new_username').keyup(function(){
                let term = $(this).val();
                $.ajax({
                    method: "POST",
                    url: <?=json_encode($module->getUrl('hub-user-management/getUserInfoAutocomplete_AJAX.php'))?>,
                    dataType: "json",
                    data: {
                        term: term
                    }
                }).done(function(response) {
                    $("#user-list").show();
                    var lists = '';
                    $.each(response, function(key, user) {
                        lists += "<div class='autocomplete-items' onclick='addUserName(\"" + user.value + "\")'><a onclick='addUserName(\"" + user.value + "\")'>" +  user.label + "</a></div>";
                    });
                    $("#user-list").html(lists);
                });
            });

            $('#add_user_management').submit(function (event) {
                let url = <?=json_encode($module->getUrl('hub-user-management/user_management_AJAX.php'))?>;
                let data = $('#add_user_management').serialize();
                console.log(url+"&"+data+"&user_role="+$('#user_role').val()+"&user_list_textarea="+$('#user_list_textarea').val()+"&option=add_user")

                // $.ajax({
                //     type: "POST",
                //     url: url,
                //     data: "&"+data+"&user_role="+$('#user_role').val()+"&user_list_textarea="+$('#user_list_textarea').val()+"&option=add_user",
                //     error: function (xhr, status, error) {
                //         alert(xhr.responseText);
                //     },
                //     success: function (result) {
                //         // var status = jQuery.parseJSON(result)['status'];
                //         // window.location = getMessageLetterUrl(window.location.href, success_message);
                //     }
                // });
                return false;
            });

            $('#remove_user_management').submit(function (event) {
                let url = <?=json_encode($module->getUrl('hub-user-management/user_management_AJAX.php'))?>;
                let data = $('#remove_user_management').serialize();
                let checked_values_user = [];
                $("input[nameCheckUser='users[]']:checked").each(function() {
                    checked_values_user.push($(this).val());
                });
                console.log(url+"&"+data+"&option=remove_user+users_checked="+checked_values_user)

                // $.ajax({
                //     type: "POST",
                //     url: url,
                //     data: "&"+data+"&option=remove_user+users_checked="+checked_values_user,
                //     error: function (xhr, status, error) {
                //         alert(xhr.responseText);
                //     },
                //     success: function (result) {
                //         // var status = jQuery.parseJSON(result)['status'];
                //         // window.location = getMessageLetterUrl(window.location.href, success_message);
                //     }
                // });
                return false;
            });

            $('#remove_user').submit(function (event) {

                return false;
            });
        });
        function addUserName(value){
            let user_list_textarea = $("#user_list_textarea").val();
            if(user_list_textarea == ""){
                $("#user_list_textarea").val(value)
            }else if(user_list_textarea.includes(value)){
                //Do nothing
            }else{
                $("#user_list_textarea").val(user_list_textarea+","+value)
            }
        }
        function checkData(){
            $('#alert_text').hide();
            let errMsg = [];
            if($('#user_list_textarea').val().length == 0){
                errMsg.push('Add at least one user to add to the projects.');
            }
            if($('#user_role').val().length == 0){
                $('#alert_text').show();
                errMsg.push('Select a role to assign to the users.');
            }
            if (errMsg.length > 0) {
                $('#alert_text').empty();
                $.each(errMsg, function (i, e) {
                    $('#alert_text').append('<div>' + e + '</div>');
                });
                $('#alert_text').show();
                return false;
            }
            return true;
        }

        function checkDataRemove(){
            $('#alert_text').hide();
            let errMsg = [];
            if($(this).find('input[nameCheckUser='users[]']:checked').length == 0){
                errMsg.push('Add at least one user to remove from the projects.');
            }
            return true;
        }
    </script>
</head>
<body>
<div style="padding-top:15px;padding-left:15px">
    The data displayed shows the different <?=$hub_name?> projects and their assigned REDCap users.<br>
</div>
<div class="container-fluid p-y-1" style="margin-top:60px">
    <div style="float:left;margin-top: 10px;">
        <input type="checkbox" id="ckb_user" name="chkAll_user" onclick="checkAll('user');" style="cursor: pointer;">
        <span style="cursor: pointer;font-size: 14px;font-weight: normal;color: black;" onclick="checkAllText('user');">Select All</span>
    </div>
    <form method="POST" action="" id="user_list">
        <button type="submit" class="btn btn-danger float-right btnClassConfirm" id="remove_user" name="remove_user">Remove User</button>
        <button type="submit" class="btn btn-warning float-right btnClassConfirm" id="remove_user" name="change_user" style="margin-right:10px">Change Role</button>
        <button type="submit" class="btn btn-primary float-right btnClassConfirm" id="add_user" name="add_user" style="margin-right:10px">Add User</button>
    </form>
</div>
<div class="container-fluid p-y-1">
    <table id="" style="padding-bottom: 10px;width: 100%;">
        <thead>
        <tr>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($projects_array as $id => $constant) {
            $gotoredcap = htmlentities(APP_PATH_WEBROOT_ALL . "Design/data_dictionary_codebook.php?pid=" . $pidsArray[$constant], ENT_QUOTES);
            $users = HubREDCapUsers::getUserList($module, $pidsArray[$constant]);
            ?>
            <tr>
                <td style="padding-bottom: 0;padding-top: 0;">
                    <div style="padding-top: 5px;">
                        <div class="panel-heading">
                            <h3 class="panel-title">
                                <table class="table table-striped table-hover" style="margin-bottom:0px; border: 1px solid #dee2e6;font-size: 13px;" data-sortable>
                                    <tr row="<?=$pidsArray[$constant]?>" value="<?=$pidsArray[$constant]?>" name="chkAll_parent_user">
                                        <td style="width: 5%;">
                                            <input value="<?=$pidsArray[$constant]?>" id="<?=$pidsArray[$constant]?>" pid="<?=$id?>" onclick="selectData('<?= $pidsArray[$constant]; ?>','user');" class='auto-submit' type="checkbox" name="chkAll_user" nameCheck='tablefields[]'>
                                        </td>
                                        <td>
                                            <a data-toggle="collapse" href="#collapse<?=$constant?>" id="<?='table_'.$constant?>" class="label label-as-badge-square ">
                                                <strong><?php echo "<span class='table_name'>".$hub_name.": ".$projects_titles_array[$id]."</span>"; ?></strong>
                                            </a>
                                            <span class="badge label-default"><?=count($users);?></span>
                                            <a href="<?=$gotoredcap?>" target="_blank" style="float: right;padding-right: 15px;color: #337ab7;font-weight: bold;">Go to REDCap</a>
                                        </td>
                                    </tr>
                                </table>
                            </h3>
                        </div>
                        <div id="collapse<?=$constant?>" class="table-responsive panel-collapse collapse" aria-expanded="true">
                            <table style="width: 100%;margin-top: 5px;">
                                <?php foreach ($users as $user){?>
                                <tr><td style="padding-left: 30px;" id="user_<?=$id?>" user_value="<?=$user['value']?>"><?=$user['name']." (".$user['value'].")";?> <a href=""><i class="fa-solid fa-x remove_user"></i></a></td></tr>
                                <?php } ?>
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
<!-- MODAL -->
<div id="addUsersForm" title="Add Users" style="display:none;">
    <form method="POST" action="" id="add_user_management">
        <div class="modal-body">
            <div class="alert alert-danger col-md-12" style="display: none" id="alert_text"></div>
            <?php
            $user_roles_info = "<div>This is more info on user roles:</div>";
            ?>
            <div>Select a user role <a tabindex="0" role="button" class="info-toggle" data-html="true" data-container="body" data-toggle="tooltip" data-trigger="hover" data-placement="right" style="outline: none;" title="<?=$user_roles_info?>"><i class="fas fa-info-circle fa-fw" style="color:#0d6efd" aria-hidden="true"></i></a>:</div>
            <select class="form-select" id="user_role">
                <option></option>
                <option value="<?=HubREDCapUsers::HUB_ROLE_ADMIN?>"><?=HubREDCapUsers::HUB_ROLE_ADMIN?></option>
            </select>
            <br/>
            <div>Add user names separated by commas or search and click on them:</div>
            <div class="autocomplete-user">
                <input id="new_username" class="form-control" type="text" name="new_username">
                <div id="user-list"></div>
            </div>
            <textarea id="user_list_textarea" class="user_list_textarea"></textarea>
            <br/>
            <br/>
            <div>You will be adding users to these projects:</div>
            <div id="projectsSelected_add_user"></div>
            <input type="hidden" id="checked_values_add_user" name="checked_values_add_user">
        </div>
        <div class="modal-footer" style="padding-top: 30px;">
            <a onclick="checkData();$('#add_user_management').submit();" class="btn btn-success" name="btnConfirm">Add User</a>
        </div>
    </form>
</div>
<div id="removeUsersForm" title="Remove Users" style="display:none;">
    <form method="POST" action="" id="remove_user_management">
        <div class="modal-body">
            <div class="alert alert-danger col-md-12" style="display: none" id="alert_text"></div>
            <div>Select the users you want to remove from the projects listed below:</div>
            <div id="user_remove_list"></div>
            <div>You will be removing these users from these projects:</div>
            <div id="projectsSelected_remove_user"></div>
            <input type="hidden" id="checked_values_remove_user" name="checked_values_remove_user">
        </div>
        <div class="modal-footer" style="padding-top: 30px;">
            <a onclick="checkDataRemove();$('#remove_user_management').submit();" class="btn btn-danger" name="btnConfirm">Remove User</a>
        </div>
    </form>
</div>
<div id="dialogWarning" title="WARNING!" style="display:none;">
    <p>No projects selected.</p>
</div>
</body>
</html>
