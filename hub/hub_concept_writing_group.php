<?php
namespace Vanderbilt\HarmonistHubExternalModule;

global $date;
$recordId = htmlentities($_REQUEST['record'], ENT_QUOTES);
$concept = $module->getConceptModel()->fetchConcept($recordId, $settings['authorship_limit']);
if($concept != null){
    $writingGroupMember = new WritingGroupModel($module, $pid, $concept);
    $writingGroupMemberList = $writingGroupMember->fetchAllWritingGroup();
    $canUserEdit = $concept->canUserEdit($current_user['record_id'], $current_user['harmonist_perms___3']);
    $docName = $writingGroupMember->fetchWritingGroupFileName($settings['hub_name']);
}
$harmonistPermEditConcept = ($current_user['harmonist_perms___3'] == 1) ? true : false;
?>
<script language="JavaScript">
    //To filter the data
    $.fn.dataTable.ext.search.push(
        function( settings, data, dataIndex ) {
            var roles = $('#selectRoles').val();
            var column_roles = data[2];

            if(roles != "" && roles == column_roles){
                return true;
            }else if(roles == ""){
                return true;
            }

            return false;
        }
    );
    $(document).ready(function() {
        let docName = <?=json_encode($docName)?>;
        let canEdit = <?=json_encode($canUserEdit)?>;
        let harmonistPermEditConcept = <?=json_encode($harmonistPermEditConcept)?>;
        let columns = [0, 1, 2, 3];

        Sortable.init();
        //double pagination (top & bottom)
        var table = $('#sortable_table').DataTable
        (
            {
                pageLength: 50,
                dom: "<'row'<'col-sm-3'l><'col-sm-4'f><'col-sm-5'p>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-5'i><'col-sm-7'p>>",
                order: [2, "asc"],
                buttons: [
                    {
                        extend: 'excel',
                        text: '<i class="fa fa-file-excel-o"></i> Excel',
                        title: docName,
                        exportOptions: {
                            columns: columns
                        }
                    },
                    {
                        extend: 'print',
                        text: '<i class="fa fa-print"></i> Print',
                        exportOptions: {
                            columns: columns,
                            stripHtml: false
                        }
                    }
                ]
            }
        );

        table.buttons().containers().appendTo( '#options_wrapper' );
        var sortable_table = $('#sortable_table').DataTable();

        //we hide the columns that we use only as filters
        if(!canEdit && !harmonistPermEditConcept){
            var column_actions = sortable_table.column(4);
            column_actions.visible(false);
        }

        $('#sortable_table_filter').appendTo( '#options_wrapper' );
        $('#sortable_table_filter').attr( 'style','float: left;padding-left: 90px;padding-top: 5px;' );
        $('.dt-buttons').attr( 'style','float: left;' );

        //when any of the filters is called upon change datatable data
        $('#selectRoles').change( function() {
            var table = $('#sortable_table').DataTable();
            table.draw();
        } );

        $('#hub_edit_writing_group').on('hidden.bs.modal', function () {
            //Clean up traces of old form
            $('#redcap-edit-frame').attr('src','');

        });
    });
