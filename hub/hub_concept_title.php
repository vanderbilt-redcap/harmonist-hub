<?php
namespace Vanderbilt\HarmonistHubExternalModule;
$RecordSetTable = \REDCap::getData(IEDEA_HARMONIST, 'array', array('record_id' => $_REQUEST['record']));
$concept = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetTable)[0];

$abstracts_publications_type = $module->getChoiceLabels('output_type', IEDEA_HARMONIST);
$abstracts_publications_badge = array("1" => "badge-manuscript", "2" => "badge-abstract", "3" => "badge-poster", "4" => "badge-presentation", "5" => "badge-report", "99" => "badge-other");

if(!empty($concept)) {
    $active = ($concept['active_y'] == "Y") ? "<span style='color:#00e600;font-weight: bold'>Active</span>" : "<span style='color:grey'>Inactive</span>";

    if($concept['active_y'] == "Y"){
        $active = "Active";
        $active_color_button = "text-button-approved";
    }else{
        $active = "Inactive";
        $active_color_button = "text-button-error";
    }

    $id_people = $concept['contact_link'];
    $name_concept = "<em>Not specified</em>";
    if (!empty($id_people)) {
        $RecordSetPeople = \REDCap::getData(IEDEA_PEOPLE, 'array', array('record_id' => $id_people));
        $person_info = ProjectData::getProjectInfoArray($RecordSetPeople,'')[0];
        $email = "";
        if (!empty($person_info)) {
            $name_concept = '<a href="mailto:'.$person_info['email'].'">'.$person_info['firstname'] . ' ' . $person_info['lastname'];
            if(!empty($person_info['person_region'])){
                $RecordSetRegion = \REDCap::getData(IEDEA_REGIONS, 'array', array('record_id' => $person_info['person_region']));
                $person_region = ProjectData::getProjectInfoArray($RecordSetRegion,'')[0];
                if(!empty($person_region)){
                    $name_concept .= " (".$person_region['region_code'].")";
                }
            }
            $name_concept .= '</a>';
            $email = $person_info['email'];
        }
    }
    $id_workingGroup = $concept['wg_link'];
    if (!empty($id_workingGroup)) {
        $RecordSetGroup = \REDCap::getData(IEDEA_GROUP, 'array', array('record_id' => $id_workingGroup));
        $wgroup = ProjectData::getProjectInfoArray($RecordSetGroup,'')[0];
        $group_name = "";
        if (!empty($wgroup)) {
            $group_name = $wgroup['group_abbr'] . ' - ' . $wgroup['group_name'];
        }
    }

    if (!empty($concept['wg2_link'])) {
        $RecordSetGroup2 = \REDCap::getData(IEDEA_GROUP, 'array', array('record_id' =>  $concept['wg2_link']));
        $wgroup2 = ProjectData::getProjectInfoArray($RecordSetGroup2,'')[0];
    }
    $group_name_total = "<em>Not specified</em>";
    if(!empty($wgroup['group_name'])){
        $group_name_total = $wgroup['group_name'];
        if(!empty($wgroup2['group_name'])){
            $group_name_total = $group_name_total.', '.$wgroup2['group_name'];
        }
    }else  if(!empty($wgroup2['group_name'])){
        $group_name_total = $wgroup2['group_name'];
    }



    #Concept Files
    $q = $module->query("SELECT doc_name,stored_name,file_extension FROM redcap_edocs_metadata WHERE doc_id = ?",[$concept["concept_file"]]);
    $row_concept_file = $q->fetch_assoc();

    #Only check if they are final
    $row_datasop_file = "";
    if (!empty($concept["status"]) && in_array('1', $concept["status"]) && !empty($concept["pdf_file"])) {
        #SOP Files from Builder SOP project
        $RecordSop = \REDCap::getData(IEDEA_SOP, 'array', array('record_id' => $concept['record_id']));
        $pdf_file = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSop,'')[0]["pdf_file"];
        $q = $module->query("SELECT doc_name,stored_name,file_extension FROM redcap_edocs_metadata WHERE doc_id = ?",[$pdf_file]);
        $row_datasop_file = $q->fetch_assoc();
    }
    if (empty($row_datasop_file['doc_name'])) {
        #SOP Files from Concept Sheets project
        $q = $module->query("SELECT doc_name,stored_name,file_extension FROM redcap_edocs_metadata WHERE doc_id = ?",[$concept["datasop_file"]]);
        $row_datasop_file = $q->fetch_assoc();
    }
    $start_date = (empty($concept['ec_approval_d']))? "<em>Not specified</em>" : $concept['ec_approval_d'];
}

