<?php
namespace Vanderbilt\HarmonistHubExternalModule;

$RecordSetHome = \REDCap::getData($pidsArray['HOME'], 'array', null);
$homepage = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetHome,$pidsArray['HOME'])[0];
$homepage_links_sectionorder = $module->getChoiceLabels('links_sectionicon', $pidsArray['HOME']);
$expire_date = date('Y-m-d', strtotime(date('Y-m-d') ."-".$settings['recentdataactivity_dur']." days"));

$comments_sevenDaysYoung = \REDCap::getData($pidsArray['SOPCOMMENTS'], 'json-array', null,null,null,null,false,false,false,"datediff ([responsecomplete_ts], '".$expire_date."', \"d\", true) <= 0");
ArrayFunctions::array_sort_by_column($comments_sevenDaysYoung, 'responsecomplete_ts',SORT_DESC);

$dataUpload_sevenDaysYoung = \REDCap::getData($pidsArray['DATAUPLOAD'], 'json-array', null,null,null,null,false,false,false,"datediff ([responsecomplete_ts], '".$expire_date."', \"d\", true) <= 0");
ArrayFunctions::array_sort_by_column($dataUpload_sevenDaysYoung, 'responsecomplete_ts',SORT_DESC);

$dataDownload_sevenDaysYoung = \REDCap::getData($pidsArray['DATADOWNLOAD'], 'json-array', null,null,null,null,false,false,false,"datediff ([responsecomplete_ts], '".$expire_date."', \"d\", true) <= 0");
ArrayFunctions::array_sort_by_column($dataDownload_sevenDaysYoung, 'responsecomplete_ts',SORT_DESC);

$all_data_recent_activity = array_merge($comments_sevenDaysYoung, $dataUpload_sevenDaysYoung);
$all_data_recent_activity = array_merge($all_data_recent_activity, $dataDownload_sevenDaysYoung);

ArrayFunctions::array_sort_by_column($all_data_recent_activity, 'responsecomplete_ts',SORT_DESC);
$number_of_recentactivity = $settings['number_recentdataactivity'];

$number_uploads = count(\REDCap::getData($pidsArray['DATAUPLOAD'], 'json-array', null, array('record_id')));
$number_downloads = count(\REDCap::getData($pidsArray['DATADOWNLOAD'], 'json-array', null, array('record_id')));

$TBLCenter = \REDCap::getData($pidsArray['TBLCENTERREVISED'], 'json-array', null);

$region_tbl_percent = getTBLCenterUpdatePercentRegions($TBLCenter, $person_region['region_code'], $settings['pastlastreview_dur']);

$news_type = $module->getChoiceLabels('news_type', $pidsArray['NEWITEMS']);
$newItems = \REDCap::getData($pidsArray['NEWITEMS'], 'json-array', null,null,null,null,false,false,false,"[news_category] = '1'");
ArrayFunctions::array_sort_by_column($newItems, 'news_d',SORT_DESC);
$news_icon_color = array('fa-newspaper-o'=>'#ffbf80',	'fa-bullhorn'=>'#ccc','fa-calendar-o'=>'#ff8080','fa-bell-o'=>'#dff028',
    'fa-list-ol'=>'#b3d9ff','fa-file-o'=>'#a3a3c2','fa-trophy'=>'#9999ff','fa-exclamation-triangle'=>'#a3c2c2');
$exploreDataToken = json_encode("&code=".getCrypt($current_user['record_id'],'e',$secret_key,$secret_iv));
?>
<div class="optionSelectData">
    <h3>Data Hub</h3>
    <p class="hub-title"><?=filter_tags($settings['hub_data_hub_text'])?></p>
</div>

