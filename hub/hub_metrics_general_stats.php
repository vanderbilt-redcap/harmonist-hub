<?php
namespace Vanderbilt\HarmonistHubExternalModule;

$q = $module->query("SELECT MAX(record) as record FROM ".\Vanderbilt\HarmonistHubExternalModule\getDataTable($pidsArray['METRICS'])." WHERE project_id = ?",[$pidsArray['METRICS']]);
$row = $q->fetch_assoc();
$RecordSetMetrics = \REDCap::getData($pidsArray['METRICS'], 'array', array('record_id' => $row['record']));
$metrics = ProjectData::getProjectInfoArray($RecordSetMetrics)[0];

$array_sections = array(0=>'requests',1=>'comments',2=>'users');
$array_sections_title = array(0=>'requests', 1=>'comments',2=>'users by region');

$array_sections_data = array(0=>'fileActivity',1=>'upRegion',2=>'downRegion');
$array_sections_title_data = array(0=>'file activity', 1=>'uploads by region',2=>'downloads by region');

$sections_donuts = array(0=>'requests',1=>'comments',2=>'users',3=>'fileActivity',4=>'upRegion',5=>'downRegion');
$sections_donuts_title = array(0=>'requests', 1=>'comments',2=>'users by region',3=>'file activity',4=>'uploads by region',5=>'downloads by region');

/******CONTENT*****/
/**
#General Hub Usage Stats
 **/
#REQUESTS
$RecordSetRM = \REDCap::getData($pidsArray['RMANAGER'], 'array', null,null,null,null,false,false,false,"[approval_y] = 1");
$request = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRM);
ArrayFunctions::array_sort_by_column($request, 'due_d');

$instance = $current_user['person_region'];
if ($instance == 1) {
    $instance = '';
}

$number_concepts = 0;
$number_abstracts = 0;
$number_manuscripts = 0;
$number_poster = 0;
$number_fastTrack = 0;
$number_other = 0;
foreach ($request as $req){
    if($req['request_type'] == '1'){
        $number_concepts++;
    }else if($req['request_type'] == '2'){
        $number_abstracts++;
    }else if($req['request_type'] == '3'){
        $number_manuscripts++;
    }else if($req['request_type'] == '4'){
        $number_poster++;
    }else if($req['request_type'] == '5'){
        $number_fastTrack++;
    }else if($req['request_type'] == '99'){
        $number_other++;
    }
}

#USERS
$RecordSetRegions = \REDCap::getData($pidsArray['REGIONS'], 'array',  null);
$regions = ProjectData::getProjectInfoArray($RecordSetRegions);
$array_region = array();
$array_region_code = array();
foreach ($regions as $region){
    if(!key_exists($region['record_id'],$array_region)){
        $array_region[$region['record_id']] =  0;
    }
    if(!key_exists($region['region_code'],$array_region_code)){
        $array_region_code[$region['record_id']] =  $region['region_code'];
    }
}

$RecordSetPeople = \REDCap::getData($pidsArray['PEOPLE'], 'array',  null,null,null,null,false,false,false,"[last_requested_token_d] <> ''");
$people = ProjectData::getProjectInfoArray($RecordSetPeople);
foreach ($people as $person){
    foreach ($array_region as $region=>$value){
        if($region == $person['person_region']){
            $array_region[$region] += 1;
        }
    }
}

$requests_values = $module->escape(array(0 => $number_concepts,1=> $number_abstracts,2 => $number_manuscripts,3 => $number_fastTrack,4 => $number_poster ,5 => $number_other));
$requests_labels = $module->escape(array(0 => "Concepts",1 => "Abstracts",2 => "Manuscripts",3 => "Fast Track",4 => "Poster", 5=>"Other"));
$requests_colors = $module->escape(array(0 => "#337ab7",1 => "#00b386",2 => "#f0ad4e",3 => "#ff9966",4 => "#5bc0de",5 => "#777"));

$comments_values = $module->escape(array(0 => $metrics['comments_pi'],1 => $metrics['comments_n']));
$comments_labels = $module->escape(array(0 => "PI",1 => "non PI"));
$comments_colors = $module->escape(array(0 => "#1ad1ff",1 => "#eb6e60"));

$users_values = array();
foreach ($array_region as $number){
    array_push($users_values,$number);
}
$users_labels = array();
$users_colors = array();
$count_wg = 0;
$wg_percent = 75;
$max_wg = count($array_region_code);
foreach ($array_region_code as $rcode){
    array_push($users_labels,$rcode);
    $color = "hsl(210,50%,".$wg_percent."%)";
    array_push($users_colors,$color);
    $wg_percent -= 5;
}

