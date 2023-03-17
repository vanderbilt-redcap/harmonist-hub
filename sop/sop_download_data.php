<?php
namespace Vanderbilt\HarmonistHubExternalModule;

$RecordSetDU = \REDCap::getData($pidsArray['DATAUPLOAD'], 'array', null);
$request_DU = ProjectData::getProjectInfoArray($RecordSetDU);
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
<style>
    .dtr-control{
        width: 130px;
    }
</style>
<script>
    $(document).ready(function () {
        $('.child_notes')
            .dataTable({
                responsive: true,
                bFilter: false,
                bPaginate: false,
                bInfo: false
            });
    });
</script>
<div class="optionSelect">
    <div class="backTo">
        <a href="<?=$module->getUrl('index.php?NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=dat')?>">< Back to Data</a>
    </div>
    <div class="optionSelect">
        <h3>Retrieve Data</h3>
        <p class="hub-title"><?=$settings['hub_download_data_text']?></p>
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
    }else if($current_user['allowgetdata_y'][1] != "1"){?>
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
                $concept = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetTable)[0];
                $concept_sheet = $concept['concept_id'];
                $concept_title = $concept['concept_title'];

                $RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', array('record_id' => $sop_id));
                $sop = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP)[0];
                $array_userid = explode(',', $sop['sop_downloaders']);

                $RecordSetPeople = \REDCap::getData($pidsArray['PEOPLE'], 'array', array('record_id' => $sop['sop_datacontact']));
                $person_info = ProjectData::getProjectInfoArray($RecordSetPeople)[0];
                if($person_info != ""){
                    $contact_concept_person = $person_info['firstname'] . " " . $person_info['lastname'] . " (<a href='mailto:" . $person_info['email'] . "'>" . $person_info['email'] . "</a>)";
                }else{
                    $contact_concept_person = "<i>None</i>";
                }



                $concept_header = '<a href="'.$module->getUrl('index.php?NOAUTH&pid=' . $pidsArray['DATAMODEL'] . '&option=ttl&record=' . $concept_id) . '" target="_blank" alt="concept_link" style="color: #337ab7;">' . $concept_sheet . '</a> | <a href="'.$module->getUrl('index.php?pid='.$pidsArray['PROJECTS'].'&option=sop&record=' . $sop_id . '&type=r').'" target="_blank" alt="concept_link" style="color: #337ab7;">Data Request #' . $sop_id . '</a>';
                $concept_header = $concept_sheet . ' | Data Request #' . $sop_id;

                $array_dates = \Vanderbilt\HarmonistHubExternalModule\getNumberOfDaysLeftButtonHTML($sop['sop_due_d'], '', '', '1', '1');

                $downloads_active = 0;
                $body = '';
                $permission_granted = false;
                foreach ($AllDataUp as $data_up) {
                    if ($data_up['data_upload_person'] == $current_user['record_id'] || ($key = array_search($current_user['record_id'], $array_userid)) !== false) {
                        $permission_granted = true;
                        $data_printed = true;
                        $assoc_concept = \Vanderbilt\HarmonistHubExternalModule\getReqAssocConceptLink($module, $pidsArray, $data_up['data_assoc_concept']);

                        $RecordSetRM = \REDCap::getData($pidsArray['RMANAGER'], 'array', array('request_id' => $data_up['data_assoc_request']));
                        $assoc_request = ProjectData::getProjectInfoArray($RecordSetRM)[0]['request_title'];

                        $RecordSetPeople = \REDCap::getData($pidsArray['PEOPLE'], 'array', array('record_id' => $data_up['data_upload_person']));
                        $person_info = ProjectData::getProjectInfoArray($RecordSetPeople)[0];
                        $contact_person = "<a href='mailto:" . $person_info['email'] . "'>" . $person_info['firstname'] . " " . $person_info['lastname'] . "</a>";

                        $RecordSetRegion = \REDCap::getData($pidsArray['REGIONS'], 'array', array('record_id' => $data_up['data_upload_region']));
                        $region_code = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRegion)[0]['region_code'];

                        $file_pdf = ($data_up['data_upload_pdf'] == "") ? "" : \Vanderbilt\HarmonistHubExternalModule\getFileLink($module,  $pidsArray['PROJECTS'], $data_up['data_upload_pdf'], '1','',$secret_key,$secret_iv,$current_user['record_id'],"");

                        $extra_days = ' + ' . $settings['retrievedata_expiration'] . " days";
                        $expire_date = date('Y-m-d', strtotime($data_up['responsecomplete_ts'] . $extra_days));

                        $array_expire_dates = \Vanderbilt\HarmonistHubExternalModule\getNumberOfDaysLeftButtonHTML($expire_date, '', '', '2');
                        $expiration_date = $array_expire_dates['text'] . " " . $array_expire_dates['button'];

                        $deleted = "";
                        $buttons = "";
                        if ($data_up['deleted_y'] != '1' && strtotime ($expire_date) >= strtotime(date('Y-m-d'))) {
                            $downloads_active++;
                            $buttons = '<div><a href="'.$module->getUrl('hub/aws/AWS_downloadFile.php?NOAUTH&pid='.$pidsArray['PROJECTS'].'&code=' . \Vanderbilt\HarmonistHubExternalModule\getCrypt("id=". $data_up['record_id']."&pid=".$user,'e',$secret_key,$secret_iv) ). '" class="btn btn-primary btn-xs"><i class="fa fa-arrow-down"></i> Download</a></div>';
                        } else if ($data_up['deleted_y'] == '1' && $data_up['deletion_ts'] != ""){
                            if($data_up['deletion_type'] == '2'){
                                $RecordSetPeopleDelete = \REDCap::getData($pidsArray['PEOPLE'], 'array', array('record_id' => $data_up['deletion_hubuser']));
                                $person_info_delete = ProjectData::getProjectInfoArray($RecordSetPeopleDelete)[0];
                                $contact_person_delete = "<a href='mailto:" . $person_info['email'] . "'>" . $person_info_delete['firstname'] . " " . $person_info_delete['lastname'] . "</a>";

                                $deleted = '<div><i>File deleted by ' . $contact_person_delete . ' on '.$data_up['deletion_ts'].'</i></div>';
                                $expiration_date = "<span class='text-error'>".date("d M Y",strtotime($data_up['deletion_ts']))."</span>";
                            }else{
                                $expiration_date = "<span class='text-error'>".date("d M Y",strtotime($data_up['deletion_ts']))."</span>";
                                $deleted = '<div><i>File auto-deleted on ' . $data_up['deletion_ts'] . '</i></div>';
                            }
                        }else if(strtotime ($expire_date) >= strtotime(date('Y-m-d'))){

                        }

                        $notes = $data_up['upload_notes'];
                        $color_notes = "";
                        if ($data_up['upload_notes'] == "") {
                            $notes = "<i>No notes available</i>";
                            $color_notes = "retrieve-notes";
                        }

                        $body .= "<tr><td>" . $data_up['responsecomplete_ts'] . "</td>" .
                            "<td>" . $region_code . "</td>" .
                            "<td>" . $contact_person . "</td>" .
                            "<td>" . $data_up['data_upload_zip'] . $deleted . "</td>" .
                            "<td style='text-align: center;'>" . $file_pdf . "</td>" .
                            "<td>" . $expiration_date . "</td>" .
                            "<td>" . $buttons . "</td>" .
                            "<td>" . $notes . "</td></tr>" ;
                    }
                }

                $header = '
            <div class="panel panel-default" style="margin-bottom: 20px">
                <div class="panel-heading" style="height: 38px">
                    <h3 class="panel-title">
                        <a data-toggle="collapse" href="#collapse_concept_' . $concept_id . $sop_id . '">' . $concept_header . '</a>
                        <span style="float:right;padding-right: 30px;font-size: 14px"> ' . $array_dates['button'] . ' <span class="label label-as-badge btn-info" style="font-weight: normal;"><i class="fa fa-arrow-down"></i> '. $downloads_active . '</span></span>
                    </h3>
    
                </div>
    
                <div id="collapse_concept_' . $concept_id . $sop_id . '" class="panel-collapse collapse" aria-expanded="true">
                    <table class="table table_requests sortable-theme-bootstrap">
                        <div class="row request">
                            <div class="col-md-12 col-sm-12" style="padding-left: 30px"><strong>Title: </strong><a href="'.$module->getUrl('index.php?NOAUTH&pid=' . $pidsArray['PROJECTS'] . '&option=ttl&record=' . $concept_id) . '" target="_blank" alt="concept_link" style="color: #337ab7;">' . $concept_title . ' <i class="fa fa-external-link"></i></a> | <a href="'.$module->getUrl('index.php?pid='.$pidsArray['PROJECTS'].'&option=sop&record=' . $sop_id . '&type=r').'" target="_blank" alt="concept_link" style="color: #337ab7;">Data Request #' . $sop_id . ' <i class="fa fa-external-link"></i></a></div>
                        </div>
                        <div class="row request">
                            <div class="col-md-12 col-sm-12" style="padding-left: 30px"><strong>Data Contact: </strong>' . $contact_concept_person . '</div>
                        </div>
                        <div class="row request">
                            <div class="col-md-12 col-sm-12" style="padding-left: 30px"><strong>Data Due: ' . $array_dates['text'] . '</strong></div>
                        </div>
                        <div class="row request"></div>
                    </table>
                    <div class="table-responsive">
                    <table class="table table_requests sortable-theme-bootstrap dt-responsive child_notes" data-sortable id="sortable_table" width="100%">
                        <thead>
                        <tr>
                            <th class="sorted_class" width="160px" data-sorted="true" data-sorted-direction="descending">Upload Date</th>
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
    include(dirname(dirname(__FILE__))."/logout_popup.php");
}
?>