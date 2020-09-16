<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include_once(__DIR__ ."/../projects.php");

class AllCrons
{
    public static function runCronDataUploadExpirationReminder($module, $upload, $sop, $peopleDown, $extra_days_delete, $extra_days, $extra_days2, $settings, $email = false)
    {
        $messageArray = array();
        $expired_date = self::sendEmailToday($upload, $extra_days_delete, $extra_days, $extra_days2);
        if ($expired_date != null) {
            if ($sop['sop_downloaders'] != "") {
                $downloaders = explode(',', $sop['sop_downloaders']);
                $number_downloaders = count($downloaders);
                $messageArray['numDownloaders'] = $number_downloaders;

                $downloadersOrdered = array();
                foreach ($downloaders as $down) {
                    if($peopleDown == null) {
                        $RecordSetPeopleDown = \REDCap::getData(IEDEA_PEOPLE, 'array', array('record_id' => $down));
                        $peopleDownData = getProjectInfoArray($RecordSetPeopleDown)[0];
                        $RecordSetRegionsLoginDown = \REDCap::getData(IEDEA_REGIONS, 'array', array('record_id' => $peopleDown['person_region']));
                        $region_codeDown = getProjectInfoArray($RecordSetRegionsLoginDown)[0]['region_code'];
                    }else{
                        $region_codeDown = "TT";
                        $peopleDownData = $peopleDown[$down];
                    }
                    $downloadersOrdered = self::getDownloadersOrdered($down, $downloadersOrdered, $peopleDownData, $region_codeDown);
                }
                array_sort_by_column($downloadersOrdered, 'name');

                $date = new \DateTime($upload['responsecomplete_ts']);
                $date->modify("+1 hours");
                $date_time = $date->format("Y-m-d H:i");

                $RecordSetPeople = \REDCap::getData(IEDEA_PEOPLE, 'array', array('record_id' => $upload['data_upload_person']));
                $people = getProjectInfoArray($RecordSetPeople)[0];
                $name_uploader = $people['firstname'] . " " . $people['lastname'];
                $RecordSetRegionsUp = \REDCap::getData(IEDEA_REGIONS, 'array', array('record_id' => $people['person_region']));
                $region_code_uploader = getProjectInfoArray($RecordSetRegionsUp)[0]['region_code'];

                $RecordSetConcepts = \REDCap::getData(IEDEA_HARMONIST, 'array', array('record_id' => $upload['data_assoc_concept']));
                $concepts = getProjectInfoArray($RecordSetConcepts)[0];
                $concept_id = $concepts['concept_id'];
                $concept_title = $concepts['concept_title'];

                $messageArray['concept_title'] = $concept_title;
                $messageArray['sop_id'] = $sop['record_id'];

                $RecordSetDOWN = \REDCap::getData(IEDEA_DATADOWNLOAD, 'array', null, null, null, null, false, false, false, "[download_id] = " . $upload['record_id']);
                $downloads = getProjectInfoArray($RecordSetDOWN);
                if (empty($downloads)) {
                    foreach ($downloadersOrdered as $down) {
                        $messageArray = self::sendExpReminder($module, $sop, $down, $upload, $expired_date['reminder'], $expired_date['reminder2'], $expired_date['delete'], $name_uploader, $region_code_uploader, $concept_id, $concept_title, $date_time, $settings, $email, $messageArray);
                        if(!$email) {
                            \REDCap::logEvent("Reminder Sent<br/>Record " . $upload['record_id'], "No downloads yet from any downloaders.\n", null, null, null, IEDEA_DATAUPLOAD);
                        }
                    }
                } else {
                    foreach ($downloadersOrdered as $down) {
                        $email_sent = false;
                        foreach ($downloads as $download) {
                            if ($upload['record_id'] == $download['download_id'] && $down['id'] == $download['downloader_id']) {
                                $email_sent = true;
                            }
                        }
                        if (!$email_sent) {
                            #Not downloaded any file
                            $messageArray = AllCrons::sendExpReminder($module, $sop, $down, $upload, $expired_date['reminder'], $expired_date['reminder2'], $expired_date['delete'], $name_uploader, $region_code_uploader, $concept_id, $concept_title, $date_time, $settings, $email, $messageArray);
                            $messageArray['notdownloaded'] += 1;
                        }
                    }
                }
            }
        }
        return $messageArray;
    }

