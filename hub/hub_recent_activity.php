<?php
namespace Vanderbilt\HarmonistHubExternalModule;

$RecordSetComments = \REDCap::getData($pidsArray['COMMENTSVOTES'], 'array', null);
$comments = ProjectData::getProjectInfoArray($RecordSetComments);
ArrayFunctions::array_sort_by_column($comments, 'responsecomplete_ts',SORT_DESC);

$region_vote_icon_text = array("1" => "text-approved", "0" => "text-error", "9" => "text-default");

$person_record = $_REQUEST['record'];
if($person_record != ""){
    $person_name = \Vanderbilt\HarmonistHubExternalModule\getPeopleName($pidsArray['PEOPLE'], $person_record);
}
?>
<script>
    //To filter the data
    $.fn.dataTable.ext.search.push(
        function( settings, data, dataIndex ) {
            var region = $('#selectRegion option:selected').text();
            var activity = $('#selectActivity option:selected').text();
            var column_region = data[4];
            var column_activity = data[3];

            if(region != 'Select All' && column_region == region ){
                if(activity != 'Select All' && column_activity.match(activity) != null){
                    return true;
                }else if(activity == 'Select All'){
                    return true;
                }
            }else if(region == 'Select All'){
                if(activity != 'Select All' && column_activity.match(activity) != null){
                    return true;
                }else if(activity == 'Select All'){
                    return true;
                }
            }

            return false;
        }
    );

    $(document).ready(function() {
        Sortable.init();
        var person_name = <?=json_encode($person_name)?>;
        if(person_name != "" && person_name != null){
            $('#table_archive').dataTable( {"pageLength": 50,"order": [0, "desc"],"oSearch": {"sSearch": person_name}});
        }else{
            $('#table_archive').dataTable( {"pageLength": 50,"order": [0, "desc"]});
        }

        //when any of the filters is called upon change datatable data
        $('#selectRegion, #selectActivity').change( function() {
            var table = $('#table_archive').DataTable();
            table.draw();
        } );
    } );
</script>
<div class="container">
    <div class="backTo">
        <a href="<?=$module->getUrl('index.php?pid='.$pidsArray['PROJECTS'])?>">< Back to Home</a>
    </div>
    <h3>Recent Activity</h3>
    <p class="hub-title"><?=$settings['hub_recent_act_text']?></p>
    </br>
