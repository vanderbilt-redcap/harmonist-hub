<?php
namespace Vanderbilt\HarmonistHubExternalModule;

$TBLCenter = \REDCap::getData($pidsArray['TBLCENTERREVISED'], 'json-array', null);

$regionstbl = \REDCap::getData($pidsArray['REGIONS'], 'json-array', null,null,null,null,false,false,false,"[showregion_y] = 1");
ArrayFunctions::array_sort_by_column($regionstbl, 'region_code');

$region_array = \Vanderbilt\HarmonistHubExternalModule\getTBLCenterUpdatePercentRegions($TBLCenter, $regionstbl, $settings['pastlastreview_dur']);

$harmonist_perm = ($current_user['harmonist_perms___8'] == 1) ? true : false;

$regions_all = \REDCap::getData($pidsArray['REGIONS'], 'json-array', null,array('record_id','region_tbl_option'));
$map_region = $person_region['region_code'];
foreach($regions_all as $region){
    if($region['record_id'] == $current_user['person_region'] && ($region['region_tbl_option'] != "0" || !array_key_exists('region_tbl_option', $region))){
        $map_region = "";
    }
}

?>
<script>
    //To filter the data
    $.fn.dataTable.ext.search.push(
        function( settings, data, dataIndex ) {
            var region = $('#selectRegion option:selected').text();
            var column_region = data[4];

            if(region != 'Select All' && column_region == region ){
                return true;
            }else if(region == 'Select All'){
                return true;
            }

            return false;
        }
    );

    $(document).ready(function() {
        Sortable.init();
        $('#table_archive').dataTable( {"pageLength": 50,"order": [0, "asc"]});
        setDataset(<?=json_encode($map_region)?>);

        //when any of the filters is called upon change datatable data
        $('#selectRegion').change( function() {
            var table = $('#table_archive').DataTable();
            table.draw();
            setDataset($('#selectRegion option:selected').attr('region_code'));
        } );

        $('#table_archive_filter').appendTo( '#options_wrapper' );
        $('#table_archive_filter').attr( 'style','padding-right: 40%;padding-top: 5px;' );
    } );
</script>
<div class="container">
<?php
if(array_key_exists('message', $_REQUEST)){
    if($_REQUEST['message'] == 'C') {
       echo '<div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;" id="succMsgContainer">The center has been successfully updated.</div>';
    }else if($_REQUEST['message'] == 'N') {
       echo '<div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;" id="succMsgContainer">The center has been successfully created.</div>';
    }
}
?>

    <div class="backTo">
        <a href="<?=$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=dat'?>">< Back to Data</a>
    </div>
</div>
<div class="container">
    <h3>tblCENTER</h3>
    <p class="hub-title"><?=filter_tags($settings['hub_tbl_center_text'])?></p>
</div>
<br>
<?php include(dirname(dirname(__FILE__)).'/map/map_mini.php');?>
<div class="container">
    <?php if($isAdmin || $harmonist_perm){?>
        <div class="optionSelect">
                <div style="margin:0 auto;width: 30%;">
                    <div style="float: left"> <a href="#" onclick="$('#sop_new_center').modal('show');" class="btn btn-success btn-md">Create New Center</a></div>
                    <div style="float: left;padding-left: 10px;"> <a href="<?=APP_PATH_WEBROOT_ALL . "DataEntry/record_status_dashboard.php?pid=".$pidsArray['TBLCENTERREVISED']?>" target="_blank" class="btn btn-default btn-md">Go to REDCap</a></div>
                </div>

                <!-- MODAL NEW CONCEPT-->
                <div class="modal fade" id="sop_new_center" tabindex="-1" role="dialog" aria-labelledby="Codes">
                    <div class="modal-dialog" role="document" style="width: 950px">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title">New Center</h4>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" value="0" id="comment_loaded_center">
                                <iframe class="commentsform" id="redcap-new-center" name="redcap-new-center" message="N" src="<?=$module->escape(APP_PATH_WEBROOT_FULL."surveys/?s=".$pidsArray['SURVEYTBLCENTERREVISED'])?>" style="border: none;height: 810px;width: 100%;"></iframe>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    <?php } ?>
    <div class="optionSelect conceptSheets_optionMenu" id="options_wrapper">
        <div style="float:left">
            <?php
            if($person_region['showregion_y'] == '1') {
                echo '<ul class="list-inline" style="padding-left: 10px;padding-top: 7px;">Your region: <li><span style="padding-right:5px">' . htmlspecialchars($map_region,ENT_QUOTES) . '</span>' . filter_tags(\Vanderbilt\HarmonistHubExternalModule\getTBLCenterUpdatePercentLabel($region_array[$map_region])). '</li></ul>';
            }
            ?>
        </div>
        <div style="float:right">
            <div style="float:left;padding-left:30px;margin-top: 8px;">
                Region:
            </div>
            <div style="float:left;padding-left:10px">
                <select class="form-control" name="selectRegion" id="selectRegion">
                    <option value="" region_code="">Select All</option>
                    <?php
                    if (!empty($regionstbl)) {
                        foreach ($regionstbl as $region){
                            $region = $module->escape($region);
                            if($region['region_code'] == $map_region){
                                echo "<option value='".$region['record_id']."' region_code='".$region['region_code']."' selected>".$region['region_code']."</option>";
                            }else{
                                echo "<option value='".$region['record_id']."' region_code='".$region['region_code']."'>".$region['region_code']."</option>";
                            }

                        }
                    }
                    ?>
                </select>
            </div>
        </div>
    </div>
