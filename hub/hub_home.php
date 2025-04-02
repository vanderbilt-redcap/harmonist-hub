<?php
namespace Vanderbilt\HarmonistHubExternalModule;

$RecordSetHome = \REDCap::getData($pidsArray['HOME'], 'array', null);
$homepage = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetHome,$pidsArray['HOME'])[0];
$homepage_links_sectionorder = $module->getChoiceLabels('links_sectionicon', $pidsArray['HOME']);

$requests = $hubData->getAllRequests();
ArrayFunctions::array_sort_by_column($requests, 'due_d');
$request_type = $module->getChoiceLabels('request_type', $pidsArray['RMANAGER']);

$instance = $current_user['person_region'];
$open_requests_values = array();
$home_metrics_values = array();
foreach ($requests as $req){
    //Only open requests
    if(!hideRequestForNonVoters($settings['pastrequest_dur'], $req, $person_region['voteregion_y']) && showOpenRequest($req,$instance)) {
        $open_requests_values[$req['request_type']] += 1;
        $home_metrics_values[$req['request_type']] += 1;
    }
}
$number_of_announcements = $settings['home_number_announcements'];
$number_of_deadlines = $settings['home_number_deadlines'];
$number_of_quicklinks = $settings['home_number_quicklinks'];
$number_of_recentactivity = $settings['home_number_recentactivity'];

$comments_sevenDaysYoung = \REDCap::getData($pidsArray['COMMENTSVOTES'], 'json-array', null);
ArrayFunctions::array_sort_by_column($comments_sevenDaysYoung, 'responsecomplete_ts',SORT_DESC);

$dealines = array();
for($i = 1; $i<$number_of_deadlines+1; $i++){
    if(!empty($homepage['deadline_text'.$i]) || !empty($homepage['deadline_date'.$i])){
        $array_dates = getNumberOfDaysLeftButtonHTML($homepage['deadline_date'.$i],'','float:right','0');
        $event['date'] = $homepage['deadline_date'.$i];
        $event['print'] = '<tr><td>'.$array_dates['text'].' '.$array_dates['button'].'</td><td>'.$homepage['deadline_text'.$i].'</td></tr>';
        array_push($dealines,$event);
    }
}
ArrayFunctions::array_sort_by_column($dealines, 'date');

/***GRAPH***/
$requests_values = array_count_values(array_column($requests, 'request_type'));
foreach ($request_type as $keyLabel => $requestsLabel){
    if(!array_key_exists($keyLabel, $home_metrics_values)){
        $requests_values[$keyLabel] = 0;
    }
}

//remove hidden options
$hidden_choices = $default_values->getHideChoice($pidsArray['RMANAGER']);
if(!empty($hidden_choices) && array_key_exists($pidsArray['RMANAGER'], $hidden_choices)){
    foreach ($hidden_choices['request_type'] as $value){
        if(array_key_exists($value,$request_type) ){
            unset($request_type[$value]);
        }
        if(array_key_exists($value,$requests_values) ){
            unset($requests_values[$value]);
        }
    }
}

ksort($requests_values);
$requests_values = array_values($requests_values);
ksort($request_type);
$requests_labels = array_values($request_type);

$requests_colors = array(0 => "#337ab7",1 => "#00b386",2 => "#f0ad4e",3 => "#ff9966",4 => "#5bc0de",5 => "#777",
                    6=>"#aa2600",7=>"#bf80ff",8=>"#006238",9=>"#6ddc9c", 10=>"#d1691f");
#If there are more options than colors, we repeat the colors from the beginning
if(count($requests_labels) > count($requests_colors)) {
    $count = 0;
    for ($i=count($requests_colors);$i<count($requests_labels)+1;$i++) {
        array_push($requests_colors,$requests_colors[$count]);
        $count++;
        if($count > 10){
            $count = 0;
        }
    }
}

if(array_key_exists('message', $_REQUEST)){
    if($_REQUEST['message'] == 'U') {
        echo '<div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;" id="succMsgContainer">The announcement has been successfully updated.</div>';
    }else if($_REQUEST['message'] == 'E') {
        echo '<div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;" id="succMsgContainer">Deadlines and Events has been successfully updated.</div>';
    }
}
?>

<div class="container">
    <h3>Home Page</h3>
    <p class="hub-title"></p>
