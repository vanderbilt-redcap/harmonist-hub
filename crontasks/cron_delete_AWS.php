<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once(dirname(dirname(__FILE__))."/classes/AllCrons.php");
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;


//$url = "/app001/credentials/Harmonist-Hub/".$pidsArray['PROJECTS']."_aws_s3.php";
$headers = @get_headers($url);

// If the URL exists, then we have the credentials in the server and can continue
if($headers && strpos( $headers[0], '200')) {
//    require_once $this->getSafePath($url, "/app001/credentials/Harmonist-Hub");

    $credentials = new Aws\Credentials\Credentials($aws_key, $aws_secret);
    $s3 = new S3Client([
        'version' => 'latest',
        'region' => 'us-east-2',
        'credentials' => $credentials
    ]);

    $settings = \REDCap::getData($pidsArray['SETTINGS'], 'json-array', null)[0];
    $request_DU = \REDCap::getData($pidsArray['DATAUPLOAD'], 'json-array', null);

    $today = date('Y-m-d');
    $days_expiration = intval($settings['retrievedata_expiration']);
    $extra_days = ' + ' . $days_expiration . " days";

    foreach ($request_DU as $upload) {
        $expired_date = date('Y-m-d', strtotime($upload['responsecomplete_ts'] . $extra_days));
        $message = AllCrons::runCronDeleteAws(
            $this,
            $pidsArray,
            null,
            $upload,
            array('sop_downloaders' => '1,2'),
            $expired_date,
            $settings,
            true
        );
        array_push($messageArray, $message);
    }

    #Delete tokens expired on H18 Data Toolkit
    $securityTokens = \REDCap::getData($pidsArray['DATATOOLUPLOADSECURITY'], 'json-array', null, array('tokenexpiration_ts', 'record_id'));
    $today = strtotime(date("Y-m-d"));
    foreach ($securityTokens as $token) {
        if (strtotime($token['tokenexpiration_ts']) <= $today) {
            $this->query("DELETE FROM " . \Vanderbilt\HarmonistHubExternalModule\getDataTable($pidsArray['DATATOOLUPLOADSECURITY']) . " WHERE project_id = ? AND field_name=? AND value = ?", [$pidsArray['DATATOOLUPLOADSECURITY'], "record_id", $token["record_id"]]);
            \Records::deleteRecordFromRecordListCache($pidsArray['DATATOOLUPLOADSECURITY'], $token["record_id"], 1);

            #Logs
            \REDCap::logEvent("Record deleted automatically", "Record: " . $token['record_id'], null, null, null, $pidsArray['DATATOOLUPLOADSECURITY']);
        }
    }
}
?>