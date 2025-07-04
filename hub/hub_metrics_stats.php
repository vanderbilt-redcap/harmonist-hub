<?php
namespace Vanderbilt\HarmonistHubExternalModule;
/**
#Consortium Productivity
 **/

$wg_link = $module->getChoiceLabels('wg_link', $pidsArray['HARMONIST']);
$wg_array = \REDCap::getData($pidsArray['GROUP'], 'json-array', null,array('record_id','group_name','group_abbr'));

$wg_link = array();
foreach ($wg_array as $wg){
    $wg_link[$wg['record_id']] = $wg['group_name'].' ('.$wg['group_abbr'].')';
}

$RecordSetConceptsALL = \REDCap::getData($pidsArray['HARMONIST'], 'array', null);
$concepts = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConceptsALL,$pidsArray['HARMONIST'],'');
$RecordSetConcepts = \REDCap::getData($pidsArray['HARMONIST'], 'array', null,null,null,null,false,false,false,"[active_y] = 'Y'");
$active_concepts = count(ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConcepts,$pidsArray['HARMONIST']));
$RecordSetConceptsIC = \REDCap::getData($pidsArray['HARMONIST'], 'array', null,null,null,null,false,false,false,"[active_y] = 'N' AND [concept_outcome] = '1'");
$inactive_complete_concepts = count(ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConceptsIC,$pidsArray['HARMONIST']));
$RecordSetConceptsID = \REDCap::getData($pidsArray['HARMONIST'], 'array', null,null,null,null,false,false,false,"[active_y] = 'N' AND [concept_outcome] = '2'");
$inactive_discontinued_concepts = count(ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConceptsID,$pidsArray['HARMONIST']));

$array_wg = array();
foreach ($wg_link as $wg){
    if(!key_exists($wg,$array_wg)){
        $array_wg[$wg] =  0;
    }
}

$other = 0;
foreach ($concepts as $concept){
    if($concept['wg_link'] == ""){
        $other += 1;
    }else if(!empty($wg_link[$concept['wg_link']])){
        $array_wg[$wg_link[$concept['wg_link']]] +=  1;
    }
}
$array_wg['No WG'] = $other;

$conceptswg_short_label = array();
$conceptswg_short_label_index = array();
foreach ($wg_link as $code => $text){
    preg_match('#\((.*?)\)#', $text, $match);
    array_push($conceptswg_short_label,$match[1]);
    $conceptswg_short_label_index[$code] = $match[1];
}
$conceptswg_short_label_index[''] = 'No WG';
array_push($conceptswg_short_label,'No WG');

$regions = \REDCap::getData($pidsArray['REGIONS'], 'json-array', null);
$conceptsleadregion_values = array();
$conceptsleadregion_labels = array();
$requests_array_region = array();
foreach ($regions as $region){
    $RecordSetConceptsLead = \REDCap::getData($pidsArray['HARMONIST'], 'array', null,null,null,null,false,false,false,"[lead_region] = '".$region['record_id']."'");
    $lead_region = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConceptsLead,$pidsArray['HARMONIST']);
    array_push($conceptsleadregion_values,count($lead_region));
    array_push($conceptsleadregion_labels,$region['region_code']);
    $requests_array_region[$region['record_id']] = 0;
}

$concepts_values = array(0 => $active_concepts,1 => $inactive_complete_concepts,2 => $inactive_discontinued_concepts);
$concepts_labels = array(0 => "Active",1 => "Inactive\nComplete",2 => "Inactive\nDiscontinued");
$concepts_colors = array(0 => "#1ad1ff",1 => "#5cb85c",2 => "#f0ad4e");

$conceptswg_values = array();
$conceptswg_colors = array();
$count_wg = 0;
$wg_percent = 75;
$max_wg = count($array_wg);
foreach ($array_wg as $name=>$wg){
    $count_wg++;
    array_push($conceptswg_values,$wg);
    if($count_wg < $max_wg){
        $color = "hsl(210,50%,".$wg_percent."%)";
        array_push($conceptswg_colors,$color);
        $wg_percent -= 5;
    }else{
        array_push($conceptswg_colors,'#8c8c8c');
    }
}
$conceptswg_labels = $wg_link;
$conceptswg_labels[''] = 'No WG';

/**
#Regional and MR (publications & abstracts)
**/

$concept_type = array(1=>'manuscripts',2=>'abstracts');
$RecordSetConcepts = \REDCap::getData($pidsArray['HARMONIST'], 'array', null);
$conceptsData = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConcepts,$pidsArray['HARMONIST'],'');
$regionalmrdata = array();
foreach ($concept_type as $output_type=>$type){
    ${"regionalmrdata_".$type} = getRegionalAndMR($pidsArray['EXTRAOUTPUTS'], $conceptsData,$type, $regionalmrdata,$settings['oldestyear_rmr_'.$type],$output_type);
    ${"data_".$type} = getDataRMRTable(${"regionalmrdata_".$type}['outputs'],$type);
}

$regionalmrpubs_color_manuscripts = ['#f5a549','#d1691f'];
$regionalmrpubs_color_abstracts = ['#6ddc9c','#3c9d68'];

/**
#Multi-regional Activity by Year
 **/

$years_label_concepts = array();
$concept_years = array();
$concept_years_output = array();
$currentYear = date("Y");
for($year = $settings['oldestyear_concepts']; $year <= $currentYear; $year++){
    $concept_years_output[$year] = array();
    $concept_years[$year] = array();
    array_push($years_label_concepts, $year);
}
krsort($concept_years_output);
foreach ($concept_years as $year => $concept){
    $concept_years[$year]['concepts'] = 0;
    $concept_years[$year]['abstracts'] = 0;
    $concept_years[$year]['manuscripts'] = 0;
    $concept_years[$year]['mrdatarequests'] = 0;
}

