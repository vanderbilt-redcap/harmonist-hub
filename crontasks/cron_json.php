<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once(dirname(dirname(__FILE__))."/classes/AllCrons.php");
include_once(__DIR__ ."/../projects.php");

$settings = \REDCap::getData($pidsArray['SETTINGS'], 'json-array', null)[0];

$message = AllCrons::runCronJson(
    $this,
    $pidsArray,
    $settings,
    true
);