</div>
<div class="container" style="padding-bottom: 20px;padding-left: 20px;">
    <ul class="list-inline">Other regions:
        <?php
            foreach ($region_array as $pregion => $percent){
                if($map_region != $pregion){
                    echo '<li style="margin-right: 15px;"><span style="padding-right:5px">'.htmlspecialchars($pregion,ENT_QUOTES).'</span>'.\Vanderbilt\HarmonistHubExternalModule\getTBLCenterUpdatePercentLabel($percent).'</li>';
                }
            }
        ?>
    </ul>
</div>
<div class="container">
    <div class="panel panel-default-archive">
        <div class="table-responsive table-archive">
            <table class="table table_requests sortable-theme-bootstrap word-wrap" data-sortable id="table_archive">
                <?php
                if(!empty($TBLCenter)) {?>
                    <colgroup>
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                    </colgroup>
                    <thead>
                    <tr>
                        <th class="sorted_class">Center</th>
                        <th class="sorted_class">Name</th>
                        <th class="sorted_class">Program</th>
                        <th class="sorted_class">Country</th>
                        <th class="sorted_class">Region</th>
                        <th class="sorted_class"><span style="display:block">Database</span><span>Close Date</span></th>
                        <th class="sorted_class"><span style="display:block">Last</span><span>Update</span></th>
                        <th class="sorting_disabled" data-sortable="false">Missing Fields</th>
                        <?php if ($harmonist_perm  || $isAdmin) {?>
                        <th class="sorting_disabled" data-sortable="false">Actions</th>
                        <?php } ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                        foreach ($TBLCenter as $center) {
                            if($center['drop_center'] == '' || !in_array($center['drop_center'],$center)) {
                                $center = $module->escape($center);
                                $buttons = "";
                                if (($harmonist_perm && $center['region'] == $map_region) || $isAdmin) {
                                    $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['SURVEYTBLCENTERREVISED'], $center['record_id'], "tblcenter", "");
                                    $survey_link =  $module->escape(APP_PATH_WEBROOT_FULL . "/surveys/?s=".$passthru_link['hash']);
                                    $buttons = '<div><a href="#" onclick="editIframeModal(\'update_center\',\'redcap-upload-center\',\'' . $survey_link . '\')" class="btn btn-primary btn-xs">Update</a></div>';
                                }

                                $last_update = $center['last_reviewed_d'];
                                if (strtotime($center['last_reviewed_d']) < strtotime(date('Y-m-d', strtotime("-" . $settings['pastlastreview_dur'] . " day")))) {
                                    $last_update = "<span class='text-error'>" . $center['last_reviewed_d'] . "</span>";
                                }

                                $missing_fields = \Vanderbilt\HarmonistHubExternalModule\searchTBLMissingFields($center);

                                echo '<tr>
                                        <td width="350px">' . $center['center'] . '</td>' .
                                    '<td width="450px">' . $center['name'] . '</td>' .
                                    '<td width="150px">' . $center['program'] . '</td>' .
                                    '<td width="100px">' . $center['country'] . '</td>' .
                                    '<td width="90px">' . $center['region'] . '</td>' .
                                    '<td width="130px">' . $center['close_d'] . '</td>' .
                                    '<td width="130px">' . filter_tags($last_update) . '</td>' .
                                    '<td width="130px">' . htmlspecialchars($missing_fields,ENT_QUOTES) . '</td>' ;

                               if ($harmonist_perm || $isAdmin) {
                                   echo '<td width="100px">' . $buttons . '</td>';
                               }


                                echo '</tr>';
                            }
                        }
                    ?>
                    </tbody>
                <?php } ?>
            </table>
        </div>
    </div>
</div>
<!-- MODAL UPDATE-->
<div class="modal fade" id="update_center" tabindex="-1" role="dialog" aria-labelledby="Codes">
    <div class="modal-dialog" role="document" style="width: 800px">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Update Center</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" value="0" id="comment_loaded">
                <iframe class="commentsform" id="redcap-upload-center" name="redcap-upload-center" message="C" src="" style="border: none;height: 810px;width: 100%;"></iframe>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        var iframeurl = <?=json_encode(APP_PATH_PLUGIN)?>;
        iFrameResize(
            {
                initCallback: function (iframe) {
                    iframe.iFrameResizer.sendMessage({
                        message: 'load resources',
                        resources: [
                            iframeurl + '/js/iframe.js'
                        ]
                    });
                }
            },
            '#redcap-upload-center,#redcap-new-center'
        );
    });
</script>
