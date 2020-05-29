<?php
include_once(__DIR__ ."/../projects.php");
include_once __DIR__ ."/../functions.php";

$RecordSetDU = \REDCap::getData(IEDEA_DATAUPLOAD, 'array', null);
$request_DU = getProjectInfoArray($RecordSetDU);

$today = date('Y-m-d');
$days_expiration = intval($settings['uploadnotification_dur']);
$extra_days = ' + ' . $days_expiration. " days";

foreach ($request_DU as $upload){
    if(!array_key_exists('emails_sent_y',$upload) || $upload['emails_sent_y'][1] == '0'){
        //Save data on project
        $Proj = new \Project(IEDEA_DATAUPLOAD);
        $event_id = $Proj->firstEventId;
        $arraySaveDU = array();
        $arraySaveDU[$upload['record_id']][$event_id]['emails_sent_y'] = array(1=>"1");//checkbox
        $results = \Records::saveData(IEDEA_DATAUPLOAD, 'array', $arraySaveDU,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
        \Records::addRecordToRecordListCache(IEDEA_DATAUPLOAD, $upload['emails_sent_y'],1);

        $expired_date = date('Y-m-d', strtotime($upload['responsecomplete_ts'] . $extra_days));
        if(strtotime($expired_date) >= strtotime($today)) {
            #Uploader email
            $RecordSetPeople = \REDCap::getData(IEDEA_PEOPLE, 'array', array('record_id' => $upload['data_upload_person']));
            $people = getProjectInfoArray($RecordSetPeople)[0];
            $to = $people['email'];
            $firstname = $people['firstname'];
            $name_uploader = $people['firstname'] . " " . $people['lastname'];
            $RecordSetRegionsUp = \REDCap::getData(IEDEA_REGIONS, 'array', array('record_id' => $people['person_region']));
            $region_code_uploader = getProjectInfoArray($RecordSetRegionsUp)[0]['region_code'];

            $RecordSetConcepts = \REDCap::getData(IEDEA_HARMONIST, 'array', array('record_id' => $upload['data_assoc_concept']));
            $concept_id = getProjectInfoArray($RecordSetConcepts)[0]['concept_id'];

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

                foreach ($downloadersOrdered as $downO) {
                    $downloaders_list .= "<li>" . $downO['name'] . " " . $downO['region_code'] . ", <a href='mailto:" . $downO['email'] . "'>" . $downO['email'] . "</a></li>";
                }
                $downloaders_list .= "</ol>";
            }

            $date = new DateTime($upload['responsecomplete_ts']);
            $date->modify("+1 hours");
            $date_time = $date->format("Y-m-d H:i");
            $extra_days = ' + ' . $settings['retrievedata_expiration'] . " days";
            $expire_date = date('Y-m-d', strtotime($date_time . $extra_days));

            $subject = "Successful ".$settings['hub_name']." data upload for " . $concept_id;
            $message = "<div>Dear " . $firstname . ",</div><br/><br/>" .
                "<div>Thank you for submitting your dataset to secure cloud storage in response to <strong><a href='" . $module->getUrl("index.php?pid=".IEDEA_PROJECTS."&option=sop&record=" . $upload['data_assoc_request']) . "' target='_blank'>" . $concept_id . "</a></strong> on <b>" . $date_time . "</b> Eastern US Time (ET). </div><br/>" .
                "<div>You may log into the ".$settings['hub_name']." Hub and view the <a href='" . $module->getUrl("index.php?pid=".IEDEA_PROJECTS."&option=slgd") . "' target='_blank'>Data Activity Log</a> report, track downloads, and delete your dataset. Your dataset will be available for " .
                "download by the approved data downloaders <strong>until " . $expire_date . " 23:59</strong> ET unless you choose to delete it before then. </div><br/>" .
                "<div>Approved Data Downloaders:</div>" .
                $downloaders_list . "<br/>" .
                "<span style='color:#777'>Please email <a href='mailto:".$settings['hub_contact_email']."'>".$settings['hub_contact_email']."</a> with any questions.</span>";

            sendEmail($to, $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], $subject, $message, $upload['data_upload_person']);

            #Data Downloaders email
            if ($downloadersOrdered != "") {
                $date = new DateTime($upload['responsecomplete_ts']);
                $date->modify("+1 hours");
                $date_time = $date->format("Y-m-d H:i");
                $extra_days = ' + ' . $settings['retrievedata_expiration'] . " days";
                $expire_date = date('Y-m-d', strtotime($date_time . $extra_days));

                $sop_short_name = explode(',', $sop['sop_name'])[0];

                $subject = "New ".$settings['hub_name']." " . $concept_id . " dataset available for download";

                foreach ($downloadersOrdered as $down) {
                    $message = "<div>Dear " . $down['firstname'] . ",</div><br/><br/>" .
                        "<div>A new dataset has been submitted to secure cloud storage by <strong>" . $name_uploader . "</strong> from <strong>" . $region_code_uploader . "</strong> in response to \"" . $sop['sop_name'] . "\" for concept <b>" . $concept_id . "</b>. The upload was received at " . $date_time . " Eastern US Time (ET). </div><br/>" .
                        "<div>The data will be available to download until <span style='color:red;font-weight: bold'>" . $expire_date . " 23:59 ET</span>.</div><br/>" .
                        "<div>To download the dataset, log in to the ".$settings['hub_name']." Hub and select <strong>Retrieve Data on the <a href='" .$module->getUrl("index.php?pid=".IEDEA_PROJECTS."&option=dat")."' target='_blank'>Data page</a></strong>. " .
                        "A summary report for the dataset is also available on that page. The dataset will be deleted on " . $expire_date . " 23:59 ET</div><br/>" .
                        "<span style='color:#777'>Please email <a href='mailto:".$settings['hub_contact_email']."'>".$settings['hub_contact_email']."</a> with any questions.</span>";

                    sendEmail($down['email'], $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], $subject, $message, $down['id']);
                }
            }
        }
    }
}

?>