<?php
namespace Vanderbilt\HarmonistHubExternalModule;

use PhpParser\Lexer\TokenEmulator\EnumTokenEmulator;

$record = htmlentities($_REQUEST['record'],ENT_QUOTES);

$RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', array('record_id' => $record));
$sop = $module->escape(ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP,$pidsArray['SOP'])[0]);

if($sop !="") {
    $sop_status = $module->getChoiceLabels('sop_status', $pidsArray['SOP']);
    $sop_visibility = $module->getChoiceLabels('sop_visibility', $pidsArray['SOP']);
    $status_type = $module->getChoiceLabels('data_response_status', $pidsArray['SOP']);

    $concept_id = \REDCap::getData($pidsArray['HARMONIST'], 'json-array', array('record_id' => $sop['sop_concept_id']),array('concept_id'))[0]['concept_id'];
    $concept = getReqAssocConceptLink($module, $pidsArray, $sop['sop_concept_id'], 1);

    $people = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array('record_id' => $sop['sop_creator']),array('firstname','lastname','email'))[0];
    $research_contact = "<em>None</em>";
    if($people['firstname'] != ''){
        $research_contact = $people['firstname'] . ' ' . $people['lastname']." (<a href='mailto:".$people['email']."'>".$people['email']."</a>)";
    }

    $RecordSetPeopleDC = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array('record_id' => $sop['sop_datacontact']),array('firstname','lastname','email'))[0];
    $data_contact = "<em>None</em>";
    if($peopleDC != ''){
        $data_contact = $peopleDC['firstname'] . ' ' . $peopleDC['lastname']." (<a href='mailto:".$peopleDC['email']."'>".$peopleDC['email']."</a>)";
    }

    $array_dates = getNumberOfDaysLeftButtonHTML($sop['sop_due_d'], '', '', '1');

    $statusBadge = 'badge-draft';
    if ($sop['sop_status'] == '1') {
        $statusBadge = 'badge-final';
    }

    $visibility = 'badge-private';
    if ($sop['sop_visibility'] == '2') {
        $visibility = 'badge-public';
    } else {
        $statusBadge = 'hidden';
    }

    $date = new \DateTime($sop['sop_updated_dt']);
    $sop_updated_dt = $date->format('d F Y');

    if ($_REQUEST['option'] == 'und' && $record != '') {
        $userid = $current_user['record_id'];
        $record_id = $record;

        $Proj = new \Project($pidsArray['SOP']);
        $event_id = $Proj->firstEventId;
        $recordSaveDU = array();

        $RecordSetFollowAct = \REDCap::getData($pidsArray['SOP'], 'json-array', array('record_id' => $record_id));
        $follow_activity = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetFollowAct,$pidsArray['SOP'])[0]['follow_activity'];
        $array_userid = explode(',', $follow_activity);

        #UNFOLLOW
        if (($key = array_search($userid, $array_userid)) !== false) {
            unset($array_userid[$key]);
            $string_userid = implode(",", $array_userid);
            $recordSaveDU[$record_id][$event_id]['follow_activity'] = $string_userid;
            $results = \Records::saveData($pidsArray['SOP'], 'array', $recordSaveDU,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
            \Records::addRecordToRecordListCache($pidsArray['SOP'], $record_id,1);
        }
    }
}

$harmonist_perm = ($current_user['harmonist_perms___1'] == 1) ? true : false;
?>
<script>
    $(document).ready(function() {
        //To change the text on select
        $(".dropdown-menu-custom li").click(function(){
            var selText = $(this).html();
            $(this).parents('.dropdown').find('.dropdown-toggle').html(selText+' <span class="caret" style="float: right;margin-top:8px"></span>');
        });
        $('#deleteDataRequest').submit(function () {
            var data = $('#deleteDataRequest').serialize();
            CallAJAXAndRedirect(data, <?=json_encode($module->getUrl('sop/sop_delete_data_request.php').'&NOAUTH')?>,<?=json_encode($module->getUrl("index.php?pid=".$pidsArray['PROJECTS']."&option=smn&message=D"))?>);
            return false;
        });
        $('#makePrivate').submit(function () {
            $('#sop-make-private-confirmation').modal('hide');
            var data = $('#makePrivate').serialize();
            CallAJAXAndShowMessage(data,<?=json_encode($module->getUrl('sop/sop_make_private.php').'&NOAUTH')?>, "X",window.location.href);
            return false;
        });
        $('#dataUploadForm').submit(function () {
            var data = $('#dataUploadForm').serialize();
            uploadDataToolkit(data,<?=json_encode($module->getUrl("hub/hub_data_upload_security_AJAX.php").'&NOAUTH')?>);
            return false;
        });
        $('#changeStatus').submit(function () {
            var data = "&status="+$('#data_status').find('.status').attr('status');
            data += "&region="+$('#region').val();
            data += "&status_record="+$('#status_record').val();
            data += "&data_response_notes="+encodeURIComponent($('#data_response_notes').val());
            var record = <?=json_encode($record)?>;
            CallAJAXAndRedirect(data,<?=json_encode($module->getUrl('sop/sop_submit_data_change_status_AJAX.php').'&NOAUTH')?>,<?=json_encode($module->getUrl("index.php?pid=".$pidsArray['PROJECTS']."&option=sop&record=".$record."&message=D"))?>);
            return false;
        });
    });
</script>

