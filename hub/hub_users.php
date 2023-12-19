<?php
namespace Vanderbilt\HarmonistHubExternalModule;
?>
<script>
    //To filter the data
    $.fn.dataTable.ext.search.push(
        function( settings, data, dataIndex ) {
            var region = $('#selectRegion option:selected').text();
            var level = $('#selectActivity option:selected').text();
            var column_region = data[1];
            var column_level= data[3];

            if(region != 'Select All' && column_region == region ){
                if(level != 'Select All' && column_level == level ){
                    return true;
                }else if(level == 'Select All'){
                    return true;
                }
            }else if(region == 'Select All'){
                if(level != 'Select All' && column_level == level ){
                    return true;
                }else if(level == 'Select All'){
                    return true;
                }
            }

            return false;
        }
    );

    $(document).ready(function() {
        Sortable.init();
        $('#table_archive').dataTable( {"pageLength": 50});

        //when any of the filters is called upon change datatable data
        $('#selectRegion, #selectActivity').change( function() {
            var table = $('#table_archive').DataTable();
            table.draw();
        } );
    } );
</script>
<div class="container">
    <div class="backTo">
        <a href="<?=$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=adm'?>">< Back to Admin</a>
    </div>
    <h3>Hub Users</h3>
    <p class="hub-title">All Hub users with "active" status are listed. To change a user's access level, grant or remove permissions, or deactivate a user account, click on the user's REDCap icon to edit the settings in REDCap (requires REDCap login.) To create a new user or to activate an inactive user account, <a href="<?= APP_PATH_WEBROOT_ALL.'DataEntry/record_status_dashboard.php?pid='.$pidsArray['PEOPLE']?>" target="_blank">log in to REDCap directly</a>.</p>
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
                    $regions = $module->escape(ProjectData::getProjectInfoArray($RecordSetRegions));
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
                Level:
            </div>
            <div style="float:left;padding-left:10px">
                <select class="form-control" name="selectActivity" id="selectActivity">
                    <option value="">Select All</option>
                    <?php
                    $harmonist_regperm = $module->escape($module->getChoiceLabels('harmonist_regperm', $pidsArray['PEOPLE']));
                    if (!empty($harmonist_regperm)) {
                        foreach ($harmonist_regperm as $level){
                            echo "<option value='".$level."'>".$level."</option>";
                        }
                    }
                    ?>
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
                $RecordSetPeople = \REDCap::getData($pidsArray['PEOPLE'], 'array', null,null,null,null,false,false,false,"[active_y] = '1'");
                $logins = $module->escape(ProjectData::getProjectInfoArray($RecordSetPeople));
                if(!empty($logins)) {
                    echo '<thead>' . '
                            <tr>' . '
                                <th class="sorted_class" data-sorted="true" data-sorted-direction="descending">Name</th>' . '
                                <th class="sorted_class" data-sorted-direction="descending">Group</th>' . '
                                <th class="sorted_class" data-sorted-direction="descending" style="width: 150px;">Last Access Link</th>' . '
                                <th class="sorted_class" data-sorted-direction="descending">Level</th>' . '
                                <th class="sorted_class" data-sorted-direction="descending">Permissions</th>' . '
                                <th class="sorted_class" data-sortable="false">REDCap</th>' . '
                                <th class="sorted_class" data-sortable="false">Options</th>' . '
                            </tr>' . '
                            </thead>';
                    $harmonist_regperm = $module->getChoiceLabels('harmonist_regperm', $pidsArray['PEOPLE']);
                    $harmonist_perms = $module->getChoiceLabels('harmonist_perms', $pidsArray['PEOPLE']);
                    foreach ($logins as $login){
                        $RecordSetRegionsLogin = \REDCap::getData($pidsArray['REGIONS'], 'array', array('record_id' => $login['person_region']));
                        $region_code = ProjectData::getProjectInfoArray($RecordSetRegionsLogin)[0]['region_code'];

                        $RecordSetPeople = \REDCap::getData($pidsArray['PEOPLE'], 'array',  array('record_id' => $login['record_id']));
                        $people = ProjectData::getProjectInfoArray($RecordSetPeople)[0];

                        $gotoredcap = $module->escape(APP_PATH_WEBROOT_ALL . "DataEntry/record_home.php?pid=" . $pidsArray['PEOPLE'] . "&arm=1&id=" . $login['record_id']);

                        $harmonist_perm_text = "";
                        if($people['harmonistadmin_y'] == "1"){
                            $harmonist_perm_text = "<div><strong>Admin</strong></div>";
                        }
                        $found = false;
                        foreach ($people['harmonist_perms'] as $index => $h_perm){
                            if($h_perm == '1'){
                                $found = true;
                                $harmonist_perm_text .= "<div>".$harmonist_perms[$index]."</div>";
                            }
                        }
                        if(!$found){
                            $harmonist_perm_text .= "<div><em>None</em></div>";
                        }

                        echo '<tr><td><a href="mailto:' . $login['email'] . '">' . $login['firstname'] .' '.$login['lastname']. '</a></td>' .
                            '<td style="text-align: center;">' . $module->escape($region_code) . '</td>' .
                            '<td>' . $login['last_requested_token_d'] . '</td>' .
                            '<td>' . $module->escape($harmonist_regperm[$people['harmonist_regperm']]) . '</td>' .
                            '<td>' . $module->escape($harmonist_perm_text) . '</td>' .
                            '<td style="text-align: center;"><a href="' . $gotoredcap . '" target="_blank"> <img src="'.$module->getUrl('img/REDCap_R_logo_transparent.png').'" style="width: 18px;" alt="REDCap Logo"></a></td>'.
                            '<td style="text-align: center;"><div><a href="'.$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=hra&record='.$login['record_id'].'" class="btn btn-primary btn-xs actionbutton"><i class="fa fa-user fa-fw" aria-hidden="true"></i> View Activity</a></div></td>';

                    }
                }else{?>
                    <tbody>
                    <tr>
                        <td><span><em>No logins available</em></span></td>
                    </tr>
                    </tbody>
                <?php }?>
            </table>
        </div>
    </div>
</div>
