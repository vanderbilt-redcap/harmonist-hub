<?php
namespace Vanderbilt\HarmonistHubExternalModule;
?>
<script>
    $(document).ready(function() {
        Sortable.init();
    } );
</script>
<div class="container">
    <?php
    if(array_key_exists('message', $_REQUEST) && ($_REQUEST['message'] == 'F')){
        ?>
        <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;"
             id="succMsgContainer">Your Request has been successfully finalized.
        </div>
        <?php
    }else if(array_key_exists('message', $_REQUEST) && ($_REQUEST['message'] == 'D')){
        ?>
        <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;"
             id="succMsgContainer">Your Request documents have been successfully uploaded.
        </div>
        <?php
    }else if(array_key_exists('message', $_REQUEST) && ($_REQUEST['message'] == 'M')){
        ?>
        <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;"
             id="succMsgContainer">You have successfully created a new concept.
        </div>
        <?php
    }else if(array_key_exists('message', $_REQUEST) && ($_REQUEST['message'] == 'S')){
        ?>
        <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;"
             id="succMsgContainer">A deadline had been set.
        </div>
        <?php
    }else if(array_key_exists('message', $_REQUEST) && ($_REQUEST['message'] == 'P')){
        ?>
        <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;"
             id="succMsgContainer">The publications json has been updated.
        </div>
        <?php
    }
    ?>
    <h3>Admin Page</h3>
    <p class="hub-title"><?=$settings['hub_admin_text']?></p>
    <div>
        <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;display: none;" id="succMsgContainer">Your edits have been saved.</div>
    </div>
    <?php

    $requests_labels = array(0 => "Concepts",1 => "Abstracts",2 => "Manuscripts",3 => "Fast Track",4 => "Poster", 5=>"Other");
    $requests_colors = array(0 => "#337ab7",1 => "#00b386",2 => "#f0ad4e",3 => "#ff9966",4 => "#5bc0de",5 => "#777");
    $abstracts_publications_badge_text = array("1" => "badge-concept-text", "2" => "badge-abstract-text", "3" => "badge-manuscript-text", "4" => "badge-poster-text", "5" => "badge-data-text", "99" => "badge-other-text");

    ?>
    <div class="pull-right">
        <p><a href="<?=$module->getUrl("index.php")."&NOAUTH&pid=".$pidsArray['PROJECTS']."&option=mts"?>">View Hub Statistics</a> | <a href="<?=$module->getUrl("index.php?pid=".$pidsArray['PROJECTS']."&option=mra&type=a")?>">View Archived Requests</a></p>
    </div>
