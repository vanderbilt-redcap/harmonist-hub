<?php
include_once(__DIR__ ."/../projects.php");
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
require_once "/app001/credentials/".IEDEA_PROJECTS."_hubsettings.php";

$credentials = new Aws\Credentials\Credentials($aws_key, $aws_secret);
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

    foreach ($objects as $object) {
        $file_name = str_replace("pending/",'',$object['Key']);
        $file_name_extension = explode('.',$file_name)[1];
        $file_name = explode('.',$file_name)[0];

        if($file_name_extension == 'json') {
            #Get the object
            $result = $s3->getObject(array(
                'Bucket' => $bucket,
                'Key' => $object['Key']
            ));

            $s3->registerStreamWrapper();
            $data = file_get_contents('s3://'.$bucket.'/'.$object['Key']);
            // Open a stream in read-only mode
            if ($stream = fopen('s3://'.$bucket.'/'.$object['Key'], 'r')) {
                // While the stream is still open
                while (!feof($stream)) {
                    // Read 1,024 bytes from the stream
                    $uploadData = json_decode(fread($stream, 1024),true);
                }
                // Be sure to close the stream resource when you're done with it
                fclose($stream);
            }

            if($uploadData != ''){
                $RecordSetUpload = \REDCap::getData(IEDEA_DATAUPLOAD, 'array', null,null,null,null,false,false,false,
                    "[data_assoc_concept] = ".$uploadData[0]['data_assoc_concept']." AND [data_assoc_request] = ".$uploadData[0]['data_assoc_request'].
                    " AND [data_upload_person] = ".$uploadData[0]['data_upload_person']." AND [data_upload_region] = ".$uploadData[0]['data_upload_region']);
                $request_DU = getProjectInfoArray($RecordSetUpload)[0];

                if($request_DU != ""){
                    $found = false;
                    foreach ($request_DU as $upload){
                        if(strtotime($upload['responsecomplete_ts']) == strtotime($uploadData[0]['responsecomplete_ts']) || $upload['responsecomplete_ts'] == "") {
                            addUploadRecord($module, $s3, $uploadData, $file_name, $bucket, $settings, $upload['record_id']);
                            $found = true;
                            break;
                        }
                    }

                    if(!$found){
                        addUploadRecord($module, $s3, $uploadData, $file_name, $bucket, $settings, "");
                    }
                }else{
                    #Record is missing, create new one
                    addUploadRecord($module, $s3, $uploadData, $file_name, $bucket, $settings, "");
                }
            }

            #Delete the object after uploading the record
            #JSON
            $result = $s3->deleteObject(array(
                'Bucket' => $bucket,
                'Key'    => $object['Key']
            ));

            #REPORT
            $reportHash = "Report".str_replace("_details","",$file_name).".pdf";
            $result = $s3->deleteObject(array(
                'Bucket' => $bucket,
                'Key'    => 'pending/'.$reportHash
            ));
        }
    }

} catch (S3Exception $e) {
    echo $e->getMessage() . "\n";
}

