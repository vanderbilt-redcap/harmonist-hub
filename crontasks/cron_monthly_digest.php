<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once(dirname(dirname(__FILE__))."/classes/AllCrons.php");
include_once(__DIR__ ."/../projects.php");

$RecordSetReq = \REDCap::getData($pidsArray['RMANAGER'], 'array', null, null, null, null, false, false, false, "[approval_y] = 1");
$requests = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetReq, $pidsArray['RMANAGER']);
ArrayFunctions::array_sort_by_column($requests, 'due_d', SORT_ASC);

$numberDaysInCurrentMonth = cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'));
$expire_date = date('Y-m-d', strtotime(date('Y-m-d') . "-" . $numberDaysInCurrentMonth . " days"));
$RecordSetReq = \REDCap::getData($pidsArray['RMANAGER'], 'array', null, null, null, null, false, false, false, "[finalize_y] <> '' and [final_d] <>'' and datediff ([final_d], '" . $expire_date . "', \"d\", true) <= 0");
$requests_hub = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetReq, $pidsArray['RMANAGER']);
ArrayFunctions::array_sort_by_column($requests_hub, 'final_d', SORT_ASC);

$RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', null);
$sops = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP, $pidsArray['SOP'], array('sop_active' => '1', 'sop_finalize_y' => array(1 => '1')));
ArrayFunctions::array_sort_by_column($sops, 'sop_due_d', SORT_ASC);

$message = AllCrons::runCronMonthlyDigest(
    $this,
    $pidsArray,
    $requests,
    $requests_hub,
    $sops,
    $settings,
    true
);
?>