</div>
<div style="margin-bottom: 70px;display:none" id="succMsgContainer">
    <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;">Deadlines and Events have been successfully updated.</div>
</div>
<div style="margin-bottom: 70px;display:none" id="succMsgContainer_an">
    <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;">Announcements have been successfully updated.</div>
</div>

<?php
$announcements = '';
if(!empty($homepage)) {
    for($i = 1; $i<$number_of_announcements+1; $i++){
        if(!empty($homepage['announce_text'.$i])){
            $announcements .= '<ul class="fa-ul">'.
                '<li><i class="fa-li fa fa-bell-o"></i>'.filter_tags($homepage['announce_text'.$i]);
            if($i == 1 && $isAdmin){
                $announcements .= '<a href="#" onclick="javascript:$(\'#announcements_survey\').modal(\'show\');" style="cursor: pointer"><span class="fa fa-cog" style="float: right;padding-right: 10px;"></span></a>';
            }
            $announcements .= '</li></ul>';
        }
    }
    if(!empty($announcements)){
        echo '<div class="alert alert-info">'.$announcements.'</div>';
    }else{
        echo '<div class="alert alert-info"><ul class="fa-ul"><li><i class="fa-li fa fa-bell-o"></i>No Announcements. ';
        if($isAdmin){
            echo '<a href="#" onclick="javascript:$(\'#announcements_survey\').modal(\'show\');" style="cursor: pointer;font-weight: bold">Create NEW.</a> <a href="#" onclick="javascript:$(\'#announcements_survey\').modal(\'show\');" style="cursor: pointer"><span class="fa fa-cog" style="float: right;padding-right: 10px;"></span></a>';
        }
        echo '</li></ul></div>';
    }
}
?>

