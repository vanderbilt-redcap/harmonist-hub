<?php
namespace Vanderbilt\HarmonistHubExternalModule;
?>
<script language="JavaScript">
    $(document).ready(function() {
        $('#makePrivate').submit(function () {
            $('#sop-make-private-confirmation').modal('hide');
            var data = $('#makePrivate').serialize();
            CallAJAXAndShowMessage(data,<?=json_encode($module->getUrl("sop/sop_make_private.php"))?>, "X",window.location.href);
            return false;
        });
        $('#deleteDataRequest').submit(function () {
            var data = $('#deleteDataRequest').serialize();
            CallAJAXAndRedirect(data,<?=json_encode($module->getUrl('sop/sop_delete_data_request.php'))?>,<?=json_encode($module->getUrl("index.php?pid=".$pidsArray['PROJECTS']."&option=smn&message=D"))?>);
            return false;
        });
    } );
</script>
<div class="container">
    <?php
    if(array_key_exists('message', $_REQUEST) && $_REQUEST['message'] != ''){
        if($_REQUEST['message'] == 'P') {?>
            <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;" id="succMsgContainer">Data Request has been made public and appears in the orange box below.</div>
            <?php
        }else if($_REQUEST['message'] == 'X') {?>
            <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;" id="succMsgContainer">Data Request has been made private</div>
            <?php
        }
    }
    ?>
    <div class="backTo">
        <a href="<?=$module->getUrl('index.php?pid='.$pidsArray['PROJECTS'].'&option=dat')?>">< Back to Data</a>
    </div>
    <div class="optionSelect">
            <h3>Request Data</h3>
        <?=str_replace('reqdatalink',APP_PATH_WEBROOT_FULL.'surveys/?s='.$pidsArray['DATARELEASEREQUEST'],$settings['hub_req_data_text'])?>
        <div class="optionSelect">
            <div style="margin: 0 auto 15px auto;width: 200px;">
                <div style="display: inline-block">
                    <a href="<?=$module->getUrl('index.php?pid='.$pidsArray['PROJECTS'].'&option=ss1')?>" class="btn btn-success btn-md">Create New Data Request</a>
                </div>
            </div>
        </div>

        <?=$settings['hub_req_data_text_after']?>
    </div>

    <div class="optionSelect">

        <div class="panel panel-default panel-info-sop">
            <div class="panel-heading panel-heading-sop">
                <h3 class="panel-title">
                    <a data-toggle="collapse" href="#collapse">Public Drafts for Review</a>
                </h3>
            </div>
                <div id="collapse" class="table-responsive panel-collapse collapse in">
                    <table class="table sortable-theme-bootstrap sop_discuss" data-sortable>
                    <?php
                    $RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', null,null,null,null,false,false,false,"[sop_status] = '0' AND [sop_active] = '1' AND [sop_visibility] = '2'");
                    $sop_drafts = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP);
                    ArrayFunctions::array_sort_by_column($sop_drafts,'sop_updated_dt',SORT_DESC);
                    if(!empty($sop_drafts)) {?>
                        <colgroup>
                            <col><col><col>
                        </colgroup>
                        <thead>
                        <tr>
                            <th class="sorted_class" data-sorted="true" data-sorted-direction="descending">Data Request Details</th>
                            <th class="sorted_class">Data Contact</th>
                            <th class="sorted_class">Updated On</th>
                            <th class="sorting_disabled" data-sortable="false">Actions</th></tr>
                        </thead>
                        <?php
                        $harmonist_perm = \Vanderbilt\HarmonistHubExternalModule\hasUserPermissions($current_user['harmonist_perms'], 1);

                        $data = "";
                        foreach ($sop_drafts as $draft){
                            $data .= \Vanderbilt\HarmonistHubExternalModule\getDataCallRow($module, $pidsArray,$draft,$isAdmin,$current_user,$secret_key,$secret_iv,0,'p',$harmonist_perm);
                        }
                        echo $data;
                    }else{?>
                        <tbody>
                        <tr>
                            <td><span><em>No public drafts for review available</em></span></td>
                        </tr>
                        </tbody>
                    <?php }?>
                </table>
            </div>
        </div>
    </div>

    <div class="optionSelect">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <a data-toggle="collapse" href="#collapse2">My Drafts</a>
                </h3>
            </div>
            <div id="collapse2" class="table-responsive panel-collapse collapse in" aria-expanded="true">
                <table class="table table_requests sortable-theme-bootstrap" data-sortable="" id="" data-sortable-initialized="true">
                    <?php
                    $RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', null,null,null,null,false,false,false,"[sop_hubuser] = '".$current_user['record_id']."' AND [sop_active] = '1' AND [sop_status] = '0'");
                    $sop_drafts = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP);
                    ArrayFunctions::array_sort_by_column($sop_drafts,'sop_updated_dt',SORT_DESC);
                    if(!empty($sop_drafts)) {?>
                        <colgroup>
                            <col><col><col><col><col><col>
                        </colgroup>
                        <thead>
                        <tr>
                            <th class="sorted_class" style="width: 100px" data-sorted="true" data-sorted-direction="descending">Data Request Details</th>
                            <th class="sorted_class" style="width: 200px">Created On</th>
                            <th class="sorted_class" style="width: 200px">Updated On</th>
                            <th class="sorting_disabled" data-sortable="false" style="width: 90px">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                    <?php
                        $data = "";
                        foreach ($sop_drafts as $draft){
                            $data .= \Vanderbilt\HarmonistHubExternalModule\getDataCallRow($module, $pidsArray,$draft,$isAdmin,$current_user,$secret_key,$secret_iv,0,'m',$harmonist_perm);
                        }
                        echo $data;
                    }else{?>
                        <tbody>
                        <tr>
                            <td><span><em>No drafts available</em></span></td>
                        </tr>
                        </tbody>
                    <?php }?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="sop-make-public" tabindex="-1" role="dialog" aria-labelledby="Codes">
    <div class="modal-dialog" role="document" style="width: 800px">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Send for Review</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" value="0" id="comment_loaded">
                <iframe class="commentsform" id="redcap-edit-frame-make-public" name="redcap-edit-frame-make-public" message="P" src="" style="border: none;height: 810px;width: 100%;"></iframe>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="sop-make-private-confirmation" tabindex="-1" role="dialog" aria-labelledby="Codes">
    <form class="form-horizontal" action="" method="post" id='makePrivate'>
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Make Private</h4>
                </div>
                <div class="modal-body">
                    <div>Are you sure you want to <strong>make this draft Data Request PRIVATE?</strong></div>
                    <div>This will move the draft data request from PUBLIC status to PRIVATE status. It will still be accessible to the original creator of the data request, but will not appear on everyone’s dashboard.</div>
                </div>
                <input type="hidden" id="record" name="record">
                <div class="modal-footer">
                    <button type="submit" form="makePrivate" class="btn btn-default btn-success" id='btnModalRescheduleForm'>Continue</button>
                    <a class="btn btn-default btn-cance;" data-dismiss="modal">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- MODAL DELETE-->
<div class="modal fade" id="admin-modal-delete" tabindex="-1" role="dialog" aria-labelledby="Codes">
    <form class="form-horizontal" action="" method="post" id='deleteDataRequest'>
        <div class="modal-dialog" role="document">
            <div class="modal-content" style="color:#333">
                <div class="modal-header">
                    <button type="button" class="close closeCustomModal" data-dismiss="modal"
                            aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Delete</h4>
                </div>
                <div class="modal-body">
                    <span>Are you sure you want to delete this Data Request?</span>
                    <input type="hidden" value="" id="index_modal_delete" name="index_modal_delete">
                </div>

                <div class="modal-footer">
                    <button type="submit" form="deleteDataRequest" class="btn btn-default btn-delete"
                            id='btnModalDeleteForm'>Delete
                    </button>
                    <a href="#" class="btn btn-default btn-cancel" data-dismiss="modal">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>
<script>
    $(document).ready(function() {
        /*var iframeurl = <?=json_encode(APP_PATH_PLUGIN)?>;
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
            '#redcap-edit-frame-make-public'
        );*/
    });
</script>