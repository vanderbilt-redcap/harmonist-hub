<?php

namespace Vanderbilt\HarmonistHubExternalModule;
use REDCap;

include_once(__DIR__ . "/../projects.php");
include_once(__DIR__ . "/../classes/HubUpdates.php");
include_once(__DIR__ . "/../classes/Messages.php");

$allUpdates = $module->getProjectSetting('hub-updates')['data'];
#Sanitize text title and descrition for pages
$hub_name = REDCap::getData($pidsArray['SETTINGS'], 'json-array', ('hub_name'))[0];

$printDataAll = HubUpdates::getPrintData($module, $pidsArray, $allUpdates);
$printData = $printDataAll[0];
$oldValues = $printDataAll[1];
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
    <script type="text/javascript" src="<?= $module->getUrl('js/functions.js') ?>"></script>
    <link type='text/css' href='<?= $module->getUrl('css/sortable-theme-bootstrap.css') ?>' rel='stylesheet'
          media='screen'/>
    <link type='text/css' href='<?= $module->getUrl('bootstrap-3.3.7/css/bootstrap.min.css') ?>' rel='stylesheet'
          media='screen'/>
    <link type='text/css' href='<?= $module->getUrl('css/style.css') ?>' rel='stylesheet' media='screen'/>
    <link type='text/css' href='<?= $module->getUrl('css/tabs-steps-menu.css') ?>' rel='stylesheet' media='screen'/>
    <link type='text/css' href='<?= $module->getUrl('css/styles_updates.css') ?>' rel='stylesheet' media='screen'/>
    <script>
        $(document).ready(function () {
            var printData = <?=json_encode($printData)?>;
            var sButton = null;
            var $form = $('#save_data');
            var $submitButtons = $form.find('.btnClassConfirm');

            $submitButtons.click(function (event) {
                sButton = this;
            });

            $('#save_data').submit(function (event) {
                var fields_total = 0;
                var title = "";
                var option = "";
                var dialog_background_color = "";
                var dialog_color = "";

                $('#update_text').hide();

                var checked_values = [];
                $("input[name='tablefields[]']:checked").each(function () {
                    checked_values.push($(this).val());
                });

                if (null === sButton) {
                    sButton = $submitButtons[0];
                }

                if (checked_values.length != 0) {
                    var display_data = "<div>";
                    Object.keys(checked_values).forEach(function (section) {
                        var data = checked_values[section].split('-');
                        var constant_name = data[0];
                        var variable_name = data[1];
                        var status = data[2];
                        var field_type = data[3];

                        display_data += "<div>";
                        display_data += getIcon(status) + " <div style='display: inline;vertical-align: sub;'>";
                        display_data += printData[constant_name]['pid'] + " - " + printData[constant_name]['title'] + " => <strong>" + variable_name + "</strong> (<em>" + field_type + "</em>)</div>";
                        display_data += "</div>";

                        fields_total += 1;

                    });
                    display_data += "</div>";

                    if (sButton.name == "save_btn") {
                        title = "Are you sure you want to import <strong>" + fields_total + "</strong> fields?";
                        dialog_background_color = "#d4edda";
                        dialog_color = "#155724";
                        option = "save";
                    } else if (sButton.name == "resolved_btn") {
                        title = "Are you sure you want to mark as resolved <strong>" + fields_total + "</strong> fields?<br>";
                        title += "<em>*These fields will not show up again on Hub Updates unless they are removed from the resolved list.</em>";
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

                } else {
                    $("#dialogWarning").dialog({
                        modal: true,
                        width: 300
                    }).prev(".ui-dialog-titlebar").css("background", "#f8d7da").css("color", "#721c24");
                }
                return false;
            });

            $('#data_confirmation').submit(function (event) {
                var redcap_csrf_token = <?=json_encode($module->getCSRFToken())?>;
                var option = $('#option').val();
                var success_message = "";
                var url = <?=json_encode($module->getUrl('hub-updates/last_updates_process_data_AJAX.php'))?>;

                var checked_values = [];
                if (option != "update") {
                    $("input[name='tablefields[]']:checked").each(function () {
                        checked_values.push($(this).val());
                    });
                    $("#confirmationForm").dialog("close");

                    if (option == 'save') {
                        success_message = "S";
                    } else {
                        success_message = "R";
                    }
                } else {
                    success_message = "U";
                }

                $.ajax({
                    type: "POST",
                    url: url,
                    data: "&checked_values=" + checked_values + "&option=" + option + "&redcap_csrf_token=" + redcap_csrf_token,
                    error: function (xhr, status, error) {
                        alert(xhr.responseText);
                    },
                    success: function (result) {
                        var status = jQuery.parseJSON(result)['status'];
                        window.location = getMessageLetterUrl(window.location.href, success_message);
                    }
                });
                return false;
            });
        });

        function changeFormUrlPDF(id) {
            $('update_text').text('');
            $('#update_text').hide();

            if (id == "btnDownloadPDF" || id == "btnUploadPDF") {
                var url = <?=json_encode($module->getUrl('hub-updates/generate_pdf.php'))?>;
                var option = $('#option').val();

                var checked_values = [];
                $("input[name='tablefields[]']:checked").each(function () {
                    checked_values.push($(this).val());
                });

                if (id == "btnUploadPDF") {
                    var redcap_csrf_token = <?=json_encode($module->getCSRFToken())?>;
                    var filerepo = "true";
                    $('#data_confirmation').attr('action', '');
                    $.ajax({
                        type: "POST",
                        url: url,
                        data: "&constant=PDF&checked_values=" + checked_values + "&option=" + option + "&filerepo=" + filerepo + "&redcap_csrf_token=" + redcap_csrf_token,
                        error: function (xhr, status, error) {
                            alert(xhr.responseText);
                        },
                        success: function (result) {
                            $('#update_text').show();
                        }
                    });
                } else {
                    $('#data_confirmation').attr('action', url + "&constant=PDF&checked_values=" + checked_values + "&option=" + option + "&redcap_csrf_token=" + redcap_csrf_token);
                }
            } else {
                $('#data_confirmation').attr('action', '');
                $('#data_confirmation').submit();
            }
        }
    </script>
</head>
<body>
<?php
if (!ProjectData::checkIfThemeExists($module, $pidsArray) && ($module->getProjectSetting(
            'hub-updates-show-theme-msg'
        ) === "true" || $module->getProjectSetting('hub-updates-show-theme-msg') === null)) {
    echo '<div class="container" style="margin-top: 10px">
                <div class="alert alert-warning col-md-12">
                <div>The survey theme <strong>' . ProjectData::HUB_SURVEY_THEME_NAME . '</strong> does not exist in some of your Hub surveys.</div>
                <div>Create and Install will add this theme in all the project/s surverys.</div>
                <form method="POST" action="' . $module->getUrl(
            'hub-updates/save_theme_AJAX.php'
        ) . '&redcap_csrf_token=' . $module->getCSRFToken() . '" class="" id="resolved_list">
                    <div class="float-right"><button type="submit" name="option" value="dismiss" class="btn btn-danger" style="display: block;" onclick="select_btn">Dismiss Message</button></div>
                    <div class="float-right"><button type="submit" name="option" value="create" class="btn btn-success" style="display: block;margin-right: 10px;" id="select_btn">Create & Install Theme</button></div>
                </form>
                </div>
            </div>';
}
if (!ProjectData::checkIfSurveysAreActivated($module, $pidsArray)) {
    echo '<div class="container" style="margin-top: 10px">
                <div class="alert alert-warning col-md-12">
                <div>There are surveys not activated on their respective projects.</div>
                <form method="POST" action="' . $module->getUrl(
            'hub-updates/update_surveys_AJAX.php'
        ) . '&redcap_csrf_token=' . $module->getCSRFToken() . '" class="" id="resolved_list">
                    <div class="float-right"><button type="submit" name="option" value="update" class="btn btn-success" style="display: block;margin-right: 10px;">Activate Surveys</button></div>
                </form>
                </div>
            </div>';
}
if (!ProjectData::checkIfModuleIsEnabledOnProjects($module, $pidsArray, (int)$_GET['pid'])) {
    echo '<div class="container" style="margin-top: 10px">
                <div class="alert alert-warning col-md-12">
                <div>There are some projects that have the module not enabled or are missing a functionality setting.</div>
                <form method="POST" action="' . $module->getUrl(
            'hub-updates/enable_module_and_settings_AJAX.php'
        ) . '&redcap_csrf_token=' . $module->getCSRFToken() . '" class="" id="resolved_list">
                    <div class="float-right"><button type="submit" name="option" value="update" class="btn btn-success" style="display: block;margin-right: 10px;">Enable</button></div>
                </form>
                </div>
            </div>';
}
if (!empty($allUpdates)) {
    if (array_key_exists('message', $_REQUEST) && !empty(Messages::getHubUpdatesMessage($_REQUEST['message']))) { ?>
        <div class="container" style="margin-top: 20px">
            <div class="alert alert-success col-md-12" id="success_message"><?= Messages::getHubUpdatesMessage(
                    $_REQUEST['message']
                ) ?></div>
        </div>
        <?php
    } ?>
    <div class="title">
        You have <strong><?= count($allUpdates) ?></strong> projects to update.
    </div>
<?php
} else { ?>
    <h4 class="title">
        There are currently no projects that need to be updated.
    </h4>
<?php
} ?>
<div class="title" style="padding-top:15px">
    The data displayed shows the projects from your <strong><?= $settings['hub_name'] ?> Hub</strong> that have
    different values when compared against the administrator's version.<br>
    <form method="POST" action="" class="" id="update_list" name="update_list">
        The data only uploads once a day. To recalculate any new changes you do without using this tool <a href="#"
                                                                                                           onclick="$('#option').val('update');$('#data_confirmation').submit();"
                                                                                                           id="update_btn"
                                                                                                           name="update_btn"
                                                                                                           style="text-decoration: underline;">click
            here</a>
    </form>