<!-- MODAL EDIT ANNOUNCEMENTS-->
<div class="modal fade" id="announcements_survey" tabindex="-1" role="dialog" aria-labelledby="Codes">
    <div class="modal-dialog" role="document" style="width: 800px">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Announcements</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" value="0" id="announcement_loaded">
                <?php
                $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['HOME'], 1, "announcements", "");
                $survey_link = APP_PATH_WEBROOT_FULL . "/surveys/?s=".$module->escape($passthru_link['hash'])."&modal=modal";
                ?>
                <iframe class="commentsform" id="announcements-frame" name="announcements-frame" message="U" src="<?=$survey_link?>" style="border: none;height: 810px;width: 100%;" message="F"></iframe>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-sm-9">
        <div class="row">
            <div class="col-sm-4">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <a href="<?=$module->getUrl("index.php")."&NOAUTH&pid=".$pidsArray['PROJECTS']."&option=hub"?>" title="open requests" class="home_openrequests_link"><span class="badge label-default" style="float: right;"><?=$numberOfOpenRequest?></span></a>
                            Open Requests</h3>

                    </div>
                   <ul class="list-group">
                       <?php
                       $i=0;
                       foreach ($request_type as $value => $label){
                           $open_req_value = ($open_requests_values[$value] == 0)?"":$open_requests_values[$value];
                           if($default_values->getHideChoice($pidsArray['RMANAGER'])[$pidsArray['RMANAGER']]['request_type'] == "" || $default_values->getHideChoice($pidsArray['RMANAGER'])[$pidsArray['RMANAGER']]['request_type'] != "" && !in_array($value,$default_values->getHideChoice($pidsArray['RMANAGER'])[$pidsArray['RMANAGER']]['request_type'])){

                               #GRADIENT for the Badge
                               $total_colors = count($requests_values) - count(empty($default_values->getHideChoice($pidsArray['RMANAGER'])[$pidsArray['RMANAGER']]['request_type']) ? $request_type:$default_values->getHideChoice($pidsArray['RMANAGER'])[$pidsArray['RMANAGER']]['request_type']);
                               $color = getGradientColor("777777","003D99",$total_colors,$i);
                               echo '<li class="list-group-item">
                                        <a href="'.$module->getUrl("index.php")."&NOAUTH&pid=".$pidsArray['PROJECTS']."&option=hub&type=".$value.'" title="'.$label.'" class="home_openrequests_link">
                                        <span class="badge" style="background-color:'.$color.'">'.$open_req_value.'</span>
                                        </a>
                                        '.$label.'
                                    </li>';
                               $i++;
                           }
                       }
                       ?>
                    </ul>
                </div>
            </div>

            <div class="col-sm-8">
                <div class="panel panel-default" >
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            Deadlines and Events
                            <?php
                            if($isAdmin || ($current_user['harmonist_perms___7'] == 1) ? true : false){ ?>
                            <a href="#" onclick="javascript:$('#deadlines_survey').modal('show');" style="cursor: pointer"><span class="fa fa-cog" style="float: right;padding-right: 10px;"></span></a>
                            <?php } ?>
                        </h3>
                    </div>
                    <div id="collapse3" class="table-responsive panel-collapse collapse in" aria-expanded="true">
                        <table class="table table_requests sortable-theme-bootstrap" data-sortable id="deadlinesAndEvents">
                            <?php
                            if(!empty($homepage)) {
                                echo '<thead>'.'
                                    <tr>'.'
                                        <th class="sorted_class" data-sorted-direction="descending" data-sorted="true" style="min-width: 180px;">Date</th>'.'
                                        <th class="sorted_class" data-sorted-direction="descending" style="">Text</th>'.'
                                    </tr>'.'
                                    </thead>';
                                foreach($dealines as $event){
                                    echo $event['print'];
                                }
                            }else{?>
                                <tbody>
                                <tr>
                                    <td><span><em>No deadlines available</em></span></td>
                                </tr>
                                </tbody>
                            <?php }?>
                        </table>
                    </div>
                </div>
            </div>
            <!-- MODAL EDIT DEADLINES-->
            <div class="modal fade" id="deadlines_survey" tabindex="-1" role="dialog" aria-labelledby="Codes">
                <div class="modal-dialog" role="document" style="width: 800px">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title">Deadlines and Events</h4>
                        </div>
                        <div class="modal-body">
                            <?php
                            $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['HOME'], 1, "deadlines", "");
                            $survey_link = APP_PATH_WEBROOT_FULL . "/surveys/?s=".$module->escape($passthru_link['hash'])."&modal=modal";
                            ?>
                            <iframe class="commentsform" id="deadlines-frame" message="E" name="deadlines-frame" src="<?=$survey_link?>" style="border: none;height: 810px;width: 100%;"></iframe>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">My Requests
                    <a href="<?=$module->getUrl("index.php")."&NOAUTH&pid=".$pidsArray['PROJECTS']."&option=mra&type=h"?>" style="float: right;padding-right: 10px;color: #337ab7">View more</a>
                </h3>
            </div>
            <div class="table-responsive">
                <table class="table table_requests sortable-theme-bootstrap" data-sortable>
                    <?php
                    if(!empty($requests)) {
                        $commentDetails = $hubData->getCommentDetails();

                        $user_req_header = getRequestHeader($hubData, $settings['vote_grid'], '1','home');
                        $user_req_body = "";
                        $requests_counter = 0;
                        foreach ($requests as $req) {
                            $user_req_body .= getHomeRequestHTML($module, $hubData, $pidsArray, $req, $commentDetails, $request_type_label, 0, $settings['vote_visibility'], $settings['vote_grid'],$settings['pastrequest_dur'],'home');
                            if($user_req_body != ""){
                                $requests_counter++;
                            }
                        }
                        if($requests_counter > 0) {
                            echo $user_req_header . $user_req_body;
                        }else{?>
                            <tbody>
                            <tr>
                                <td><span style="padding-left:5px"><em>No active requests</em></span></td>
                            </tr>
                            </tbody>
                        <?php }
                    }else{?>
                        <tbody>
                        <tr>
                            <td><span style="padding-left:5px"><em>No active requests</em></span></td>
                        </tr>
                        </tbody>
                    <?php }?>
                </table>
            </div>
        </div>

        <div class="panel panel-default home-panel">
            <div class="panel-heading">
                <h3 class="panel-title">
                    Recent Activity
                    <a href="<?=$module->getUrl("index.php")."&NOAUTH&pid=".$pidsArray['PROJECTS']."&option=hra"?>" style="float: right;padding-right: 10px;color: #337ab7">View more</a>
                </h3>
            </div>
            <ul class="list-group">
                <?php
                if(!empty($comments_sevenDaysYoung)) {
                    $i = 0;
                    foreach ($comments_sevenDaysYoung as $comment) {
                        $seveDaysYoung = strtotime(date('Y-m-d', strtotime(date('Y-m-d') . "- 7 days")));
                        if(strtotime($comment['responsecomplete_ts']) >= $seveDaysYoung && ($comment['author_revision_y'] == '1' || $comment['pi_vote'] != '' || $comment['comments'] != '')) {
                            if ($i < $number_of_recentactivity) {
                                echo '<li class="list-group-item">';

                                $people = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array('record_id' => $comment['response_person']),array('firstname','lastname'))[0];
                                $name = trim($people['firstname'] . ' ' . $people['lastname']);

                                $requestComment = \REDCap::getData($pidsArray['RMANAGER'], 'json-array', array('request_id' => $comment['request_id']))[0];

                                $time = getDateForHumans($comment['responsecomplete_ts']);

                                $title = substr($requestComment['request_title'], 0, 50) . '...';

                                if ($comment['author_revision_y'] == '1') {
                                    echo '<i class="fa fa-fw fa-file-text-o text-success" aria-hidden="true"></i>' .
                                        '<span class="time"> ' . $time . '</span> ' .
                                        '<strong>' . $name . '</strong> submitted a <b>revision</b> for <a href="'.$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=hub&record=' . $requestComment['request_id'] . '" target="_blank">' . $title . '</a>';
                                } else{
                                    $text = '<span class="time"> ' . $time . '</span> <strong>' . $name . '</strong> submited a ';
                                    $itemcount = 0;
                                    if ($comment['comments'] != '') {
                                        $icon = '<i class="fa fa-fw fa-comment-o text-info" aria-hidden="true"></i>';
                                    }
                                    if ($comment['pi_vote'] != '') {
                                        $icon = '<i class="fa fa-fw fa-check text-info" aria-hidden="true"></i>';
                                    }

                                    if($comment['comments'] != '' && $comment['pi_vote'] != '' && $comment['revised_file'] != ''){
                                        $text .= '<strong>comment, vote and file</strong>';
                                    }else if($comment['comments'] != '' && $comment['pi_vote'] != ''){
                                        $text .= '<strong>comment and vote</strong>';
                                    }else if($comment['comments'] != '' && $comment['revised_file'] != ''){
                                        $text .= '<strong>comment and file</strong>';
                                    }else if($comment['pi_vote'] != '' && $comment['revised_file'] != ''){
                                        $text .= '<strong>vote and file</strong>';
                                    }else if($comment['comments'] != ''){
                                        $text .= '<strong>comment</strong>';
                                    }else if($comment['revised_file'] != ''){
                                        $text .= '<strong>file</strong>';
                                    }else if($comment['pi_vote'] != ''){
                                        $text .= '<strong>vote</strong>';
                                    }

                                    echo $icon.$text.' for <a href="'.$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=hub&record=' . $requestComment['request_id'].'" target="_blank">' . $title . '</a>';
                                }
                                echo '</li>';
                                $i++;
                            } else {
                                break;
                            }
                        }
                    }
                    if($i == 0){
                        ?><li class="list-group-item"><em>No activity in last 7 days.</em></li><?php
                    }
                }else{?>
                    <li class="list-group-item"><em>No activity in last 7 days.</em></li>
                <?php }?>
            </ul>
        </div>
    </div>
    <div class="col-sm-3">
        <?php if($settings['deactivate_datahub___1'] != "1"){ ?>
        <div class="panel panel-default">
            <div class="panel-heading" style="background-color: #5cb85c;color:#fff">
                <h3 class="panel-title">
                    Active Data Calls <span class="badge" style="padding: 2px 6px;background-color:#fff;color:#333;float: right"><?=fetchNumberOfOpenDataCalls($pidsArray['SOP'], $current_user['person_region']);?></span>
                </h3>
            </div>
            <div class="stat-table-outer" aria-expanded="true">
                <div style="padding-bottom: 10px;text-align: left;"><?=$settings['hub_active_shortcut']?></div>
                <div style="text-align: center;"><a href="<?=$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=upd'?>" class="btn btn-default">View Data Calls</a></div>
            </div>
        </div>
        <?php } ?>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title panelHeight">
                    <span class="col-sm-6" style="padding:0">Hub Metrics</span>
                    <?php if($settings['deactivate_metrics___1'] != "1" || $isAdmin){ ?>
                        <span class="col-sm-6" style="text-align:right;padding:0"><a href="<?=$module->getUrl("index.php")."&NOAUTH&pid=".$pidsArray['PROJECTS']."&option=mts"?>">View more</a></span>
                    <?php } ?>
                </h3>
            </div>
            <div class="stat-table-outer" aria-expanded="true">
                <div style="display: inline-block;width: 50%;vertical-align:top;padding-right:5px">
                    <div style="font-weight:bold; padding-bottom:20px">
                        Requests
                    </div>
                    <?php
                    if(empty($requests_values)){
                        ?> No data available.<?php
                    }else{
                        ?> All <?=$settings['hub_name']?> Hub requests by category.<?php
                    }
                    ?>

                </div>

                <div style="display: inline-block">
                    <canvas id="IedeaChart" class="canvas_statistics" width="100px" height="100px"></canvas>
                </div>
            </div>
        </div>

        <?php if($settings['calendar_active'][1] == "1"){?>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    Calendar
                    <span style="float: right"><a href="<?=$module->getUrl("index.php")."&NOAUTH&pid=".$pidsArray['PROJECTS']."&option=cal"?>">View more</a></span>
                </h3>
            </div>
            <div class="stat-table-outer" aria-expanded="true">
                <iframe src="<?=$settings['calendar_iframe']."&mode=AGENDA"?>" style="border-width:0" frameborder="0" scrolling="no" width="235px" height="280px"></iframe>
            </div>
        </div>
        <?php } ?>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <a data-toggle="collapse" href="#collapse_quicklinks">Quick Links</a>
                </h3>
            </div>
            <?php
            $collapseOrExpand = "collapse";
            if($settings['home_quicklinks_expand'] == '1'){
                $collapseOrExpand = "";
            }
            ?>
            <div id="collapse_quicklinks" class="panel-collapse <?=$collapseOrExpand?>" aria-expanded="true" style="margin-bottom: 0px;border:0px;">
                <ul class="list-group">
                    <?php
                    if(!empty($homepage)) {
                        $section_order = $homepage['links_sectionorder'];
                        if(is_array($section_order)) {
                            $section_order = array_flip($section_order);
                            ksort($section_order);
                        }else {
                            $section_order = $homepage['links_sectionhead'];
                        }
                        foreach ($section_order as $linkid => $order){
                            echo '<li class="list-group-item quicklink_header"><i class="fa fa-fw '.$module->escape($homepage_links_sectionorder[$homepage['links_sectionicon'][$order]]).'" aria-hidden="true"></i> '.$module->escape($homepage['links_sectionhead'][$order]).'</li>';

                            for($i = 1; $i<$number_of_quicklinks+1; $i++){
                                if(!empty($homepage['links_text'.$i][$order])){
                                    $stay = "target='_blank'";
                                    if($homepage['links_stay'.$i][$order][1] == '1'){
                                        $stay = "";
                                    }
                                    echo '<li class="list-group-item"><i class="fa fa-fw" aria-hidden="true"></i><a href="'.$module->escape($homepage['links_link'.$i][$order]).'" '.$module->escape($stay).'>'.$module->escape($homepage['links_text'.$i][$order]).'</a></li>';
                                }
                            }
                        }
                    }else{?>
                    <li class="list-group-item"><em>No quick links available</em></li>
                    <?php }?>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        Sortable.init();
        $('html,body').scrollTop(0);
        $("html,body").animate({ scrollTop: 0 }, "slow");

        var requests_values = <?=json_encode($requests_values)?>;
        var requests_labels = <?=json_encode($requests_labels)?>;
        var requests_colors = <?=json_encode($requests_colors)?>;

        //Show an empty donut
        if (requests_values.length === 0 || requests_values == "") {
            requests_values = [-1];
            requests_labels = ["No data"];
            requests_colors = ["#f1f1f1"];
        }

        var  ctx_iedea = $("#IedeaChart");
        var config_iedea = {
            type: 'doughnut',
            data: {
                labels: requests_labels,
                datasets: [{
                    backgroundColor: requests_colors,
                    data: requests_values
                }]
            },
            options: {
                responsive: false,
                legend: {
                    display: false
                },
                plugins: {
                    labels: [
                        {
                            render: 'value',
                            fontColor: '#fff',
                            fontSize:12
                        }
                    ]
                }
            }
        }


        var iedea_chart = new Chart(ctx_iedea, config_iedea);
    });
</script>