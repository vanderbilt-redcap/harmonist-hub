<?php
namespace Vanderbilt\HarmonistHubExternalModule;

$back_button = '<a href="'.$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'">< Back to Home</a>';

$person_name = "";
if($_REQUEST['type'] != ""){
    if($_REQUEST['type'] == 'h'){
        $person_name = $name;
        $back_button = '<a href="'.$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'">< Back to Home</a>';
    }else if($_REQUEST['type'] == 'r'){
        $back_button = '<a href="'.$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=hub'.'">< Back to Requests</a>';
    }else if($_REQUEST['type'] == 'a'){
        $back_button = '<a href="'.$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=adm'.'">< Back to Admin</a>';
    }
}
?>
<script>
    $(document).ready(function() {
        var person_name = <?=json_encode($person_name)?>;
        Sortable.init();
        $('#table_archive').dataTable( {"pageLength": 50,"order": [0, "desc"],"oSearch": {"sSearch": person_name}});

        $('#table_archive_filter').appendTo( '#options_wrapper' );
        $('#table_archive_filter').attr( 'style','float: left;padding-left: 170px;padding-top: 5px;' );

        //when any of the filters is called upon change datatable data
        $('#selectFinal, #selectReqType').change( function() {
            var table = $('#table_archive').DataTable();
            table.draw();
        } );
    } );

    //To filter the data
    $.fn.dataTable.ext.search.push(
        function( settings, data, dataIndex ) {
            var final = $('#selectFinal option:selected').val();
            var type = $('#selectReqType option:selected').val();
            var column_final = data[5];
            var column_type = data[1];
            var typePosition = column_type.search(type)
            var finalPosition = column_final.search(final)

            if(final != 'Select All' && finalPosition >= 0){
                if(type != 'Select All' && typePosition >= 0){
                    return true;
                }else if(type == 'Select All'){
                    return true;
                }
            }else if(final == 'Select All'){
                if(type != 'Select All' && ctypePosition >= 0){
                    return true;
                }else if(type == 'Select All'){
                    return true;
                }
            }

            return false;
        }
    );
</script>
<div class="container">
    <div class="backTo">
        <?=$back_button?>
    </div>
    <h3>Requests Archive</h3>
    <p class="hub-title"><?=filter_tags($settings['hub_req_archive_text'])?></p>
    <br>
    <?php if($isAdmin){?>
    <div class="pull-right">
        <p><a href="<?=$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=mrr&type=a'?>">View Rejected & Deactivated Requests</a></p>
    </div>
    <?php }?>
    <div class="optionSelect conceptSheets_optionMenu">
        <div style="float:left" id="options_wrapper"></div>
        <div style="float:right">
            <div style="float:left;padding-left:30px;margin-top: 8px;">
                Final status:
            </div>
            <div style="float:left;padding-left:10px">
                <select class="form-control" name="selectFinal" id="selectFinal">
                    <option value="">Select All</option>
                    <option value="None">Not finalized</option>
                    <option value="Approved">Approved</option>
                    <option value="Not Approved">Not Approved</option>
                </select>
            </div>
        </div>
        <div style="float:right">
            <div style="float:left;padding-left:30px;margin-top: 8px;">
               Request type:
            </div>
            <div style="float:left;padding-left:10px">
                <select class="form-control" name="selectReqType" id="selectReqType">
                    <option value="">Select All</option>
                    <?php
                        foreach ($request_type_label as $value => $label){
                            if(!in_array($value,$default_values->getHideChoice($pidsArray['RMANAGER'])[$pidsArray['RMANAGER']]['request_type'])) {
                                echo "<option value='" . $label . "'>" . $label . "</option>";
                            }
                        }
                    ?>
                </select>
            </div>
        </div>
    </div>
    <div>
        <table class="table table_requests sortable-theme-bootstrap" data-sortable id="table_archive">
            <?php
            if(!empty($requests)) {
                $RecordSetRegions = \REDCap::getData($pidsArray['REGIONS'], 'array', null,null,null,null,false,false,false,"[showregion_y] = 1");
                $regions = ProjectData::getProjectInfoArray($RecordSetRegions);
                ArrayFunctions::array_sort_by_column($regions, 'region_code');

                $user_req_header = \Vanderbilt\HarmonistHubExternalModule\getRequestHeader($pidsArray['REGIONS'], $regions, $current_user['person_region'], $settings['vote_grid'], '1','archive');

                $requests_counter = 0;
                foreach ($requests as $req) {
                    $user_req_body .= \Vanderbilt\HarmonistHubExternalModule\getHomeRequestHTML($module, $pidsArray, $req, $regions, $request_type_label, $current_user, 0, $settings['vote_visibility'], $settings['vote_grid'],'none','archive');
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