foreach ($conceptsData as $concepts){
    foreach ($concept_years as $year=>$c_year){
        if($concepts['start_year'] == $year){
            $concept_years[$year]['concepts'] += 1;
        }

        if(is_array($concepts['output_year'])){
            foreach ($concepts['output_year'] as $index => $output){
                if($output == $year){
                    if($concepts['output_type'][$index] == '' || $concepts['output_type'][$index] == '1'){
                        $concept_years[$year]['manuscripts'] += 1;
                    }else if($concepts['output_type'][$index] == '2'){
                        $concept_years[$year]['abstracts'] += 1;
                    }
                }
            }
        }
    }
}
$RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', null,null,null,null,false,false,false,"[sop_final_d] <> ''");
$sopData = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP,$pidsArray['SOP']);
foreach ($sopData as $sop){
    $sop_year = date("Y",strtotime($sop['sop_final_d']));
    foreach ($concept_years as $year=>$c_year){
        if($sop_year == $year){
            $concept_years[$year]['mrdatarequests'] += 1;
        }
    }
}


$iedea_concepts = array();
$iedea_manuscripts = array();
$iedea_abstracts = array();
$iedea_mrdatarequests = array();
foreach ($concept_years as $year => $concept){
    array_push($iedea_concepts, $concept['concepts']);
    array_push($iedea_manuscripts, $concept['manuscripts']);
    array_push($iedea_abstracts, $concept['abstracts']);
    array_push($iedea_mrdatarequests, $concept['mrdatarequests']);
}
/**
#Requests
 **/
$RecordSetRM = \REDCap::getData($pidsArray['RMANAGER'], 'array', null,null,null,null,false,false,false,"[approval_y] = '1'");
$request = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRM,$pidsArray['RMANAGER']);
ArrayFunctions::array_sort_by_column($request, 'due_d');

$instance = $current_user['person_region'];

foreach ($request as $req){
    $requests_array_region[$req['contact_region']] += 1;
}

$requestsreq_values = array();
$requestsreq_labels = array();
$requestsreq_colors = array();
$count_wg = 0;
$wg_percent = 75;
$max_wg = count($array_wg);
foreach ($regions as $region){
    array_push($requestsreq_labels,$region['region_code']);
    foreach ($requests_array_region as $record => $reqreg){
        if($region['record_id'] == $record){
            $count_wg++;
            array_push($requestsreq_values,$reqreg);
            if($count_wg < $max_wg){
                $color = "hsl(120,39%,".$wg_percent."%)";
                array_push($requestsreq_colors,$color);
                $wg_percent -= 5;
            }else{
                array_push($requestsreq_colors,'#8c8c8c');
            }
        }

    }
}

$requests_labels = $module->getChoiceLabels('request_type', $pidsArray['RMANAGER']);
$requests_values = array_count_values(array_column($request, 'request_type'));

//Add possible missing keys
foreach ($requests_labels as $keyLabel => $requestsLabel){
    if(!array_key_exists($keyLabel, $requests_values)){
        $requests_values[$keyLabel] = 0;
    }
}

//remove hidden options
$hidden_choices = $default_values->getHideChoice($pidsArray['RMANAGER'])[$pidsArray['RMANAGER']]['request_type'];
foreach ($hidden_choices as $value){
    if(array_key_exists($value,$requests_labels) ){
        unset($requests_labels[$value]);
    }
    if(array_key_exists($value,$requests_values) ){
        unset($requests_values[$value]);
    }
}

ksort($requests_values);
$requests_values = array_values($requests_values);
$requests_colors = array(0 => "#337ab7",1 => "#00b386",2 => "#f0ad4e",3 => "#ff9966",4 => "#5bc0de",5 => "#777");
ksort($requests_labels);
$requests_labels = array_values($requests_labels);

$array_sections = array(0=>'concepts',1=>'conceptswg',2=>'requests');
$array_sections_title = array(0=>'concepts by status', 1=>'concepts by wg',2=>'Hub Review Requests');
$array_sections_all = array(0=>'concepts',1=>'conceptswg',2=>'requests');
$array_sections_title_all = array(0=>'concepts by status', 1=>'concepts by Working Group',2=>'Hub Review Requests');

#Escape All Data
$requests_values = $module->escape($requests_values);
$requests_labels = array_values($module->escape($requests_labels));
$requests_colors = $module->escape($requests_colors);
$array_sections = $module->escape($array_sections);
$array_sections_title = $module->escape($array_sections_title);
$array_sections_all = $module->escape($array_sections_all);
$array_sections_title_all = $module->escape($array_sections_title_all);
$concepts_values = $module->escape($concepts_values);
$concepts_labels = $module->escape($concepts_labels);
$concepts_colors = $module->escape($concepts_colors);
$conceptswg_values = $module->escape($conceptswg_values);
$conceptswg_labels = $module->escape($conceptswg_labels);
$conceptswg_colors = $module->escape($conceptswg_colors);
$conceptswg_short_label_index = $module->escape($conceptswg_short_label_index);
$conceptswg_short_label = $module->escape($conceptswg_short_label);
$conceptsleadregion_values = $module->escape($conceptsleadregion_values);
$conceptsleadregion_labels = $module->escape($conceptsleadregion_labels);
$regionalmrdata_manuscripts = $module->escape($regionalmrdata_manuscripts);
$regionalmrpubs_color_abstracts = $module->escape($regionalmrpubs_color_abstracts);
$years_label_concepts = $module->escape($years_label_concepts);
$iedea_concepts = $module->escape($iedea_concepts);
$iedea_manuscripts = $module->escape($iedea_manuscripts);
$iedea_abstracts = $module->escape($iedea_abstracts);
$iedea_mrdatarequests = $module->escape($iedea_mrdatarequests);
$requestsreq_values = $module->escape($requestsreq_values);
$requestsreq_labels = $module->escape($requestsreq_labels);
$requestsreq_colors = $module->escape($requestsreq_colors);
$concept_type = $module->escape($concept_type);

