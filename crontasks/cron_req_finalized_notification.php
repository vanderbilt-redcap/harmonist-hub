<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once(dirname(dirname(__FILE__))."/classes/AllCrons.php");
include_once(__DIR__ ."/../projects.php");

$RecordSetSettings = \REDCap::getData($pidsArray['SETTINGS'], 'array', null);
$settings = ProjectData::getProjectInfoArray($RecordSetSettings)[0];

$RecordSetRM = \REDCap::getData($pidsArray['RMANAGER'], 'array');
$requests = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRM,array('finalize_y' => '1'));
foreach ($requests as $request) {
    if($request['finalize_noemail'] != "1") {
        if (!array_key_exists('request_summary_sent_y', $request) || $request['request_summary_sent_y'][1] == '0') {
            error_log($request['request_id']);
            $message = AllCrons::runCronReqFinalizedNotification(
                $this,
                $pidsArray,
                $request,
                $settings,
                true
            );
        }
    }
}

?>