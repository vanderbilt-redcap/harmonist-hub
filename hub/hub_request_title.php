<?php
namespace Vanderbilt\HarmonistHubExternalModule;

$option = htmlentities($_GET['option'],ENT_QUOTES);
$record = htmlentities($_GET['record'],ENT_QUOTES);

$RecordSetRM = \REDCap::getData($pidsArray['RMANAGER'], 'array', array('request_id' => $record));
$request = $module->escape(ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRM,'')[0]);
if($request !="") {
    $request_type_label = $module->getChoiceLabels('request_type', $pidsArray['RMANAGER']);
    $region_response_status = $module->getChoiceLabels('region_response_status', $pidsArray['RMANAGER']);
    $region_vote_status = $module->getChoiceLabels('region_vote_status', $pidsArray['RMANAGER']);
    $region_review_icon = array("0" => "fa fa-times", "1" => "fa fa-wrench", "2" => "fa fa-check");
    $region_review_icon_text = array("0" => "text-default", "1" => "text-warning", "2" => "text-approved");
    $region_vote_icon = array("1" => "fa fa-thumbs-o-up", "0" => "fa fa-thumbs-o-down", "9" => "fa fa-ban");
    $region_vote_icon_text = array("1" => "text-approved", "0" => "text-error", "9" => "text-default");
    $region_vote_icon_view = array("1" => "fa fa-check", "0" => "fa fa-times", "9" => "fa fa-ban");
    $region_vote_icon_text_view = array("1" => "label label-approved", "0" => "label label-notapproved", "9" => "label label-default");

    $wg_name = "<em>Not specified</em>";
    if (!empty($request['wg_name'])) {
        $wg_name = \REDCap::getData($pidsArray['GROUP'], 'json-array', array('record_id' => $request['wg_name']),array('group_name'))[0]['group_name'];
        if (!empty($request['wg2_name'])) {
            $wg_name .= "; " . \REDCap::getData($pidsArray['GROUP'], 'json-array', array('record_id' => $request['wg2_name']),array('group_name'))[0]['group_name'];
        }
    } else if (!empty($request['wg2_name'])) {
        $wg_name = \REDCap::getData($pidsArray['GROUP'], 'json-array', array('record_id' => $request['wg2_name']),array('group_name'))[0]['group_name'];
    }

    $array_dates = getNumberOfDaysLeftButtonHTML($request['due_d'], $request['region_response_status'][$current_user['person_region']], '', '1');

    $conference_info = "";
    if (!empty($request_type_label[$request['request_type']]) && ($request_type_label[$request['request_type']] == 'Other' || $request_type_label[$request['request_type']] == 'Abstract' || $request_type_label[$request['request_type']] == 'Poster')) {
        $conference_info = $request['request_conf'];
    } else if (empty($request_type_label[$request['request_type']])) {
        $conference_info = "<em>Not specified/Not applicable</em>";
    }

    $concept = "<em>None</em>";
    if (!empty($request['assoc_concept'])) {
        $RecordSetConceptSheets = \REDCap::getData($pidsArray['HARMONIST'], 'array', array('record_id' => $request['assoc_concept']));
        $concept_sheet = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConceptSheets)[0]['concept_id'];
        $concept = '<a href="i'.$module->getUrl('index.php').'&NOAUTH&pid=' . $pidsArray['DATAMODEL'] . '&option=ttl&record=' . $request['assoc_concept'] . '" target="_blank">' . $concept_sheet . '</a>';
    }else if($request['mr_temporary'] != ""){
        $concept = $request['mr_temporary'];
    }

    $request_id = $record;
    if ($option == 'unf' && $record != '') {
        $userid = $current_user['record_id'];

        $RecordSetRM = \REDCap::getData($pidsArray['RMANAGER'], 'array', array('request_id' => $request_id));
        $follow_activity = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRM)[0]['follow_activity'];
        $array_userid = explode(',', $follow_activity);
        $arrayMRmanager = array(array('record_id' => $request_id));

        #UNFOLLOW
        if (($key = array_search($userid, $array_userid)) !== false) {
            unset($array_userid[$key]);
            $string_userid = implode(",", $array_userid);
            $arrayMRmanager[0]['follow_activity'] = $string_userid;
            $results = \Records::saveData($pidsArray['RMANAGER'], 'array', $arrayMRmanager,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
            \Records::addRecordToRecordListCache($pidsArray['RMANAGER'], $request_id,1);
        }
    }
}
?>
<script>
    $(document).ready(function() {
        //To change the text on select
        $(".dropdown-menu-custom li").click(function(){
            var selText = $(this).html();
            $(this).parents('.dropdown').find('.dropdown-toggle').html(selText+' <span class="caret" style="float: right;margin-top:8px"></span>');
        });
        var table = $('#table_request').DataTable({"order": [3, "desc"]});


        var option = <?=json_encode($option)?>;
        var record = <?=json_encode($record)?>;
        if(option == 'unf' && record != ''){
            $('#succMsgContainer_unfollow').show();
        }
    } );
