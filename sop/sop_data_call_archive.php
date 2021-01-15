<?php
namespace Vanderbilt\HarmonistHubExternalModule;
?>
<script>
    $(document).ready(function() {
        Sortable.init();
        $('#sortable_table').dataTable( {"pageLength": 50,"order": [0, "desc"]});

        $('#dataUploadForm').submit(function () {
            var data = $('#dataUploadForm').serialize();
            uploadDataToolkit(data);
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
<div class="container">
    <div class="backTo">
        <a href="<?=$module->getUrl('index.php?pid='.IEDEA_PROJECTS.'&option=upd')?>">< Back to Submit Data</a>
    </div>
    <h3>Data Call Archive</h3>
    <p class="hub-title"><?=$settings['hub_datacall_archive']?></p>
    <br>
    <div class="table-responsive">
        <table class="table table_requests sortable-theme-bootstrap" data-sortable id="sortable_table">
            <?php
            $RecordSetSOP = \REDCap::getData(IEDEA_SOP, 'array', null);
            $request_dataCall_arc = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP,array('sop_active' => '1', 'sop_finalize_y' => array(1=>'1')));
            ArrayFunctions::array_sort_by_column($request_dataCall_arc,'sop_due_d',SORT_DESC);
            if(!empty($request_dataCall_arc)) {
                echo \Functions\getDataCallHeader($current_user['person_region'],1);
                foreach ($request_dataCall_arc as $sop){
                    echo \Functions\getDataCallRow($module, $sop,$isAdmin,$current_user,$secret_key,$secret_iv,1,'a');
                }
            }else{?>
                <tbody>
                <tr>
                    <td><span><em>No archived Data Calls to display.</em></span></td>
                </tr>
                </tbody>
            <?php }?>
        </table>
        <div class="modal fade" id="modal-data-upload-confirmation" tabindex="-1" role="dialog" aria-labelledby="Codes">
            <form class="form-horizontal" action="" method="post" id='dataUploadForm'>
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title">Upload Data</h4>
                        </div>
                        <div class="modal-body">
                            <span>Are you ready to submit data for concept <span id="data-submit-concept" style="font-weight: bold"></span>?</span>
                            <br>
                            <span>This will redirect you to the Data Toolkit.</span>
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
    </div>
</div>