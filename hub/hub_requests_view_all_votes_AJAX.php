<?php
define('NOAUTH',true);
require_once dirname(dirname(__FILE__))."/projects.php";

$request_id = $_REQUEST['request_id'];
$project_id = $_REQUEST['pid'];

$RecordSetRegions = \REDCap::getData(IEDEA_REGIONS, 'array', null,null,null,null,false,false,false,"[showregion_y] =1");
$regions = getProjectInfoArray($RecordSetRegions);
array_sort_by_column($regions, 'region_code');

$RecordSetRequest = \REDCap::getData(IEDEA_RMANAGER, 'array', array('request_id' => $request_id));
$request = getProjectInfoArrayRepeatingInstruments($RecordSetRequest,null)[0];

$region_vote_icon_view = array("1" => "fa fa-check", "0" => "fa fa-times", "9" => "fa fa-ban");
$region_vote_icon_text = array("1" => "text-approved", "0" => "text-error", "9" => "text-default");
$region_vote_status = $module->getChoiceLabels('region_vote_status', IEDEA_RMANAGER);

$region_row = '';
foreach ($regions as $region){
    $region_id = $region['record_id'];
    $region_time = $request['region_close_ts'][$region_id];
    if(!empty( $region_time)) {
        $region_time = date('Y-m-d H:i', strtotime($region_time));
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
        '<td>'.$region['region_code'].'/'.$region['region_name'].'</td>'.
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