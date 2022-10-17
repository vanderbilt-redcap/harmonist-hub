<?php
use Vanderbilt\HarmonistHubExternalModule\ProjectData;

$RecordSetAbout = \REDCap::getData($pidsArray['ABOUT'], 'array', null);
$about = ProjectData::getProjectInfoArray($RecordSetAbout)[0];

$versionsByPrefix = $module->getEnabledModules($_GET['pid']);

?>

<div class="footer">
    <div>Powered by <a href="<?=$module->getUrl('index.php?NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=log')?>">Harmonist Hub <?=$versionsByPrefix['harmonist-hub']?></a></div>
    <div><a href="<?=$module->getUrl('index.php?NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=abt')?>">About <?=$about['about_title']?></a> | <a href="mailto:<?=$settings['hub_contact_email']?>">Contact us</a> | <a href="<?=$module->getUrl('index.php?NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=bug')?>">Report a bug</a></div>
</div>