$show_donuts_single = ProjectData::getCheckboxValuesAsArray($module, $pidsArray['SETTINGS'], 'hub_stats_consortium_select', $settings, "chart");
$pub_data = ProjectData::getCheckboxValuesAsArray($module, $pidsArray['SETTINGS'], 'pub_data', $settings);
$abs_data = ProjectData::getCheckboxValuesAsArray($module, $pidsArray['SETTINGS'], 'abs_data', $settings);
$activity_data = ProjectData::getCheckboxValuesAsArray($module, $pidsArray['SETTINGS'], 'activity_data', $settings);
?>
<script>
    $(document).ready(function() {
        var showChar = 200;
        var ellipsestext = "...";
        var moretext = "more";
        var lesstext = "less";
        $('.more').each(function() {
            var content = $(this).html();

            if(content.length > showChar) {

                var snippetContent = content.substr(0, showChar);
                var allContent = content.substr(showChar, content.length - showChar);

                var html = snippetContent + '<span class="moreellipses">' + ellipsestext+ '&nbsp;</span><span class="morecontent"><span>' + allContent + '</span>&nbsp;&nbsp;<a href="" class="morelink">' + moretext + '</a></span>';

                $(this).html(html);
            }

        });

        $(".morelink").click(function(){
            if($(this).hasClass("less")) {
                $(this).removeClass("less");
                $(this).html(moretext);
            } else {
                $(this).addClass("less");
                $(this).html(lesstext);
            }
            $(this).parent().prev().toggle();
            $(this).prev().toggle();
            return false;
        });
    });
    function createBase64Chart(chart,url){
        // var url = donuts_chart.toBase64Image();
        var url_base64 = document.getElementById(chart).toDataURL('image/png');
        var url_base64 = save64Img(chart.toBase64Image());
    }
    $(function () {
        var show_donuts = <?=json_encode($settings['hub_stats_section1_y'])?>;
        var show_donuts_single = <?=json_encode($show_donuts_single)?>;
        var show_publications = <?=json_encode($settings['hub_stats_section2_y'])?>;
        var show_manuscripts_single = <?=json_encode($pub_data)?>;
        var show_manuscripts_single_label1 = <?=json_encode(($settings['pub_data_label1']=="")?$default_values_settings['pub_data_label1']:$settings['pub_data_label1'])?>;
        var show_manuscripts_single_label2 = <?=json_encode(($settings['pub_data_label2']=="")?$default_values_settings['pub_data_label2']:$settings['pub_data_label2'])?>;
        var show_abstracts = <?=json_encode($settings['hub_stats_section3_y'])?>;
        var show_abstracts_single = <?=json_encode($abs_data)?>;
        var show_abstracts_single_label1 = <?=json_encode(($settings['abs_data_label']=="")?$default_values_settings['abs_data_label']:$settings['abs_data_label'])?>;
        var show_abstracts_single_label2 = <?=json_encode(($settings['abs_data_label2']=="")?$default_values_settings['abs_data_label2']:$settings['abs_data_label2'])?>;
        var show_activity = <?=json_encode($settings['hub_stats_section4_y'])?>;
        var show_activity_single = <?=json_encode($activity_data)?>;

        var url = <?=json_encode($module->getUrl('index.php').'&NOAUTH&option=cpt')?>;

        var array_sections = <?=json_encode($array_sections)?>;
        var array_sections_title = <?=json_encode($array_sections_title)?>;
        var array_sections_all = <?=json_encode($array_sections_all)?>;
        var array_sections_title_all = <?=json_encode($array_sections_title_all)?>;

        //Consortium Productivity
        var conceptsleadregion_values = <?=json_encode($conceptsleadregion_values)?>;
        var conceptsleadregion_labels = <?=json_encode($conceptsleadregion_labels)?>;
        var conceptsleadregion_colors = <?=json_encode($conceptswg_colors)?>;

        //Multiregional and Regional Publications
        var concept_type = <?=json_encode($concept_type)?>;
        var regionalmrpubs_mrw_manuscripts = <?=json_encode($regionalmrdata_manuscripts['mrw'])?>;
        var regionalmrpubs_r_manuscripts = <?=json_encode($regionalmrdata_manuscripts['r'])?>;
        var regionalmrpubs_color_manuscripts = <?=json_encode(array_values($regionalmrpubs_color_manuscripts))?>;

        //Multiregional and Regional Abstracts
        var regionalmrpubs_mrw_abstracts = <?=json_encode($regionalmrdata_abstracts['mrw'])?>;
        var regionalmrpubs_r_abstracts = <?=json_encode($regionalmrdata_abstracts['r'])?>;
        var regionalmrpubs_color_abstracts = <?=json_encode(array_values($regionalmrpubs_color_abstracts))?>;

        //Multi-regional Activity by Year
        const multiregionalActivityByYear = {
            concepts:<?=json_encode($iedea_concepts)?>,
            manuscripts:<?=json_encode($iedea_manuscripts)?>,
            abstracts:<?=json_encode($iedea_abstracts)?>,
            mrdatarequests:<?=json_encode($iedea_mrdatarequests)?>
        }
        var years_label_concepts = <?=json_encode($years_label_concepts)?>;

        //Requests
        var requestsreq_values = <?=json_encode($requestsreq_values)?>;
        var requestsreq_labels = <?=json_encode($requestsreq_labels)?>;
        var requestsreq_colors = <?=json_encode($requestsreq_colors)?>;

        const sectionsAll = {
            concepts:{
                values: <?=json_encode($concepts_values)?>,
                labels: <?=json_encode($concepts_labels)?>,
                colors: <?=json_encode($concepts_colors)?>
            },
            conceptswg:{
                values: <?=json_encode($conceptswg_values)?>,
                labels: <?=json_encode($conceptswg_labels)?>,
                colors: <?=json_encode($conceptswg_colors)?>,
                short_label: <?=json_encode($conceptswg_short_label)?>,
                short_label_index: <?=json_encode($conceptswg_short_label_index)?>
            },
            requests:{
                values: <?=json_encode($requests_values)?>,
                labels: <?=json_encode($requests_labels)?>,
                colors: <?=json_encode($requests_colors)?>
            },
            manuscripts:{
                years_label: <?=json_encode($regionalmrdata_abstracts['years'])?>,
                single: <?=json_encode($pub_data)?>,
                label1: <?=json_encode(($settings['pub_data_label1']=="")?$default_values_settings['pub_data_label1']:$settings['pub_data_label1'])?>,
                label2: <?=json_encode(($settings['pub_data_label2']=="")?$default_values_settings['pub_data_label2']:$settings['pub_data_label2'])?>,
                regionalmrpubs_mr: <?=json_encode(array_values($regionalmrdata_manuscripts['mr']))?>,
                regionalmrpubs_outputs: <?=json_encode($regionalmrdata_manuscripts['outputsAll'])?>,
                regionalmrpubs_color: <?=json_encode(array_values($regionalmrpubs_color_manuscripts))?>
            },
            abstracts:{
                years_label: <?=json_encode($regionalmrdata_manuscripts['years'])?>,
                single: <?=json_encode($abs_data)?>,
                label1: <?=json_encode(($settings['abs_data_label']=="")?$default_values_settings['abs_data_label']:$settings['abs_data_label'])?>,
                label2: <?=json_encode(($settings['abs_data_label2']=="")?$default_values_settings['abs_data_label2']:$settings['abs_data_label2'])?>,
                regionalmrpubs_mr: <?=json_encode(array_values($regionalmrdata_abstracts['mr']))?>,
                regionalmrpubs_outputs: <?=json_encode($regionalmrdata_abstracts['outputsAll'])?>,
                regionalmrpubs_color: <?=json_encode(array_values($regionalmrpubs_color_abstracts))?>
            }
        }
        //DONUTS
        if(show_donuts == "1") {
            Object.keys(array_sections_all).forEach(function (section) {
                if(show_donuts_single[(parseInt(section))] == '1') {
                    var ctx = $("#" + array_sections_all[section] + "Chart");
                    if (array_sections_all[section] == 'conceptswg') {
                        var customTooltips = function (tooltip) {
                            // Tooltip Element
                            var tooltipEl = document.getElementById(array_sections_all[section] + "tooltip");
                            $('#conceptswgtooltip').show();

                            if (!tooltipEl) {
                                tooltipEl = document.createElement('div');
                                tooltipEl.id = array_sections_all[section] + "tooltip";
                                tooltipEl.innerHTML = "<table></table>"
                                document.body.appendChild(tooltipEl);
                            }
                            // Hide if no tooltip
                            if (tooltip.opacity === 0) {
                                // tooltipEl.style.opacity = 0;
                                //  return;
                            }
                            // Set caret Position
                            tooltipEl.classList.remove('above', 'below', 'no-transform');
                            if (tooltip.yAlign) {
                                tooltipEl.classList.add(tooltip.yAlign);
                            } else {
                                tooltipEl.classList.add('no-transform');
                            }

                            function getBody(bodyItem) {
                                return bodyItem.lines;
                            }

                            // Set Text
                            if (tooltip.body) {
                                var titleLines = tooltip.title || [];
                                var bodyLines = tooltip.body.map(getBody);

                                var label = bodyLines[0][0].substr(0, bodyLines[0][0].indexOf(':'));
                                var labelIndex = "";
                                var labelLong = "";
                                Object.keys(sectionsAll[array_sections_all[section]]['short_label_index']).forEach(function (index) {
                                    if (sectionsAll[array_sections_all[section]]['short_label_index'][index] == label) {
                                        if (index == "") {
                                            labelLong = "No WG";
                                        } else {
                                            labelLong = sectionsAll[array_sections_all[section]]['labels'][index];
                                        }
                                        labelIndex = index;
                                    }
                                });

                                //CUSTOM HTML TOOLTIP CONTENT
                                var innerHtml = '<thead>';
                                titleLines.forEach(function (title) {
                                    innerHtml += '<tr><th>' + title + '</th></tr>';
                                });
                                innerHtml += '</thead><tbody>';
                                bodyLines.forEach(function (body, i) {
                                    var colors = tooltip.labelColors[i];
                                    var style = 'background:' + colors.backgroundColor;
                                    style += '; border-color:' + colors.borderColor;
                                    style += '; border-width: 2px';
                                    var span = '<span class="chartjs-tooltip-key" style="' + style + '"></span>';
                                    var custom_url = url + '&type=' + labelIndex;
                                    innerHtml += '<tr><td><a href="' + custom_url + '" target="_blank" class="linkWG">' + span + labelLong + '</a><a href="#" onclick="$(\'#conceptswgtooltip\').hide()" class="closeWG" >x</a></td></tr>';
                                });
                                innerHtml += '</tbody>';
                                $('#' + array_sections_all[section] + "tooltip").html(innerHtml);

                            }
                            var position = this._chart.canvas.getBoundingClientRect();
                            // Display, position, and set styles for font
                            tooltipEl.style.opacity = 1;
                            tooltipEl.style.left = position.left + tooltip.caretX + 'px';
                            tooltipEl.style.top = position.top + tooltip.caretY + 'px';


                            tooltipEl.style.fontFamily = tooltip._fontFamily;
                            tooltipEl.style.fontSize = tooltip.fontSize;
                            tooltipEl.style.fontStyle = tooltip._fontStyle;
                            tooltipEl.style.padding = tooltip.yPadding + 'px ' + tooltip.xPadding + 'px';
                        };
                        var config = {
                            type: 'doughnut',
                            data: {
                                labels: sectionsAll[array_sections_all[section]]["short_label"],
                                datasets: [{
                                    backgroundColor: sectionsAll[array_sections_all[section]]["colors"],
                                    data: sectionsAll[array_sections_all[section]]["values"]
                                }]
                            },
                            options: {
                                responsive: false,
                                title: {
                                    display: false,
                                    position: "top",
                                    text: array_sections_title_all[section].toUpperCase(),
                                    fontSize: 18,
                                    fontColor: "#111"
                                },
                                legend: {
                                    display: false
                                },
                                plugins: {
                                    labels: [
                                        {
                                            render: 'label',
                                            position: 'outside',
                                            fontSize: 9,
                                            fontStyle: 'normal',
                                            textMargin: 5,
                                            outsidePadding: 20
                                        },
                                        {
                                            render: 'value',
                                            fontColor: '#fff',
                                            fontSize: 12
                                        }
                                    ]
                                },
                                tooltips: {
                                    enabled: false,
                                    mode: 'index',
                                    position: 'nearest',
                                    custom: customTooltips
                                },
                                animation: {
                                    onComplete: function (animation) {
                                        document.querySelector('#down' + array_sections_all[section]).setAttribute('href', this.toBase64Image());
                                    }
                                }
                            }
                        }

                        var donuts_chart = new Chart(ctx, config);
                    } else {
                        var config = {
                            type: 'doughnut',
                            data: {
                                labels: sectionsAll[array_sections_all[section]]["labels"],
                                datasets: [{
                                    backgroundColor: sectionsAll[array_sections_all[section]]["colors"],
                                    data: sectionsAll[array_sections_all[section]]["values"]
                                }]
                            },
                            options: {
                                responsive: false,
                                title: {
                                    display: false,
                                    position: "top",
                                    text: array_sections_title_all[section].toUpperCase(),
                                    fontSize: 18,
                                    fontColor: "#111"
                                },
                                legend: {
                                    display: false
                                },
                                plugins: {
                                    labels: [
                                        {
                                            render: 'label',
                                            position: 'outside',
                                            fontSize: 9,
                                            fontStyle: 'normal',
                                            textMargin: 5,
                                            outsidePadding: 20
                                        },
                                        {
                                            render: 'value',
                                            fontColor: '#fff',
                                            fontSize: 12
                                        }
                                    ]
                                },
                                tooltips: {
                                    mode: 'dataset'
                                },
                                animation: {
                                    onComplete: function (animation) {
                                        document.querySelector('#down' + array_sections_all[section]).setAttribute('href', this.toBase64Image());
                                    }
                                }
                            }
                        }
                        var donuts_chart = new Chart(ctx, config);
                    }
                }
                Chart.defaults.global.defaultFontStyle = 'bold';
            });
        }

        //MULTIREGIONAL & REGIONAL PUBLICATIONS / ABSTRACTS
        Object.keys(concept_type).forEach(function (section) {
            if((show_publications == '1' && section == '1') || (show_abstracts == '1' && section == "2")) {
                var dataset = [];
                if(sectionsAll[concept_type[section]]["single"]['0'] == '1'){
                    dataset.push(
                        {
                            label: sectionsAll[concept_type[section]]["label1"],
                            data: sectionsAll[concept_type[section]]["regionalmrpubs_mr"],
                            backgroundColor: sectionsAll[concept_type[section]]["regionalmrpubs_color"][0],
                            borderWidth: 0
                        }
                    );
                }
                if(sectionsAll[concept_type[section]]["single"]['1'] == '1'){
                    dataset.push(
                        {
                            label: sectionsAll[concept_type[section]]["label2"],
                            data: sectionsAll[concept_type[section]]["regionalmrpubs_outputs"],
                            backgroundColor: sectionsAll[concept_type[section]]["regionalmrpubs_color"][1],
                            borderWidth: 0
                        }
                    );
                }
                var ctxPubs = $("#" + concept_type[section] + "Chart");
                var configdataTimelineChart = {
                    type: 'bar',
                    data: {
                        labels: sectionsAll[concept_type[section]]["years_label"],
                        datasets: []
                    },
                    options: {
                        legend: {
                            display: true,
                            onHover: function (event, legendItem) {
                                document.getElementById(concept_type[section] + "Chart").style.cursor = 'pointer';
                            },
                            onClick: function (e, legendItem) {
                                var index = legendItem.datasetIndex;
                                var ci = this.chart;
                                var alreadyHidden = (ci.getDatasetMeta(index).hidden === null) ? false : ci.getDatasetMeta(index).hidden;

                                ci.data.datasets.forEach(function (e, i) {
                                    var meta = ci.getDatasetMeta(i);
                                    if (i !== index) {
                                        if (!alreadyHidden) {
                                            meta.hidden = meta.hidden === null ? !meta.hidden : null;
                                        } else if (meta.hidden === null) {
                                            meta.hidden = true;
                                        }
                                    } else if (i === index) {
                                        meta.hidden = null;
                                    }
                                });

                                ci.update();
                            }
                        },
                        tooltips: {
                            custom: function (tooltip) {
                                if (!tooltip.opacity) {
                                    document.getElementById(concept_type[section] + "Chart").style.cursor = 'default';
                                    return;
                                }
                            },
                            mode: 'index',
                            intersect: false
                        },
                        respondive: true,
                        scales: {
                            xAxes: [{
                                stacked: true

                            }],
                            yAxes: [{
                                stacked: true,
                                ticks: {
                                    stepSize: 10,
                                    beginAtZero: true,
                                }
                            }]
                        },
                        plugins: {
                            labels: false
                        },
                        animation: {
                            onComplete: function (animation) {
                                document.querySelector('#down' + concept_type[section]).setAttribute('href', this.toBase64Image());
                            }
                        }
                    }
                };
                var communication_chart = new Chart(ctxPubs, configdataTimelineChart);

                Object.keys(dataset).forEach(function (index) {
                    communication_chart.data.datasets.push(dataset[index]);
                    communication_chart.update();
                });
            }
        });

        //MULTIREGIONAL ACTIVITY BY YEAR
        if(show_activity == '1') {
            var dataset = [];
            var activity_labels = ['New concepts','Manuscripts','Abstracts','MR Data Requests'];
            var activity_color = ['#337ab7','#ffa64d','#00b386','#bf80ff'];
            var activity_data = ['concepts','manuscripts','abstracts','mrdatarequests'];
            Object.keys(activity_data).forEach(function (index) {
                if (show_activity_single[parseInt(index)] == '1') {
                    dataset.push(
                        {
                            label: activity_labels[index],
                            data: multiregionalActivityByYear[activity_data[index]],
                            backgroundColor: activity_color[index],
                            borderColor: activity_color[index],
                            fill: false
                        }
                    );
                }
            });
            var ctx_iedea = $("#IedeaChart");
            var config_iedea = {
                type: 'line',
                data: {
                    labels: years_label_concepts,
                    datasets: []
                },
                options: {
                    elements: {
                        line: {
                            tension: 0, // disables bezier curves
                        }
                    },
                    tooltips: {
                        mode: 'index',
                        intersect: false
                    },
                    animation: {
                        onComplete: function (animation) {
                            document.querySelector('#downmultiregionalyear').setAttribute('href', this.toBase64Image());
                        }
                    }
                }
            }

            var iedea_chart = new Chart(ctx_iedea, config_iedea);

            Object.keys(dataset).forEach(function (index) {
                iedea_chart.data.datasets.push(dataset[index]);
                iedea_chart.update();
            });
        }
    });
