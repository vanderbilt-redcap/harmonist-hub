<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once(dirname(__FILE__)."/classes/AllCrons.php");
include_once(__DIR__ ."/../projects.php");

$RecordSetDU = \REDCap::getData(IEDEA_DATAUPLOAD, 'array', null);
$request_DU = getProjectInfoArray($RecordSetDU);
$RecordSetSettings = \REDCap::getData(IEDEA_SETTINGS, 'array', null);
$settings = getProjectInfoArray($RecordSetSettings)[0];

$days_expiration = intval($settings['uploadnotification_dur']);
$extra_days = ' + ' . $days_expiration. " days";

foreach ($request_DU as $upload) {
    $RecordSetSOP = \REDCap::getData(IEDEA_SOP, 'array', array('record_id' => $upload['data_assoc_request']));
    $sop = getProjectInfoArrayRepeatingInstruments($RecordSetSOP)[0];

    $message = AllCrons::runCronDataUploadNotification(
        $module,
        $upload,
        $sop,
        null,
        $extra_days,
        $settings,
        true
    );
}
?>