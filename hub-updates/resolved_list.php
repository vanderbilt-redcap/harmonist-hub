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
                $('#copy_data').submit(function (event) {
                    var pid_array = [];
                    $('.rowSelected').each(function() {
                        pid_array.push($(this).attr('row'));
                    });
                    var pid_list = pid_array.join(",");
                    $("#pid_list").val(pid_list);
                    return true;
                });
            });
        </script>
    </head>
    <body>
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
                    ?>
                    <tr onclick="javascript:selectData('<?= $id; ?>')" row="<?=$id?>" value="<?=$id?>">
                        <td>
                            <input value="<?=$id?>" id="<?=$id?>" onclick="selectData('<?= $id; ?>');" class='auto-submit' type="checkbox" name='tablefields[]'>
                        </td>
                        <td><?=$printProject;?></td>
                    </tr>
                <?php
                }
            }
            ?>
        </table>
        <form method="POST" action="<?=$module->getUrl('index.php').'&redcap_csrf_token='.$module->getCSRFToken()?>" id="copy_data">
            <input type="hidden" id="pid_list" name="pid_list">
            <button type="submit" class="btn btn-primary btn-block float-right" id="copy_btn">Remove from Resolved</button>
        </form>
    </div>
    </body>
    </html>
    <?php include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';?>