</script>
<div class="container">
    <?php
    if(isset( $_REQUEST['message'] )) {
        echo '<div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;" id="succMsgContainer">'.$module->getMessageHandler()->fetchMessage('writingGroup',$_REQUEST['message']).'</div>';
    }
    ?>
    <div class="backTo">
        <a href="<?=$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=ttl&record='.$recordId?>">< Back to Concept</a>
    </div>
    <?php if($concept != "" && $concept != null) {?>
    <h3 class="concepts-title-title"><?=$concept->getConceptId().": Writing Group"?></h3>

    <?php if($isAdmin || $harmonistPermEditConcept){
        $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['HARMONIST'], $recordId, "concept_sheet", "");
        $survey_link = APP_PATH_WEBROOT_FULL . "/surveys/?s=".$module->escape($passthru_link['hash'])."&modal=modal";

        $gotoredcap = htmlentities(APP_PATH_WEBROOT_ALL."DataEntry/record_home.php?pid=".$pidsArray['HARMONIST']."&arm=1&id=".$recordId,ENT_QUOTES);
        ?>
        <div class="btn-group hidden-xs pull-right">
            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                Admin <span class="caret"></span>
            </button>
            <ul class="dropdown-menu">
                <li><a href="<?=$gotoredcap?>" target="_blank">Go to REDCap</a></li>
            </ul>
        </div>
    <?php } ?>
    <p class="hub-title concepts-title-title" style="font-weight: normal"><?=$concept->getConceptTitle()?></p>

    <table class="table table_requests sortable-theme-bootstrap" data-sortable>
        <div class="row request">
            <div class="col-md-2 col-sm-12"><strong>Working Group:</strong></div>
            <div class="col-md-6 col-sm-12"><?=$concept->getWorkingGroup();?> </div>
            <div class="col-md-4"><strong>Start Date: </strong><?=$concept->getStartDate();?> </span></div>
        </div>
        <div class="row request">
            <div class="col-md-2 col-sm-12"><strong>Contact:</strong> </div>
            <div class="col-md-6 col-sm-12"><?=$concept->getContact();?></div>
            <div class="col-md-4"><strong>Status: </strong><?=$concept->getStatus();?></div>
        </div>
    </table>
        <div style="float:right;padding-bottom:10px;">
            <?php if($canUserEdit){ ?>
            <a href="#" onclick="$('#hub_new_writing_group_member').modal('show');" class="btn btn-success btn-md"><span class="fa fa-plus"></span> Member</a>
            <?php } ?>
        </div>
    <div class="optionSelect conceptSheets_optionMenu">
        <div style="float:left" id="options_wrapper"></div>
        <div style="float:right">
            <div style="float:left;margin-top: 8px;padding-left: 10px">
                Roles:
            </div>
            <div style="float:left;padding-left:10px">
                <select class="form-control" name="selectRoles" id="selectRoles">
                    <option value="">Select All</option>
                    <?php
                    $cmemberRole = $module->getChoiceLabels('cmember_role', $pidsArray['HARMONIST']);
                    $params = [
                        'project_id' => $pidsArray['REGIONS'],
                        'return_format' => 'json-array',
                        'filterLogic' => "[showregion_y] = 1",
                        'events' => ['region_name']
                    ];
                    $regions = \REDCap::getData($params);
                    foreach ($regions as $region){
                        array_push($cmemberRole,$region['region_name']);
                    }
                    sort($cmemberRole);
                    foreach ($cmemberRole as $text){
                        echo "<option value='".htmlspecialchars($text,ENT_QUOTES)."'>".htmlspecialchars($text,ENT_QUOTES)."</option>";
                    }
                    ?>
                </select>
            </div>
        </div>
    </div>
    <div>
        <table class="table table_requests sortable-theme-bootstrap concepts-table" data-sortable id="sortable_table">
            <thead>
                <tr>
                    <th class="sorted_class" data-sorted="true" data-sorted-direction="descending">Name</th>
                    <th class="sorted_class" >Email</th>
                    <th class="sorted_class">Role</th>
                    <th class="sorted_class">Order</th>
                    <?php if($canUserEdit || $harmonistPermEditConcept){?>
                    <th class="sorted_class">Actions</th>
                    <?php } ?>
                </tr>
            </thead>
            <tbody>
            <?php
                foreach ($writingGroupMemberList as $writingGroupMember) {
                    $edit = "";
                    if(($harmonistPermEditConcept && $writingGroupMember->getRoleId() == $current_user["person_region"]) || $canUserEdit){
                        $edit = '<a href="#" class="btn btn-default open-codesModal" onclick="editIframeModal(\'hub_edit_writing_group\',\'redcap-edit-frame\',\'' . $writingGroupMember->getEditLink() . '\');"><em class="fa fa-pencil"></em></a>';
                    }
                    echo "<tr>
                        <td style='width: 25%'>".$writingGroupMember->getName()."</td>
                        <td style='width: 30%'><a href='mailto:".$writingGroupMember->getEmail()."'>".$writingGroupMember->getEmail()."</a></td>
                        <td style='width: 15%'>".$writingGroupMember->getRole()."</td>
                        <td style='width: 15%'>".$writingGroupMember->getOrder()."</td>";
                    if($canUserEdit || $harmonistPermEditConcept) {
                        echo "<td style='width: 5%'>" . $edit . "</td>";
                    }
                        echo "</tr>";
                }
            ?>
            </tbody>
        </table>
    </div>
    <!-- MODAL WRITING GROUP-->
    <div class="modal fade" id="hub_edit_writing_group" tabindex="-1" role="dialog" aria-labelledby="Codes">
        <div class="modal-dialog" role="document" style="width: 800px">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="edit_title">Edit Member</h4>
                </div>
                <div class="modal-body">
                    <iframe id="redcap-edit-frame" message="U" name="redcap-edit-frame" src="" style="border: none;height: 810px;width: 100%;"></iframe>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!-- MODAL NEW WRITING GROUP MEMBER-->
    <div class="modal fade" id="hub_new_writing_group_member" tabindex="-1" role="dialog" aria-labelledby="Codes">
        <div class="modal-dialog" role="document" style="width: 950px">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">New Member</h4>
                </div>
                <div class="modal-body">
                    <iframe id="redcap-new-frame" name="redcap-new-frame" message="N" src="<?=$module->getSurveyLinkNewInstance("writing_group_core", $recordId, $pidsArray['HARMONIST']);?>" style="border: none;height: 810px;width: 100%;"></iframe>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
<?php }else{ ?>
    <div class="alert alert-warning fade in col-md-12"><em>Concept #<?=$recordId?> is not available at this time.</em></div>
<?php } ?>

