<?php
namespace Vanderbilt\HarmonistHubExternalModule;

$RecordSetComments = \REDCap::getData(IEDEA_SOPCOMMENTS, 'array');
$comments = ProjectData::getProjectInfoArray($RecordSetComments);
ArrayFunctions::array_sort_by_column($comments, 'responsecomplete_ts',SORT_DESC);

$RecordSetDataUpload = \REDCap::getData(IEDEA_DATAUPLOAD, 'array', null);
$dataUpload = ProjectData::getProjectInfoArray($RecordSetDataUpload);
ArrayFunctions::array_sort_by_column($dataUpload, 'responsecomplete_ts',SORT_DESC);

$RecordSetDataDownload = \REDCap::getData(IEDEA_DATADOWNLOAD, 'array', null);
$dataDownload = ProjectData::getProjectInfoArray($RecordSetDataUpload);
ArrayFunctions::array_sort_by_column($dataDownload, 'responsecomplete_ts',SORT_DESC);

$all_data_recent_activity = array_merge($comments, $dataUpload);
$all_data_recent_activity = array_merge($all_data_recent_activity, $dataDownload);

ArrayFunctions::array_sort_by_column($all_data_recent_activity, 'responsecomplete_ts',SORT_DESC);
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
        <a href="<?=$module->getUrl('index.php?pid='.IEDEA_PROJECTS.'&option=dat')?>">< Back to Data</a>
    </div>
    <h3>Recent Data Activity</h3>
    <p class="hub-title"><?=$settings['hub_recent_data_act_text']?></p>
    <br>
    <div class="optionSelect conceptSheets_optionMenu">
        <div style="float:right">
            <div style="float:left;padding-left:30px;margin-top: 8px;">
                Region:
            </div>
            <div style="float:left;padding-left:10px">
                <select class="form-control" name="selectRegion" id="selectRegion">
                    <option value="">Select All</option>
                    <?php
                    $RecordSetRegionsLoginDown = \REDCap::getData(IEDEA_REGIONS, 'array', null);
                    $regions = ProjectData::getProjectInfoArray($RecordSetRegionsLoginDown);
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
                    <option value='file'>file</option>
                    <option value='upload'>upload</option>
                    <option value='download'>download</option>
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
                if(!empty($all_data_recent_activity)) {?>
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
                        <th class="sorted_class">Region</th>
                        <th class="sorted_class">Title</th>
                        <th class="sorted_class">File</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($all_data_recent_activity as $recent_activity) {
                        $comment_time ="";
                        if(!empty($recent_activity['responsecomplete_ts'])){
                            $dateComment = new \DateTime($recent_activity['responsecomplete_ts']);
                            $dateComment->modify("+1 hours");
                            $comment_time = $dateComment->format("Y-m-d H:i:s");
                        }

                        if($recent_activity['comments'] != '') {
                            #COMMENTS
                            $projectPeople = \REDCap::getData(IEDEA_PEOPLE, 'array', array('record_id' => $recent_activity['response_person']));
                            $people = ProjectData::getProjectInfoArray($projectPeople)[0];
                            $name = trim($people['firstname'] . ' ' . $people['lastname']);

                            $RecordSetSOP = \REDCap::getData(IEDEA_SOP, 'array', array('record_id' => $recent_activity['sop_id']));
                            $sop = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP)[0];
                            $sop_concept_id = $sop['sop_concept_id'];
                            $sop_name = $sop['sop_name'];
                            $assoc_concept = \Vanderbilt\HarmonistHubExternalModule\getReqAssocConceptLink($module, $sop_concept_id);

                            $RecordSetRegions = \REDCap::getData(IEDEA_REGIONS, 'array', array('record_id' => $people['person_region']),null,null,null,false,false,false,"[showregion_y] = 1");
                            $region = ProjectData::getProjectInfoArray($RecordSetRegions)[0];

                            echo '<tr><td width="170px">'.$comment_time.'</td>'.
                                '<td width="80px">'.$assoc_concept.'</td>'.
                                '<td width="200px">'.$name.'</td>'.
                                '<td width="230px">';

                            $text = 'submited a ';
                            if($recent_activity['comments'] != '' && $recent_activity['revised_file'] != ''){
                                $text .= '<strong>comment and file</strong>';
                            }else if($recent_activity['comments'] != ''){
                                $text .= '<strong>comment</strong>';
                            }else if($recent_activity['revised_file'] != ''){
                                $text .= '<strong>file</strong>';
                            }
                            echo $text.'</td>';

                            echo '<td width="40px">'.$region['region_code'].'</td>'.
                                '<td width="360px"><a href="'.$module->getUrl('index.php?pid='.IEDEA_PROJECTS.'&option=sop&record=' . $recent_activity['sop_id']) . '" target="_blank">'.$sop_name.'</a></td>';

                            if($recent_activity['revised_file'] != ''){
                                echo '<td width="40px">'.\Vanderbilt\HarmonistHubExternalModule\getFileLink($module, $recent_activity['revised_file'],'1','',$secret_key,$secret_iv,$current_user['record_id'],"").'</td>';
                            }else{
                                echo '<td width="40px"></td>';
                            }
                            '</tr>';
                        }else if($recent_activity['download_id'] != ""){
                            #DOWNLOADS
                            $projectPeople = \REDCap::getData(IEDEA_PEOPLE, 'array', array('record_id' => $recent_activity['response_person']));
                            $people = ProjectData::getProjectInfoArray($projectPeople)[0];
                            $name = trim($people['firstname'] . ' ' . $people['lastname']);

                            $RecordSetDataUpload = \REDCap::getData(IEDEA_DATAUPLOAD, 'array', array('record_id' => $recent_activity['download_id']));
                            $data_upload_region = ProjectData::getProjectInfoArray($RecordSetDataUpload)[0]['data_upload_region'];
                            $RecordSetRegion = \REDCap::getData(IEDEA_REGIONS, 'array', array('record_id' => $data_upload_region));
                            $region_code = ProjectData::getProjectInfoArray($RecordSetRegion)[0]['region_code'];

                            $assoc_concept = \Vanderbilt\HarmonistHubExternalModule\getReqAssocConceptLink($module, $recent_activity['downloader_assoc_concept']);

                            $icon = '<i class="fa fa-fw fa-arrow-down text-info" aria-hidden="true"></i>';

                            echo '<tr><td width="170px">'.$comment_time.'</td>'.
                                '<td width="80px">'.$assoc_concept.'</td>'.
                                '<td width="200px">'.$name.'</td>'.
                                '<td width="230px">downloaded data</td>'.
                                '<td width="40px">'.$region_code.'</td>'.
                                '<td width="360px"><i class="fa fa-fw fa-arrow-down text-info" aria-hidden="true"></i> '.$recent_activity['download_files'].'</td>'.
                                '<td width="40px"></td>';
                        }else if($recent_activity['data_assoc_request'] != ""){
                            #UPLOADS
                            $projectPeople = \REDCap::getData(IEDEA_PEOPLE, 'array', array('record_id' => $recent_activity['data_upload_person']));
                            $people = ProjectData::getProjectInfoArray($projectPeople)[0];
                            $name = trim($people['firstname'] . ' ' . $people['lastname']);

                            $RecordSetRegion = \REDCap::getData(IEDEA_REGIONS, 'array', array('record_id' => $recent_activity['data_upload_region']));
                            $region_code = ProjectData::getProjectInfoArray($RecordSetRegion)[0]['region_code'];

                            $assoc_concept = \Vanderbilt\HarmonistHubExternalModule\getReqAssocConceptLink($module, $recent_activity['data_assoc_concept']);

                            echo '<tr><td width="170px">'.$comment_time.'</td>'.
                                '<td width="80px">'.$assoc_concept.'</td>'.
                                '<td width="200px">'.$name.'</td>'.
                                '<td width="230px">uploaded data</td>'.
                                '<td width="40px">'.$region_code.'</td>'.
                                '<td width="360px"><i class="fa fa-fw fa-arrow-up text-info" aria-hidden="true"></i> '.$recent_activity['data_upload_zip'].'</td>'.
                                '<td width="40px"></td>';

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
