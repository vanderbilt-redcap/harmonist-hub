<script>
    $(document).ready(function() {
        $('[name=submit-btn-savereturnlater]').hide();
        $('#return_code_completed_survey_div').hide();
        $('.bubbleInfo').hide();
        $('#two_factor_verification_code_btn span').show();
        $( "#pagecontent span:contains('Close survey')" ).text('Close window');
        $('[name=submit-btn-saverecord]').text('Submit');
        $('body').css('background-color','#fff');
    });
</script>