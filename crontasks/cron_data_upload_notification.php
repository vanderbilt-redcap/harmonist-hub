<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once(dirname(dirname(__FILE__))."/classes/AllCrons.php");
include_once(__DIR__ ."/../projects.php");

$request_DU = \REDCap::getData($pidsArray['DATAUPLOAD'], 'json-array', null);
$settings = \REDCap::getData($pidsArray['SETTINGS'], 'json-array', null)[0];

$days_expiration = intval($settings['uploadnotification_dur']);
$extra_days = ' + ' . $days_expiration. " days";
foreach ($request_DU as $upload) {
    $RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', array('record_id' => $upload['data_assoc_request']));
    $sop = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP)[0];

    $message = AllCrons::runCronDataUploadNotification(
        $this,
        $pidsArray,
        $upload,
        $sop,
        null,
        $extra_days,
        $settings,
        true
    );
}
?>