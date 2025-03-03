<?php
namespace Vanderbilt\HarmonistHubExternalModule;

$recordId = htmlentities($_REQUEST['record'], ENT_QUOTES);
$concept = $module->getConceptModel()->fetchConcept($recordId);

$abstracts_publications_type = $module->getChoiceLabels('output_type', $pidsArray['HARMONIST']);
$abstracts_publications_badge = array("1" => "badge-manuscript", "2" => "badge-abstract", "3" => "badge-poster", "4" => "badge-presentation", "5" => "badge-report", "99" => "badge-other");
$harmonist_perm_edit_concept = ($current_user['harmonist_perms___3'] == 1) ? true : false;
?>

<script>
    $(document).ready(function() {
        $('html,body').scrollTop(0);
        $("html,body").animate({ scrollTop: 0 }, "slow");

        reloadCode();

        $('#hub_edit_concept').on('hidden.bs.modal', function () {
            top.location.hash = "triggerReloadCode";
            top.location.reload(true);
        });
    });

    function reloadCode() {
        if (window.location.hash.substr(1) == "triggerReloadCode") {
            window.location.hash = "";
            $('#succMsgContainer').show();
        }
    }
</script>
<div class="container">
    <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;display: none;" id="succMsgContainer">If you've made any changes, they have been saved.</div>
    <div class="backTo">
        <a href="<?=$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=cpt'?>">< Back to Concepts</a>
    </div>
    <?php if($concept != "") {?>
    <h3 class="concepts-title-title"><?=$concept->getConceptId()?></h3>

        <?php if($isAdmin || $harmonist_perm_edit_concept){
            $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['HARMONIST'], $recordId, "concept_sheet", "");
            $survey_link = APP_PATH_WEBROOT_FULL . "/surveys/?s=".$module->escape($passthru_link['hash'])."&modal=modal";

            $gotoredcap = htmlentities(APP_PATH_WEBROOT_ALL."DataEntry/record_home.php?pid=".$pidsArray['HARMONIST']."&arm=1&id=".$recordId,ENT_QUOTES);

            $survey_queue_link = \REDCap::getSurveyQueueLink($recordId);
            ?>
            <div class="btn-group hidden-xs pull-right">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Admin <span class="caret"></span>
                </button>
                <ul class="dropdown-menu">
                    <li><a href="#" onclick="$('#hub_edit_concept').modal('show');">Edit Concept</a></li>
                    <?php if($survey_queue_link != ''){?>
                        <li><a href="#" onclick="$('#hub_news_pubs').modal('show');">Edit News & Pubs</a></li>
                    <?php } ?>
                    <li role="separator" class="divider"></li>
                    <li><a href="<?=$gotoredcap?>" target="_blank">Go to REDCap</a></li>
                </ul>
            </div>
            <!-- MODAL EDIT CONCEPT-->
            <div class="modal fade" id="hub_edit_concept" tabindex="-1" role="dialog" aria-labelledby="Codes">
                <div class="modal-dialog" role="document" style="width: 900px">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title">Edit Concept</h4>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" value="0" id="comment_loaded">
                            <iframe class="commentsform" id="redcap-concept-frame" name="redcap-concept-frame" src="<?=$survey_link?>" style="border: none;height: 810px;width: 100%;"></iframe>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-success" onclick="refreshModal('redcap-concept-frame','<?=$survey_link?>');">Back to Concept</button>
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                function refreshModal(id,link){
                    $('#'+id).attr('src', '');
                    document.getElementById(id).contentWindow.location.reload(); //Reloads the Iframe
                    $('#'+id).attr('src', link);
                }
            </script>
            <!-- MODAL NEWS PUBS-->
            <div class="modal fade" id="hub_news_pubs" tabindex="-1" role="dialog" aria-labelledby="Codes">
                <div class="modal-dialog" role="document" style="width: 900px">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title">Edit Concept Details</h4>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" value="0" id="comment_loaded_newspubs">
                            <iframe class="commentsform" id="redcap-pubs-frame" name="redcap-pubs-frame" src="<?=$survey_queue_link?>" style="border: none;height: 515px;width: 100%;"></iframe>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-success" onclick="refreshModal('redcap-pubs-frame','<?=$survey_queue_link?>');">Back to Queue</button>
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
       <?php } ?>
    <p class="hub-title concepts-title-title" style="font-weight: normal"><?=$concept->getConceptTitle()?></p>

    <table class="table table_requests sortable-theme-bootstrap" data-sortable>
        <div class="row request">
            <div class="col-md-2 col-sm-12"><strong>Working Group:</strong></div>
            <div class="col-md-6 col-sm-12"><?=$group_name_total?> </div>
            <div class="col-md-4"><strong>Start Date: </strong><?=$start_date;?> </span></div>
        </div>
        <div class="row request">
            <div class="col-md-2 col-sm-12"><strong>Contact:</strong> </div>
            <div class="col-md-6 col-sm-12"><?=$name_concept?></div>
            <div class="col-md-4"><strong>Status: </strong><span class="label label-as-badge <?=$active_color_button;?>"><?=$active;?></span> <?=$revised?></div>
        </div>
        <div class="row request">
            <div class="col-md-2"><strong>Participants:</strong></div>
            <div class="col-md-6">
                <?php
                if(!empty($concept->getParticipantsComplete())) {
                    foreach ($concept->getParticipantsComplete() as $id => $participant) {
                        $RecordSetParticipant = \REDCap::getData($pidsArray['PEOPLE'], 'array', array('record_id' => $concept->getPersonLink()[$id]));
                        $participant_info = $module->escape(ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetParticipant,$pidsArray['PEOPLE'])[0]);
                        if (!empty($participant_info)) {
                            #get the label from the drop down menu
                            echo '<div><a href="mailto:' . $participant_info['email'] . '">' . $participant_info['firstname'] . ' ' . $participant_info['lastname'] . '</a> (' . htmlspecialchars($module->getChoiceLabels('person_role', $pidsArray['HARMONIST'])[$concept->getPersonRole()[$id]],ENT_QUOTES). ')</div>';
                        } else {

                            echo '<div>' . htmlspecialchars($concept->getPersonOther()[$id],ENT_QUOTES) . '</div>';
                        }
                    }
                }else{
                    echo "<div><em>Not specified</em></div>";
                }
                ?>
            </div>
            <div class="col-md-4" style="display: flex">
                <div>
                    <strong>Tags: </strong>
                </div>
                <div>
                <?php
                $noTags = true;
                $concept_tags = $module->getChoiceLabels('concept_tags', $pidsArray['HARMONIST']);
                foreach ($concept->getConceptTags() as $tag=>$value){
                    if($value == 1) {
                        $noTags = false;
                        echo '<div style="display: inline-block;padding:0 5px 5px 5px"><span class="label label-as-badge badge-draft"> ' . $concept_tags[$tag].'</span></div>';
                    }
                }
                if($noTags){
                   echo '<div style="display: inline-block;padding:0 5px 5px 5px"><em>None</em></div>';
                }
                ?>
                </div>
            </div>
        </div>
    </table>

