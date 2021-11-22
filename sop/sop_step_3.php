<?php
namespace Vanderbilt\HarmonistHubExternalModule;
?>
<script>
    $(document).ready(function () {
        $(".datepicker_aux").datepicker({
            showOn: "button",
            buttonImage: <?=json_encode(DATEICON)?>,
            buttonImageOnly: true,
            buttonText: "Select date",
            dateFormat: "yy-mm-dd",
            onSelect: function(dateText) {
                checkStep(3);
            }
        });

        $('#researchContact_name').change(function(){
            $.ajax({
                type: "POST",
                url: "sop/load_email_AJAX.php",
                data: "&id="+$(this).val(),
                error: function (xhr, status, error) {
                    alert(xhr.responseText);
                },
                success: function (result) {
                    jsonAjax = jQuery.parseJSON(result);
                    $('#researchContact_email').val(jsonAjax);
                }
            });
        });
        tinymce.init({selector:'#sop_inclusion',
            height: 200,
            menubar: false,
            branding: false,
            elementpath: false, // Hide this, since it oddly renders below the textarea.
            plugins: ['autolink lists link image charmap hr anchor pagebreak searchreplace code fullscreen insertdatetime media nonbreaking table directionality textcolor colorpicker imagetools'],
            toolbar1: 'undo redo | bold italic underline | alignleft aligncenter alignright alignjustify | link unlink | charmap',
            toolbar2: 'outdent indent | removeformat | subscript superscript | bullist numlist | forecolor backcolor | searchreplace code'});
        tinymce.init({selector:'#sop_exclusion',
            height: 200,
            menubar: false,
            branding: false,
            elementpath: false, // Hide this, since it oddly renders below the textarea.
            plugins: ['autolink lists link image charmap hr anchor pagebreak searchreplace code fullscreen insertdatetime media nonbreaking table directionality textcolor colorpicker imagetools'],
            toolbar1: 'undo redo | bold italic underline | alignleft aligncenter alignright alignjustify | link unlink | charmap',
            toolbar2: 'outdent indent | removeformat | subscript superscript | bullist numlist | forecolor backcolor | searchreplace code'});
        tinymce.init({selector:'#sop_notes',
            height: 200,
            menubar: false,
            branding: false,
            elementpath: false, // Hide this, since it oddly renders below the textarea.
            plugins: ['autolink lists link image charmap hr anchor pagebreak searchreplace code fullscreen insertdatetime media nonbreaking table directionality textcolor colorpicker imagetools'],
            toolbar1: 'undo redo | bold italic underline | alignleft aligncenter alignright alignjustify | link unlink | charmap',
            toolbar2: 'outdent indent | removeformat | subscript superscript | bullist numlist | forecolor backcolor | searchreplace code'});
        tinymce.init({selector:'#dataformat_notes',
            height: 200,
            menubar: false,
            branding: false,
            elementpath: false, // Hide this, since it oddly renders below the textarea.
            plugins: ['autolink lists link image charmap hr anchor pagebreak searchreplace code fullscreen insertdatetime media nonbreaking table directionality textcolor colorpicker imagetools'],
            toolbar1: 'undo redo | bold italic underline | alignleft aligncenter alignright alignjustify | link unlink | charmap',
            toolbar2: 'outdent indent | removeformat | subscript superscript | bullist numlist | forecolor backcolor | searchreplace code'});
    });
</script>
<div id="loader" style=""></div>
<script>
    $(document).ready(function () {
        $('#loader').hide();
    });
