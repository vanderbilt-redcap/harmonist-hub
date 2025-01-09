<?php
namespace Vanderbilt\HarmonistHubExternalModule;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
include_once(dirname(dirname(dirname(__FILE__))) . "/email.php");

$hub_mapper = $module->getProjectSetting('hub-mapper');
if($hub_mapper != "") {
    $pidsArray = REDCapManagement::getPIDsArray($hub_mapper);
    if ($pid == $pidsArray['DATADOWNLOADUSERS']) {
        $settings = \REDCap::getData($pidsArray['SETTINGS'], 'json-array', null)[0];

        if(!empty($settings)){
            $settings = $module->escape($settings);
        }else{
            $settings = htmlspecialchars($settings,ENT_QUOTES);
        }

        #Escape name just in case they add quotes
        if(!empty($settings["hub_name"])) {
            $settings["hub_name"] = addslashes($settings["hub_name"]);
        }

        #Sanitize text title and descrition for pages
        $settings = ProjectData::sanitizeALLVariablesFromInstrument($module,$pidsArray['SETTINGS'],array(0=>"harmonist_text"),$settings);

        $default_values = new ProjectData;
        $default_values_settings = $default_values->getDefaultValues($pidsArray['SETTINGS']);

        if($settings['deactivate_datadown___1'] != "1" && $settings['deactivate_datahub___1'] != "1"){

            session_start();
            $token = "";
            if(defined("USERID") && !empty(getToken(USERID, $pidsArray['PEOPLE']))){
                $_SESSION['token'] = array();
                $_SESSION['token'][$settings['hub_name'].$pidsArray['PROJECTS']] = getToken(USERID, $pidsArray['PEOPLE']);
                $token = $_SESSION['token'][$settings['hub_name'].$pidsArray['PROJECTS']];
            }else if(array_key_exists('token', $_REQUEST)  && !empty($_REQUEST['token']) && isTokenCorrect($_REQUEST['token'],$pidsArray['PEOPLE'])){
                $token = $_REQUEST['token'];
            }else if(!empty($_SESSION['token'][$settings['hub_name'].$pidsArray['PROJECTS']])&& isTokenCorrect($_SESSION['token'][$settings['hub_name'].$pidsArray['PROJECTS']],$pidsArray['PEOPLE'])) {
                $token = $_SESSION['token'][$settings['hub_name'].$pidsArray['PROJECTS']];
            }
            if(array_key_exists('token', $_REQUEST)  && !empty($_REQUEST['token']) && isTokenCorrect($_REQUEST['token'],$pidsArray['PEOPLE'])) {
                $_SESSION['token'][$settings['hub_name'].$pidsArray['PROJECTS']] = $_REQUEST['token'];
            }

            if(file_exists("/app001/credentials/Harmonist-Hub/" . $pidsArray['PROJECTS'] . "_aws_s3.php")) {
                require_once "/app001/credentials/Harmonist-Hub/" . $pidsArray['PROJECTS'] . "_aws_s3.php";
            }

            $code = getCrypt($_REQUEST['code'],"d",$secret_key,$secret_iv);
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

            if($request_DU['deleted_y'] != '1' && $request_DU != '' && !empty($_SESSION['token'][$settings['hub_name'].$pidsArray['PROJECTS']])&& isTokenCorrect($_SESSION['token'][$settings['hub_name'].$pidsArray['PROJECTS']],$pidsArray['PEOPLE'])) {
                $RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', array('record_id' => $request_DU['data_assoc_request']));
                $sop = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP,$pidsArray['SOP'])[0];
                $array_userid = explode(',', $sop['sop_downloaders']);
                $token = $_SESSION['token'][$settings['hub_name'].$pidsArray['PROJECTS']];
                $RecordSetCurrentUser = \REDCap::getData($pidsArray['PEOPLE'], 'array', array('access_token' => $token));
                $current_user = $exploded['user_id'];
                if (!empty($current_user) && ($request_DU['data_upload_person'] == $current_user || ($key = array_search($current_user, $array_userid)) !== false)) {
                    try {
                        #Get the object
                        $result = $s3->getObject(array(
                                                     'Bucket' => $request_DU['data_upload_bucket'],
                                                     'Key' => $request_DU['data_upload_folder'] . $request_DU['data_upload_zip']
                                                 ));

                        $persondown = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', null, null, null, null, false, false, false, "[redcap_name] = '" . USERID . "'")[0];
                        $downloader = $persondown['record_id'];
                        $downloader_region = $persondown['person_region'];

                        $region_codeDown = \REDCap::getData($pidsArray['REGIONS'], 'json-array', array('record_id' => $downloader_region),array('region_code'))[0]['region_code'];
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
                        $results = \Records::saveData($pidsArray['DATADOWNLOAD'], 'array', $recordSaveDU, 'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);

                        $date = new \DateTime($download_time);
                        $date->modify("+1 hours");
                        $download_time_et = $date->format("Y-m-d H:i");

                        #EMAIL NOTIFICATION
                        $RecordSetConcepts = \REDCap::getData($pidsArray['HARMONIST'], 'array', array('record_id' => $request_DU['data_assoc_concept']));
                        $concepts = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConcepts,$pidsArray['HARMONIST'])[0];
                        $concept_id = $concepts['concept_id'];

                        $peopleUp = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array('record_id' => $request_DU['data_upload_person']))[0];

                        $region_codeUp = \REDCap::getData($pidsArray['REGIONS'], 'json-array', array('record_id' => $peopleUp['person_region']))[0]['region_code'];

                        $date = new \DateTime($request_DU['responsecomplete_ts']);
                        $date->modify("+1 hours");
                        $date_time = $date->format("Y-m-d H:i");
                        $extra_days = ' + ' . $settings['retrievedata_expiration'] . " days";
                        $expire_date = date('Y-m-d', strtotime($date_time . $extra_days));

                        $RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', array('record_id' => $request_DU['data_assoc_request']));
                        $sop = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP,$pidsArray['SOP'])[0];

                        #to uploader user
                        $subject = "Your " . $settings['hub_name'] . " " . $concept_id . " dataset was downloaded";
                        $message = "<div>Dear " . $peopleUp['firstname'] . ",</div><br/><br/>" .
                            "<div>The dataset you submitted to secure cloud storage in response to <strong>\"" . $sop['sop_name'] . "\"</strong> on " . $date_time . " Eastern US Time (ET) has been downloaded by <b>" . $downloader_all . "</b> at " . $download_time_et . ".</div><br/>" .
                            "<div>Your dataset will remain available for download until <span style='color:red;font-weight: bold'>" . $expire_date . " 23:59 ET</span>.</div><br/>" .
                            "<span style='color:#777'>Please email <a href='mailto:" . $settings['hub_contact_email'] . "'>" . $settings['hub_contact_email'] . "</a> with any questions.</span>";
                        sendEmail($peopleUp['email'], $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], $subject, $message, $request_DU['data_upload_person'], "Dataset downloaded", $pidsArray['DATADOWNLOAD']);

                        if ($request_DU['data_upload_person'] != $downloader) {
                            $peopleDown = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array('record_id' => $downloader))[0];

                            #to downloader
                            $subject = "Confirmation of " . $settings['hub_name'] . " " . $concept_id . " dataset download";
                            $message = "<div>Dear " . $peopleDown['firstname'] . ",</div><br/><br/>" .
                                "<div>This email serves as your confirmation that at " . $download_time_et . " Eastern US Time (ET), you downloaded the dataset submitted by " . $peopleUp['firstname'] . " " . $peopleUp['lastname'] .
                                " from " . $region_codeUp . " in response to <strong>\"" . $sop['sop_name'] . "\"</strong> (uploaded on " . $date_time . " ET).</div><br/>" .
                                "<div>The dataset will remain available for download until <span style='color:red;font-weight: bold'>" . $expire_date . " 23:59 ET</span>.</div><br/>" .
                                "<span style='color:#777'>Please email <a href='mailto:" . $settings['hub_contact_email'] . "'>" . $settings['hub_contact_email'] . "</a> with any questions.</span>";
                            sendEmail($peopleDown['email'], $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], $subject, $message, $downloader, "Dataset downloaded", $pidsArray['DATADOWNLOAD']);
                        }


                        #Display the object in the browser
                        header("Content-Type: {$result['ContentType']}");
                        header('Content-Disposition: attachment; filename="' . $request_DU['data_upload_zip'] . '"');
                        echo $result['Body'];
                    } catch (S3Exception $e) {
                        echo $e->getMessage() . "\n";
                    }
                }
            }
        }
    }
}
?>