<div class="row">
    <div class="col-sm-3">
        <div class="well centerwell data_boxes" >
            <i class="fa fa-2x fa-fw fa-map" aria-hidden="true"></i>
            <div class="welltitle"><strong>Explore</strong> the different types of <?=$settings['hub_name']?> data</div>
            <a onclick='javascript:exploreDataToken(<?=$exploreDataToken;?>,<?=json_encode($module->getUrl("sop/sop_explore_data_AJAX.php")."&NOAUTH");?>,<?=json_encode($module->getUrl('index.php'));?>)' class="btn btn-warning">Explore Data</a>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="well centerwell data_boxes">
            <i class="fa fa-2x fa-fw fa-bullhorn" aria-hidden="true"></i>
            <div class="welltitle"><strong>Request</strong> <?=$settings['hub_name']?> data for your approved concept</div>
            <a href="<?=$module->getUrl("index.php")."&NOAUTH&pid=".$pidsArray['PROJECTS']."&option=smn";?>" class="btn btn-primary">Create Data Request</a>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="well centerwell data_boxes">
            <i class="fa fa-2x fa-fw fa-cloud-upload" aria-hidden="true"></i>
            <div class="welltitle"><strong>Check and submit</strong> data for an active data call</div>
            <a href="<?=$module->getUrl("index.php")."&NOAUTH&option=upd";?>" class="btn btn-success">View Data Calls <span class="badge" style="padding: 2px 6px;"><?=fetchNumberOfOpenDataCalls($pidsArray['SOP'], $current_user['person_region']);?></span></a>
        </div>
    </div>
    <?php
    if($settings['deactivate_datadown___1'] != "1") {
        ?>
        <div class="col-sm-3">
            <div class="well centerwell data_boxes">
                <i class="fa fa-2x fa-fw fa-arrow-down" aria-hidden="true"></i>
                <div class="welltitle"><strong>Retrieve</strong> data uploaded for your project</div>
                <?php if ($current_user['allowgetdata_y___1'] != "1") { ?>
                    <a href="#" onclick="$('#modal-data-download-no-permissions').modal('show');" class="btn btn-info">Download
                        Data</a>
                <?php } else if ($current_user['redcap_name'] == '') { ?>
                    <a href="#" onclick="$('#modal-data-download-denied').modal('show');" class="btn btn-info">Download
                        Data</a>
                <?php } else { ?>
                    <a href="#" onclick="$('#modal-data-download-confirmation').modal('show');" class="btn btn-info">Download
                        Data</a>
                <?php } ?>
            </div>
        </div>
        <?php } ?>
