<?php
include_once(__DIR__ ."/../projects.php");
require_once(dirname(dirname(__FILE__))."/classes/AllCrons.php");
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Vanderbilt\HarmonistHubExternalModule\AllCrons;

require_once $module->getSafePath("/app001/credentials/".$pidsArray['PROJECTS']."_hubsettings.php","/app001/credentials/");

if(ENVIRONMENT == 'TEST'){
    #Don't do pending
}else if(ENVIRONMENT == 'PROD') {
    $credentials = new Aws\Credentials\Credentials($aws_key, $aws_secret);
    $s3 = new S3Client([
        'version' => 'latest',
        'region' => 'us-east-2',
        'credentials' => $credentials
    ]);

    foreach (self::getRequestDU() as $upload) {
        $message = AllCrons::runCronUploadPendingDataSetData(
            $this,
            $pidsArray,
            $s3,
            $bucket,
            $settings,
            false
        );
    }
}
?>