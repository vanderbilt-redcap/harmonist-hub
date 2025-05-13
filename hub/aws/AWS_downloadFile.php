<?php
namespace Vanderbilt\HarmonistHubExternalModule;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
include_once(dirname(dirname(dirname(__FILE__))) . "/email.php");
include_once(dirname(dirname(dirname(__FILE__))) . "/classes/SecurityHandler.php");

\REDCap::email('eva.bascompte.moragas@vumc.org', 'eva.bascompte.moragas@vumc.org', "TEST", "IN AWS DOWN");

print_array("AWS");
if ($module->getSecurityHandler()->isAuthorizedPage()) {
    print_array("isAuthorizedPage");
    $pidsArray = $module->getSecurityHandler()->getPidsArray();
    $settings = $module->getSecurityHandler()->getSettingsData();
    if($settings['deactivate_datadown___1'] != "1" && $settings['deactivate_datahub___1'] != "1") {
        print_array("Data Downloads activated");
        require_once ($module->getSecurityHandler()->getCredentialsServerVars("ENCRYPTION"));
        require_once ($module->getSecurityHandler()->getCredentialsServerVars("AWS"));

        $code = getCrypt($_REQUEST['code'], "d", $secret_key, $secret_iv);
        $exploded = array();
        parse_str($code, $exploded);

        $record_id = $exploded['id'];
        $request_DU = \REDCap::getData($pidsArray['DATAUPLOAD'], 'json-array', array('record_id' => $record_id))[0];

        $credentials = new \Aws\Credentials\Credentials($aws_key, $aws_secret);
        $s3 = new S3Client([
                               'version' => 'latest',
                               'region' => 'us-east-2',
                               'credentials' => $credentials
                           ]);
        print_array("after Credentials");
        if ($request_DU['deleted_y'] != '1' && $request_DU != '' && !empty($_SESSION[SecurityHandler::SESSION_TOKEN_STRING][$settings['hub_name'] . $pidsArray['PROJECTS']]) && $module->getSecurityHandler()->isTokenCorrect(
                $_SESSION[SecurityHandler::SESSION_TOKEN_STRING][$settings['hub_name'] . $pidsArray['PROJECTS']]
            )) {
            $RecordSetSOP = \REDCap::getData(
                $pidsArray['SOP'],
                'array',
                array('record_id' => $request_DU['data_assoc_request'])
            );
            $sop = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP, $pidsArray['SOP'])[0];
            $array_userid = explode(',', $sop['sop_downloaders']);
            $current_user = $exploded['user_id'];
            $userData = \REDCap::getData(
                $pidsArray['PEOPLE'],
                'json-array',
                array('record_id' => $current_user),
                array('harmonistadmin_y', 'redcap_name', 'active_y')
            )[0];
            print_array("Token Correct");
            print_array("USERID: ".USERID);
            if (!empty($current_user) && $userData['redcap_name'] == USERID && $userData['active_y'] == "1" && ($request_DU['data_upload_person'] == $current_user || ($key = array_search(
                        $current_user,
                        $array_userid
                    )) !== false)) {
                try {
                    print_array("We are in!");
                    #Get the object as a link
                    $cmd = $s3->getCommand('GetObject', [
                        'Bucket' => $request_DU['data_upload_bucket'],
                        'Key' => $request_DU['data_upload_folder'] . $request_DU['data_upload_zip'],
                        'ResponseContentDisposition' => 'attachment; filename="'.$request_DU['data_upload_zip'].'"'
                    ]);

                    $request = $s3->createPresignedRequest($cmd, '+15 min');
                    $presignedUrl = (string)$request->getUri();

                    $persondown = \REDCap::getData(
                        $pidsArray['PEOPLE'],
                        'json-array',
                        null,
                        null,
                        null,
                        null,
                        false,
                        false,
                        false,
                        "[redcap_name] = '" . USERID . "'"
                    )[0];
                    $downloader = $persondown['record_id'];
                    $downloader_region = $persondown['person_region'];

                    $region_codeDown = \REDCap::getData(
                        $pidsArray['REGIONS'],
                        'json-array',
                        array('record_id' => $downloader_region),
                        array('region_code')
                    )[0]['region_code'];
                    $downloader_all = "<a href='" . $persondown['email'] . "'>" . $persondown['firstname'] . " " . $persondown['lastname'] . "</a> (" . $region_codeDown . ")";
                    $download_time = date("Y-m-d H:i:s");

                    $Proj = new \Project($pidsArray['DATADOWNLOAD']);
                    $event_id = $Proj->firstEventId;
                    $recordSaveDU = array();
                    $recordDown = $module->framework->addAutoNumberedRecord($pidsArray['DATADOWNLOAD']);
                    $recordSaveDU[$recordDown][$event_id]['record_id'] = $recordDown;
                    $recordSaveDU[$recordDown][$event_id]['downloader_assoc_concept'] = $request_DU['data_assoc_concept'];
                    $recordSaveDU[$recordDown][$event_id]['downloader_id'] = $downloader;
                    $recordSaveDU[$recordDown][$event_id]['downloader_region'] = $downloader_region;
                    $recordSaveDU[$recordDown][$event_id]['downloader_rcuser'] = USERID;
                    $recordSaveDU[$recordDown][$event_id]['download_id'] = $record_id;
                    $recordSaveDU[$recordDown][$event_id]['download_files'] = $request_DU['data_upload_zip'];
                    $recordSaveDU[$recordDown][$event_id]['responsecomplete_ts'] = $download_time;
                    $results = \Records::saveData(
                        $pidsArray['DATADOWNLOAD'],
                        'array',
                        $recordSaveDU,
                        'overwrite',
                        'YMD',
                        'flat',
                        '',
                        true,
                        true,
                        true,
                        false,
                        true,
                        array(),
                        true,
                        false
                    );

                    $date = new \DateTime($download_time);
                    $date->modify("+1 hours");
                    $download_time_et = $date->format("Y-m-d H:i");

                    #EMAIL NOTIFICATION
                    $RecordSetConcepts = \REDCap::getData(
                        $pidsArray['HARMONIST'],
                        'array',
                        array('record_id' => $request_DU['data_assoc_concept'])
                    );
                    $concepts = ProjectData::getProjectInfoArrayRepeatingInstruments(
                        $RecordSetConcepts,
                        $pidsArray['HARMONIST']
                    )[0];
                    $concept_id = $concepts['concept_id'];

                    $peopleUp = \REDCap::getData(
                        $pidsArray['PEOPLE'],
                        'json-array',
                        array('record_id' => $request_DU['data_upload_person'])
                    )[0];

                    $region_codeUp = \REDCap::getData(
                        $pidsArray['REGIONS'],
                        'json-array',
                        array('record_id' => $peopleUp['person_region'])
                    )[0]['region_code'];

                    $date = new \DateTime($request_DU['responsecomplete_ts']);
                    $date->modify("+1 hours");
                    $date_time = $date->format("Y-m-d H:i");
                    $extra_days = ' + ' . $settings['retrievedata_expiration'] . " days";
                    $expire_date = date('Y-m-d', strtotime($date_time . $extra_days));

                    $RecordSetSOP = \REDCap::getData(
                        $pidsArray['SOP'],
                        'array',
                        array('record_id' => $request_DU['data_assoc_request'])
                    );
                    $sop = ProjectData::getProjectInfoArrayRepeatingInstruments(
                        $RecordSetSOP,
                        $pidsArray['SOP']
                    )[0];

                    #to uploader user
                    $subject = "Your " . $settings['hub_name'] . " " . $concept_id . " dataset was downloaded";
                    $message = "<div>Dear " . $peopleUp['firstname'] . ",</div><br/><br/>" .
                        "<div>The dataset you submitted to secure cloud storage in response to <strong>\"" . $sop['sop_name'] . "\"</strong> on " . $date_time . " Eastern US Time (ET) has been downloaded by <b>" . $downloader_all . "</b> at " . $download_time_et . ".</div><br/>" .
                        "<div>Your dataset will remain available for download until <span style='color:red;font-weight: bold'>" . $expire_date . " 23:59 ET</span>.</div><br/>" .
                        "<span style='color:#777'>Please email <a href='mailto:" . $settings['hub_contact_email'] . "'>" . $settings['hub_contact_email'] . "</a> with any questions.</span>";
                    sendEmail(
                        $peopleUp['email'],
                        $settings['accesslink_sender_email'],
                        $settings['accesslink_sender_name'],
                        $subject,
                        $message,
                        $request_DU['data_upload_person'],
                        "Dataset downloaded",
                        $pidsArray['DATADOWNLOAD']
                    );

                    if ($request_DU['data_upload_person'] != $downloader) {
                        $peopleDown = \REDCap::getData(
                            $pidsArray['PEOPLE'],
                            'json-array',
                            array('record_id' => $downloader)
                        )[0];

                        #to downloader
                        $subject = "Confirmation of " . $settings['hub_name'] . " " . $concept_id . " dataset download";
                        $message = "<div>Dear " . $peopleDown['firstname'] . ",</div><br/><br/>" .
                            "<div>This email serves as your confirmation that at " . $download_time_et . " Eastern US Time (ET), you downloaded the dataset submitted by " . $peopleUp['firstname'] . " " . $peopleUp['lastname'] .
                            " from " . $region_codeUp . " in response to <strong>\"" . $sop['sop_name'] . "\"</strong> (uploaded on " . $date_time . " ET).</div><br/>" .
                            "<div>The dataset will remain available for download until <span style='color:red;font-weight: bold'>" . $expire_date . " 23:59 ET</span>.</div><br/>" .
                            "<span style='color:#777'>Please email <a href='mailto:" . $settings['hub_contact_email'] . "'>" . $settings['hub_contact_email'] . "</a> with any questions.</span>";
                        sendEmail(
                            $peopleDown['email'],
                            $settings['accesslink_sender_email'],
                            $settings['accesslink_sender_name'],
                            $subject,
                            $message,
                            $downloader,
                            "Dataset downloaded",
                            $pidsArray['DATADOWNLOAD']
                        );
                    }

                    #Download the object in the browser
                    header("Content-Type: application/zip, application/octet-stream");
                    header('Content-Disposition: attachment; filename="'.$request_DU['data_upload_zip'].'"');
                    header("Location: " . $presignedUrl);
                } catch (S3Exception $e) {
                    echo $e->getMessage() . "\n";
                }
            }
        }
    }
}
?>