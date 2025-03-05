<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once dirname(dirname(__FILE__))."/projects.php";

$request_id = (int)$_REQUEST['request_id'];
$region_id = (int)$_REQUEST['region_id'];
$project_id = (int)$_REQUEST['pid'];

$regions = $module->escape(\REDCap::getData($pidsArray['REGIONS'], 'json-array', null,null,null,null,false,false,false,"[showregion_y] =1"));
ArrayFunctions::array_sort_by_column($regions, 'region_code');

$region_vote_icon_view = $module->escape(array("1" => "fa fa-check", "0" => "fa fa-times", "9" => "fa fa-ban"));
$region_vote_icon_text = $module->escape(array("1" => "text-approved", "0" => "text-error", "9" => "text-default"));
$vote_text = $module->escape(array("1" => "Approved", "0" => "Not Approved", "9" => "Abstained/Not applicable"));

$votes_menu = '<ul class="nav nav-tabs">';
$votes_table = "";
foreach ($regions as $region){
    $votes_text = '<div style="padding-top: 20px"><h4>Votes for <strong>'.$region['region_name'].' ('.$region['region_code'].')</strong></h4></div><div><p>Here you will find all votes submitted for this request.</p></div>';

    $total_voters = count(\REDCap::getData($pidsArray['PEOPLE'], 'json-array', null,array('recor_id'),null,null,false,false,false,"[harmonist_regperm] = 3 and [person_region] =".$region['record_id']));

    $votes_text .='<div style="padding-bottom: 20px">There are currently <strong>'.$total_voters.' voters</strong> for this region.</div>';
    $active = "";
    $in = "";
    $activetab = "";
    $activeLabel = "label-white";
    if($region['record_id'] == $region_id){
        $active = "active";
        $in = "in ";
        $activetab = "activetab";
        $activeLabel = "label-default label-white";
    }
    $params = [
        'project_id' => $pidsArray['COMMENTSVOTES'],
        'return_format' => 'array',
        'filterLogic' => "[request_id] = ".$request_id." and [response_region] = ".$region['record_id'],
        'filterType' => "RECORD"
    ];
    $votesData = \REDCap::getData($params);
    $votes = ProjectData::getProjectInfoArrayRepeatingInstruments($votesData,$pidsArray['COMMENTSVOTES']);
    $region_row = '';
    $total_votes = 0;
    foreach ($votes as $vote){
        if(array_key_exists('pi_vote',$vote)){
            $region_time = $vote['responsecomplete_ts'];
            $name = getPeopleName($pidsArray['PEOPLE'], $vote['response_person'],"");

            $region_row .= '<tr>'.
                '<td><span class="'.$region_vote_icon_view[$vote['pi_vote']].' '.$region_vote_icon_text[$vote['pi_vote']].'" aria-hidden="true"></span><span class="'.$region_vote_icon_text[$vote['pi_vote']].'"> '.$vote_text[$vote['pi_vote']].'</span></td>'.
                '<td>'.$module->escape($region_time).'</td>'.
                '<td>'.$module->escape($name).'</td>'.
                '</tr>';

            $total_votes++;
        }
    }

    $votes_menu .= ' <li class="'.$active.'"><a data-toggle="tab" href="#'.$region['region_code'].'">'.$region['region_code'].' <span class="badge '.$activeLabel.' '.$activetab.'">'.$total_votes.'</span></a></li>';

    if($region_row == ''){
        $region_row .= '<tr><td colspan="3">No votes recorded.</td></tr>';
    }

    $votes_table .= '<div id="'.$region['region_code'].'" class="tab-pane fade '.$in.$active.'">'.filter_tags($votes_text).'
                   <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>Vote</th>
                        <th>On</th>
                        <th>By</th>
                    </tr>
                    </thead>
                    <tbody>
                        '.$region_row.'
                    </tbody>
                </table>
                </div>';
}
$votes_menu .= '</ul>';


echo json_encode($votes_menu.'<div class="tab-content">'.$votes_table.'</div>');
?>