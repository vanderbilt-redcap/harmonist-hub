<?php
use Vanderbilt\HarmonistHubExternalModule\ProjectData;

$aboutData = \REDCap::getData($pidsArray['ABOUT'], 'json-array', null);
$aboutTitle = "";
if(array_key_exists(0, $aboutData) && is_array($aboutData[0]) && array_key_exists('about_title', $aboutData[0])) {
    $aboutTitle = $aboutData[0]['about_title'];
}
$versionsByPrefix = $module->getEnabledModules($_GET['pid']);

?>

<div class="footer">
    <div>Powered by <a href="<?=$indexUrl.'&NOAUTH&option=log'?>">Harmonist Hub <?=$module->escape($versionsByPrefix['harmonist-hub'])?></a></div>
    <div><a href="<?=$indexUrl.'&NOAUTH&option=abt'?>">About <?=$module->escape($aboutTitle)?></a> | <a href="mailto:<?=$settings['hub_contact_email']?>">Contact us</a> | <a href="<?=$indexUrl.'&NOAUTH&option=bug'?>">Report a bug</a></div>
</div>