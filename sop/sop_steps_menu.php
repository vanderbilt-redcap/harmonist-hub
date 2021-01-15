<?php
namespace Vanderbilt\HarmonistHubExternalModule;
if($_REQUEST['record'] != ""){
    $RecordSetSOP = \REDCap::getData(IEDEA_SOP, 'array', array("record_id" => $_REQUEST['record']));
    $sop = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP)[0];
}

$harmonist_perm = \Functions\hasUserPermissions($current_user['harmonist_perms'], 1);

if(!array_key_exists('record', $_REQUEST) || ($sop !="" && ($isAdmin || $harmonist_perm || $sop['sop_hubuser'] == $current_user['record_id'] || $sop['sop_creator'] == $current_user['record_id'] || $sop['sop_creator2'] == $current_user['record_id'] || $sop['sop_datacontact'] == $current_user['record_id'] ))){
?>
<script>
    var selConcept = "";
    $(document).ready(function () {
        //Initialize tooltips
        $('.nav-tabs > li a[title]').tooltip();

        //Wizard
        $('a[data-toggle="tab"]').on('show.bs.tab', function (e) {

            var $target = $(e.target);

            if ($target.parent().hasClass('disabled')) {
                return false;
            }
        });

        $(".next-step").click(function (e) {
            var $active = $('.wizard .nav-tabs li.active');
            $active.next().removeClass('disabled');
            if($(this).attr('id') == "save_continue_1"  && $('[name=optradio]:checked').val() != "1"  && $('#selectSOP_'+$('[name=optradio]:checked').val()+' option:selected').attr('concept') != $('#selectConcept option:selected').val()){
                changeConcept($('[name=optradio]:checked').val());
            }else{
                nextTab($active);
                if($(this).attr('id') == 'save_continue_1'){
                    $( "#form_steps_menu" ).submit();
                }
            }
        });
        $(".prev-step").click(function (e) {
            var $active = $('.wizard .nav-tabs li.active');
            prevTab($active);
        });

        //STEPS FORM
        var sButton = null;
        var $form = $('#form_steps_menu');
        var $submitButtons = $form.find('.saveAndContinue');
        $('#form_steps_menu').submit(function (event) {

            if (null === sButton) {
                sButton = $submitButtons[0];
            }

            var id = $('#selectSOP_'+$('[name=optradio]:checked').val()).val();
            if (id == undefined || id == ''){
                id = $('#save_option').val();
            }

            if(sButton.name == 'save_continue_0' || sButton.name == 'save_continue_1'){
                var sop_hubuser = <?=json_encode($current_user['record_id'])?>;

                var saveoption = $('#save_option').val();
                if($('[name=optradio]:checked').val() == '1'){
                    saveoption = "";
                }
                loadAjax_steps('&save_option='+saveoption+'&selectConcept='+$('#selectConcept').val()+'&option='+$('[name=optradio]:checked').val()+"&sop_hubuser="+sop_hubuser+'&id='+id, <?=json_encode($module->getUrl('sop/sop_step_1_save_AJAX.php'))?>, 'loadFields','1');
            }else if(sButton.name == 'save_continue_2'){
                var checked_values = [];
                $("input[name='tablefields[]']:checked").each(function() {
                    checked_values.push($(this).val());
                });
                //We add the required variables if any
                var required_variables = check_required_variables();
                checked_values.push(required_variables);
                loadAjax_steps('&checked_values='+checked_values+'&id='+id, <?=json_encode($module->getUrl('sop/sop_step_2_save_AJAX.php'))?>, 'loadFields','2');
            }else if(sButton.name == 'save_continue_3' || sButton.name == 'save_and_stay') {
                deleteFile($('#selectSOP_'+$('[name=optradio]:checked').val()).val());
                if ($("[name=sop_extrapdf]").val() != ""){
                    saveFilesIfTheyExist('sop/save-file.php', this);
                }
                var data = $('#form_steps_menu').serialize();
                data +="&sop_inclusion="+encodeURIComponent(tinyMCE.get('sop_inclusion').getContent());
                data +="&sop_exclusion="+encodeURIComponent(tinyMCE.get('sop_exclusion').getContent());
                data +="&sop_notes="+encodeURIComponent(tinyMCE.get('sop_notes').getContent());
                data +="&dataformat_notes="+encodeURIComponent(tinyMCE.get('dataformat_notes').getContent());

                var checked_values = [];
                $("input[name='dataformat_prefer[]']:checked").each(function() {
                    checked_values.push($(this).val());
                });

                loadAjax_steps(data+'&downloaders='+$( "#sortable2" ).sortable( "toArray" )+'&id='+id+'&dataformat_prefer='+checked_values, <?=json_encode($module->getUrl('sop/sop_step_3_save_AJAX.php'))?>, 'loadFields','3');
                if(sButton.name == 'save_and_stay'){
                    $('#modal-save-and-stay').modal('show');
                    //After 25 seconds hide message
                    setTimeout(function(){ $('#modal-save-and-stay').modal('hide'); }, 25000);
                }
            }else if(sButton.name == 'save_continue_4') {
                $('#save_continue_4_spinner').addClass('fa fa-spinner fa-spin');
                $('#previous_4').css('right','220px');
                generate_pdf($('#save_option').val(),<?=json_encode($module->getUrl('sop/sop_step_4_save_AJAX.php'))?>,<?=json_encode($module->getUrl('index.php?pid='.IEDEA_PROJECTS.'&option=ss5'))?>);
            }
            return false;
        });

        $submitButtons.click(function(event) {
            sButton = this;
        });

        //STEP 3
        $( "#sortable1, #sortable2" ).sortable({
            connectWith: ".connectedSortable",
            cursor: "move",
            dropOnEmpty: true
        }).disableSelection();

        $( ".connectedSortable" ).sortable({
            receive: function( event, ui ) {
                 checkStep(3);
            }
        });

        function saveFilesIfTheyExist(url, files) {
            $.ajax({
                url: url,
                type: "POST",
                data:  new FormData(files),
                contentType: false,
                cache: false,
                processData: false,
                success: function(returnData){
                    if (returnData.status != 'success') {
                        alert(returnData.status+" One or more of the files could not be saved."+JSON.stringify(returnData));
                    }else{
                    \Functions\getFileFieldElement(returnData.edoc)
                    }
                }
            });
        }

        $('l')
    });

    function nextTab(elem) {
        $(elem).next().find('a[data-toggle="tab"]').click();
        $('html,body').scrollTop(0);
    }
    function prevTab(elem) {
        $(elem).prev().find('a[data-toggle="tab"]').click();
        $('html,body').scrollTop(0);
    }
</script>
<?php
$step = $_REQUEST['step'];

if($_REQUEST['step'] == '3') {
    echo '<script>$(document).ready(function () {'.
                    'var step = '.json_encode($_REQUEST["step"]).';'.
                    '$("#title_step_1").removeClass("active");'.
                    '$("#title_step_1").removeClass("disabled");'.
                    '$("#title_step_2").removeClass("disabled");'.
                    '$("#title_step_3").removeClass("disabled");'.
                    '$("#title_step_4").removeClass("disabled");'.
                    '$("#step1").removeClass("active");'.
                    '$("#step"+step).addClass("active");'.
                    '$("#title_step_"+step).addClass("active");});'.
        '</script>';
}
?>
<div class="container">
    <div class="row">
        <section>
            <div class="wizard">
                <div class="wizard-inner">
                    <div class="connecting-line"></div>
                    <ul class="nav nav-tabs" role="tablist">

                        <li role="presentation" class="active" id="title_step_1">
                            <a href="#step1" data-toggle="tab" aria-controls="step1" role="tab" title="Step 1: Setup">
                            <span class="round-tab">
                                <i class="fa fa-cog"></i>
                            </span>
                            </a>
                        </li>

                        <li role="presentation" class="disabled" id="title_step_2">
                            <a href="#step2" data-toggle="tab" aria-controls="step2" role="tab" title="Step 2: Choose Variables">
                            <span class="round-tab">
                                <i class="fa fa-hand-pointer-o"></i>
                            </span>
                            </a>
                        </li>
                        <li role="presentation" class="disabled" id="title_step_3">
                            <a href="#step3" data-toggle="tab" aria-controls="step3" role="tab" title="Step 3: Add Details">
                            <span class="round-tab">
                                <i class="fa fa-pencil"></i>
                            </span>
                            </a>
                        </li>

                        <li role="presentation" class="disabled" id="title_step_4">
                            <a href="#step4" data-toggle="tab" aria-controls="step4" role="tab" title="Step 4: Preview Data Request">
                            <span class="round-tab">
                                <i class="fa fa-check"></i>
                            </span>
                            </a>
                        </li>
                    </ul>
                </div>
                <form method="POST" action="" id='form_steps_menu' >
                    <div class="tab-content">
                        <div class="tab-pane active" role="tabpanel" id="step1">
                            <h3><span style="color:#50a9c4;font-weight:bold">STEP 1:</span>&nbsp;&nbsp;Setup</h3>
                            <p><?=$settings['hub_step1']?></p>
                                <?php include('sop_step_1.php');?>
                                <ul class="list-inline pull-right">
                                    <li><button type="button" class="btn btn-primary next-step saveAndContinue" disabled id="save_continue_1" name="save_continue_1">Save and continue</button></li>
                                </ul>
                        </div>
                        <div class="tab-pane" role="tabpanel" id="step2">
                            <h3><span style="color:#50a9c4;font-weight:bold">STEP 2:</span>&nbsp;&nbsp;Choose Variables</h3>
                            <p><em><span class="fa fa-pencil"></span> <span name="step_concept_id" style="font-weight: bold"></span> <span name="step_sop"></span></em></p>
                            <p><?=$settings['hub_step2']?></p>
                            <?php include('sop_step_2.php');?>
                            <ul class="list-inline pull-right">
                                <li><button type="button" class="btn btn-default prev-step" id="previous_2">Previous</button></li>
                                <li><button type="submit" class="btn btn-primary next-step saveAndContinue" disabled id="save_continue_2" name="save_continue_2">Save and continue</button></li>
                            </ul>
                        </div>
                        <div class="tab-pane" role="tabpanel" id="step3">
                            <div class="alert alert-warning fade in" style="display:none" id="warnMsgContainer"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a></div>

                            <h3><span style="color:#50a9c4;font-weight:bold">STEP 3:</span>&nbsp;&nbsp;Add Details</h3>
                            <p><em><span class="fa fa-pencil"></span> <span name="step_concept_id" style="font-weight: bold"></span> <span name="step_sop"></span></em></p>
                            <p><?=$settings['hub_step3']?></p>
                            <?php include('sop_step_3.php')?>
                            <ul class="list-inline pull-right">
                                <li><button type="button" class="btn btn-default prev-step" id="previous_3">Previous</button></li>
                                <li><button type="submit" class="btn btn-primary btn-info-full next-step saveAndContinue" disabled id="save_continue_3" name="save_continue_3">Save and continue</button></li>
                            </ul>

                        </div>
                        <div class="tab-pane" role="tabpanel" id="step4">
                            <h3><span style="color:#50a9c4;font-weight:bold">STEP 4:</span>&nbsp;&nbsp;Preview Data Request</h3>
                            <p><?=$settings['hub_step4']?></p>
                            <?php include('sop_step_4.php');?>
                            <ul class="list-inline pull-right">
                                <li><button type="button" class="btn btn-default prev-step" id="previous_4">Previous</button></li>
                                <li><button type="submit" class="btn btn-success next-step saveAndContinue" id="save_continue_4" name="save_continue_4"><span id="save_continue_4_spinner"></span> Save and create PDF</button></li>
                            </ul>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                    <div class="modal fade" id="sop-change-concept-modal" tabindex="-1" role="dialog" aria-labelledby="Codes">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    <h4 class="modal-title">Change associated concept</h4>
                                </div>
                                <div class="modal-body">
                                    <span id="sop-change-concept-question"></span>
                                </div>

                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-default btn-success saveAndContinue" name='save_continue_0' id='save_continue_0' onclick="$('#sop-change-concept-modal').modal('hide');nextTab($('.wizard .nav-tabs li.active'));">Save</button>
                                    <button type="button" class="btn btn-default" id='btnChangeConceptClose' data-dismiss="modal">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>
<div class="modal fade" id="modal-save-and-stay" tabindex="-1" role="dialog" aria-labelledby="Codes">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Data Saved</h4>
            </div>
            <div class="modal-body">
                <span>Your information has been successfully saved.</span>
            </div>
        </div>
    </div>
</div>
<?php
}else{
    ?><div class="alert alert-warning fade in col-md-12"><em>Data Request #<?=$_REQUEST['record']?> is not available at this time.</em></div> <?php
 } ?>
