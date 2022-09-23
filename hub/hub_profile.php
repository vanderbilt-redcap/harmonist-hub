<div class="container">
    <h3>Profile</h3>
    <p class="hub-title">View and edit your profile information.</p>
</div>
<?php
if(array_key_exists('message', $_REQUEST) && ($_REQUEST['message'] == 'U')){
    ?>
<div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;" id="succMsgContainer">Your profile information has been successfully updated.</div><?php
}
?>
<div class="container">
    <div class="panel-body">
        <?php
        $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['PEOPLE'], $current_user['record_id'], "user_profile", "");
        $survey_link = APP_PATH_WEBROOT_FULL . "/surveys/?s=".$passthru_link['hash'];
        ?>
        <iframe class="commentsform" id="redcap-frame" name="redcap-frame" message="U" src="<?=$survey_link?>" style="border: none;height: 980px;width: 100%;"></iframe>
    </div>
</div>