<?php
if ((!empty($concept) && $concept->getAdminupdateD() != "" && count($concept->getAdminupdateD())>0) || (!empty($concept) && $concept->getUpdateD() != "" && count($concept->getUpdateD())>0)) {
?>

    <div class="panel panel-default-archive">
        <div class="table-responsive table-archive">
            <table class="table table_requests sortable-theme-bootstrap" data-sortable id="table_projectUpdate">
                <thead>
                    <tr>
                        <th class="archive_grid_dued sorted_class">Date</th>
                        <th class="archive_grid_dued sorted_class">Project Update</th>
                        <th class="archive_grid_dued">Status</th>
                    </tr>
                </thead>

                <tbody>
                <?php
                    $project_status = $module->getChoiceLabels('project_status', $pidsArray['HARMONIST']);
                    $admin_status = $module->getChoiceLabels('admin_status', $pidsArray['HARMONIST']);
                    if($concept->getAdminupdateD() == "" && $concept->getUpdateD() == ""){
                        echo '<tr><td colspan="3">No updates available</td></tr>';
                    }else if($concept->getAdminupdateD() != "" && $concept->getUpdateD() != ""){
                        $adminUpdateD = array();
                        foreach ($concept->getAdminupdateD() as $aindex => $adminupdate){
                            $adminUpdateD[$aindex."-admin"] = $adminupdate;
                        }
                        $updateD = array();
                        foreach ($concept->getUpdateD() as $uindex => $update){
                            $updateD[$uindex."-project"] = $update;
                        }
                        $allUpdates = array_merge($updateD,$adminUpdateD);
                        #sort elements by most recent date Admin
                        arsort($allUpdates);
                        foreach ($allUpdates as $index=>$value){
                            $index_data = explode('-',$index);
                            echo '<tr>';
                            echo  '<td style="width: 10%;">' . $value. '</td>';
                            echo  '<td>' . $concept->{"get".ucfirst($index_data[1])."Update"}()[$index_data[0]]. '</td>';
                            echo  '<td style="width: 25%;">'.${$index_data[1]."_status"}[$concept->{"get".ucfirst($index_data[1])."Status"}()[$index_data[0]]].'</td>';
                            echo '</tr>';
                        }
                    }else if($concept->getAdminupdateD()!= "" && $concept->getUpdateD() == ""){
                        asort($concept->getAdminupdateD());
                        foreach ($concept->getAdminupdateD() as $index=>$value){
                            echo '<tr>';
                            echo  '<td style="width: 10%;">' . htmlspecialchars($value,ENT_QUOTES). '</td>';
                            echo  '<td>' . filter_tags($concept->getAdminUpdate()[$index]). '</td>';
                            echo  '<td style="width: 25%;">'.htmlspecialchars($admin_status[$concept->getAdminStatus()[$index]],ENT_QUOTES).'</td>';
                            echo '</tr>';
                        }
                    }else if($concept->getAdminupdateD() == "" && $concept->getUpdateD() != ""){
                        asort($concept->getUpdateD());
                        foreach ($concept->getUpdateD() as $index=>$value){
                            echo '<tr>';
                            echo  '<td style="width: 10%;">' . htmlspecialchars($value,ENT_QUOTES). '</td>';
                            echo  '<td>' . filter_tags($concept->getProjectUpdate()[$index]). '</td>';
                            echo  '<td style="width: 25%;">'.htmlspecialchars($project_status[$concept->getProjectUpdate()[$index]],ENT_QUOTES).'</td>';
                            echo '</tr>';
                        }
                    }

                    echo '</tbody></table>';
                ?>
                </tbody>
            </table>
        </div>
    </div>

<?php } ?>


    <div class="panel panel-default" style="margin-bottom: 40px">
        <div class="panel-heading" style="height: 38px">
            <h3 class="panel-title">
                <a data-toggle="collapse" href="#collapse_concept">Concept Sheet</a>
                <?php
                if(!empty($row_concept_file['doc_name'])) {
                    $extension = ($row_concept_file['file_extension'] == 'pdf')? "pdf-icon.png" : "word-icon.png";
                    $pdf_path = $module->getUrl("loadPDF.php")."&NOAUTH&pid=".$pidsArray['PROJECTS']."&edoc=".$concept->getConceptFile()."#page=1&zoom=100";

                    $file_icon = getFileLink($module, $pidsArray['PROJECTS'], $concept->getConceptFile(),'1','',$secret_key,$secret_iv,$current_user['record_id'],"");
                    $download_link = $module->getUrl("downloadFile.php")."&NOAUTH&code=".getCrypt("sname=".$row_concept_file['stored_name']."&file=". urlencode($row_concept_file['doc_name'])."&edoc=".$concept->getConceptFile()."&pid=".$current_user['record_id'],'e',$secret_key,$secret_iv);
                    ?>
                    <span style="float: right;padding-right: 15px;"><?=$file_icon;?></span>
                    <a href="<?=$download_link?>" target="_blank" style="float: right;padding-right: 10px;"><span class="">Download </span>PDF </a>
                <?php }?>
            </h3>
        </div>
        <div id="collapse_concept" class="table-responsive panel-collapse collapse in" aria-expanded="true">
            <?php if(!empty($row_concept_file['doc_name'])) {?>
            <iframe class="commentsform" id="redcap-frame" src="<?=$pdf_path?>" style="border: none;width: 100%;height: 500px;"></iframe>
            <?php }else{?>
                <table class="table table-hover table-bordered table-list table-font-size">
                    <tbody>
                    <tr>
                        <td><span><em>No document available</em></span></td>
                    </tr>
                    </tbody>
                </table>
            <?php }?>
        </div>
    </div>

    <?php if(!$deactivate_datahub){?>
    <div class="panel panel-default" style="margin-bottom: 40px">
        <div class="panel-heading" style="height: 38px">
            <h3 class="panel-title">
                <a data-toggle="collapse" href="#collapse4" class="collapsed">Data Requests for <?=$concept->getConceptId()?></a>
            </h3>
        </div>

        <div id="collapse4" class="table-responsive panel-collapse collapse in" aria-expanded="false" style="overflow-y: hidden;">
            <table class="table sortable-theme-bootstrap" data-sortable>
            <?php
            $q = $module->query("SELECT record FROM ".getDataTable($pidsArray['HARMONIST'])." WHERE field_name = ? AND value IS NOT NULL AND record = ? AND project_id = ?",['datasop_file',$recordId,$pidsArray['HARMONIST']]);

            $params = [
                'project_id' => $pidsArray['SOP'],
                'return_format' => 'array',
                'filterLogic' => "[sop_active] = '1' and [sop_visibility] = '2' and [sop_concept_id] = ".$recordId,
                'filterType' => "RECORD"
            ];
            $RecordSetSOP = \REDCap::getData($params);
            $data_requests = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP,$pidsArray['SOP']);
            ArrayFunctions::array_sort_by_column($data_requests,'sop_updated_dt',SORT_DESC);
            if(!empty($data_requests) || $q->num_rows > 0) {
                echo getDataCallConceptsHeader($pidsArray['REGIONS'], $current_user['person_region'],$settings['vote_grid']);
                foreach ($data_requests as $sop) {
                    echo getDataCallConceptsRow(
                        $module,
                        $pidsArray,
                        $sop,
                        $isAdmin,
                        $current_user,
                        $secret_key,
                        $secret_iv,
                        $settings['vote_grid'],
                        '',
                        ''
                    );
                }
                while ($rowConcept = db_fetch_assoc($q)){
                    $RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', null,null,null,null,false,false,false,"[sop_concept_id] = ".$rowConcept['record']);
                    $data_requests_old = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP,$pidsArray['SOP']);
                    if(empty($data_requests_old)){
                        echo getDataCallConceptsRow($module, $pidsArray,$sop,$isAdmin,$current_user,$secret_key,$secret_iv,$settings['vote_grid'],$rowConcept['record'],"1");
                    }
                }
            }else{?>
                <tbody>
                <tr>
                    <td><span><em>No data requests available</em></span></td>
                </tr>
                </tbody>
            <?php }?>
            </table>
        </div>
    </div>

    <div class="panel panel-default" style="margin-bottom: 40px">
        <div class="panel-heading">
            <h3 class="panel-title">
                <a data-toggle="collapse" href="#collapse_dataReqUp">Data Uploads</a>
            </h3>
        </div>
        <div id="collapse_dataReqUp" class="panel-collapse collapse in" aria-expanded="true">
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
                $uploads = \REDCap::getData($pidsArray['DATAUPLOAD'], 'json-array', null,null,null,null,false,false,false,"[data_assoc_concept] = ".$recordId);
                if(!empty($uploads)){?>

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
                foreach ($uploads as $up){
                    $people = $module->escape(\REDCap::getData($pidsArray['PEOPLE'], 'json-array', array('record_id' => $up['data_upload_person']))[0]);
                    $contact_person = "<a href='mailto:" . $people['email'] . "'>" . $people['firstname'] . " " . $people['lastname'] . "</a>";

                    $region_code = \REDCap::getData($pidsArray['REGIONS'], 'json-array', array('record_id' => $up['data_upload_region']),array('region_code'))[0]['region_code'];

                    $status = '<span class="badge label-updated">Available</span>';
                    if($up['deleted_y'] == '1'){
                        $status = '<span class="badge label-notupdated" >Deleted</span>';
                    }

                    echo "<tr>";
                    echo "<td width='200px'>" . htmlspecialchars($up['responsecomplete_ts'],ENT_QUOTES) . "</td>" .
                        "<td width='250px'>" . $contact_person . "</td>" .
                        "<td width='500px'>" . htmlspecialchars($up['upload_notes'],ENT_QUOTES) . "</td>" .
                        "<td width='120px'>" . htmlspecialchars($region_code ,ENT_QUOTES). "</td>" .
                        "<td width='120px'>".filter_tags($status)."</td>" .
                        "</tr>";

                }
                }else{
                    ?>
                    <li class="list-group-item"><em>No data uploads.</em></li>
                <?php }?>
                </tbody>
            </table>
        </div>
    </div>
    <?php } ?>


    <div class="panel panel-default" style="margin-bottom: 40px">
        <div class="panel-heading">
            <h3 class="panel-title">
                <a data-toggle="collapse" href="#collapse_publications">Abstracts & Publications</a>
                <?php
                $harmonist_perm = ($current_user['harmonist_perms___10'] == 1) ? true : false;
                $can_edit_pub = UserEditConditions::canUserEditData($isAdmin, $current_user['record_id'], $concept->getContactLink(), $concept->getContact2Link(), $harmonist_perm);
                if($can_edit_pub){
                    $output_link = $module->getSurveyLinkNewInstance("outputs", $recordId, $pidsArray['HARMONIST']);
                ?>
                <a href="#" onclick="$('#hub_new_output').modal('show');" style="float: right;padding-right: 30px;color: #337ab7;cursor: pointer"><em class="fa fa-plus"></em> New Output</a>
                <!-- MODAL NEW OUTPUT-->
                <div class="modal fade" id="hub_new_output" tabindex="-1" role="dialog" aria-labelledby="Codes">
                    <div class="modal-dialog" role="document" style="width: 900px">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title"><em class="fa fa-plus"></em> New Output</h4>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" value="0" id="comment_loaded">
                                <iframe class="commentsform" id="redcap-new-output-frame" name="redcap-new-output-frame" src="<?=$output_link?>" style="border: none;height: 810px;width: 100%;"></iframe>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
                }
                ?>
            </h3>
        </div>


        <div id="collapse_publications" class="table-responsive panel-collapse collapse in" aria-expanded="true">
            <table class="table table_requests sortable-theme-bootstrap" data-sortable id="abstracts">
                <?php
                if(!empty($concept->getOutputType())){
                    $header = '<colgroup>
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                        </colgroup>';

                    $header .= '<thead>'.
                        '<tr>'.
                        '<th class="sorted_class" data-sorted="true" style="width:5%;" data-sorted-direction="descending">Year</th>'.
                        '<th class="sorted_class" data-sorted="false" style="width:20%;"><span style="display:block">Journal /</span><span>Conference</span></th>'.
                        '<th class="sorted_class" data-sorted="false" style="width:40%;">Title and Authors</th>'.
                        '<th class="sorted_class" data-sorted="false" style="width:20%;">Available</th>'.
                        '<th class="sorted_class" data-sorted="false" style="width:10%;">File</th>';
                    if($isAdmin){
                        $header .= '<th class="sorted_class" style="width:5%;text-align: center;" data-sorted="false"><em class="fa fa-cog"></em></th>';
                    }
                    echo '</tr></thead>'.$header;

                    echo '<tbody>';

                    //Order by year
                    $output_year = $concept->getOutputYear();
                    if(!empty($output_year) && is_array($output_year))
                        asort ($output_year);
                    foreach ($output_year as $index =>$value){

                        echo '<tr><td>'.$output_year[$index].'</td>'.
                            '<td>'.$concept->getOutputVenue()[$index].'</td>'.
                            '<td><span class="badge badge-pill '.$abstracts_publications_badge[$concept->getOutputType()[$index]].'">'.$abstracts_publications_type[$concept->getOutputType()[$index]].'</span> <strong>'.$concept->getOutputTitle()[$index].'</strong> </br><span class="abstract_text">'.$concept->getOutputAuthors()[$index].'</span></td>';

                        $available = '';
                        if(!empty($concept->getOutputCitation()[$index])){
                            $available = htmlspecialchars($concept->getOutputCitation()[$index],ENT_QUOTES);
                        }
                        if(!empty($concept->getOutputPmcid()[$index])){
                            $available .= 'PMCID: <a href="https://www.ncbi.nlm.nih.gov/pmc/articles/'.$concept->getOutputPmcid()[$index].'" target="_blank">'.$concept->getOutputPmcid()[$index].'<i class="fa fa-fw fa-external-link" aria-hidden="true"></i></a>';
                        }
                        if(!empty($concept->getOutputUrl()[$index])){
                            $available .= '<a href="'.$concept->getOutputUrl()[$index].'" target="_blank">Link<i class="fa fa-fw fa-external-link" aria-hidden="true"></i></a>';
                        }

                        echo '<td>'.$available.'</td>';

                        $file ='';
                        if($concept->getOutputFile()[$index] != ""){
                            $file = getFileLink($module, $pidsArray['PROJECTS'], $concept->getOutputFile()[$index],'1','',$secret_key,$secret_iv,$current_user['record_id'],"");
                        }
                        echo '<td>'.$file.'</td>';

                        if($can_edit_pub){
                            $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['HARMONIST'], $concept->getRecordId(), "outputs", "", $index);
                            $survey_link = APP_PATH_WEBROOT_FULL . "/surveys/?s=".$module->escape($passthru_link['hash']);
                            echo '<td><button class="btn btn-default open-codesModal" onclick="$(\'#edit_title\').html(\'Edit Publication\');editIframeModal(\'hub_edit_pub\',\'redcap-edit-frame\',\''.$survey_link.'\');"><em class="fa fa-pencil"></em></button></td>';
                        }

                        echo '</tr>';
                    }
                    echo '</tbody>';
                }else{
                    echo "<tr><td><em>No Abstracts & Publications available</em></td></tr>";
                }
                ?>
            </table>
        </div>
    </div>
    <!-- MODAL EDIT CONCEPT-->
    <div class="modal fade" id="hub_edit_pub" tabindex="-1" role="dialog" aria-labelledby="Codes">
        <div class="modal-dialog" role="document" style="width: 800px">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="edit_title">Edit Publication</h4>
                </div>
                <div class="modal-body">
                    <iframe class="commentsform" id="redcap-edit-frame" message="E" name="redcap-edit-frame" src="" style="border: none;height: 810px;width: 100%;"></iframe>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="panel panel-default" style="margin-bottom: 40px">
        <div class="panel-heading">
            <h3 class="panel-title">
                <a data-toggle="collapse" href="#collapse_publications">Linked Documents</a>
                <?php
                $harmonist_perm = ($current_user['harmonist_perms___10'] == 1) ? true : false;
                $can_edit_linked_doc = UserEditConditions::canUserEditData($isAdmin, $current_user['record_id'], $concept->getContactLink(), $concept->getContact2Link(), $harmonist_perm);
                if($can_edit_linked_doc){
                    $linked_doc_link = $module->getSurveyLinkNewInstance("linked_documents", $recordId, $pidsArray['HARMONIST']);
                    ?>
                    <a href="#" onclick="$('#hub_new_linked_doc').modal('show');" style="float: right;padding-right: 30px;color: #337ab7;cursor: pointer"><em class="fa fa-plus"></em> New File</a>
                    <!-- MODAL NEW OUTPUT-->
                    <div class="modal fade" id="hub_new_linked_doc" tabindex="-1" role="dialog" aria-labelledby="Codes">
                        <div class="modal-dialog" role="document" style="width: 900px">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    <h4 class="modal-title"><em class="fa fa-plus"></em> New File</h4>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" value="0" id="comment_loaded">
                                    <iframe class="commentsform" id="redcap-new-output-frame" name="redcap-new-output-frame" src="<?=$linked_doc_link?>" style="border: none;height: 810px;width: 100%;"></iframe>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </h3>
        </div>

        <div id="collapse_publications" class="table-responsive panel-collapse collapse in" aria-expanded="true">
            <table class="table table_requests sortable-theme-bootstrap" data-sortable id="abstracts">
                <?php
                if(!empty($concept->getDocTitle())){
                    $header = '<colgroup>
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                        </colgroup>';

                    $header .= '<thead>'.
                        '<tr>'.
                        '<th class="sorted_class" data-sorted="true" style="width:5%;" data-sorted-direction="descending">Upload Date</th>'.
                        '<th class="sorted_class" data-sorted="false" style="width:20%;"><span style="display:block">File Title</th>'.
                        '<th class="sorted_class" data-sorted="false" style="width:40%;">Description</th>'.
                        '<th class="sorted_class" data-sorted="false" style="width:20%;">File</th>';
                    if($isAdmin){
                        $header .= '<th class="sorted_class" style="width:5%;text-align: center;" data-sorted="false"><em class="fa fa-cog"></em></th>';
                    }
                    echo '</tr></thead>'.$header;

                    echo '<tbody>';
                    foreach ($concept->getDochiddenY() as $linked_doc_instance => $doc_hidden_value){
                        if($doc_hidden_value !== "1"){
                            echo '<tr>';
                            echo '<td width="15%">'.$concept->getDocuploadDt()[$linked_doc_instance].'</td>';
                            echo '<td width="25%">'.$concept->getDocTitle()[$linked_doc_instance].'</td>';
                            echo '<td width="50%">'.$concept->getDocDescription()[$linked_doc_instance].'</td>';

                            $file ='';
                            if($concept->getDocFile()[$linked_doc_instance] != ""){
                                $file = getFileLink($module, $pidsArray['PROJECTS'], $concept->getDocFile()[$linked_doc_instance],'1','',$secret_key,$secret_iv,$current_user['record_id'],"");
                            }
                            echo '<td width="5%">'.$file.'</td>';

                            if($can_edit_linked_doc){
                                $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['HARMONIST'], $concept->getRecordId(), "linked_documents", "", $linked_doc_instance);
                                $survey_link = APP_PATH_WEBROOT_FULL . "/surveys/?s=".$module->escape($passthru_link['hash']);
                                echo '<td><button class="btn btn-default open-codesModal" onclick="$(\'#edit_title\').html(\'Edit Linked Document\');editIframeModal(\'hub_edit_pub\',\'redcap-edit-frame\',\''.$survey_link.'\');"><em class="fa fa-pencil"></em></button></td>';
                            }
                            echo '</tr>';
                        }
                    }
                    echo '</tbody>';
                }else{
                    echo "<tr><td><em>No Linked Documents available</em></td></tr>";
                }
                ?>
            </table>
        </div>
    </div>

    <div class="panel panel-default" style="margin-bottom: 140px">
        <div class="panel-heading">
            <h3 class="panel-title">
                <a data-toggle="collapse" href="#collapse3">Related Requests</a>
            </h3>
        </div>

        <div id="collapse3" class="table-responsive panel-collapse collapse in" aria-expanded="true">
            <table class="table table_requests sortable-theme-bootstrap" data-sortable>
                <?php
                $RecordSetRM = \REDCap::getData($pidsArray['RMANAGER'], 'array', null);
                $request = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRM,$pidsArray['RMANAGER'],array('approval_y' => "1",'assoc_concept' => $concept->getRecordId()));
                if(!empty($request)){
                    echo \Vanderbilt\HarmonistHubExternalModule\getArchiveHeader('Status');
                    ?>
                    <tbody>
                    <?php
                    foreach ($request as $req) {
                        echo \Vanderbilt\HarmonistHubExternalModule\getArchiveHTML($module, $pidsArray, $req, $request_type_label, $current_user['person_region'],$settings['vote_visibility']);
                    }
                }else{?>
                    <tbody>
                    <tr>
                        <td><span><em>No related requests available</em></span></td>
                    </tr>
                    </tbody>
                <?php }?>
            </table>
        </div>
    </div>
</div>
<?php }else{ ?>
    <div class="alert alert-warning fade in col-md-12"><em>Concept #<?=$recordId?> is not available at this time.</em></div>
<?php } ?>
<div class="modal fade" id="hub_view_votes" tabindex="-1" role="dialog" aria-labelledby="Codes">
    <div class="modal-dialog" role="document" style="width: 800px">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">All Votes</h4>
            </div>
            <div class="modal-body">
                <div id="allvotes"> </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