</script>
<div class="container">
    <div class="backTo">
        <a href="<?=$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS']?>">< Back to Home</a>
    </div>
</div>
<div class="container">
    <h3><?=$settings['hub_name']?> Metrics</h3>
    <p class="hub-title"><?=filter_tags(($settings['hub_statistics_text']=="")? $default_values_settings['hub_statistics_text']:$settings['hub_statistics_text'])?></p>
</div>

<!-- DONUTS -->
<?php if($settings['hub_stats_section1_y'] == '1'){?>
<div class="container" style="padding-top: 60px">
    <h4><?=($settings['hub_stats_consortium_title']=="")? $default_values_settings['hub_stats_consortium_title']:$settings['hub_stats_consortium_title']?></h4>
    <p class="hub-title"><?=filter_tags(($settings['hub_stats_consortium']=="")? $default_values_settings['hub_stats_consortium']:$settings['hub_stats_consortium'])?></p>
</div>
<div class="container">
    <?php foreach ($array_sections_title_all as $index=>$section){
        if($index <= 2 && $settings['hub_stats_consortium_select___'.($index+1)] == '1'){?>
        <div class="canvas_title"><?=$section?>
            <a href="#" download="<?=$array_sections_all[$index].".png"?>" class="fa fa-download" style="color:#8c8c8c;padding-left:10px;" id="<?="down".$array_sections_all[$index]?>" name="<?="down".$array_sections_all[$index]?>"></a>
        </div>
    <?php }
        }?>
</div>
<div class="container">
    <?php foreach ($array_sections as $index => $section){
        $id = $section."Chart";
        if($settings['hub_stats_consortium_select___'.($index+1)] == '1'){
            if($section == 'conceptswg'){
                $idtool = $section."tooltip";
                ?><div id="<?=$idtool?>"></div><?php
            }
            ?>
            <canvas id="<?=$id?>" class="canvas_statistics" width="360px" height="330px"></canvas>
    <?php }
    }?>
</div>
<?php } ?>