function addUploadRecord($module, $s3, $uploadData, $file_name, $bucket, $settings, $record = ""){
    #Email Data
    $RecordSetConceptSheets = \REDCap::getData(IEDEA_HARMONIST, 'array',  array('record_id' => $uploadData[0]['data_assoc_concept']));
    $concept_id = getProjectInfoArrayRepeatingInstruments($RecordSetConceptSheets)[0]['concept_id'];

    $RecordSetSOP = \REDCap::getData(IEDEA_SOP, 'array',  array('record_id' => $uploadData[0]['data_assoc_request']));
    $sop = getProjectInfoArrayRepeatingInstruments($RecordSetSOP)[0];

    $uploader_name = getPeopleName($uploadData[0]['data_upload_person'],"");

    $RecordSetRegionsUp = \REDCap::getData(IEDEA_REGIONS, 'array', array('record_id' => $uploadData[0]['data_upload_region']));
    $region_codeUp = getProjectInfoArray($RecordSetRegionsUp)[0]['region_code'];

    $gotoredcap = APP_PATH_WEBROOT_ALL."DataEntry/record_status_dashboard.php?pid=".IEDEA_DATAUPLOAD;


    if($record != "") {
        $recordpdf = $record;

        $RecordSetUpload = \REDCap::getData(IEDEA_SOP, 'array',  array('record_id' => $record));
        $upload = getProjectInfoArrayRepeatingInstruments($RecordSetUpload)[0];

        $link = APP_PATH_WEBROOT_ALL . "DataEntry/record_home.php?pid=" . IEDEA_DATAUPLOAD . "&arm=1&id=" . $recordpdf;
        $message = "<div>Dear administrator,</div><br/>" .
            "<div>A pending upload dataset has been found on the Harmonist Data Toolkit server: </div><br/>".
            "<div><strong>\"" . $sop['sop_name'] . "\"</strong> uploaded by <em>" . $uploader_name . "</em> from ".$region_codeUp." on " . $uploadData[0]['responsecomplete_ts'] . " Eastern US Time (ET).</div><br/>".
            "<div>This upload record has now been added to the Hub REDCap project as <a href='".$link."'>Record ID ".$recordpdf."</a>.</div>".
            "<div>All associated notification emails have been activated.</div><br/>".
            "<div>Click here to view the <a href='".$gotoredcap."'>Hub Uploads</a> page.</div>";
    }else{
        $recordpdf = $module->framework->addAutoNumberedRecord(IEDEA_DATAUPLOAD);

        $link = APP_PATH_WEBROOT_ALL . "DataEntry/record_home.php?pid=" . IEDEA_DATAUPLOAD . "&arm=1&id=" . $recordpdf;
        $message = "<div>Dear administrator,</div><br/>" .
            "<div>A pending upload dataset has been found on the Harmonist Data Toolkit server: </div><br/>".
            "<div><strong>\"" . $sop['sop_name'] . "\"</strong> uploaded by <em>" . $uploader_name . "</em> from ".$region_codeUp." on " . $uploadData[0]['responsecomplete_ts'] . " Eastern US Time (ET).</div><br/>".
            "<div>A <strong>partial, matching upload record</strong> was found in the Hub REDCap project under <a href='".$link."'>Record ID ".$recordpdf."</a>. This record has now been updated with additional information.</div>".
            "<div>All associated notification emails have been activated.</div><br/>".
            "<div>Click here to view the <a href='".$gotoredcap."'>Hub Uploads</a> page.</div>";
    }

    $Proj = new \Project(IEDEA_DATAUPLOAD);
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
    $results = \Records::saveData(IEDEA_DATAUPLOAD, 'array', $recordUp,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
    \Records::addRecordToRecordListCache(IEDEA_DATAUPLOAD, $recordpdf, 1);

    if(($record != "" && $upload['data_upload_pdf'] == "") || $record == "") {
        //SAVE PDF ON DB
        $reportHash = "Report".str_replace("_details","",$file_name);
        $storedName = md5($reportHash);
        $filePath = EDOC_PATH.$storedName;
        $s3->registerStreamWrapper();
        $output = file_get_contents('s3://'.$bucket.'/pending/'.$reportHash.".pdf");
        $filesize = file_put_contents(EDOC_PATH.$storedName, $output);

        //Save document on DB
        $q = $module->query("INSERT INTO redcap_edocs_metadata (stored_name,doc_name,doc_size,file_extension,mime_type,gzipped,project_id,stored_date) VALUES (?,?,?,?,?,?,?,?)",[$storedName,'application/octet-stream',$reportHash.".pdf",$filesize,'.pdf','0',IEDEA_DATAUPLOAD,date('Y-m-d h:i:s')]);
        $docId = db_insert_id();

        //Add document DB ID to project
        $jsonConcepts = json_encode(array(array('record_id' => $recordpdf, 'data_upload_pdf' => $docId)));
        $results = \Records::saveData(IEDEA_DATAUPLOAD, 'json', $jsonConcepts,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);

        \Records::addRecordToRecordListCache(IEDEA_DATAUPLOAD, $record,1);

    }

    #EMAIL NOTIFICATION
    $subject = "Pending dataset upload found for ".$settings['hub_name']." ".$concept_id;

    if($settings['hub_email_pending_uploads'] != "") {
        $emails = explode(';', $settings['hub_email_pending_uploads']);
        foreach ($emails as $email) {
            sendEmail($email, $settings['accesslink_sender_email'], $settings['accesslink_sender_name'], $subject, $message, $recordpdf);
        }
    }
}

?>