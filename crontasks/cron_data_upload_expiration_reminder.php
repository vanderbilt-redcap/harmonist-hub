<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once(dirname(__FILE__)."/classes/AllCrons.php");
include_once(__DIR__ ."/../projects.php");

$RecordSetDU = \REDCap::getData(IEDEA_DATAUPLOAD, 'array', null);
$request_DU = getProjectInfoArray($RecordSetDU);

$RecordSetSettings = \REDCap::getData(IEDEA_SETTINGS, 'array', null);
$settings = getProjectInfoArray($RecordSetSettings)[0];

$days_expiration = intval($settings['downloadreminder_dur']);
$expire_number = $settings['retrievedata_expiration'] - $days_expiration;
$extra_days = ' + ' . $expire_number . " days";
$days_expiration2 = intval($settings['downloadreminder2_dur']);
$expire_number2 = $settings['retrievedata_expiration'] - $days_expiration2;
$extra_days2 = ' + ' . $expire_number2 . " days";

$days_expiration_delete = intval($settings['retrievedata_expiration']);
$extra_days_delete = ' + ' . $days_expiration_delete . " days";

foreach ($request_DU as $upload) {
    $RecordSetSOP = \REDCap::getData(IEDEA_SOP, 'array', array('record_id' => $upload['data_assoc_request']));
    $sop = getProjectInfoArrayRepeatingInstruments($RecordSetSOP)[0];

    $message = AllCrons::runCronDataUploadExpirationReminder(
        $module,
        $upload,
        $sop,
        null,
        $extra_days_delete,
        $extra_days,
        $extra_days2,
        $settings,
        true
    );
}


?>