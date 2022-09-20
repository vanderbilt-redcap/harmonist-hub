<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include_once(__DIR__ ."/../projects.php");

class AllCrons
{
    public static function runCronDataUploadExpirationReminder($module, $pidsArray, $upload, $sop, $peopleDown, $extra_days_delete, $extra_days, $extra_days2, $settings, $email = false)
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
                        $RecordSetPeopleDown = \REDCap::getData($pidsArray['PEOPLE'], 'array', array('record_id' => $down));
                        $peopleDownData = ProjectData::getProjectInfoArray($RecordSetPeopleDown)[0];
                        $RecordSetRegionsLoginDown = \REDCap::getData($pidsArray['REGIONS'], 'array', array('record_id' => $peopleDown['person_region']));
                        $region_codeDown = ProjectData::getProjectInfoArray($RecordSetRegionsLoginDown)[0]['region_code'];
                    }else{
                        $region_codeDown = "TT";
                        $peopleDownData = $peopleDown[$down];
                    }
                    $downloadersOrdered = self::getDownloadersOrdered($down, $downloadersOrdered, $peopleDownData, $region_codeDown);
                }
                ArrayFunctions::array_sort_by_column($downloadersOrdered, 'name');

                $date = new \DateTime($upload['responsecomplete_ts']);
                $date->modify("+1 hours");
                $date_time = $date->format("Y-m-d H:i");

                $RecordSetPeople = \REDCap::getData($pidsArray['PEOPLE'], 'array', array('record_id' => $upload['data_upload_person']));
                $people = ProjectData::getProjectInfoArray($RecordSetPeople)[0];
                $name_uploader = $people['firstname'] . " " . $people['lastname'];
                $RecordSetRegionsUp = \REDCap::getData($pidsArray['REGIONS'], 'array', array('record_id' => $people['person_region']));
                $region_code_uploader = ProjectData::getProjectInfoArray($RecordSetRegionsUp)[0]['region_code'];

                $RecordSetConcepts = \REDCap::getData($pidsArray['HARMONIST'], 'array', array('record_id' => $upload['data_assoc_concept']));
                $concepts = ProjectData::getProjectInfoArray($RecordSetConcepts)[0];
                $concept_id = $concepts['concept_id'];
                $concept_title = $concepts['concept_title'];

                $messageArray['concept_title'] = $concept_title;
                $messageArray['sop_id'] = $sop['record_id'];

                $RecordSetDOWN = \REDCap::getData($pidsArray['DATADOWNLOAD'], 'array', null, null, null, null, false, false, false, "[download_id] = " . $upload['record_id']);
                $downloads = ProjectData::getProjectInfoArray($RecordSetDOWN);
                if (empty($downloads)) {
                    foreach ($downloadersOrdered as $down) {
                        $messageArray = self::sendExpReminder($module, $pidsArray, $sop, $down, $upload, $expired_date['reminder'], $expired_date['reminder2'], $expired_date['delete'], $name_uploader, $region_code_uploader, $concept_id, $concept_title, $date_time, $settings, $email, $messageArray);
                        if(!$email) {
                            \REDCap::logEvent("Reminder Sent<br/>Record " . $upload['record_id'], "No downloads yet from any downloaders.\n", null, null, null, $pidsArray['DATAUPLOAD']);
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
                            $messageArray = AllCrons::sendExpReminder($module, $pidsArray, $sop, $down, $upload, $expired_date['reminder'], $expired_date['reminder2'], $expired_date['delete'], $name_uploader, $region_code_uploader, $concept_id, $concept_title, $date_time, $settings, $email, $messageArray);
                            $messageArray['notdownloaded'] += 1;
                        }
                    }
                }
            }
        }
        return $messageArray;
    }

    public static function runCronDataUploadNotification($module, $pidsArray, $upload, $sop, $peopleDown, $extra_days, $settings, $email = false)
    {
        $messageArray = array();
        $expired_date = date('Y-m-d', strtotime($upload['responsecomplete_ts'] . $extra_days));
        if(strtotime($expired_date) >= strtotime(date('Y-m-d'))) {
            if(!array_key_exists('emails_sent_y',$upload) || $upload['emails_sent_y'][1] == '0') {
                if($email) {
                    //Save data on project
                    $Proj = new \Project($pidsArray['DATAUPLOAD']);
                    $event_id = $Proj->firstEventId;
                    $arraySaveDU = array();
                    $arraySaveDU[$upload['record_id']][$event_id]['emails_sent_y'] = array(1 => "1");//checkbox
                    $results = \Records::saveData($pidsArray['DATAUPLOAD'], 'array', $arraySaveDU, 'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
                    \Records::addRecordToRecordListCache($pidsArray['DATAUPLOAD'], $upload['emails_sent_y'], 1);
                }

                $downloaders_list = "";
                if ($sop['sop_downloaders'] != "") {
                    $downloaders = explode(',', $sop['sop_downloaders']);
                    $number_downloaders = count($downloaders);
                    $messageArray['numDownloaders'] = $number_downloaders;

                    $downloadersOrdered = array();
                    foreach ($downloaders as $down) {
                        if ($peopleDown == null) {
                            $RecordSetPeopleDown = \REDCap::getData($pidsArray['PEOPLE'], 'array', array('record_id' => $down));
                            $peopleDownData = ProjectData::getProjectInfoArray($RecordSetPeopleDown)[0];
                            $RecordSetRegionsLoginDown = \REDCap::getData($pidsArray['REGIONS'], 'array', array('record_id' => $peopleDown['person_region']));
                            $region_codeDown = ProjectData::getProjectInfoArray($RecordSetRegionsLoginDown)[0]['region_code'];
                        } else {
                            $region_codeDown = "TT";
                            $peopleDownData = $peopleDown[$down];
                        }
                        $downloadersOrdered = self::getDownloadersOrdered($down, $downloadersOrdered, $peopleDownData, $region_codeDown);
                    }
                    ArrayFunctions::array_sort_by_column($downloadersOrdered, 'name');

                    foreach ($downloadersOrdered as $downO) {
                        $downloaders_list .= "<li>" . $downO['name'] . " " . $downO['region_code'] . ", <a href='mailto:" . $downO['email'] . "'>" . $downO['email'] . "</a></li>";
                    }
                    $downloaders_list .= "</ol>";
                }

                #Uploader email
                $RecordSetPeople = \REDCap::getData($pidsArray['PEOPLE'], 'array', array('record_id' => $upload['data_upload_person']));
                $people = ProjectData::getProjectInfoArray($RecordSetPeople)[0];
                $to = $people['email'];
                $firstname = $people['firstname'];
                $name_uploader = $people['firstname'] . " " . $people['lastname'];
                $RecordSetRegionsUp = \REDCap::getData($pidsArray['REGIONS'], 'array', array('record_id' => $people['person_region']));
                $region_code_uploader = ProjectData::getProjectInfoArray($RecordSetRegionsUp)[0]['region_code'];

                $RecordSetConcepts = \REDCap::getData($pidsArray['HARMONIST'], 'array', array('record_id' => $upload['data_assoc_concept']));
                $concept_id = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConcepts)[0]['concept_id'];

                $date = new \DateTime($upload['responsecomplete_ts']);
                $date->modify("+1 hours");
                $date_time = $date->format("Y-m-d H:i");
                $expire_date = date('Y-m-d', strtotime($date_time . $extra_days));

                if ($email) {
                    $subject = "Successful " . $settings['hub_name'] . " data upload for " . $concept_id;
                    $message = "<div>Dear " . $firstname . ",</div><br/><br/>" .
                        "<div>Thank you for submitting your dataset to secure cloud storage in response to <strong><a href='" . $module->getUrl("index.php?NOAUTH&pid=" . $pidsArray['PROJECTS'] . "&option=sop&record=" . $upload['data_assoc_request']) . "' target='_blank'>" . $concept_id . "</a></strong> on <b>" . $date_time . "</b> Eastern US Time (ET). </div><br/>" .
                        "<div>You may log into the " . $settings['hub_name'] . " Hub and view the <a href='" . $module->getUrl("index.php?NOAUTH&pid=" . $pidsArray['PROJECTS'] . "&option=slgd") . "' target='_blank'>Data Activity Log</a> report, track downloads, and delete your dataset. Your dataset will be available for " .
                        "download by the approved data downloaders <strong>until " . $expire_date . " 23:59</strong> ET unless you choose to delete it before then. </div><br/>" .
                        "<div>Approved Data Downloaders:</div>" .
                        $downloaders_list . "<br/>" .
                        "<span style='color:#777'>Please email <a href='mailto:" . $settings['hub_contact_email'] . "'>" . $settings['hub_contact_email'] . "</a> with any questions.</span>";

                    \Vanderbilt\HarmonistHubExternalModule\sendEmail($to, $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], $subject, $message, $upload['data_upload_person'],"Dataset submission notification", $pidsArray['DATAUPLOAD']);

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
                                "<div>To download the dataset, log in to the " . $settings['hub_name'] . " Hub and select <strong>Retrieve Data on the <a href='" . $module->getUrl("index.php?NOAUTH&pid=" . $pidsArray['PROJECTS'] . "&option=dat") . "' target='_blank'>Data page</a></strong>. " .
                                "A summary report for the dataset is also available on that page. The dataset will be deleted on " . $expire_date . " 23:59 ET</div><br/>" .
                                "<span style='color:#777'>Please email <a href='mailto:" . $settings['hub_contact_email'] . "'>" . $settings['hub_contact_email'] . "</a> with any questions.</span>";

                            \Vanderbilt\HarmonistHubExternalModule\sendEmail($down['email'], $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], $subject, $message, $down['id'],"Dataset submission notification", $pidsArray['DATAUPLOAD']);
                        }
                    }
                }
            }
        }
        return $messageArray;
    }

    public static function runCronMonthlyDigest($module, $pidsArray, $requests, $requests_hub, $sops, $settings, $email = false)
    {
        $environment = "";
        if(ENVIRONMENT == 'DEV' || ENVIRONMENT == 'TEST'){
            $environment = " ".ENVIRONMENT;
        }

        $request_type = $module->getChoiceLabels('request_type', $pidsArray['RMANAGER']);
        $finalize_y = $module->getChoiceLabels('finalize_y', $pidsArray['RMANAGER']);

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
                    $RecordSetConceptSheets = \REDCap::getData($pidsArray['HARMONIST'], 'array',  array('record_id' => $req['assoc_concept']));
                    $concept = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConceptSheets)[0];
                    $concept_sheet = $concept['concept_id'];
                    $concept_title = $concept['concept_title'];
                    $email_req .= ", ".$concept_sheet;
                }
                $email_req .= ", ".$req['contact_name']."</div>";

                $email_req .= "<div style='padding: 3px;'><a href='".$module->getUrl("index.php?NOAUTH&pid=".$pidsArray['PROJECTS']."&option=hub&record=".$req['request_id'])."' target='_blank' alt='concept_link'>".$req['request_title']."</a></div>";
                $votes = array();
                foreach ($req['region_response_status'] as $region => $vote_status){
                    if($vote_status != 0 && in_array($req['region_vote_status'],$req)){
                        if($region == ""){
                            $region = "1";
                        }
                        $RecordSetRegions = \REDCap::getData($pidsArray['REGIONS'], 'array',  array('record_id' => $region));
                        $region_code = ProjectData::getProjectInfoArray($RecordSetRegions)[0]['region_code'];
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
                    $RecordSetConceptSheets = \REDCap::getData($pidsArray['HARMONIST'], 'array', array('record_id' => $req['assoc_concept']));
                    $concept = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConceptSheets)[0];
                    $concept_sheet = $concept['concept_id'];
                    $concept_title = $concept['concept_title'];
                    $email_req .= ", ".$concept_sheet;
                }
                $email_req .= ", ".$req['contact_name']."</div>";

                $email_req .= "<div style='padding: 3px;'><a href='".$module->getUrl("index.php?NOAUTH&pid=".$pidsArray['DATAMODEL']."&option=hub&record=".$req['request_id'])."' target='_blank' alt='concept_link'>".$req['request_title']."</a></div>";

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
        $RecordSetRegions = \REDCap::getData($pidsArray['SOP'], 'array', null,null,null,null,false,false,false,"[showregion_y] = 1");
        $regions = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRegions);
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
                    $RecordSetConceptSheets = \REDCap::getData($pidsArray['HARMONIST'], 'array', array('record_id' => $sop['sop_concept_id']));
                    $concept = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConceptSheets)[0];
                    $concept_sheet = $concept['concept_id'];
                    $concept_title = $concept['concept_title'];

                    $email_req .= "<li style='padding-bottom: 15px;padding-left: 10px;'><div style='padding: 3px;'><strong>Due: <span style='$date_color_text'>" . $sop['sop_due_d'] . "</span></strong></span></div>";
                }

                $email_req .= "<div style='padding: 3px;'><a href='" . $module->getUrl("index.php?NOAUTH&pid=" . $pidsArray['DATAMODEL'] . "&option=hub&record=" . $sop['request_id']) . "' target='_blank' alt='concept_link'>" . $sop['request_title'] . "</a></div>";
                $RecordSetCreator = \REDCap::getData($pidsArray['PEOPLE'], 'array', array('record_id' => $sop['sop_creator']));
                $creator = ProjectData::getProjectInfoArray($RecordSetCreator)[0];
                $RecordSetCreator2 = \REDCap::getData($pidsArray['PEOPLE'], 'array', array('record_id' => $sop['sop_creator2']));
                $creator2 = ProjectData::getProjectInfoArray($RecordSetCreator2)[0];
                $RecordSetDataContact = \REDCap::getData($pidsArray['PEOPLE'], 'array', array('record_id' => $sop['sop_creator2']));
                $datacontact = ProjectData::getProjectInfoArray($RecordSetDataContact)[0];
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
                $email_req .= "<div style='padding: 3px;'><a href='".$module->getUrl("index.php?NOAUTH&pid=".$pidsArray['PROJECTS']."&option=sop&record=".$sop['record_id']). "'>" . $sop['sop_name'] . "</a></div>";

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
                    \Vanderbilt\HarmonistHubExternalModule\sendEmail($email, 'noreply.harmonist@vumc.org', $settings['accesslink_sender_name'], $subject, $email_req, "Not in database","Monthly Digest", $pidsArray['RMANAGER']);
                }
            }
        }
        $message['code_test'] = 1;
        return $message;
    }

    public static function runCronDeleteAws($module, $pidsArray, $s3, $upload, $sop, $peopleDown, $expired_date, $settings, $email = false)
    {
        if((!array_key_exists('deleted_y',$upload) || $upload['deleted_y'] != "1") && strtotime($expired_date) <= strtotime(date('Y-m-d'))){
            try {
                if($email) {
                    #Delete the object
                    $result = $s3->deleteObject(array(
                        'Bucket' => $upload['data_upload_bucket'],
                        'Key' => $upload['data_upload_folder'] . $upload['data_upload_zip']
                    ));

                    //Save data on project
                    $Proj = new \Project($pidsArray['DATAUPLOAD']);
                    $event_id = $Proj->firstEventId;
                    $recordSaveDU = array();
                    $recordSaveDU[$upload['record_id']][$event_id]['record_id'] = $upload['record_id'];
                    $recordSaveDU[$upload['record_id']][$event_id]['deletion_type'] = "1";
                    $date = new \DateTime();
                    $recordSaveDU[$upload['record_id']][$event_id]['deletion_ts'] = $date->format('Y-m-d H:i:s');
                    $recordSaveDU[$upload['record_id']][$event_id]['deletion_rs'] = "Expired. Deleted automatically";
                    $recordSaveDU[$upload['record_id']][$event_id]['deletion_information_complete'] = "2";
                    $recordSaveDU[$upload['record_id']][$event_id]['deleted_y'] = "1";
                    $results = \Records::saveData($pidsArray['DATAUPLOAD'], 'array', $recordSaveDU, 'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
                    \Records::addRecordToRecordListCache($pidsArray['DATAUPLOAD'], $upload['record_id'], 1);

                    #EMAIL NOTIFICATION
                    $RecordSetConcepts = \REDCap::getData($pidsArray['HARMONIST'], 'array', array('record_id' => $upload['data_assoc_concept']));
                    $concepts = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConcepts)[0];
                    $concept_id = $concepts['concept_id'];
                    $concept_title = $concepts['concept_title'];

                    $RecordSetPeopleUp = \REDCap::getData($pidsArray['PEOPLE'], 'array', array('record_id' => $upload['data_upload_person']));
                    $peopleUp = ProjectData::getProjectInfoArray($RecordSetPeopleUp)[0];

                    $RecordSetRegionsUp = \REDCap::getData($pidsArray['REGIONS'], 'array', array('record_id' => $peopleUp['person_region']));
                    $region_codeUp = ProjectData::getProjectInfoArray($RecordSetRegionsUp)[0]['region_code'];

                    $date = new \DateTime($upload['responsecomplete_ts']);
                    $date->modify("+1 hours");
                    $date_time = $date->format("Y-m-d H:i");

                    #to uploader user
                    $url = $module->getUrl("index.php?NOAUTH&option=dat=&pid=" . $pidsArray['PROJECTS']);
                    $subject = "Notification of " . $settings['hub_name'] . " " . $concept_id . " dataset deletion";
                    $message = "<div>Dear " . $peopleUp['firstname'] . ",</div><br/><br/>" .
                        "<div>The dataset you submitted to secure cloud storage in response to&nbsp;<strong>\"" . $concept_id . ": " . $concept_title . "\"</strong> <em>(Draft ID: " . $sop['record_id'] . ")</em>, on " . $date_time . " Eastern US Time (ET) has been deleted automatically because the&nbsp;<b><span style='color:#0070c0'>" . $settings['retrievedata_expiration'] . "-day storage window has ended</span></b>. " .
                        "This dataset will not be available for future downloads. To replace the deleted dataset, log in to the " . $settings['hub_name'] . " Hub and select&nbsp;<strong>Submit Data on the <a href='" . $url . "' target='_blank'>Data page</a></strong>.</div><br/>" .
                        "<span style='color:#777'>Please email <a href='mailto:" . $settings['hub_contact_email'] . "'>" . $settings['hub_contact_email'] . "</a> with any questions.</span>";
                    \Vanderbilt\HarmonistHubExternalModule\sendEmail($peopleUp['email'], $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], $subject, $message, $upload['data_upload_person'],"Dataset deletion notification", $pidsArray['DATAUPLOAD']);
                }

                #to downloaders
                if ($sop['sop_downloaders'] != "") {
                    $downloaders = explode(',', $sop['sop_downloaders']);
                    $number_downloaders = count($downloaders);
                    $messageArray['numDownloaders'] = $number_downloaders;

                    $downloadersOrdered = array();
                    foreach ($downloaders as $down) {
                        if ($peopleDown == null) {
                            $RecordSetPeopleDown = \REDCap::getData($pidsArray['PEOPLE'], 'array', array('record_id' => $down));
                            $peopleDownData = ProjectData::getProjectInfoArray($RecordSetPeopleDown)[0];
                            $RecordSetRegionsLoginDown = \REDCap::getData($pidsArray['REGIONS'], 'array', array('record_id' => $peopleDown['person_region']));
                            $region_codeDown = ProjectData::getProjectInfoArray($RecordSetRegionsLoginDown)[0]['region_code'];
                        } else {
                            $region_codeDown = "TT";
                            $peopleDownData = $peopleDown[$down];
                        }
                        $downloadersOrdered = self::getDownloadersOrdered($down, $downloadersOrdered, $peopleDownData, $region_codeDown);
                    }
                    ArrayFunctions::array_sort_by_column($downloadersOrdered, 'name');

                    $RecordSetConcepts = \REDCap::getData($pidsArray['HARMONIST'], 'array', array('record_id' => $upload['data_assoc_concept']));
                    $concept_id = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConcepts)[0]['concept_id'];

                    if($email) {
                        $subject = "Notification of " . $settings['hub_name'] . " " . $concept_id . " dataset deletion";
                        foreach ($downloadersOrdered as $down) {
                            $message = "<div>Dear " . $down['firstname'] . ",</div><br/><br/>" .
                                "<div>The dataset previously submitted in response to&nbsp;<strong>\"" . $concept_id . ": " . $concept_title . "\"</strong> <em>(Draft ID: " . $sop['record_id'] . ")</em>, on " . $date_time . " Eastern US Time (ET) by&nbsp;<b>" . $peopleUp['firstname'] . " " . $peopleUp['lastname'] . " from " . $region_codeUp . "</b> has been deleted automatically because the&nbsp;<b><span style='color:#0070c0'>" . $settings['retrievedata_expiration'] . "-day storage window has ended</span></b>. " .
                                "If you still need to access this dataset, please e-mail <a href='mailto:" . $peopleUp['email'] . "'>" . $peopleUp['email'] . "</a> to request a new dataset.</div><br/>" .
                                "<span style='color:#777'>Please email <a href='mailto:" . $settings['hub_contact_email'] . "'>" . $settings['hub_contact_email'] . "</a> with any questions.</span>";

                            \Vanderbilt\HarmonistHubExternalModule\sendEmail($down['email'], $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], $subject, $message, $down['id'],"Dataset deletion notification", $pidsArray['DATAUPLOAD']);
                        }
                    }
                    $message['code_test'] = "1";
                }
                if($email) {
                    \REDCap::logEvent("Dataset deleted automatically\nRecord " . $upload['record_id'], "Concept ID: " . $concept_id . "\n Draft ID: " . $sop['record_id'], null, null, null, $pidsArray['DATAUPLOAD']);
                }
            } catch (S3Exception $e) {
                echo $e->getMessage() . "\n";
            }
        }
        return $message;
    }

    public static function runCronMetrics($module, $pidsArray, $email = false)
    {
        $date = new \DateTime();
        $record_id_metrics = "";
        if($email != false){
            $record_id_metrics = $module->framework->addAutoNumberedRecord($pidsArray['METRICS']);
        }
        $message = "";

        $arrayMetrics = array(array('record_id' => $record_id_metrics));
        $arrayMetrics[0]['date'] = $date->format('Y-m-d H:i:s');


        /***CONCEPTS***/
        $RecordSetConcepts = \REDCap::getData($pidsArray['HARMONIST'], 'array', null);
        $total_concepts = count($RecordSetConcepts);
        $arrayMetrics[0]['concepts'] = $total_concepts;

        $RecordSetConceptsActive = \REDCap::getData($pidsArray['HARMONIST'], 'array', null, null, null, null, false, false, false, "[active_y] = 'Y'");
        $number_concepts_active = count(ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConceptsActive));
        $arrayMetrics[0]['concepts_a'] = $number_concepts_active;

        $RecordSetConceptsCompleted = \REDCap::getData($pidsArray['HARMONIST'], 'array', null, null, null, null, false, false, false, "[concept_outcome] = 1");
        $number_concepts_completed = count(ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConceptsCompleted));
        $arrayMetrics[0]['concepts_c'] = $number_concepts_completed;

        $RecordSetConceptsDiscontinued = \REDCap::getData($pidsArray['HARMONIST'], 'array', null, null, null, null, false, false, false, "[concept_outcome] = 2");
        $number_concepts_discontinued = count(ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConceptsDiscontinued));
        $arrayMetrics[0]['concepts_d'] = $number_concepts_discontinued;

        /***REQUESTS***/
        $RecordSetRequests = \REDCap::getData($pidsArray['RMANAGER'], 'array', null, null, null, null, false, false, false, "[approval_y] != 9");
        $total_requests = count(ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRequests));
        $arrayMetrics[0]['requests'] = $total_requests;

        $RecordSetRequestsApproved = \REDCap::getData($pidsArray['RMANAGER'], 'array', null, null, null, null, false, false, false, "[approval_y] = 1");
        $number_requests_approved = count(ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRequestsApproved));
        $arrayMetrics[0]['requests_a'] = $number_requests_approved;

        $RecordSetRequestsRejected = \REDCap::getData($pidsArray['RMANAGER'], 'array', null, null, null, null, false, false, false, "[approval_y] = 0");
        $number_requests_rejected = count(ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRequestsRejected));
        $arrayMetrics[0]['requests_r'] = $number_requests_rejected;

        $RecordSetRequestsDeactivated = \REDCap::getData($pidsArray['RMANAGER'], 'array', null, null, null, null, false, false, false, "[approval_y] = 9");
        $number_requests_deactivated = count(ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRequestsDeactivated));
        $arrayMetrics[0]['requests_d'] = $number_requests_deactivated;


        $RecordSetRegions = \REDCap::getData($pidsArray['REGIONS'], 'array', null, null, null, null, false, false, false, "[showregion_y] = 1");
        $regions = ProjectData::getProjectInfoArray($RecordSetRegions);


        #PUBLICATIONS AND ABSTRACTS;
        $publications = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConcepts);

        $number_publications = 0;
        $number_publications_year = 0;
        $number_abstracts = 0;
        $number_abstracts_year = 0;
        foreach ($publications as $outputs) {
            foreach ($outputs['output_type'] as $index => $output_type) {
                if ($output_type == '1') {
                    $number_publications++;
                    if ($outputs['output_year'][$index] == $date->format('Y')) {
                        $number_publications_year++;
                    }
                } else if ($output_type == '2') {
                    $number_abstracts++;
                    if ($outputs['output_year'][$index] == $date->format('Y')) {
                        $number_abstracts_year++;
                    }
                }
            }
        }
        $arrayMetrics[0]['publications'] = $number_publications;
        $arrayMetrics[0]['abstracts'] = $number_abstracts;
        $arrayMetrics[0]['publications_current'] = $number_publications_year;
        $arrayMetrics[0]['abstracts_current'] = $number_abstracts_year;

        #COMMENTS AND VOTES
        $RecordSetComments = \REDCap::getData($pidsArray['COMMENTSVOTES'], 'array', null);
        $comments = ProjectData::getProjectInfoArray($RecordSetComments);
        $req_id = array();
        foreach ($comments as $comments) {
            if ($comments['request_id'] != '') {
                array_push($req_id, $comments['request_id']);
            }
        }
        $req_id = array_unique($req_id);

        $query = $module->framework->createQuery();
        $query->add("SELECT record FROM redcap_data WHERE field_name = ? AND project_id = ? AND 'value' = ?", ["approval_y", $pidsArray['RMANAGER'], "9"]);
        $query->add('and')->addInClause('record ', $req_id);
        $query->add('group by record');
        $q = $query->execute();
        while ($row = $q->fetch_assoc()) {
            if (($key = array_search($row['record'], $req_id)) !== false) {
                unset($req_id[$key]);
            }
        }

        $query = $module->framework->createQuery();
        $query->add("SELECT a.record FROM redcap_data a INNER JOIN redcap_data b on a.record=b.record and a.project_id=b.project_id WHERE a.field_name = ? AND a.project_id = ? ", ["request_id", $pidsArray['COMMENTSVOTES']]);
        $query->add('and')->addInClause('a.value ', $req_id);
        $query->add('group by a.record');
        $q = $query->execute();
        $total_comments = 0;
        $comments_id = array();
        while ($row = $q->fetch_assoc()) {
            $total_comments++;
            array_push($comments_id, $row['record']);
        }
        $arrayMetrics[0]['comments'] = $total_comments;

        $query = $module->framework->createQuery();
        $query->add("SELECT * FROM redcap_data WHERE field_name = ? AND project_id = ? ", ["response_pi_level", $pidsArray['COMMENTSVOTES']]);
        $query->add('and')->addInClause('record ', $comments_id);
        $query->add('group by record');
        $q = $query->execute();
        $number_comments_pi = 0;
        $number_comments_nonpi = 0;
        while ($row = $q->fetch_assoc()) {
            if ($row['value'] == '1') {
                $number_comments_pi++;
            } else if ($row['value'] == '0') {
                $number_comments_nonpi++;
            }
        }
        $arrayMetrics[0]['comments_pi'] = $number_comments_pi;
        $arrayMetrics[0]['comments_n'] = $number_comments_nonpi;

        $query = $module->framework->createQuery();
        $query->add("SELECT * FROM redcap_data WHERE field_name = ? AND project_id = ? ", ["pi_vote", $pidsArray['COMMENTSVOTES']]);
        $query->add('and')->addInClause('record ', $comments_id);
        $query->add('group by record');
        $q = $query->execute();
        $number_votes = 0;
        $request_ids = array();
        while ($row = $q->fetch_assoc()) {
            if ($row['value'] != '') {
                $number_votes++;
                array_push($request_ids, $row['record']);
            }
        }
        $arrayMetrics[0]['votes'] = $number_votes;

        $query = $module->framework->createQuery();
        $query->add("SELECT * FROM redcap_data WHERE field_name = ? AND project_id = ? ", ["vote_now", $pidsArray['COMMENTSVOTES']]);
        $query->add('and')->addInClause('record ', $comments_id);
        $query->add('group by record');
        $q = $query->execute();
        $number_votes_later = 0;
        while ($row = $q->fetch_assoc()) {
            if ($row['value'] != '0') {
                $number_votes_later++;
            }
        }
        $arrayMetrics[0]['vote_later'] = $number_votes_later;

        $RecordSetComments = \REDCap::getData($pidsArray['COMMENTSVOTES'], 'array', null, null, null, null, false, false, false, "[author_revision_y] = 1");
        $comments_revision = ProjectData::getProjectInfoArray($RecordSetComments);
        #get unique values from matrix column request_id (unique request ids)
        $revisions = 0;
        foreach ($comments_revision as $comment) {
            $RecordSetRM = \REDCap::getData($pidsArray['RMANAGER'], 'array', array('request_id' => $comment['request_id']));
            $approval_y = ProjectData::getProjectInfoArray($RecordSetRM)[0]['approval_y'];
            if ($approval_y == '1') {
                $revisions++;
            }
        }
        $arrayMetrics[0]['revisions'] = $revisions;

        $RecordRequests = \REDCap::getData($pidsArray['RMANAGER'], 'array');
        $requests = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordRequests, array('approval_y' => '1'));

        $number_votes_completed_before_duedate = 0;
        $number_votes_completed_after_duedate = 0;
        $completerequests = 0;
        $numregions = count($regions);
        $completed_requests_by_all_regions = array();
        foreach ($requests as $request) {
            $votecount = 0;
            foreach ($regions as $region) {
                $instance = $region['record_id'];

                if ($request['region_vote_status'][$instance] != "") {
                    $votecount++;
                    $request_date = date("Y-m-d", strtotime($request['region_close_ts'][$instance]));
                    if (strtotime($request['due_d']) <= strtotime($request_date)) {
                        //if vote submitted before or on due date
                        $number_votes_completed_before_duedate++;
                    } else {
                        $number_votes_completed_after_duedate++;
                    }
                }

                if ($votecount == $numregions) {
                    $completerequests++; //if the number of votes (vote count) equals the number of voting regions, then this request is complete, so increment complete counter
                    array_push($completed_requests_by_all_regions, $request['request_id']);
                }
            }
        }

        foreach ($completed_requests_by_all_regions as $completed) {
            $RecordSetRM = \REDCap::getData($pidsArray['RMANAGER'], 'array', array('request_id' => $completed));
            $recordRMComplete = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRM)[0];
            if ($recordRMComplete['detected_complete'][1] != "1") {
                $Proj = new \Project($pidsArray['RMANAGER']);
                $event_id_RM = $Proj->firstEventId;
                $arrayRM = array();
                $arrayRM[$comment['request_id']][$event_id_RM]['detected_complete'] = array(1 => "1");//checkbox
                $arrayRM[$comment['request_id']][$event_id_RM]['detected_complete_ts'] = date('Y-m-d H:i:s');
                $results = \Records::saveData($pidsArray['RMANAGER'], 'array', $arrayRM, 'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
            }
        }

        $number_votes = $number_votes_completed_before_duedate + $number_votes_completed_after_duedate;
        $arrayMetrics[0]['votes_c'] = $number_votes_completed_before_duedate;
        $number_votes_completed_before_duedate_percent = ($number_votes_completed_before_duedate / $number_votes) * 100;
        $arrayMetrics[0]['votes_c_percentage'] = round($number_votes_completed_before_duedate_percent, 2);

        $arrayMetrics[0]['votes_late'] = $number_votes_completed_after_duedate;
        $number_votes_completed_after_duedate_percent = ($number_votes_completed_after_duedate / $number_votes) * 100;
        $arrayMetrics[0]['votes_late_percentage'] = round($number_votes_completed_after_duedate_percent, 2);

        #REQUESTS COMPLETED
        $arrayMetrics[0]['requests_c'] = $completerequests;

        #USERS
        $query = $module->framework->createQuery();
        $query->add("SELECT count(*) as total_registered_users FROM redcap_data WHERE field_name = ? AND project_id = ? AND value in (1,2,3)", ["harmonist_regperm", $pidsArray['PEOPLE']]);
        $q = $query->execute();
        $arrayMetrics[0]['users'] = $q->fetch_assoc()['total_registered_users'];

        $RecordSetUsersPi = \REDCap::getData($pidsArray['PEOPLE'], 'array', null, null, null, null, false, false, false, "[harmonist_regperm] = 3");
        $number_users_pi = count(ProjectData::getProjectInfoArray($RecordSetUsersPi));
        $arrayMetrics[0]['users_pi'] = $number_users_pi;

        $query = $module->framework->createQuery();
        $query->add("SELECT count(*) as number_users_accesslink FROM redcap_data WHERE field_name = ? AND project_id = ? AND DATEDIFF(NOW(),value) between 0 AND 30", ["last_requested_token_d", $pidsArray['PEOPLE']]);
        $q = $query->execute();
        $arrayMetrics[0]['users_access'] = $q->fetch_assoc()['number_users_accesslink'];

        $RecordSetUsersAdmin = \REDCap::getData($pidsArray['PEOPLE'], 'array', null, null, null, null, false, false, false, "[harmonistadmin_y] = 1");
        $number_requests_admin = count(ProjectData::getProjectInfoArray($RecordSetUsersAdmin));
        $arrayMetrics[0]['admins'] = $number_requests_admin;

        $json = json_encode($arrayMetrics);
        $results = \Records::saveData($pidsArray['METRICS'], 'json', $json, 'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
        if($email != false) {
            \Records::addRecordToRecordListCache($pidsArray['METRICS'], $record_id_metrics, 1);
        }
        $message['metrics'] = 1;
        return $message;
    }

    public static function runCronUploadPendingDataSetData($module, $pidsArray, $s3, $bucket, $settings, $email = false)
    {
        try {
            $message = array();
            $message['pending_total'] = 0;
            //Get list of elements in folder
            $objects = $s3->getIterator('ListObjects', array(
                "Bucket" => $bucket,
                "Prefix" => "pending/"
            ));

            foreach ($objects as $object) {
                $file_name = str_replace("pending/", '', $object['Key']);
                $file_name_extension = explode('.', $file_name)[1];
                $file_name = explode('.', $file_name)[0];

                if ($file_name_extension == 'json') {
                    $message['pending_total'] = $message['pending_total'] + 1;
                    #Get the object
                    $result = $s3->getObject(array(
                        'Bucket' => $bucket,
                        'Key' => $object['Key']
                    ));

                    $s3->registerStreamWrapper();
                    $data = file_get_contents('s3://' . $bucket . '/' . $object['Key']);
                    // Open a stream in read-only mode
                    if ($stream = fopen('s3://' . $bucket . '/' . $object['Key'], 'r')) {
                        // While the stream is still open
                        while (!feof($stream)) {
                            // Read 1,024 bytes from the stream
                            $uploadData = json_decode(fread($stream, 1024), true);
                        }
                        // Be sure to close the stream resource when you're done with it
                        fclose($stream);
                    }

                    if ($uploadData != '') {
                        if (strpos($file_name, 'test_') !== false) {
                            #test file
                            $message['data_assoc_concept'] = $uploadData[0]['data_assoc_concept'];
                            $message['responsecomplete_ts'] = $uploadData[0]['responsecomplete_ts'];
                        }else {
                            $RecordSetUpload = \REDCap::getData($pidsArray['DATAUPLOAD'], 'array', null, null, null, null, false, false, false,
                                "[data_assoc_concept] = " . $uploadData[0]['data_assoc_concept'] . " AND [data_assoc_request] = " . $uploadData[0]['data_assoc_request'] .
                                " AND [data_upload_person] = " . $uploadData[0]['data_upload_person'] . " AND [data_upload_region] = " . $uploadData[0]['data_upload_region']);
                            $request_DU = ProjectData::getProjectInfoArray($RecordSetUpload)[0];

                            if ($request_DU != "") {
                                $found = false;
                                foreach ($request_DU as $upload) {
                                    if (strtotime($upload['responsecomplete_ts']) == strtotime($uploadData[0]['responsecomplete_ts']) || $upload['responsecomplete_ts'] == "") {
                                        self::addUploadRecord($module, $s3, $uploadData, $file_name, $bucket, $settings, $upload['record_id']);
                                        $found = true;
                                        break;
                                    }
                                }

                                if (!$found) {
                                    self::addUploadRecord($module, $s3, $uploadData, $file_name, $bucket, $settings, "");
                                }
                            } else {
                                #Record is missing, create new one
                                self::addUploadRecord($module, $s3, $uploadData, $file_name, $bucket, $settings, "");
                            }
                        }
                    }

                    if($email != false) {
                        #Delete the object after uploading the record
                        #JSON
                        $result = $s3->deleteObject(array(
                            'Bucket' => $bucket,
                            'Key' => $object['Key']
                        ));

                        #REPORT
                        $reportHash = "Report" . str_replace("_details", "", $file_name) . ".pdf";
                        $result = $s3->deleteObject(array(
                            'Bucket' => $bucket,
                            'Key' => 'pending/' . $reportHash
                        ));
                    }
                }
            }
            return $message;
        } catch (S3Exception $e) {
            echo $e->getMessage() . "\n";
        }
    }

    public static function runCronJson($module, $pidsArray, $settings, $email = false)
    {
        self::hasJsoncopyBeenUpdated($module, '0a', $settings, $pidsArray);
        self::hasJsoncopyBeenUpdated($module, '0b', $settings, $pidsArray);
        self::hasJsoncopyBeenUpdated($module, '0c', $settings, $pidsArray);
    }

    public static function sendEmailToday($upload, $extra_days_delete, $extra_days, $extra_days2)
    {
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

    public static function getDownloadersOrdered($down, $downloadersOrdered, $peopleDown, $region_codeDown)
    {
        $downloadersOrdered[$down]['name'] = $peopleDown['firstname'] . " " . $peopleDown['lastname'];
        $downloadersOrdered[$down]['email'] = $peopleDown['email'];
        $downloadersOrdered[$down]['region_code'] = "(" . $region_codeDown . ")";
        $downloadersOrdered[$down]['id'] = $peopleDown['record_id'];
        $downloadersOrdered[$down]['firstname'] = $peopleDown['firstname'];

        return $downloadersOrdered;
    }

    public static function sendExpReminder($module, $pidsArray, $sop, $down, $upload, $expired_date_reminder, $expired_date_reminder2, $expired_date_delete, $name_uploader, $region_code_uploader, $concept_id, $concept_title, $date_time, $settings, $email, $messageArray)
    {
        if (strtotime($expired_date_reminder) == strtotime(date('Y-m-d'))) {
            $subject = $settings['hub_name'] . " Data Request for " . $concept_id . " download expires on " . $expired_date_delete;
            $message = "<div>Dear " . $down['firstname'] . ",</div><br/><br/>" .
                "<div>This is a reminder that you have not downloaded the dataset that was submitted to secure cloud storage by&nbsp;<strong>" . $name_uploader . "</strong> from&nbsp;<strong>" . $region_code_uploader . "</strong> in response to your data request \"" . $concept_title . "\" for concept&nbsp;<b>" . $concept_id . "</b>, <i>Draft ID: " . $sop['record_id'] . "</i>. The upload was received at " . $date_time . " Eastern US Time (ET). </div><br/>" .
                "<div>The dataset will be deleted on&nbsp;<strong><span style='color:red;'>" . $expired_date_delete . " 23:59 ET (" . $settings['downloadreminder_dur'] . " days)</span></strong>.</div><br/>" .
                "<div>To download the dataset, log in to the " . $settings['hub_name'] . " Hub and select&nbsp;<strong>Retrieve Data on the <a href='" . $module->getUrl("index.php?NOAUTH&pid=" . $pidsArray['PROJECTS'] . "&option=dat") . "' target='_blank'>Data page</a></strong>. " .
                "A summary report for the dataset is also available on that page.</div><br/>" .
                "<span style='color:#777'>Please email <a href='mailto:" . $settings['hub_contact_email'] . "'>" . $settings['hub_contact_email'] . "</a> with any questions.</span>";
            $reminder_num = $settings['downloadreminder_dur'];
            $messageArray[$settings['downloadreminder_dur']] += 1;
        } else if (strtotime($expired_date_reminder2) == strtotime(date('Y-m-d'))) {
            $subject = $settings['hub_name'] . " Data Request for " . $concept_id . " download expires on " . $expired_date_delete;
            $message = "<div>Dear " . $down['firstname'] . ",</div><br/><br/>" .
                "<div>This is a reminder that you have not downloaded the dataset that was submitted to secure cloud storage by&nbsp;<strong>" . $name_uploader . "</strong> from&nbsp;<strong>" . $region_code_uploader . "</strong> in response to your data request \"" . $concept_title . "\" for concept&nbsp;<b>" . $concept_id . "</b>, <i>Draft ID: " . $sop['record_id'] . "</i>. The upload was received at " . $date_time . " Eastern US Time (ET). </div><br/>" .
                "<div>The dataset will be deleted on&nbsp;<strong><span style='color:red;'>" . $expired_date_delete . " 23:59 ET (" . $settings['downloadreminder2_dur'] . " days)</span></strong>.</div><br/>" .
                "<div>To download the dataset, log in to the " . $settings['hub_name'] . " Hub and select&nbsp;<strong>Retrieve Data on the <a href='" . $module->getUrl("index.php?NOAUTH&pid=" . $pidsArray['PROJECTS'] . "&option=dat") . "' target='_blank'>Data page</a></strong>. " .
                "A summary report for the dataset is also available on that page.</div><br/>" .
                "<div>This is the final reminder for this dataset.</div><br/>" .
                "<span style='color:#777'>Please email <a href='mailto:" . $settings['hub_contact_email'] . "'>" . $settings['hub_contact_email'] . "</a> with any questions.</span>";
            $reminder_num = $settings['downloadreminder2_dur'];
            $messageArray[$settings['downloadreminder2_dur']] += 1;
        }

        if ($email) {
            \Vanderbilt\HarmonistHubExternalModule\sendEmail($down['email'], $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], $subject, $message, $down['id'],"Data Request expiration reminder for " . $concept_id, $pidsArray['DATAUPLOAD']);
            \REDCap::logEvent("Reminder Sent<br/>Record " . $upload['record_id'], $reminder_num . " days reminder \nTo: " . $down['email'] . "\nConcept ID: " . $concept_id . "\n", null, null, null, $pidsArray['DATAUPLOAD']);
        }
        return $messageArray;
    }

    function addUploadRecord($module, $pidsArray, $s3, $uploadData, $file_name, $bucket, $settings, $record = "")
    {
        #Email Data
        $RecordSetConceptSheets = \REDCap::getData($pidsArray['HARMONIST'], 'array', array('record_id' => $uploadData[0]['data_assoc_concept']));
        $concept_id = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConceptSheets)[0]['concept_id'];

        $RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', array('record_id' => $uploadData[0]['data_assoc_request']));
        $sop = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP)[0];

        $uploader_name = \Vanderbilt\HarmonistHubExternalModule\getPeopleName($pidsArray['PEOPLE'], $uploadData[0]['data_upload_person'], "");

        $RecordSetRegionsUp = \REDCap::getData($pidsArray['REGIONS'], 'array', array('record_id' => $uploadData[0]['data_upload_region']));
        $region_codeUp = ProjectData::getProjectInfoArray($RecordSetRegionsUp)[0]['region_code'];

        $gotoredcap = APP_PATH_WEBROOT_ALL . "DataEntry/record_status_dashboard.php?pid=" . $pidsArray['DATAUPLOAD'];


        if ($record != "") {
            $recordpdf = $record;

            $RecordSetUpload = \REDCap::getData($pidsArray['SOP'], 'array', array('record_id' => $record));
            $upload = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetUpload)[0];

            $link = APP_PATH_WEBROOT_ALL . "DataEntry/record_home.php?pid=" . $pidsArray['DATAUPLOAD'] . "&arm=1&id=" . $recordpdf;
            $message = "<div>Dear administrator,</div><br/>" .
                "<div>A pending upload dataset has been found on the Harmonist Data Toolkit server: </div><br/>" .
                "<div><strong>\"" . $sop['sop_name'] . "\"</strong> uploaded by <em>" . $uploader_name . "</em> from " . $region_codeUp . " on " . $uploadData[0]['responsecomplete_ts'] . " Eastern US Time (ET).</div><br/>" .
                "<div>This upload record has now been added to the Hub REDCap project as <a href='" . $link . "'>Record ID " . $recordpdf . "</a>.</div>" .
                "<div>All associated notification emails have been activated.</div><br/>" .
                "<div>Click here to view the <a href='" . $gotoredcap . "'>Hub Uploads</a> page.</div>";
        } else {
            $recordpdf = $module->framework->addAutoNumberedRecord($pidsArray['DATAUPLOAD']);

            $link = APP_PATH_WEBROOT_ALL . "DataEntry/record_home.php?pid=" . $pidsArray['DATAUPLOAD'] . "&arm=1&id=" . $recordpdf;
            $message = "<div>Dear administrator,</div><br/>" .
                "<div>A pending upload dataset has been found on the Harmonist Data Toolkit server: </div><br/>" .
                "<div><strong>\"" . $sop['sop_name'] . "\"</strong> uploaded by <em>" . $uploader_name . "</em> from " . $region_codeUp . " on " . $uploadData[0]['responsecomplete_ts'] . " Eastern US Time (ET).</div><br/>" .
                "<div>A <strong>partial, matching upload record</strong> was found in the Hub REDCap project under <a href='" . $link . "'>Record ID " . $recordpdf . "</a>. This record has now been updated with additional information.</div>" .
                "<div>All associated notification emails have been activated.</div><br/>" .
                "<div>Click here to view the <a href='" . $gotoredcap . "'>Hub Uploads</a> page.</div>";
        }

        $Proj = new \Project($pidsArray['DATAUPLOAD']);
        $event_id = $Proj->firstEventId;
        $recordUp = array();
        $recordUp[$recordpdf][$event_id]['data_assoc_concept'] = $uploadData[0]['data_assoc_concept'];
        $recordUp[$recordpdf][$event_id]['data_assoc_request'] = $uploadData[0]['data_assoc_request'];
        $recordUp[$recordpdf][$event_id]['data_upload_person'] = $uploadData[0]['data_upload_person'];
        $recordUp[$recordpdf][$event_id]['data_upload_region'] = $uploadData[0]['data_upload_region'];
        $recordUp[$recordpdf][$event_id]['responsecomplete_ts'] = $uploadData[0]['responsecomplete_ts'];
        $recordUp[$recordpdf][$event_id]['data_upload_bucket'] = $uploadData[0]['data_upload_bucket'];
        $recordUp[$recordpdf][$event_id]['data_upload_folder'] = $uploadData[0]['data_upload_folder'];
        $recordUp[$recordpdf][$event_id]['data_upload_zip'] = $uploadData[0]['data_upload_zip'];
        $results = \Records::saveData($pidsArray['DATAUPLOAD'], 'array', $recordUp, 'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
        \Records::addRecordToRecordListCache($pidsArray['DATAUPLOAD'], $recordpdf, 1);

        if (($record != "" && $upload['data_upload_pdf'] == "") || $record == "") {
            //SAVE PDF ON DB
            $reportHash = "Report" . str_replace("_details", "", $file_name);
            $storedName = md5($reportHash);
            $filePath = EDOC_PATH . $storedName;
            $s3->registerStreamWrapper();
            $output = file_get_contents('s3://' . $bucket . '/pending/' . $reportHash . ".pdf");
            $filesize = file_put_contents(EDOC_PATH . $storedName, $output);

            //Save document on DB
            $q = $module->query("INSERT INTO redcap_edocs_metadata (stored_name,doc_name,doc_size,file_extension,mime_type,gzipped,project_id,stored_date) VALUES (?,?,?,?,?,?,?,?)", [$storedName, 'application/octet-stream', $reportHash . ".pdf", $filesize, '.pdf', '0', $pidsArray['DATAUPLOAD'], date('Y-m-d h:i:s')]);
            $docId = db_insert_id();

            //Add document DB ID to project
            $jsonConcepts = json_encode(array(array('record_id' => $recordpdf, 'data_upload_pdf' => $docId)));
            $results = \Records::saveData($pidsArray['DATAUPLOAD'], 'json', $jsonConcepts, 'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);

            \Records::addRecordToRecordListCache($pidsArray['DATAUPLOAD'], $record, 1);

        }

        #EMAIL NOTIFICATION
        $subject = "Pending dataset upload found for " . $settings['hub_name'] . " " . $concept_id;

        if ($settings['hub_email_pending_uploads'] != "") {
            $emails = explode(';', $settings['hub_email_pending_uploads']);
            foreach ($emails as $email) {
                \Vanderbilt\HarmonistHubExternalModule\sendEmail($email, $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], $subject, $message, $recordpdf,"Pending dataset upload notification", $pidsArray['DATAUPLOAD']);
            }
        }
    }

    public static function hasJsoncopyBeenUpdated($module, $type, $settings, $pidsArray){
        $project_pid = "";
        if($type == '0a'){
            $project_pid = $pidsArray['DATAMODEL'];
        }else if($type == '0b'){
            $project_pid = $pidsArray['CODELIST'];
        }else if($type == '0c'){
            $project_pid = $pidsArray['DATAMODELMETADATA'];
        }
        if($project_pid != "") {
            #Check if the project has information
            $RecordSetProjectData = \REDCap::getData($project_pid, 'array');
            $projectData = ProjectData::getProjectInfoArray($RecordSetProjectData)[0];
            if (!empty($projectData)) {
                $jsoncopyPID = $pidsArray['JSONCOPY'];
                $sqltype = "SELECT record as record FROM redcap_data WHERE project_id='".db_escape(IEDEA_JSONCOPY)."' AND field_name='".db_escape('type')."' and value='".db_escape($type)."' order by record";
                while($rowtype[] = $qtype->fetch_assoc());
                $maxRecord = max(array_column($rowtype, 'record'));

                $RecordSetJsonCopy = \REDCap::getData($jsoncopyPID, 'array', array('record_id' => $maxRecord));
                $jsoncopy = ProjectData::getProjectInfoArray($RecordSetJsonCopy)[0];
                $today = date("Y-m-d");
                if ($jsoncopy["jsoncopy_file"] != "" && strtotime(date("Y-m-d", strtotime($jsoncopy['json_copy_update_d']))) == strtotime($today)) {
                    return true;
                } else if (empty($jsoncopy) || strtotime(date("Y-m-d", strtotime($jsoncopy['json_copy_update_d']))) == "" || !array_key_exists('json_copy_update_d', $jsoncopy)) {
                    self::checkAndUpdateJSONCopyProject($module, $type, $rowtype['record'], $jsoncopy, $settings, $pidsArray);
                    return true;
                }
            }
        }
        return false;
    }

    public static function createAndSaveJSONCron($module, $project_id){
        error_log("createpdf - createAndSaveJSONCron");
        $RecordSetConstants = \REDCap::getData($project_id, 'array', null,null,null,null,false,false,false,"[project_constant]='DATAMODEL'");
        $dataModelPID = getProjectInfoArray($RecordSetConstants)[0]['project_id'];

        $RecordSetDataModel = \REDCap::getData($dataModelPID, 'array');
        $dataTable = getProjectInfoArrayRepeatingInstruments($RecordSetDataModel);
        $dataFormat = $module->getChoiceLabels('data_format', $dataModelPID);

        foreach ($dataTable as $data) {
            $jsonVarArrayAux = array();
            if($data['table_name'] != "") {
                foreach ($data['variable_order'] as $id => $value) {
                    if ($data['variable_name'][$id] != '') {
                        $url = $module->getUrl("browser.php?NOAUTH&pid=" . $_GET['pid'] . '&tid=' . $data['record_id'] . '&vid=' . $id . '&option=variableInfo');
                        $jsonVarArrayAux[trim($data['variable_name'][$id])] = array();
                        $variables_array = array(
                            "instance" => $id,
                            "description" => $data['description'][$id],
                            "description_extra" => $data['description_extra'][$id],
                            "code_list_ref" => $data['code_list_ref'][$id],
                            "data_format" => trim($dataFormat[$data['data_format'][$id]]),
                            "code_text" => $data['code_text'][$id],
                            "variable_link" => $url
                        );

                        $jsonVarArrayAux[$data['variable_name'][$id]] = $variables_array;
                    }
                }
                $jsonVarArray = $jsonVarArrayAux;
                $urltid = $module->getUrl("browser.php?NOAUTH&pid=" . $_GET['pid'] . '&tid=' . $data['record_id'] . '&option=variables');
                $jsonVarArray['table_link'] = $urltid;
                $jsonArray[trim($data['table_name'])] = $jsonVarArray;
            }
        }
        #we save the new JSON
        if(!empty($jsonArray)){
            self::saveJSONCopyVarSearch($module, $jsonArray, $project_id);
        }
    }

    public static function saveJSONCopyVarSearch($module, $jsonArray, $project_id){
        error_log("createpdf - saveJSONCopyVarSearch");
        $RecordSetConstants = \REDCap::getData($project_id, 'array', null,null,null,null,false,false,false,"[project_constant]='DATAMODEL'");
        $settingsPID = ProjectData::getProjectInfoArray($RecordSetConstants)[0]['project_id'];

        #create and save file with json
        $filename = "jsoncopy_file_variable_search_".date("YmdsH").".txt";
        $storedName = date("YmdsH")."_pid".$settingsPID."_".\Vanderbilt\HarmonistHubExternalModule\getRandomIdentifier(6).".txt";

        $file = fopen(EDOC_PATH.$storedName,"wb");
        fwrite($file,json_encode($jsonArray,JSON_FORCE_OBJECT));
        fclose($file);

        $output = file_get_contents(EDOC_PATH.$storedName);
        $filesize = file_put_contents(EDOC_PATH.$storedName, $output);

        //Save document on DB
        $q = $module->query("INSERT INTO redcap_edocs_metadata (stored_name,doc_name,doc_size,file_extension,mime_type,gzipped,project_id,stored_date) VALUES(?,?,?,?,?,?,?,?)",
            [$storedName,$filename,$filesize,'txt','application/octet-stream','0',$settingsPID,date('Y-m-d h:i:s')]);
        $docId = db_insert_id();

        //Add document DB ID to project
        $Proj = new \Project($settingsPID);
        $event_id = $Proj->firstEventId;
        $json = json_encode(array(array('record_id' => 1, 'des_variable_search' => $docId)));
        $results = \Records::saveData($settingsPID, 'json', $json,'normal', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
        \Records::addRecordToRecordListCache($settingsPID, 1,$event_id);
    }

    public static function checkAndUpdateJSONCopyProject($module, $type, $last_record, $jsoncocpy, $settings, $pidsArray){
        $jsoncopyPID = $pidsArray['JSONCOPY'];
        if($jsoncocpy["jsoncopy_file"] != ""){
            $q = $module->query("SELECT stored_name,doc_name,doc_size,mime_type FROM redcap_edocs_metadata WHERE doc_id=?",[$jsoncocpy["jsoncopy_file"]]);
            while ($row = $q->fetch_assoc()) {
                $path = EDOC_PATH.$row['stored_name'];
                $strJsonFileContents = file_get_contents($path);
                $last_array = json_decode($strJsonFileContents, true);
                $array_data = call_user_func_array("\Vanderbilt\HarmonistHubExternalModule\createProject".strtoupper($type)."JSON",array($module, $pidsArray));
                $new_array = json_decode($array_data['jsonArray'],true);
                $record = $array_data['record_id'];
                if($type == "0c"){
                    $result_prev = array_filter_empty(array_diff_assoc($last_array,$new_array));
                    $result = array_filter_empty(array_diff_assoc($new_array,$last_array));
                }else{
                    //multidimensional projects
                    $result_prev = array_filter_empty(multi_array_diff($last_array,$new_array));
                    $result = array_filter_empty(multi_array_diff($new_array,$last_array));
                }
            }
        }else{
            $array_data = call_user_func_array("\Vanderbilt\HarmonistHubExternalModule\createProject".strtoupper($type)."JSON",array($module, $pidsArray));
            $result = json_decode($array_data['jsonArray'],true);
            $result_prev = "";
            $record = $array_data['record_id'];
        }

        if($last_record == ""){
            $last_record = "<i>None</i>";
        }

        if(!empty($record)){
            $environment = "";
            if(ENVIRONMENT == 'DEV' || ENVIRONMENT == 'TEST'){
                $environment = " ".ENVIRONMENT;
            }

            $sender = $settings['accesslink_sender_email'];
            if($settings['accesslink_sender_email'] == ""){
                $sender = "noreply.harmonist@vumc.org";
            }

            $link = APP_PATH_WEBROOT_ALL . "DataEntry/record_home.php?pid=" . $jsoncopyPID . "&arm=1&id=" . $record;
            $subject = "Changes in the DES ".strtoupper($type)." detected ";
            $message = "<div>The following changes have been detected in the DES ".strtoupper($type)." and a new record #".$record." has been created:</div><br/>".
                "<div>Last record: ". $last_record."</div><br/>".
                "<div>To see the record <a href='".$link."'>click here</a></div><br/>".
                "<ul><pre>".print_r($result,true)."</pre>".
                "<span style='color:#777'><pre><em>".print_r($result_prev,true)."</em></pre></ul></span>";

            if($settings['hub_subs_0a0b'] != "") {
                $emails = explode(';', $settings['hub_subs_0a0b']);
                foreach ($emails as $email) {
                    \REDCap::email($email, $sender, $subject.$environment, $message,"","",$settings['accesslink_sender_name']);
                }
            }
        }
        return null;
    }
    public static function runCronReqFinalizedNotification($module, $pidsArray, $request, $settings, $email = false)
    {
        //Save variable as sent
        $Proj = new \Project($pidsArray['RMANAGER']);
        $event_id = $Proj->firstEventId;
        $recordSave = array();
        $recordSave[$request['request_id']][$event_id]['request_summary_sent_y'] = array(1 => "1");//checkbox;
        $results = \Records::saveData($pidsArray['RMANAGER'], 'array', $recordSave, 'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
        \Records::addRecordToRecordListCache($pidsArray['RMANAGER'], $request['request_id'], 1);

        #Email
        $request_type = $module->getChoiceLabels('request_type', $pidsArray['RMANAGER']);
        $subject = $settings['hub_name'] . " Request #" . $request['request_id'] . " Request Summary for " . $request_type[$request['request_type']] . ", " . $request['contact_name'];
        $body = "<h2>Request Summary</h2>";
        $body .= "<div>Dear " . $request['contact_name'] . ",<div>
        <div>Your " . $settings['hub_name'] . " request has been approved, \"" . $request['request_title'] . "\", as of ".$request['final_d'].". Below is a summary of the votes and comments that were recorded for your request. Please check the final approval e-mail for next steps for your request; this is just a digest of recorded votes and comments.</div>
        <div>".$settings['author_summary_footer']."</div></br></br>";

        $RecordSetComments = \REDCap::getData($pidsArray['COMMENTSVOTES'], 'array', null, null, null, null, false, false, false, "[request_id] = ".$request['request_id']);
        $comments = ProjectData::getProjectInfoArray($RecordSetComments);
        if (!empty($comments)) {
            $body .= "<table style='border: 1px solid #ddd;max-width: 900px;font-size: 14px;border-collapse: collapse;'>
        <thead>
        <tr>
        <th style='width:20%;padding: 8px;vertical-align: middle;border: 1px solid #ddd;'>Name / Time</th>
        <th style='width:20%;padding: 8px;vertical-align: middle;border: 1px solid #ddd;'>Comments</th>
        </tr>
        </thead>
        <tbody>";
            foreach ($comments as $comment) {
                $RecordSetRegionsLoginDown = \REDCap::getData($pidsArray['REGIONS'], 'array', array("record_id"=>$comment['response_region']));
                $regions = ProjectData::getProjectInfoArray($RecordSetRegionsLoginDown)[0];
                $name =  \Vanderbilt\HarmonistHubExternalModule\getPeopleName($pidsArray['PEOPLE'],$comment['response_person'],"email");

                $comment_time ="";
                if(!empty($comment['responsecomplete_ts'])){
                    $dateComment = new \DateTime($comment['responsecomplete_ts']);
                    $dateComment->modify("+1 hours");
                    $comment_time = $dateComment->format("Y-m-d H:i:s");
                }

                $writing_group = "";
                if($comment['writing_group'] != ""){
                    $writing_group = "<div style='padding-top:10px'><em>Writing group nominee(s): ".$comment['writing_group']."</em></div>";
                }

                $files = false;
                if(!empty($comment['revised_file'])){
                    $files = true;
                }
                if(!empty($comment['extra_revfile1'])){
                    $files = true;
                }
                if(!empty($comment['extra_revfile2'])){
                    $files = true;
                }

                $comment_vote = "";
                if($comment['pi_vote'] != ''){
                    if ($comment['pi_vote'] == "1") {
                        //Approved
                        $comment_vote = '<img src="'.APP_PATH_MODULE.'/img/vote_approved.jpg" alt="Approved">&nbsp;&nbsp;<span style="color:#5cb85c;">Approved</span>';
                    } else if ($comment['pi_vote'] == "0") {
                        //Not Approved
                        $comment_vote = '<img src="'.APP_PATH_MODULE.'/img/vote_notapproved.jpg" alt="Not Approved">&nbsp;&nbsp;<span style="color:#e74c3c">Not Approved</span>';
                    } else if ($comment['pi_vote'] == "9") {
                        //Complete
                        $comment_vote = '<img src="'.APP_PATH_MODULE.'/img/vote_abstained.jpg" alt="Abstained">&nbsp;&nbsp;<span  style="color:#8c8c8c">Abstained</span>';
                    } else {
                        $comment_vote = '<img src="'.APP_PATH_MODULE.'/img/vote_abstained.jpg" alt="Abstained">&nbsp;&nbsp;<span  style="color:#8c8c8c">Abstained</span>';
                    }
                }

                $body .= "<tr>".
                    "<td style='width:20%;padding: 8px;vertical-align: middle;border: 1px solid #ddd;'>".$name." (".$regions['region_code'].")<br/>".$comment_time."</td>".
                    "<td style='width:75%;padding: 8px;vertical-align: middle;border: 1px solid #ddd'>".$comment_vote."<div>".nl2br($comment['comments']);

                if($files){
                    $body .= "</div><div>File uploaded, available in the Hub.</div>";
                }else{
                    $body .= "</div>";
                }
                $body .= "<div>".$writing_group."</div></td></tr>";
            }
            $url = $module->getUrl("index.php?NOAUTH&pid=".$pidsArray['PROJECTS']."&option=hub&record=".$request['request_id']);
            $body .= "</tbody>
            </table>
            </br><div>Link to review request #".$request['request_id'].": <a href='".$url."'>".$url."</a></div>";
            if($email) {
                \Vanderbilt\HarmonistHubExternalModule\sendEmail($request['contact_email'], $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], $subject, $body, $request['request_id'], "Request Finalized notification", $pidsArray['RMANAGER'], $settings['hub_email_author_summary']);
            }
        }
    }
}

?>