</div>
<?php
if (!empty($allUpdates)) { ?>
    <div class="title" style="padding-top:15px">
        <form method="POST" action="<?= $module->getUrl(
            'hub-updates/resolved_list.php'
        ) . '&redcap_csrf_token=' . $module->getCSRFToken() ?>" class="" id="resolved_list">
            If you do not want to make changes to certain variables
            <mark style="background-color: #fffbce">mark them as resolved</mark>
            and the updates will omit them from now onwards.
            <br><br>
            <div style="float: left;padding-right: 5px;">You can always add them back by clicking here:</div>
            <button type="submit" class="btn btn-resolved" style="display: block;" id="select_btn">See Resolved List
            </button>
        </form>
    </div>
    <div class="container-fluid p-y-1" style="margin-top:60px">
        <form method="POST" action="<?= $module->getUrl('hub-updates/generate_pdf.php') . '&constant=ALL' ?>"
              id="download_pdf_all" class="download-pdf-all">
            <a onclick="this.closest('form').submit();return false;">
                <i class="fa fa-arrow-down"></i> <i class="fa fa-solid fa-file-pdf"></i> Download All
            </a>
        </form>
        <form method="POST" action="" id="save_data">
            <button type="submit" onclick="$('#option').val('save');"
                    class="btn btn-primary float-right btnClassConfirm" id="save_btn" name="save_btn">Save Changes
            </button>
            <button type="submit" onclick="$('#option').val('resolved');"
                    class="btn btn-warning float-right btnClassConfirm" id="resolved_btn" name="resolved_btn"
                    style="margin-right:10px">Mark as Resolved
            </button>
        </form>
    </div>
    <div class="container-fluid p-y-1">
        <?php
        foreach ($allUpdates

        as $constant => $project_data){ ?>
        <div style="padding-top: 5px;">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <span class="badge label-default"><?= $allUpdates[$constant]['TOTAL']["total"] ?></span>
                    <a data-toggle="collapse" href="#collapse<?= $constant ?>" id="<?= 'table_' . $constant ?>"
                       class="label label-as-badge-square ">
                        <strong><?php
                            echo "<span class='table_name'>" . $printData[$constant]['title'] . "</span>"; ?></strong>
                    </a>
                    <form method="POST"
                          action="<?= $module->getUrl('hub-updates/generate_pdf.php') . '&constant=' . $constant ?>"
                          id="download_pdf" class="badge label-primary"
                          style="cursor:pointer;font-size: 12px;padding:.2em .6em .2em;">
                        <a onclick="this.closest('form').submit();return false;">
                            <i class="fa fa-arrow-down"></i> <i class="fa fa-solid fa-file-pdf"></i>
                        </a>
                    </form>
                    <span class="badge dataRequests" id="counter_<?= $constant; ?>"></span>
                    <span style="padding-left:10px">
                            <input type="checkbox" id="ckb_<?= $constant; ?>" name="<?= "chkAll_" . $constant ?>"
                                   onclick="checkAll('<?= $constant ?>');" style="cursor: pointer;">
                            <span style="cursor: pointer;font-size: 14px;font-weight: normal;color: black;"
                                  onclick="checkAllText('<?= $constant ?>');">Select All</span>
                        </span>

                    <a href="<?= $printData[$constant]['gotoredcap'] ?>" target="_blank"
                       style="float: right;padding-right: 15px;color: #337ab7;font-weight: bold;margin-top: 5px;">Go to
                        REDCap</a>
                    <span class="hub-update-last-updated">
                            <?php
                            echo "Updated on " . HubUpdates::getTemplateLastUpdatedDate($module, $constant); ?>
                        </span>
                </h3>
            </div>
            <div id="collapse<?= $constant ?>" class="table-responsive panel-collapse collapse" aria-expanded="true">
                <table class="table sortable-theme-bootstrap" data-sortable>
                    <tr>
                        <td colspan='5' style="text-align: left !important;">
                            <span style="padding-left: 5px"><?= HubUpdates::getIcon(
                                    HubUpdates::CHANGED
                                ) . " <span style='vertical-align: sub'>" . ucfirst(
                                    HubUpdates::CHANGED
                                ) . " (" . ($allUpdates[$constant]['TOTAL'][HubUpdates::CHANGED] ?? 0) . ")" ?></span></span>
                            <span style="padding-left: 5px"><?= HubUpdates::getIcon(
                                    HubUpdates::ADDED
                                ) . " <span style='vertical-align: sub'>" . ucfirst(
                                    HubUpdates::ADDED
                                ) . " (" . ($allUpdates[$constant]['TOTAL'][HubUpdates::ADDED] ?? 0) . ") " ?></span></span>
                            <span style="padding-left: 5px"><?= HubUpdates::getIcon(
                                    HubUpdates::REMOVED
                                ) . " <span style='vertical-align: sub'>" . ucfirst(
                                    HubUpdates::REMOVED
                                ) . " (" . ($allUpdates[$constant]['TOTAL'][HubUpdates::REMOVED] ?? 0) . ")" ?></span></span>
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
                    foreach ($project_data as $instrument => $instrumentData) {
                        if ($instrument != "TOTAL") {
                            ?>
                            <tr>
                                <td colspan='5' class='instrument-header' style="text-align: left !important;">*<u>Instrument</u>:
                                    <em><strong><?= ucwords(str_replace('_', ' ', $instrument)) ?></em></strong></td>
                            </tr>
                            <?php
                            foreach ($instrumentData as $status => $typeData) {
                                foreach ($typeData as $variable => $data) {
                                    ?>
                                    <tr onclick="checkselect('<?= $constant . "-" . $variable; ?>');"
                                        parent_table='<?= $constant; ?>' row="<?= $constant . "-" . $variable ?>">
                                        <td>
                                            <input value="<?= $constant . "-" . $variable . "-" . $status . "-" . $data['field_type'] ?>"
                                                   id="<?= $constant . "-" . $variable ?>"
                                                   onclick="checkselect('<?= $constant . "-" . $variable; ?>');"
                                                   class='auto-submit' type="checkbox"
                                                   chk_name='chk_table_<?= $constant; ?>' name='tablefields[]'>
                                        </td>
                                        <td><?= HubUpdates::getIcon($status) ?></td>
                                        <td><?= HubUpdates::getFieldName(
                                                $data,
                                                $oldValues[$constant][$variable],
                                                $status,
                                                'field_name'
                                            ) ?></td>
                                        <td><?php
                                            if ($status == HubUpdates::CHANGED) {
                                                $col = HubUpdates::getFieldLabel(
                                                    $data,
                                                    $oldValues[$constant][$variable],
                                                    $status,
                                                    'Section Header:',
                                                    'section_header'
                                                );
                                                $col .= HubUpdates::getFieldName(
                                                    $data,
                                                    $oldValues[$constant][$variable],
                                                    $status,
                                                    'field_label'
                                                );
                                                $col .= HubUpdates::getFieldLabel(
                                                    $data,
                                                    $oldValues[$constant][$variable],
                                                    $status,
                                                    '',
                                                    'field_note'
                                                );
                                            } else {
                                                $col = HubUpdates::getFieldLabel(
                                                    $data,
                                                    $oldValues[$constant][$variable],
                                                    $status,
                                                    '',
                                                    ''
                                                );
                                            }
                                            print($col);
                                            ?>
                                        </td>
                                        <td class="col-sm-4">
                                            <?php
                                            if ($status == HubUpdates::CHANGED) {
                                                print(HubUpdates::getFieldAttributesChanged(
                                                    $data,
                                                    $oldValues[$constant][$variable]
                                                ));
                                            } else {
                                                print(HubUpdates::getFieldAttributes($data));
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php
                                }
                            }
                        }
                    } ?>
                </table>
            </div>

            <?php
            } ?>
        </div>
    </div>
<?php
} else { ?>
    <?php
    $resolved_list = HubUpdates::getResolvedList($module, 'resolved');
    if (!empty($resolved_list)) {
        ?>
        <h4 class="title" style="padding-top:15px">
            <form method="POST" action="<?= $module->getUrl(
                'hub-updates/resolved_list.php'
            ) . '&redcap_csrf_token=' . $module->getCSRFToken() ?>" class="" id="resolved_list">
                <div style="float: left;padding-right: 5px;">You can check your resolved list clicking here:</div>
                <button type="submit" class="btn btn-resolved" style="display: block;" id="select_btn">See Resolved
                    List
                </button>
            </form>
        </h4>
    <?php
    } ?>
<?php
} ?>
<!-- MODAL -->
<div id="confirmationForm" title="Confirmation" style="display:none;">
    <form method="POST" action="" id="data_confirmation">
        <div class="modal-body">
            <div class="alert alert-success col-md-12" style="display: none" id="update_text">File added to the REDCap
                File Repository.
            </div>
            <span id="fields_total"></span>
            <br>
            <br>
            <div id="import_confirmation"></div>
            <input type="hidden" id="option" name="option">
        </div>
        <div class="modal-footer" style="padding-top: 30px;">
            <a onclick="changeFormUrlPDF(this.id);return false;" class="btn btn-primary" id='btnUploadPDF'><em
                        class="fa fa-solid fa-upload"></em> File Repository</a>
            <a onclick="changeFormUrlPDF(this.id);this.closest('form').submit();return false;" class="btn btn-primary"
               id='btnDownloadPDF'><em class="fa fa-solid fa-file-pdf"></em> Download</a>
            <a onclick="changeFormUrlPDF(this.id);" class="btn btn-success" id='btnConfirm'
               name="btnConfirm">Continue</a>
        </div>
    </form>
</div>
<div id="dialogWarning" title="WARNING!" style="display:none;">
    <p>No fields selected.</p>
</div>
</body>
</html>
