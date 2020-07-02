<?php
$record_id = $_REQUEST['record'];
$sop = getProjectInfoArray(IEDEA_SOP,array('record_id' => $_REQUEST['record']),"simple");

$harmonist_perm = hasUserPermissions($current_user['harmonist_perms'], 1);
?>
<br>
<?php
if(array_key_exists('message', $_REQUEST) && ($_REQUEST['message'] == 'S')){
    ?>
    <div class="alert alert-success fade in col-md-12" style="line-height: 2.5em;border-color: #b2dba1 !important;"
         id="succMsgContainer">Data Request successfully finalized.  <a class="btn btn-success" style="float:right" href="index.php?option=upd">View Data Request</a>
    </div>
    <?php
}
?>
<div class="container" style="padding-bottom: 5px;">
    <div class="optionSelect">
        <h3 style="color:#5cb85c">Steps Complete <i class="fa fa-check" aria-hidden="true"></i></span><span></h3>
        <div class="hub-title">
            <p>Your Data Request has been generated successfully. You can review and download the PDF below or download a ZIP file with an HTML and a PDF version. If you need to make changes, <a href="index.php?&option=ss1&record=<?=$record_id?>&step=3">you can go back to edit your data request</a>.</p>
            <?php
            echo $settings['hub_steps_complete_text'];
            if($sop['sop_visibility'] != "2"){
                $style = "margin: 0 auto;width: 350px; margin-top:20px";
            }else{
                $style = "margin: 0 auto;width: 450px; margin-top:20px;text-align:center";
            }?>
            <div style="<?=$style?>">
                <div style="display: inline-block; margin-right:20px">
                    <?php if($sop['sop_status'] == "1"){

                       ?><a href="index.php?&option=upd" class="btn btn-default btn-md">View in Submit Data</a><?php
                    }else{
                        ?><a href="index.php?&option=smn" class="btn btn-default btn-md">View in My Drafts</a><?php
                    }?>
                </div>
                <?php if($sop['sop_visibility'] != "2"){?>
                    <div style="display: inline-block">
                        <a href="index.php?&option=spr&record=<?=$record_id?>" class="btn btn-success btn-md">Send for Review</a>
                    </div>
                <?php }else if($harmonist_perm){
                    $RecordSetSOP = new \Plugin\RecordSet($projectSOP, array('record_id' => $_REQUEST['record']));
                    $passthru_link = \Plugin\Passthru::passthruToSurvey($RecordSetSOP->getRecords()[0],"finalization_of_data_request",true);
                    $survey_link = 'surveyPassthru.php?&surveyLink='.$passthru_link;
                    ?>
                    <div style="display: inline-block">
                        <a href="#" onclick="editIframeModal('hub-modal-data-finalize','redcap-finalize-frame','<?=$survey_link?>');" class="btn btn-primary btn-md">Finalize Data Request</a>
                    </div>
                    <!-- MODAL FINALIZE-->
                    <div class="modal fade" id="hub-modal-data-finalize" tabindex="-1" role="dialog" aria-labelledby="Codes">
                        <div class="modal-dialog" role="document" style="width: 800px;">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    <h4 class="modal-title" style="float:left">Finalize Data Request</h4>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" value="0" id="comment_loaded_finalize">
                                    <iframe class="commentsform" id="redcap-finalize-frame" name="redcap-finalize-frame" message="S" src="" style="border: none;height: 500px;width: 100%;"></iframe>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php }?>
            </div>
        </div>
    </div>
    <div class="pull-left backTo">
        <a href="index.php?&option=ss1&record=<?=$record_id?>&step=3">< Back to Edit Data Request</a>
    </div>
</div>
<div class="container">
<div class="panel panel-default" style="margin-bottom: 40px">
    <div class="panel-heading" style="height: 38px">
        <form method="POST" action="sop/generate_zip.php" id='form_steps_generate_zip' >
        <h3 class="panel-title">
            <a data-toggle="collapse" href="#collapse1">Data Request</a>
            <?php

            #Concept Files
            $sql = "SELECT doc_name,stored_name,file_extension FROM redcap_edocs_metadata WHERE doc_id ='" . db_escape($sop["sop_finalpdf"])."'";
            $q = db_query($sql);
            $row_sop_file = db_fetch_assoc($q);

            if(!empty($row_sop_file['doc_name'])) {
                $extension = ($row_concept_file['file_extension'] == 'pdf')? "pdf-icon.png" : "word-icon.png";
                $pdf_path = APP_PATH_PLUGIN."/loadPDF.php?edoc=".$sop["sop_finalpdf"];

                $file_icon = getFileLink($sop["sop_finalpdf"],'1','',$secret_key,$secret_iv,$current_user['record_id'],"");
                ?>

                    <span style="float: right;padding-right: 15px;color:#333 !important;cursor:pointer"><a href="#" onclick="$('#form_steps_generate_zip').submit();" target='_blank'><i class='fa fa-file-o' aria-hidden='true'></i></a></span>
                    <a href="#" onclick="$('#form_steps_generate_zip').submit();"  style="float: right;padding-right: 10px;cursor:pointer;color:#333 !important">Download ZIP </a>
                    <input type="hidden" value="<?=$record_id?>" name="record" id="record">
            <?php }?>
        </h3>
        </form>
    </div>

    <div id="collapse1" class="table-responsive panel-collapse collapse in" aria-expanded="true">
        <?php if(!empty($row_sop_file['doc_name'])) {?>
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
</div>
<script>
    $(document).ready(function() {
        Sortable.init();
        $('html,body').scrollTop(0);
        $("html,body").animate({ scrollTop: 0 }, "slow");

        var iframeurl = <?=json_encode(APP_PATH_PLUGIN)?>;
        iFrameResize(
            {
                initCallback: function (iframe) {
                    iframe.iFrameResizer.sendMessage({
                        message: 'load resources',
                        resources: [
                            iframeurl+'/js/iframe.js'
                        ]
                    });
                }
            },
            '#redcap-finalize-frame'
        );
    });
</script>