<?php
use Vanderbilt\HarmonistHubExternalModule\ProjectData;

$about = \REDCap::getData($pidsArray['ABOUT'], 'json-array', null)[0];
$versionsByPrefix = $module->getEnabledModules($_GET['pid']);

?>

<div class="footer">
    <div>Powered by <a href="<?=$module->getUrl('index.php').'&NOAUTH&option=log'?>">Harmonist Hub <?=$module->escape($versionsByPrefix['harmonist-hub'])?></a></div>
    <div><a href="<?=$module->getUrl('index.php').'&NOAUTH&option=abt'?>">About <?=$module->escape($about['about_title'])?></a> | <a href="mailto:<?=$settings['hub_contact_email']?>">Contact us</a> | <a href="<?=$module->getUrl('index.php?NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=bug')?>">Report a bug</a></div>
</div>