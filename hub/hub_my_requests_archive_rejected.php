<?php
namespace Vanderbilt\HarmonistHubExternalModule;
?>
<script>
    $(document).ready(function() {
        var person_name = <?=json_encode($person_name)?>;
        Sortable.init();
        $('#table_archive').dataTable( {"pageLength": 50,"order": [0, "desc"]});

        $('#table_archive_filter').appendTo( '#options_wrapper' );
        $('#table_archive_filter').attr( 'style','float: left;padding-left: 170px;padding-top: 5px;' );

        //when any of the filters is called upon change datatable data
        $('#selectReqType').change( function() {
            var table = $('#table_archive').DataTable();
            table.draw();
        } );
    } );

    //To filter the data
    $.fn.dataTable.ext.search.push(
        function( settings, data, dataIndex ) {
            var type = $('#selectReqType option:selected').val();
            var column_type = data[1];


            if(type != 'Select All' && column_type.match(type) != null){
                return true;
            }else if(type == 'Select All'){
                return true;
            }


            return false;
        }
    );
</script>
<div class="container">
    <div class="backTo">
        <a href="<?=$module->getUrl('index.php?pid='.$pidsArray['PROJECTS'].'&option=mra&type=a')?>">< Back to Requests Archive</a>
    </div>
    <h3>Rejected & Deactivated Requests Archive</h3>
    <p class="hub-title"><?=$settings['hub_req_arc_rejected_text']?></p>
    <br>
    <div class="optionSelect conceptSheets_optionMenu">
        <div style="float:left" id="options_wrapper"></div>
        <div style="float:right">
            <div style="float:left;padding-left:30px;margin-top: 8px;">
                Request type:
            </div>
            <div style="float:left;padding-left:10px">
                <select class="form-control" name="selectReqType" id="selectReqType">
                    <option value="">Select All</option>
                    <?php
                    foreach ($request_type_label as $reqType){
                        echo "<option value='".$reqType."'>".$reqType."</option>";
                    }
                    ?>
                </select>
            </div>
        </div>
    </div>
    <div>
        <table class="table table_requests sortable-theme-bootstrap" data-sortable id="table_archive">
            <?php
            $RecordSetRM = \REDCap::getData($pidsArray['RMANAGER'], 'array',null,null,null,null,false,false,false,"[approval_y] != 1");
            $request_reject = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRM);
            if(!empty($request_reject)) {
                $RecordSetRegions = \REDCap::getData($pidsArray['REGIONS'], 'array', null,null,null,null,false,false,false,"[showregion_y] = 1");
                $regions = ProjectData::getProjectInfoArray($RecordSetRegions);
                ArrayFunctions::array_sort_by_column($regions, 'region_code');

                $user_req_header = \Vanderbilt\HarmonistHubExternalModule\getRequestHeader($pidsArray['REGIONS'], $regions, $current_user['person_region'], $settings['vote_grid'], '2','archive');

                $requests_counter = 0;
                foreach ($request_reject as $req) {
                    $user_req_body .= \Vanderbilt\HarmonistHubExternalModule\getHomeRequestHTML($module, $pidsArray, $req, $regions, $request_type_label, $current_user, 2, $settings['vote_visibility'], $settings['vote_grid'],'none','archive');
                    if($user_req_body != ""){
                        $requests_counter++;
                    }
                }
                if($requests_counter > 0) {
                    echo $user_req_header . $user_req_body;
                }else{?>
                    <tbody>
                    <tr>
                        <td><span><em>No requests available</em></span></td>
                    </tr>
                    </tbody>
                <?php }
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

<!-- MODAL EDIT PROCESS-->
<div class="modal fade" id="hub_process_survey" tabindex="-1" role="dialog" aria-labelledby="Codes">
    <div class="modal-dialog" role="document" style="width: 800px">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Process</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" value="0" id="comment_loaded">
                <iframe class="commentsform" id="redcap-edit-frame-admin" name="redcap-edit-frame-admin" src="" style="border: none;height: 810px;width: 100%;"></iframe>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>