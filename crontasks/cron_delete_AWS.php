<?php
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Vanderbilt\HarmonistHubExternalModule\AllCrons;

require_once "/app001/credentials/".$pidsArray['PROJECTS']."_hubsettings.php";

$credentials = new Aws\Credentials\Credentials($aws_key, $aws_secret);
$s3 = new S3Client([
    'version' => 'latest',
    'region' => 'us-east-2',
    'credentials' => $credentials
]);

$RecordSetDU = \REDCap::getData($pidsArray['DATAUPLOAD'], 'array', null);
$request_DU = getProjectInfoArray($RecordSetDU);

$today = date('Y-m-d');
$days_expiration = intval($settings['retrievedata_expiration']);
$extra_days = ' + ' . $days_expiration. " days";

foreach (self::getRequestDU() as $upload) {
    $expired_date = date('Y-m-d', strtotime($upload['responsecomplete_ts'] . $extra_days));
    $message = AllCrons::runCronDeleteAws(
        $module,
        $pidsArray,
        null,
        $upload,
        array('sop_downloaders' => '1,2'),
        self::getPeopleDown(),
        $expired_date,
        $settings,
        true
    );
    array_push($messageArray,$message);
}

#Delete tokens expired on H18 Data Toolkit
$RecordSetSecurity = \REDCap::getData($pidsArray['DATATOOLUPLOADSECURITY'], 'array', null);
$securityTokens = getProjectInfoArray($RecordSetSecurity);
$today = strtotime(date("Y-m-d"));
foreach ($securityTokens as $token){
    if(strtotime($token['tokenexpiration_ts']) <= $today){
        $module->query("DELETE FROM redcap_data WHERE project_id = ? AND field_name=? AND value = ?",[$pidsArray['DATATOOLUPLOADSECURITY'],"record_id",$token["record_id"]]);
        \Records::deleteRecordFromRecordListCache($pidsArray['DATATOOLUPLOADSECURITY'], $token["record_id"], 1);

        #Logs
        \REDCap::logEvent("Record deleted automatically","Record: ".$token['record_id'],null,null,null,$pidsArray['DATATOOLUPLOADSECURITY']);
    }
}

?>