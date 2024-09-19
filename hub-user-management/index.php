<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include_once(__DIR__ ."/../projects.php");
include_once(__DIR__ ."/../classes/HubREDCapUsers.php");

$projects_array = REDCapManagement::getProjectsConstantsArray();
$projects_titles_array = REDCapManagement::getProjectsTitlesArray();
$hub_name = $setting['hub_name']." Hub";
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
            $('#user_list').submit(function (event) {
                console.log("user_list")
                let checked_values = [];
                $("input[nameCheck='tablefields[]']:checked").each(function() {
                    checked_values.push($(this).val());
                });

                if(checked_values.length != 0) {
                    $('#checked_values').val(checked_values);
                    let redcap_csrf_token = <?=json_encode($module->getCSRFToken())?>;
                    let projects_titles_array = <?=json_encode($projects_titles_array)?>;
                    let hub_name = <?=json_encode($hub_name)?>;
                    let option = $('#option').val();

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
                    $('#projectsSelected').html(project_info);
                    $("#addUsersForm").dialog({
                        width: 700,
                        modal: true,
                        enableRemoteModule: true
                    });
                }else{
                    $("#dialogWarning").dialog({modal:true, width:300}).prev(".ui-dialog-titlebar").css("background","#f8d7da").css("color","#721c24");
                }
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
            let user_list = $('#user_list_textarea').val()
            if(user_list.length == 0){
                $('#alert_text').show();
            }
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
        <button type="submit" onclick="$('#option').val('remove');" class="btn btn-danger float-right btnClassConfirm" id="removed_btn" name="resolved_btn">Remove User</button>
        <button type="submit" onclick="$('#option').val('add');" class="btn btn-primary float-right btnClassConfirm" id="add_btn" name="save_btn" style="margin-right:10px">Add User</button>
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
                                            <input value="<?=$pidsArray[$constant]?>" id="<?=$pidsArray[$constant]?>" pid="<?=$id?>" class='auto-submit' type="checkbox" name="chkAll_user" nameCheck='tablefields[]'>
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
                                <tr><td style="padding-left: 30px;"><?=$user['name']." (".$user['value'].")";?> <a href=""><i class="fa-solid fa-x remove_user"></i></a></td></tr>
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
    <form method="POST" action="<?=json_encode($module->getUrl('hub-user-management/user_management_AJAX.php'))?>">
        <div class="modal-body">
            <div class="alert alert-success col-md-12" style="display: none" id="alert_text">Select at least one user to add to the projects.</div>
            <div>Add user names separated by commas or select them from the selector.</div>
            <div>Only users added on the <strong><?=$hub_name?>:Parent Project (MAP)</strong> can be added to other projects.</div>
            <br/>
            <select class="form-select" onchange="addUserName(this.value)">
                <option value=""></option>
            <?php
            $choices = HubREDCapUsers::getUserList($module, $pidsArray['PROJECTS']);
            foreach ($choices as $user){
                echo "<option value='".$user['value']."'>".$user['name']." (".$user['value'].")"."</option>";
            }
            ?>
            </select>
            <textarea id="user_list_textarea" class="user_list_textarea"></textarea>
            <br/>
            <br/>
            <div>You will be adding users to these projects:</div>
            <div id="projectsSelected"></div>
            <input type="hidden" id="checked_values" name="checked_values">
        </div>
        <div class="modal-footer" style="padding-top: 30px;">
            <a onclick="checkData();" class="btn btn-success" id='btnConfirm' name="btnConfirm">Continue</a>
        </div>
    </form>
</div>
<div id="dialogWarning" title="WARNING!" style="display:none;">
    <p>No projects selected.</p>
</div>
</body>
</html>