    public static function runCronDataUploadNotification($module, $upload, $sop, $peopleDown, $extra_days, $settings, $email = false)
    {
        $messageArray = array();
        $expired_date = date('Y-m-d', strtotime($upload['responsecomplete_ts'] . $extra_days));
        if(strtotime($expired_date) >= strtotime(date('Y-m-d'))) {
            if(!array_key_exists('emails_sent_y',$upload) || $upload['emails_sent_y'][1] == '0') {
                if($email) {
                    //Save data on project
                    $Proj = new \Project(IEDEA_DATAUPLOAD);
                    $event_id = $Proj->firstEventId;
                    $arraySaveDU = array();
                    $arraySaveDU[$upload['record_id']][$event_id]['emails_sent_y'] = array(1 => "1");//checkbox
                    $results = \Records::saveData(IEDEA_DATAUPLOAD, 'array', $arraySaveDU, 'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
                    \Records::addRecordToRecordListCache(IEDEA_DATAUPLOAD, $upload['emails_sent_y'], 1);
                }

                $downloaders_list = "";
                if ($sop['sop_downloaders'] != "") {
                    $downloaders = explode(',', $sop['sop_downloaders']);
                    $number_downloaders = count($downloaders);
                    $messageArray['numDownloaders'] = $number_downloaders;

                    $downloadersOrdered = array();
                    foreach ($downloaders as $down) {
                        if ($peopleDown == null) {
                            $RecordSetPeopleDown = \REDCap::getData(IEDEA_PEOPLE, 'array', array('record_id' => $down));
                            $peopleDownData = getProjectInfoArray($RecordSetPeopleDown)[0];
                            $RecordSetRegionsLoginDown = \REDCap::getData(IEDEA_REGIONS, 'array', array('record_id' => $peopleDown['person_region']));
                            $region_codeDown = getProjectInfoArray($RecordSetRegionsLoginDown)[0]['region_code'];
                        } else {
                            $region_codeDown = "TT";
                            $peopleDownData = $peopleDown[$down];
                        }
                        $downloadersOrdered = self::getDownloadersOrdered($down, $downloadersOrdered, $peopleDownData, $region_codeDown);
                    }
                    array_sort_by_column($downloadersOrdered, 'name');

                    foreach ($downloadersOrdered as $downO) {
                        $downloaders_list .= "<li>" . $downO['name'] . " " . $downO['region_code'] . ", <a href='mailto:" . $downO['email'] . "'>" . $downO['email'] . "</a></li>";
                    }
                    $downloaders_list .= "</ol>";
                }

                #Uploader email
                $RecordSetPeople = \REDCap::getData(IEDEA_PEOPLE, 'array', array('record_id' => $upload['data_upload_person']));
                $people = getProjectInfoArray($RecordSetPeople)[0];
                $to = $people['email'];
                $firstname = $people['firstname'];
                $name_uploader = $people['firstname'] . " " . $people['lastname'];
                $RecordSetRegionsUp = \REDCap::getData(IEDEA_REGIONS, 'array', array('record_id' => $people['person_region']));
                $region_code_uploader = getProjectInfoArray($RecordSetRegionsUp)[0]['region_code'];

                $RecordSetConcepts = \REDCap::getData(IEDEA_HARMONIST, 'array', array('record_id' => $upload['data_assoc_concept']));
                $concept_id = getProjectInfoArrayRepeatingInstruments($RecordSetConcepts)[0]['concept_id'];

                $date = new \DateTime($upload['responsecomplete_ts']);
                $date->modify("+1 hours");
                $date_time = $date->format("Y-m-d H:i");
                $expire_date = date('Y-m-d', strtotime($date_time . $extra_days));

                if ($email) {
                    $subject = "Successful " . $settings['hub_name'] . " data upload for " . $concept_id;
                    $message = "<div>Dear " . $firstname . ",</div><br/><br/>" .
                        "<div>Thank you for submitting your dataset to secure cloud storage in response to <strong><a href='" . $module->getUrl("index.php?pid=" . IEDEA_PROJECTS . "&option=sop&record=" . $upload['data_assoc_request']) . "' target='_blank'>" . $concept_id . "</a></strong> on <b>" . $date_time . "</b> Eastern US Time (ET). </div><br/>" .
                        "<div>You may log into the " . $settings['hub_name'] . " Hub and view the <a href='" . $module->getUrl("index.php?pid=" . IEDEA_PROJECTS . "&option=slgd") . "' target='_blank'>Data Activity Log</a> report, track downloads, and delete your dataset. Your dataset will be available for " .
                        "download by the approved data downloaders <strong>until " . $expire_date . " 23:59</strong> ET unless you choose to delete it before then. </div><br/>" .
                        "<div>Approved Data Downloaders:</div>" .
                        $downloaders_list . "<br/>" .
                        "<span style='color:#777'>Please email <a href='mailto:" . $settings['hub_contact_email'] . "'>" . $settings['hub_contact_email'] . "</a> with any questions.</span>";

                    sendEmail($to, $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], $subject, $message, $upload['data_upload_person']);

                }
                #Data Downloaders email
                if ($downloadersOrdered != "") {
                    $date = new \DateTime($upload['responsecomplete_ts']);
                    $date->modify("+1 hours");
                    $date_time = $date->format("Y-m-d H:i");
                    $extra_days = ' + ' . $settings['retrievedata_expiration'] . " days";
                    $expire_date = date('Y-m-d', strtotime($date_time . $extra_days));

                    $subject = "New " . $settings['hub_name'] . " " . $concept_id . " dataset available for download";

                    foreach ($downloadersOrdered as $down) {
                        if ($email) {
                            $message = "<div>Dear " . $down['firstname'] . ",</div><br/><br/>" .
                                "<div>A new dataset has been submitted to secure cloud storage by <strong>" . $name_uploader . "</strong> from <strong>" . $region_code_uploader . "</strong> in response to \"" . $sop['sop_name'] . "\" for concept <b>" . $concept_id . "</b>. The upload was received at " . $date_time . " Eastern US Time (ET). </div><br/>" .
                                "<div>The data will be available to download until <span style='color:red;font-weight: bold'>" . $expire_date . " 23:59 ET</span>.</div><br/>" .
                                "<div>To download the dataset, log in to the " . $settings['hub_name'] . " Hub and select <strong>Retrieve Data on the <a href='" . $module->getUrl("index.php?pid=" . IEDEA_PROJECTS . "&option=dat") . "' target='_blank'>Data page</a></strong>. " .
                                "A summary report for the dataset is also available on that page. The dataset will be deleted on " . $expire_date . " 23:59 ET</div><br/>" .
                                "<span style='color:#777'>Please email <a href='mailto:" . $settings['hub_contact_email'] . "'>" . $settings['hub_contact_email'] . "</a> with any questions.</span>";

                            sendEmail($down['email'], $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], $subject, $message, $down['id']);
                        }
                    }
                }
            }
        }
        return $messageArray;
    }

    public static function runCronMonthlyDigest($module, $requests, $requests_hub, $sops, $settings, $email = false){
        $environment = "";
        if(ENVIRONMENT == 'DEV' || ENVIRONMENT == 'TEST'){
            $environment = " ".ENVIRONMENT;
        }

        $request_type = $module->getChoiceLabels('request_type', IEDEA_RMANAGER);
        $finalize_y = $module->getChoiceLabels('finalize_y', IEDEA_RMANAGER);

        $subject = $settings['hub_name']." Hub – Monthly Summary for ".date("F",strtotime("-1 months"))." ".date("Y",strtotime("-1 months")).$environment;
        $email_req = "<div>".
            "<div>".$settings['hub_name']." Program Managers,</div><br>".
            "<div>This e-mail provides a summary of ".$settings['hub_name']." Hub activity for <strong>".date("F",strtotime("-1 months"))." ".date("Y",strtotime("-1 months"))."</strong>. This includes active Hub requests, Hub requests that have been finalized, and active data calls. If you have questions about the content of this e-mail, please e-mail <a href='mailto:".$settings['hub_contact_email']."'>".$settings['hub_contact_email']."</a>.</div><br><br>".
            "<div><h3><strong>Active Hub Requests</strong></h3></div>".
            "<ol style='padding-left: 15px;'>";
        $isEmpty = true;
        $message = array();
        $message['active_requests'] = 0;
        $message['requests_finalized'] = 0;
        $message['active_data_calls'] = 0;
        foreach ($requests as $req){
            if((!array_key_exists('finalize_y',$req) || $req['finalize_y'] == "") && $req['due_d'] != "" ){
                $message['active_requests'] = $message['active_requests'] + 1;
                $isEmpty = false;
                $datetime = strtotime($req['due_d']);
                $today = strtotime(date("Y-m-d"));
                $interval = $datetime - $today;
                $days_passed = floor($interval / (60 * 60 * 24));

                if($datetime > $today){
                    $date_color_text = "color:#1F8B4D";
                }else{
                    $date_color_text = "color:#e74c3c";
                }

                $email_req .= "<li style='padding-bottom: 15px;padding-left: 10px;'><div><strong>Due: <span style='".$date_color_text."'>".$req['due_d']."</span></strong> </div>";

                $email_req .= "<div style='padding: 3px;'><strong>" . $request_type[$req['request_type']] . "</strong>";
                if(!empty($req['assoc_concept']) && $req['request_type'] != "1") {
                    $RecordSetConceptSheets = \REDCap::getData(IEDEA_HARMONIST, 'array',  array('record_id' => $req['assoc_concept']));
                    $concept = getProjectInfoArrayRepeatingInstruments($RecordSetConceptSheets)[0];
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
        $isEmpty = true;
        foreach ($requests_hub as $req){
            if($req['final_d'] != "" ){
                $message['requests_finalized'] = $message['requests_finalized'] + 1;
                $isEmpty = false;
                $email_req .= "<li style='padding-bottom: 15px;padding-left: 10px;'><div style='padding: 3px;'>Date finalized: ".$req['final_d']."</span></div>";

                $email_req .= "<div style='padding: 3px;'><strong>" . $request_type[$req['request_type']] . "</strong>";
                if(!empty($req['assoc_concept']) && $req['request_type'] != "1") {
                    $RecordSetConceptSheets = \REDCap::getData(IEDEA_HARMONIST, 'array', array('record_id' => $req['assoc_concept']));
                    $concept = getProjectInfoArrayRepeatingInstruments($RecordSetConceptSheets)[0];
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
            "<br><div style='padding: 3px;'><h3><strong>Active Data Calls</strong></h3></div><ol style='padding-left: 15px;'>";

        $isEmpty = true;
        $RecordSetRegions = \REDCap::getData(IEDEA_SOP, 'array', null,null,null,null,false,false,false,"[showregion_y] = 1");
        $regions = getProjectInfoArrayRepeatingInstruments($RecordSetRegions);
        foreach ($sops as $sop){
            if((!array_key_exists('sop_closed_y',$sop) || $sop['sop_closed_y'][0] == "") && $sop['sop_due_d'] != ""){
                $message['active_data_calls'] = $message['active_data_calls'] + 1;
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
                    $concept = getProjectInfoArrayRepeatingInstruments($RecordSetConceptSheets)[0];
                    $concept_sheet = $concept['concept_id'];
                    $concept_title = $concept['concept_title'];

                    $email_req .= "<li style='padding-bottom: 15px;padding-left: 10px;'><div style='padding: 3px;'><strong>Due: <span style='$date_color_text'>" . $sop['sop_due_d'] . "</span></strong></span></div>";
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

        if($email) {
            if ($settings['hub_subs_monthly_digest'] != "") {
                $emails = explode(';', $settings['hub_subs_monthly_digest']);
                foreach ($emails as $email) {
                    sendEmail($email, 'noreply@vumc.org', $settings['accesslink_sender_name'], $subject, $email_req, "Not in database");
                }
            }
        }
        $message['code_test'] = 1;
        return $message;
    }

    public static function runCronDeleteAws($module, $s3, $upload, $sop, $peopleDown, $expired_date, $settings, $email = false){
        if((!array_key_exists('deleted_y',$upload) || $upload['deleted_y'] != "1") && strtotime($expired_date) <= strtotime(date('Y-m-d'))){
            try {
                if($email) {
                    #Delete the object
                    $result = $s3->deleteObject(array(
                        'Bucket' => $upload['data_upload_bucket'],
                        'Key' => $upload['data_upload_folder'] . $upload['data_upload_zip']
                    ));

                    //Save data on project
                    $Proj = new \Project(IEDEA_DATAUPLOAD);
                    $event_id = $Proj->firstEventId;
                    $recordSaveDU = array();
                    $recordSaveDU[$upload['record_id']][$event_id]['record_id'] = $upload['record_id'];
                    $recordSaveDU[$upload['record_id']][$event_id]['deletion_type'] = "1";
                    $date = new DateTime();
                    $recordSaveDU[$upload['record_id']][$event_id]['deletion_ts'] = $date->format('Y-m-d H:i:s');
                    $recordSaveDU[$upload['record_id']][$event_id]['deletion_rs'] = "Expired. Deleted automatically";
                    $recordSaveDU[$upload['record_id']][$event_id]['deletion_information_complete'] = "2";
                    $recordSaveDU[$upload['record_id']][$event_id]['deleted_y'] = "1";
                    $results = \Records::saveData(IEDEA_DATAUPLOAD, 'array', $recordSaveDU, 'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
                    \Records::addRecordToRecordListCache(IEDEA_DATAUPLOAD, $upload['record_id'], 1);

                    #EMAIL NOTIFICATION
                    $RecordSetConcepts = \REDCap::getData(IEDEA_HARMONIST, 'array', array('record_id' => $upload['data_assoc_concept']));
                    $concepts = getProjectInfoArrayRepeatingInstruments($RecordSetConcepts)[0];
                    $concept_id = $concepts['concept_id'];
                    $concept_title = $concepts['concept_title'];

                    $RecordSetPeopleUp = \REDCap::getData(IEDEA_PEOPLE, 'array', array('record_id' => $upload['data_upload_person']));
                    $peopleUp = getProjectInfoArray($RecordSetPeopleUp)[0];

                    $RecordSetRegionsUp = \REDCap::getData(IEDEA_REGIONS, 'array', array('record_id' => $peopleUp['person_region']));
                    $region_codeUp = getProjectInfoArray($RecordSetRegionsUp)[0]['region_code'];

                    $date = new DateTime($upload['responsecomplete_ts']);
                    $date->modify("+1 hours");
                    $date_time = $date->format("Y-m-d H:i");

                    #to uploader user
                    $url = $module->getUrl("index.php?&option=dat=&pid=" . IEDEA_PROJECTS);
                    $subject = "Notification of " . $settings['hub_name'] . " " . $concept_id . " dataset deletion";
                    $message = "<div>Dear " . $peopleUp['firstname'] . ",</div><br/><br/>" .
                        "<div>The dataset you submitted to secure cloud storage in response to&nbsp;<strong>\"" . $concept_id . ": " . $concept_title . "\"</strong> <em>(Draft ID: " . $sop['record_id'] . ")</em>, on " . $date_time . " Eastern US Time (ET) has been deleted automatically because the&nbsp;<b><span style='color:#0070c0'>" . $settings['retrievedata_expiration'] . "-day storage window has ended</span></b>. " .
                        "This dataset will not be available for future downloads. To replace the deleted dataset, log in to the " . $settings['hub_name'] . " Hub and select&nbsp;<strong>Submit Data on the <a href='" . $url . "' target='_blank'>Data page</a></strong>.</div><br/>" .
                        "<span style='color:#777'>Please email <a href='mailto:" . $settings['hub_contact_email'] . "'>" . $settings['hub_contact_email'] . "</a> with any questions.</span>";
                    sendEmail($peopleUp['email'], $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], $subject, $message, $upload['data_upload_person']);
                }

                #to downloaders
                if ($sop['sop_downloaders'] != "") {
                    $downloaders = explode(',', $sop['sop_downloaders']);
                    $number_downloaders = count($downloaders);
                    $messageArray['numDownloaders'] = $number_downloaders;

                    $downloadersOrdered = array();
                    foreach ($downloaders as $down) {
                        if ($peopleDown == null) {
                            $RecordSetPeopleDown = \REDCap::getData(IEDEA_PEOPLE, 'array', array('record_id' => $down));
                            $peopleDownData = getProjectInfoArray($RecordSetPeopleDown)[0];
                            $RecordSetRegionsLoginDown = \REDCap::getData(IEDEA_REGIONS, 'array', array('record_id' => $peopleDown['person_region']));
                            $region_codeDown = getProjectInfoArray($RecordSetRegionsLoginDown)[0]['region_code'];
                        } else {
                            $region_codeDown = "TT";
                            $peopleDownData = $peopleDown[$down];
                        }
                        $downloadersOrdered = self::getDownloadersOrdered($down, $downloadersOrdered, $peopleDownData, $region_codeDown);
                    }
                    array_sort_by_column($downloadersOrdered, 'name');

                    $RecordSetConcepts = \REDCap::getData(IEDEA_HARMONIST, 'array', array('record_id' => $upload['data_assoc_concept']));
                    $concept_id = getProjectInfoArrayRepeatingInstruments($RecordSetConcepts)[0]['concept_id'];

                    if($email) {
                        $subject = "Notification of " . $settings['hub_name'] . " " . $concept_id . " dataset deletion";
                        foreach ($downloadersOrdered as $down) {
                            $message = "<div>Dear " . $down['firstname'] . ",</div><br/><br/>" .
                                "<div>The dataset previously submitted in response to&nbsp;<strong>\"" . $concept_id . ": " . $concept_title . "\"</strong> <em>(Draft ID: " . $sop['record_id'] . ")</em>, on " . $date_time . " Eastern US Time (ET) by&nbsp;<b>" . $peopleUp['firstname'] . " " . $peopleUp['lastname'] . " from " . $region_codeUp . "</b> has been deleted automatically because the&nbsp;<b><span style='color:#0070c0'>" . $settings['retrievedata_expiration'] . "-day storage window has ended</span></b>. " .
                                "If you still need to access this dataset, please e-mail <a href='mailto:" . $peopleUp['email'] . "'>" . $peopleUp['email'] . "</a> to request a new dataset.</div><br/>" .
                                "<span style='color:#777'>Please email <a href='mailto:" . $settings['hub_contact_email'] . "'>" . $settings['hub_contact_email'] . "</a> with any questions.</span>";

                            sendEmail($down['email'], $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], $subject, $message, $down['id']);
                        }
                    }
                    $message['code_test'] = "1";
                }
                if($email) {
                    \REDCap::logEvent("Dataset deleted automatically\nRecord " . $upload['record_id'], "Concept ID: " . $concept_id . "\n Draft ID: " . $sop['record_id'], null, null, null, IEDEA_DATAUPLOAD);
                }
            } catch (S3Exception $e) {
                echo $e->getMessage() . "\n";
            }
        }
        return $message;
    }

    public static function runCronUploadPendingDataSetData($module){

    }

    public static function sendEmailToday($upload, $extra_days_delete, $extra_days, $extra_days2){
        $today = date('Y-m-d');
        if (!array_key_exists('deleted_y', $upload) || $upload['deleted_y'] != '1') {
            $expired_date_delete = date('Y-m-d', strtotime($upload['responsecomplete_ts'] . $extra_days_delete));
            $expired_date_reminder = date('Y-m-d', strtotime($upload['responsecomplete_ts'] . $extra_days));
            $expired_date_reminder2 = date('Y-m-d', strtotime($upload['responsecomplete_ts'] . $extra_days2));
            if (strtotime($expired_date_reminder) == strtotime($today) || strtotime($expired_date_reminder2) == strtotime($today)) {
                return (array('delete' => $expired_date_delete, 'reminder' => $expired_date_reminder, 'reminder2' => $expired_date_reminder2));
            }
        }
        return null;
    }

    public static function getDownloadersOrdered($down, $downloadersOrdered, $peopleDown, $region_codeDown){
        $downloadersOrdered[$down]['name'] = $peopleDown['firstname'] . " " . $peopleDown['lastname'];
        $downloadersOrdered[$down]['email'] = $peopleDown['email'];
        $downloadersOrdered[$down]['region_code'] = "(" . $region_codeDown . ")";
        $downloadersOrdered[$down]['id'] = $peopleDown['record_id'];
        $downloadersOrdered[$down]['firstname'] = $peopleDown['firstname'];

        return $downloadersOrdered;
    }

    public static function sendExpReminder($module, $sop, $down, $upload, $expired_date_reminder, $expired_date_reminder2, $expired_date_delete, $name_uploader, $region_code_uploader, $concept_id, $concept_title, $date_time, $settings, $email, $messageArray)
    {
        if (strtotime($expired_date_reminder) == strtotime(date('Y-m-d'))) {
            $subject = $settings['hub_name'] . " Data Request for " . $concept_id . " download expires on " . $expired_date_delete;
            $message = "<div>Dear " . $down['firstname'] . ",</div><br/><br/>" .
                "<div>This is a reminder that you have not downloaded the dataset that was submitted to secure cloud storage by&nbsp;<strong>" . $name_uploader . "</strong> from&nbsp;<strong>" . $region_code_uploader . "</strong> in response to your data request \"" . $concept_title . "\" for concept&nbsp;<b>" . $concept_id . "</b>, <i>Draft ID: " . $sop['record_id'] . "</i>. The upload was received at " . $date_time . " Eastern US Time (ET). </div><br/>" .
                "<div>The dataset will be deleted on&nbsp;<strong><span style='color:red;'>" . $expired_date_delete . " 23:59 ET (" . $settings['downloadreminder_dur'] . " days)</span></strong>.</div><br/>" .
                "<div>To download the dataset, log in to the " . $settings['hub_name'] . " Hub and select&nbsp;<strong>Retrieve Data on the <a href='" . $module->getUrl("index.php?pid=" . IEDEA_PROJECTS . "&option=dat") . "' target='_blank'>Data page</a></strong>. " .
                "A summary report for the dataset is also available on that page.</div><br/>" .
                "<span style='color:#777'>Please email <a href='mailto:" . $settings['hub_contact_email'] . "'>" . $settings['hub_contact_email'] . "</a> with any questions.</span>";
            $reminder_num = $settings['downloadreminder_dur'];
            $messageArray[$settings['downloadreminder_dur']] += 1;
        } else if (strtotime($expired_date_reminder2) == strtotime(date('Y-m-d'))) {
            $subject = $settings['hub_name'] . " Data Request for " . $concept_id . " download expires on " . $expired_date_delete;
            $message = "<div>Dear " . $down['firstname'] . ",</div><br/><br/>" .
                "<div>This is a reminder that you have not downloaded the dataset that was submitted to secure cloud storage by&nbsp;<strong>" . $name_uploader . "</strong> from&nbsp;<strong>" . $region_code_uploader . "</strong> in response to your data request \"" . $concept_title . "\" for concept&nbsp;<b>" . $concept_id . "</b>, <i>Draft ID: " . $sop['record_id'] . "</i>. The upload was received at " . $date_time . " Eastern US Time (ET). </div><br/>" .
                "<div>The dataset will be deleted on&nbsp;<strong><span style='color:red;'>" . $expired_date_delete . " 23:59 ET (" . $settings['downloadreminder2_dur'] . " days)</span></strong>.</div><br/>" .
                "<div>To download the dataset, log in to the " . $settings['hub_name'] . " Hub and select&nbsp;<strong>Retrieve Data on the <a href='" . $module->getUrl("index.php?pid=" . IEDEA_PROJECTS . "&option=dat") . "' target='_blank'>Data page</a></strong>. " .
                "A summary report for the dataset is also available on that page.</div><br/>" .
                "<div>This is the final reminder for this dataset.</div><br/>" .
                "<span style='color:#777'>Please email <a href='mailto:" . $settings['hub_contact_email'] . "'>" . $settings['hub_contact_email'] . "</a> with any questions.</span>";
            $reminder_num = $settings['downloadreminder2_dur'];
            $messageArray[$settings['downloadreminder2_dur']] += 1;
        }

        if ($email) {
            sendEmail($down['email'], $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], $subject, $message, $down['id']);
            \REDCap::logEvent("Reminder Sent<br/>Record " . $upload['record_id'], $reminder_num . " days reminder \nTo: " . $down['email'] . "\nConcept ID: " . $concept_id . "\n", null, null, null, IEDEA_DATAUPLOAD);
        }
        return $messageArray;
    }
}

?>