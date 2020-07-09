<?php
include_once(__DIR__ ."/../projects.php");
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
require_once "/app001/credentials/iedea_aws_s3.php";

$credentials = new Aws\Credentials\Credentials($aws_key, $aws_secret);
$s3 = new S3Client([
    'version' => 'latest',
    'region' => 'us-east-2',
    'credentials' => $credentials
]);

$RecordSetDU = \REDCap::getData(IEDEA_DATAUPLOAD, 'array', null);
$request_DU = getProjectInfoArray($RecordSetDU);

$today = date('Y-m-d');
$days_expiration = intval($settings['retrievedata_expiration']);
$extra_days = ' + ' . $days_expiration. " days";
foreach ($request_DU as $upload){
    $expired_date = date('Y-m-d', strtotime($upload['responsecomplete_ts'] . $extra_days));
    if((!array_key_exists('deleted_y',$upload) || $upload['deleted_y'] != "1") && strtotime($expired_date) <= strtotime($today)){

        try {
             #Delete the object
            $result = $s3->deleteObject(array(
                'Bucket' => $upload['data_upload_bucket'],
                'Key'    => $upload['data_upload_folder'].$upload['data_upload_zip']
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
            $results = \Records::saveData(IEDEA_DATAUPLOAD, 'array', $recordSaveDU,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
            \Records::addRecordToRecordListCache(IEDEA_DATAUPLOAD, $upload['record_id'],1);

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

            $RecordSetSOP = \REDCap::getData(IEDEA_SOP, 'array', array('record_id' => $upload['data_assoc_request']));
            $sop = getProjectInfoArrayRepeatingInstruments($RecordSetSOP)[0];

            #to uploader user
            $url = $module->getUrl("index.php?&option=dat=&pid=".IEDEA_PROJECTS);
            $subject = "Notification of ".$settings['hub_name']." ".$concept_id." dataset deletion";
            $message = "<div>Dear " . $peopleUp['firstname'] . ",</div><br/><br/>" .
                "<div>The dataset you submitted to secure cloud storage in response to&nbsp;<strong>\"" . $concept_id.": ".$concept_title . "\"</strong> <em>(Draft ID: ".$sop['record_id'].")</em>, on " . $date_time . " Eastern US Time (ET) has been deleted automatically because the&nbsp;<b><span style='color:#0070c0'>".$settings['retrievedata_expiration']."-day storage window has ended</span></b>. ".
                "This dataset will not be available for future downloads. To replace the deleted dataset, log in to the ".$settings['hub_name']." Hub and select&nbsp;<strong>Submit Data on the <a href='".$url."' target='_blank'>Data page</a></strong>.</div><br/>".
                "<span style='color:#777'>Please email <a href='mailto:".$settings['hub_contact_email']."'>".$settings['hub_contact_email']."</a> with any questions.</span>";
            sendEmail($peopleUp['email'], $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], $subject, $message, $upload['data_upload_person']);

            #to downloaders
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

                $RecordSetConcepts = \REDCap::getData(IEDEA_HARMONIST, 'array', array('record_id' => $upload['data_assoc_concept']));
                $concept_id = getProjectInfoArrayRepeatingInstruments($RecordSetConcepts)[0]['concept_id'];

                $subject = "Notification of ".$settings['hub_name']." ".$concept_id." dataset deletion";
                foreach ($downloadersOrdered as $down) {
                    $message = "<div>Dear " . $down['firstname'] . ",</div><br/><br/>" .
                        "<div>The dataset previously submitted in response to&nbsp;<strong>\"" .$concept_id.": ".$concept_title . "\"</strong> <em>(Draft ID: ".$sop['record_id'].")</em>, on ".$date_time." Eastern US Time (ET) by&nbsp;<b>" . $peopleUp['firstname']." ".$peopleUp['lastname']. " from ".$region_codeUp."</b> has been deleted automatically because the&nbsp;<b><span style='color:#0070c0'>".$settings['retrievedata_expiration']."-day storage window has ended</span></b>. ".
                        "If you still need to access this dataset, please e-mail <a href='mailto:".$peopleUp['email']."'>".$peopleUp['email']."</a> to request a new dataset.</div><br/>" .
                        "<span style='color:#777'>Please email <a href='mailto:".$settings['hub_contact_email']."'>".$settings['hub_contact_email']."</a> with any questions.</span>";

                    sendEmail($down['email'], $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], $subject, $message, $down['id']);
                }
            }
            \REDCap::logEvent("Dataset deleted automatically\nRecord ".$upload['record_id'],"Concept ID: ".$concept_id."\n Draft ID: ".$sop['record_id'],null,null,null,IEDEA_DATAUPLOAD);
        } catch (S3Exception $e) {
            echo $e->getMessage() . "\n";
        }
    }
}

#Delete tokens expired on H18 Data Toolkit
$RecordSetSecurity = \REDCap::getData(IEDEA_DATATOOLUPLOADSECURITY, 'array', null);
$securityTokens = getProjectInfoArray($RecordSetSecurity);
$today = strtotime(date("Y-m-d"));
foreach ($securityTokens as $token){
    if(strtotime($token['tokenexpiration_ts']) <= $today){
        $module->query("DELETE FROM redcap_data WHERE project_id = ? AND field_name=? AND value = ?",[IEDEA_DATATOOLUPLOADSECURITY,"record_id",$token["record_id"]]);
        \Records::deleteRecordFromRecordListCache(IEDEA_DATATOOLUPLOADSECURITY, $token["record_id"], 1);

        #Logs
        \REDCap::logEvent("Record deleted automatically","Record: ".$token['record_id'],null,null,null,IEDEA_DATATOOLUPLOADSECURITY);
    }
}

?>