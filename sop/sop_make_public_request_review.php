<?php
$record = htmlentities($_REQUEST['record'],ENT_QUOTES);
?>
<div class="optionSelect">
    <div style="margin-bottom:5px">
        <a href="<?=$module->getUrl('index.php').'&NOAUTH&option=ss5&record='.$record?>">&lt; Back to Steps Complete</a>
    </div>
    <h3>Share Data Request for Review</h3>
    <p class="hub-title"><?=filter_tags($settings['hub_datareq_for_review']);?></p>
</div>
<div class="container">
    <div class="panel-body">
        <?php
        $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['SOP'], $record, "dhwg_review_request", "");
        $survey_link =  $module->escape(APP_PATH_WEBROOT_FULL . "/surveys/?s=".$passthru_link['hash']);
        ?>
        <input type="hidden" value="0" id="comment_loaded">
        <iframe class="commentsform" id="redcap-frame" message="" src="<?=$survey_link?>" approot="<?=APP_PATH_PLUGIN?>" record="<?=$record?>" style="border: none;height: 980px;width: 100%;"></iframe>
    </div>
</div>