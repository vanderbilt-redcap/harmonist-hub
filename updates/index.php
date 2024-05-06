<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include_once(__DIR__ ."/../projects.php");
include_once(__DIR__ . "/../classes/HubUpdates.php");

$allUpdates = $module->getProjectSetting('hub-updates')['data'];

$printData = [];
$oldValues = [];
foreach ($allUpdates as $constant => $project_data) {
    $oldValues[$constant] = \REDCap::getDataDictionary($pidsArray[$constant], 'array', false);

    $Proj = $module->getProject($pidsArray[$constant]);
    $gotoredcap = htmlentities(APP_PATH_WEBROOT_ALL . "Design/data_dictionary_codebook.php?pid=" . $pidsArray[$constant], ENT_QUOTES);
    $printData[$constant]['title'] = $Proj->getTitle();
    $printData[$constant]['gotoredcap'] = $gotoredcap;
    $printData[$constant]['pid'] = $pidsArray[$constant];
}
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
        <link type='text/css' href='<?=$module->getUrl('css/styles_updates.css')?>' rel='stylesheet' media='screen' />
        <script type="text/javascript" src="<?=$module->getUrl('js/functions.js')?>"></script>
        <link type='text/css' href='<?=$module->getUrl('css/sortable-theme-bootstrap.css')?>' rel='stylesheet' media='screen' />
        <link type='text/css' href='<?=$module->getUrl('bootstrap-3.3.7/css/bootstrap.min.css')?>' rel='stylesheet' media='screen' />
        <link type='text/css' href='<?=$module->getUrl('css/style.css')?>' rel='stylesheet' media='screen' />
        <link type='text/css' href='<?=$module->getUrl('css/tabs-steps-menu.css')?>' rel='stylesheet' media='screen' />
        <script>
            $(document).ready(function () {
                var printData = <?=json_encode($printData)?>;
                var sButton = null;
                var $form = $('#save_data');
                var $submitButtons = $form.find('.btnClassConfirm');

                $submitButtons.click(function(event) {
                    sButton = this;
                });

                $('#save_data').submit(function (event) {
                    var fields_total = 0;
                    var title = "";
                    var option = "";
                    var dialog_background_color = "";
                    var dialog_color = "";

                    var checked_values = [];
                    $("input[name='tablefields[]']:checked").each(function() {
                        checked_values.push($(this).val());
                    });

                    if (null === sButton) {
                        sButton = $submitButtons[0];
                    }

                    if(checked_values.length != 0) {
                            var display_data = "<div>";
                            Object.keys(checked_values).forEach(function (section) {
                                var data = checked_values[section].split('-');
                                var constant_name = data[0];
                                var variable_name = data[1];
                                var status = data[2];
                                var field_type = data[3];

                                display_data += "<div>";
                                if(sButton.name == "save_btn"){
                                    display_data += getIcon(status)+" <div style='display: inline;vertical-align: sub;'>";
                                }
                                display_data += printData[constant_name]['pid'] + " - " + printData[constant_name]['title'] + " => <strong>" + variable_name + "</strong> <em>(" + field_type + ")</em></div>";
                                if(sButton.name == "save_btn"){
                                    display_data += "</div>";
                                }
                                fields_total += 1;

                            });
                            display_data += "</div>";

                        if(sButton.name == "save_btn") {
                            title = "Are you sure you want to import <strong>"+fields_total+"</strong> fields?";
                            dialog_background_color = "#d4edda";
                            dialog_color = "#155724";
                            option = "save";
                        }else if(sButton.name == "resolved_btn") {
                            title = "Are you sure you want to mark as resolved <strong>"+fields_total+"</strong> fields?<br>";
                            title += "<em>*These fileds will not show up again on Hub Updates unless they are removed from the resolved list.</em>";
                            dialog_background_color = "#fff3cd";
                            dialog_color = "#856404";
                            option = "resolved";
                        }
                        $('#fields_total').html(title);
                        $('#import_confirmation').html(display_data);
                        $('#option').val(option);

                        $("#confirmationForm").dialog({
                            width: 700,
                            modal: true,
                            enableRemoteModule: true
                        }).prev(".ui-dialog-titlebar").css("background", dialog_background_color).css("color", dialog_color);

                    }else{
                        $("#dialogWarning").dialog({modal:true, width:300}).prev(".ui-dialog-titlebar").css("background","#f8d7da").css("color","#721c24");
                    }
                    return false;
                });

                $('#data_confirmation').submit(function (event) {
                    var redcap_csrf_token = <?=json_encode($module->getCSRFToken())?>;
                    var option = $('#option').val();
                    var checked_values = [];
                    $("input[name='tablefields[]']:checked").each(function() {
                        checked_values.push($(this).val());
                    });
                    url = <?=json_encode($module->getUrl('updates/last_updates_process_data_AJAX.php'))?>;
                    console.log(url+"&checked_values="+checked_values+"&option="+option+"&redcap_csrf_token="+redcap_csrf_token);
                    $("#confirmationForm").dialog("close");

                    // $.ajax({
                    //    type: "POST",
                    //    url: url,
                    //    data: "&checked_values="+checked_values+"&option="+option+"&redcap_csrf_token="+redcap_csrf_token,
                    //    error: function (xhr, status, error) {
                    //        alert(xhr.responseText);
                    //    },
                    //    success: function (result) {
                    //        var status = jQuery.parseJSON(result)['status'];
                    //        location.reload();
                    //    }
                    // });
                    return false;
                });
            });
        </script>
    </head>
    <body>
        <h4 class="title">
            You have <strong><?=count($allUpdates)?></strong> projects to update.
        </h4>
        <div class="container-fluid p-y-1" style="margin-top:60px">
            <form method="POST" action="" id="save_data">
                <button type="submit" class="btn btn-primary float-right btnClassConfirm" id="save_btn" name="save_btn">Save Changes</button>
                <button type="submit" class="btn btn-resolved float-right btnClassConfirm" id="resolved_btn" name="resolved_btn" style="margin-right:10px">Mark as Resolved</button>
            </form>
        </div>
        <div class="container-fluid p-y-1">
            <?php foreach ($allUpdates as $constant => $project_data){ ?>
            <div style="padding-top: 5px;">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <span class="badge label-default"><?=$allUpdates[$constant]['TOTAL']["total"]?></span>
                        <a data-toggle="collapse" href="#collapse<?=$constant?>" id="<?='table_'.$constant?>" class="label label-as-badge-square ">
                            <strong><?php echo "<span class='table_name'>".$printData[$constant]['title']."</span>"; ?></strong>
                        </a>
                        <span class="badge dataRequests" id="counter_<?=$constant;?>"></span>
                        <span style="padding-left:10px">
                            <input type="checkbox" id="ckb_<?= $constant; ?>" name="<?= "chkAll_" . $constant ?>" onclick="checkAll('<?= $constant ?>');" style="cursor: pointer;">
                            <span style="cursor: pointer;font-size: 14px;font-weight: normal;color: black;" onclick="checkAllText('<?= $constant ?>');">Select All</span>
                        </span>
                        <a href="<?=$printData[$constant]['gotoredcap']?>"target="_blank" style="float: right;padding-right: 15px;color: #337ab7;font-weight: bold;margin-top: 5px;">Go to REDCap</a>
                    </h3>
                </div>
                <div id="collapse<?=$constant?>" class="table-responsive panel-collapse collapse" aria-expanded="true">
                    <table class="table sortable-theme-bootstrap" data-sortable>
                        <tr>
                            <td colspan='5' style="text-align: left !important;">
                                <span style="padding-left: 5px"><?=HubUpdates::getIcon(HubUpdates::CHANGED)." <span style='vertical-align: sub'>".ucfirst(HubUpdates::CHANGED)." (".($allUpdates[$constant]['TOTAL'][HubUpdates::CHANGED] ?? 0).")"?></span></span>
                                <span style="padding-left: 5px"><?=HubUpdates::getIcon(HubUpdates::ADDED)." <span style='vertical-align: sub'>".ucfirst(HubUpdates::ADDED)." (".($allUpdates[$constant]['TOTAL'][HubUpdates::ADDED] ?? 0).") "?></span></span>
                                <span style="padding-left: 5px"><?=HubUpdates::getIcon(HubUpdates::REMOVED)." <span style='vertical-align: sub'>".ucfirst(HubUpdates::REMOVED)." (".($allUpdates[$constant]['TOTAL'][HubUpdates::REMOVED] ?? 0).")"?></span></span>
                            </td>
                        </tr>
                        <tr class="section-header">
                            <th>Select</th>
                            <th>Status</th>
                            <th>Variable / Field Name</th>
                            <th>Field Label <br><em>Field Note</em></th>
                            <th>Field Attributes<br>(Field Type, Validation, Choices, Calculations, etc.)</th>
                        </tr>
                        <?php
                        foreach ($project_data as $instrument => $instrumentData){
                            if($instrument != "TOTAL"){
                        ?>
                            <tr>
                                <td colspan='5' class='instrument-header' style="text-align: left !important;">*<u>Instrument</u>: <em><strong><?=ucwords(str_replace('_', ' ', $instrument))?></em></strong></td>
                            </tr>
                            <?php foreach ($instrumentData as $status => $typeData){
                                    foreach ($typeData as $variable => $data){
                                        ?>
                                        <tr  onclick="checkselect('<?= $constant."-".$variable; ?>');" parent_table='<?= $constant; ?>' row="<?=$constant."-".$variable?>">
                                            <td>
                                                <input value="<?=$constant."-".$variable."-".$status."-".$data['field_type']?>" id="<?=$constant."-".$variable?>" onclick="checkselect('<?= $constant."-".$variable; ?>');" class='auto-submit' type="checkbox" chk_name='chk_table_<?=$constant;?>' name='tablefields[]'>
                                            </td>
                                            <td><?=HubUpdates::getIcon($status)?></td>
                                            <td><?=HubUpdates::getFieldName($data, $oldValues[$constant][$variable], $status, 'field_name')?></td>
                                            <td><?php
                                                if($status == HubUpdates::CHANGED) {
                                                    $col = HubUpdates::getFieldLabel($data, $oldValues[$constant][$variable], $status, 'Section Header :', 'section_header');
                                                    $col .= HubUpdates::getFieldName($data, $oldValues[$constant][$variable], $status, 'field_label');
                                                    $col .= HubUpdates::getFieldLabel($data, $oldValues[$constant][$variable], $status, '', 'field_note');
                                                }else{
                                                    $col = HubUpdates::getFieldLabel($data, $oldValues[$constant][$variable], $status, '', '');
                                                }
                                                print($col);
                                                ?>
                                            </td>
                                            <td class="col-sm-4">
                                                <?php
                                                if($status == HubUpdates::CHANGED){
                                                    print(HubUpdates::getFieldAttributesChanged($data,$oldValues[$constant][$variable]));
                                                }else{
                                                    print(HubUpdates::getFieldAttributes($data));
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                <?php }
                                }
                            }
                        } ?>
                    </table>
                </div>

            <?php } ?>
            </div>
        </div>
        <!-- MODAL -->
        <div id="confirmationForm" title="Confirmation" style="display:none;">
            <form method="POST" action="" id="data_confirmation">
                <div class="modal-body">
                    <span id="fields_total"></span>
                    <br>
                    <br>
                    <div id="import_confirmation"></div>
                    <input type="hidden" id="option" name="option">
                </div>
                <div class="modal-footer" style="padding-top: 30px;">
                    <button type="submit" style="color:white;" class="btn btn-default btn-success" id='btnConfirm'>Continue</button>
                </div>
            </form>
        </div>
        <div id="dialogWarning" title="WARNING!" style="display:none;">
            <p>No fields selected.</p>
        </div>
    </body>
</html>
