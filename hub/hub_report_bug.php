<div class="container">
    <h3>Issue Report Survey</h3>
    <p class="hub-title"></p>
</div>
<?php
if(array_key_exists('message', $_REQUEST) && ($_REQUEST['message'] == 'R')){
    ?>
    <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;" id="succMsgContainer">Bug successfully reported.</div><?php
}
?>
<div>
    <div class="panel-collapse collapse in" aria-expanded="true">
        <div class="panel-body">
            <iframe class="commentsform" id="redcap-frame" message="R" src="https://redcap.vumc.org/surveys/?s=3RKCREXK7R" style="border: none;height: 860px;width: 100%;"></iframe>
        </div>
    </div>
</div>