<!-- PUBLICATIONS -->
<?php if($settings['hub_stats_section2_y'] == '1'){?>
<div class="container" style="padding-top: 60px">
    <h4>
        <?=($settings['hub_stats_pubs_title']=="")?$default_values_settings['hub_stats_pubs_title']:$settings['hub_stats_pubs_title']?>
        <a href="#" download="mr_r_publications.png" class="fa fa-download" style="color:#8c8c8c;padding-left:10px;" id="downmanuscripts" name="downmanuscripts"></a>
    </h4>
    <p class="hub-title"><?=filter_tags(($settings['hub_stats_rmr_publications']=="")?$default_values_settings['hub_stats_rmr_publications']:$settings['hub_stats_rmr_publications'])?></p>
</div>
<div class="container">
    <canvas id="manuscriptsChart" class="canvas_statistics" width="1100px" height="310px"></canvas>
</div>
<br>
<br>
    <?php if($settings['hub_stats_section2a_y'] == '1'){?>
    <div class="container">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <a data-toggle="collapse" href="#collapse_manuscripts"><?=($settings['hub_stats_pubs2_title']=="")?$default_values_settings['hub_stats_pubs2_title']:$settings['hub_stats_pubs2_title']?> <span class="badge badge-primary"><?=$data_manuscripts['total']?></span></a>
                </h3>
            </div>
            <div id="collapse_manuscripts" class="panel-collapse collapse" aria-expanded="true">
                <table class="table table_requests sortable-theme-bootstrap" data-sortable id="sortable_table">
                    <thead>
                    <th width="150px" style="text-align: center">Year</th>
                    <th width="150px" style="text-align: center">Total</th>
                    <th>Journal</th>
                    </thead>
                    <tbody>
                    <?php echo $data_manuscripts['content']; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div style="padding-left: 15px;"><i class="fa fa-info-circle fa-fw " aria-hidden="true"></i> <em>Table shows publications between <?=$settings['oldestyear_rmr_manuscripts']?> and the current year. Publications with no year listed are not shown.</em></div>
    <?php } ?>
<?php } ?>

