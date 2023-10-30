<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once(dirname(dirname(__FILE__))."/classes/AllCrons.php");
include_once(__DIR__ ."/../projects.php");

$RecordSetSettings = \REDCap::getData($pidsArray['SETTINGS'], 'array', null);
$settings = ProjectData::getProjectInfoArray($RecordSetSettings)[0];

$RecordSetRM = \REDCap::getData($pidsArray['RMANAGER'], 'array',null, null, null, null, false, false, false,
    "[finalize_y] = 1 AND [finalize_noemail] != 1 AND [request_summary_sent_y(1)] != 1");
$requests = ProjectData::getProjectInfoArray($RecordSetRM);
foreach ($requests as $request) {
    $message = AllCrons::runCronReqFinalizedNotification(
        $this,
        $pidsArray,
        $request,
        $settings,
        true
    );
}

?>