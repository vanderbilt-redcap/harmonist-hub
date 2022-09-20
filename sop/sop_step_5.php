<?php
namespace Vanderbilt\HarmonistHubExternalModule;

$record_id = htmlentities($_REQUEST['record'],ENT_QUOTES);
$RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', array('record_id' => $record_id));
$sop = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP)[0];

$harmonist_perm = \Vanderbilt\HarmonistHubExternalModule\hasUserPermissions($current_user['harmonist_perms'], 1);
?>
<br>
<?php
if(array_key_exists('message', $_REQUEST) && ($_REQUEST['message'] == 'S')){
    ?>
    <div class="alert alert-success fade in col-md-12" style="line-height: 2.5em;border-color: #b2dba1 !important;"
         id="succMsgContainer">Data Request successfully finalized.  <a class="btn btn-success" style="float:right" href="<?=$module->getUrl('index.php?NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=upd')?>">View Data Request</a>
    </div>
    <?php
}
?>
<div class="container" style="padding-bottom: 5px;">
    <div class="optionSelect">
        <h3 style="color:#5cb85c">Steps Complete <i class="fa fa-check" aria-hidden="true"></i></span><span></h3>
        <div class="hub-title">
            <p>Your Data Request has been generated successfully. You can review and download the PDF below or download a ZIP file with an HTML and a PDF version. If you need to make changes, <a href="<?=$module->getUrl('index.php?NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=ss1&record='.$record_id.'&step=3')?>">you can go back to edit your data request</a>.</p>
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

                       ?><a href="<?=$module->getUrl('index.php?NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=upd')?>" class="btn btn-default btn-md">View in Submit Data</a><?php
                    }else{
                        ?><a href="<?=$module->getUrl('index.php?NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=smn')?>" class="btn btn-default btn-md">View in My Drafts</a><?php
                    }?>
                </div>
                <?php if($sop['sop_visibility'] != "2"){?>
                    <div style="display: inline-block">
                        <a href="<?=$module->getUrl('index.php?NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=spr&record='.$record_id)?>" class="btn btn-success btn-md">Send for Review</a>
                    </div>
                <?php }else if($harmonist_perm){
                    $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['SOP'], 1, "finalization_of_data_request","");
                    $survey_link = $module->getUrl('surveyPassthru.php?NOAUTH&surveyLink='.APP_PATH_SURVEY_FULL . "?s=".$passthru_link['hash']);
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
        <a href="<?=$module->getUrl('index.php?NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=ss1&record='.$record_id.'&step=3')?>">< Back to Edit Data Request</a>
    </div>
</div>
<div class="container">
<div class="panel panel-default" style="margin-bottom: 40px">
    <div class="panel-heading" style="height: 38px">
        <form method="POST" action="<?=$module->getUrl('sop/sop_step_5_generate_zip.php?NOAUTH')?>" id='form_steps_generate_zip' >
        <h3 class="panel-title">
            <a data-toggle="collapse" href="#collapse1">Data Request</a>
            <?php
            #Concept Files
            $q = $module->query("SELECT doc_name,stored_name,file_extension FROM redcap_edocs_metadata WHERE doc_id = ?",[$sop["sop_finalpdf"]]);
            $row_sop_file = $q->fetch_assoc();

            if(!empty($row_sop_file['doc_name'])) {
                $extension = ($row_sop_file['file_extension'] == 'pdf')? "pdf-icon.png" : "word-icon.png";
                $pdf_path = $module->getUrl("loadPDF.php?NOAUTH&pid=".$pidsArray['PROJECTS']."&edoc=" . $sop["sop_finalpdf"]."#navpanes=0&scrollbar=0");

                $file_icon = \Vanderbilt\HarmonistHubExternalModule\getFileLink($module, $pidsArray['PROJECTS'], $sop["sop_finalpdf"],'1','',$secret_key,$secret_iv,$current_user['record_id'],"");
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