<!-- ABSTRACTS -->
<?php if($settings['hub_stats_section3_y'] == '1'){?>
<div class="container" style="padding-top: 60px">
    <h4>
        <?=($settings['hub_stats_abs_title']=="")?$default_values_settings['hub_stats_abs_title']:$settings['hub_stats_abs_title']?>
        <a href="#" download="mr_r_abstracts.png" class="fa fa-download" style="color:#8c8c8c;padding-left:10px;" id="downabstracts" name="downabstracts"></a>
    </h4>
    <p class="hub-title"><?=filter_tags(($settings['hub_stats_rmr_abstratcs']=="")?$default_values_settings['hub_stats_rmr_abstratcs']:$settings['hub_stats_rmr_abstratcs'])?></p>
</div>
<div class="container">
    <canvas id="abstractsChart" class="canvas_statistics" width="1100px" height="310px"></canvas>
</div>
<br>
<br>
<?php if($settings['hub_stats_section3a_y'] == '1'){?>
    <div class="container">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <a data-toggle="collapse" href="#collapse_abstracts"><?=($settings['hub_stats_abs2_title']=="")?$default_values_settings['hub_stats_abs2_title']:$settings['hub_stats_abs2_title']?> <span class="badge badge-primary"><?=$data_abstracts['total']?></span></a>
                </h3>
            </div>
            <div id="collapse_abstracts" class="panel-collapse collapse" aria-expanded="true">
                <table class="table table_requests sortable-theme-bootstrap" data-sortable id="sortable_table">
                    <thead>
                    <th width="150px" style="text-align: center">Year</th>
                    <th width="150px" style="text-align: center">Total</th>
                    <th>Conference</th>
                    </thead>
                    <tbody>
                    <?php echo $data_abstracts['content']; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div style="padding-left: 15px;"><i class="fa fa-info-circle fa-fw " aria-hidden="true"></i> <em>Table shows abstracts between <?=$settings['oldestyear_rmr_abstracts']?> and the current year. Abstracts with no year listed are not shown.</em></div>
    <?php } ?>
<?php } ?>

