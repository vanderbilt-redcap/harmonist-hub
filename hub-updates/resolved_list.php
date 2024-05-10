<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
include_once(__DIR__ ."/../projects.php");
include_once(__DIR__ . "/../classes/HubUpdates.php");

$resolved_list = HubUpdates::getResolvedList($module,'resolved');
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
        <link type='text/css' href='<?=$module->getUrl('css/style.css')?>' rel='stylesheet' media='screen' />
        <script>
            function selectData(pid){
                var checked = $('#'+pid).is(':checked');
                if (!checked) {
                    $('#' + pid).prop("checked", true);
                    $('[row="' + pid + '"]').addClass('rowSelected');
                } else {
                    $('#' + pid).prop("checked", false);
                    $('[row="' + pid + '"]').removeClass('rowSelected');
                }

                //Update Projects Counter
                var count = $('.rowSelected').length;
                if(count>0){
                    $("#pid_total").text(count);
                }else{
                    $("#pid_total").text("0");
                }
            }

            $(document).ready(function () {
                $('#remove_data').submit(function (event) {
                    var removed_list = [];
                    $('.rowSelected').each(function() {
                        removed_list.push($(this).attr('row'));
                    });
                    if(removed_list != ""){
                        var checked_values = removed_list.join(",");
                        $("#checked_values").val(checked_values);
                    }else{
                        $("#dialogWarning").dialog({modal:true, width:300}).prev(".ui-dialog-titlebar").css("background","#f8d7da").css("color","#721c24");
                    }
                    return true;
                });
            });
        </script>
    </head>
    <body>
    <?php if(!empty($resolved_list)){ ?>
    <h6 class="container">
        Select the REDCap variables you want to remove from the resolved list and press the button at the end.
    </h6>
    <br><br>
    <h6 class="container">
        You have selected <span id="pid_total" class="badge dataRequests">0</span> variables
    </h6>
    <div class="container-fluid p-y-1" style="margin-top:40px">
        <table class="table table-striped table-hover" style="border: 1px solid #dee2e6;" data-sortable>
            <?php
            foreach ($resolved_list as $constant => $variablesData) {
                foreach ($variablesData as $index => $variable) {
                    $project_id = (int)$pidsArray[$constant];
                    $variable = $module->escape($variable);
                    $Proj = $module->getProject($pidsArray[$constant]);
                    $gotoredcap = htmlentities(APP_PATH_WEBROOT_ALL . "Design/data_dictionary_codebook.php?pid=" . $pidsArray[$constant], ENT_QUOTES);
                    $title = $Proj->getTitle();
                    $printProject = "#".$project_id." - ".$title." => <strong>".$variable['field_name']."</strong> (<em>".$variable['field_type']."</em>)";
                    $id = $constant."-".$variable['field_name']."-".$variable['field_status']."-".$variable['field_type'];
                    #TODO: modal iframe window with that displays changes.
                    ?>
                    <tr onclick="javascript:selectData('<?= $id; ?>')" row="<?=$id?>" value="<?=$id?>">
                        <td>
                            <input value="<?=$id?>" id="<?=$id?>" onclick="selectData('<?= $id; ?>');" class='auto-submit' type="checkbox" name='tablefields[]'>
                        </td>
                        <td><?=$printProject;?></td>
                        <td><a href="<?=$gotoredcap?>"target="_blank" style="float: right;padding-right: 15px;color: #337ab7;font-weight: bold;margin-top: 5px;">Go to REDCap</a></td>
                    </tr>
                <?php
                }
            }
            ?>
        </table>
        <form method="POST" action="<?=$module->getUrl('hub-updates/last_updates_process_data_AJAX.php').'&option=removed&redcap_csrf_token='.$module->getCSRFToken()?>" id="remove_data">
            <input type="hidden" id="checked_values" name="checked_values">
            <button type="submit" class="btn btn-primary btn-block float-right" id="remove_btn">Remove from Resolved List</button>
        </form>
    </div>
    <div id="dialogWarning" title="WARNING!" style="display:none;">
        <p>No fields selected.</p>
    </div>
    <?php } else {?>
    <h6 class="container">
        You have no variables in the resolved list.
    </h6>
    <?php } ?>
    </body>
    </html>
    <?php include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';?>