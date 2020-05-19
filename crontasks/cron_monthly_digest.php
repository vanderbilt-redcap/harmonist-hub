<?php
include_once(__DIR__ ."/../projects.php");
include_once __DIR__ ."/../functions.php";

$environment = "";
if(ENVIRONMENT == 'DEV' || ENVIRONMENT == 'TEST'){
    $environment = " ".ENVIRONMENT;
}

$request_type = $module->getChoiceLabels('request_type', IEDEA_RMANAGER);
$finalize_y = $module->getChoiceLabels('finalize_y', IEDEA_RMANAGER);

$RecordSetReq = \REDCap::getData(IEDEA_RMANAGER, 'array', null,null,null,null,false,false,false,"[approval_y] = 1");
$requests = getProjectInfoArray($RecordSetReq);
array_sort_by_column($requests, 'due_d',SORT_ASC);

$subject = $settings['hub_name']." Hub – Monthly Summary for ".date("F",strtotime("-1 months"))." ".date("Y",strtotime("-1 months")).$environment;
$email_req = "<div>".
    "<div>".$settings['hub_name']." Program Managers,</div><br>".
    "<div>This e-mail provides a summary of ".$settings['hub_name']." Hub activity for <strong>".date("F",strtotime("-1 months"))." ".date("Y",strtotime("-1 months"))."</strong>. This includes active Hub requests, Hub requests that have been finalized, and active data calls. If you have questions about the content of this e-mail, please e-mail <a href='mailto:".$settings['hub_contact_email']."'>".$settings['hub_contact_email']."</a>.</div><br><br>".
    "<div><h3><strong>Active Hub Requests</strong></h3></div>".
    "<ol style='padding-left: 15px;'>";
$isEmpty = true;
foreach ($requests as $req){
    if((!array_key_exists('finalize_y',$req) || $req['finalize_y'] == "") && $req['due_d'] != "" ){
        $isEmpty = false;
        $datetime = strtotime($req['due_d']);
        $today = strtotime(date("Y-m-d"));
        $interval = $datetime - $today;
        $days_passed = floor($interval / (60 * 60 * 24));

        if($datetime > $today){
            $date_color_text = "color:#5cb85c";
        }else{
            $date_color_text = "color:#e74c3c";
        }

        $email_req .= "<li style='padding-bottom: 15px;padding-left: 10px;'><div>Due: <span style='".$date_color_text."'>".$req['due_d']."</span></div>";

        $email_req .= "<div style='padding: 3px;'><strong>" . $request_type[$req['request_type']] . "</strong>";
        if(!empty($req['assoc_concept']) && $req['request_type'] != "1") {
            $RecordSetConceptSheets = \REDCap::getData(IEDEA_HARMONIST, 'array',  array('record_id' => $req['assoc_concept']));
            $concept = getProjectInfoArray($RecordSetConceptSheets)[0];
            $concept_sheet = $concept['concept_id'];
            $concept_title = $concept['concept_title'];
            $email_req .= ", ".$concept_sheet;
        }
        $email_req .= ", ".$req['contact_name']."</div>";

        $email_req .= "<div style='padding: 3px;'><a href='".$module->getUrl("index.php?pid=".IEDEA_PROJECTS."&option=hub&record=".$req['request_id'])."' target='_blank' alt='concept_link'>".$req['request_title']."</a></div>";
        $votes = array();
        foreach ($req['region_response_status'] as $region => $vote_status){
            if($vote_status != 0 && in_array($req['region_vote_status'],$req)){
                if($region == ""){
                    $region = "1";
                }
                $RecordSetRegions = \REDCap::getData(IEDEA_REGIONS, 'array',  array('record_id' => $region));
                $region_code = getProjectInfoArray($RecordSetRegions)[0]['region_code'];
                array_push($votes,$region_code);
            }
        }
        sort($votes);
        $email_req .= "<div style='padding: 3px;'>Votes received from: ";
        if(!empty($votes)){
            $email_req .= implode(', ',$votes);
        }else{
            $email_req .= "<em>None</em>";
        }
        $email_req .="</div></li>";
    }
}
if($isEmpty){
    $email_req .= "<li><em>No active hub requests.</em></li>";
}
$email_req .= "</ol>".
    "<br><div style='padding: 3px;'><h3><strong>Hub Requests Finalized in Past Month</strong></h3></div><ol style='padding-left: 15px;'>";

$numberDaysInCurrentMonth = cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'));
$expire_date = date('Y-m-d', strtotime(date('Y-m-d') ."-".$numberDaysInCurrentMonth." days"));
$RecordSetReq = \REDCap::getData(IEDEA_RMANAGER, 'array',null,null,null,null,false,false,false,"[finalize_y] <> '' and [final_d] <>'' and datediff ([final_d], '".$expire_date."', \"d\", true) <= 0");
$requests_hub = getProjectInfoArray($RecordSetReq);
array_sort_by_column($requests_hub, 'due_d',SORT_ASC);
$isEmpty = true;
foreach ($requests_hub as $req){
    if($req['final_d'] != "" ){
        $isEmpty = false;
        $email_req .= "<li style='padding-bottom: 15px;padding-left: 10px;'><div style='padding: 3px;'>Date finalized: ".$req['final_d']."</span></div>";

        $email_req .= "<div style='padding: 3px;'><strong>" . $request_type[$req['request_type']] . "</strong>";
        if(!empty($req['assoc_concept']) && $req['request_type'] != "1") {
            $RecordSetConceptSheets = \REDCap::getData(IEDEA_HARMONIST, 'array', array('record_id' => $req['assoc_concept']));
            $concept = getProjectInfoArray($RecordSetConceptSheets)[0];
            $concept_sheet = $concept['concept_id'];
            $concept_title = $concept['concept_title'];
            $email_req .= ", ".$concept_sheet;
        }
        $email_req .= ", ".$req['contact_name']."</div>";

        $email_req .= "<div style='padding: 3px;'><a href='".$module->getUrl("index.php?pid=".IEDEA_DATAMODEL."&option=hub&record=".$req['request_id'])."' target='_blank' alt='concept_link'>".$req['request_title']."</a></div>";

        if($req['finalize_y'] == "1"){
            $color_text = "color:#5cb85c";
        }else{
            $color_text = "color:#e74c3c";
        }
        $email_req .= "<div style='padding: 3px;'>Status: <span style='".$color_text."'>".$finalize_y[$req['finalize_y']]."</span></div>";
        $email_req .="</li>";
    }
}
if($isEmpty){
    $email_req .= "<li><em>No finalized hub requests.</em></li>";
}
$email_req .= "</ol>".
    "<br><div style='padding: 3px;'><h3><strong>Active  Data Calls</strong></h3></div><ol style='padding-left: 15px;'>";

