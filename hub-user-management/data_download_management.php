<?php
namespace Vanderbilt\HarmonistHubExternalModule;

$hub_mapper = $module->getProjectSetting('hub-mapper');
$pidsArray = REDCapManagement::getPIDsArray($hub_mapper);
$hubDataDownloadsUsers = new HubDataDownloadsUsers($module, $pidsArray);
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

        });

    </script>
</head>
<body>
<?php if (!empty($hubDataDownloadsUsers->getErrorUserList())){ ?>
    <div class="container" style="margin-top: 10px">
        <div class="alert alert-warning col-md-12">
            <div style="float: left;">There are users that need to be reviewed due to permission issues.</div>
            <form method="POST" action="<?=$module->getUrl('hub-updates/update_surveys_AJAX.php') . '&redcap_csrf_token=' . $module->getCSRFToken()?>" class="" id="resolved_list">
                <div class="float-right"><button type="submit" name="option" value="update" class="btn btn-warning" style="display: block;margin-right: 10px;">Manage Users</button></div>
            </form>
        </div>
    </div>
<?php } ?>
<div style="padding-top:15px;padding-left:15px">
    Here you will find a list of users that have the correct permissions to use Data Downloads.<br>
</div>

<div class="container-fluid p-y-1"  style="margin-top:40px">
    <table id="selectDataTable" class="table table-striped table-hover" style="border: 1px solid #dee2e6;" data-sortable>
        <tbody>
        <?php
        $count = 0;
//        print_array($hubDataDownloadsUsers->getErrorUserList());
        foreach ($hubDataDownloadsUsers->getSuccessUserList() as $index => $user) {
            $count++;
            $admin = "";
            if($user['harmonistadmin_y'] == "1"){
                $admin = '<span class="label label-approved">Admin</span>';
            }
            ?>
            <tr onclick="javascript:selectData('<?= $index; ?>')" row="<?=$index?>" multipleSel="<?=$count?>" value="<?=$index?>" name="chkAllTR" >
                <td width="5%" multipleSel="<?=$count?>">
                    <input value="<?=$index?>" id="<?=$index?>" multipleSel="<?=$count?>" onclick="selectData('<?= $index; ?>');" class='auto-submit' type="checkbox" name='chkAll' name='tablefields[]'>
                </td>
                <td multipleSel="<?=$count?>"><?=$user['firstname']." ".$user['lastname'];?> <?=$user['region_code'];?> <?=$admin;?></td>
            </tr>
            <?php
        }
        ?>
        </tbody>
    </table>
    <form method="POST" action="<?=$module->getUrl('index.php').'&redcap_csrf_token='.$module->getCSRFToken()?>" id="copy_data" style="padding-top: 20px;">
        <input type="hidden" id="pid_list" name="pid_list">
        <button type="submit" class="btn btn-primary btn-block float-right" id="copy_btn">Select Projects</button>
    </form>
</div>
</body>
</html>
