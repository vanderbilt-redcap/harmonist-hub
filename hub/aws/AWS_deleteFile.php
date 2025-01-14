<?php
namespace Vanderbilt\HarmonistHubExternalModule;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

include_once(dirname(dirname(dirname(__FILE__))) . "/email.php");
include_once(dirname(dirname(dirname(__FILE__))) . "/classes/SecurityHandler.php");
if ($module->getSecurityHandler()->isAuthorizedPage()) {
    $settings = $module->getSecurityHandler()->getSetttingsData();
    if($settings['deactivate_datahub___1'] != "1") {
        $module->getSecurityHandler()->getAwsCredentialsServerVars();
        $module->getSecurityHandler()->getEncryptionCredentialsServerVars();

        $code = getCrypt($_REQUEST['code'], "d", $secret_key, $secret_iv);
        $exploded = array();
        parse_str($code, $exploded);

        $record_id = $exploded['id'];
        $current_user = $exploded['idu'];
        $deletion_rs = $_REQUEST['deletion_rs'];
        $request_DU = \REDCap::getData($pidsArray['DATAUPLOAD'], 'json-array', array('record_id' => $record_id))[0];

        $credentials = new \Aws\Credentials\Credentials($aws_key, $aws_secret);
        $s3 = new S3Client([
                               'version' => 'latest',
                               'region' => 'us-east-2',
                               'credentials' => $credentials
                           ]);
        try {
            if($request_DU['deleted_y'] != '1' && $request_DU != '' && !empty($_SESSION['token'][$settings['hub_name'].$pidsArray['PROJECTS']])&& isTokenCorrect($_SESSION['token'][$settings['hub_name'].$pidsArray['PROJECTS']],$pidsArray['PEOPLE'])) {
                $userData = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array('record_id' => $current_user),array('harmonistadmin_y','redcap_name'))[0];
                if (!empty($current_user) && $userData['redcap_name'] == USERID && ($request_DU['data_upload_person'] == $current_user || $userData['harmonistadmin_y'])){
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
                    $recordSaveDU[$record_id][$event_id]['deletion_hubuser'] = $current_user;
                    $date = new \DateTime();
                    $recordSaveDU[$record_id][$event_id]['deletion_ts'] = $date->format('Y-m-d H:i:s');
                    $recordSaveDU[$record_id][$event_id]['deletion_rs'] = $deletion_rs;
                    $recordSaveDU[$record_id][$event_id]['deletion_information_complete'] = "2";
                    $recordSaveDU[$record_id][$event_id]['deleted_y'] = "1";
                    $results = \Records::saveData(
                        $pidsArray['DATAUPLOAD'],
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
                    \Records::addRecordToRecordListCache($pidsArray['DATAUPLOAD'], $record_id, 1);

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
                    $concept_title = $concepts['concept_title'];

                    $peopleUp = \REDCap::getData(
                        $pidsArray['PEOPLE'],
                        'json-array',
                        array('record_id' => $request_DU['data_upload_person'])
                    )[0];

                    $region_codeUp = \REDCap::getData(
                        $pidsArray['REGIONS'],
                        'json-array',
                        array('record_id' => $peopleUp['person_region']),
                        array('region_code')
                    )[0]['region_code'];

                    $date = new \DateTime($request_DU['responsecomplete_ts']);
                    $date->modify("+1 hours");
                    $date_time = $date->format("Y-m-d H:i");

                    $RecordSetSOP = \REDCap::getData(
                        $pidsArray['SOP'],
                        'array',
                        array('record_id' => $request_DU['data_assoc_request'])
                    );
                    $sop = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP, $pidsArray['SOP'])[0];

                    $delete_user = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array('record_id' => $current_user))[0];
                    $delete_user_fullname = $delete_user['firstname'] . " " . $delete_user['lastname'];
                    $delete_user_name = $delete_user['firstname'];

                    if ($current_user == $request_DU['data_upload_person']) {
                        $subject = "Confirmation of " . $settings['hub_name'] . " " . $concept_id . " dataset deletion";
                        $message = "<div>Dear " . $peopleUp['firstname'] . ",</div><br/><br/>" .
                            "<div>The dataset you submitted to secure cloud storage in response to <strong>\"" . $concept_id . ": " . $concept_title . "\"</strong> <em>(Draft ID: " . $sop['record_id'] . ")</em>, on " . $date_time . " Eastern US Time (ET) has been deleted successfully at your request and will not be available for future downloads.</div><br/>" .
                            "<div>The following reason was logged for this deletion: <strong>" . $deletion_rs . "</strong></div><br/>" .
                            "<div>To replace the deleted dataset, log in to the " . $settings['hub_name'] . " Hub and select <strong>Submit Data on the <a href='" . $module->getUrl(
                                APP_PATH_PLUGIN . "/index.php"
                            ) . "&NOAUTH&option=dat" . "' target='_blank'>Data page</a></strong>.</div><br/>" .
                            "<span style='color:#777'>Please email <a href='mailto:" . $settings['hub_contact_email'] . "'>" . $settings['hub_contact_email'] . "</a> with any questions.</span>";
                        sendEmail(
                            $peopleUp['email'],
                            $settings['accesslink_sender_email'],
                            $settings['accesslink_sender_name'],
                            $subject,
                            $message,
                            $request_DU['data_upload_person'],
                            "Dataset deleted",
                            $pidsArray['DATAUPLOAD']
                        );
                    } else {
                        $subject = "Notification of " . $settings['hub_name'] . " " . $concept_id . " dataset deletion";
                        $message = "<div>Dear " . $peopleUp['firstname'] . ",</div><br/><br/>" .
                            "<div>The dataset you submitted to secure cloud storage in response to <strong>\"" . $concept_id . ": " . $concept_title . "\"</strong> <em>(Draft ID: " . $sop['record_id'] . ")</em>, on " . $date_time . " Eastern US Time (ET) has been deleted by " . $delete_user_fullname . " and will not be available for future downloads.</div><br/>" .
                            "<div>The following reason was logged for this deletion: <strong>" . $deletion_rs . "</strong></div><br/>" .
                            "<div>To replace the deleted dataset, log in to the " . $settings['hub_name'] . " Hub and select <strong>Submit Data on the <a href='" . $module->getUrl(
                                APP_PATH_PLUGIN . "/index.php"
                            ) . "&NOAUTH&option=dat" . "' target='_blank'>Data page</a></strong>.</div><br/>" .
                            "<span style='color:#777'>Please email <a href='mailto:" . $settings['hub_contact_email'] . "'>" . $settings['hub_contact_email'] . "</a> with any questions.</span>";
                        sendEmail(
                            $peopleUp['email'],
                            $settings['accesslink_sender_email'],
                            $settings['accesslink_sender_name'],
                            $subject,
                            $message,
                            $request_DU['data_upload_person'],
                            "Dataset deleted",
                            $pidsArray['DATAUPLOAD']
                        );

                        #To deletetion user
                        $subject = "Confirmation  of " . $settings['hub_name'] . " " . $concept_id . " dataset deletion";
                        $message = "<div>Dear " . $delete_user_name . ",</div><br/><br/>" .
                            "<div>The dataset submitted to secure cloud storage by <strong>" . $peopleUp['firstname'] . " " . $peopleUp['lastname'] . "</strong> in response to  <b>\"" . $concept_id . ": " . $concept_title . "\"</b> <em>(Draft ID: " . $sop['record_id'] . ")</em>,on " . $date_time . " Eastern US Time (ET) has been deleted successfully at your request and will not be available for future downloads.</div><br/>" .
                            "<div>The following reason was logged for this deletion: <strong>" . $deletion_rs . "</strong></div><br/>" .
                            "<span style='color:#777'>Please email <a href='mailto:" . $settings['hub_contact_email'] . "'>" . $settings['hub_contact_email'] . "</a> with any questions.</span>";
                        sendEmail(
                            $delete_user['email'],
                            $settings['accesslink_sender_email'],
                            $settings['accesslink_sender_name'],
                            $subject,
                            $message,
                            $current_user,
                            "Dataset deleted",
                            $pidsArray['DATAUPLOAD']
                        );
                    }
                    \REDCap::logEvent(
                        "Dataset deleted manually\nRecord " . $request_DU['record_id'],
                        "Concept ID: " . $concept_id . "\n Draft ID: " . $sop['record_id'] . "\n Deleted by: " . $delete_user_fullname,
                        null,
                        null,
                        null,
                        $pidsArray['DATAUPLOAD']
                    );

                    #Email to Downloaders
                    $downloaders_list = "";
                    if ($sop['sop_downloaders'] != "") {
                        $downloaders = explode(',', $sop['sop_downloaders']);
                        $number_downloaders = count($downloaders);
                        $downloaders_list = "<ol>";
                        $downloadersOrdered = array();
                        foreach ($downloaders as $down) {
                            $peopleDown = \REDCap::getData(
                                $pidsArray['PEOPLE'],
                                'json-array',
                                array('record_id' => $down)
                            )[0];
                            $region_codeDown = \REDCap::getData(
                                $pidsArray['REGIONS'],
                                'json-array',
                                array('record_id' => $peopleDown['person_region']),
                                array('region_code')
                            )[0]['region_code'];

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

                        $person = \REDCap::getData(
                            $pidsArray['PEOPLE'],
                            'json-array',
                            array('record_id' => $request_DU['data_upload_person'])
                        )[0];
                        $firstname = $person['firstname'];
                        $name_uploader = $person['firstname'] . " " . $person['lastname'];
                        $region_code_uploader = \REDCap::getData(
                            $pidsArray['REGIONS'],
                            'json-array',
                            array('record_id' => $person['person_region']),
                            array('region_code')
                        )[0]['region_code'];

                        $RecordSetConcepts = \REDCap::getData(
                            $pidsArray['HARMONIST'],
                            'array',
                            array('record_id' => $request_DU['data_assoc_concept'])
                        );
                        $concept_id = ProjectData::getProjectInfoArrayRepeatingInstruments(
                            $RecordSetConcepts,
                            $pidsArray['HARMONIST']
                        )[0]['concept_id'];

                        $subject = "Notification of " . $settings['hub_name'] . " " . $concept_id . " dataset deletion";
                        foreach ($downloadersOrdered as $down) {
                            $message = "<div>Dear " . $down['firstname'] . ",</div><br/><br/>" .
                                "<div>The dataset previously submitted in response to <strong>\"" . $sop['sop_name'] . "\"</strong> on " . $date_time . " Eastern US Time (ET) by " . $peopleUp['firstname'] . " " . $peopleUp['lastname'] . " from " . $region_codeUp . " has been deleted by <b>" . $delete_user_fullname . ".</b></div><br/>" .
                                "<div>The following reason was provided for this deletion: <strong>" . $deletion_rs . "</strong></div><br/>" .
                                "<div>You will receive an email to alert you if a replacement dataset is available for download. </div><br/>" .
                                "<span style='color:#777'>Please email <a href='mailto:" . $settings['hub_contact_email'] . "'>" . $settings['hub_contact_email'] . "</a> with any questions.</span>";

                            sendEmail(
                                $down['email'],
                                $settings['accesslink_sender_email'],
                                $settings['accesslink_sender_name'],
                                $subject,
                                $message,
                                $down['id'],
                                "Dataset deleted",
                                $pidsArray['DATAUPLOAD']
                            );
                        }
                    }
                }
            }

            session_start();
            $_SESSION['token'][$settings['hub_name'].$pidsArray['PROJECTS']] = $delete_user['access_token'];
            $returnToDataActivity = preg_replace('/pid=(\d+)/', "pid=".$pidsArray['PROJECTS'],$module->getUrl('index.php'))."&NOAUTH&option=lgd&message=D";
            header("Location: ".$returnToDataActivity);

        } catch (S3Exception $e) {
            echo $e->getMessage() . "\n";
        }
    }
}
?>