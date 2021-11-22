<?php
namespace Vanderbilt\HarmonistHubExternalModule;
$harmonist_perm = \Vanderbilt\HarmonistHubExternalModule\hasUserPermissions($current_user['harmonist_perms'], 1);
?>
<div class="container">
    <div class="col-md-12 col lg-12" style="padding: 30px 0px 20px">
        <div style="width:180px;float:left;padding-left:30px;margin-top: 8px;font-weight: bold">
            Select Your Concept:
        </div>
        <div style="float:left;padding-left:10px;width:50%">
            <select class="form-control" name="selectConcept" id="selectConcept" onchange="checkStep(1)">
                <option value="">Select option</option>
                <?php
                $RecordSetConceptsActive = \REDCap::getData($pidsArray['HARMONIST'], 'array', null,null,null,null,false,false,false,"[active_y] = 'Y'");
                $concepts = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConceptsActive);
                ArrayFunctions::array_sort_by_column($concepts, 'concept_id');
                if (!empty($concepts)) {
                    foreach ($concepts as $concept){
                        $concept_short_title= strlen($concept['concept_title']) > 120 ? substr($concept['concept_title'],0,120)."..." : $concept['concept_title'];
                        echo "<option value='".$concept['record_id']."' concept='".$concept['concept_id']."'>".$concept['concept_id']." - ".$concept_short_title . "</option>";
                    }
                }
                ?>
            </select>
            <span style="color:gray;font-style:italic">For test requests, select MR000.</span>
        </div>
    </div>
    <div class="col-md-12 col lg-12" style="padding: 30px 0px 30px;font-weight: bold">
        <div style="width:180px;float:left;padding-left:30px;display: block;">
            Setup Type:
        </div>
        <div style="float:left;padding-left:10px;">
            <label class="radio-inline" style="padding-right: 20px;"><input type="radio" name="optradio" id="optradio_1" onclick="checkStep(1)" value="1">Create new data request</label>
            <label class="radio-inline"><input type="radio" name="optradio" id="optradio_3" onclick="checkStep(1)" value="3">Load draft</label>
        </div>
        <input type="hidden" value="" id="save_option" name="save_option">
    </div>

    <div class="col-md-12 col lg-12" style="padding: 30px 0px 20px;display: none" id="setup_show_all_option">
        <div style="width:180px;float:left;padding-left:30px;margin-top: 8px;;font-weight: bold">
            Select Draft:
        </div>
        <div style="float:left;padding-left:10px;width:50%;display:none" id="setup_show_option_2">
                <?php
                $RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', null,null,null,null,false,false,false,"[sop_active] = 1 && [sop_status] = 2");
                $sop_templates = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP);
                if (!empty($sop_templates)) {?>
                    <select class="form-control" name="selectSOP_2" id="selectSOP_2" onchange="checkStep(1);checkConcept();">
                        <option value="">Select template</option>
                        <?php
                        foreach ($sop_templates as $template){
                            $RecordSetConcepts = \REDCap::getData($pidsArray['HARMONIST'], 'array', array('record_id' => $template['sop_concept_id']));
                            $concept_id = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConcepts)[0]['concept_id'];
                            echo "<option value='".$template['record_id']."' concept='".$template['sop_concept_id']."' concept_id='".$concept_id."'>".$template['sop_name']."</option>";
                        }?>
                    </select>
                    <?php
                }else{
                    echo "<div style='float:left;padding-left:30px;margin-top: 8px;'><em>No drafts available</em></div>";
                }
                ?>
        </div>

        <div style="float:left;padding-left:10px;width:50%;display:none" id="setup_show_option_3">
                <?php
                $RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', null,null,null,null,false,false,false,"[sop_active] = 1 && [sop_status] = 0");
                $sop_drafts = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP);
                if (!empty($sop_drafts)) {?>
                    <select class="form-control" name="selectSOP_3" id="selectSOP_3" onchange="checkStep(1);checkConcept();">
                        <option value="">Select draft</option>
                    <?php
                    foreach ($sop_drafts as $draft){
                        if($isAdmin || $harmonist_perm || $draft['sop_hubuser'] == $current_user['record_id'] || $draft['sop_creator'] == $current_user['record_id'] || $draft['sop_creator2'] == $current_user['record_id'] || $draft['sop_datacontact'] == $current_user['record_id'] ){
                            $RecordSetConcepts = \REDCap::getData($pidsArray['HARMONIST'], 'array', array('record_id' => $draft['sop_concept_id']));
                            $concept_id = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConcepts)[0]['concept_id'];
                            echo "<option value='" . $draft['record_id'] . "' concept='" . $draft['sop_concept_id'] . "' concept_id='" . $concept_id . "'>" . $draft['sop_name'] . "</option>";
                        }
                    }?>
                    </select>
                <?php
                }else{
                    echo "<div style='float:left;padding-left:30px;margin-top: 8px;'><em>No drafts available</em></div>";
                }
                ?>

        </div>
    </div>
</div>