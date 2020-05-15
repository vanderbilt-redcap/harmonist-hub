<?php
include_once(__DIR__ ."/../projects.php");
include_once __DIR__ ."/../functions.php";

$RecordSetDU = \REDCap::getData(IEDEA_DATAUPLOAD, 'array', null);
$request_DU = getProjectInfoArray($RecordSetDU);

$today = date('Y-m-d');
$days_expiration = intval($settings['downloadreminder_dur']);
$expire_number = $settings['retrievedata_expiration'] - $days_expiration;
$extra_days = ' + ' . $expire_number. " days";
$days_expiration2 = intval($settings['downloadreminder2_dur']);
$expire_number2 = $settings['retrievedata_expiration'] - $days_expiration2;
$extra_days2 = ' + ' . $expire_number2. " days";

$days_expiration_delete = intval($settings['retrievedata_expiration']);
$extra_days_delete = ' + ' . $days_expiration_delete. " days";
foreach ($request_DU as $upload){
    if(!array_key_exists('deleted_y', $upload) || $upload['deleted_y'] != '1') {
        $expired_date_delete = date('Y-m-d', strtotime($upload['responsecomplete_ts'] . $extra_days_delete));
        $expired_date_reminder = date('Y-m-d', strtotime($upload['responsecomplete_ts'] . $extra_days));
        $expired_date_reminder2 = date('Y-m-d', strtotime($upload['responsecomplete_ts'] . $extra_days2));
        if (strtotime($expired_date_reminder) == strtotime($today) || strtotime($expired_date_reminder2) == strtotime($today)) {
            $RecordSetSOP = \REDCap::getData(IEDEA_SOP, 'array', array('record_id' => $upload['data_assoc_request']));
            $sop = getProjectInfoArray($RecordSetSOP)[0];
            $downloaders_list = "";
            if ($sop['sop_downloaders'] != "") {
                $downloaders = explode(',', $sop['sop_downloaders']);
                $number_downloaders = count($downloaders);
                $downloaders_list = "<ol>";
                $downloadersOrdered = array();
                foreach ($downloaders as $down) {
                    $RecordSetPeopleDown = \REDCap::getData(IEDEA_PEOPLE, 'array', array('record_id' => $down));
                    $peopleDown = getProjectInfoArray($RecordSetPeopleDown)[0];
                    $RecordSetRegionsLoginDown = \REDCap::getData(IEDEA_REGIONS, 'array', array('record_id' => $peopleDown['person_region']));
                    $region_codeDown = getProjectInfoArray($RecordSetRegionsLoginDown)[0]['region_code'];

                    $downloadersOrdered[$down]['name'] = $peopleDown['firstname'] . " " . $peopleDown['lastname'];
                    $downloadersOrdered[$down]['email'] = $peopleDown['email'];
                    $downloadersOrdered[$down]['region_code'] = "(" . $region_codeDown . ")";
                    $downloadersOrdered[$down]['id'] = $peopleDown['record_id'];
                    $downloadersOrdered[$down]['firstname'] = $peopleDown['firstname'];
                }
                array_sort_by_column($downloadersOrdered, 'name');

                $date = new DateTime($upload['responsecomplete_ts']);
                $date->modify("+1 hours");
                $date_time = $date->format("Y-m-d H:i");

                $RecordSetPeople = \REDCap::getData(IEDEA_PEOPLE, 'array', array('record_id' => $upload['data_upload_person']));
                $people = getProjectInfoArray($RecordSetPeople)[0];
                $name_uploader = $people['firstname'] . " " . $people['lastname'];
                $RecordSetRegionsUp = \REDCap::getData(IEDEA_REGIONS, 'array', array('record_id' => $people['person_region']));
                $region_code_uploader = getProjectInfoArray($RecordSetRegionsUp)[0]['region_code'];

                $RecordSetConcepts = \REDCap::getData(IEDEA_REGIONS, 'array', array('record_id' => $upload['data_assoc_concept']));
                $concepts = getProjectInfoArray($RecordSetConcepts)[0];
                $concept_id = $concepts['concept_id'];
                $concept_title = $concepts['concept_title'];

                $RecordSetDOWN = \REDCap::getData(IEDEA_DATADOWNLOAD, 'array',null,null,null,null,false,false,false,"[download_id] = ".$upload['record_id']);
                $downloads = getProjectInfoArray($RecordSetDOWN);
                if(empty($downloads)){
                    foreach ($downloadersOrdered as $down) {
                        sendExpReminder($module,$sop,$down,$upload,$expired_date_reminder,$expired_date_reminder2,$today,$expired_date_delete,$name_uploader,$region_code_uploader,$concept_id,$concept_title,$date_time,$settings);
                        \REDCap::logEvent("Reminder Sent<br/>Record ".$upload['record_id'],"No downloads yet from any downloaders.\n",null,null,null,IEDEA_DATAUPLOAD);
                    }
                }else{
                    foreach ($downloadersOrdered as $down) {
                        $email_sent = false;
                        foreach ($downloads as $download) {
                            if ($upload['record_id'] == $download['download_id'] && $down['id'] == $download['downloader_id']) {
                                $email_sent = true;
                            }
                        }
                        if(!$email_sent){
                            #Not downloaded any file
                            sendExpReminder($module,$sop,$down,$upload,$expired_date_reminder,$expired_date_reminder2,$today,$expired_date_delete,$name_uploader,$region_code_uploader,$concept_id,$concept_title,$date_time,$settings);
                        }
                    }
                }
            }
        }
    }
}

