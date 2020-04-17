<?php
include_once __DIR__ ."/../functions.php";

#Add to all projects needed
if($project_id == IEDEA_HARMONIST){
    if($_REQUEST['s'] != "" || $_REQUEST['sq'] != ""){?>
        <script>
            $(document).ready(function() {
                $('[name=submit-btn-savereturnlater]').hide();
                $('#return_code_completed_survey_div').hide();
                $('#surveytitlelogo').hide();
                $('.bubbleInfo').hide();
                $('#two_factor_verification_code_btn span').show();
                $('body').css('background-color','#fff');
                $('[name=submit-btn-saverecord]').text('Submit');
                $('.questionnum ').hide();

                //For Queue Surveys
                $('table#table-survey_queue .hidden').removeClass('hidden').hide().show('fade');
                $('.wrap a').parent().parent().parent().parent().hide();
                $( "span:contains('Close survey queue')" ).parent().parent().hide();
                $( "span:contains('Close survey')" ).parent().parent().hide();
            });
        </script>`

    <?php }
}else if($project_id == IEDEA_SOPCOMMENTS || $project_id == IEDEA_HOME || $project_id == IEDEA_PEOPLE || $project_id == IEDEA_SOP || $project_id == IEDEA_RMANAGER || $project_id == IEDEA_SOPCOMMENTS){
    if($project_id == IEDEA_SOPCOMMENTS || $project_id == IEDEA_HOME || $project_id == IEDEA_COMMENTSVOTES || $project_id == IEDEA_RMANAGER || ($project_id == IEDEA_PEOPLE && $_REQUEST['s'] != IEDEA_SURVEYPERSONINFO) || $project_id == IEDEA_SOP && $_REQUEST['s'] != IEDEA_DATARELEASEREQUEST){?>
        <script>
            $(document).ready(function() {
                $('[name=submit-btn-savereturnlater]').hide();
                $('#return_code_completed_survey_div').hide();
                $('#surveytitlelogo').hide();
                $('.bubbleInfo').hide();
                $('#pagecontent span.ui-button-text').hide();
                $('#two_factor_verification_code_btn span').show();
                $('body').css('background-color','#fff');
                $('[name=submit-btn-saverecord]').text('Submit');
            });
        </script>
    <?php } ?>
    <script>
        $(document).ready(function() {
            $('.questionnum ').hide();
        });
    </script>
<?php }
?>