</div>
<div class="container">
    <div class="panel panel-default" style="margin-bottom: 20px">
        <div class="panel-heading">
            <h3 class="panel-title">
                <a data-toggle="collapse" href="#collapse_req_admin">New Requests for Admin Review</a>
            </h3>
        </div>

        <div id="collapse_req_admin" class="table-responsive panel-collapse collapse in" aria-expanded="true">
            <table class="table table_requests sortable-theme-bootstrap" data-sortable>
                <?php
                $any_request_found = false;
                if($request_admin != "") {?>
                    <colgroup>
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                    </colgroup>

                    <thead>
                    <tr>
                        <th class="request_grid_dued sorted_class" data-sorted="true" data-sorted-direction="descending" style="width: 232px">Date Submitted</th>
                        <th class=" sorted_class" style="width: 148px"><span style="display:block;">Request</span><span>Type</span></th>
                        <th class=" sorted_class" style="width: 150px"><span style="display:block">Submitted</span><span>By</span></th>
                        <th class="request_grid_title sorted_class hidden-xs" style="width: 461px;">Title</th>
                        <th class="request_grid_actions" data-sortable="false" style="width: 146px">Actions</th>
                    </tr>
                    </thead>

                    <?php
                    foreach ($request_admin as $req) {
                        if ($req['approval_y'] == '' || $req['approval_y'] == null) {
                            $any_request_found = true;
                            $person_region_code = \REDCap::getData($pidsArray['REGIONS'], 'json-array', array('record_id' => $req['contact_region']),array('region_code'))[0]['region_code'];
                            $region = "";
                            if($person_region_code != ""){
                                $region = " (".$person_region_code.")";
                            }

                            $check_submission_text = ($settings['admintext1']=="")? $default_values_settings['admintext1']:$settings['admintext1'];
                            $set_deadline_text = ($settings['admintext2']=="")? $default_values_settings['admintext2']:$settings['admintext2'];
                            $concept_link = \Vanderbilt\HarmonistHubExternalModule\getReqAssocConceptLink($module, $pidsArray, $req['assoc_concept'], "");
                            if($concept_link == ""){
                                $concept_link = $req['mr_temporary'];
                            }
                            echo '<tr>
                                    <td><span class="nowrap">'.$req['requestopen_ts'].'</span></td>
                                    <td><strong>'.htmlspecialchars($request_type_label[$req['request_type']],ENT_QUOTES).'</strong><br>'.filter_tags($concept_link).'</td>
                                    <td><a href="mailto:'.$req['contact_email'].'">'.$req['contact_name'].'</a>'.htmlspecialchars($region,ENT_QUOTES).'</td>
                                    <td class="hidden-xs"><a href="'.$module->getUrl('index.php').'&NOAUTH&option=hub&record='.$req['request_id'].'" target="_blank">'.htmlspecialchars($req['request_title'],ENT_QUOTES).'</a></td>';

                            $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['RMANAGER'], $req['request_id'], "request", "");
                            $survey_link = APP_PATH_WEBROOT_FULL . "/surveys/?s=".$module->escape($passthru_link['hash'])."&modal=modal";
                            echo '<td><div><a href="#" onclick="editIframeModal(\'hub_process_survey\',\'redcap-edit-frame-admin\',\''.$survey_link.'\',\''.$check_submission_text.'\');" class="btn btn-primary btn-xs actionbutton"><i class="fa fa-eye fa-fw" aria-hidden="true"></i> '.$check_submission_text.'</a></div>';

                            $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['RMANAGER'], $req['request_id'], "admin_review", "");
                            $survey_link = APP_PATH_WEBROOT_FULL . "/surveys/?s=".$module->escape($passthru_link['hash'])."&modal=modal";
                            echo '<div><a href="#" onclick="editIframeModal(\'hub_process_survey\',\'redcap-edit-frame-admin\',\''.$survey_link.'\',\''.$set_deadline_text.'\');" class="btn btn-success btn-xs open-codesModal" style="margin-top: 7px;"><i class="fa fa-calendar fa-fw" aria-hidden="true"></i> '.$set_deadline_text.'</a></div></td>';
                        }
                    }
                    if(!$any_request_found){
                        ?>
                        <tbody>
                        <tr>
                            <td><span><em>No requests available</em></span></td>
                        </tr>
                        </tbody>
                        <?php
                    }
                }else{?>
                    <tbody>
                    <tr>
                        <td><span><em>No requests available</em></span></td>
                    </tr>
                    </tbody>
                <?php }?>
            </table>
        </div>
    </div>
    <div class="panel panel-default" style="margin-bottom: 20px">
        <div class="panel-heading">
            <h3 class="panel-title">
                <a data-toggle="collapse" href="#collapse1">Request Finalization Workflow</a>
            </h3>
        </div>

        <div id="collapse1" class="table-responsive panel-collapse collapse in" aria-expanded="true">
            <table class="table table_requests sortable-theme-bootstrap admin-table" data-sortable>
                <?php
                $RecordSetRM = \REDCap::getData($pidsArray['RMANAGER'], 'array', null,null,null,null,false,false,false,"[approval_y] = '1' AND [detected_complete(1)] = '1'");
                $requests = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRM);
                ArrayFunctions::array_sort_by_column($requests,"due_d");
                $any_request_found = false;
                if($requests != "") {
                    echo '<colgroup>
                            <col>
                            <col>
                            <col>
                            <col>
                            <col>
                         </colgroup>';

                    echo '<thead>'.
                        '<tr>'.
                        '<th class="request_grid_dued sorted_class" data-sorted="true" data-sorted-direction="descending" style="">Request</th>'.
                        '<th class="request_grid_title sorted_class hidden-xs" style="">Title</th>'.
                        '<th class="request_grid_actions" data-sortable="false" style=""></th>'.
                        '<th class="request_grid_actions" data-sortable="false" style=""></th>'.
                        '<th class="request_grid_actions" data-sortable="false" style=""></th>'.
                        '</tr></thead>';

                    foreach ($requests as $req) {
                        if(($req['finalize_y'] != "" && ($req['request_type'] != '1' && $req['request_type'] != '5')) || ($req['finalize_y'] == "2" && ($req['request_type'] == '1' || $req['request_type'] == '5')) || ($req['mr_assigned'] != "" && $req['finalconcept_doc'] != "" && $req['finalconcept_pdf'] != "")) {
                           //Do not show request
                        }else{
                            $any_request_found = true;
                            $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['RMANAGER'], $req['request_id'], "finalization_of_request", "");
                            $survey_link = APP_PATH_WEBROOT_FULL . "/surveys/?s=".$module->escape($passthru_link['hash'])."&modal=modal";
                            $passthru_link_doc = $module->resetSurveyAndGetCodes($pidsArray['RMANAGER'], $req['request_id'], "final_docs_request_survey", "");
                            $survey_link_doc = APP_PATH_WEBROOT_FULL . "/surveys/?s=".$module->escape($passthru_link_doc['hash'])."&modal=modal";
                            $passthru_link_mr = $module->resetSurveyAndGetCodes($pidsArray['RMANAGER'], $req['request_id'], "tracking_number_assignment_survey", "");
                            $survey_link_mr = APP_PATH_WEBROOT_FULL . "/surveys/?s=".$module->escape($passthru_link_mr['hash'])."&modal=modal";

                            $req_type = \Vanderbilt\HarmonistHubExternalModule\getReqAssocConceptLink($module, $pidsArray, $req['assoc_concept'], "");
                            if($req_type == "" && $req['mr_temporary'] != ""){
                                $req_type = $req['mr_temporary'];
                            }
                            $array_dates = \Vanderbilt\HarmonistHubExternalModule\getNumberOfDaysLeftButtonHTML($req['due_d'], '', 'float:right', '0');

                            $person_region_code = \REDCap::getData($pidsArray['REGIONS'], 'json-array', array('record_id' => $req['contact_region']),array('region_code'))[0]['region_code'];
                            $region = "";
                            if ($person_region_code != "") {
                                $region = " (" . $person_region_code . ")";
                            }

                            $finalize_review_text = ($settings['admintext3']=="")? $default_values_settings['admintext3']:$settings['admintext3'];
                            $request_docs_text = ($settings['admintext4']=="")? $default_values_settings['admintext4']:$settings['admintext4'];
                            $assign_mr_text = ($settings['admintext5']=="")? $default_values_settings['admintext5']:$settings['admintext5'];

                            if ($req['finalize_y'] != "") {
                                $finalize_review = '<a href="#" onclick="editIframeModal(\'hub-modal-finalize\',\'redcap-finalize-frame\',\'' . $survey_link . '\',\'' . $finalize_review_text . '\');" class="btn btn-default btn-xs open-codesModal"><span class="fa fa-check-square text-approved"></span> '.htmlspecialchars($finalize_review_text,ENT_QUOTES).'</a>';
                            } else {
                                $finalize_review = '<a href="#" onclick="editIframeModal(\'hub-modal-finalize\',\'redcap-finalize-frame\',\'' . $survey_link . '\',\'' . $finalize_review_text . '\');" class="btn btn-secondary btn-xs open-codesModal"><i class="fa fa-legal fa-fw" aria-hidden="true"></i> '.htmlspecialchars($finalize_review_text,ENT_QUOTES).'</a>';
                            }
                            if($req['request_type'] == '1' || $req['request_type'] == '5'){
                                if ($req['author_doc'] != "") {
                                    $request_docs = '<a href="#" onclick="editIframeModal(\'hub-modal-doc\',\'redcap-doc-frame\',\'' . $survey_link_doc . '\',\'' . $request_docs_text . '\');" class="btn btn-default btn-xs open-codesModal"><span class="fa fa-check-square text-approved"></span> '.htmlspecialchars($request_docs_text,ENT_QUOTES).'</a>';
                                } else {
                                    $request_docs = '<a href="#" onclick="editIframeModal(\'hub-modal-doc\',\'redcap-doc-frame\',\'' . $survey_link_doc . '\',\'' . $request_docs_text . '\');" class="btn btn-secondary btn-xs open-codesModal"><i class="fa fa-file fa-fw" aria-hidden="true"></i> '.htmlspecialchars($request_docs_text,ENT_QUOTES).'</a>';
                                }
                                if ($req['mr_assigned'] != "" && $req['finalconcept_doc'] != "" && $req['finalconcept_pdf'] != "") {
                                    $assign_mr = '<a href="#" onclick="editIframeModal(\'hub-modal-mr\',\'redcap-mr-frame\',\'' . $survey_link_mr . '\',\'' . $assign_mr_text . '\');" class="btn btn-default btn-xs open-codesModal"><span class="fa fa-check-square text-approved"></span> '.htmlspecialchars($assign_mr_text,ENT_QUOTES).'</a>';
                                } else {
                                    $assign_mr = '<a href="#" onclick="editIframeModal(\'hub-modal-mr\',\'redcap-mr-frame\',\'' . $survey_link_mr . '\',\'' . $assign_mr_text . '\');" class="btn btn-secondary btn-xs open-codesModal"><i class="fa fa-hashtag fa-fw" aria-hidden="true"></i> '.htmlspecialchars($assign_mr_text,ENT_QUOTES).'</a>';
                                }
                            }else{
                                $request_docs = "";
                                $assign_mr = "";
                            }

                            echo '<tr>' .
                                '<td><div><strong><span class="fa fa-user fa-square  ' . htmlspecialchars($abstracts_publications_badge_text[$req['request_type']],ENT_QUOTES) . '"></span> ' . htmlspecialchars($request_type_label[$req['request_type']],ENT_QUOTES) . '</strong> (' . $req_type . ')</div>' .
                                '<div style="padding-top:5px;">by <a href="mailto:' . $req['contact_email'] . '">' . $req['contact_name'] . '</a>' . $region . '</div>' .
                                '<div style="padding-top:5px;">Due on: ' . filter_tags($array_dates['text']) . '</div></td>' .
                                '<td><a href="'.$module->getUrl("index.php")."&NOAUTH&pid=".$pidsArray['PROJECTS']."&option=hub&record=" . $req['request_id'] . '" target="_blank">' . htmlspecialchars($req['request_title'],ENT_QUOTES) . '</a></td>' .
                                '<td><div>' . $finalize_review . '</div></td>' .
                                '<td><div>' . $request_docs. '</div></td>' .
                                '<td><div>' . $assign_mr . '</div></td>' .
                                '</tr>';
                        }
                    }
                    if(!$any_request_found){
                        ?>
                        <tbody>
                        <tr>
                            <td><span><em>No requests available</em></span></td>
                        </tr>
                        </tbody>
                        <?php
                    }
                }else{?>
                    <tbody>
                    <tr>
                        <td><span><em>No requests available</em></span></td>
                    </tr>
                    </tbody>
                <?php }?>
            </table>
        </div>
    </div>
    <div class="panel panel-default" style="margin-bottom: 20px">
        <div class="panel-heading">
            <h3 class="panel-title">
                <a data-toggle="collapse" href="#collapse_req_finalized">Recently Completed Requests</a>
            </h3>
        </div>

        <div id="collapse_req_finalized" class="table-responsive panel-collapse collapse in" aria-expanded="true">
            <table class="table table_requests sortable-theme-bootstrap" data-sortable>
                <?php
                $RecordSetRM = \REDCap::getData($pidsArray['RMANAGER'], 'array', null,null,null,null,false,false,false,"[finalize_y] = '1' AND [approval_y] = '1'");
                $requests = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRM);
                ArrayFunctions::array_sort_by_column($requests,"due_d");
                $any_request_found = false;
                if($requests != "") {
                    echo '<colgroup>
                            <col>
                            <col>
                            <col>
                            <col>
                            <col>
                         </colgroup>';

                    echo '<thead>'.
                        '<tr>'.
                        '<th class="request_grid_dued sorted_class" data-sorted="true" data-sorted-direction="descending" style="width: 232px">Request</th>'.
                        '<th class="request_grid_title sorted_class hidden-xs" style="width: 461px;">Title</th>'.
                        '<th class="request_grid_actions" data-sortable="false" style="width: 146px"></th>'.
                        '<th class="request_grid_actions" data-sortable="false" style="width: 146px"></th>'.
                        '<th class="request_grid_actions" data-sortable="false" style="width: 146px"></th>'.
                        '</tr></thead>';

                    $extra_days = ' + ' . $settings['recentfinalreq_expiration'] . " days";
                    foreach ($requests as $req) {
                        $expire_date = date('Y-m-d', strtotime($req['workflowcomplete_d'] . $extra_days));
                        if($req['workflowcomplete_d'] != "" && strtotime ($expire_date) >= strtotime(date('Y-m-d'))){
                            $any_request_found = true;
                            $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['RMANAGER'], $req['request_id'], "finalization_of_request", "");
                            $survey_link = APP_PATH_WEBROOT_FULL . "/surveys/?s=".$module->escape($passthru_link['hash'])."&modal=modal";
                            $passthru_link_doc = $module->resetSurveyAndGetCodes($pidsArray['RMANAGER'], $req['request_id'], "final_docs_request_survey", "");
                            $survey_link_doc = APP_PATH_WEBROOT_FULL . "/surveys/?s=".$module->escape($passthru_link_doc['hash'])."&modal=modal";
                            $passthru_link_mr = $module->resetSurveyAndGetCodes($pidsArray['RMANAGER'], $req['request_id'], "tracking_number_assignment_survey", "");
                            $survey_link_mr = APP_PATH_WEBROOT_FULL . "/surveys/?s=".$module->escape($passthru_link_mr['hash'])."&modal=modal";

                            $req_type = \Vanderbilt\HarmonistHubExternalModule\getReqAssocConceptLink($module, $pidsArray, $req['assoc_concept'], "");
                            if($req_type == "" && $req['mr_temporary'] != ""){
                                $req_type = $req['mr_temporary'];
                            }

                            $person_region_code = \REDCap::getData($pidsArray['REGIONS'], 'json-array', array('record_id' => $req['contact_region']),array('region_code'))[0]['region_code'];
                            $region = "";
                            if ($person_region_code != "") {
                                $region = " (" . $person_region_code . ")";
                            }

                            $finalize_review_text = ($settings['admintext3']=="")? $default_values_settings['admintext3']:$settings['admintext3'];
                            $request_docs_text = ($settings['admintext4']=="")? $default_values_settings['admintext4']:$settings['admintext4'];
                            $assign_mr_text = ($settings['admintext5']=="")? $default_values_settings['admintext5']:$settings['admintext5'];


                            if ($req['finalize_y'] != "") {
                                $finalize_review = '<a href="#" onclick="editIframeModal(\'hub-modal-finalize\',\'redcap-finalize-frame\',\'' . $survey_link . '\',\'' . $finalize_review_text . '\');" class="btn btn-default btn-xs open-codesModal"><span class="fa fa-check-square text-approved"></span> '.htmlspecialchars($finalize_review_text,ENT_QUOTES).'</a>';
                            } else {
                                $finalize_review = '<a href="#" onclick="editIframeModal(\'hub-modal-finalize\',\'redcap-finalize-frame\',\'' . $survey_link . '\',\'' . $finalize_review_text . '\');" class="btn btn-secondary btn-xs open-codesModal"><i class="fa fa-legal fa-fw" aria-hidden="true"></i> '.htmlspecialchars($finalize_review_text,ENT_QUOTES).'</a>';
                            }
                            if($req['request_type'] == '1' || $req['request_type'] == '5'){
                                if ($req['author_doc'] != "") {
                                    $request_docs = '<a href="#" onclick="editIframeModal(\'hub-modal-doc\',\'redcap-doc-frame\',\'' . $survey_link_doc . '\',\'' . $request_docs_text . '\');" class="btn btn-default btn-xs open-codesModal"><span class="fa fa-check-square text-approved"></span> '.htmlspecialchars($request_docs_text,ENT_QUOTES).'</a>';
                                } else {
                                    $request_docs = '<a href="#" onclick="editIframeModal(\'hub-modal-doc\',\'redcap-doc-frame\',\'' . $survey_link_doc . '\',\'' . $request_docs_text . '\');" class="btn btn-secondary btn-xs open-codesModal"><i class="fa fa-file fa-fw" aria-hidden="true"></i> '.htmlspecialchars($request_docs_text,ENT_QUOTES).'</a>';
                                }
                                if ($req['mr_assigned'] != "" && $req['finalconcept_doc'] != "" && $req['finalconcept_pdf'] != "") {
                                    $assign_mr = '<a href="#" onclick="editIframeModal(\'hub-modal-mr\',\'redcap-mr-frame\',\'' . $survey_link_mr . '\',\'' . $assign_mr_text . '\');" class="btn btn-default btn-xs open-codesModal"><span class="fa fa-check-square text-approved"></span> '.htmlspecialchars($assign_mr_text,ENT_QUOTES).'</a>';
                                } else {
                                    $assign_mr = '<a href="#" onclick="editIframeModal(\'hub-modal-mr\',\'redcap-mr-frame\',\'' . $survey_link_mr . '\',\'' . $assign_mr_text . '\');" class="btn btn-secondary btn-xs open-codesModal"><i class="fa fa-hashtag fa-fw" aria-hidden="true"></i> '.htmlspecialchars($assign_mr_text,ENT_QUOTES).'</a>';
                                }
                            }else{
                                $request_docs = "";
                                $assign_mr = "";
                            }

                            echo '<tr>' .
                                '<td><div><strong><span class="fa fa-user fa-square  ' . htmlspecialchars($abstracts_publications_badge_text[$req['request_type']],ENT_QUOTES) . '"></span> ' . htmlspecialchars($request_type_label[$req['request_type']],ENT_QUOTES) . '</strong> (' . htmlspecialchars($req_type,ENT_QUOTES) . ')</div>' .
                                '<div style="padding-top:5px;">by <a href="mailto:' . $req['contact_email'] . '">' . $req['contact_name'] . '</a>' . htmlspecialchars($region,ENT_QUOTES) . '</div>' .
                                '<div style="padding-top:5px;">Completed on: ' . $req['workflowcomplete_d'] . '</div></td>' .
                                '<td><a href="'.$module->getUrl("index.php")."&NOAUTH&pid=".$pidsArray['PROJECTS']."&option=hub&record=" . $req['request_id'] . '" target="_blank">' . htmlspecialchars($req['request_title'],ENT_QUOTES) . '</a></td>' .
                                '<td><div>' . filter_tags($finalize_review) . '</div></td>' .
                                '<td><div>' . filter_tags($request_docs) . '</div></td>' .
                                '<td><div>' . filter_tags($assign_mr) . '</div></td>' .
                                '</tr>';
                        }
                    }
                    if(!$any_request_found){
                        ?>
                        <tbody>
                        <tr>
                            <td><span><em>No requests available</em></span></td>
                        </tr>
                        </tbody>
                        <?php
                    }
                }else{?>
                    <tbody>
                    <tr>
                        <td><span><em>No requests available</em></span></td>
                    </tr>
                    </tbody>
                <?php }?>
            </table>
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
                    <iframe class="commentsform" id="redcap-finalize-frame" message="F" name="redcap-finalize-frame" src="" style="border: none;height: 810px;width: 100%;"></iframe>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="hub-modal-doc" tabindex="-1" role="dialog" aria-labelledby="Codes">
        <div class="modal-dialog" role="document" style="width: 800px">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Request Docs</h4>
                </div>
                <div class="modal-body">
                    <iframe class="commentsform" id="redcap-doc-frame" message="D" name="redcap-doc-frame" src="" style="border: none;height: 810px;width: 100%;"></iframe>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="hub-modal-mr" tabindex="-1" role="dialog" aria-labelledby="Codes">
        <div class="modal-dialog" role="document" style="width: 800px">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Assign MR</h4>
                </div>
                <div class="modal-body">
                    <iframe class="commentsform" id="redcap-mr-frame" message="M" name="redcap-mr-frame" src="" style="border: none;height: 810px;width: 100%;"></iframe>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>


    <!-- MODAL EDIT PROCESS-->
    <div class="modal fade" id="hub_process_survey" tabindex="-1" role="dialog" aria-labelledby="Codes">
        <div class="modal-dialog" role="document" style="width: 800px">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Process</h4>
                </div>
                <div class="modal-body">
                    <iframe class="commentsform" id="redcap-edit-frame-admin" message="S" name="redcap-edit-frame-admin" src="" style="border: none;height: 810px;width: 100%;"></iframe>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8" style="padding-left:0px">
        <div class="col-md-12" style="padding-left:0px">
            <div class="panel panel-default" style="margin-bottom: 20px">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="fa fa-refresh" aria-hidden="true"></i> Update Publications
                    </h3>
                </div>
                <div class="table-responsive panel-collapse" aria-expanded="true">
                    <table class="table table_requests sortable-theme-bootstrap" style="font-size: 14px;">
                        <tr>
                            <td>
                                On clicking the button the publications code will run creating a new JSON file and updating the content.
                            </td>
                            <td>
                                <a href="#" onclick="$('#modal-publications-confirmation').modal('show');" class="btn btn-primary" style="float: right;"><span class="fa fa-refresh"></span> Update Publications</a>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="modal fade" id="modal-publications-confirmation" tabindex="-1" role="dialog" aria-labelledby="Codes">
                <form class="form-horizontal" action="" method="post" id='dataPubForm'>
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title">Update Publications</h4>
                            </div>
                            <div class="modal-body">
                                <span>Are you sure you want to update publications?</span>
                                <br>
                                <span style="color:red;">This will create a new JSON and prevent the cron from automatically running tonight until the next day.</span>
                                <div style="display:none" id="pubsSpinner">
                                    <div style="padding-top: 20px">
                                        <div class="alert alert-success">
                                            <em class="fa fa-spinner fa-spin"></em> Updating... Please wait until the process finishes.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <a type="submit" onclick="runPubsCron('<?=$module->getUrl('crontasks/cron_publications.php')?>')" class="btn btn-default btn-success" id='btndataPubForm'>Continue</a>
                                <a href="#" class="btn btn-default btn-cancel" data-dismiss="modal">Cancel</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-12" style="padding-left:0px" id="hubUsers">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        Hub Users
                        <a href="<?=$module->getUrl('index.php').'&NOAUTH&option=usr'?>" style="float: right;padding-right: 10px;color: #337ab7">View more</a>
                    </h3>
                </div>

                <div class="table-responsive panel-collapse collapse in" aria-expanded="true">
                    <table class="table table_requests sortable-theme-bootstrap" data-sortable>
                        <?php
                        $q = $module->query("SELECT a.record,max(if(a.field_name = ?, a.value, '')) as active_y,max(if(a.field_name = ?, a.value, '')) as last_requested_token_d,max(if(a.field_name = ?, a.value, NULL))as email, max(if(a.field_name = ?, a.value, NULL))as person_region,CONCAT_WS(' ',max(if(a.field_name = ?, a.value, NULL)),max(if(a.field_name = ?, a.value, NULL))) as name FROM ".\Vanderbilt\HarmonistHubExternalModule\getDataTable($pidsArray['PEOPLE'])." a INNER JOIN ".\Vanderbilt\HarmonistHubExternalModule\getDataTable($pidsArray['PEOPLE'])." b on (b.value is not null and b.field_name = ? AND a.record=b.record and a.project_id=b.project_id) WHERE a.project_id=? group by a.record ORDER BY b.value DESC LIMIT 15", ['active_y','last_requested_token_d','email','person_region', 'firstname','lastname', 'last_requested_token_d' ,$pidsArray['PEOPLE']]);
                        while ($row = $q->fetch_assoc()) {
                            $logins[] = $row;
                        }
                        if(!empty($logins)){
                            echo '<thead>'.'
                                    <tr>'.'
                                        <th class="sorted_class" data-sorted-direction="descending">Name</th>'.'
                                        <th class="sorted_class" data-sorted-direction="descending">Region</th>'.'
                                        <th class="sorted_class" data-sorted="true" data-sorted-direction="descending" style="width: 150px;">Last Access Link</th>'.'
                                        <th class="sorted_class" data-sorted-direction="descending">Level</th>'.'
                                        <th class="sorted_class" data-sortable="false" data-sorted="false">REDCap</th>'.'
                                    </tr>'.'
                                    </thead>';

                            $harmonist_regperm = $module->getChoiceLabels('harmonist_regperm', $pidsArray['PEOPLE']);
                            $harmonist_perms = $module->getChoiceLabels('harmonist_perms', $pidsArray['PEOPLE']);
                            foreach ($logins as $login){
                                if($login['active_y'] != "0") {
                                    $region_code = \REDCap::getData($pidsArray['REGIONS'], 'json-array', array('record_id' => $login['person_region']),array('region_code'))[0]['region_code'];

                                    $people = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array('record_id' => $login['record']))[0];

                                    $gotoredcap = APP_PATH_WEBROOT_ALL . "DataEntry/record_home.php?pid=" . $module->escape($pidsArray['PEOPLE']) . "&arm=1&id=" . $module->escape($login['record']);

                                    echo '<tr><td><a href="mailto:' . htmlspecialchars($login['email'],ENT_QUOTES) . '">' . htmlspecialchars($login['name'],ENT_QUOTES) . '</a></td>' .
                                        '<td style="text-align: center;">' . htmlspecialchars($region_code,ENT_QUOTES) . '</td>' .
                                        '<td>' . htmlspecialchars($login['last_requested_token_d'],ENT_QUOTES) . '</td>' .
                                        '<td>' . htmlspecialchars($harmonist_regperm[$people['harmonist_regperm']],ENT_QUOTES) . '</td>' .
                                        '<td style="text-align: center;"><a href="' . $gotoredcap . '" target="_blank"> <img src="'.$module->getUrl('img/REDCap_R_logo_transparent.png').'" style="width: 18px;" alt="REDCap Logo"></a></td>';
                                }
                            }
                        }else{?>
                            <tbody>
                            <tr>
                                <td><span><em>No logins available</em></span></td>
                            </tr>
                            </tbody>
                        <?php }?>
                    </table>
                </div>
            </div>
            <div style="width:5%;float:left">&nbsp;</div>
        </div>
    </div>
    <div class="col-md-4"  style="padding-right:0px;padding-left:0px" id="RedcapLinks">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    REDCap Links
                </h3>
            </div>

            <div id="collapse3" class="table-responsive panel-collapse collapse in" aria-expanded="true">
                <table class="table table_requests sortable-theme-bootstrap" data-sortable>
                    <?php
                    $projectsY = \REDCap::getData($pidsArray['PROJECTS'], 'json-array', null,null,null,null,false,false,false, "[project_show_y] = '1'");
                    foreach ($projectsY as $project){
                        $iedea_constant = $pidsArray[$project['project_constant']];
                        $title = $module->framework->getProject($iedea_constant)->getTitle();
                        $project_plugin = $iedea_constant;
                        echo '<tr>'.
                            '<td><a href="'.APP_PATH_WEBROOT_ALL."Design/online_designer.php?pid=".$iedea_constant.'" target="_blank">'.htmlspecialchars($title,ENT_QUOTES).'</a></td>'.
                             '</tr>';
                    }
                    ?>
                </table>
            </div>
        </div>
    </div>
</div>
<?php
if($settings['session_timeout_popup'] == 2 && $settings['session_timeout_popup'] != ''){
    include(dirname(dirname(__FILE__))."/logout_popup.php");
}
?>