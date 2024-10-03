<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once(dirname(dirname(__FILE__))."/classes/AllCrons.php");

if(is_numeric($pidsArray['RMANAGER']) && is_numeric($pidsArray['SETTINGS'])) {
    $settings = \REDCap::getData($pidsArray['SETTINGS'], 'json-array', null)[0];
    $requests = \REDCap::getData($pidsArray['RMANAGER'], 'json-array', null, null, null, null, false, false, false, "[finalize_y] = 1 AND [finalize_noemail] != 1 AND [request_summary_sent_y(1)] != 1");
    foreach ($requests as $request) {
        $message = AllCrons::runCronReqFinalizedNotification(
            $this,
            $pidsArray,
            $request,
            $settings,
            true
        );
    }
}
?>