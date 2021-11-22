<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include_once(__DIR__ ."/../projects.php");
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
require_once "/app001/credentials/".$pidsArray['PROJECTS']."_hubsettings.php";

$code = \Vanderbilt\HarmonistHubExternalModule\getCrypt($_REQUEST['code'],"d",$secret_key,$secret_iv);
$exploded = array();
parse_str($code, $exploded);

$record_id = $exploded['id'];
$user = $exploded['idu'];
$deletion_rs = $_REQUEST['deletion_rs'];

$RecordSetDU = \REDCap::getData($pidsArray['DATAUPLOAD'], 'array', array('record_id' => $record_id));
$request_DU = ProjectData::getProjectInfoArray($RecordSetDU)[0];

$credentials = new Aws\Credentials\Credentials($aws_key, $aws_secret);
$s3 = new S3Client([
    'version' => 'latest',
    'region' => 'us-east-2',
    'credentials' => $credentials
]);

try {
    if($request_DU['deleted_y'] != "1") {
        // Delete the object
        $result = $s3->deleteObject(array(
            'Bucket' => $request_DU['data_upload_bucket'],
            'Key' => $request_DU['data_upload_folder'] . $request_DU['data_upload_zip']
        ));

        //Save data on project
        $Proj = new \Project($pidsArray['DATAUPLOAD']);
        $event_id = $Proj->firstEventId;
        $recordSaveDU = array();
        $recordSaveDU[$record_id][$event_id]['record_id'] = $record_id;
        $recordSaveDU[$record_id][$event_id]['deletion_type'] = "2";
        $recordSaveDU[$record_id][$event_id]['deletion_hubuser'] = $user;
        $date = new \DateTime();
        $recordSaveDU[$record_id][$event_id]['deletion_ts'] = $date->format('Y-m-d H:i:s');
        $recordSaveDU[$record_id][$event_id]['deletion_rs'] = $deletion_rs;
        $recordSaveDU[$record_id][$event_id]['deletion_information_complete'] = "2";
        $recordSaveDU[$record_id][$event_id]['deleted_y'] = "1";
        $results = \Records::saveData($pidsArray['DATAUPLOAD'], 'array', $recordSaveDU,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
        \Records::addRecordToRecordListCache($pidsArray['DATAUPLOAD'], $record_id,1);

        #EMAIL NOTIFICATION
        $RecordSetConcepts = \REDCap::getData($pidsArray['HARMONIST'], 'array', array('record_id' => $request_DU['data_assoc_concept']));
        $concepts = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConcepts)[0];
        $concept_id = $concepts['concept_id'];
        $concept_title = $concepts['concept_title'];

        $RecordSetPeopleUp = \REDCap::getData($pidsArray['PEOPLE'], 'array', array('record_id' => $request_DU['data_upload_person']));
        $peopleUp = ProjectData::getProjectInfoArray($RecordSetPeopleUp)[0];

        $RecordSetRegionsUp = \REDCap::getData($pidsArray['REGIONS'], 'array', array('record_id' => $peopleUp['person_region']));
        $region_codeUp = ProjectData::getProjectInfoArray($RecordSetRegionsUp)[0]['region_code'];

        $date = new \DateTime($request_DU['responsecomplete_ts']);
        $date->modify("+1 hours");
        $date_time = $date->format("Y-m-d H:i");

        $RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', array('record_id' => $request_DU['data_assoc_request']));
        $sop = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP)[0];

        $RecordSetPeopleDelete = \REDCap::getData($pidsArray['PEOPLE'], 'array', array('record_id' => $user));
        $delete_user = ProjectData::getProjectInfoArray($RecordSetPeopleDelete)[0];
        $delete_user_fullname = $delete_user['firstname'] . " " . $delete_user['lastname'];
        $delete_user_name = $delete_user['firstname'];

        if ($user == $request_DU['data_upload_person']) {
            $subject = "Confirmation of ".$settings['hub_name']." " . $concept_id . " dataset deletion";
            $message = "<div>Dear " . $peopleUp['firstname'] . ",</div><br/><br/>" .
                "<div>The dataset you submitted to secure cloud storage in response to <strong>\"" .$concept_id.": ".$concept_title . "\"</strong> <em>(Draft ID: ".$sop['record_id'].")</em>, on " . $date_time . " Eastern US Time (ET) has been deleted successfully at your request and will not be available for future downloads.</div><br/>" .
                "<div>The following reason was logged for this deletion: <strong>" . $deletion_rs . "</strong></div><br/>" .
                "<div>To replace the deleted dataset, log in to the ".$settings['hub_name']." Hub and select <strong>Submit Data on the <a href='" . $module->getUrl(APP_PATH_PLUGIN . "/index.php?option=dat")."' target='_blank'>Data page</a></strong>.</div><br/>" .
                "<span style='color:#777'>Please email <a href='mailto:".$settings['hub_contact_email']."'>".$settings['hub_contact_email']."</a> with any questions.</span>";
            sendEmail($peopleUp['email'], $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], $subject, $message, $request_DU['data_upload_person'],"Dataset deleted",$pidsArray['DATAUPLOAD']);
        } else {
            $subject = "Notification of ".$settings['hub_name']." " . $concept_id . " dataset deletion";
            $message = "<div>Dear " . $peopleUp['firstname'] . ",</div><br/><br/>" .
                "<div>The dataset you submitted to secure cloud storage in response to <strong>\"" . $concept_id.": ".$concept_title . "\"</strong> <em>(Draft ID: ".$sop['record_id'].")</em>, on " . $date_time . " Eastern US Time (ET) has been deleted by " . $delete_user_fullname . " and will not be available for future downloads.</div><br/>" .
                "<div>The following reason was logged for this deletion: <strong>" . $deletion_rs . "</strong></div><br/>" .
                "<div>To replace the deleted dataset, log in to the ".$settings['hub_name']." Hub and select <strong>Submit Data on the <a href='" . $module->getUrl(APP_PATH_PLUGIN . "/index.php?option=dat")."' target='_blank'>Data page</a></strong>.</div><br/>" .
                "<span style='color:#777'>Please email <a href='mailto:".$settings['hub_contact_email']."'>".$settings['hub_contact_email']."</a> with any questions.</span>";
            sendEmail($peopleUp['email'], $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], $subject, $message, $request_DU['data_upload_person'],"Dataset deleted",$pidsArray['DATAUPLOAD']);

            #To deletetion user
            $subject = "Confirmation  of ".$settings['hub_name']." " . $concept_id . " dataset deletion";
            $message = "<div>Dear " . $delete_user_name . ",</div><br/><br/>" .
                "<div>The dataset submitted to secure cloud storage by <strong>" . $peopleUp['firstname'] . " " . $peopleUp['lastname'] . "</strong> in response to  <b>\"" .  $concept_id.": ".$concept_title . "\"</b> <em>(Draft ID: ".$sop['record_id'].")</em>,on " . $date_time . " Eastern US Time (ET) has been deleted successfully at your request and will not be available for future downloads.</div><br/>" .
                "<div>The following reason was logged for this deletion: <strong>" . $deletion_rs . "</strong></div><br/>" .
                "<span style='color:#777'>Please email <a href='mailto:".$settings['hub_contact_email']."'>".$settings['hub_contact_email']."</a> with any questions.</span>";
            sendEmail($delete_user['email'], $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], $subject, $message, $user,"Dataset deleted",$pidsArray['DATAUPLOAD']);
        }
        \REDCap::logEvent("Dataset deleted manually\nRecord ".$request_DU['record_id'],"Concept ID: ".$concept_id."\n Draft ID: ".$sop['record_id']."\n Deleted by: ".$delete_user_fullname,null,null,null,$pidsArray['DATAUPLOAD']);

        #Email to Downloaders
        $downloaders_list = "";
        if ($sop['sop_downloaders'] != "") {
            $downloaders = explode(',', $sop['sop_downloaders']);
            $number_downloaders = count($downloaders);
            $downloaders_list = "<ol>";
            $downloadersOrdered = array();
            foreach ($downloaders as $down) {
                $RecordSetPeopleDown = \REDCap::getData($pidsArray['PEOPLE'], 'array', array('record_id' => $down));
                $peopleDown = ProjectData::getProjectInfoArray($RecordSetPeopleDown)[0];
                $RecordSetRegionsLoginDown = \REDCap::getData($pidsArray['REGIONS'], 'array', array('record_id' => $peopleDown['person_region']));
                $region_codeDown = ProjectData::getProjectInfoArray($RecordSetRegionsLoginDown)[0]['region_code'];

                $downloadersOrdered[$down]['name'] = $peopleDown['firstname'] . " " . $peopleDown['lastname'];
                $downloadersOrdered[$down]['email'] = $peopleDown['email'];
                $downloadersOrdered[$down]['region_code'] = "(" . $region_codeDown . ")";
                $downloadersOrdered[$down]['id'] = $peopleDown['record_id'];
                $downloadersOrdered[$down]['firstname'] = $peopleDown['firstname'];
            }
            ArrayFunctions::array_sort_by_column($downloadersOrdered, 'name');

            $date = new \DateTime($request_DU['responsecomplete_ts']);
            $date->modify("+1 hours");
            $date_time = $date->format("Y-m-d H:i");
            $extra_days = ' + ' . $settings['retrievedata_expiration'] . " days";
            $expire_date = date('Y-m-d', strtotime($date_time . $extra_days));

            $RecordSetPeople = \REDCap::getData($pidsArray['PEOPLE'], 'array', array('record_id' => $request_DU['data_upload_person']));
            $person = ProjectData::getProjectInfoArray($RecordSetPeopleDown)[0];
            $firstname = $person['firstname'];
            $name_uploader = $person['firstname'] . " " . $person['lastname'];
            $RecordSetRegionsUp = \REDCap::getData($pidsArray['REGIONS'], 'array', array('record_id' => $person['person_region']));
            $region_code_uploader = ProjectData::getProjectInfoArray($RecordSetRegionsUp)[0]['region_code'];

            $RecordSetConcepts = \REDCap::getData($pidsArray['HARMONIST'], 'array', array('record_id' => $request_DU['data_assoc_concept']));
            $concept_id = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConcepts)[0]['concept_id'];

            $subject = "Notification of ".$settings['hub_name']." " . $concept_id . " dataset deletion";
            foreach ($downloadersOrdered as $down) {
                $message = "<div>Dear " . $down['firstname'] . ",</div><br/><br/>" .
                    "<div>The dataset previously submitted in response to <strong>\"" . $sop['sop_name'] . "\"</strong> on " . $date_time . " Eastern US Time (ET) by " . $peopleUp['firstname'] . " " . $peopleUp['lastname'] . " from " . $region_codeUp . " has been deleted by <b>" . $delete_user_fullname . ".</b></div><br/>" .
                    "<div>The following reason was provided for this deletion: <strong>" . $deletion_rs . "</strong></div><br/>" .
                    "<div>You will receive an email to alert you if a replacement dataset is available for download. </div><br/>" .
                    "<span style='color:#777'>Please email <a href='mailto:".$settings['hub_contact_email']."'>".$settings['hub_contact_email']."</a> with any questions.</span>";

                sendEmail($down['email'], $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], $subject, $message, $down['id'],"Dataset deleted",$pidsArray['DATAUPLOAD']);
            }

        }
    }
} catch (S3Exception $e) {
    echo $e->getMessage() . "\n";
}

echo json_encode("success");
?>