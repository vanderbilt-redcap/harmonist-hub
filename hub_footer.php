<?php
use Vanderbilt\HarmonistHubExternalModule\ProjectData;

$RecordSetAbout = \REDCap::getData(IEDEA_ABOUT, 'array', null);
$about = ProjectData::getProjectInfoArray($RecordSetAbout)[0];

$sql = "SELECT MAX(CAST(record AS Int)) as record FROM redcap_data WHERE project_id='".db_escape(IEDEA_CHANGELOG)."'";
$q = db_query($sql);
$row = db_fetch_assoc($q);
$sql = "SELECT value as version_num FROM redcap_data WHERE project_id='".db_escape(IEDEA_CHANGELOG)."' AND record = '".db_escape($row['record'])."' AND field_name = '".db_escape('version_num')."'";
$q = db_query($sql);

$row = db_fetch_assoc($q);
?>

<div class="footer">
    <div>Powered by <a href="<?=$module->getUrl('index.php?pid='.IEDEA_PROJECTS.'&option=log')?>">Harmonist Hub v<?=$row['version_num']?></a></div>
    <div><a href="<?=$module->getUrl('index.php?pid='.IEDEA_PROJECTS.'&option=abt')?>">About <?=$about['about_title']?></a> | <a href="mailto:<?=$settings['hub_contact_email']?>">Contact us</a> | <a href="<?=$module->getUrl('index.php?pid='.IEDEA_PROJECTS.'&option=bug')?>">Report a bug</a></div>
</div>