<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
include_once(__DIR__ ."/../projects.php");
include_once(__DIR__ . "/../classes/HubUpdates.php");

$resolved_list = HubUpdates::getResolvedList($module,'resolved');
$hub_updates_resolved_list_last_updated = empty($module->getProjectSetting('hub-updates-resolved-list-last-updated')) ? array() : $module->getProjectSetting('hub-updates-resolved-list-last-updated');
$allUpdates['data'] = HubUpdates::compareDataDictionary($module, $pidsArray, 'resolved');

$oldValues = [];
$updated_resolved_date = false;
foreach ($allUpdates['data']  as $constant => $project_data) {
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
        <link type='text/css' href='<?=$module->getUrl('bootstrap-3.3.7/css/bootstrap.min.css')?>' rel='stylesheet' media='screen' />
        <link type='text/css' href='<?=$module->getUrl('css/styles_updates.css')?>' rel='stylesheet' media='screen' />
        <link type='text/css' href='<?=$module->getUrl('css/jquery.dataTables.min.css')?>' rel='stylesheet' media='screen' />

        <script type="text/javascript" src="<?=$module->getUrl('js/jquery.dataTables.min.js')?>"></script>
        <script type="text/javascript" src="<?=$module->getUrl('js/selectAll.js')?>"></script>
        <script>
            $(document).ready(function () {
                $('#selectDataTableHubUpdates').dataTable({
                    "bPaginate": false,
                    "bLengthChange": false,
                    "bSort": false,
                    "bFilter": true,
                    "bInfo": false,
                    "fnDrawCallback": function(oSettings) {
                        $('#selectAllDiv').insertAfter($('#selectDataTableHubUpdates_wrapper'));
                        $('#legend').insertAfter($('#selectDataTableHubUpdates_filter'));
                        $('#pdf').insertAfter($('#selectDataTableHubUpdates_filter'));
                        $('#selectDataTableHubUpdates_filter').attr("style","padding-right:10px");
                    }
                });

                $('#remove_data, #update_data').submit(function (event) {
                    var formId = $(this).attr('id');
                    var removed_list = [];
                    $('.rowSelected').each(function() {
                        removed_list.push($(this).attr('row'));
                    });
                    if(removed_list != ""){
                        var checked_values = removed_list.join(",");
                        var checked_values_id = "checked_values";
                        if(formId == "update_data"){
                            checked_values_id = "checked_values_dates";
                        }
                        $("#"+checked_values_id).val(checked_values);
                    }else{
                        $("#dialogWarning").dialog({modal:true, width:300}).prev(".ui-dialog-titlebar").css("background","#f8d7da").css("color","#721c24");
                        return false;
                    }
                    return true;
                });
            });

            function selectData(pid){
                var checked = $('#'+pid).is(':checked');
                if (!checked) {
                    $('#' + pid).prop("checked", true);
                    $('[row="' + pid + '"]').addClass('rowSelected');
                    if($('.rowSelected').length >= ($('[name=\'chkAll_resolved\']').length - 1)){
                        $('#ckb_resolved').prop("checked", true);
                    }
                } else {
                    $('#' + pid).prop("checked", false);
                    $('#ckb_resolved').prop("checked", false);
                    $('[row="' + pid + '"]').removeClass('rowSelected');
                }

                //Update Projects Counter
                updateCounterLabel();
            }

            function changeFormUrlPDF(id){
                var url = '<?=$module->getUrl('hub-updates/generate_pdf.php')?>';
                var form_option = "download_PDF_selected";

                var checked_values = [];
                $("input[namecheck='tablefields[]']:checked").each(function() {
                    checked_values.push($(this).val());
                });

                if(checked_values == ""){
                    //Not checked values. Submit ALL
                    $("input[namecheck='tablefields[]']:not(:checked)").each(function() {
                        checked_values.push($(this).val());
                    });
                }
                $('#'+form_option).attr('action',url+"&constant=PDF&checked_values="+checked_values+"&option=resolved&all");
                $("#"+form_option).submit();
                return false;
            }
        </script>
    </head>
    <body>
    <div class="backTo">
        <a href="<?=$module->getUrl('hub-updates/index.php')?>">< Back to Hub Updates</a>
    </div>
    <br><br>
    <?php if(!empty($resolved_list)){ ?>
    <h4 class="title">
        Select the REDCap variables you want to remove from the resolved list and press the button at the end.
    </h4>
    <?php $message = "";
        if (array_key_exists('message', $_REQUEST) && ($_REQUEST['message'] == 'R')) {
            $message = "The variables dates have been successfull been successfully updated.";
        }
    ?>
    <?php if (array_key_exists('message', $_REQUEST)){ ?>
        <div class="container" style="margin-top: 20px">
            <div class="alert alert-success col-md-12" id="success_message"><?=$message?></div>
        </div>
    <?php } ?>
    <br><br>
    <h4 class="title">
        You have selected <span id="pid_total" class="badge dataRequests">0</span> variables
    </h4>
    <div class="container-fluid p-y-1" style="margin-top:40px">
        <div style="float:left;padding-left: 5px;" id="legend">
                <span style="padding-left: 5px"><?=HubUpdates::getIcon(HubUpdates::CHANGED)." <span style='vertical-align: sub'>".ucfirst(HubUpdates::CHANGED)?></span></span>
                <span style="padding-left: 5px"><?=HubUpdates::getIcon(HubUpdates::ADDED)." <span style='vertical-align: sub'>".ucfirst(HubUpdates::ADDED)?></span></span>
                <span style="padding-left: 5px"><?=HubUpdates::getIcon(HubUpdates::REMOVED)." <span style='vertical-align: sub'>".ucfirst(HubUpdates::REMOVED)?></span></span>
        </div>
        <div id="pdf" style="margin-right:15px;float:right;">
            <input type="checkbox" id="ckb_resolved" name="chkAll_resolved" onclick="checkAll('resolved');" style="cursor: pointer;">
            <span style="cursor: pointer;font-size: 14px;font-weight: normal;color: black;" onclick="checkAllText('resolved');">Select All</span>
        </div>
        <div id="selectAllDiv" style="float: right"></div>
        <table id="selectDataTableHubUpdates" style="padding-bottom: 10px;">
            <thead>
            <tr>
                <th></th>
            </tr>
            </thead>
            <tbody>
        <?php
        foreach ($resolved_list as $constant => $variablesData) {
            foreach ($variablesData as $index => $variable) {
                $project_id = (int)$pidsArray[$constant];
                $variable = $module->escape($variable);
                $gotoredcap = htmlentities(APP_PATH_WEBROOT_ALL . "Design/data_dictionary_codebook.php?pid=" . $pidsArray[$constant], ENT_QUOTES);
                $Proj = $module->getProject($pidsArray[$constant]);
                $title = $Proj->getTitle();
                $printProject = "#".$project_id." - ".$title." => <strong>".$variable['field_name']."</strong> (<em>".$variable['field_type']."</em>)";
                $id = $constant."-".$variable['field_name']."-".$variable['field_status']."-".$variable['field_type'];

                #If there's old data without dates, we add them
                if(is_array($hub_updates_resolved_list_last_updated) &&
                    (empty($hub_updates_resolved_list_last_updated) ||
                        ((array_key_exists($constant, $hub_updates_resolved_list_last_updated) && !array_key_exists($variable['field_name'], $hub_updates_resolved_list_last_updated[$constant]))
                            || !array_key_exists($constant, $hub_updates_resolved_list_last_updated)))){
                    $updated_resolved_date = true;
                    $hub_updates_resolved_list_last_updated[$constant][$variable['field_name']]['date'] = date("F d Y H:i:s");
                }
                ?>
            <tr>
                <td style="padding-bottom: 0;padding-top: 0;">
                    <div>
                        <h3 class="panel-title">
                            <table class="table table-striped table-hover resolved-heading" style="margin-bottom:5px; border: 1px solid #dee2e6;font-size: 13px;" data-sortable>
                                <tr row="<?=$id?>" value="<?=$id?>" name="chkAll_parent_resolved">
                                    <td onclick="javascript:selectData('<?= $id; ?>')" style="width: 5%;">
                                        <input value="<?=$id?>" id="<?=$id?>" onclick="selectData('<?= $id; ?>');" class='auto-submit' type="checkbox" name="chkAll_resolved" nameCheck='tablefields[]'>
                                    </td>
                                    <td onclick="javascript:selectData('<?= $id; ?>')">
                                        <?=$printProject;?>
                                    </td>
                                    <td>
                                        <a data-toggle="collapse" href="#collapse<?=$id?>" class="resolved-view-changes">
                                            <strong>View Changes</strong>
                                        </a>
                                        <?php
                                        if(is_array($hub_updates_resolved_list_last_updated)){
                                            $user = "";
                                            if(is_array($hub_updates_resolved_list_last_updated[$constant][$variable['field_name']]) && array_key_exists('user', $hub_updates_resolved_list_last_updated[$constant][$variable['field_name']])){
                                                $user = " by ".$hub_updates_resolved_list_last_updated[$constant][$variable['field_name']]['user'];
                                            }
                                            $resolved_date = "";
                                            if(isset($hub_updates_resolved_list_last_updated[$constant][$variable['field_name']]['date'])){
                                                $resolved_date = "Resolved on ".$hub_updates_resolved_list_last_updated[$constant][$variable['field_name']]['date'];
                                            }
                                            ?>
                                            <span class="hub-update-last-updated">
                                                <?php
                                                echo $resolved_date.$user;
                                                if(isset($hub_updates_resolved_list_last_updated[$constant][$variable['field_name']]['date'])) {
                                                    echo " " . HubUpdates::getTemplateLastUpdatedDate($module, $constant, $hub_updates_resolved_list_last_updated[$constant][$variable['field_name']]['date']);
                                                }
                                                ?>
                                            </span>
                                        <?php } ?>
                                    </td>
                                </tr>
                            </table>
                        </h3>
                    </div>
                    <div id="collapse<?=$id?>" class="table-responsive panel-collapse collapse" aria-expanded="true">
                        <table class="table sortable-theme-bootstrap" data-sortable>
                            <tr class="section-header">
                                <th>Status</th>
                                <th>Variable / Field Name</th>
                                <th>Field Label <br><em>Field Note</em></th>
                                <th>Field Attributes<br>(Field Type, Validation, Choices, Calculations, etc.)</th>
                                <th style="text-align: center;"><span class="fa-regular fa-eye"></span></th>
                            </tr>
                            <?php
                            foreach ($allUpdates['data'][$constant] as $instrument => $instrumentData){
                                if($instrument != "TOTAL"){
                                    $printInstrument = true;
                                    $printInstrumentData = "
                                    <tr>
                                        <td colspan='5' class='instrument-header' style='text-align: left !important;'>*<u>Instrument</u>: <em><strong>".ucwords(str_replace('_', ' ', $instrument))."</em></strong></td>
                                    </tr>
                                    ";
                                    ?>

                                    <?php foreach ($instrumentData as $status => $typeData){
                                        foreach ($typeData as $variableChanges => $data){
                                            if($variableChanges == $variable['field_name']){
                                                if($printInstrument){
                                                    echo $printInstrumentData;
                                                    $printInstrument = false;
                                                }
                                                ?>
                                                <tr>
                                                    <td><?=HubUpdates::getIcon($status)?></td>
                                                    <td><?=HubUpdates::getFieldName($data, $oldValues[$constant][$variableChanges], $status, 'field_name')?></td>
                                                    <td><?php
                                                        if($status == HubUpdates::CHANGED) {
                                                            $col = HubUpdates::getFieldLabel($data, $oldValues[$constant][$variableChanges], $status, 'Section Header:', 'section_header');
                                                            $col .= HubUpdates::getFieldName($data, $oldValues[$constant][$variableChanges], $status, 'field_label');
                                                            $col .= HubUpdates::getFieldLabel($data, $oldValues[$constant][$variableChanges], $status, '', 'field_note');
                                                        }else{
                                                            $col = HubUpdates::getFieldLabel($data, $oldValues[$constant][$variableChanges], $status, '', '');
                                                        }
                                                        print($col);
                                                        ?>
                                                    </td>
                                                    <td class="col-sm-4">
                                                        <?php
                                                        if($status == HubUpdates::CHANGED){
                                                            print(HubUpdates::getFieldAttributesChanged($data,$oldValues[$constant][$variableChanges]));
                                                        }else{
                                                            print(HubUpdates::getFieldAttributes($data));
                                                        }
                                                        ?>
                                                    </td>
                                                    <td style="text-align: center;width: 5%"><a href="<?=$gotoredcap?>" target="_blank"> <img src="<?=$module->getUrl('img/REDCap_R_logo_transparent.png')?>" style="width: 18px;" alt="REDCap Logo"></a></td>
                                                </tr>
                                            <?php }
                                        }
                                    }
                                }
                            } ?>
                        </table>
                    </div>
                </td>
            </tr>
            <?php
            }
        }
        ?>
            </tbody>
        </table>
        <div style="padding-right: 10px;">
            <form method="POST" style="float:right" action="<?=$module->getUrl('hub-updates/last_updates_process_data_AJAX.php').'&option=removed&redcap_csrf_token='.$module->getCSRFToken()?>" id="remove_data">
                <input type="hidden" id="checked_values" name="checked_values">
                <button type="submit" class="btn btn-primary btn-block float-right" id="remove_btn">Remove from Resolved List</button>
            </form>
            <form method="POST" style="float:right;margin-right: 5px;" action="<?=$module->getUrl('hub-updates/last_updates_process_data_AJAX.php').'&option=dates&redcap_csrf_token='.$module->getCSRFToken()?>" id="update_data">
                <input type="hidden" id="checked_values_dates" name="checked_values_dates">
                <button type="submit" class="btn btn-secondary btn-block float-right" id="remove_btn">Update Resolved Date</button>
            </form>
            <form method="POST" style="float:right;margin-right: 5px;" action="" id="download_PDF_selected">
                <a onclick="changeFormUrlPDF(this.id);" id="download_PDF_sel" class="btn btn-secondary">
                    Download PDF
                </a>
            </form>
        </div>
    </div>
    <div id="dialogWarning" title="WARNING!" style="display:none;">
        <p>No fields selected.</p>
    </div>
    <?php } else {?>
    <h4  class="title">
        You have no variables in the resolved list.
    </h4>
    <?php } ?>
    </body>
    </html>
    <?php include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';?>