</script>

<div>
    <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;display: none;" id="succMsgContainer_unfollow">You have successfully unfollowed this Request.</div>
    <?php
    if(array_key_exists('message', $_REQUEST)){
        if($_REQUEST['message'] == 'F'){
            ?>
            <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;"
                 id="succMsgContainer">Your Request has been successfully finalized.
            </div>
            <?php
        }else if($_REQUEST['message'] == 'A'){
            ?>
            <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;"
                 id="succMsgContainer">Your comment has been successfully added.
            </div>
            <?php
        }else if($_REQUEST['message'] == 'E'){
            ?>
            <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;"
                 id="succMsgContainer">Your comment has been successfully updated.
            </div>
            <?php
        }else if($_REQUEST['message'] == 'R'){
            ?>
            <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;"
                 id="succMsgContainer">Your Revision File has been successfully updated.
            </div>
            <?php
        }
    }
    ?>
    <div class="backTo">
        <?php
        if($_REQUEST['type'] != "" && $_REQUEST['type'] == 'r'){ ?>
            <a href="<?=$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=mrr'?>">< Back to Rejected Requests Archive</a>
        <?php }else{ ?>
            <a href="<?=$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=hub'?>">< Back to Dashboard</a>
        <?php }
        ?>

    </div>
    <?php if(($request['approval_y'] == '0' || $request['approval_y'] == '9') && !$isAdmin) { ?>
        <div class="alert alert-warning fade in col-md-12"><em>This Request ID is not active.</em></div>
    <?php }else if($request !="") {
            if(($request['approval_y'] == '0' || $request['approval_y'] == '9') && $isAdmin) {?>
                <div class="alert alert-warning fade in col-md-12" style="float: none;"><em>This Request is not active.</em></div>
        <?php   } ?>
    <div class="panel panel-info">
        <div class="panel-heading" id="singleRequestHeader">
            <h2 class="panel-title" style="display: inline-block;padding: 8px 0px 10px;">
                Request #<?=$module->escape($request['request_id']);?> | <?=$module->escape($request_type_label[$request['request_type']]);?> | <?=$module->escape($request['contact_name']);?>
            </h2>
            <?php if($isAdmin){
                $editRequestButton ='';
                $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['RMANAGER'], $request_id, "request", "");
                $editRequest = $module->escape(APP_PATH_WEBROOT_FULL . "/surveys/?s=".$passthru_link['hash']);

                $gotoredcap = APP_PATH_WEBROOT_ALL."DataEntry/record_home.php?pid=".$module->escape($pidsArray['RMANAGER'])."&arm=1&id=".$module->escape($request['request_id']);

                $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['RMANAGER'], $request_id, "admin_review", "");
                $changeApproval = $module->escape(APP_PATH_WEBROOT_FULL . "/surveys/?s=".$passthru_link['hash']);
                ?>
                <div class="btn-group hidden-xs pull-right">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Admin <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a href="#" onclick="$('#hub_view_votes').modal('show');">Edit Votes</a></li>
                        <?php echo $editRequestButton;?>
                        <li><a href="<?=$editRequest?>"target="_blank">Edit Request</a></li>
                        <li><a href="<?=$changeApproval?>"target="_blank">Change Approval</a></li>
                        <?php
                        $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['RMANAGER'], $record, "finalization_of_request", "");
                        $survey_link = $module->escape(APP_PATH_WEBROOT_FULL . "/surveys/?s=".$passthru_link['hash']);
                        echo '<li><a href="#" onclick="editIframeModal(\'hub-modal-finalize\',\'redcap-finalize-frame\',\''.$survey_link.'\');" style="cursor:pointer">Finalize Request</a></li>';
                        ?>
                        <li role="separator" class="divider"></li>
                        <li><a href="<?=$gotoredcap?>" target="_blank">Go to REDCap</a></li>
                    </ul>
                </div>
                <!-- MODAL VIEW VOTES-->
                <div class="modal fade" id="hub_view_votes" tabindex="-1" role="dialog" aria-labelledby="Codes">
                    <div class="modal-dialog" role="document" style="width: 800px">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title">View Votes</h4>
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
                                    $regions = $module->escape(\REDCap::getData($pidsArray['REGIONS'], 'json-array', null,null,null,null,false,false,false,"[showregion_y] = 1"));
                                    ArrayFunctions::array_sort_by_column($regions, 'region_code');

                                    $region_row = '';
                                    foreach ($regions as $region){
                                        if ($region['voteregion_y'] != 0) {
                                            $region_id = $region['record_id'];
                                            $region_time = $request['region_close_ts'][$region_id];
                                            if (!empty($region_time)) {
                                                $region_time = date('Y-m-d H:i', strtotime($region_time));
                                                $class = "";
                                                if (strtotime($request['due_d']) < strtotime($region_time)) {
                                                    $class = "overdue";
                                                }
                                            }
                                            $menu = '<li><span><em>No vote recorded</em></li></span><input type="hidden" value="" class="dropdown_votes" request="' . $request['request_id'] . '" id="' . $region_id . '_none" >';
                                            $selected = '<span><em>No vote recorded</em></span><input type="hidden" value="" class="dropdown_votes" request="' . $request['request_id'] . '" id="' . $region_id . '_none">';
                                            foreach ($region_vote_status as $index => $vote_text) {
                                                $menu .= '<li><span class="fa ' . $module->escape($region_vote_icon_view[$index] . ' ' . $region_vote_icon_text[$index]) . '" aria-hidden="true"></span><span class="' . $module->escape($region_vote_icon_text[$index]) . '"> ' . $module->escape($vote_text) . '</span>';
                                                $menu .= '<input type="hidden" value="' . $module->escape($index) . '" class="dropdown_votes" request="' . $request['request_id'] . '" id="' . $module->escape($region_id . '_' . $index) . '"></li>';
                                                if ($request['region_vote_status'][$region_id] == $index && $request['region_vote_status'][$region_id] != '') {
                                                    $selected = '<span class="fa ' . $module->escape($region_vote_icon_view[$index] . ' ' . $region_vote_icon_text[$index]) . '" aria-hidden="true"></span><span class="' . $module->escape($region_vote_icon_text[$index]) . '"> ' . $module->escape($vote_text) . '</span>';
                                                    $selected .= '<input type="hidden" value="' . $module->escape($index) . '" class="dropdown_votes" request="' . $module->escape($request['request_id']) . '" id="' . $module->escape($region_id . '_' . $index) . '"">';
                                                }
                                            }
                                            $region_row .= '<tr>' .
                                                '<td>' . $module->escape($region['region_code'] . '/' . $region['region_name']) . '</td>' .
                                                '<td>
                                                                <div style="float:left;">
                                                                    <ul class="nav navbar-nav navbar-right">
                                                                        <li class="menu-item dropdown">
                                                                            <a href="#" data-toggle="dropdown" class="dropdown-toggle dropdown-toggle-custom form-control output_select btn-group" id="default-select-value" style="width: 200px;">' . $selected . '<span class="caret" style="float: right;margin-top:8px"></span></a>
                                                                            <ul class="dropdown-menu dropdown-menu-custom output-dropdown-menu" style="width: 200px;">
                                                                                 ' . $menu . '
                                                                            </ul>
                                                                        </li>
                                                                    </ul>
                                                                    </div></td>' .
                                                '<td><span class="' . $module->escape($class) . '">' . $module->escape($region_time) . '</span></td>' .
                                                '</tr>';
                                        }
                                    }
                                    echo $region_row;
                                    ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                <?php
                                $response_pi_level = 0;
                                if($current_user['harmonist_regperm'] == '3'){
                                    $response_pi_level = 1;
                                }
                                ?>
                                <button type="button" class="btn btn-default btn-save" onclick='save_votes(<?=json_encode($current_user['record_id'])?>,<?=json_encode($current_user['person_region'])?>,<?=json_encode($response_pi_level)?>,<?=json_encode($module->getUrl('hub/hub_request_admin_vote_AJAX.php').'&NOAUTH')?>)' data-dismiss="modal">Save</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MODAL FINALIZE-->
                <div class="modal fade" id="hub-modal-finalize" tabindex="-1" role="dialog" aria-labelledby="Codes">
                    <div class="modal-dialog" role="document" style="width: 800px">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title">Finalize Request</h4>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" value="0" id="comment_loaded_finalize">
                                <iframe class="commentsform" id="redcap-finalize-frame" name="redcap-finalize-frame" message="F" src="" style="border: none;height: 810px;width: 100%;"></iframe>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php }
                $follow_padding = "";
                if($isAdmin){
                    $follow_padding = 'style="padding-right: 10px"';
                }
            ?>
            <div class="pull-right" id="btn_follow" <?=$follow_padding?>>
                <?php
                $follow_option = '1';
                $follow_class = 'btn-default';
                $follow_icon_class ='fa fa-plus-square';
                $follow_text = "Follow Activity";
                if($request['follow_activity'] != '' && $option != 'unf'){
                    $array_userid = explode(',',$request['follow_activity']);
                    if(in_array($current_user['record_id'],$array_userid)){
                        $follow_option = '0';
                        $follow_class = 'btn-primary';
                        $follow_icon_class ='fa fa-check-square';
                        $follow_text = "Following";
                    }
                }
                ?>
                <button onclick="follow_activity('<?=$follow_option?>','<?=$current_user['record_id']?>','<?=$request['request_id']?>','<?=$module->getUrl('hub/hub_request_follow_activity_AJAX.php').'&NOAUTH'?>')" class="btn <?=$follow_class?> actionbutton"><i class="<?=$follow_icon_class?>"></i> <span class="hidden-xs"><?=$follow_text?></span></button>
            </div>
        </div>

        <div class="panel-body">
            <?php if($request['finalize_y'] != ""){
                $request_finalize_y_label = $module->getChoiceLabels('finalize_y', $pidsArray['RMANAGER']);

                $finalize_date = "";
                if($request['final_d'] != ""){
                    $finalize_date = " on ".$request['final_d'];
                }
                ?>
            <div class="alert alert-warning fade in col-md-12" id="succMsgContainer_stay">Request <?=$request_finalize_y_label[$request['finalize_y']]?><?=$finalize_date?>.</div>
            <?php }?>
            <h4><?=$request['request_title'];?></h4>
            <div class="row request">
                <div class="col-md-12"><strong>Type:</strong> <?=$request_type_label[$request['request_type']];?> </div>
            </div>
            <div class="row request">
                <div class="col-md-8 col-sm-12"><strong>Contact:</strong> <?=$request['contact_name'];?> (<a href="mailto:<?=$request['contact_email'];?>"><?=$request['contact_email'];?></a>) </div>
                <div class="col-md-4 hidden-sm hidden-xs"><strong>Due: </strong><?=$array_dates['text']?> <?=$array_dates['button']?></div>
            </div>
            <div class="row request">
                <div class="col-md-8 col-sm-12"><strong>Concept:</strong> <?=$concept;?></div>
                <div class="col-md-4 hidden-sm hidden-xs"><strong>Review: </strong><span class="<?=$region_review_icon_text[$request['region_response_status'][$current_user['person_region']]]?>"><?=$region_response_status[$request['region_response_status'][$current_user['person_region']]]?> <i class="<?=$region_review_icon[$request['region_response_status'][$current_user['person_region']]]?>" aria-hidden="true"></i></span></div>
            </div>
            <div class="row request">
                <div class="col-md-8 col-sm-12"><strong>Working Group:</strong> <?=$wg_name;?></div>
                <?php if(($settings['vote_visibility'] == '1' || $settings['vote_visibility'] == '') || ($settings['vote_visibility'] == '2' && $current_user['harmonist_regperm'] == '3')){
                    $region_id = $current_user['person_region'];
                    $vote = empty($region_vote_status[$request['region_vote_status'][$region_id]])?"<em>No vote recorded</em>":$region_vote_status[$request['region_vote_status'][$region_id]];
                ?>
                <div class="col-md-4 hidden-sm hidden-xs"><strong>Vote: </strong><span class="<?=$region_vote_icon_text[$request['region_vote_status'][$region_id]]?>"><?=$vote?> <i class="<?=$region_vote_icon_view[$request['region_vote_status'][$region_id]]?>" aria-hidden="true"></i></span></div>
           <?php } ?>
            </div>
            <?php if(!empty($conference_info)){ ?>
                <div class="row request">
                    <div class="col-md-12">
                        <strong>Conference:</strong>  <?=$conference_info;?>
                    </div>
                </div>
            <?php } ?>
            <div class="row request hidden-md hidden-lg">
                <div class="col-md-8"><strong>Due: </strong><?=$array_dates['text']?> <?=$array_dates['button']?></div>
            </div>
            <div class="row request hidden-md hidden-lg">
                <div class="col-md-8"><strong>Review: </strong><span class="<?=$region_review_icon_text[$request['region_response_status'][$current_user['person_region']]]?>"><?=$region_response_status[$request['region_response_status'][$current_user['person_region']]]?> <i class="<?=$region_review_icon[$request['region_response_status'][$current_user['person_region']]]?>" aria-hidden="true"></i></span></div>
            </div>
            <?php if(($settings['vote_visibility'] == '1' || $settings['vote_visibility'] == '') || ($settings['vote_visibility'] == '2' && $current_user['harmonist_regperm'] == '3')){?>
            <div class="row request hidden-md hidden-lg">
                <div class="col-md-8"><strong>Vote: </strong><span class="<?=$region_vote_icon_text[$request['region_vote_status'][$region_id]]?>"><?=$vote?> <i class="<?=$region_vote_icon_view[$request['region_vote_status'][$region_id]]?>" aria-hidden="true"></i></span></div>
            </div>
            <?php } ?>

            <div class="row request">
                <div class="col-md-12 col-sm-12"><strong>Request: </strong><br>
                    <?=nl2br($request['request_description']);?>
                </div>
            </div>
            <div class="row request">
                <div class="col-md-12"><strong>Admin Notes: </strong><br>
                    <?=empty($request['admin_review_notes'])?"<em>None</em>":nl2br($request['admin_review_notes']);?>
                </div>
            </div>
            <?php //Admin or Author
            if($isAdmin || $request['contactperson_id'] == $current_user['record_id'] ){ ?>
                <div class="row request">
                    <div class="col-md-12" style="padding-bottom: 8px">
                        <button type="button" class="btn btn-xs btn-default dropdown-toggle" style="float:right" onclick="$('#upload_author_revision').modal('show');"><span class="glyphicon glyphicon-arrow-up"></span> Upload Author Revision</button>
                    </div>
                </div>

                <!-- MODAL ADD REVISION-->
                <div class="modal fade" id="upload_author_revision" tabindex="-1" role="dialog" aria-labelledby="Codes">
                    <div class="modal-dialog" role="document" style="width: 800px">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title">Upload Author Revision</h4>
                            </div>
                            <div class="modal-body">
                                <?php
                                $survey_path = APP_PATH_WEBROOT_FULL."/surveys/?s=".$module->escape($pidsArray['SURVEYLINK'])."&author_revision_y=1"."&request_id=".$module->escape($record)."&response_person=".$module->escape($current_user['record_id'])."&response_region=".$module->escape($current_user['person_region'])."&response_pi_level=".$module->escape($response_pi_level)."&modal=modal";
                                ?>
                                <iframe class="commentsform" id="redcap-author-revision" name="redcap-author-revision" message="R" src="<?=$survey_path?>" style="border: none;height: 810px;width: 100%;"></iframe>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php }else{
                echo "<br>";
            } ?>

            <div class="row request" style="margin-top:0">
                <div class="col-md-12">
                    <table class="table table-hover approved-study-docs table-font-size" data-sortable id="table_request">
                        <thead>
                        <tr><th>File Name</th><th>Description</th><th>Uploaded By</th><th data-sorted="true" data-sorted-direction="descending">On</th><th>Size</th></tr>
                        </thead>
                        <tbody>
                        <?php
                        $request_time = "";
                        if(!empty($request['requestopen_ts'])){
                            $date = new \DateTime($request['requestopen_ts']);
                            $date->modify("+1 hours");
                            $request_time = $date->format("Y-m-d H:i:s");
                        }

                        for($i = 1; $i<7 ; $i++){
                            if(!empty($request['extra_file'.$i])){
                                echo "<tr>".getFileRow($module,$request['extra_file'.$i], $request['contact_name'],"Original", $request_time,$secret_key,$secret_iv,$current_user['record_id'],"")."</tr>";
                            }
                        }

                        if(!empty($request['request_file'])) {
                            echo "<tr class='info'>" . getFileRow($module,$request['request_file'], $request['contact_name'], "Original", $request_time, $secret_key, $secret_iv, $current_user['record_id'], "");
                        }

                        $parameter = '[request_id] = "'.$request['request_id'].'"';
                        $comments = \REDCap::getData($pidsArray['COMMENTSVOTES'], 'array', null, null, null, null, false, false, false, $parameter, false);
                        if(!empty($comments))
                            krsort($comments);

                        $most_recent_file = \REDCap::getData($pidsArray['COMMENTSVOTES'], 'json-array', array('request_id' => $request['request_id']),null,null,null,false,false,false,"[responsecomplete_ts] <> '' and [revised_file] <> ''");
                        if(is_array($most_recent_file) && !empty($most_recent_file)) {
                            foreach ($most_recent_file as $k => $v) {
                                if (array_key_exists('responsecomplete_ts',$v) && $v['responsecomplete_ts'] > $max) {
                                    $max = $v['responsecomplete_ts'];
                                    $newest_record = $v['record_id'];
                                }
                            }
                        }

                        if(!empty($request['author_doc'])){
                            echo "<tr class='author_doc'>".getFileRow($module,$request['author_doc'], $request['contact_name'], "Final", "",$secret_key,$secret_iv,$current_user['record_id'],"")."</tr>";
                        }

                        if(!empty($comments)){
                            foreach ($comments as $comment_record) {
                                foreach ($comment_record as $comment) {
                                    $i++;
                                    $text = "";
                                    $revised_class = "";
                                    if ($comment['author_revision_y'] == '1') {
                                        $text = "Revision ".$comment['revision_counter'];
                                        $revised_class = "info";
                                    } else {
                                        $text = "Comments";
                                    }

                                    if($comment['record_id'] == $newest_record){
                                        $revised_class = 'last_file';
                                    }

                                    $people = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array('record_id' => $comment['response_person']),array('firstname','lastname','email'))[0];
                                    $name = trim($people['firstname'].' '.$people['lastname']);

                                    $comment_time ="";
                                    if(!empty($comment['responsecomplete_ts'])){
                                        $dateComment = new \DateTime($comment['responsecomplete_ts']);
                                        $dateComment->modify("+1 hours");
                                        $comment_time = $dateComment->format("Y-m-d H:i:s");
                                    }

                                    $gd_files = "";
                                    if(!empty($comment['revised_file'])){
                                        echo "<tr class='".$revised_class."'>" . getFileRow($module,$comment['revised_file'], $name, $text, $comment_time,$secret_key,$secret_iv,$current_user['record_id'],"") . "</tr>";
                                        if(!empty($comment['comments'])){
                                            $gd_files .= "<div style='padding-top:10px'>";
                                        }
                                        $gd_files .= getFileLink($module, $pidsArray['PROJECTS'], $comment['revised_file'],'','',$secret_key,$secret_iv,$current_user['record_id'],"")."</div>";
                                    }
                                    if(!empty($comment['extra_revfile1'])){
                                        echo "<tr class='".$revised_class."'>" . getFileRow($module, $comment['extra_revfile1'], $name, $text, $comment_time,$secret_key,$secret_iv,$current_user['record_id'],"") . "</tr>";
                                        $gd_files .= "<div style='padding-top:10px'>".getFileLink($module, $pidsArray['PROJECTS'], $comment['extra_revfile1'],'','',$secret_key,$secret_iv,$current_user['record_id'],"")."</div>";
                                    }
                                    if(!empty($comment['extra_revfile2'])){
                                        echo "<tr class='".$revised_class."'>" . getFileRow($module, $comment['extra_revfile2'], $name, $text, $comment_time,$secret_key,$secret_iv,$current_user['record_id'],"") . "</tr>";
                                        $gd_files .= "<div style='padding-top:10px'>".getFileLink($module, $pidsArray['PROJECTS'], $comment['extra_revfile2'],'','',$secret_key,$secret_iv,$current_user['record_id'],"")."</div>";
                                    }

                                    /*** GROUP DISCUSION ***/
                                    $text = "";
                                    if ($comment['author_revision_y'] == '1' && $comment['revision_counter'] != '') {
                                        $text = "<div class='request_revision_text'>revision ".$comment['revision_counter']."</div>";
                                    }
                                    $comment_vote = "";
                                    if($comment['pi_vote'] != ''){
                                        if ($comment['pi_vote'] == "1") {
                                            //Approved
                                            $comment_vote = '<div style="padding-bottom:10px"><span class="label label-approved" title="Approved"><i class="fa fa-check" aria-hidden="true"></i></span> <span class="'.$region_vote_icon_text[$comment['pi_vote']].'" style="vertical-align: -1.5px;">Approved</span></div>';
                                        } else if ($comment['pi_vote'] == "0") {
                                            //Not Approved
                                            $comment_vote = '<div style="padding-bottom:10px"><span class="label label-notapproved" title="Not Approved"><i class="fa fa-times" aria-hidden="true"></i></span> <span class="'.$region_vote_icon_text[$comment['pi_vote']].'" style="vertical-align: -1.5px;">Not Approved</span></div>';
                                        } else if ($comment['pi_vote'] == "9") {
                                            //Complete
                                            $comment_vote = '<div style="padding-bottom:10px"><span class="label label-default" title="Abstained"><i class="fa fa-ban" aria-hidden="true"></i></span> <span class="'.$region_vote_icon_text[$comment['pi_vote']].'" style="vertical-align: -1.5px;">Abstained</span></div>';
                                        } else {
                                            $comment_vote = '<div style="padding-bottom:10px"><span class="label label-default" title="Abstained"><i class="fa fa-ban" aria-hidden="true"></i></span> <span class="'.$region_vote_icon_text[$comment['pi_vote']].'" style="vertical-align: -1.5px;">Abstained</span></div>';
                                        }
                                    }

                                    $region_code = $comment['response_regioncode'];
                                    if($region_code == ""){
                                        $region_code = \REDCap::getData($pidsArray['REGIONS'], 'json-array', array('record_id' => $comment['response_region']),array('region_code'))[0]['region_code'];
                                    }

                                    $writing_group = "";
                                    if($comment['writing_group'] != ""){
                                        $writing_group = "<div style='padding-top:10px'><em>Writing group nominee(s): ".$comment['writing_group']."</em></div>";
                                    }

                                    $group_discussion .= "<tr>".
                                        "<td style='width:20%'><a href='mailto:".$people['email']."'>".$name."</a> (".$region_code.")<br/>".$comment_time.$text."</td>".
                                        "<td style='width:75%'>".$comment_vote."<div>".nl2br($comment['comments'])."</div>".$writing_group.$gd_files."</td>".
                                        "<td  style='width:5%'>";
                                    if($comment['response_person'] == $current_user['record_id']){
                                        $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['COMMENTSVOTES'], $comment['record_id'], "comments_and_votes", "");
                                        $survey_link = $module->escape(APP_PATH_WEBROOT_FULL . "/surveys/?s=".$passthru_link['hash']);
                                        $group_discussion .= '<button class="btn btn-default open-codesModal" onclick="editIframeModal(\'hub_comment_and_votes_survey\',\'redcap-edit-frame\',\''.$survey_link.'\');"><em class="fa fa-pencil"></em></button>';
                                    }
                                    $group_discussion .= "</td></tr>";
                                }
                            }
                        }

                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title">
                <a data-toggle="collapse" href="#collapse_comment">Comments to Date</a>
            </h4>
        </div>
        <div id="collapse_comment" class="panel-collapse collapse in" aria-expanded="true">
            <table class="table table-hover table-bordered table-list table-font-size">
                <thead>
                <tr>
                    <th class="comments-table">Name / Time</th>
                    <th>Comments</th>
                    <th style="text-align: center"><em class="fa fa-cog"></em></th>
                </tr>
                </thead>
                <tbody>
                <?php echo $group_discussion;?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- MODAL EDIT COMMENT-->
    <div class="modal fade" id="hub_comment_and_votes_survey" tabindex="-1" role="dialog" aria-labelledby="Codes">
        <div class="modal-dialog" role="document" style="width: 800px">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Comments and Votes</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" value="0" id="comment_loaded">
                    <iframe class="commentsform" id="redcap-edit-frame" name="redcap-edit-frame" message="E" src="" style="border: none;height: 810px;width: 100%;"></iframe>
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
                <a data-toggle="collapse" href="#collapse_review">Review / Comment</a> <span class="glyphicon glyphicon-info-sign" data-toggle="tooltip" style="cursor: pointer;" data-container='body' title="If you have previously submitted a vote for this request, we will use the most recent vote as your final selection. All comments will be archived and saved"></span>
            </h4>
        </div>
        <?php
            $response_pi_level = 0;
            if($current_user['harmonist_regperm'] == '3'){
                $response_pi_level = 1;
            }
            $survey_path = APP_PATH_WEBROOT_FULL."surveys/?s=".$module->escape($pidsArray['SURVEYLINK'])."&request_id=".$module->escape($record)."&response_person=".$module->escape($current_user['record_id'])."&response_region=".$module->escape($current_user['person_region'])."&response_pi_level=".$module->escape($response_pi_level)."&modal=modal";
        ?>
        <div id="collapse_review" class="panel-collapse collapse in" aria-expanded="true">
            <div class="panel-body">
                <iframe class="commentsform" id="redcap-frame" src="<?=$survey_path?>" stayrequest_y="<?=($current_user['stayrequest_y___1']=="")?"0":"1"?>" message="A" style="border: none;height: 810px;width: 100%;"></iframe>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            Sortable.init();
            $('html,body').scrollTop(0);
            $("html,body").animate({ scrollTop: 0 }, "slow");
        });
    </script>
    <?php }?>

    <?php }else{?>
        <div class="alert alert-warning fade in col-md-12"><em>Request #<?=$record?> is not available at this time.</em></div>
    <?php }?>
</div>