</div>
<?php
if($settings['deactivate_datadown___1'] != "1"){
    $downloadUrl = preg_replace('/pid=(\d+)/', "pid=".$pidsArray['DATADOWNLOADUSERS'],$module->getUrl('index.php').'&option=dnd');
    ?>
<div class="hidden-xs" style="margin-bottom: 20px;"></div>
<div class="modal fade" id="modal-data-download-confirmation" tabindex="-1" role="dialog" aria-labelledby="Codes">
    <form class="form-horizontal" action="<?=$downloadUrl?>" method="post" id='dataDownloadForm'>
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Download Data</h4>
                </div>
                <div class="modal-body">
                    <span>Are you sure you want to download data?</span>
                    <br>
                    <span style="color:red;">You will need to log in to Vanderbilt REDCap.</span>
                </div>
                <input type="hidden" id="assoc_concept" name="assoc_concept">
                <input type="hidden" id="user" name="user">
                <div class="modal-footer">
                    <a type="submit" onclick="document.getElementById('dataDownloadForm').submit();" class="btn btn-default btn-success" id='btnModalRescheduleForm'>Continue</a>
                    <a href="#" class="btn btn-default btn-cancel" data-dismiss="modal">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>
<div class="modal fade" id="modal-data-download-denied" tabindex="-1" role="dialog" aria-labelledby="Codes">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Download Data</h4>
            </div>
            <div class="modal-body">
                <span>Your account is not yet associated with a REDCap Downloader account. If you are expecting to download datasets for an approved concept, please contact <a href="mailto:<?=$settings['hub_contact_email']?>"><?=$settings['hub_contact_email']?></a></span>

            </div>
            <input type="hidden" id="assoc_concept" name="assoc_concept">
            <input type="hidden" id="user" name="user">
            <div class="modal-footer">
                <a class="btn btn-default btn-cancel" data-dismiss="modal">Close</a>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="modal-data-download-no-permissions" tabindex="-1" role="dialog" aria-labelledby="Codes">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Download Data</h4>
            </div>
            <div class="modal-body">
                <span>You do not have permission to access <?=$settings['hub_name']?> data downloads.<br>For inquiries, contact <a href="mailto:<?=$settings['hub_contact_email']?>"><?=$settings['hub_contact_email']?></a></span>
            </div>
            <input type="hidden" id="assoc_concept" name="assoc_concept">
            <input type="hidden" id="user" name="user">
            <div class="modal-footer">
                <a class="btn btn-default btn-cancel" data-dismiss="modal">Close</a>
            </div>
        </div>
    </div>
</div>
<?php } ?>
<script>
    $(document).ready(function() {
        var showChar = 110;
        var ellipsestext = "...";
        var moretext = "more";
        var lesstext = "less";
        $('.more').each(function() {
            var content = $(this).html();

            if(content.length > showChar) {

                var snippetContent = content.substr(0, showChar-1);
                var allContent = content.substr(showChar-1, content.length - showChar);

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
</script>
<div class="row">
    <div class="col-sm-9">
        <div class="panel panel-default">
            <div class="panel-heading" style="border: none;">
                <h3 class="panel-title">
                    Data News
                    <a href="<?=$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=dna&type=1'?>" style="float: right;padding-right: 10px;color: #337ab7">View more</a>
                </h3>
            </div>
            <div id="collapse3" class="table-responsive panel-collapse collapse in" aria-expanded="true">
                <table class="table table_requests sortable-theme-bootstrap" data-sortable id="deadlinesAndEvents">
                    <?php
                    if(!empty($newItems)) {
                        $i=1;
                        $newItems = $module->escape($newItems);
                        foreach($newItems as $event){
                            if($i <= 5) {
                                echo "<tr>";
                                echo "<td style='width: 755px;' class='media'>" .
                                    "<span class='label news-label' style='background-color:".$news_icon_color[$event['news_type']].";' title='".$news_type[$event['news_type']]."'><i class='fa ".$event['news_type']."'></i></span>".
                                    "<div style='float:left;padding-left: 10px;width:95%'>".
                                    "<span>" . getPeopleName($pidsArray['PEOPLE'], $event['news_person'], 'email') . " on " . $event['news_d'] ."</span>".
                                    "<div><strong>".$event['news_title']. "</strong></div>".
                                    "</div>";
                                echo "<div class='comment more' style='display: inline-block;'>".filter_tags($event['news'])." ";
                                if($event['news_file'] != "" && $event['news_file2'] == ""){
                                    echo "<div style='padding-top: 10px;padding-bottom: 10px'>".getFileLink($module, $pidsArray['PROJECTS'], $event['news_file'],'','',$secret_key,$secret_iv,$current_user['record_id'],"")."</div> ";
                                }else if($event['news_file'] != "" && $event['news_file2'] != ""){
                                    echo "<div style='padding-top: 10px;'>".getFileLink($module, $pidsArray['PROJECTS'], $event['news_file'],'','',$secret_key,$secret_iv,$current_user['record_id'],"")."</div> ";
                                    echo  "<div style='padding-bottom: 10px'>".getFileLink($module, $pidsArray['PROJECTS'], $event['news_file2'],'','',$secret_key,$secret_iv,$current_user['record_id'],"")."</div> ";
                                }
                                echo "</div>";

                                echo "</td>" .
                                    "</tr>";
                            }
                            $i++;
                        }
                    }else{?>
                        <tbody>
                        <tr>
                            <td style="padding-left: 15px;"><span><em>No data news to display</em></span></td>
                        </tr>
                        </tbody>
                    <?php }?>
                </table>
            </div>
        </div>

        <div class="panel panel-default panel-table">
            <div class="panel-heading">
                <h3 class="panel-title">
                    Recent Data Activity
                    <a href="<?=$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=sra'?>" style="float: right;padding-right: 10px;color: #337ab7">View more</a>
                </h3>
            </div>
            <ul class="list-group">
                <?php
                if(!empty($all_data_recent_activity)) {
                    $i = 0;
                    foreach ($all_data_recent_activity as $recent_activity) {
                        if ($i < $number_of_recentactivity) {
                            $time = getDateForHumans($recent_activity['responsecomplete_ts']);
                            if($recent_activity['comments'] != '') {
                                echo '<li class="list-group-item">';

                                $people = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array('record_id' => $recent_activity['response_person']),array('firstname','lastname'))[0];
                                $name = htmlspecialchars(trim($people['firstname'] . ' ' . $people['lastname']),ENT_QUOTES);

                                $RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', array('record_id' => $recent_activity['sop_id']));
                                $sop = $module->escape(ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP,$pidsArray['SOP'])[0]);
                                $sop_concept_id = $sop['sop_concept_id'];
                                $sop_name = $sop['sop_name'];
                                $assoc_concept = getReqAssocConceptLink($module, $pidsArray, $sop_concept_id, "");

                                $title = substr($sop_name, 0, 50) . '...';

                                if ($recent_activity['author_revision_y'] == '1') {
                                    echo '<i class="fa fa-fw fa-file-text-o text-success" aria-hidden="true"></i>' .
                                        '<span class="time"> ' . $time . '</span> ' .
                                        '<strong>' . $name . '</strong> submitted a <strong>revision</strong> for ' . $assoc_concept.', <a href="'.$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=sop&record=' . $recent_activity['sop_id'] . '" target="_blank">'.$title . '</a>';
                                } else{
                                    $text = '<span class="time"> ' . $time . '</span> <strong>' . $name . '</strong> submited a ';
                                    $itemcount = 0;
                                    if ($recent_activity['comments'] != '') {
                                        $icon = '<i class="fa fa-fw fa-comment-o text-info" aria-hidden="true"></i>';
                                    }
                                    if ($recent_activity['pi_vote'] != '') {
                                        $icon = '<i class="fa fa-fw fa-check text-info" aria-hidden="true"></i>';
                                    }

                                    if($recent_activity['comments'] != '' && $recent_activity['revised_file'] != ''){
                                        $text .= '<strong>comment and file</strong>';
                                    }else if($recent_activity['comments'] != ''){
                                        $text .= '<strong>comment</strong>';
                                    }else if($recent_activity['revised_file'] != ''){
                                        $text .= '<strong>file</strong>';
                                    }

                                    echo $icon.$text.' for ' . $assoc_concept.', <a href="'.$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=sop&record=' . $recent_activity['sop_id'] . '" target="_blank">'.$title . '</a>';
                                }
                                echo '</li>';
                                $i++;
                            }else if($recent_activity['download_id'] != ""){
                                echo '<li class="list-group-item">';

                                $people = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array('record_id' => $recent_activity['downloader_id']),array('firstname','lastname'))[0];
                                $name = htmlspecialchars(trim($people['firstname'] . ' ' . $people['lastname']));

                                $data_upload_region = \REDCap::getData($pidsArray['DATAUPLOAD'], 'json-array', array('record_id' => $recent_activity['download_id']),array('data_upload_region'))[0]['data_upload_region'];
                                $region_code = \REDCap::getData($pidsArray['REGIONS'], 'json-array', array('record_id' => $data_upload_region),array('region_code'))[0]['region_code'];

                                $assoc_concept = getReqAssocConceptLink($module, $pidsArray, $recent_activity['downloader_assoc_concept'], "");

                                $icon = '<i class="fa fa-fw fa-arrow-down text-info" aria-hidden="true"></i>';

                                echo filter_tags($icon.' <span class="time"> ' . $time . '</span><strong>'.$name.'</strong> downloaded '.$region_code.' data for '.$assoc_concept.'.');
                                echo '</li>';
                                $i++;
                            }else if($recent_activity['data_assoc_request'] != "") {
                                echo '<li class="list-group-item">';

                                $people = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array('record_id' => $recent_activity['data_upload_person']),array('firstname','lastname'))[0];
                                $name = htmlspecialchars(trim($people['firstname'] . ' ' . $people['lastname']),ENT_QUOTES);

                                $region_code = \REDCap::getData($pidsArray['REGIONS'], 'json-array', array('record_id' => $recent_activity['data_upload_region']),array('region_code'))[0]['region_code'];

                                $assoc_concept = getReqAssocConceptLink($module, $pidsArray, $recent_activity['data_assoc_concept'], "");

                                $icon = '<i class="fa fa-fw fa-arrow-up text-info" aria-hidden="true"></i>';

                                echo filter_tags($icon.' <span class="time"> ' . $time . '</span><strong>'.$name.'</strong> uploaded '.$region_code.' data for '.$assoc_concept.'.');
                                echo '</li>';
                                $i++;
                            }
                        }else {
                            break;
                        }
                    }
                }else{?>
                    <li class="list-group-item"><em>No recent data activity in last 7 days.</em></li>
                <?php }?>
            </ul>
        </div>
        <div style="margin-bottom: 140px;" class="hidden-xs"></div>
    </div>
    <div class="col-sm-3">
        <div class="list-group">
            <a href="<?=$module->getUrl('index.php').'&NOAUTH&option=lgd'?>" style="cursor:pointer" class="list-group-item list-group-item-action flex-column align-items-start">
                <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-1">
                        <span style="float:left;font-weight: bold">Data Log</span>
                        <span class="badge label-default dataRequests" style="float: right;"><i class="fa fa-arrow-down"></i> <?=$number_downloads;?></span>
                        <span class="badge label-default dataRequests" style="float: right;margin-right: 3px;"><i class="fa fa-arrow-up"></i> <?=$number_uploads;?></span>
                    </h5>

                </div>
                <div style="display: block;padding-top: 25px;">
                    <p class="mb-1">Track uploads and downloads of <?=$settings['hub_name']?> patient-level datasets.</p>
                </div>
            </a>
            <?php if(!$deactivate_toolkit){?>
            <a href="https://iedeadata.org/iedea-harmonist" target="_blank" class="list-group-item list-group-item-action flex-column align-items-start">
                <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-1">
                        <span style="float:left;font-weight: bold">Harmonist Data Toolkit</span>
                        <i class="fa fa-external-link" style="float: right;"></i>
                    </h5>

                </div>
                <div style="display: block;padding-top: 25px;">
                    <p class="mb-1">Go to the Toolkit webpage (without a data submission request).</p>
                </div>
            </a>
            <?php } ?>
            <a href="<?=APP_PATH_WEBROOT_FULL."external_modules/?prefix=data-model-browser&page=browser&NOAUTH=&pid=".$pidsArray['DES']?>" target="_blank" class="list-group-item list-group-item-action flex-column align-items-start">
                <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-1">
                        <span style="float:left;font-weight: bold">iedeades.org</span>
                        <i class="fa fa-external-link" style="float: right;"></i>
                    </h5>

                </div>
                <div style="display: block;padding-top: 25px;">
                    <p class="mb-1">Browse the <?=$settings['hub_name']?> Data Exchange Standard.</p>
                </div>
            </a>
            <?php if($settings['deactivate_tblcenter___1'] != "1"){?>
            <a href="<?=$module->getUrl('index.php').'&NOAUTH&option=tbl'?>" class="list-group-item list-group-item-action flex-column align-items-start">
                <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-1">
                        <span style="float:left;font-weight: bold">tblCENTER</span>
                        <?php
                        if($person_region['showregion_y'] == '1') {
                           echo getTBLCenterUpdatePercentLabel($region_tbl_percent);
                        }
                        ?>
                    </h5>

                </div>
                <div style="display: block;padding-top: 25px;">
                    <p class="mb-1">View and maintain the list of active <?=$settings['hub_name']?> sites.</p>
                </div>
            </a>
            <?php } ?>

            <?php if($settings['deactivate_datametrics___1'] != "1" || $isAdmin){?>
            <a href="<?=$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=mth'?>" class="list-group-item list-group-item-action flex-column align-items-start">
                <div style="display: inline-block;width: 50%;vertical-align:top;padding-right:5px">
                    <div style="font-weight:bold; padding-bottom:20px">
                        Hub Stats
                    </div>
                    <?=$settings['hub_name']?> file activity by category.
                </div>

                <div style="display: inline-block">
                    <canvas id="IedeaChart" class="canvas_statistics" width="100px" height="100px"></canvas>
                </div>
            </a>
            <?php } ?>
            <a href="<?=$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=ofs'?>" class="list-group-item list-group-item-action flex-column align-items-start">
                <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-1">
                        <span style="float:left;font-weight: bold">Document Library</span>
                    </h5>

                </div>
                <div style="display: block;padding-top: 25px;">
                    <p class="mb-1">Archive of extra files for <?=$settings['hub_name']?> projects, meetings, and governance.</p>
                </div>
            </a>
        </div>
    </div>
</div>

<?php

#FILE ACTIVITY
$number_uploads = count(\REDCap::getData($pidsArray['DATAUPLOAD'], 'json-array', null, array('record_id')));
$number_downloads = count(\REDCap::getData($pidsArray['DATADOWNLOAD'], 'json-array', null, array('record_id')));
$number_deletes = count(\REDCap::getData($pidsArray['DATADOWNLOAD'], 'json-array', null, array('record_id'),null,null,false,false,false,"[deleted_y] = '1' AND [deletion_type] = '2'"));
$number_deletes_auto = count(\REDCap::getData($pidsArray['DATADOWNLOAD'], 'json-array', array('record_id'),null,null,null,false,false,false,"[deleted_y] = '1' AND [deletion_type] = '1'"));

//GRAPH
$fileActivity_values = array(0 => $number_uploads,1 => $number_downloads,2 => $number_deletes);
$fileActivity_labels = array(0 => "Uploads",1 => "Downloads",2 => "Manual Delete");
$fileActivity_colors = array(0 => "#5cb85c",1 => "#337ab7",2 => "#eb6e60");

?>
<script>
    $(document).ready(function() {
        /*var iframeurl = <?=json_encode(APP_PATH_PLUGIN)?>;
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
            '#announcements-frame,#deadlines-frame'
        );*/

        Sortable.init();
        $('html,body').scrollTop(0);
        $("html,body").animate({ scrollTop: 0 }, "slow");

        var requests_values = <?=json_encode($fileActivity_values)?>;
        var requests_labels = <?=json_encode($fileActivity_labels)?>;
        var requests_colors = <?=json_encode($fileActivity_colors)?>;

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

