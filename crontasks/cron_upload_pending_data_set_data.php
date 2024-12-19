<?php

namespace Vanderbilt\HarmonistHubExternalModule;

include_once(__DIR__ . "/../projects.php");
require_once(dirname(dirname(__FILE__)) . "/classes/AllCrons.php");

use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use REDCap;
error_log("IeDEA HUB: cron_upload_pending_data_set_data BEFORE credentials PROJECTS: ".$pidsArray['PROJECTS']."_aws_s3.php");
if(file_exists("/app001/credentials/Harmonist-Hub/".$pidsArray['PROJECTS']."_aws_s3.php")) {
    require_once "/app001/credentials/Harmonist-Hub/" . $pidsArray['PROJECTS'] . "_aws_s3.php";
    error_log("IeDEA HUB: cron_upload_pending_data_set_data AFTER credentials");
    $credentials = new Credentials($aws_key, $aws_secret);
    $s3 = new S3Client([
                           'version' => 'latest',
                           'region' => 'us-east-2',
                           'credentials' => $credentials
                       ]);
    $bucket = 'shiny-app-test';

    try {
        //Get list of elements in folder
        $objects = $s3->getIterator('ListObjects', array(
            "Bucket" => $bucket,
            "Prefix" => "pending/"
        ));

        $settings = REDCap::getData($pidsArray['SETTINGS'], 'json-array', null)[0];

        foreach ($objects as $object) {
            $file_name = str_replace("pending/", '', $object['Key']);
            $file_name_extension = explode('.', $file_name)[1];
            $file_name = explode('.', $file_name)[0];

            if ($file_name_extension == 'json') {
                error_log("IeDEA HUB: cron_upload_pending_data_set_data File Found");
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
                if (!empty($uploadData)) {
                    $request_DU = REDCap::getData(
                        $pidsArray['DATAUPLOAD'],
                        'json-array',
                        null,
                        null,
                        null,
                        null,
                        false,
                        false,
                        false,
                        "[data_assoc_concept] = " . $uploadData[0]['data_assoc_concept'] .
                        " AND [data_assoc_request] = " . $uploadData[0]['data_assoc_request'] .
                        " AND [data_upload_person] = " . $uploadData[0]['data_upload_person'] .
                        " AND [data_upload_region] = " . $uploadData[0]['data_upload_region']
                    );
                    if (!empty($request_DU)) {
                        $found = false;
                        foreach ($request_DU as $upload) {
                            if (strtotime($upload['responsecomplete_ts']) == strtotime(
                                    $uploadData[0]['responsecomplete_ts']
                                ) || $upload['responsecomplete_ts'] == "") {
                                AllCrons::addUploadRecord(
                                    $this,
                                    $pidsArray,
                                    $s3,
                                    $uploadData,
                                    $file_name,
                                    $bucket,
                                    $settings,
                                    $upload['record_id']
                                );
                                $found = true;
                                break;
                            }
                        }
                        if (!$found) {
                            AllCrons::addUploadRecord(
                                $this,
                                $pidsArray,
                                $s3,
                                $uploadData,
                                $file_name,
                                $bucket,
                                $settings,
                                ""
                            );
                        }
                    } else {
                        #Record is missing, create new one
                        AllCrons::addUploadRecord(
                            $this,
                            $pidsArray,
                            $s3,
                            $uploadData,
                            $file_name,
                            $bucket,
                            $settings,
                            ""
                        );
                    }
                }
                error_log("IeDEA HUB: cron_upload_pending_data_set_data DELETE!!");
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
    } catch (S3Exception $e) {
        echo $e->getMessage() . "\n";
    }
}
?>