/**
#Data Exchange
 **/
#FILE ACTIVITY
$RecordSetDU = \REDCap::getData($pidsArray['DATAUPLOAD'], 'array', null);
$number_uploads = count(ProjectData::getProjectInfoArray($RecordSetDU));

$RecordSetDN = \REDCap::getData($pidsArray['DATADOWNLOAD'], 'array', null);
$number_downloads = count(ProjectData::getProjectInfoArray($RecordSetDN));

$RecordSetDU = \REDCap::getData($pidsArray['DATAUPLOAD'], 'array', null,null,null,null,false,false,false,"[deleted_y] = '1' AND [deletion_type] = '2'");
$number_deletes = count(ProjectData::getProjectInfoArray($RecordSetDU));

$RecordSetDU = \REDCap::getData($pidsArray['DATAUPLOAD'], 'array', null,null,null,null,false,false,false,"[deleted_y] = '1' AND [deletion_type] = '1'");
$number_deletes_auto = count(ProjectData::getProjectInfoArray($RecordSetDU));

$fileActivity_values = array(0 => $number_uploads,1 => $number_downloads,2 => $number_deletes);
$fileActivity_total = $number_uploads + $number_downloads;


#REGFIONS UP & DOWN
$upRegion_labels = array();
$upRegion_values = array();
$upRegion_colors = array();
$downRegion_labels = array();
$downRegion_values = array();
$downRegion_colors = array();
$wg_percent_down = 75;
$wg_percent_up = 75;
$RecordSetRegions = \REDCap::getData($pidsArray['REGIONS'], 'array', null,null,null,null,false,false,false,"[showregion_y] = '1'");
$regions = ProjectData::getProjectInfoArray($RecordSetRegions);
foreach ($regions as $region){
    array_push($upRegion_labels,$region['region_code']);
    array_push($downRegion_labels,$region['region_code']);

    $RecordSetDU = \REDCap::getData($pidsArray['DATAUPLOAD'], 'array', null,null,null,null,false,false,false,"[data_upload_region] = '".$region['record_id']."'");
    array_push($upRegion_values,count(ProjectData::getProjectInfoArray($RecordSetDU)));
    $RecordSetDN = \REDCap::getData($pidsArray['DATADOWNLOAD'], 'array', null,null,null,null,false,false,false,"[downloader_region] = '".$region['record_id']."'");
    array_push($downRegion_values,count(ProjectData::getProjectInfoArray($RecordSetDN)));

    $color_up = "hsl(120,39%,".$wg_percent_up."%)";
    $color_down = "hsl(210,50%,".$wg_percent_down."%)";
    array_push($upRegion_colors,$color_up);
    array_push($downRegion_colors,$color_down);
    $wg_percent_up -= 5;
    $wg_percent_down -= 5;
}

#TITLES AND COLORS
$fileActivity_labels = $module->escape(array(0 => "Uploads",1 => "Downloads",2 => "Manual Delete"));
$fileActivity_colors = $module->escape(array(0 => "#5cb85c",1 => "#337ab7",2 => "#eb6e60"));


/**
#Statistics Data & Data Call timeline
 **/
$RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', null,null,null,null,false,false,false,"[sop_active] = '1'");
$request_dataCall = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP);

#DATA CALL TIMELINE
$current_year = date('Y');
$current_month = date('m');
$first_year = $current_year - $settings['numberofyears_datacall'];
$start_date_datacall_Y = date('Y',strtotime($request_dataCall[1]['sop_created_dt']));
$start_date_datacall_m = date('m',strtotime($request_dataCall[1]['sop_created_dt']));
$time_labels = array();
$dataCall_started_timeline = array();
$dataCall_ended_timeline = array();
$yearfound = false;
for($year = $first_year; $year<$current_year+1; $year++){

    if($year == $start_date_datacall_Y){
        $start_month = $start_date_datacall_m;
        $yearfound = true;
    }else{
        $start_month = 1;
    }

    if($year == $current_year){
        $end_month = $current_month+1;
    }else{
        $end_month = 13;
    }

    if($yearfound) {
        for ($month = $start_month; $month < $end_month; $month++) {
            $text_month = $month;
            if ($month < 10 && substr($month, 0, 1) !== '0') {
                $text_month = "0" . $month;
            }

            array_push($time_labels, $year . "-" . $text_month);
            array_push($dataCall_started_timeline, 0);
            array_push($dataCall_ended_timeline, 0);
        }
    }
}