<!-- ACTIVITY -->
<?php if($settings['hub_stats_section4_y'] == '1'){?>
<div class="container" style="padding-top: 60px">
    <h4>
        <?=$settings['hub_name']?> <?=($settings['hub_stats_activity_title']=="")?$default_values_settings['hub_stats_activity_title']:$settings['hub_stats_activity_title']?>
        <a href="#" download="multiregional_activity_year.png" class="fa fa-download" style="color:#8c8c8c;padding-left:10px;" id="downmultiregionalyear" name="downmultiregionalyear"></a>
    </h4>
    <p class="hub-title"><?=filter_tags(($settings['hub_stats_mr_activity_year']=="")?$default_values_settings['hub_stats_mr_activity_year']:$settings['hub_stats_mr_activity_year'])?></p>
</div>
<div class="container">
    <canvas id="IedeaChart" class="canvas_statistics" width="350px" height="100px"></canvas>
</div>
<?php } ?>

<!-- MAP -->
<?php if($settings['hub_stats_section5_y'] == '1'){?>
<div class="container" style="padding-top: 60px">
    <h4><?=$settings['hub_name']?> <?=filter_tags(($settings['hub_stats_map_title']=="")?$default_values_settings['hub_stats_map_title']:$settings['hub_stats_map_title'])?></h4>
    <p class="hub-title"><?=filter_tags(($settings['hub_stats_map']=="")?$default_values_settings['hub_stats_map']:$settings['hub_stats_map'])?></p>
</div>
<div class="container" style="padding-top: 20px">
    <?php include(dirname(dirname(__FILE__)).'/map/map_stats.php');?>
</div>
<script>
    $(document).ready(function() {
        setDataset("");
    } );
</script>
<?php } ?>
<?php if($settings['hub_stats_section5a_y'] == '1'){?>
<div class="container" style="padding-top: 60px">
    <h4><?=$settings['hub_name']?> <?=filter_tags(($settings['hub_stats_sitelist_title']=="")?$default_values_settings['hub_stats_sitelist_title']:$settings['hub_stats_sitelist_title'])?></h4>
    <p class="hub-title"><?=filter_tags(($settings['hub_stats_site_list']=="")?$default_values_settings['hub_stats_site_list']:$settings['hub_stats_site_list'])?></p>
</div>

<div class="container">
    <?php
    $TBLCenter = \REDCap::getData($pidsArray['TBLCENTERREVISED'], 'json-array', null);
    $country = $module->getChoiceLabels('country', $pidsArray['TBLCENTERREVISED']);
    $region_name = $module->getChoiceLabels('region', $pidsArray['TBLCENTERREVISED']);

    $tbl_array = array();
    $regions_ordered = \REDCap::getData($pidsArray['REGIONS'], 'json-array', null,null,null,null,false,false,false,"[showregion_y] = '1'");
    ArrayFunctions::array_sort_by_column($regions_ordered,'region_code');
    //To order the display
    foreach ($regions_ordered as $region){
        $tbl_array[$region['region_code']]['country'] = array();
        $tbl_array[$region['region_code']]['center'] = array();
    }

    $tbl_adultped_array = [];
    $tbl_adultped_array['adultstotalcountry'] = [];
    $tbl_adultped_array['pedsstotalcountry'] = [];
    $tbl_array['country'] = [];
    foreach($TBLCenter as $record ){
        if(($record['drop_center'] == "" || !array_key_exists('drop_center',$record)) && $record['region'] != ""){
            if(!array_key_exists($record['region'], $tbl_array)){
                $tbl_array[$record['region']] = [];
                $tbl_array[$record['region']]['center'] = [];
                $tbl_array[$record['region']]['country'] = [];
            }
            $tbl_array[$record['region']]['sites'] += 1;
            $tbl_adultped_array['sites'] += 1;

            if($record['country'] != "") {
                if(!array_key_exists($country[$record['country']], $tbl_array['country'])){
                    $tbl_array['country'][$country[$record['country']]] = 0;
                }
                $tbl_array['country'][$country[$record['country']]] += 1;

                if(!array_key_exists($country[$record['country']],  $tbl_array[$record['region']]['country'])){
                    $tbl_array[$record['region']]['country'][$country[$record['country']]] = 0;
                }
                $tbl_array[$record['region']]['country'][$country[$record['country']]] += 1;
            }

            if($record['center'] != "") {
                if(!array_key_exists($record['center'], $tbl_array[$record['region']]['center'])){
                    $tbl_array[$record['region']]['center'][$record['center']] = 0;
                }
                $tbl_array[$record['region']]['center'][$record['center']] += 1;
            }

            if($record['adultped'] == 'ADULT'){
                $tbl_array[$record['region']]['adults'] += 1;
                $tbl_adultped_array['adultstotal'] += 1;
                if($record['country'] != "") {
                    if(!array_key_exists($country[$record['country']], $tbl_adultped_array['adultstotalcountry'])){
                        $tbl_adultped_array['adultstotalcountry'][$country[$record['country']]] = 0;
                    }
                    $tbl_adultped_array['adultstotalcountry'][$country[$record['country']]] += 1;
                }
            }else if($record['adultped'] == 'PED'){
                $tbl_array[$record['region']]['peds'] += 1;
                $tbl_adultped_array['pedsstotal'] += 1;
                if($record['country'] != "") {
                    $tbl_adultped_array['pedsstotalcountry'][$country[$record['country']]] += 1;
                }
            }else if($record['adultped'] == 'BOTH'){
                $tbl_array[$record['region']]['adults'] += 1;
                $tbl_array[$record['region']]['peds'] += 1;
                $tbl_adultped_array['adultstotal'] += 1;
                $tbl_adultped_array['pedsstotal'] += 1;
                if($record['country'] != "") {
                    $tbl_adultped_array['adultstotalcountry'][$country[$record['country']]] += 1;
                    $tbl_adultped_array['pedsstotalcountry'][$country[$record['country']]] += 1;
                }
            }
        }

    }
    ksort($tbl_adultped_array['adultstotalcountry']);
    ksort($tbl_adultped_array['pedsstotalcountry']);
    ksort($tbl_array['country']);

    $consortumcomp = "<tr style='background-color: #f5f5f5'><td><strong>Adult</strong></td>
                           <td width='120px'>".$tbl_adultped_array['adultstotal']."</td>
                           <td>".count($tbl_adultped_array['adultstotalcountry'])."</td>
                           <td width='419px'><div class='more'>".\Vanderbilt\HarmonistHubExternalModule\implode_key_and_value($tbl_adultped_array['adultstotalcountry'])."</div></td></tr>";
    $consortumcomp .= "<tr style='background-color: #f5f5f5'><td><strong>Pediatric</strong></span></td>
                           <td>".$tbl_adultped_array['pedsstotal']."</td>
                           <td>".count($tbl_adultped_array['pedsstotalcountry'])."</td>
                           <td width='419px'><div class='more'>".\Vanderbilt\HarmonistHubExternalModule\implode_key_and_value($tbl_adultped_array['pedsstotalcountry'])."</div></td></tr>";
    $total_countries = 0;
    foreach($tbl_array as $region=>$table ){
        if($region != 'country') {
            $total_countries += count($tbl_array[$region]['country']);
            ksort($tbl_array[$region]['country']);
            $consortumcomp .= "<tr><td width='120px'><strong>" . $region_name[$region] . "</strong></span></td>
                            <td>" . $tbl_array[$region]['sites'] . "</td>
                            <td>" . count($tbl_array[$region]['country']) . "</td>
                            <td width='419px'><div class='more'>" . \Vanderbilt\HarmonistHubExternalModule\implode_key_and_value($tbl_array[$region]['country']) . "</div></td></tr>";
        }
    }
    $consortumcomp_all = "<tr style='background-color: aliceblue'><td><strong>Total</strong></td>
                           <td width='120px'>".$tbl_adultped_array['sites']."</td>
                           <td>".$total_countries."</td>
                           <td width='419px'><div class='more'>".\Vanderbilt\HarmonistHubExternalModule\implode_key_and_value($tbl_array['country'])."</div></td></tr>";

    $consortumcomp = $consortumcomp_all.$consortumcomp;
    ?>

    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">
                <a data-toggle="collapse" href="#collapse_consortium"><?=$settings['hub_name']?> <?=filter_tags(($settings['hub_stats_sitelist_title']=="")?$default_values_settings['hub_stats_sitelist_title']:$settings['hub_stats_sitelist_title'])?> <span class="badge badge-primary"><?=$tbl_adultped_array['sites']?></span></a>
            </h3>
        </div>
        <div id="collapse_consortium" class="panel-collapse collapse" aria-expanded="true">
            <table class="table table_requests sortable-theme-bootstrap" data-sortable id="sortable_table">
                <thead>
                <th width="120px"></th>
                <th width="105px"># Sites</th>
                <th width="105px"># Countries</th>
                <th width="419px">Countries (# Sites)</th>
                </thead>
                <tbody>
                <?php echo $consortumcomp; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php } ?>
<div style="padding-bottom: 100px"></div>