<div class="container">
    <?php
    if(array_key_exists('message', $_REQUEST)){
        if($_REQUEST['message'] == 'F') {?>
            <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;" id="succMsgContainer">This Data Call has been marked complete (if "Close Data Call" checkbox was selected).</div>
            <?php
        }if($_REQUEST['message'] == 'S') {?>
            <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;" id="succMsgContainer">This Data Call has been started (if "Begin Data Call" checkbox was selected).</div>
            <?php
        }else if($_REQUEST['message'] == 'C'){?>
            <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;" id="succMsgContainer">Your comment has been successfully added.</div>
            <?php
        }else if($_REQUEST['message'] == 'E'){?>
            <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;" id="succMsgContainer">Your comment has been successfully updated.</div>
            <?php
        }else if($_REQUEST['message'] == 'D'){?>
            <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;" id="succMsgContainer">The status has been changed</div>
            <?php
        }else if($_REQUEST['message'] == 'P') {?>
            <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;" id="succMsgContainer">Data Request has been made public.</div>
            <?php
        }else if($_REQUEST['message'] == 'X') {?>
            <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;" id="succMsgContainer">Data Request has been made private.</div>
            <?php
        }
    }
    ?>

    <div class="backTo">
        <?php
        $back_button = '<a href="'.$module->getUrl('index.php').'&NOAUTH&option=smn'.'">< Back to Request Data</a>';
        if($_REQUEST['type'] != ""){
            if($_REQUEST['type'] == 's'){
                $back_button = '<a href="'.$module->getUrl('index.php').'&NOAUTH&option=upd'.'">< Back to Check and Submit Data</a>';
            }else if($_REQUEST['type'] == 'r'){
                $back_button = '<a href="'.$module->getUrl('index.php').'&NOAUTH&option=dnd'.'">< Back to Retrieve Data</a>';
            }
        }
        echo $back_button;
        ?>
    </div>
    <?php if(($sop['sop_hubuser'] == $current_user['record_id'] || $isAdmin || $sop['sop_visibility'] == '2') && $sop !="") { ?>
        <div class="panel panel-info panel-info-sop">
            <div class="panel-heading panel-heading-sop">
                <h2 class="panel-title" style="display: inline-block;padding: 8px 0px 10px;">
                    Data Request #<?= $sop['record_id']; ?> | <?= $concept_id; ?>
                </h2>
                <?php if ($isAdmin || $harmonist_perm) {
                    $gotoredcap = APP_PATH_WEBROOT_ALL . "DataEntry/record_home.php?pid=" . $pidsArray['SOP'] . "&arm=1&id=" . $sop['record_id'];
                    ?>
                    <div class="btn-group hidden-xs pull-right">
                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                            Admin <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            <?php
                                if(($sop['sop_status'] == '2' && ($isAdmin || $harmonist_perm)) || $sop['sop_status'] != '2'){
                                    ?> <li>
                                        <a href='<?=$module->getUrl("index.php")."&NOAUTH&pid=".$pidsArray['PROJECTS']."&option=ss1&record=".$record."&step=3"?>' target="_blank">Edit Data Request</a>
                                    </li>
                                    <?php
                                }
                                if($isAdmin || $harmonist_perm){
                                ?> <li>
                                    <a href="#" onclick="$('#modal-copy-data-request').modal('show');">Copy Data Request</a>
                                </li>
                                    <?php
                                }
                            echo '<li>
                                    <a href="#" onclick="$(\'#hub_view_votes\').modal(\'show\');" >Edit Group Status</a>
                                </li>';
                            $status = $sop['data_response_status'][$current_user['person_region']];
                            $status_icons = getDataCallStatusIcons($status);
                            $status_text = $status_type[$sop['data_response_status'][$current_user['person_region']]];
                            if ($sop['data_response_status'][$current_user['person_region']] == "") {
                                $status_text = $status_type[0];
                            }
                            $current_region_status = htmlentities($status_icons . '<span class="status-text"> ' . htmlspecialchars($status_text,ENT_QUOTES) . '</span>');

                            echo '<li>
                                <a href="#" onclick="changeStatus(\'' . $current_region_status . '\',\'' . $sop['record_id'] . '\',\'' . $current_user['person_region'] . '\',\'' . htmlspecialchars($sop['data_response_notes'][$current_user['person_region']]) . '\',\'' . $sop['region_update_ts'][$current_user['person_region']] . '\',\'modal-data-change-status\')">Change Status</a>
                            </li>';

                            if(($isAdmin || $harmonist_perm) && $sop['sop_visibility'] == "2" && $sop['sop_status'] != "1"){
                                echo '<li><a href="#" onclick="confirmMakePrivate(\'' . $sop['record_id'] . '\')" class="open-codesModal">Revert to Private</a></li>';
                            }
                            if($sop['sop_status'] != "1") {
                                $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['SOP'], $record, "finalization_of_data_request", "");
                                $survey_link =  $module->escape($module->escape(APP_PATH_WEBROOT_FULL . "/surveys/?s=".$passthru_link['hash']));
                                echo '<li><a href="#" onclick="editIframeModal(\'hub-modal-data-finalize\',\'redcap-finalize-frame\',\'' . $survey_link . '\');" style="cursor:pointer">Start Data Call</a></li>';
                            }
                            if($sop['sop_status'] == "1" && $sop['sop_visibility'] == "2" && $sop['sop_finalize_y'][1] == '1' && empty($sop['sop_closed_y'])){
                                $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['SOP'], $record, "data_call_closure", "");
                                $survey_link_closure =  $module->escape($module->escape(APP_PATH_WEBROOT_FULL . "/surveys/?s=".$passthru_link['hash']));
                                echo '<li><a href="#" onclick="editIframeModal(\'hub-modal-data-closure\',\'redcap-closure-frame\',\'' . $survey_link_closure . '\');" style="cursor:pointer">Archive Data Call</a></li>';
                            }
                            ?>
                            <li role="separator" class="divider"></li>
                            <li><a href="#" onclick="$('#admin-modal-delete').modal('show');" style="cursor:pointer">Delete</a>
                            </li>
                            <li role="separator" class="divider"></li>
                            <li><a href="<?= $gotoredcap ?>" target="_blank">Go to REDCap</a></li>
                        </ul>
                    </div>

                    <!-- MODAL COPY DATA REQUEST-->
                    <div class="modal fade" id="modal-copy-data-request" tabindex="-1" role="dialog" aria-labelledby="Codes">
                        <form class="form-horizontal" action="" method="post" id='dataDownloadForm'>
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        <h4 class="modal-title">Copy Data Request</h4>
                                    </div>
                                    <div class="modal-body">
                                        <span>Are you sure you want to copy this data request?</span>
                                    </div>
                                    <div class="modal-footer">
                                        <?php
                                        $url = $module->getUrl('sop/sop_copy_data_request_AJAX.php?id='.$module->escape($sop['record_id'])."&NOAUTH");
                                        $urlgoto = $module->getUrl("index.php?&option=ss1&step=3")."&NOAUTH";
                                        ?>
                                        <a type="submit" onclick='copy_data_request("<?=$url?>","<?=$urlgoto?>")' class="btn btn-default btn-success" id='btnModalRescheduleForm'>Continue</a>
                                        <a href="#" class="btn btn-default btn-cancel" data-dismiss="modal">Cancel</a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <!-- MODAL FINALIZE-->
                    <div class="modal fade" id="hub-modal-data-finalize" tabindex="-1" role="dialog"
                         aria-labelledby="Codes">
                        <div class="modal-dialog" role="document" style="width: 800px;">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close closeCustomModal" data-dismiss="modal"
                                            aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    <h4 class="modal-title">Start Data Request</h4>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" value="0" id="comment_loaded_finalize">
                                    <iframe class="commentsform" id="redcap-finalize-frame" message="S" name="redcap-finalize-frame"
                                            src="" style="border: none;height: 500px;width: 100%;"></iframe>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MODAL VIEW STATUS-->
                    <div class="modal fade" id="hub_view_votes" tabindex="-1" role="dialog" aria-labelledby="Codes">
                        <div class="modal-dialog" role="document" style="width: 800px">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    <h4 class="modal-title">Edit Group Status</h4>
                                </div>
                                <div class="modal-body">
                                    <table class="table table-striped">
                                        <thead>
                                        <tr>
                                            <th>Region</th>
                                            <th>Vote</th>
                                            <th>On</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        $regions = \REDCap::getData($pidsArray['REGIONS'], 'json-array', null,null,null,null,false,false,false,"[showregion_y] = '1'");
                                        ArrayFunctions::array_sort_by_column($regions, 'region_code');

                                        $status_type = $module->getChoiceLabels('data_response_status', $pidsArray['SOP']);
                                        $status_icon_color = array(0=>"label-default_light",1=>"label-warning",2=>"label-approved",3=>"label-default",4=>"label-default",9=>"label-other");
                                        $status_icon = array(0=>"fa-times text-default_light",1=>"fa-wrench",2=>"fa-check",3=>"fa-ban",4=>"fa-times",9=>"fa-question");

                                        $region_row = '';
                                        foreach ($regions as $region){
                                            $region_id = ($region['record_id'] == '1')? "" : $region['record_id'];
                                            $region_time = $sop['region_complete_ts'][$region_id];
                                            if(!empty( $region_time)) {
                                                $region_time = date('Y-m-d H:i', strtotime($region_time));
                                                $class = "";
                                                if (strtotime($sop['sop_due_d']) < strtotime($region_time)){
                                                    $class = "overdue";
                                                }
                                            }

                                            $status = $sop['data_response_status'][$region['record_id']];
                                            $status_icons = getDataCallStatusIcons($status);
                                            $status_text = $status_type[$sop['data_response_status'][$region['record_id']]];
                                            if ($sop['data_response_status'][$region['record_id']] == "") {
                                                $status_text = $status_type[0];
                                            }
                                            $current_region_status = $status_icons . '<span class="status-text"> ' . htmlspecialchars($status_text,ENT_QUOTES) . '</span>';

                                            $selected = $status_icons .'<span class="status-text"> ' . htmlspecialchars($status_text,ENT_QUOTES) . '</span>';
                                            $selected .= '<input type="hidden" value="" class="dropdown_votes" record="'.$module->escape($sop['record_id']).'" id="'.$module->escape($region_id.'_'.$sop['data_response_status'][$region['record_id']]).'">';
                                            $menu = '';
                                            foreach ($status_type as $index=>$status){
                                                $menu .= '<li style="width:290px"><span class="fa-label status fa fa-fw '.$module->escape($status_icon[$index].' '.$status_icon_color[$index]).'" style="padding: 2px;border-radius:3px;color:#fff" aria-hidden="true" status="'.$index.'"></span><span class="status-text"> '.htmlspecialchars($status,ENT_QUOTES).'</span>';
                                                $menu .= '<input type="hidden" value="'.$module->escape($index).'" class="dropdown_votes" record="'.$module->escape($sop['record_id']).'" id="'.$module->escape($region_id.'_'.$index).'"></li>';
                                            }

                                            $region_row .= '<tr>'.
                                                '<td>'.htmlspecialchars($region['region_code'],ENT_QUOTES).'/'.htmlspecialchars($region['region_name'],ENT_QUOTES).'</td>'.
                                                '<td>
                                                            <div style="float:left;">
                                                                <ul class="nav" style="margin:0;width:290px" name="data_status_region">
                                                                    <li class="menu-item dropdown">
                                                                       <a href="#" data-toggle="dropdown" class="dropdown-toggle dropdown-toggle-custom form-control output_select btn-group" id="default-select-value" style="width: 300px;">'.$selected.'<span class="caret" style="float: right;margin-top:8px"></span></a>
                                                                        <ul class="dropdown-menu output-dropdown-menu dropdown-menu-custom" style="width:290px">
                                                                            '.$menu.'
                                                                        </ul>
                                                                    </li>
                                                                </ul>
                                                                </div></td>'.
                                                '<td><span class="'.$class.'">'.htmlspecialchars($region_time,ENT_QUOTES).'</span></td>'.
                                                '</tr>';
                                        }
                                        echo $region_row;
                                        ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-default btn-save" onclick='save_status(<?=json_encode($module->getUrl('sop/sop_data_request_title_admin_status_AJAX.php').'&NOAUTH')?>,<?=json_encode($current_user['record_id'])?>,<?=json_encode($current_user['person_region'])?>)' data-dismiss="modal">Save</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MODAL CHANGE STATUS-->
                    <div class="modal fade" id="modal-data-change-status" tabindex="-1" role="dialog" aria-labelledby="Codes">
                        <form class="form-horizontal" action="" method="post" id='changeStatus'>
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        <h4 class="modal-title">Change Status</h4>
                                    </div>
                                    <div class="modal-body">
                                        <div style="padding-bottom:10px">Last update on <i id="region_update_ts"></i></div>
                                        <div style="width: 100%;">
                                            <div style="float: left;line-height: 30px;">Set my status:</div>
                                            <div style="float: left;padding-left: 10px">
                                                <?php
                                                $status_type = $module->escape($module->getChoiceLabels('data_response_status', $pidsArray['SOP']));
                                                $status_icon_color = $module->escape(array(0=>"label-default_light",1=>"label-warning",2=>"label-approved",3=>"label-default",4=>"label-default",9=>"label-other"));
                                                $status_icon = $module->escape(array(0=>"fa-times text-default_light",1=>"fa-wrench",2=>"fa-check",3=>"fa-ban",4=>"fa-times",9=>"fa-question"));
                                                $selected .= '<input type="hidden" value="" class="dropdown_votes" record="'.$module->escape($sop['record_id']).'" id="'.$module->escape($region_id.'_'.$sop['data_response_status'][$region['record_id']]).'">';
                                                $menu = '';
                                                foreach ($status_type as $index=>$status){
                                                    $menu .= '<li style="width:290px"><span class="fa-label status fa fa-fw '.$status_icon[$index].' '.$status_icon_color[$index].'" style="padding: 2px;border-radius:3px;color:#fff" aria-hidden="true" status="'.$index.'"></span><span class="status-text"> '.htmlspecialchars($status,ENT_QUOTES).'</span>';
                                                    $menu .= '<input type="hidden" value="'.$module->escape($index).'" class="dropdown_votes" record="'.$module->escape($sop['record_id']).'" id="'.$module->escape($region_id.'_'.$index).'"></li>';
                                                }
                                                ?>
                                                <ul class="nav" style="margin:0;width:290px" id="data_status" name="data_status">
                                                    <li class="menu-item dropdown">
                                                        <a href="#" data-toggle="dropdown" class="dropdown-toggle dropdown-toggle-custom form-control output_select btn-group" id="default-select-value" style="width: 300px;"><?=$module->escape($selected)?><span class="caret" style="float: right;margin-top:8px"></span></a>
                                                        <ul class="dropdown-menu output-dropdown-menu dropdown-menu-custom" style="width:290px">
                                                            <?=$menu?>
                                                        </ul>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div style="margin-top: 40px;">Status notes (only visible to your own region):</div>
                                        <div>
                                            <textarea style="width: 100%;height: 100px;" id="data_response_notes" name="data_response_notes"></textarea>
                                        </div>
                                        <input type="hidden" name="status_record" id="status_record" value="">
                                        <input type="hidden" name="region" id="region" value="">
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" form="changeStatus" class="btn btn-default btn-success" id='btnModalRescheduleForm'>Save</button>
                                        <a href="#" class="btn btn-default btn-cance;" data-dismiss="modal">Cancel</a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="modal fade" id="hub-modal-data-closure" tabindex="-1" role="dialog"
                         aria-labelledby="Codes">
                        <div class="modal-dialog" role="document" style="width: 800px;">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close closeCustomModal" data-dismiss="modal"
                                            aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    <h4 class="modal-title">Archive Data Call</h4>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" value="0" id="comment_loaded_closure">
                                    <iframe class="commentsform" id="redcap-closure-frame" message="F" name="redcap-closure-frame"
                                            src="" style="border: none;height: 500px;width: 100%;"></iframe>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>

                <?php if ($sop['sop_visibility'] != '2' && !$isAdmin && !$harmonist_perm) { ?>
                    <div class="btn-group hidden-xs pull-right" style="margin-top: 5px;">
                        <a href="#" onclick="$('#admin-modal-delete').modal('show');" style="cursor: pointer"
                           title="Delete Data Request"><span class="fa fa-trash"
                                                             style="font-size: 25px;color:#663300"></span></a>
                    </div>
                <?php } ?>
                <!-- MODAL DELETE-->
                <div class="modal fade" id="admin-modal-delete" tabindex="-1" role="dialog" aria-labelledby="Codes">
                    <form class="form-horizontal" action="" method="post" id='deleteDataRequest'>
                        <div class="modal-dialog" role="document">
                            <div class="modal-content" style="color:#333">
                                <div class="modal-header">
                                    <button type="button" class="close closeCustomModal" data-dismiss="modal"
                                            aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    <h4 class="modal-title">Delete</h4>
                                </div>
                                <div class="modal-body">
                                    <span>Are you sure you want to delete this Data Request?</span>
                                    <input type="hidden" value="<?= $record ?>" id="index_modal_delete"
                                           name="index_modal_delete">
                                </div>

                                <div class="modal-footer">
                                    <button type="submit" form="deleteDataRequest" class="btn btn-default btn-delete"
                                            id='btnModalDeleteForm'>Delete
                                    </button>
                                    <a href="#" class="btn btn-default btn-cancel" data-dismiss="modal">Cancel</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="pull-right" id="btn_follow" style="padding-right: 10px">
                    <?php
                    $follow_option = '1';
                    $follow_class = 'btn-default';
                    $follow_icon_class = 'fa fa-plus-square';
                    $follow_text = "Follow Activity";
                    if ($sop['follow_activity'] != '' && $_REQUEST['option'] != 'und') {
                        $array_userid = explode(',', $sop['follow_activity']);
                        if (in_array($current_user['record_id'], $array_userid)) {
                            $follow_option = '0';
                            $follow_class = 'btn-primary';
                            $follow_icon_class = 'fa fa-check-square';
                            $follow_text = "Following";
                        }
                    }
                    ?>
                    <button onclick="follow_activity('<?= $module->escape($follow_option) ?>','<?= $module->escape($current_user['record_id']) ?>','<?= $module->escape($sop['record_id']) ?>','<?= $module->getUrl("sop/sop_data_request_follow_activity_AJAX.php") ?>')"
                            class="btn <?= $module->escape($follow_class) ?> actionbutton"><i
                                class="<?= $module->escape($follow_icon_class) ?>"></i> <span class="hidden-xs"><?= htmlspecialchars($follow_text,ENT_QUOTES) ?></span></button>
                </div>
                <div class="pull-right" id="btn_follow" style="padding-right: 10px">
                    <?php
                    if ($sop['sop_visibility'] == '1') {
                        $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['SOP'], $_REQUEST['record_id'], "dhwg_review_request", "");
                        $survey_link =  $module->escape(APP_PATH_WEBROOT_FULL . "/surveys/?s=".$passthru_link['hash']);

                        echo '<a href="#" onclick="editIframeModal(\'sop-make-public\',\'redcap-edit-frame-make-public\',\'' . $survey_link . '\');" class="btn btn-success open-codesModal"><i class="fa fa-paper-plane" aria-hidden="true"></i> Send for Review</a>';
                    }
                    ?>

                </div>
            </div>

            <div class="modal fade" id="sop-make-public" tabindex="-1" role="dialog" aria-labelledby="Codes">
                <div class="modal-dialog" role="document" style="width: 800px">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close closeCustomModal" data-dismiss="modal"
                                    aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title">Send for Review</h4>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" value="0" id="comment_loaded_public">
                            <iframe class="commentsform" id="redcap-edit-frame-make-public"
                                    name="redcap-edit-frame-make-public" message="P" src=""
                                    style="border: none;height: 810px;width: 100%;"></iframe>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="sop-make-private-confirmation" tabindex="-1" role="dialog"
                 aria-labelledby="Codes">
                <form class="form-horizontal" action="" method="post" id='makePrivate'>
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close closeCustomModal" data-dismiss="modal"
                                        aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title">Make Private</h4>
                            </div>
                            <div class="modal-body">
                                <div>Are you sure you want to <strong>make this draft Data Request PRIVATE?</strong></div>
                                <div>This will move the draft data request from PUBLIC status to PRIVATE status. It will still be accessible to the original creator of the data request, but will not appear on everyone’s dashboard.</div>
                            </div>
                            <input type="hidden" id="record" name="record">
                            <div class="modal-footer">
                                <button type="submit" form="makePrivate" class="btn btn-default btn-success"
                                        id='btnModalRescheduleForm'>Continue
                                </button>
                                <a class="btn btn-default btn-cance;" data-dismiss="modal">Cancel</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="panel-body">
                <?php if ($sop['sop_finalize_y'] != "" || ($sop['sop_closed_y'] != "" && $sop['sop_closed_y'] != "1")) {
                    $message_text = "";
                    if ($sop['sop_finalize_y'] != "") {
                        $message_text .= "Data Call started";
                        if ($sop['sop_final_d'] != "") {
                            $message_text .= " on " . $sop['sop_final_d'] . ".";
                        } else {
                            $message_text .= ".";
                        }
                    }
                    if ($sop['sop_closed_y'] != "" && $sop['sop_closed_y'] == "1") {
                        $message_text .= " Data Call archived";
                        if ($sop['sop_closed_d'] != "") {
                            $message_text .= " on " . $sop['sop_closed_d'] . ".";
                        } else {
                            $message_text .= ".";
                        }
                    }

                    ?>
                    <div class="alert alert-warning fade in col-md-12 col-xs-12" id="succMsgContainer_stay">
                        <?php
                        echo "<div class='pull-left'>".htmlspecialchars($message_text,ENT_QUOTES)."</div>";
                        echo '<a href="#" onclick="confirmDataUpload(\'' . $module->escape($sop['sop_concept_id']) . '\',\'' . $module->escape($current_user['record_id']) . '\',\'' .$module->escape($concept_id) . '\',\'' .$module->escape($sop['record_id']) . '\');" class="pull-right btn btn-default btn-xs hidden-sm hidden-xs">Upload Data</a>';
                        ?>
                    </div>
                    <div class="modal fade" id="modal-data-upload-confirmation" tabindex="-1" role="dialog" aria-labelledby="Codes">
                        <form class="form-horizontal" action="" method="post" id='dataUploadForm'>
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        <h4 class="modal-title">Upload Data</h4>
                                    </div>
                                    <div class="modal-body">
                                        <span>Are you ready to upload data for concept <span id="data-submit-concept" style="font-weight: bold"></span>?</span>
                                        <br>
                                        <span>This will redirect you to the Data Toolkit, where you can check and/or submit data.</span>
                                    </div>
                                    <input type="hidden" id="assoc_concept" name="assoc_concept">
                                    <input type="hidden" id="user" name="user">
                                    <input type="hidden" id="upload_record" name="upload_record">
                                    <div class="modal-footer">
                                        <button type="submit" form="dataUploadForm" class="btn btn-default btn-success" id='btnModalRescheduleForm'>Continue</button>
                                        <a class="btn btn-default btn-cance;" data-dismiss="modal">Cancel</a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php } ?>
                <div class="row request">
                    <div class="col-md-8 col-sm-12"><strong>Linked Concept:</strong> <?= filter_tags($concept); ?></div>
                    <div class="col-md-4">
                        <strong>Version: </strong><span><em><?=htmlspecialchars($sop_updated_dt,ENT_QUOTES)?></em></span>
                        <span class="label label-as-badge <?= $statusBadge ?>"><?= htmlspecialchars($sop_status[$sop['sop_status']],ENT_QUOTES); ?></span>&nbsp;&nbsp;
                        <span class="label label-as-badge <?= $visibility ?>"><?= htmlspecialchars($sop_visibility[$sop['sop_visibility']],ENT_QUOTES); ?></span>
                    </div>
                </div>
                <div class="row request">
                    <div class="col-md-8 col-sm-12"><strong>Research Contact:</strong> <?= filter_tags($research_contact); ?>
                    </div>
                    <div class="col-md-4"><strong>Data Due: </strong>
                        <?= filter_tags($array_dates['text']) ?> <?= filter_tags($array_dates['button']) ?>
                    </div>
                </div>
                <div class="row request">
                    <div class="col-md-8 col-sm-12"><strong>Data Contact:</strong> <?= filter_tags($data_contact); ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Status: </strong>
                        <?php
                        $status_text = $status_type[$sop['data_response_status'][$current_user['person_region']]];
                        if($sop['data_response_status'][$current_user['person_region']] == ""){
                            $status_text = $status_type[0];
                        }
                        $status_icons = getDataCallStatusIcons($sop['data_response_status'][$current_user['person_region']]);
                        echo $status_icons.'<span class="status-text"> '.htmlspecialchars($status_text,ENT_QUOTES).'</span>';
                        ?>
                    </div>
                </div>
                <div class="row request">
                    <div class="col-md-8 col-sm-12">
                        <div style="display:inline-block;"><strong>Data Downloaders: </strong></div>
                        <?php
                        if($sop['sop_downloaders'] != "") {
                            $downloaders = explode(',', $sop['sop_downloaders']);
                            $number_downloaders = count($downloaders);
                            $downloaders_list = "";
                            $downloadersOrdered = array();
                            foreach ($downloaders as $down) {
                                $peopleDown = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array('record_id' => $down),array('firstname','lastname','email','person_region'))[0];
                                $region_codeDown = \REDCap::getData($pidsArray['REGIONS'], 'json-array', array('record_id' => $peopleDown['person_region']),array('region_code'))[0]['region_code'];

                                $downloadersOrdered[$down]['name'] = $peopleDown['firstname'] . " " . $peopleDown['lastname'];
                                $downloadersOrdered[$down]['email'] = $peopleDown['email'];
                                $downloadersOrdered[$down]['region_code'] = "(" . $region_codeDown . ")";
                            }
                            ArrayFunctions::array_sort_by_column($downloadersOrdered,'name');

                            $count = 0;
                            foreach ($downloadersOrdered as $downO) {
                                $downO = $module->escape($downO);
                                $comma = ",&nbsp;";
                                $count++;
                                if(count($downloadersOrdered) == $count){
                                    $comma = "";
                                }
                                $downloaders_list .= "<div style='display:inline-block;'><a href='mailto:" . $downO['email'] . "'>" . $downO['name'] . "</a> ".htmlspecialchars($downO['region_code'],ENT_QUOTES).$comma."</div>";
                            }

                        }else{
                            $downloaders_list = '<em>None Assigned</em>';
                        }
                        echo $downloaders_list;
                        ?>
                    </div>
                    <div class="col-md-4"></div>
                </div>
                <div class="row request">
                    <div class="col-md-12"><strong>Data Call Notes: </strong><br>
                        <?=filter_tags(empty($sop['sop_final_notes']) ? "<em>None</em>" : $sop['sop_final_notes'],ENT_QUOTES); ?>
                    </div>
                </div>
                <?php if (!empty($conference_info)) { ?>
                    <div class="row request">
                        <div class="col-md-12">
                            <strong>Conference:</strong> <?= $conference_info; ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
        <div class="hidden-md hidden-lg hidden-sm" style="margin-bottom: 20px">
            <?php
            #Concept Files
            $q = $module->query("SELECT doc_name,stored_name,file_extension FROM redcap_edocs_metadata WHERE doc_id = ?",[$sop["sop_finalpdf"]]);
            $row_sop_file = $q->fetch_assoc();

            if (!empty($row_sop_file['doc_name'])) {
                $extension = ($row_sop_file['file_extension'] == 'pdf') ? "pdf-icon.png" : "word-icon.png";
                $pdf_path = APP_PATH_PLUGIN . "/loadPDF.php?edoc=" . $sop["sop_finalpdf"];

                $file_icon = getFileLink($module, $pidsArray['PROJECTS'], $sop["sop_finalpdf"], '1', '', $secret_key, $secret_iv,$current_user['record_id'],"");
                ?>

                <a class="btn btn-default" href="downloadFile.php?code=<?= getCrypt("sname=" . $row_sop_file['stored_name'] . "&file=" . urlencode($row_sop_file['doc_name'])."&edoc=".$sop["sop_finalpdf"]."&pid=".$current_user['record_id'], 'e', $secret_key, $secret_iv) ?>" target="_blank">
                    <span class="fa fa-file-pdf-o"></span> Download PDF
                </a>
            <?php } ?>
        </div>
        <div class="panel panel-default hidden-xs" style="margin-bottom: 40px">
            <div class="panel-heading" style="height: 38px">
                <h3 class="panel-title">
                    <a data-toggle="collapse" href="#collapse1">Data Request</a>
                    <?php
                    #Concept Files
                    $q = $module->query("SELECT doc_name,stored_name,file_extension FROM redcap_edocs_metadata WHERE doc_id = ?",[$sop["sop_finalpdf"]]);
                    $row_sop_file = $q->fetch_assoc();

                    if (!empty($row_sop_file['doc_name'])) {
                        $extension = ($row_sop_file['file_extension'] == 'pdf') ? "pdf-icon.png" : "word-icon.png";
                        $pdf_path = $module->getUrl("loadPDF.php")."&NOAUTH&pid=".$pidsArray['PROJECTS']."&edoc=" . $sop["sop_finalpdf"]."#navpanes=0&scrollbar=0";

                        $file_icon = getFileLink($module, $pidsArray['PROJECTS'], $sop["sop_finalpdf"], '1', '', $secret_key, $secret_iv,$current_user['record_id'],"");
                        ?>
                        <span style="float: right;padding-right: 15px;"><?= $file_icon; ?></span>
                        <a href="<?=$module->getUrl('downloadFile.php').'&NOAUTH&code='.getCrypt("sname=" . $row_sop_file['stored_name'] . "&file=" . urlencode($row_sop_file['doc_name'])."&edoc=".$sop["sop_finalpdf"]."&pid=".$current_user['record_id'], 'e', $secret_key, $secret_iv) ?>"
                           target="_blank" style="float: right;padding-right: 10px;">Download PDF </a>
                    <?php } ?>
                </h3>
            </div>

            <div id="collapse1" class="table-responsive panel-collapse collapse in" aria-expanded="true">
                <?php if (!empty($row_sop_file['doc_name'])) { ?>
                    <iframe class="commentsform" id="redcap-frame" src="<?= $pdf_path ?>"
                            style="border: none;width: 100%;height: 500px;" frameborder="0"></iframe>
                <?php } else { ?>
                    <table class="table table-hover table-bordered table-list table-font-size">
                        <tbody>
                        <tr>
                            <td><span><em>No document available</em></span></td>
                        </tr>
                        </tbody>
                    </table>
                <?php } ?>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    Data Request Uploads
                    <a href="<?=$module->geturl("index.php")."&NOAUTH&pid=".$pidsArray['PROJECTS']."option=lgd&record=".$record;?>" style="float: right;padding-right: 10px;color: #337ab7">View more</a>
                </h3>
            </div>
            <div id="collapse_dataReqUp" class="panel-collapse" aria-expanded="true">
                <table class="table table_requests sortable-theme-bootstrap" data-sortable id="sortable_table">
                    <colgroup>
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                    </colgroup>
                    <?php
                    $uploads = \REDCap::getData($pidsArray['DATAUPLOAD'], 'json-array', null,null,null,null,false,false,false,"[data_assoc_request] = '".$record."'");
                    ArrayFunctions::array_sort_by_column($uploads, 'responsecomplete_ts',SORT_DESC);
                    if (!empty($uploads)){
                    ?>

                    <thead>
                    <tr>
                        <th class="sorted_class" data-sorted="true" data-sorted-direction="descending">Upload Date</th>
                        <th class="sorted_class">Uploaded By</th>
                        <th class="sorted_class">Notes</th>
                        <th class="sorted_class">Region</th>
                        <th class="sorted_class">Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $count=0;
                    foreach ($uploads as $up) {
                        if($settings['dataupload_dur'] > $count) {
                            $people = $module->escape(\REDCap::getData($pidsArray['PEOPLE'], 'json-array', array('record_id' => $up['data_upload_person']),array('firstname','lastname','email'))[0]);
                            $contact_person = "<a href='mailto:" . $people['email'] . "'>" . $people['firstname'] . " " . $people['lastname'] . "</a>";

                            $region_code = \REDCap::getData($pidsArray['REGIONS'], 'json-array', array('record_id' => $up['data_upload_region']),array('region_code'))[0]['region_code'];

                            $status = '<span class="badge label-updated">Available</span>';
                            if ($up['deleted_y'] == '1') {
                                $status = '<span class="badge label-notupdated" >Expired</span>';
                            }

                            echo "<tr>";
                            echo "<td width='200px'>" . htmlspecialchars($up['responsecomplete_ts'],ENT_QUOTES) . "</td>" .
                                "<td width='250px'>" .  $contact_person . "</td>" .
                                "<td width='500px'>" .  htmlspecialchars($up['upload_notes'],ENT_QUOTES) . "</td>" .
                                "<td width='120px'>" .  htmlspecialchars($region_code,ENT_QUOTES) . "</td>" .
                                "<td width='120px'>" .  $status . "</td>" .
                                "</tr>";
                            $count++;
                        }else{
                            break;
                        }
                    }
                    } else {
                        ?>
                        <li class="list-group-item"><em>No active data calls.</em></li>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a data-toggle="collapse" href="#collapse1">Comments and Questions</a>
                </h4>
            </div>
            <div id="collapse1" class="panel-collapse collapse in" aria-expanded="true">
                <table class="table table-hover table-bordered table-list table-font-size">
                    <thead>
                    <tr>
                        <th class="comments-table">Name / Time</th>
                        <th class="comments-table">Version</th>
                        <th>Comments</th>
                        <th style="text-align: center"><em class="fa fa-cog"></em></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $comments = \REDCap::getData($pidsArray['SOPCOMMENTS'], 'json-array', null,null,null,null,false,false,false,"[sop_id] = '".$record."'");
                    krsort($comments);
                    if (!empty($comments)) {
                        foreach ($comments as $comment) {
                            $comment = $module->escape($comment);
                            $comment_time = "";
                            if (!empty($comment['responsecomplete_ts'])) {
                                $dateComment = new \DateTime($comment['responsecomplete_ts']);
                                $dateComment->modify("+1 hours");
                                $comment_time = $dateComment->format("Y-m-d H:i:s");
                            }

                            $people = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array('record_id' => $comment['response_person']),array('firstname','lastname','email'))[0];
                            $name = $module->escape(trim($people['firstname'] . ' ' . $people['lastname']));

                            $gd_files = "";
                            if (!empty($comment['revised_file'])) {
                                if (!empty($comment['comments'])) {
                                    $gd_files .= "<br/>";
                                }
                                $gd_files .= getFileLink($module, $pidsArray['PROJECTS'], $comment['revised_file'], '', '', $secret_key, $secret_iv,$current_user['record_id'],"");
                            }


                            if ($comment['sop_status'] == '1') {
                                $statusBadge = 'badge-final';
                            } else {
                                $statusBadge = 'badge-draft';
                            }

                            $group_discussion .= "<tr>" .
                                "<td style='width:20%'><a href='mailto:" . $people['email'] . "'>" . $name . "</a> (" . $comment['response_regioncode'] . ")<br/>" . $comment_time . "</td>" .
                                "<td style='width:3%'><span class='label label-as-badge " . $statusBadge . "'>" . $sop_status[$comment['comment_ver']] . "</span></td>" .
                                "<td style='width:70%'>" . nl2br($comment['comments']) . $gd_files . "</td>" .
                                "<td  style='width:5%'>";
                            if ($comment['response_person'] == $current_user['record_id']) {
                                $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['SOPCOMMENTS'], $comment['record_id'], "sop_comments", "");
                                $survey_link =  $module->escape(APP_PATH_WEBROOT_FULL . "/surveys/?s=".$passthru_link['hash']);

                                $group_discussion .= '<a href="#" class="btn btn-default open-codesModal" onclick="editIframeModal(\'hub_comment_and_votes_survey\',\'redcap-edit-frame\',\'' . $survey_link . '\');"><em class="fa fa-pencil"></em></a>';
                            }
                            $group_discussion .= "</td></tr>";
                        }
                        echo $group_discussion;
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- MODAL EDIT COMMENT-->
        <div class="modal fade" id="hub_comment_and_votes_survey" tabindex="-1" role="dialog" aria-labelledby="Codes">
            <div class="modal-dialog" role="document" style="width: 800px">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Comments and Votes</h4>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" value="0" id="comment_loaded">
                        <iframe class="commentsform" id="redcap-edit-frame" name="redcap-edit-frame" src="" message="E"
                                style="border: none;height: 810px;width: 100%;"></iframe>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

    <?php if ($current_user['harmonist_regperm'] != 1) { ?>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a data-toggle="collapse" href="#collapse_ask">Comment / Ask Question</a>
                </h4>
            </div>
            <?php
            $survey_path = APP_PATH_WEBROOT_FULL . "/surveys/?s=" . $module->escape($pidsArray['SURVEYLINKSOP']) . "&sop_id=" . $module->escape($record) . "&response_person=" . $module->escape($current_user['record_id']) . "&response_region=" . $module->escape($current_user['person_region']) . "&comment_ver=" . $module->escape($sop['sop_status']);
            ?>
            <div id="collapse_ask" class="panel-collapse collapse in" aria-expanded="true">
                <div class="panel-body">
                    <iframe class="commentsform" id="redcap-sop" src="<?= $survey_path ?>" message="C"
                            style="border: none;height: 550px;width: 100%;"></iframe>
                </div>
            </div>
        </div>
        <script>
            $(document).ready(function () {
                Sortable.init();
            });

            $(document).ready(function () {
                $('html,body').scrollTop(0);
                $("html,body").animate({scrollTop: 0}, "slow");
            });
        </script>
    <?php }
   }else{ ?>
        <div class="alert alert-warning fade in col-md-12"><em>Data Request #<?=$record?> is not available at this time.</em></div>
    <?php } ?>
</div>