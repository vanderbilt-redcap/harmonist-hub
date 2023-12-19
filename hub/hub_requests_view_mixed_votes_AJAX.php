<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once dirname(dirname(__FILE__))."/projects.php";

$request_id = (int)$_REQUEST['request_id'];
$region_id = (int)$_REQUEST['region_id'];
$project_id = (int)$_REQUEST['pid'];

$RecordSetRegions = \REDCap::getData($pidsArray['REGIONS'], 'array', null,null,null,null,false,false,false,"[showregion_y] =1");
$regions = $module->escape(ProjectData::getProjectInfoArray($RecordSetRegions));
ArrayFunctions::array_sort_by_column($regions, 'region_code');

$RecordSetRequest = \REDCap::getData($pidsArray['RMANAGER'], 'array', array('request_id' => $request_id));
$request = $module->escape(ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRequest)[0]);

$region_vote_icon_view = $module->escape(array("1" => "fa fa-check", "0" => "fa fa-times", "9" => "fa fa-ban"));
$region_vote_icon_text = $module->escape(array("1" => "text-approved", "0" => "text-error", "9" => "text-default"));
$vote_text = $module->escape(array("1" => "Approved", "0" => "Not Approved", "9" => "Abstained/Not applicable"));
$region_vote_status = $module->getChoiceLabels('region_vote_status', $pidsArray['RMANAGER']);

$votes_menu = '<ul class="nav nav-tabs">';
$votes_table = "";
foreach ($regions as $region){
    $votes_text = '<div style="padding-top: 20px"><h4>Votes for <strong>'.$region['region_name'].' ('.$region['region_code'].')</strong></h4></div><div><p>Here you will find all votes submitted for this request.</p></div>';

    $RecordSetVoters = \REDCap::getData($pidsArray['PEOPLE'], 'array', null,null,null,null,false,false,false,"[harmonist_regperm] = 3 and [person_region] =".$region['record_id']);
    $total_voters = ProjectData::getProjectInfoArray($RecordSetVoters);

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

    $RecordSetComments = \REDCap::getData($pidsArray['COMMENTSVOTES'], 'array', array("request_id" => $request_id),null,null,null,false,false,false,"[response_region] =".$region['record_id']);
    $votes = ProjectData::getProjectInfoArray($RecordSetComments);
    $response_person = $module->getChoiceLabels('response_person', $pidsArray['COMMENTSVOTES']);
    $region_row = '';
    $total_votes = 0;
    foreach ($votes as $vote){
        if(array_key_exists('pi_vote',$vote)){
            $region_time = $vote['responsecomplete_ts'];
            $name = \Vanderbilt\HarmonistHubExternalModule\getPeopleName($pidsArray['PEOPLE'], $vote['response_person'],"");

            $region_row .= '<tr>'.
                '<td><span class="'.$region_vote_icon_view[$vote['pi_vote']].' '.$region_vote_icon_text[$vote['pi_vote']].'" aria-hidden="true"></span><span class="'.$region_vote_icon_text[$vote['pi_vote']].'"> '.$vote_text[$vote['pi_vote']].'</span></td>'.
                '<td>'.$module->escape($region_time).'</td>'.
                '<td>'.$module->escape($name).'</td>'.
                '</tr>';

            $total_votes++;
        }
    }

    $votes_menu .= ' <li class="'.$module->escape($active).'"><a data-toggle="tab" href="#'.$region['region_code'].'">'.$region['region_code'].' <span class="badge '.$activeLabel.' '.$activetab.'">'.$total_votes.'</span></a></li>';

    if($region_row == ''){
        $region_row .= '<tr><td colspan="3">No votes recorded.</td></tr>';
    }

    $votes_table .= '<div id="'.$module->escape($region['region_code']).'" class="tab-pane fade '.$module->escape($in.$active).'">'.$module->escape($votes_text).'
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