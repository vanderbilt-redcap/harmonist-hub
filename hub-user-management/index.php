<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include_once(__DIR__ ."/../projects.php");
include_once(__DIR__ ."/../classes/HubREDCapUsers.php");

$projects_array = REDCapManagement::getProjectsConstantsArray();
$projects_titles_array = REDCapManagement::getProjectsTitlesArray();
$hub_name = $settings['hub_name']." Hub";
array_push($projects_array,"PROJECTS");
array_push($projects_titles_array,$hub_name.": Parent Project (MAP)");
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
            });

            let gotoREDCap = <?=json_encode(APP_PATH_WEBROOT_ALL . "ProjectSetup/index.php?pid=")?>;

            $('#add_user,#change_user,#remove_user').click(function () {
                //Clean up autocomplete
                $('#new_username').val("");
                $('#user-list').html("");

                let checked_values = [];
                $("input[nameCheck='tablefields[]']:checked").each(function() {
                    checked_values.push($(this).val());
                });

                if(checked_values.length != 0) {
                    //List of projects selected
                    let projects_titles_array = <?=json_encode($projects_titles_array)?>;
                    let hub_name = <?=json_encode($hub_name)?>;
                    let button_name = $(this).attr('id');
                    $('#checked_values_'+button_name).val(checked_values);

                    if(button_name == "change_user") {
                        let project_info = "<ul>";
                        checked_values.forEach((project_id) => {
                            $('[user_pid = "'+project_id+'"]').each(function() {
                                let user_val = $(this).attr('user_value');
                                let user_role = $(this).attr('user_role');
                                let user_name = $(this).attr('user_name');
                                project_info += "<li style='list-style-type: none;'><input type='checkbox' nameCheckUser='users[]' value='"+user_val+"'><span style='padding-left: 5px'>"+user_name+"</span>";
                                if(user_role != ""){
                                    project_info += ", "+user_role;
                                }
                                project_info += " on <a href='" + gotoREDCap + project_id + "' target='_blank'>" + hub_name + ": " + projects_titles_array[$("#" + project_id).attr("pid")] + "</a></li>";
                            });
                        });
                        project_info += "<ul>";
                        $('#projectsSelected_'+button_name).html(project_info);

                        $("#changeUsersForm").dialog({
                            width: 700,
                            modal: true,
                            enableRemoteModule: true
                        });
                    }else{
                        let project_info = "<ul>";
                        if (checked_values.length == projects_titles_array.length) {
                            project_info += "<li><span style='color:red;'>ALL REDCap projects have been selected.</span></li>";
                        } else {
                            checked_values.forEach((project_id) => {
                                project_info += "<li><a href='" + gotoREDCap + project_id + "' target='_blank'>" + hub_name + ": " + projects_titles_array[$("#" + project_id).attr("pid")] + "</a></li>";
                            });
                        }
                        project_info += "</ul>";
                        $('#projectsSelected_'+button_name).html(project_info);

                        if(button_name == "add_user") {
                            $("#addUsersForm").dialog({
                                width: 700,
                                modal: true,
                                enableRemoteModule: true
                            });
                        }else if(button_name == "remove_user") {
                            let users_info = "<ul>";
                            let user_removal_list = [];
                            checked_values.forEach((project_id) => {
                                $('[user_pid = "'+project_id+'"]').each(function() {
                                    let user_val = $(this).attr('user_value');
                                    let user_name = $(this).attr('user_name');
                                    if(!user_removal_list.includes(user_val)){
                                        user_removal_list.push(user_val);
                                        users_info += "<li style='list-style-type: none;'><input type='checkbox' nameCheckUser='users[]' value='"+user_val+"'><span style='padding-left: 5px'>"+user_name+"</span></li>";
                                    }
                                });
                            });
                            users_info += "<ul>";
                            $('#user_remove_list').html(users_info);
                            $("#removeUsersForm").dialog({
                                width: 700,
                                modal: true,
                                enableRemoteModule: true
                            }).prev(".ui-dialog-titlebar").css("background", "#f8d7da").css("color", "#721c24")
                        }
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

            $('#add_user_management, #remove_user_management, #change_user_management, #change_user_single_management, #remove_user_single_management').submit(function (event) {
                let url = <?=json_encode($module->getUrl('hub-user-management/user_management_AJAX.php'))?>;
                let id = $(this).attr('id');
                let option = id.replace('_management','');
                let role_name = $('#user_role_'+option+' option:selected').attr('role_name');
                let data = "&user_role_id_"+option+"="+$('#user_role_'+option).val()+
                    "&user_id_"+option+"="+$('#user_id_'+option).val()+
                    "&project_id="+$('#project_id_'+option).val()+
                    "&option="+option;

                if(option == "add_user"){
                    data += "&users_checked="+$("#user_list_textarea").val();
                }else if(option == "remove_user" || option == "change_user"){
                    let checked_values_user = [];
                    $("input[nameCheckUser='users[]']:checked").each(function() {
                        checked_values_user.push($(this).val());
                    });
                    data += "&users_checked="+checked_values_user;
                }else if(option == "remove_user_single") {
                    role_name = $('#role_name_remove_user_single').val();
                }
                data +=  "&user_role_name_"+option+"="+role_name;

                $.ajax({
                    type: "POST",
                    url: url,
                    data: $('#'+option+'_management').serialize() + data,
                    error: function (xhr, status, error) {
                        alert(xhr.responseText);
                    },
                    success: function (result) {
                        var message = jQuery.parseJSON(result)['message'];
                        window.location = getMessageLetterUrl(window.location.href, message);
                    }
                });
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

        function checkData(option){
            $('#alert_text_'+option).hide();
            let errMsg = [];

            if(option == "add_user") {
                if ($('#user_role_'+option).val().length == 0) {
                    errMsg.push('Select a role to assign to the users.');
                }
                if ($('#user_list_textarea').val().length == 0) {
                    errMsg.push('Add at least one user to add to the projects.');
                }
            }else {
                if (option != "change_user_single"){
                    if ($('#' + option + '_management').find('input[nameCheckUser=\'users[]\']:checked').length == 0) {
                        errMsg.push('Add at least one user needs to be checked to remove from the projects.');
                    }
                }
                if(option == "change_user" || option == "change_user_single") {
                    if ($('#user_role_' + option).val().length == 0) {
                        errMsg.push('A role must be selected.');
                    }
                }
            }

            if(!showErrorMessage('alert_text_'+option, errMsg)){
                $('#'+option+'_management').submit();
            }
        }

        function showErrorMessage(id, errMsg){
            if (errMsg.length > 0) {
                $('#'+id).empty();
                $.each(errMsg, function (i, e) {
                    $('#'+id).append('<div>' + e + '</div>');
                });
                $('#'+id).show();
                return true;
            }
            return false;
        }

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
</head>
<body>
<?php
 $message = "";
    if (array_key_exists('message', $_REQUEST) && ($_REQUEST['message'] == 'A')) {
        $message = "The users have been added successfully.";
    }else if (array_key_exists('message', $_REQUEST) && ($_REQUEST['message'] == 'D')) {
        $message = "The users have been removed successfully.";
    }else if (array_key_exists('message', $_REQUEST) && ($_REQUEST['message'] == 'C')) {
        $message = "User role changed successfully.";
    }
?>
<?php if (array_key_exists('message', $_REQUEST)){ ?>
    <div class="container" style="margin-top: 20px">
        <div class="alert alert-success col-md-12" id="success_message"><?=$message?></div>
    </div>
<?php } ?>
<div style="padding-top:15px;padding-left:15px">
    The data displayed shows the different <?=$hub_name?> projects and their assigned REDCap users.<br>
</div>
<div class="container-fluid p-y-1" style="margin-top:60px">
    <div style="float:left;margin-top: 10px;">
        <input type="checkbox" id="ckb_user" name="chkAll_user" onclick="checkAll('user');" style="cursor: pointer;">
        <span style="cursor: pointer;font-size: 14px;font-weight: normal;color: black;" onclick="checkAllText('user');">Select All</span>
    </div>
    <button type="button" class="btn btn-danger float-right btnClassConfirm" id="remove_user">Remove User</button>
    <button type="button" class="btn btn-warning float-right btnClassConfirm" id="change_user"style="margin-right:10px">Change Role</button>
    <button type="button" class="btn btn-primary float-right btnClassConfirm" id="add_user" style="margin-right:10px">Add User</button>
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
            $gotoredcap = htmlentities(APP_PATH_WEBROOT_ALL . "ProjectSetup/index.php?pid=" . $pidsArray[$constant], ENT_QUOTES);
            $users = HubREDCapUsers::getUserList($module, $pidsArray[$constant]);
            $user_roles = HubREDCapUsers::getAllRoles($module, $pidsArray[$constant]);
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
                                <?php foreach ($users as $user){
                                    $user_data = $user['name']." (<strong>".$user['value']."</strong>)";
                                    $role_name = "";
                                    if($user['role_id'] != null){
                                        $role_name = array_search($user['role_id'], $user_roles);
                                        $user_data .= ", <em>".$role_name."</em>";
                                    }
                                    ?>
                                <tr><td style="padding:8px 30px;" user_pid="<?=$pidsArray[$constant]?>" user_role="<?=$role_name?>" user_name="<?=$user['name'].' (<strong>'.$user['value'].'</strong>)'?>" user_value="<?=$user['value']?>">
                                        <?=$user_data;?>
                                        <a onclick='$("#project_id_remove_user_single").val("<?=$pidsArray[$constant]?>");$("#user_id_remove_user_single").val("<?=$user['value']?>");$("#role_name_remove_user_single").val("<?=$role_name?>");$("#dialogWarningDelete").dialog({modal:true, width:400}).prev(".ui-dialog-titlebar").css("background","#f8d7da").css("color","#721c24");' style="cursor:pointer;padding-left: 5px;"><i class="fa-solid fa-x remove_user"></i></a>
                                        <a onclick='$("#project_id_change_user_single").val("<?=$pidsArray[$constant]?>");$("#user_id_change_user_single").val("<?=$user['value']?>");$("#dialogWarningChange").dialog({modal:true, width:400}).prev(".ui-dialog-titlebar");' style="cursor:pointer;padding-left: 5px;"><i class="fa-solid fa-pencil" style="font-size:12px;"></i></a>
                                    </td></tr>
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
            <div class="alert alert-danger col-md-12" style="display: none" id="alert_text_add_user"></div>
            <?php
            $user_roles_info = "<div>This is more info on user roles:</div>";
            ?>
            <div>Select a user role <a tabindex="0" role="button" class="info-toggle" data-html="true" data-container="body" data-toggle="tooltip" data-trigger="hover" data-placement="right" style="outline: none;" title="<?=$user_roles_info?>"><i class="fas fa-info-circle fa-fw" style="color:#0d6efd" aria-hidden="true"></i></a>:</div>
            <?php echo HubREDCapUsers::getRoleSelector("user_role_add_user");?>
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
            <a onclick="checkData('add_user');" class="btn btn-success" name="btnConfirm">Add User</a>
        </div>
    </form>
</div>
<div id="removeUsersForm" title="Remove Users" style="display:none;">
    <form method="POST" action="" id="remove_user_management">
        <div class="modal-body">
            <div class="alert alert-danger col-md-12" style="display: none" id="alert_text_remove_user"></div>
            <div>Select the users you want to remove from the projects listed below:</div>
            <div id="user_remove_list" style="padding: 10px;"></div>
            <div>You will be removing these users from these projects:</div>
            <div id="projectsSelected_remove_user"></div>
            <input type="hidden" id="checked_values_remove_user" name="checked_values_remove_user">
        </div>
        <div class="modal-footer" style="padding-top: 30px;">
            <a onclick="checkData('remove_user');" class="btn btn-danger" name="btnConfirm">Remove User</a>
        </div>
    </form>
</div>

<div id="changeUsersForm" title="Change User Role" style="display:none;">
    <form method="POST" action="" id="change_user_management">
        <div class="modal-body">
            <div class="alert alert-danger col-md-12" style="display: none" id="alert_text_change_user"></div>
            <div>Select a user role <a tabindex="0" role="button" class="info-toggle" data-html="true" data-container="body" data-toggle="tooltip" data-trigger="hover" data-placement="right" style="outline: none;" title="<?=$user_roles_info?>"><i class="fas fa-info-circle fa-fw" style="color:#0d6efd" aria-hidden="true"></i></a>:</div>
            <?php echo HubREDCapUsers::getRoleSelector("user_role_change_user");?>
            <div id="user_remove_list" style="padding: 10px;"></div>
            <div>You will be changing the roles for these users/projects:</div>
            <div id="projectsSelected_change_user"></div>
            <input type="hidden" id="checked_values_change_user" name="checked_values_change_user">
        </div>
        <div class="modal-footer" style="padding-top: 30px;">
            <a onclick="checkData('change_user');" class="btn btn-primary" name="btnConfirm">Change Role</a>
        </div>
    </form>
</div>
<div id="dialogWarning" title="WARNING!" style="display:none;">
    <p>No projects selected.</p>
</div>
<div id="dialogWarningDelete" title="WARNING!" style="display:none;">
    <form method="POST" action="" id="remove_user_single_management">
        <p>Are you sure you want to remove this user?</p>
        <p>This will remove the user from the project.</p>
        <input type="hidden" id="role_name_remove_user_single" name="role_name_remove_user_single">
        <input type="hidden" id="user_id_remove_user_single" name="user_id_remove_user_single">
        <input type="hidden" id="project_id_remove_user_single" name="project_id_remove_user_single">
        <div class="modal-footer" style="padding-top: 30px;">
            <a onclick="$('#remove_user_single_management').closest('form').submit();" class="btn btn-danger" name="btnConfirm">Remove User</a>
        </div>
    </form>
</div>
<div id="dialogWarningChange" title="Change User Role" style="display:none;">
    <form method="POST" action="" id="change_user_single_management">
        <div class="alert alert-danger col-md-12" style="display: none" id="alert_text_change_user_single"></div>
        <div>Select a user role <a tabindex="0" role="button" class="info-toggle" data-html="true" data-container="body" data-toggle="tooltip" data-trigger="hover" data-placement="right" style="outline: none;" title="<?=$user_roles_info?>"><i class="fas fa-info-circle fa-fw" style="color:#0d6efd" aria-hidden="true"></i></a>:</div>
        <br/>
        <?php echo HubREDCapUsers::getRoleSelector("user_role_change_user_single");?>
        <input type="hidden" id="user_id_change_user_single" name="user_id_change_user_single">
        <input type="hidden" id="project_id_change_user_single" name="project_id_change_user_single">
        <div class="modal-footer" style="padding-top: 30px;">
            <a onclick="checkData('change_user_single');" class="btn btn-primary" name="btnConfirm">Change Role</a>
        </div>
    </form>
</div>
</body>
</html>
