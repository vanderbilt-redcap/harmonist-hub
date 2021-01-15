<?PHP
namespace Vanderbilt\HarmonistHubExternalModule;
#We get the Tables and Variables information
$dataTable = \Functions\getTablesInfo($module);
$first_table = 0;

$type_status = $module->getChoiceLabels('available_status', IEDEA_DATAAVAILABILITY);
$type_label = array(0=>'label-available-few', 1=>'label-available-some', 2=>'label-available-most',3=>'label-available-all', 99=>'label-unknown');
$type_icon = array(0=>'fa-circle-o', 1=>'fa-adjust', 2=>'fa-circle', 99=>'fa-question');

$tr_class = 'in';
if($indexSubSet>0) {
    //collapse columns as there is some existing info
    $tr_class = '';
}

?>
<script>
    $(document).ready(function () {
       $('.table_requests').removeClass('rowSelected');
    });
</script>
<div class="container col-md-12 col lg-12" style="padding: 30px 0px 20px">
    <div class="panel panel-default">
        <?PHP foreach( $dataTable as $data ) {
            if (!empty($data['record_id'])) {

                $record_id_array .= $data['record_id'].',';

                $table_draft= "";
                $table_draft_text= "";
                if (array_key_exists('table_status', $data) ) {
                    $table_draft = ($data['table_status'] == 0) ? "des_draft_header" : "";
                    $table_draft_text = ($data['table_status'] == 0) ?'<span style="color: red;font-style: italic"> (DRAFT)</span>':"";
                }

                $header_style = "";
                if($first_table > 0){
                    $header_style = "border-top: 1px solid #ddd !important;";
                }
                $first_table++;

                ?>
                <div class="panel-heading <?=$table_draft?>" style="<?=$header_style?>">
                    <h3 class="panel-title">
                        <a data-toggle="collapse" href="#collapse<?=$data['record_id']?>" id="<?='table_'.$data['record_id']?>" class="label label-as-badge-square <?='des-'.$data['table_category']?>"><strong><?PHP echo $data['table_name'].$table_draft_text; ?></strong></a>
                        <span class="badge dataRequests" id="counter_<?= $data['record_id']; ?>"></span>
                        <span style="padding-left:10px">
                            <input type="checkbox" id="ckb_<?= $data['table_name']; ?>" name="<?= "chkAll_" . $data['record_id'] ?>" onclick="checkAll('<?= $data['record_id'] ?>');checkStep(2)" style="cursor: pointer;">
                            <span style="cursor: pointer;font-size: 14px;font-weight: normal;color: black;" onclick="checkAllText('<?= $data['record_id'] ?>');checkStep(2)">Select All</span>
                         </span>
                    </h3>
                </div>

                <div id="collapse<?=$data['record_id']?>" class="table-responsive panel-collapse collapse in step2_collapse" aria-expanded="true">
                    <table class="table sortable-theme-bootstrap desTable sopTable" data-sortable id="desTable">
                        <colgroup>
                            <col>
                            <col>
                            <col>
                            <col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="sorting_disabled" data-sortable="false" style="width: 6%;">Select</th>
                                <th class="sorting_disabled" data-sortable="false" style="width: 14%;">Field</th>
                                <th class="sorting_disabled" data-sortable="false" style="width: 10%;">Availability</th>
                                <th class="sorting_disabled" data-sortable="false" style="width: 20%;">Format</th>
                                <th class="sorting_disabled" data-sortable="false" style="width: 50%;">Description</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?PHP
                        foreach ($data['variable_order'] as $id=>$value) {
                            $variable_status = "";
                            $variable_display = "";
                            $variable_text = "";
                            if (array_key_exists('variable_status', $data) && array_key_exists($id, $data['variable_status'])) {
                                if($data['variable_status'][$id] == "0"){//DRAFT
                                    $variable_status = "class='des_draft'";
                                    $variable_text = "<span style='color:red;font-weight:bold'>DRAFT</span><br/>";
                                }else if($data['variable_status'][$id] == "2"){//DEPRECATED
                                    $variable_status = "class='deprecated'";
                                    $variable_display = "display:none";
                                    $variable_text = "<span style='color:red;font-weight:bold'>DEPRECATED</span><br/>";
                                }
                            }


                            $record_varname = empty($id) ? $data['record_id'] . '_1' : $data['record_id'] . '_' . $id;
                            $name = $data['variable_name'][$id];

                            #If the variable is required or not
                            $variable_required = "N";
                            if(array_key_exists('variable_required',$data) && array_key_exists($id, $data['variable_required']) && $data['variable_required'][$id][0] == 1){
                                $variable_required = "Y";
                            }

                            $RecordSetDA = \REDCap::getData(IEDEA_DATAAVAILABILITY, 'array', null,null,null,null,false,false,false,"[available_table] = '".$data['record_id']."'");
                            $data_availability = ProjectData::getProjectInfoArray($RecordSetDA);
                            $type_text = $type_status[99];
                            $type_color = $type_label[99];
                            if($data_availability != ""){
                                foreach ($data_availability as $available){
                                    $instance_table_var = explode("|",$available['available_variable'])[1];

                                    if($instance_table_var == "1"){
                                        $instance_table_var = "";
                                    }
                                    if($data['variable_name'][$id] == $data['variable_name'][$instance_table_var]){
                                        $type_text = $type_status[$available['available_status']];
                                        $type_color = $type_label[$available['available_status']];
                                    }
                                }
                            }
                        //Do not include DEPRECATED variables
                        if($data['variable_status'][$id] != "2"){
                        ?>
                        <tr record_id="<?= $record_varname; ?>" <?= $variable_status ?> parent_table='<?= $data['record_id']; ?>' style="<?= $variable_display ?>" onclick="checkselectDoubles('<?= $record_varname; ?>');checkStep(2)">
                            <td>
                                <input value="<?= $record_varname; ?>" id="<?= 'record_id_' . $record_varname; ?>" onclick="checkselectDoubles('<?= $record_varname; ?>');checkStep(2)" chk_name='chk_table_<?= $data['record_id']; ?>' class='auto-submit' type="checkbox" name='tablefields[]' variable_required="<?=$variable_required?>">
                            </td>
                            <td id="name_<?= $record_varname; ?>" couple_row="<?= $couple_row; ?>"><?=trim($name); ?></td>
                            <td><span class="label <?=$type_color?>" style="font-size: 12px;padding: .3em .6em .3em;"><?=$type_text;?></span></td>
                            <td>
                                <?PHP
                                $dataFormat = $dataTable['data_format_label'][$data['data_format'][$id]];

                                if ($data['has_codes'][$id] == '0') {
                                    echo $dataFormat;
                                    if (!empty($data['code_text'][$id])) {
                                        echo "<br/>".$data['code_text'][$id];
                                    }
                                } else if ($data['has_codes'][$id] == '1') {
                                    if(!empty($data['code_list_ref'][$id])){
                                        $RecordSetCode = \REDCap::getData(IEDEA_CODELIST, 'array', array('record_id' => $data['code_list_ref'][$id]));
                                        $codeformat = ProjectData::getProjectInfoArray($RecordSetCode);
                                        if ($codeformat['code_format'] == '1') {
                                            $codeOptions = empty($codeformat['code_list']) ? $data['code_text'][$id] : explode(" | ", $codeformat['code_list']);
                                            if (!empty($codeOptions[0])) {
                                                $dataFormat .= "<div style='padding-left:15px'>";
                                            }
                                            foreach ($codeOptions as $option) {
                                                $dataFormat .= $option . "<br/>";
                                            }
                                            if (!empty($codeOptions[0])) {
                                                $dataFormat .= "</div>";
                                            }
                                            echo $dataFormat;

                                        } else if ($codeformat['code_format'] == '3') { ?>
                                            Numeric<br/>
                                            <?PHP
                                            if (array_key_exists('code_file', $codeformat)) {
                                                ?>
                                                <a href="#codesModal<?= $codeformat['code_file']; ?>"
                                                   id='btnViewCodes'
                                                   type="button" class="btn_code_modal open-codesModal"
                                                   data-toggle="modal"
                                                   data-target="#codesModal<?= $codeformat['code_file']; ?>">See
                                                    Code List</a>

                                                <?PHP require('codes_modal.php');
                                            }
                                        } else {
                                            echo $dataFormat;
                                        }
                                    }
                                }
                                ?>
                            </td>
                            <td>
                                <?= $variable_text.$data['description'][$id]; ?>
                                <?PHP
                                if (!empty($data['description_extra'][$id])) {
                                    echo "<br/><em>" . $data['description_extra'][$id] . "</em>";
                                }
                                ?>
                            </td>
                        </tr>
                            <?php } ?>
                        </tbody>
                        <?php } ?>
                    </table>
                </div>
            <?php }
        }?>
    </div>
</div>
<input type="hidden" value="<?=$record_id_array?>" name="parent_table_record_id_array" id="parent_table_record_id_array">