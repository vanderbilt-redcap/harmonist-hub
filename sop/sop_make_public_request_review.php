<div class="optionSelect">
    <div style="margin-bottom:5px">
        <a href="<?=$module->getUrl('index.php?pid='.$pidsArray['PROJECTS'].'&option=ss5&record='.$_REQUEST['record'])?>">&lt; Back to Steps Complete</a>
    </div>
    <h3>Share Data Request for Review</h3>
    <p class="hub-title"><?=$settings['hub_datareq_for_review']?></p>
</div>
<div class="container">
    <div class="panel-body">
        <?php
        $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['SOP'], $_REQUEST['record'], "dhwg_review_request", "");
        $survey_link = $module->getUrl('surveyPassthru.php?&surveyLink='.APP_PATH_SURVEY_FULL . "?s=".$passthru_link['hash']);
        ?>
        <input type="hidden" value="0" id="comment_loaded">
        <iframe class="commentsform" id="redcap-frame" message="" src="<?=$survey_link?>" approot="<?=APP_PATH_PLUGIN?>" record="<?=$_REQUEST['record']?>" style="border: none;height: 980px;width: 100%;"></iframe>
    </div>
</div>