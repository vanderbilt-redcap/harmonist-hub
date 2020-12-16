<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once(dirname(__FILE__)."/classes/AllCrons.php");
include_once(__DIR__ ."/../projects.php");

$RecordSetDU = \REDCap::getData($pidsArray['DATAUPLOAD'], 'array', null);
$request_DU = ProjectData::getProjectInfoArray($RecordSetDU);
$RecordSetSettings = \REDCap::getData($pidsArray['SETTINGS'], 'array', null);
$settings = ProjectData::getProjectInfoArray($RecordSetSettings)[0];

$days_expiration = intval($settings['uploadnotification_dur']);
$extra_days = ' + ' . $days_expiration. " days";

foreach ($request_DU as $upload) {
    $RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', array('record_id' => $upload['data_assoc_request']));
    $sop = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP)[0];

    $message = AllCrons::runCronDataUploadNotification(
        $module,
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