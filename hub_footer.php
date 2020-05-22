<?php
$RecordSetAbout = \REDCap::getData(IEDEA_ABOUT, 'array', null);
$about = getProjectInfoArray($RecordSetAbout)[0];

$sql = "SELECT MAX(CAST(record AS Int)) as record FROM redcap_data WHERE project_id='".db_escape(IEDEA_CHANGELOG)."'";
$q = db_query($sql);
$row = db_fetch_assoc($q);
$sql = "SELECT value as version_num FROM redcap_data WHERE project_id='".db_escape(IEDEA_CHANGELOG)."' AND record = '".db_escape($row['record'])."' AND field_name = '".db_escape('version_num')."'";
$q = db_query($sql);

$row = db_fetch_assoc($q);
?>

<div class="footer">
    <div>Powered by <a href="index.php?pid=<?=IEDEA_HARMONIST?>&option=log">Harmonist Hub v<?=$row['version_num']?></a></div>
    <div><a href="index.php?pid=<?=IEDEA_HARMONIST?>&option=abt">About <?=$about['about_title']?></a> | <a href="mailto:<?=$settings['hub_contact_email']?>">Contact us</a> | <a href="index.php?pid=<?=IEDEA_HARMONIST?>&option=bug">Report a bug</a></div>
</div>