function sendExpReminder($module,$sop,$down,$upload,$expired_date_reminder,$expired_date_reminder2,$today,$expired_date_delete,$name_uploader,$region_code_uploader,$concept_id,$concept_title,$date_time,$settings){
    if (strtotime($expired_date_reminder) == strtotime($today)) {
        $subject = $settings['hub_name']." Data Request for " . $concept_id . " download expires on " . $expired_date_delete;
        $message = "<div>Dear " . $down['firstname'] . ",</div><br/><br/>" .
            "<div>This is a reminder that you have not downloaded the dataset that was submitted to secure cloud storage by&nbsp;<strong>" . $name_uploader . "</strong> from&nbsp;<strong>" . $region_code_uploader . "</strong> in response to your data request \"" . $concept_title . "\" for concept&nbsp;<b>" . $concept_id . "</b>, <i>Draft ID: ".$sop['record_id']."</i>. The upload was received at " . $date_time . " Eastern US Time (ET). </div><br/>" .
            "<div>The dataset will be deleted on&nbsp;<strong><span style='color:red;'>" . $expired_date_delete . " 23:59 ET (" . $settings['downloadreminder_dur'] . " days)</span></strong>.</div><br/>" .
            "<div>To download the dataset, log in to the ".$settings['hub_name']." Hub and select&nbsp;<strong>Retrieve Data on the <a href='" .$module->getUrl("index.php?pid=".IEDEA_PROJECTS."&option=dat") . "' target='_blank'>Data page</a></strong>. " .
            "A summary report for the dataset is also available on that page.</div><br/>" .
            "<span style='color:#777'>Please email <a href='mailto:".$settings['hub_contact_email']."'>".$settings['hub_contact_email']."</a> with any questions.</span>";
        $reminder_num = $settings['downloadreminder_dur'];
    } else if (strtotime($expired_date_reminder2) == strtotime($today)) {
        $subject = $settings['hub_name']." Data Request for " . $concept_id . " download expires on " . $expired_date_delete;
        $message = "<div>Dear " . $down['firstname'] . ",</div><br/><br/>" .
            "<div>This is a reminder that you have not downloaded the dataset that was submitted to secure cloud storage by&nbsp;<strong>" . $name_uploader . "</strong> from&nbsp;<strong>" . $region_code_uploader . "</strong> in response to your data request \"" . $concept_title . "\" for concept&nbsp;<b>" . $concept_id . "</b>, <i>Draft ID: ".$sop['record_id']."</i>. The upload was received at " . $date_time . " Eastern US Time (ET). </div><br/>" .
            "<div>The dataset will be deleted on&nbsp;<strong><span style='color:red;'>" . $expired_date_delete . " 23:59 ET (" . $settings['downloadreminder2_dur'] . " days)</span></strong>.</div><br/>" .
            "<div>To download the dataset, log in to the ".$settings['hub_name']." Hub and select&nbsp;<strong>Retrieve Data on the <a href='" .$module->getUrl("index.php?pid=".IEDEA_PROJECTS."&option=dat")."' target='_blank'>Data page</a></strong>. " .
            "A summary report for the dataset is also available on that page.</div><br/>" .
            "<div>This is the final reminder for this dataset.</div><br/>" .
            "<span style='color:#777'>Please email <a href='mailto:".$settings['hub_contact_email']."'>".$settings['hub_contact_email']."</a> with any questions.</span>";
        $reminder_num = $settings['downloadreminder2_dur'];
    }
    sendEmail($down['email'], $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], $subject, $message, $down['id']);
    \REDCap::logEvent("Reminder Sent<br/>Record ".$upload['record_id'],$reminder_num." days reminder \nTo: ".$down['email']."\nConcept ID: ".$concept_id."\n",null,null,null,IEDEA_DATAUPLOAD);
}
?>