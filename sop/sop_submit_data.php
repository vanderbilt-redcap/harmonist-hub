<?php
namespace Vanderbilt\HarmonistHubExternalModule;
?>
<script language="JavaScript">
    $(document).ready(function() {
        //To change the text on select
        $(".dropdown-menu-custom li").click(function(){
            var selText = $(this).html();
            $(this).parents('.dropdown').find('.dropdown-toggle').html(selText+' <span class="caret" style="float: right;margin-top:8px"></span>');
        });

        $("#sortable_table").dataTable( {"pageLength": 50});
        $('#dataUploadForm').submit(function () {
            var data = $('#dataUploadForm').serialize();
            uploadDataToolkit(data,<?=json_encode($module->getUrl("hub/hub_data_upload_security_AJAX.php")."&NOAUTH")?>);
            return false;
        });

        $('#changeStatus').submit(function () {
            var data = "&status="+$('#data_status').find('.status').attr('status');
            data += "&region="+$('#region').val();
            data += "&status_record="+$('#status_record').val();
            data += "&data_response_notes="+encodeURIComponent($('#data_response_notes').val());
            CallAJAXAndRedirect(data,<?=json_encode($module->getUrl('sop/sop_submit_data_change_status_AJAX.php')."&NOAUTH")?>,<?=json_encode($module->getUrl("index.php?pid=".$pidsArray['PROJECTS']."&option=upd&NOAUTH&message=S"))?>);
            return false;
        });

        jQuery('[data-toggle="popover"]').popover({
            html : true,
            content: function() {
                return $(jQuery(this).data('target-selector')).html();
            },
            title: function(){
                return '<span style="padding-top:0px;">'+jQuery(this).data('title')+'<span class="close" style="line-height: 0.5;padding-top:0px;padding-left: 10px">&times;</span></span>';
            }
        }).on('shown.bs.popover', function(e){
            var popover = jQuery(this);
            jQuery(this).parent().find('div.popover .close').on('click', function(e){
                popover.popover('hide');
            });
            $('div.popover .close').on('click', function(e){
                popover.popover('hide');
            });

        });
        //We add this or the second time we click it won't work. It's a bug in bootstrap
        $('[data-toggle="popover"]').on("hidden.bs.popover", function() {
            if($(this).data("bs.popover").inState == undefined){
                //BOOTSTRAP 4
                $(this).data("bs.popover")._activeTrigger.click = false;
            }else{
                //BOOTSTRAP 3
                $(this).data("bs.popover").inState.click = false;
            }
        });

        //To prevent the popover from scrolling up on click
        $("a[rel=popover]")
            .popover()
            .click(function(e) {
                e.preventDefault();
            });
    } );
</script>

<?php
$RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', null);
$request_dataCall = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP,$pidsArray['SOP'],array('sop_active' => '1', 'sop_finalize_y' => array(1=>'1')));
ArrayFunctions::array_sort_by_column($request_dataCall,'sop_due_d');
$open_data_calls = "";
$completed_data_calls = "";
$personRegion = arrayKeyExistsReturnValue($current_user, ['person_region']);
if(!empty($request_dataCall)) {
    foreach ($request_dataCall as $sop) {
        if ($sop['sop_closed_y'] != "1") {
            $sopDataResponseStatus = arrayKeyExistsReturnValue($sop, ['data_response_status',$personRegion]);
            if($sopDataResponseStatus == "0" || $sopDataResponseStatus == "1" || $sopDataResponseStatus == ""){
                $open_data_calls .= getDataCallRow($module, $pidsArray,$sop,$isAdmin,$current_user,$secret_key,$secret_iv,$settings['vote_grid'],'s');
            }else{
                $completed_data_calls .= getDataCallRow($module, $pidsArray,$sop,$isAdmin,$current_user,$secret_key,$secret_iv,$settings['vote_grid'],'s');
            }
        }
    }
}

if(array_key_exists('message', $_REQUEST) && ($_REQUEST['message'] == 'S')){
    ?><div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;"
         id="succMsgContainer">Your Data Request status has been successfully modified.
    </div><?php
}
?>
<div class="backTo">
    <a href="<?=$module->getUrl('index.php').'&NOAUTH&option=dat'?>">< Back to Data</a>
</div>
<div class="optionSelect">
    <h3>Check and Submit Data</h3>
    <?=filter_tags($settings['hub_check_submit_text'])?>

</div>

<div class="pull-right">
    <p><a href="<?=$module->getUrl('index.php').'&NOAUTH&option=lgd&type=upload'?>">View Data Activity Log</a> | <a href="<?=$module->getUrl('index.php')."&NOAUTH&option=pdc"?>">View Past Data Calls</a></p>
</div>