</script>
<?php
$record = $_REQUEST['record'];
$url = json_encode($module->getUrl('sop/sop_step_1_save_AJAX.php'));
if($record != ''){?>
    <script>
        $(document).ready(function () {
            var record = <?=json_encode($record)?>;
            loadAjax_steps('option=1&selectConcept=&save_option=' + record, <?=$url?>, 'loadFields', '0');
            $('#save_continue_1').prop('disabled',false);
            $('#save_continue_2').prop('disabled',false);
            $('#save_continue_3').prop('disabled',false);

            $('#sortable1 li').on('keydown', function(e){
                if(e.keyCode == 39){
                    $(this).appendTo('#sortable2');
                }
            });
            $('#sortable1 li').on('keydown', function(e){
                if(e.keyCode == 37){
                    $(this).appendTo('#sortable1');
                }
            });
        });
    </script>
<?php }
$RecordSetPeople = \REDCap::getData($pidsArray['PEOPLE'], 'array', null);
$people = ProjectData::getProjectInfoArray($RecordSetPeople);
ArrayFunctions::array_sort_by_column($people,'firstname');
if (!empty($people)) {
    $select_people = "<option value=''>Select Name</option>";
    foreach ($people as $person){
        $select_people .= "<option value='".$person['record_id']."'>".$person['firstname']." ".$person['lastname']." | ".$person['email']. "</option>";
    }
}
?>
<div class="container">
    <div class="col-12" style="padding: 30px 0px 20px;margin: 0 auto;width: 70%;">
        <div class="sop_builder_header">Text Descriptions</div>
        <div class="form-group">
            <label>Inclusion criteria <span style="font-weight:normal;font-style:italic">(list variable names if possible)</span></label>
            <textarea class="step-3-rich-text-editor" style="width:90%" name="sop_inclusion" id="sop_inclusion"></textarea>
            <input type="hidden" id="sop_inclusion_input">
        </div>
            <br>
        <div class="form-group">
            <label>Exclusion criteria <span style="font-weight:normal;font-style:italic">(list variable names if possible)</span></label>
            <textarea class="step-3-rich-text-editor" style="width:90%" name="sop_exclusion" id="sop_exclusion"></textarea>
            <input type="hidden" id="sop_exclusion_input">
        </div><br>
        <div class="form-group">
            <label>Notes <span style="font-weight:normal;font-style:italic">(describe specific DIS_ID and LAB_ID codes requested and other data preparation instructions)</span></label>
            <textarea class="step-3-rich-text-editor" style="width:90%" name="sop_notes" id="sop_notes"></textarea>
            <input type="hidden" id="sop_notes_input">
        </div>
            <br>
        <div class="form-group" id="sop_extrapdf_div" style="display:none">
            <label class="steps_label">Upload PDF</label>
            <input type="file" name="sop_extrapdf" value="">
            <span id='sop_extrapdf_name'></span>
        </div>
        <div class="sop_builder_header">Study Contacts</div>
        <div class="form-group">
            <label>Research Contact</label>
            <div class="steps_sub_div">
                <label class="steps_sub_label">Name / Email:</label>
                <select class="form-control data-form-control" name="sop_creator" id="sop_creator">
                    <?php echo $select_people; ?>
                </select>
            </div>
            <div class="steps_sub_div">
                <label class="steps_sub_label">Institution:</label>
                <input type="text" class="form-control data-form-control" id="sop_creator_org" name="sop_creator_org" value="">
            </div>
        </div>
        <div class="form-group">
            <label>Research Contact #2 <em>(optional)</em></label>
            <div class="steps_sub_div">
                <label class="steps_sub_label">Name / Email:</label>
                <select class="form-control data-form-control" name="sop_creator2" id="sop_creator2">
                    <?php echo $select_people; ?>
                </select>
            </div>
            <div class="steps_sub_div">
                <label class="steps_sub_label">Institution:</label>
                <input type="text" class="form-control data-form-control" id="sop_creator2_org" name="sop_creator2_org" value="">
            </div>
        </div>
        <div class="form-group">
            <label>Data Contact <span style="color:red !important;font-weight:bold;font-size:10px;font-style: italic;padding-left: 10px;">*required</span></label>
            <div class="steps_sub_div">
                <label class="steps_sub_label">Name / Email:</label>
                <select class="form-control data-form-control" name="sop_datacontact" id="sop_datacontact" onchange="checkStep(3);">
                    <?php echo $select_people; ?>
                </select>
            </div>
            <div class="steps_sub_div">
                <label class="steps_sub_label">Institution:</label>
                <input type="text" class="form-control data-form-control" id="sop_datacontact_org" name="sop_datacontact_org" value="">
            </div>
        </div>
        <div class="form-group">
            <label>Data Request Creator info</label>
            <div class="steps_sub_div">
                <label class="steps_sub_label">Name / Email:</label>
                <select class="form-control data-form-control" name="sop_hubuser" id="sop_hubuser" disabled>
                    <?php
                    $RecordSetPeople = \REDCap::getData($pidsArray['PEOPLE'], 'array', null);
                    $people = ProjectData::getProjectInfoArray($RecordSetPeople);
                    if (!empty($people)) {
                        foreach ($people as $person){
                            if($current_user['record_id'] == $person['record_id']){
                                echo "<option value='" . $person['record_id'] . "' selected>" . $person['firstname'] . " " . $person['lastname'] . " | " . $person['email'] . "</option>";
                            }else {
                                echo "<option value='" . $person['record_id'] . "'>" . $person['firstname'] . " " . $person['lastname'] . " | " . $person['email'] . "</option>";
                            }
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="steps_sub_div">
                <label class="steps_sub_label">Region</label>
                <select class="form-control data-form-control" name="sopCreator_region" id="sopCreator_region" onchange="checkStep(3);" disabled>
                    <?php
                    $RecordSetRegions = \REDCap::getData($pidsArray['REGIONS'], 'array', null);
                    $regions = ProjectData::getProjectInfoArray($RecordSetRegions);
                    if (!empty($regions)) {
                        foreach ($regions as $region){
                            if($current_user['person_region'] == $region['record_id']){
                                echo "<option value='".$region['record_id']."' selected>".$region['region_name'] . "</option>";
                            }else{
                                echo "<option value='".$region['record_id']."'>".$region['region_name']. "</option>";
                            }
                        }
                    }
                    ?>
                </select>
            </div>
        </div>
        <div class="sop_builder_header">Data Format and Access</div>
        <div class="form-group">
            <label class="steps_label">Due Date <span style="color:red !important;font-weight:bold;font-size:10px;font-style: italic;padding-left: 10px;">*required</span></label>
            <div><input type="text" class="datepicker_aux form-control data-form-control" style="width: 120px;height: 25px;text-align: center;" name="sop_due_d" id="sop_due_d" autocomplete="off" value="" onkeyup="checkStep(3);"/></div>
        </div>
        <div class="form-group">
            <label>Preferred File Format</label>
            <div class="steps_sub_div">
                <label class="steps_sub_label"></label>
                <div style="display: inline-block">
            <?php
            $dataformat_prefer = $module->getChoiceLabels('dataformat_prefer', $pidsArray['SOP']);
            foreach($dataformat_prefer as $dataid => $dataformat){
                echo '<div><input type="checkbox" class="" id="dataformat_prefer_'.$dataid.'" name="dataformat_prefer[]" value="'.$dataid.'" onkeyup="checkStep(3);"><span style="padding-left: 10px">'.$dataformat.'</span></div>';
            }
            ?>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label>File Format Details</label>
            <textarea class="step-3-rich-text-editor" style="width:90%" name="dataformat_notes" id="dataformat_notes"></textarea>
            <input type="hidden" id="fileformat_details_input">
        </div>
        <br/>
        <div class="form-group">
            <label style="padding-right:20px">Choose your Data Downloaders <span style="color:red !important;font-weight:bold;font-size:10px;font-style: italic;padding-left: 10px;">*required</span></label>
            <select class="form-control data-form-control" style="width:150px" name="dropDown_region" id="dropDown_region" onchange="check_people_region_dragAndDrop();checkStep(3);">
                <option value="" region="all" selected>All Regions</option>
                <?php
                $RecordSetRegions = \REDCap::getData($pidsArray['REGIONS'], 'array', null);
                $regions = ProjectData::getProjectInfoArray($RecordSetRegions);
                if (!empty($regions)) {
                    foreach ($regions as $region){
                        echo "<option value='".$region['record_id']."'>".$region['region_name']. "</option>";
                    }
                }
                ?>
            </select>
        </div>
        <div class="col-md-12">
            <ul id="sortable1" class="connectedSortable" style="width: 35%;" role="list">
            <?php
            $RecordSetPeople = \REDCap::getData($pidsArray['PEOPLE'], 'array', null,null,null,null,false,false,false,"[active_y] = '1' AND [redcap_name] <> '' AND [allowgetdata_y(1)] = 1");
            $people_sop = ProjectData::getProjectInfoArray($RecordSetPeople);
            ArrayFunctions::array_sort_by_column($people_sop,'firstname');
            foreach ($people_sop as $person){
                if($current_user['person_region'] == $person['person_region']){
                    echo ' <li tabindex="0" class="ui-state-default" id="'.$person['record_id'].'" region="'.$person['person_region'].'">'.$person['firstname'].' '.$person['lastname'].'</li>';
                }else{
                    echo ' <li tabindex="0" class="ui-state-default" id="'.$person['record_id'].'" region="'.$person['person_region'].'">'.$person['firstname'].' '.$person['lastname'].'</li>';
                }
            }
            ?>
            </ul>
            <span class="sortable_doubleArrow" style="width: 20%;">Drag and Drop <span class="fa fa-arrows-h sortable_doubleArrow_icon"></span></span>
            <ul id="sortable2" class="connectedSortable" style="width: 35%;">
            </ul>
        </div>
        <div class="clearfix"></div>
        <div class="form-group">
            <span style="padding-right: 10px;font-style: italic;">Not sure about Data Downloaders yet.</span><input type="checkbox" onclick="checkStep(3)"; name="sop_downloaders_dummy" id="sop_downloaders_dummy" style="width: 20px;height: 20px;vertical-align: -2px;">
        </div>

        <div class="form-group">
            <i>Only Hub users with linked REDCap accounts can download data. To keep your data secure, we use REDCap's two-factor authentication to make sure only authorized people can access the data. If you want to add new Data Downloaders, contact <a href="mailto:stephany.duda@vanderbilt.edu">stephany.duda@vanderbilt.edu</a>. The Harmonist team can revise the list of Data Downloaders with you during the Data Request review process.</i>
        </div>
    </div>
</div>