$dataCall_closed = 0;
$dataCall_avg_count = 0;
$datasets_up_due = 0;
$datasets_up_afterdue = 0;
$dataCall_total = count($request_dataCall);
$unique_data_downloaders = array();
$dataCall_timeline_labels = array();
foreach ($request_dataCall as $dataCall){
    if($dataCall['sop_visibility'] == 1){
        $dataReq_values[0] += 1;
    }else if($dataCall['sop_visibility'] == 2){
        if($dataCall['sop_status'] == '0'){
            $dataReq_values[1] += 1;
        }else if($dataCall['sop_status'] == '1'){
            $dataReq_values[2] += 1;
        }
    }
    if($dataCall['sop_closed_y'][1] == '1'){
        $dataCall_closed += 1;
        $RecordSetDU = \REDCap::getData($pidsArray['DATAUPLOAD'], 'array', null,null,null,null,false,false,false,"[data_assoc_request] = '".$dataCall['record_id']."'");
        $dataCall_avg_count +=count(ProjectData::getProjectInfoArray($RecordSetDU)[0]);
    }

    if($dataCall['sop_downloaders'] != ""){
        $array_userid = explode(',', $dataCall['sop_downloaders']);
        foreach ($array_userid as $id){
            if(!in_array($id, $unique_data_downloaders)){
                array_push($unique_data_downloaders,$id);
            }
        }
    }

    $RecordSetDU = \REDCap::getData($pidsArray['DATAUPLOAD'], 'array', null,null,null,null,false,false,false,"[data_assoc_request] = '".$dataCall['record_id']."'");
    if(strtotime($dataCall['sop_due_d']) >= strtotime(ProjectData::getProjectInfoArray($RecordSetDU)[0]['responsecomplete_ts'])){
        $datasets_up_due++;
    }else{
        $datasets_up_afterdue++;
    }

    #Timeline calculations
    if($dataCall['sop_created_dt'] != "" && $dataCall['sop_final_d'] != ""){
        $start_found = false;
        $end_found = false;
        foreach ($time_labels as $index => $time){
            #START DATACALL
            if($time == date('Y-m',strtotime($dataCall['sop_created_dt']))){
                $start_found = true;
            }
            if($start_found && (strtotime($time) <= strtotime($dataCall['sop_final_d']) || !array_key_exists('sop_final_d',$dataCall) || $dataCall['sop_final_d'] == '')){
                $dataCall_started_timeline[$index]++;
                $title = explode(",",$dataCall['sop_name'])[0];
                $title = str_replace("Data Request for ","",$title)."\n";
                if(strpos($dataCall_timeline_labels[$index], $title) === false) {
                    $dataCall_timeline_labels[$index] .= $title;
                }
            }
            #END DATACALL
            if($start_found && $time == date('Y-m',strtotime($dataCall['sop_final_d']))){
                $end_found = true;
            }
            if($end_found && (strtotime($time) <= strtotime($dataCall['sop_closed_d']) || !array_key_exists('sop_closed_d',$dataCall) || $dataCall['sop_closed_d'] == '')){
                $dataCall_ended_timeline[$index]++;
                $title = explode(",",$dataCall['sop_name'])[0];
                $title = str_replace("Data Request for ","",$title)."\n";
                if(strpos($dataCall_timeline_labels[$index], $title) === false) {
                    $dataCall_timeline_labels[$index] .= $title;
                }
            }
        }
    }
}

$data_downloaders = count($unique_data_downloaders);
$dataCall_avg = round($dataCall_avg_count/$dataCall_closed,2);

/**
#Hub Communications
 **/
$harmonist_regperm_labels = $module->getChoiceLabels('harmonist_regperm', $pidsArray['PEOPLE']);
$RecordSetPeople = \REDCap::getData($pidsArray['PEOPLE'], 'array', null);
$people = ProjectData::getProjectInfoArray($RecordSetPeople);
$array_communications = array();
$communications_years = array();
for($year = $settings['oldestyear_communications']; $year <= date("Y"); $year++){
    array_push($communications_years,$year);
    foreach ($harmonist_regperm_labels as $value=>$label){
        $array_communications[$value][$year] = 0;
    }
    foreach ($people as $person){
        $year_person = date('Y',strtotime($person['first_ever_login_d']));
        $year_person_inactive = date('Y',strtotime($person['inactive_d']));
        if($year_person == $year) {
            $array_communications[$person['harmonist_regperm']][$year] += 1;
        }
        if($year_person_inactive == $year && $person['active_y'] == '0' && $array_communications[$person['harmonist_regperm'][1]][$year] > 0){
            $array_communications[$person['harmonist_regperm']][$year] -= 1;
        }
    }
    #Add the values from the previous year
    if($year > $settings['oldestyear_communications']){
        foreach ($harmonist_regperm_labels as $value=>$label){
            $array_communications[$value][$year] = $array_communications[$value][$year] + $array_communications[$value][$year-1];
        }
    }
}