<div>
    <ul class="list-inline">
        <li><span class="label label-default_light" title="Not Started"><i class="fa-label-legend fa fa-times text-default_light" aria-hidden="true"></i></span> <a href="#" data-toggle="tooltip" title="No regional activity on this request." data-placement="top" class="custom-tooltip" style="vertical-align: -2px;cursor:default;">Not started</a></li>
        <li><span class="label label-warning" title="Partial Data"><i class="fa-label-legend fa fa-wrench" aria-hidden="true"></i></span> <a href="#" data-toggle="tooltip" title="Your region has submitted some but not all data for this data request." data-placement="top" class="custom-tooltip" style="vertical-align: -2px;cursor:default;">Partial Data</a></li>
        <li><span class="label label-approved" title="Complete Data"><i class="fa-label-legend fa fa-check" aria-hidden="true"></i></span> <a href="#" data-toggle="tooltip" title="Your region has submitted a full regional dataset for this data request." data-placement="top" class="custom-tooltip" style="vertical-align: -2px;cursor:default;">Complete Data</a></li>
        <li><span class="label label-default" title="Not Applicable Data"><i class="fa-label-legend fa fa-ban" aria-hidden="true"></i></span> <a href="#" data-toggle="tooltip" title="Your region does not have the requested data for this project." data-placement="top" class="custom-tooltip" style="vertical-align: -2px;cursor:default;">Data Not Available</a></li>
        <li><span class="label label-default" title="Not Applicable Region"><i class="fa-label-legend fa fa-times" aria-hidden="true"></i></span> <a href="#" data-toggle="tooltip" title="Your region is not one of the requested regions for this project." data-placement="top" class="custom-tooltip" style="vertical-align: -2px;cursor:default;">Region Not Requested</a></li>
        <li><span class="label label-other" title="Other Status"><i class="fa-label-legend fa fa-question" aria-hidden="true"></i></span> <a href="#" data-toggle="tooltip" title="Some other data submission or project participation status applies." data-placement="top" class="custom-tooltip" style="vertical-align: -2px;cursor:default;">Other Status</a></li>
    </ul>
</div>
<div class="optionSelect">
    <div class="panel panel-default">
        <div class="panel-heading ">
            <h3 class="panel-title">
                Open Data Calls
            </h3>
        </div>
        <div class="table-responsive">
            <table class="table sortable-theme-bootstrap" data-sortable>
                <?php
                if(!empty($open_data_calls)) {
                    echo getDataCallHeader($pidsArray['REGIONS'], $personRegion,$settings['vote_grid']);
                    echo $open_data_calls;
                }else{?>
                    <tbody>
                    <tr>
                        <td><span><em>No Open Data Calls available</em></span></td>
                    </tr>
                    </tbody>
                    <?php
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel panel-success">
        <div class="panel-heading ">
            <h3 class="panel-title">
                Completed Data Calls
            </h3>
        </div>

        <div class="table-responsive">
            <table class="table sortable-theme-bootstrap" data-sortable>
                <?php
                if(!empty($completed_data_calls)) {
                    echo \Vanderbilt\HarmonistHubExternalModule\getDataCallHeader($pidsArray['REGIONS'], $personRegion,$settings['vote_grid']);
                    echo $completed_data_calls;
                }else{?>
                    <tbody>
                    <tr>
                        <td><span><em>No Completed Data Calls available</em></span></td>
                    </tr>
                    </tbody>
                    <?php
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
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
                            $status_type = $module->getChoiceLabels('data_response_status', $pidsArray['SOP']);
                            $status_icon_color = array(0=>"label-default_light",1=>"label-warning",2=>"label-approved",3=>"label-default",4=>"label-default",9=>"label-other");
                            $status_icon = array(0=>"fa-times text-default_light",1=>"fa-wrench",2=>"fa-check",3=>"fa-ban",4=>"fa-times",9=>"fa-question");
                            $selected = ' <a href="#" data-toggle="dropdown" style="width:290px" class="dropdown-toggle form-control output_select btn-group" id="default-select-value"><span class="fa-label-legend status fa fa-fw fa-times text-default_light label-default_light " style="padding: 2px;border-radius:3px;color:#fff" aria-hidden="true" status="0"><span class="status-text"> Not Started</span><span class="caret" style="float: right;margin-top:8px"></span></a>';
                            $menu = "";
                            foreach ($status_type as $index=>$status){
                                $menu .= '<li style="width:290px"><a href="#" tabindex="1"><span class="fa-label status fa fa-fw '.$status_icon[$index].' '.$status_icon_color[$index].'" style="padding: 2px;border-radius:3px;color:#fff" aria-hidden="true" status="'.htmlspecialchars($index,ENT_QUOTES).'"></span><span class="status-text"> '.htmlspecialchars($status,ENT_QUOTES).'</span></a></li>';
                            }
                            ?>
                            <ul class="nav" style="margin:0;width:290px" id="data_status" name="data_status">
                                <li class="menu-item dropdown">
                                    <?=$selected?>
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