$RecordSetSOP = \REDCap::getData(IEDEA_SOP, 'array', null,null,null,null,false,false,false,"[sop_active] = 1 && [sop_finalize_y] = 1");
$sops = getProjectInfoArray($RecordSetSOP);
array_sort_by_column($sops, 'due_d',SORT_ASC);
$isEmpty = true;
$RecordSetRegions = \REDCap::getData(IEDEA_SOP, 'array', null,null,null,null,false,false,false,"[showregion_y] = 1");
$regions = getProjectInfoArray($RecordSetRegions);
foreach ($sops as $sop){
    if((!array_key_exists('sop_closed_y',$sop) || $sop['sop_closed_y'][0] == "") && $sop['sop_due_d'] != ""){
        $isEmpty = false;
        if (!empty($sop['sop_concept_id'])) {
            $datetime = strtotime($sop['sop_due_d']);
            $today = strtotime(date("Y-m-d"));
            $interval = $datetime - $today;
            $days_passed = floor($interval / (60 * 60 * 24));

            if ($datetime > $today) {
                $date_color_text = "color:#1F8B4D";
            } else {
                $date_color_text = "color:#e74c3c";
            }
            $RecordSetConceptSheets = \REDCap::getData(IEDEA_HARMONIST, 'array', array('record_id' => $sop['sop_concept_id']));
            $concept = getProjectInfoArray($RecordSetConceptSheets)[0];
            $concept_sheet = $concept['concept_id'];
            $concept_title = $concept['concept_title'];

            $email_req .= "<li style='padding-bottom: 15px;padding-left: 10px;'><div style='padding: 3px;'><strong>Due:</strong> <span style='$date_color_text'>" . $sop['sop_due_d'] . "</span></span></div>";
        }

        $email_req .= "<div style='padding: 3px;'><a href='" . $module->getUrl("index.php?pid=" . IEDEA_DATAMODEL . "&option=hub&record=" . $sop['request_id']) . "' target='_blank' alt='concept_link'>" . $sop['request_title'] . "</a></div>";
        $RecordSetCreator = \REDCap::getData(IEDEA_PEOPLE, 'array', array('record_id' => $sop['sop_creator']));
        $creator = getProjectInfoArray($RecordSetCreator)[0];
        $RecordSetCreator2 = \REDCap::getData(IEDEA_PEOPLE, 'array', array('record_id' => $sop['sop_creator2']));
        $creator2 = getProjectInfoArray($RecordSetCreator2)[0];
        $RecordSetDataContact = \REDCap::getData(IEDEA_PEOPLE, 'array', array('record_id' => $sop['sop_creator2']));
        $datacontact = getProjectInfoArray($RecordSetDataContact)[0];
        $data_contact = $datacontact['firstname'] . " " . $datacontact['lastname'];
        $sop_creator = $creator['firstname'] . " " . $creator['lastname'];
        $sop_creator2 = $creator2['firstname'] . " " . $creator2['lastname'];
        $sop_people = $sop_creator;
        if ($creator['lastname'] != "" && $creator['lastname'] != "") {
            $sop_people .= ", " . $sop_creator2;
        }
        if (($creator['lastname'] != "" || $creator2['lastname'] != "") && $datacontact['lastname'] != "") {
            $sop_people .= ", " . $data_contact;
        }
        $sop_people_all = implode(', ',array_unique(explode(', ' , $sop_people)));

        $email_req .= "<div style='padding: 3px;'><strong>Data Request for " . $concept_sheet . ", </strong>" . $sop_people_all . "</div>";
        $email_req .= "<div style='padding: 3px;'><a href='".$module->getUrl("index.php?pid=".IEDEA_PROJECTS."&option=sop&record=".$sop['record_id']). "'>" . $sop['sop_name'] . "</a></div>";

        $votes = array();
        foreach ($regions as $region){
            if($sop['data_response_status'][$region['record_id']] == "1" || $sop['data_response_status'][$region['record_id']] == "2") {
                array_push($votes, $region['region_code']);
            }
        }

        sort($votes);
        $email_req .= "<div style='padding: 3px;'>Data received from: ";
        if (!empty($votes)) {
            $email_req .= implode(', ', $votes);
        } else {
            $email_req .= "<em>None</em>";
        }
        $email_req .= "</div></li>";
    }
}
if($isEmpty){
    $email_req .= "<li><em>No active data calls.</em></li>";
}
$email_req .= "</ol></div>";

if($settings['hub_subs_monthly_digest'] != "") {
    $emails = explode(';', $settings['hub_subs_monthly_digest']);
    foreach ($emails as $email) {
        sendEmail($email, 'noreply@vumc.org', $settings['accesslink_sender_name'], $subject, $email_req, "Not in database");
    }
}

?>