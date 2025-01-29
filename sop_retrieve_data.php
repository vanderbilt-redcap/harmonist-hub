<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once dirname(__FILE__) . "/classes/HubData.php";

require_once ($module->getSecurityHandler()->getCredentialsServerVars("AWS"));

$request_DU = $module->escape(\REDCap::getData($pidsArray['DATAUPLOAD'], 'json-array', null));
krsort($request_DU);
ArrayFunctions::array_sort_by_column($request_DU,'responsecomplete_ts',SORT_DESC);

$array_downloads_by_concept = array();
foreach ($request_DU as $down){
    if(!array_key_exists($down['data_assoc_concept'],$array_downloads_by_concept)){
        $array_downloads_by_concept[$down['data_assoc_concept']] = array();
    }
    if(!array_key_exists($down['data_assoc_request'],$array_downloads_by_concept[$down['data_assoc_concept']])){
        $array_downloads_by_concept[$down['data_assoc_concept']][$down['data_assoc_request']] = array();
    }
    array_push($array_downloads_by_concept[$down['data_assoc_concept']][$down['data_assoc_request']],$down);
}
?>
<!DOCTYPE html>
<html lang="en">
    <?php include("hub_html_head.php"); ?>
<script>
    $(document).ready(function () {
        var tableDown = $('#sortable_table_downloads').DataTable({
            order: [[ 0, "desc" ]],
            responsive: true,
            bFilter: false,
            bPaginate: false,
            bInfo: false
        });
    });
</script>
<body>
<?php include('hub_header.php');?>
<div class="container" style="margin: 0 auto;float:none;min-height: 900px;">
<div class="optionSelect">
    <div class="backTo">
        <a href="<?=$indexUrl.'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=dat'?>">< Back to Data</a>
    </div>
    <div class="optionSelect">
        <h3>Retrieve Data</h3>
        <p class="hub-title"><?=filter_tags($settings['hub_download_data_text'])?></p>
    </div>
