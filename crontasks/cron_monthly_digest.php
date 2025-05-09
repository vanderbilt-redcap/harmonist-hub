<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once(dirname(dirname(__FILE__))."/classes/AllCrons.php");
include_once(__DIR__ ."/../projects.php");

$params = [
    'project_id' => $pidsArray['RMANAGER'],
    'return_format' => 'array',
    'filterLogic' => "[approval_y] = '1'",
    'filterType' => "RECORD"
];
$RecordSetRM = \REDCap::getData($params);
$requests = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRM, $pidsArray['SOP']);
ArrayFunctions::array_sort_by_column($requests, 'due_d', SORT_ASC);

$numberDaysInCurrentMonth = cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'));
$expire_date = date('Y-m-d', strtotime(date('Y-m-d') . "-" . $numberDaysInCurrentMonth . " days"));
$params = [
    'project_id' => $pidsArray['RMANAGER'],
    'return_format' => 'array',
    'filterLogic' => "[finalize_y] <> '' and [final_d] <>'' and datediff ([final_d], '" . $expire_date . "', \"d\", true) <= 0",
    'filterType' => "RECORD"
];
$RecordSetReq = \REDCap::getData($params);
$requests_hub = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetReq, $pidsArray['SOP']);
ArrayFunctions::array_sort_by_column($requests_hub, 'final_d', SORT_ASC);

$params = [
    'project_id' => $pidsArray['SOP'],
    'return_format' => 'array',
    'filterLogic' => "[sop_active] = '1' and [sop_finalize_y(1)] = '1'",
    'filterType' => "RECORD"
];
$RecordSetSOP = \REDCap::getData($params);
$sops = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP, $pidsArray['SOP']);
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