</div>
<div class="container">
    <div class="optionSelect conceptSheets_optionMenu">
        <div style="float:right">
            <div style="float:left;padding-left:30px;margin-top: 8px;">
                Group:
            </div>
            <div style="float:left;padding-left:10px">
                <select class="form-control" name="selectRegion" id="selectRegion">
                    <option value="">Select All</option>
                    <?php
                    $RecordSetRegions = \REDCap::getData($pidsArray['REGIONS'], 'array', null);
                    $regions = ProjectData::getProjectInfoArray($RecordSetRegions);
                    ArrayFunctions::array_sort_by_column($regions,'region_code');
                    if (!empty($regions)) {
                        foreach ($regions as $region){
                            echo "<option value='".$region['record_id']."'>".$region['region_code']."</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <div style="float:left;padding-left:30px;margin-top: 8px;">
                Activity:
            </div>
            <div style="float:left;padding-left:10px">
                <select class="form-control" name="selectActivity" id="selectActivity">
                    <option value="">Select All</option>
                    <option value='comment'>comment</option>
                    <option value='vote'>vote</option>
                    <option value='revision'>revision</option>
                    <option value='file'>file</option>
                </select>
            </div>
        </div>
    </div>
</div>
<div class="container">
    <div class="panel panel-default-archive">
        <div class="table-responsive table-archive">
            <table class="table table_requests sortable-theme-bootstrap" data-sortable id="table_archive">
                <?php
                if(!empty($comments)) {?>
                    <colgroup>
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="sorted_class" data-sorted="true" data-sorted-direction="descending">Date</th>
                            <th class="sorted_class">Concept</th>
                            <th class="sorted_class">Name</th>
                            <th class="sorted_class">Activity</th>
                            <th class="sorted_class">Group</th>
                            <th class="sorted_class"><span style="display:block">Request</span><span>Title</span></th>
                            <th class="sorted_class">File</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($comments as $comment) {
                        if($comment['author_revision_y'] == '1' || $comment['pi_vote'] != '' || $comment['comments'] != '') {
                            $RecordSetPeople = \REDCap::getData($pidsArray['PEOPLE'], 'array', array('record_id' => $comment['response_person']));
                            $people = ProjectData::getProjectInfoArray($RecordSetPeople)[0];
                            $name = trim($people['firstname'] . ' ' . $people['lastname']);

                            $RecordSetRegions = \REDCap::getData($pidsArray['REGIONS'], 'array', array('record_id' => $people['person_region']),null,null,null,false,false,false,"[showregion_y] = 1");
                            $region = ProjectData::getProjectInfoArray($RecordSetRegions)[0];

                            $RecordSetRM = \REDCap::getData($pidsArray['RMANAGER'], 'array', array('request_id' => $comment['request_id']));
                            $requestComment = ProjectData::getProjectInfoArray($RecordSetRM)[0];

                            $comment_time ="";
                            if(!empty($comment['responsecomplete_ts'])){
                                $dateComment = new \DateTime($comment['responsecomplete_ts']);
                                $dateComment->modify("+1 hours");
                                $comment_time = $dateComment->format("Y-m-d H:i:s");
                            }

                            echo '<tr><td width="150px">'.$comment_time.'</td>';

                            $concept_id = "<em>None</em>";
                            if(!empty($requestComment['assoc_concept'])){
                                $RecordSetConcepts = \REDCap::getData($pidsArray['HARMONIST'], 'array', array('record_id' => $requestComment['assoc_concept']));
                                $concept = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConcepts)[0];
                                $concept_id = '<a href="'.$module->getUrl('index.php?pid='.$pidsArray['PROJECTS'].'&option=ttl&record='.$concept['record_id']).'">'.$concept['concept_id'].'</a>';
                            }else if($requestComment['mr_temporary'] != ""){
                                $concept_id = $requestComment['mr_temporary'];
                            }
                            echo '<td width="50px">'.$concept_id.'</td>'.
                                '<td width="160px">'.$name.'</td>';

                            echo '<td width="160px">';

                            if ($comment['author_revision_y'] == '1') {
                                echo 'submitted a <strong>revision</strong></td>';
                            } else{
                                $text = 'submited a ';

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

                                echo $text.'</td>';
                            }
                            echo '<td width="65px">'.$region['region_code'].'</td>'.
                                 '<td width="450px">';

                            $RecordSetRM = \REDCap::getData($pidsArray['RMANAGER'], 'array', array('request_id' => $comment['request_id']));
                            $request = ProjectData::getProjectInfoArray($RecordSetRM)[0];

                            $instance = $current_user['person_region'];

                            $comment_vote = "";
                            if($settings['vote_visibility'] == "" || $settings['vote_visibility'] =="1") {
                                //PRIVATE VOTES
                               if ($request['region_response_status'][$instance] == 2) {
                                    //Complete
                                    $comment_vote .= '<div style="padding-bottom: 10px;"><span class="label label-info" title="Complete"><i class="fa fa-check" aria-hidden="true"></i></span> <span class="text-info">Complete</span></div>';
                               }
                            }else{
                                //PUBLIC VOTES
                                if ($request['region_response_status'][$instance] == "2" && $comment['pi_vote'] != '') {
                                    if ($comment['pi_vote'] != '') {
                                        if ($comment['pi_vote'] == "1") {
                                            //Approved
                                            $comment_vote = '<div style="padding-bottom: 10px;"><span class="label label-approved" title="Approved"><i class="fa fa-check" aria-hidden="true"></i></span> <span class="' . $region_vote_icon_text[$comment['pi_vote']] . '">Approved</span></div>';
                                        } else if ($comment['pi_vote'] == "0") {
                                            //Not Approved
                                            $comment_vote = '<div style="padding-bottom: 10px;"><span class="label label-notapproved" title="Not Approved"><i class="fa fa-times" aria-hidden="true"></i></span> <span class="' . $region_vote_icon_text[$comment['pi_vote']] . '">Not Approved</span></div>';
                                        } else if ($comment['pi_vote'] == "9") {
                                            //Complete
                                            $comment_vote = '<div style="padding-bottom: 10px;"><span class="label label-default" title="Abstained"><i class="fa fa-ban" aria-hidden="true"></i></span> <span class="' . $region_vote_icon_text[$comment['pi_vote']] . '">Abstained</span></div>';
                                        } else {
                                            $comment_vote = '<div style="padding-bottom: 10px;"><span class="label label-default" title="Abstained"><i class="fa fa-ban" aria-hidden="true"></i></span> <span class="' . $region_vote_icon_text[$comment['pi_vote']] . '">Abstained</span></div>';
                                        }
                                    }
                                }
                            }

                            echo    $comment_vote.'<a href="'.$module->getUrl('index.php?pid='.$pidsArray['PROJECTS'].'&option=hub&record=' . $requestComment['request_id']) . '" target="_blank">' . $requestComment['request_title'] . '</a></td>';
                            if($comment['revised_file'] != ''){
                                echo '<td>'.\Vanderbilt\HarmonistHubExternalModule\getFileLink($module, $pidsArray['PROJECTS'], $comment['revised_file'],'1','',$secret_key,$secret_iv,$current_user['record_id'],"").'</td>';
                            }else{
                                echo '<td></td>';
                            }
                            '</tr>';
                        }
                    }
                    ?>
                    </tbody>
                <?php
                }
                ?>

            </table>
        </div>
    </div>
</div>