#Escape All Data
$dataCall_timeline_labels = $module->escape($dataCall_timeline_labels);
$dataCall_ended_timeline = $module->escape($dataCall_ended_timeline);
$dataCall_started_timeline = $module->escape($dataCall_started_timeline);
$time_labels = $module->escape($time_labels);
$array_communications = $module->escape($array_communications);
$communications_years = $module->escape($communications_years);
$array_sections = $module->escape($array_sections);
$array_sections_data = $module->escape($array_sections_data);
$array_sections_title_data = $module->escape($array_sections_title_data);
$sections_donuts = $module->escape($sections_donuts);
$sections_donuts_title = $module->escape($sections_donuts_title);
$requests_values = $module->escape($requests_values);
$requests_labels = $module->escape($requests_labels);
$requests_colors = $module->escape($requests_colors);
$comments_values = $module->escape($comments_values);
$comments_labels = $module->escape($comments_labels);
$comments_colors = $module->escape($comments_colors);
$users_values = $module->escape($users_values);
$users_labels = $module->escape($users_labels);
$users_colors = $module->escape($users_colors);
$fileActivity_values = $module->escape($fileActivity_values);
$fileActivity_labels = $module->escape($fileActivity_labels);
$fileActivity_colors = $module->escape($fileActivity_colors);
$upRegion_values = $module->escape($upRegion_values);
$upRegion_labels = $module->escape($upRegion_labels);
$upRegion_colors = $module->escape($upRegion_colors);
$downRegion_values = $module->escape($downRegion_values);
$downRegion_labels = $module->escape($downRegion_labels);
$downRegion_colors = $module->escape($downRegion_colors);
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
    $(function () {
        var sections_donuts = <?=json_encode($sections_donuts)?>;
        var sections_donuts_title = <?=json_encode($sections_donuts_title)?>;

        //General Hub Usage Stats
        var array_sections = <?=json_encode($array_sections)?>;
        var array_sections_title = <?=json_encode($array_sections_title)?>;

        var requests_values = <?=json_encode($requests_values)?>;
        var requests_labels = <?=json_encode($requests_labels)?>;
        var requests_colors = <?=json_encode($requests_colors)?>;
        var comments_values = <?=json_encode($comments_values)?>;
        var comments_labels = <?=json_encode($comments_labels)?>;
        var comments_colors = <?=json_encode($comments_colors)?>;
        var users_values = <?=json_encode($users_values)?>;
        var users_labels = <?=json_encode($users_labels)?>;
        var users_colors = <?=json_encode($users_colors)?>;

        //Data Exchange
        var array_sections_data = <?=json_encode($array_sections_data)?>;
        var array_sections_title_data = <?=json_encode($array_sections_title_data)?>;

        var fileActivity_values = <?=json_encode($fileActivity_values)?>;
        var fileActivity_labels = <?=json_encode($fileActivity_labels)?>;
        var fileActivity_colors = <?=json_encode($fileActivity_colors)?>;

        var upRegion_values = <?=json_encode($upRegion_values)?>;
        var upRegion_labels = <?=json_encode($upRegion_labels)?>;
        var upRegion_colors = <?=json_encode($upRegion_colors)?>;

        var downRegion_values = <?=json_encode($downRegion_values)?>;
        var downRegion_labels = <?=json_encode($downRegion_labels)?>;
        var downRegion_colors = <?=json_encode($downRegion_colors)?>;

        //Data Call Timeline
        var time_labels = <?=json_encode($time_labels)?>;
        var dataCall_started_timeline = <?=json_encode($dataCall_started_timeline)?>;
        var dataCall_ended_timeline = <?=json_encode($dataCall_ended_timeline)?>;
        var dataCall_timeline_labels = <?=json_encode($dataCall_timeline_labels)?>;

        //Hub Communications
        var communications_years = <?=json_encode($communications_years)?>;
        var communications_noaccess = <?=json_encode(array_values($array_communications[0]))?>;
        var communications_viewonly = <?=json_encode(array_values($array_communications[1]))?>;
        var communications_submitccomments = <?=json_encode(array_values($array_communications[2]))?>;
        var communications_voteregion = <?=json_encode(array_values($array_communications[3]))?>;

        //DONUTS
        Object.keys(sections_donuts).forEach(function (section) {
            var  ctx = $("#"+sections_donuts[section]+"Chart");
            var config = {
                type: 'doughnut',
                data: {
                    labels: eval(sections_donuts[section]+"_labels"),
                    datasets: [{
                        backgroundColor: eval(sections_donuts[section]+"_colors"),
                        data: eval(sections_donuts[section]+"_values")
                    }]
                },
                options: {
                    responsive: false,
                    title: {
                        display: false,
                        position: "top",
                        text: sections_donuts_title[section].toUpperCase(),
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
                                fontSize:9,
                                fontStyle: 'normal',
                                textMargin: 5,
                                outsidePadding: 20
                            },
                            {
                                render: 'value',
                                fontColor: '#fff',
                                fontSize:12
                            }
                        ]
                    },
                    tooltips: {
                        mode: 'dataset'
                    },
                    animation: {
                        onComplete: function(animation){
                            document.querySelector('#down'+sections_donuts[section]).setAttribute('href', this.toBase64Image());
                        }
                    }
                }
            }
            var publications_chart = new Chart(ctx, config);


            Chart.defaults.global.defaultFontStyle = 'bold';
        });

        //Data Call Timeline
        var  ctxdataTimelineChart = $("#dataTimelineChart");
        var configdataTimelineChart = {
            type: 'bar',
            data: {
                labels: time_labels,
                datasets: [
                    {
                        label: 'Pre-Data Call Requests',
                        data: dataCall_started_timeline,
                        backgroundColor: '#337ab7',
                        borderWidth: 0
                    },
                    {
                        label: 'Active Data Calls',
                        data: dataCall_ended_timeline,
                        backgroundColor: '#ffa64d',
                        borderWidth: 0
                    }
                ]
            },
            options: {
                legend: {
                    display: true,
                    onHover: function(event, legendItem) {
                        document.getElementById("dataTimelineChart").style.cursor = 'pointer';
                    },
                    onClick: function(e, legendItem) {
                        var index = legendItem.datasetIndex;
                        var ci = this.chart;
                        var alreadyHidden = (ci.getDatasetMeta(index).hidden === null) ? false : ci.getDatasetMeta(index).hidden;

                        ci.data.datasets.forEach(function(e, i) {
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
                    callbacks: {
                        title: function(tooltipItem, data) {
                            return "Pre-Data Call Requests: "+data['datasets'][0]['data'][tooltipItem[0]['index']]+"\n"+"Active Data Calls: "+data['datasets'][1]['data'][tooltipItem[0]['index']];
                        },
                        label: function(tooltipItem, data) {
                            // return dataCall_timeline_labels[tooltipItem['index']];
                            if(dataCall_timeline_labels[tooltipItem['index']] != undefined){
                                var text = dataCall_timeline_labels[tooltipItem['index']];
                                var titles = text.split("\n");
                                var text_array = [];
                                Object.keys(titles).forEach(function (index) {
                                    if(titles[index] != ""){
                                        text_array.push(titles[index]);
                                    }
                                });
                                return text_array;
                            }

                        }
                    },
                    backgroundColor: '#FFF',
                    titleFontSize: 16,
                    titleFontColor: '#337ab7',
                    bodyFontColor: '#000',
                    bodyFontSize: 14,
                    displayColors: false,
                    intersect: false
                },
                responsive: false,
                scales : {
                    xAxes : [{
                        stacked : true
                    }],
                    yAxes : [{
                        stacked : true,
                        ticks: {
                            beginAtZero:true
                        }
                    }]
                },
                plugins: {
                    labels:false
                },
                animation: {
                    onComplete: function(animation){
                        document.querySelector('#downdatacalltimeline').setAttribute('href', this.toBase64Image());
                    }
                }
            }
        };
        //By default all labels bold and font size bigger
        Chart.defaults.global.defaultFontStyle = 'bold';

        var timeline_chart = new Chart(ctxdataTimelineChart, configdataTimelineChart);


        var  ctxdataTimelineChart = $("#iedeaChartCommunications");
        var configdataTimelineChart = {
            type: 'bar',
            data: {
                labels: communications_years,
                datasets: [
                    {
                        label: 'No Access',
                        data: communications_noaccess,
                        backgroundColor: '#ff8080',
                        borderWidth: 0
                    },
                    {
                        label: 'View-only access',
                        data: communications_viewonly,
                        backgroundColor: '#bfbfbf',
                        borderWidth: 0
                    },
                    {
                        label: 'Submit comments for the region',
                        data: communications_submitccomments,
                        backgroundColor: '#337ab7',
                        borderWidth: 0
                    },
                    {
                        label: 'Vote for the region',
                        data: communications_voteregion,
                        backgroundColor: '#5cb85c',
                        borderWidth: 0
                    }
                ]
            },
            options: {
                legend: {
                    display: true,
                    onHover: function(event, legendItem) {
                        document.getElementById("iedeaChartCommunications").style.cursor = 'pointer';
                    },
                    onClick: function(e, legendItem) {
                        var index = legendItem.datasetIndex;
                        var ci = this.chart;
                        var alreadyHidden = (ci.getDatasetMeta(index).hidden === null) ? false : ci.getDatasetMeta(index).hidden;

                        ci.data.datasets.forEach(function(e, i) {
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
                    custom: function(tooltip) {
                        if (!tooltip.opacity) {
                            document.getElementById("iedeaChartCommunications").style.cursor = 'default';
                            return;
                        }
                    },
                    mode:'index',
                    intersect: false
                },
                responsive:true,
                scales : {
                    xAxes : [{
                        stacked : true

                    }],
                    yAxes : [{
                        stacked : true,
                        ticks: {
                            stepSize: 10,
                            beginAtZero:true,
                        }
                    }]
                },
                plugins: {
                    labels: false
                },
                animation: {
                    onComplete: function(animation){
                        document.querySelector('#downcommunications').setAttribute('href', this.toBase64Image());
                    }
                }
            }
        };
        var communication_chart = new Chart(ctxdataTimelineChart, configdataTimelineChart);

    });
</script>
<div class="container">
    <div class="backTo">
        <a href="<?=$module->getUrl('index.php').'&NOAUTH&'.$pidsArray['PROJECTS'].'&option=dat'?>">< Back to Data</a>
    </div>
</div>
<div class="container">
    <h3>General Hub Stats</h3>
    <p class="hub-title"><?=$settings['hub_statistics_gen_text']?></p>
    <p class="hub-title"><i class="fa fa-refresh" aria-hidden="true"></i> Last update on <span style="font-weight: bold"><?=$metrics['date']?></span></p>
</div>

<div class="container" style="padding-top: 60px">
    <h4>General Hub Usage Stats</h4>
    <p class="hub-title"><?=$settings['hub_stats_general_usage']?></p>
</div>
<div class="container">
    <?php foreach ($sections_donuts as $index=>$section) {
        if ($index <= 2) {
            ?>
            <div class="canvas_title"><?= $section ?>
                <a href="#" download="<?= $sections_donuts_title[$index] . ".png" ?>" class="fa fa-download"
                   style="color:#8c8c8c;padding-left:10px;" id="<?= "down" . $section ?>"
                   name="<?= "down" . $section ?>"></a>
            </div>
        <?php }
    }?>
</div>
<div class="container">
    <?php foreach ($array_sections as $section){
        $id = $section."Chart";
        ?>
        <canvas id="<?=$id?>" class="canvas_statistics" width="360px" height="360px"></canvas>
    <?php }?>
</div>


<div class="container" style="padding-top: 60px">
    <h4>Statistics data</h4>
    <p class="hub-title"><?=$settings['hub_stats_data']?></p>
</div>
<div class="container" style="padding-bottom: 40px">
    <div class="col-sm-4">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    Votes <span class="badge badge-primary" style="float: right"><?=$metrics['votes']?></span>
                </h3>
            </div>
            <div class="stat-table-outer" aria-expanded="true">
                <div>
                    <span><i class="fa fa-hashtag color-primary"></i> of times "vote later" was used</span>
                    <span class="stat-table-value"><?=$metrics['vote_later'];?></span>
                </div>
                <div>
                    <span><i class="fa fa-hashtag color-primary"></i> of revisions uploaded</span>
                    <span class="stat-table-value"><?=$metrics['revisions'];?></span>
                </div>
                <div>
                    <span><i class="fa fa-hashtag color-primary"></i> of votes completed before due date</span>
                    <span class="stat-table-value"><?=$metrics['votes_c'];?></span>
                </div>
                <div>
                    <span><i class="fa fa-percent color-primary"></i> of votes completed before due date</span>
                    <span class="stat-table-value"><?=$metrics['votes_c_percentage'];?></span>
                </div>
                <div>
                    <span><i class="fa fa-hashtag color-primary"></i> of votes completed after due date</span>
                    <span class="stat-table-value"><?=$metrics['votes_late'];?></span>
                </div>
                <div>
                    <span><i class="fa fa-percent color-primary"></i> of votes completed after due date</span>
                    <span class="stat-table-value"><?=$metrics['votes_late_percentage'];?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    Requests <span class="badge badge-primary" style="float: right"><?=$metrics['requests']?></span>
                </h3>
            </div>
            <div class="stat-table-outer" aria-expanded="true">
                <div>
                    <span><i class="fa fa-hashtag color-primary"></i> requests currently approved by admin</span>
                    <span class="stat-table-value"><?=$metrics['requests_a'];?></span>
                </div>
                <div>
                    <span><i class="fa fa-hashtag color-primary"></i> of requests currently rejected by admin</span>
                    <span class="stat-table-value"><?=$metrics['requests_r'];?></span>
                </div>
                <div>
                    <span><i class="fa fa-hashtag color-primary"></i> of requests currently deactivated by admin</span>
                    <span class="stat-table-value"><?=$metrics['requests_d'];?></span>
                </div>
                <div>
                    <span><i class="fa fa-hashtag color-primary"></i> of requests completed (overall)</span>
                    <span class="stat-table-value"><?=$metrics['requests_c'];?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    Publications <span class="badge badge-primary" style="float: right"><?=$metrics['publications']?></span>
                </h3>
            </div>
            <div class="stat-table-outer" aria-expanded="true">
                <div>
                    <span><i class="fa fa-hashtag color-primary"></i> of publications so far in current year</span>
                    <span class="stat-table-value"><?=$metrics['publications_current'];?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-4">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    Abstracts <span class="badge badge-primary" style="float: right"><?=$metrics['abstracts']?></span>
                </h3>
            </div>
            <div class="stat-table-outer" aria-expanded="true">
                <div>
                    <span><i class="fa fa-hashtag color-primary"></i> of abstracts so far in current year</span>
                    <span class="stat-table-value"><?=$metrics['abstracts_current'];?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    Comments <span class="badge badge-primary" style="float: right"><?=$metrics['comments']?></span>
                </h3>
            </div>
            <div class="stat-table-outer" aria-expanded="true">
                <div>
                    <span><i class="fa fa-hashtag color-primary"></i> of comments by PI-level users</span>
                    <span class="stat-table-value"><?=$metrics['comments_pi'];?></span>
                </div>
                <div>
                    <span><i class="fa fa-hashtag color-primary"></i> of comments by non-PI-level users</span>
                    <span class="stat-table-value"><?=$metrics['comments_n'];?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    Concepts <span class="badge badge-primary" style="float: right"><?=$metrics['concepts']?></span>
                </h3>
            </div>
            <div class="stat-table-outer" aria-expanded="true">
                <div>
                    <span><i class="fa fa-hashtag color-primary"></i> of active concepts</span>
                    <span class="stat-table-value"><?=$metrics['concepts_a']?></span>
                </div>
                <div>
                    <span><i class="fa fa-hashtag color-primary"></i> of completed concepts</span>
                    <span class="stat-table-value"><?=$metrics['concepts_c']?></span>
                </div>
                <div>
                    <span><i class="fa fa-hashtag color-primary"></i> of discontinued concepts</span>
                    <span class="stat-table-value"><?=$metrics['concepts_d']?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-4">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    Users <span class="badge badge-primary" style="float: right"><?=$metrics['users']?></span>
                </h3>
            </div>
            <div class="stat-table-outer" aria-expanded="true">
                <div>
                    <span><i class="fa fa-hashtag color-primary"></i> of PI-level users</span>
                    <span class="stat-table-value"><?=$metrics['users_pi'];?></span>
                </div>
                <div>
                    <span><i class="fa fa-hashtag color-primary"></i> of users with Access Link in last 30 days</span>
                    <span class="stat-table-value"><?=$metrics['users_access'];?></span>
                </div>
                <div>
                    <span><i class="fa fa-hashtag color-primary"></i> of Harmonist Admins</span>
                    <span class="stat-table-value"><?=$metrics['admins'];?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container" style="padding-top: 10px">
    <h4>Data Exchange</h4>
    <p class="hub-title"><?=$settings['hub_stats_data_exchange']?></p>
</div>
<div class="container">
    <?php foreach ($sections_donuts as $index=>$section) {
        if ($index >= 3) {
            ?>
            <div class="canvas_title"><?= $section ?>
                <a href="#" download="<?= $sections_donuts_title[$index] . ".png" ?>" class="fa fa-download"
                   style="color:#8c8c8c;padding-left:10px;" id="<?= "down" . $section ?>"
                   name="<?= "down" . $section ?>"></a>
            </div>
        <?php }
    }?>
</div>
<div class="container">
    <?php foreach ($array_sections_data as $section){
        $id = $section."Chart";
        ?>
        <canvas id="<?=$id?>" class="canvas_statistics" width="360px" height="360px"></canvas>
    <?php }?>
</div>
<div class="container" style="padding-top: 80px">
    <h4>Statistics data</h4>
    <p class="hub-title"><?=$settings['hub_stats_data_requests']?></p>
</div>
<div class="container" style="padding-bottom: 40px">
    <div class="col-sm-4">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    Data Requests <span class="badge badge-primary" style="float: right"><?=$dataCall_total?></span>
                </h3>
            </div>
            <div class="stat-table-outer" aria-expanded="true">
                <div>
                    <span><i class="fa fa-hashtag color-primary"></i> private</span>
                    <span class="stat-table-value"><?=$dataReq_values['0'];?></span>
                </div>
                <div>
                    <span><i class="fa fa-hashtag color-primary"></i> public draft</span>
                    <span class="stat-table-value"><?=$dataReq_values['1'];?></span>
                </div>
                <div>
                    <span><i class="fa fa-hashtag color-primary"></i> public final</span>
                    <span class="stat-table-value"><?=$dataReq_values['2'];?></span>
                </div>
                <div>
                    <span><i class="fa fa-hashtag color-primary"></i> data call closed</span>
                    <span class="stat-table-value"><?=$dataCall_closed;?></span>
                </div>
                <div>
                    <span>avg <i class="fa fa-hashtag color-primary"></i> uploads per request</span>
                    <span class="stat-table-value"><?=$dataCall_avg;?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    File Activity <span class="badge badge-primary" style="float: right"><?=$fileActivity_total?></span>
                </h3>
            </div>
            <div class="stat-table-outer" aria-expanded="true">
                <div>
                    <span><i class="fa fa-hashtag color-primary"></i> uploads</span>
                    <span class="stat-table-value"><?=$fileActivity_values['0'];?></span>
                </div>
                <div>
                    <span><i class="fa fa-hashtag color-primary"></i> downloads</span>
                    <span class="stat-table-value"><?=$fileActivity_values['1'];?></span>
                </div>
                <div>
                    <span><i class="fa fa-hashtag color-primary"></i> manual deletes</span>
                    <span class="stat-table-value"><?=$fileActivity_values['2'];?></span>
                </div>
                <div>
                    <span><i class="fa fa-hashtag color-primary"></i> automatic deletes</span>
                    <span class="stat-table-value"><?=$number_deletes_auto?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    Other Stats
                </h3>
            </div>
            <div class="stat-table-outer" aria-expanded="true">
                <div>
                    <span><i class="fa fa-hashtag color-primary"></i> data downloaders</span>
                    <span class="stat-table-value"><?=$data_downloaders;?></span>
                </div>
                <div>
                    <span><i class="fa fa-hashtag color-primary"></i> datasets uploaded by due date</span>
                    <span class="stat-table-value"><?=$datasets_up_due;?></span>
                </div>
                <div>
                    <span><i class="fa fa-percent color-primary"></i> datasets uploaded by due date</span>
                    <span class="stat-table-value"><?=round((($datasets_up_due/$dataCall_total)*100),2);?></span>
                </div>
                <div>
                    <span><i class="fa fa-hashtag color-primary"></i> datasets uploaded after due date</span>
                    <span class="stat-table-value"><?=$datasets_up_afterdue;?></span>
                </div>
                <div>
                    <span><i class="fa fa-percent color-primary"></i> datasets uploaded after due date</span>
                    <span class="stat-table-value"><?=round((($datasets_up_afterdue/$dataCall_total)*100),2)?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container" style="padding-top: 60px">
    <h4>
        Data Call Timeline
        <a href="#" download="statistics_data.png" class="fa fa-download" style="color:#8c8c8c;padding-left:10px;" id="downdatacalltimeline" name="downdatacalltimeline"></a>
    </h4>
    <p class="hub-title"><?=$settings['hub_stats_datacall_time']?></p>
</div>
<div class="container">
    <canvas id="dataTimelineChart" class="canvas_statistics" width="1100px" height="310px"></canvas>
</div>

<div class="container" style="padding-top: 60px">
    <h4>
        <?=$settings['hub_name']?> Hub Communications
        <a href="#" download="communications.png" class="fa fa-download" style="color:#8c8c8c;padding-left:10px;" id="downcommunications" name="downcommunications"></a>
    </h4>
    <p class="hub-title"><?=$settings['hub_stats_comunications']?></p>
</div>
<div class="container">
    <canvas id="iedeaChartCommunications" class="canvas_statistics" width="1100px" height="310px"></canvas>
</div>
<div style="padding-bottom: 100px"></div>