</div>
<div class="optionSelect">
    <?php
    if($array_downloads_by_concept == ""){?>
        <table>
        <tbody>
        <tr>
            <td><span><i>No Data available</i></span></td>
        </tr>
        </tbody>
        </table>
    <?php
    }else if($current_user['allowgetdata_y___1'] != "1"){?>
        <table>
        <tbody>
        <tr>
            <td><span><i>You do not have permissions to retrieve data. Please contact an administrator.</i></span></td>
        </tr>
        </tbody>
        </table>
    <?php
    }else{
        $data_printed = false;
        foreach ($array_downloads_by_concept as $concept_id => $concept_table) {
            foreach ($concept_table as $sop_id => $AllDataUp) {
                $RecordSetTable = \REDCap::getData($pidsArray['HARMONIST'], 'array', array('record_id' => $concept_id));
                $concept = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetTable,$pidsArray['HARMONIST'])[0];
                $concept_sheet = $concept['concept_id'];
                $concept_title = $concept['concept_title'];

                $RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', array('record_id' => $sop_id));
                $sop = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP,$pidsArray['SOP'])[0];
                $array_userid = explode(',', $sop['sop_downloaders']);

                $person_info = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array('record_id' => $sop['sop_datacontact']),array('firstname','lastname','email'))[0];
                if($person_info != ""){
                    $contact_concept_person = $person_info['firstname'] . " " . $person_info['lastname'] . " (<a href='mailto:" . $person_info['email'] . "'>" . $person_info['email'] . "</a>)";
                }else{
                    $contact_concept_person = "<i>None</i>";
                }

                $concept_header = $concept_sheet . ' | Data Request #' . $sop_id;

                $array_dates = $module->escape(getNumberOfDaysLeftButtonHTML($sop['sop_due_d'], '', '', '1', '1'));

                $downloads_active = 0;
                $body = '';
                $permission_granted = false;
                foreach ($AllDataUp as $data_up) {
                    if ($data_up['data_upload_person'] == $current_user['record_id'] || ($key = array_search($current_user['record_id'], $array_userid)) !== false) {
                        $permission_granted = true;
                        $data_printed = true;
                        $assoc_concept = getReqAssocConceptLink($module, $pidsArray, $data_up['data_assoc_concept']);

                        $assoc_request = \REDCap::getData($pidsArray['RMANAGER'], 'json-array', array('request_id' => $data_up['data_assoc_request']),array('request_title'))[0]['request_title'];

                        $person_info = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array('record_id' => $data_up['data_upload_person']),array('firstname','lastname','email'))[0];
                        $contact_person = "<a href='mailto:" . $person_info['email'] . "'>" . $person_info['firstname'] . " " . $person_info['lastname'] . "</a>";

                        $RecordSetRegion = \REDCap::getData($pidsArray['REGIONS'], 'array', array('record_id' => $data_up['data_upload_region']));
                        $region_code = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRegion,$pidsArray['REGIONS'])[0]['region_code'];

                        $file_pdf = ($data_up['data_upload_pdf'] == "") ? "" : getFileLink($module,  $pidsArray['PROJECTS'], $data_up['data_upload_pdf'], '1','',$secret_key,$secret_iv,$current_user['record_id'],"");

                        $extra_days = ' + ' . $settings['retrievedata_expiration'] . " days";
                        $expire_date = date('Y-m-d', strtotime($data_up['responsecomplete_ts'] . $extra_days));

                        $array_expire_dates = getNumberOfDaysLeftButtonHTML($expire_date, '', '', '2');
                        $expiration_date = $array_expire_dates['text'] . " " . $array_expire_dates['button'];

                        $deleted = "";
                        $buttons = "";
                        if ($data_up['deleted_y'] != '1' && strtotime ($expire_date) >= strtotime(date('Y-m-d'))) {
                            $downloads_active++;
                            $downloadUrl = $module->getUrl('hub/aws/AWS_downloadFile.php').'&code=' . getCrypt("id=". $data_up['record_id']."&user_id=".$hubData->getCurrentUser()['record_id'],'e',$secret_key,$secret_iv);
                            $buttons = '<div><a href="'.$downloadUrl. '" class="btn btn-primary btn-xs"><i class="fa fa-arrow-down"></i> Download</a></div>';
                        } else if ($data_up['deleted_y'] == '1' && $data_up['deletion_ts'] != ""){
                            if($data_up['deletion_type'] == '2'){
                                $person_info_delete = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array('record_id' => $data_up['deletion_hubuser']),array('firstname','lastname','email'))[0];
                                $contact_person_delete = "<a href='mailto:" . $person_info['email'] . "'>" . $person_info_delete['firstname'] . " " . $person_info_delete['lastname'] . "</a>";

                                $deleted = '<div><i>File deleted by ' . $contact_person_delete . ' on '.htmlspecialchars($data_up['deletion_ts'],ENT_QUOTES).'</i></div>';
                                $expiration_date = "<span class='text-error'>".htmlspecialchars(date("d M Y",strtotime($data_up['deletion_ts'])),ENT_QUOTES)."</span>";
                            }else{
                                $expiration_date = "<span class='text-error'>".htmlspecialchars(date("d M Y",strtotime($data_up['deletion_ts'])),ENT_QUOTES)."</span>";
                                $deleted = '<div><i>File auto-deleted on ' . htmlspecialchars($data_up['deletion_ts'],ENT_QUOTES) . '</i></div>';
                            }
                        }else if(strtotime ($expire_date) >= strtotime(date('Y-m-d'))){

                        }

                        $notes = $data_up['upload_notes'];
                        $color_notes = "";
                        if ($data_up['upload_notes'] == "") {
                            $notes = "<i>No notes available</i>";
                            $color_notes = "retrieve-notes";
                        }

                        $body .= "<tr><td>" . htmlspecialchars($data_up['responsecomplete_ts'],ENT_QUOTES) . "</td>" .
                            "<td>" . htmlspecialchars($region_code,ENT_QUOTES) . "</td>" .
                            "<td>" . filter_tags($contact_person) . "</td>" .
                            "<td>" . htmlspecialchars($data_up['data_upload_zip'],ENT_QUOTES).filter_tags($deleted) . "</td>" .
                            "<td style='text-align: center;'>" . filter_tags($file_pdf) . "</td>" .
                            "<td>" . filter_tags($expiration_date) . "</td>" .
                            "<td>" . filter_tags($buttons) . "</td>" .
                            "<td>" . filter_tags($notes) . "</td></tr>" ;
                    }
                }

                $header = '
            <div class="panel panel-default" style="margin-bottom: 20px">
                <div class="panel-heading" style="height: 38px">
                    <h3 class="panel-title">
                        <a data-toggle="collapse" href="#collapse_concept_' . htmlspecialchars($concept_id . $sop_id,ENT_QUOTES) . '">' . htmlspecialchars($concept_header,ENT_QUOTES) . '</a>
                        <span style="float:right;padding-right: 30px;font-size: 14px"> ' . filter_tags($array_dates['button'],ENT_QUOTES) . ' <span class="label label-as-badge btn-info" style="font-weight: normal;"><i class="fa fa-arrow-down"></i> '. htmlspecialchars($downloads_active,ENT_QUOTES) . '</span></span>
                    </h3>
    
                </div>
    
                <div id="collapse_concept_' . htmlspecialchars($concept_id . $sop_id,ENT_QUOTES) . '" class="panel-collapse collapse" aria-expanded="true">
                    <table class="table table_requests sortable-theme-bootstrap">
                        <div class="row request">
                            <div class="col-md-12 col-sm-12" style="padding-left: 30px"><strong>Title: </strong><a href="'.$indexUrl.'&NOAUTH&pid=' . $pidsArray['PROJECTS'] . '&option=ttl&record=' . htmlspecialchars($concept_id,ENT_QUOTES) . '" target="_blank" alt="concept_link" style="color: #337ab7;">' . htmlspecialchars($concept_title,ENT_QUOTES) . ' <i class="fa fa-external-link"></i></a> | <a href="'.$indexUrl.'&option=sop&record=' . $sop_id . '&type=r'.'" target="_blank" alt="concept_link" style="color: #337ab7;">Data Request #' . htmlspecialchars($sop_id,ENT_QUOTES) . ' <i class="fa fa-external-link"></i></a></div>
                        </div>
                        <div class="row request">
                            <div class="col-md-12 col-sm-12" style="padding-left: 30px"><strong>Data Contact: </strong>' . filter_tags($contact_concept_person) . '</div>
                        </div>
                        <div class="row request">
                            <div class="col-md-12 col-sm-12" style="padding-left: 30px"><strong>Data Due: ' . filter_tags($array_dates['text']) . '</strong></div>
                        </div>
                        <div class="row request"></div>
                    </table>
                    <div class="table-responsive">
                    <table class="table table_requests sortable-theme-bootstrap dt-responsive child_notes" data-sortable id="sortable_table_downloads" width="100%">
                        <thead>
                        <tr>
                            <th class="sorted_class sorting_desc" width="160px" data-sorted="true" aria-sort="descending" data-sorted-direction="descending">Upload Date</th>
                            <th class="sorted_class" style="width:80px">Region</th>
                            <th class="sorted_class" style="width:150px">Submitted By</th>
                            <th class="sorted_class" style="width:250px">Filename</th>
                            <th class="sorted_class" style="width:60px">PDF</th>
                            <th class="sorted_class" style="width:180px">Available Until</th>
                            <th class="sorting_disabled" style="width:96px" data-sortable="false">Actions</th>
                            <th class="none"></th>
                        </tr>
                        </thead>
                        <tbody>';

                $header .= $body;
                $header .= '</tbody></table></div></div></div>';

                if ($permission_granted) {
                    echo $header;
                }
            }
        }
        if (!$data_printed) {
            echo '<div><div><table>
                <tbody>
                <tr>
                    <td><span><i>You are not an assigned Data Downloader on any current datasets.</i></span></td>
                </tr></tbody></table></div></div>';
        }
    }
        ?>
</div>
<?php
if($settings['session_timeout_popup'] == 2 && $settings['session_timeout_popup'] != ''){
    include(dirname(dirname(__FILE__)) . "/logout_popup.php");
}
?>
</div>
<?php include('hub_footer.php'); ?>
<br/>
</body>
</html>
