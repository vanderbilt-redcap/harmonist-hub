<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once dirname(dirname(__FILE__))."/projects.php";

$request_id = htmlentities($_REQUEST['request_id'],ENT_QUOTES);
$project_id = (int)$_REQUEST['pid'];

$RecordSetRegions = \REDCap::getData($pidsArray['REGIONS'], 'array', null,null,null,null,false,false,false,"[showregion_y] =1");
$regions = ProjectData::getProjectInfoArray($RecordSetRegions);
ArrayFunctions::array_sort_by_column($regions, 'region_code');
$regions = $module->escape($regions);

$RecordSetRequest = \REDCap::getData($pidsArray['RMANAGER'], 'array', array('request_id' => $request_id));
$request = $module->escape(ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRequest,null)[0]);

$region_vote_icon_view = $module->escape(array("1" => "fa fa-check", "0" => "fa fa-times", "9" => "fa fa-ban"));
$region_vote_icon_text = $module->escape(array("1" => "text-approved", "0" => "text-error", "9" => "text-default"));
$region_vote_status = $module->escape($module->getChoiceLabels('region_vote_status', $pidsArray['RMANAGER']));

$region_row = '';
foreach ($regions as $region){
    $region_id = $module->escape($region['record_id']);
    $region_time = $request['region_close_ts'][$region_id];
    if(!empty($region_time)) {
        $region_time = $module->escape(date('Y-m-d H:i', strtotime($region_time)));
        $class = "";
        if (strtotime($request['due_d']) < strtotime($region_time)){
            $class = "overdue";
        }
    }
    $menu = '<li><span><i>No vote recorded</i></li></span><input type="hidden" value="" class="dropdown_votes" request="'.$request_id.'" id="'.$region_id.'_none" >';
    $selected = '<span><i>No vote recorded</i></span><input type="hidden" value="" class="dropdown_votes" request="'.$request_id.'" id="'.$region_id.'_none">';
    foreach ($region_vote_status as $index=>$vote_text){
        $menu .= '<li><span class="fa '.$region_vote_icon_view[$index].' '.$region_vote_icon_text[$index].'" aria-hidden="true"></span><span class="'.$region_vote_icon_text[$index].'"> '.$vote_text.'</span>';
        $menu .= '<input type="hidden" value="'.$index.'" class="dropdown_votes" request="'.$request_id.'" id="'.$region_id.'_'.$index.'"></li>';
        if($request['region_vote_status'][$region_id] == $index && $request['region_vote_status'][$region_id] != ''){
            $selected = '<span class="fa '.$region_vote_icon_view[$index].' '.$region_vote_icon_text[$index].'" aria-hidden="true"></span><span class="'.$region_vote_icon_text[$index].'"> '.$vote_text.'</span>';
            $selected .= '<input type="hidden" value="'.$index.'" class="dropdown_votes" request="'.$request_id.'" id="'.$region_id.'_'.$index.'"">';
        }
    }
    $region_row .= '<tr>'.
        '<td>'.$region['region_code'].' / '.$region['region_name'].'</td>'.
        '<td>
            <div style="float:left;">
                <ul class="nav navbar-nav navbar-right">
                    <li class="menu-item dropdown">
                        '.$selected.'
                        <ul class="dropdown-menu dropdown-menu-custom output-dropdown-menu" style="width: 200px;">
                             '.$menu.'
                        </ul>
                    </li>
                </ul>
                </div></td>'.
        '<td><span class="'.$class.'">'.$region_time.'</span></td>'.
        '</tr>';
}
$votes_table = '<table class="table table-striped">
                    <thead>
                    <tr>
                        <th>Region</th>
                        <th>Vote</th>
                        <th>On</th>
                    </tr>
                    </thead>
                    <tbody>
                        '.$region_row.'
                    </tbody>
                </table>';

echo json_encode($votes_table);
?>