$harmonist_perm_edit_concept = \Vanderbilt\HarmonistHubExternalModule\hasUserPermissions($current_user['harmonist_perms'], 3);

$revised = "";
if($concept['revised_y'][0] == '1'){
    $revised = '<span class="label label-as-badge badge-revised">Revised</span>';
}

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
        <a href="<?=$module->getUrl('index.php?pid='.IEDEA_PROJECTS.'&option=cpt')?>">< Back to Concepts</a>
    </div>
    <?php if($concept != "") {?>
    <h3 class="concepts-title-title"><?=$concept['concept_id']?></h3>

        <?php if($isAdmin || $harmonist_perm_edit_concept){
            $passthru_link = $module->resetSurveyAndGetCodes(IEDEA_HARMONIST, $_REQUEST['record'], "concept_sheet", "");
            $survey_link = $module->getUrl('surveyPassthru.php?&surveyLink='.APP_PATH_SURVEY_FULL . "?s=".$passthru_link['hash']."&modal");

            $gotoredcap = APP_PATH_WEBROOT_ALL."DataEntry/record_home.php?pid=".IEDEA_HARMONIST."&arm=1&id=".$_REQUEST['record'];

            $survey_queue_link = \REDCap::getSurveyQueueLink($_REQUEST['record']);
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
    <p class="hub-title concepts-title-title" style="font-weight: normal"><?=$concept['concept_title']?></p>

    <table class="table table_requests sortable-theme-bootstrap" data-sortable>
        <div class="row request">
            <div class="col-md-2 col-sm-12"><strong>Working Group:</strong></div>
            <div class="col-md-6 col-sm-12"><?=$group_name_total?> </div>
            <div class="col-md-4 hidden-sm hidden-xs"><strong>Start Date: </strong><?=$start_date;?> </span></div>
        </div>
        <div class="row request">
            <div class="col-md-2 col-sm-12"><strong>Contact:</strong> </div>
            <div class="col-md-6 col-sm-12"><?=$name_concept?></div>
            <div class="col-md-4 hidden-sm hidden-xs"><strong>Status: </strong><span class="label label-as-badge <?=$active_color_button;?>"><?=$active;?></span> <?=$revised?></div>
        </div>
        <div class="row request">
            <div class="col-md-2"><strong>Participants:</strong></div>
            <div class="col-md-6">
                <?php
                if(!empty($concept['participants_complete'])) {
                    foreach ($concept['participants_complete'] as $id => $participant) {
                        $RecordSetParticipant = \REDCap::getData(IEDEA_PEOPLE, 'array', array('record_id' => $concept['person_link'][$id]));
                        $participant_info = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetParticipant)[0];
                        if (!empty($participant_info)) {
                            #get the label from the drop down menu
                            echo '<div><a href="mailto:' . $participant_info['email'] . '">' . $participant_info['firstname'] . ' ' . $participant_info['lastname'] . '</a> (' . $module->getChoiceLabels('person_role', IEDEA_HARMONIST)[$concept['person_role'][$id]]. ')</div>';
                        } else {

                            echo '<div>' . $concept['person_other'][$id] . '</div>';
                        }
                    }
                }else{
                    echo "<div><em>Not specified</em></div>";
                }
                ?>
            </div>
            <div class="col-md-4 hidden-sm hidden-xs" style="display: flex">
                <div>
                    <strong>Tags: </strong>
                </div>
                <div>
                <?php
                $noTags = true;
                $concept_tags = $module->getChoiceLabels('concept_tags', IEDEA_HARMONIST);
                foreach ($concept['concept_tags'] as $tag=>$value){
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
if ((!empty($concept) && $concept['adminupdate_d'] != "" && count($concept['adminupdate_d'])>0) || (!empty($concept) && $concept['update_d'] != "" && count($concept['update_d'])>0)) {
?>

    <div class="panel panel-default-archive">
        <div class="table-responsive table-archive">
            <table class="table table_requests sortable-theme-bootstrap" data-sortable id="table_projectUpdate">
                <thead>
                    <tr>
                        <th class="archive_grid_dued sorted_class">Date</th>
                        <th class="archive_grid_dued sorted_class">Project Update</th>
                        <th class="archive_grid_dued">File</th>
                    </tr>
                </thead>

                <tbody>
                <?php
                    if($concept['adminupdate_d'] == "" && $concept['update_d'] == ""){
                        echo '<tr><td colspan="3">No updates available</td></tr>';
                    }else{
                        #sort elements by most recent date Admin
                        arsort($concept['adminupdate_d']);
                        foreach ($concept['adminupdate_d'] as $index=>$value){
                            echo '<tr>';
                            echo  '<td style="width: 10%;">' . $value. '</td>';
                            echo  '<td>' . $concept['admin_update'][$index]. '</td>';
                            echo  '<td style="width: 5%;">' . \Vanderbilt\HarmonistHubExternalModule\getFileLink($module, $concept['adminupdate_file'][$index],'1','',$secret_key,$secret_iv,$current_user['record_id'],""). '</td>';
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
                    $pdf_path = APP_PATH_PLUGIN."/loadPDF.php?edoc=".$concept["concept_file"]."#page=1&zoom=100";

                    $file_icon = \Vanderbilt\HarmonistHubExternalModule\getFileLink($module, $concept["concept_file"],'1','',$secret_key,$secret_iv,$current_user['record_id'],"");
                    ?>
                    <span style="float: right;padding-right: 15px;"><?=$file_icon;?></span>
                    <a href="downloadFile.php?code=<?=\Vanderbilt\HarmonistHubExternalModule\getCrypt("sname=".$row_concept_file['stored_name']."&file=". urlencode($row_concept_file['doc_name'])."&edoc=".$concept["concept_file"]."&pid=".$current_user['record_id'],'e',$secret_key,$secret_iv)?>" target="_blank" style="float: right;padding-right: 10px;"><span class="hidden-sm hidden-xs">Download </span>PDF </a>
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
                <a data-toggle="collapse" href="#collapse4" class="collapsed">Data Requests for <?=$concept['concept_id']?></a>
            </h3>
        </div>

        <div id="collapse4" class="table-responsive panel-collapse collapse in" aria-expanded="false" style="overflow-y: hidden;">
            <table class="table sortable-theme-bootstrap" data-sortable>
            <?php
            $q = $module->query("SELECT record FROM redcap_data WHERE field_name = ? AND value IS NOT NULL AND record = ? AND project_id = ?",['datasop_file',$_REQUEST['record'],IEDEA_HARMONIST]);

            $RecordSetSOP = \REDCap::getData(IEDEA_SOP, 'array', null,null,null,null,false,false,false,"[sop_active] = 1 and [sop_visibility] = 2 and [sop_concept_id] = ".$_REQUEST['record']);
            $data_requests = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP);

            ArrayFunctions::array_sort_by_column($data_requests,'sop_updated_dt',SORT_DESC);
            if(!empty($data_requests) || $q->num_rows > 0) {
                echo \Vanderbilt\HarmonistHubExternalModule\getDataCallConceptsHeader($current_user['person_region'],$settings['vote_grid']);
                foreach ($data_requests as $sop) {
                        echo \Vanderbilt\HarmonistHubExternalModule\getDataCallConceptsRow($module,$sop,$isAdmin,$current_user,$secret_key,$secret_iv,$settings['vote_grid'],'','');
                }
                while ($row = db_fetch_assoc($q)){
                    $RecordSetSOP = \REDCap::getData(IEDEA_SOP, 'array', null,null,null,null,false,false,false,"[sop_concept_id] = ".$row['record']);
                    $data_requests_old = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP);
                    if(empty($data_requests_old)){
                        echo \Vanderbilt\HarmonistHubExternalModule\getDataCallConceptsRow($module,$sop,$isAdmin,$current_user,$secret_key,$secret_iv,$settings['vote_grid'],$row['record'],"1");
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
                $RecordSetUpload = \REDCap::getData(IEDEA_DATAUPLOAD, 'array', null,null,null,null,false,false,false,"[data_assoc_concept] = ".$_REQUEST['record']);
                $uploads = ProjectData::getProjectInfoArray($RecordSetUpload);
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
                    $RecordSetPeople = \REDCap::getData(IEDEA_PEOPLE, 'array', array('record_id' => $up['data_upload_person']));
                    $people = ProjectData::getProjectInfoArray($RecordSetPeople)[0];
                    $contact_person = "<a href='mailto:" . $people['email'] . "'>" . $people['firstname'] . " " . $people['lastname'] . "</a>";

                    $RecordSetRegionsLogin = \REDCap::getData(IEDEA_REGIONS, 'array', array('record_id' => $up['data_upload_region']));
                    $region_code = ProjectData::getProjectInfoArray($RecordSetRegionsLogin)[0]['region_code'];

                    $status = '<span class="badge label-updated">Available</span>';
                    if($up['deleted_y'] == '1'){
                        $status = '<span class="badge label-notupdated" >Deleted</span>';
                    }

                    echo "<tr>";
                    echo "<td width='200px'>" . $up['responsecomplete_ts'] . "</td>" .
                        "<td width='250px'>" . $contact_person . "</td>" .
                        "<td width='500px'>" . $up['upload_notes'] . "</td>" .
                        "<td width='120px'>" . $region_code . "</td>" .
                        "<td width='120px'>".$status."</td>" .
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
            </h3>
        </div>

        <div id="collapse_publications" class="table-responsive panel-collapse collapse in" aria-expanded="true">
            <table class="table table_requests sortable-theme-bootstrap" data-sortable id="abstracts">
                <?php
                if(!empty($concept['output_type'])){
                    $header = '<colgroup>
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                        </colgroup>';

                    $header .= '<thead>'.
                        '<tr>'.
                        '<th class="sorted_class" data-sorted="true" data-sorted-direction="descending">Year</th>'.
                        '<th class="sorted_class" data-sorted="false"><span style="display:block">Journal /</span><span>Conference</span></th>'.
                        '<th class="sorted_class" data-sorted="false">Title and Authors</th>'.
                        '<th class="sorted_class" data-sorted="false">Available</th>'.
                        '<th class="sorted_class" data-sorted="false">File</th></tr></thead>';
                    echo $header;

                    echo '<tbody>';

                    //Order by year
                    $output_year = $concept['output_year'];
                    asort ($output_year);
                    foreach ($output_year as $index =>$value){

                        echo '<tr><td>'.$concept['output_year'][$index].'</td>'.
                            '<td>'.$concept['output_venue'][$index].'</td>'.
                            '<td><span class="badge badge-pill '.$abstracts_publications_badge[$concept['output_type'][$index]].'">'.$abstracts_publications_type[$concept['output_type'][$index]].'</span> <strong>'.$concept['output_title'][$index].'</strong> </br><span class="abstract_text">'.$concept['output_authors'][$index].'</span></td>';

                        $available = '';
                        if(!empty($concept['output_citation'][$index])){
                            $available = $concept['output_citation'][$index];
                        }
                        if(!empty($concept['output_pmcid'][$index])){
                            $available .= 'PMCID: <a href="https://www.ncbi.nlm.nih.gov/pmc/articles/'.$concept['output_pmcid'][$index].'" target="_blank">'.$concept['output_pmcid'][$index].'<i class="fa fa-fw fa-external-link" aria-hidden="true"></i></a>';
                        }
                        if(!empty($concept['output_url'][$index])){
                            $available .= '<a href="'.$concept['output_url'][$index].'" target="_blank">Link<i class="fa fa-fw fa-external-link" aria-hidden="true"></i></a>';
                        }

                        echo '<td>'.$available.'</td>';

                        $file ='';
                        if($concept['output_file'][$index] != ""){
                            $file = \Vanderbilt\HarmonistHubExternalModule\getFileLink($module, $concept['output_file'][$index],'1','',$secret_key,$secret_iv,$current_user['record_id'],"");
                        }
                        echo '<td>'.$file.'</td></tr>';
                    }
                    echo '</tbody>';
                }else{
                    echo "<tr><td><em>No Abstracts & Publications available</em></td></tr>";
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
                $RecordSetRM = \REDCap::getData(IEDEA_RMANAGER, 'array', null);
                $request = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRM,array('approval_y' => "1",'assoc_concept' => $concept['record_id']));
                if(!empty($request)){
                    echo \Vanderbilt\HarmonistHubExternalModule\getArchiveHeader('Status');
                    ?>
                    <tbody>
                    <?php
                    foreach ($request as $req) {
                        echo \Vanderbilt\HarmonistHubExternalModule\getArchiveHTML($module, $req, $request_type_label, $current_user['person_region'],$settings['vote_visibility']);
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
    <div class="alert alert-warning fade in col-md-12"><em>Concept #<?=$_REQUEST['record']?> is not available at this time.</em></div>
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

