<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include_once(__DIR__ ."/../projects.php");
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
require_once "/app001/credentials/".IEDEA_PROJECTS."_hubsettings.php";

$code = \Vanderbilt\HarmonistHubExternalModule\getCrypt($_REQUEST['code'],"d",$secret_key,$secret_iv);
$exploded = array();
parse_str($code, $exploded);

$record_id = $exploded['id'];
$RecordSetDU = \REDCap::getData(IEDEA_DATAUPLOAD, 'array', array('record_id' => $record_id));
$request_DU = ProjectData::getProjectInfoArray($RecordSetDU)[0];

$credentials = new Aws\Credentials\Credentials($aws_key, $aws_secret);
$s3 = new S3Client([
    'version' => 'latest',
    'region' => 'us-east-2',
    'credentials' => $credentials
]);

if($request_DU['deleted_y'] != '1' && $request_DU != '' && $_SESSION['token'][$settings['hub_name'].constant(ENVIRONMENT.'_IEDEA_PROJECTS')]) {
    try {
        #Get the object
        $result = $s3->getObject(array(
            'Bucket' => $request_DU['data_upload_bucket'],
            'Key' => $request_DU['data_upload_folder'] . $request_DU['data_upload_zip']
        ));

        $RecordSetPerson = \REDCap::getData(IEDEA_PEOPLE, 'array', null,null,null,null,false,false,false,"[redcap_name] = '".USERID."'");
        $persondown = ProjectData::getProjectInfoArray($RecordSetPerson)[0];
        $downloader = $persondown['record_id'];
        $downloader_region = $persondown['person_region'];

        $RecordSetRegionsDown = \REDCap::getData(IEDEA_REGIONS, 'array', array('record_id' => $downloader_region));
        $region_codeDown = ProjectData::getProjectInfoArray($RecordSetRegionsDown)[0]['region_code'];
        $downloader_all = "<a href='".$persondown['email']."'>".$persondown['firstname']." ".$persondown['lastname']."</a> (".$region_codeDown.")";
        $download_time = date("Y-m-d H:i:s");

        $Proj = new \Project(IEDEA_DATADOWNLOAD);
        $event_id = $Proj->firstEventId;
        $recordSaveDU = array();
        $recordSaveDU[$record_id][$event_id]['record_id'] = $record_id;
        $recordSaveDU[$record_id][$event_id]['downloader_assoc_concept'] = $request_DU['data_assoc_concept'];
        $recordSaveDU[$record_id][$event_id]['downloader_id'] = $downloader;
        $recordSaveDU[$record_id][$event_id]['downloader_region'] = $downloader_region;
        $recordSaveDU[$record_id][$event_id]['downloader_rcuser'] = USERID;
        $recordSaveDU[$record_id][$event_id]['download_id'] = $record_id;
        $recordSaveDU[$record_id][$event_id]['download_files'] = $request_DU['data_upload_zip'];
        $recordSaveDU[$record_id][$event_id]['responsecomplete_ts'] = $download_time;
        $results = \Records::saveData(IEDEA_DATADOWNLOAD, 'array', $recordSaveDU,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
        \Records::addRecordToRecordListCache(IEDEA_DATADOWNLOAD, $record_id,1);

        $date = new \DateTime($download_time);
        $date->modify("+1 hours");
        $download_time_et = $date->format("Y-m-d H:i");

        #EMAIL NOTIFICATION
        $RecordSetConcepts = \REDCap::getData(IEDEA_HARMONIST, 'array', array('record_id' => $request_DU['data_assoc_concept']));
        $concepts = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConcepts)[0];
        $concept_id = $concepts['concept_id'];

        $RecordSetPeopleUp = \REDCap::getData(IEDEA_PEOPLE, 'array', array('record_id' => $request_DU['data_upload_person']));
        $peopleUp = ProjectData::getProjectInfoArray($RecordSetPeopleUp)[0];

        $RecordSetRegionsUp = \REDCap::getData(IEDEA_REGIONS, 'array', array('record_id' => $peopleUp['person_region']));
        $region_codeUp = ProjectData::getProjectInfoArray($RecordSetRegionsUp)[0]['region_code'];;

        $date = new \DateTime($request_DU['responsecomplete_ts']);
        $date->modify("+1 hours");
        $date_time = $date->format("Y-m-d H:i");
        $extra_days = ' + ' . $settings['retrievedata_expiration'] . " days";
        $expire_date = date('Y-m-d', strtotime($date_time . $extra_days));

        $RecordSetSOP = \REDCap::getData(IEDEA_SOP, 'array', array('record_id' => $request_DU['data_assoc_request']));
        $sop = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP)[0];

        #to uploader user
        $subject = "Your ".$settings['hub_name']." ".$concept_id." dataset was downloaded";
        $message = "<div>Dear " . $peopleUp['firstname'] . ",</div><br/><br/>" .
            "<div>The dataset you submitted to secure cloud storage in response to <strong>\"" . $sop['sop_name'] . "\"</strong> on " . $date_time . " Eastern US Time (ET) has been downloaded by <b>".$downloader_all."</b> at ".$download_time_et.".</div><br/>".
            "<div>Your dataset will remain available for download until <span style='color:red;font-weight: bold'>" . $expire_date . " 23:59 ET</span>.</div><br/>".
            "<span style='color:#777'>Please email <a href='mailto:".$settings['hub_contact_email']."'>".$settings['hub_contact_email']."</a> with any questions.</span>";
        sendEmail($peopleUp['email'], $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], $subject, $message, $request_DU['data_upload_person']);

        if($request_DU['data_upload_person'] != $downloader){
            $RecordSetPeopleDown = \REDCap::getData(IEDEA_PEOPLE, 'array', array('record_id' => $downloader));
            $peopleDown = ProjectData::getProjectInfoArray($RecordSetPeopleDown)[0];

            #to downloader
            $subject = "Confirmation of ".$settings['hub_name']." ".$concept_id." dataset download";
            $message = "<div>Dear " . $peopleDown['firstname'] . ",</div><br/><br/>" .
                "<div>This email serves as your confirmation that at ".$download_time_et." Eastern US Time (ET), you downloaded the dataset submitted by ".$peopleUp['firstname']." ".$peopleUp['lastname'].
                " from ".$region_codeUp." in response to <strong>\"" . $sop['sop_name'] . "\"</strong> (uploaded on ".$date_time." ET).</div><br/>".
                "<div>The dataset will remain available for download until <span style='color:red;font-weight: bold'>" . $expire_date . " 23:59 ET</span>.</div><br/>".
                "<span style='color:#777'>Please email <a href='mailto:".$settings['hub_contact_email']."'>".$settings['hub_contact_email']."</a> with any questions.</span>";
            sendEmail($peopleDown['email'], $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], $subject, $message, $downloader);
        }


        #Display the object in the browser
        header("Content-Type: {$result['ContentType']}");
        header('Content-Disposition: attachment; filename="' . $request_DU['data_upload_zip'] . '"');
        echo $result['Body'];
    } catch (S3Exception $e) {
        echo $e->getMessage() . "\n";
    }
}
?>