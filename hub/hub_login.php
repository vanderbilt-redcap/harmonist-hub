<?php
namespace Vanderbilt\HarmonistHubExternalModule;

$option = htmlentities($_GET['option'],ENT_QUOTES);
$record = htmlentities($_GET['record'],ENT_QUOTES);
?>

<script>
    $(document).ready(function() {
        $('#form_login').submit(function () {
            var url_getLink = <?=json_encode($module->getUrl('hub/hub_getLink.php?NOAUTH'))?>;
            var errMsg = [];
            $('#hub_error_message').hide();
            if(!validateEmail($('#hub_email').val())){
                errMsg.push('<strong>Email address not valid. </strong> <br />Please enter a valid email address.');
            }

            if (errMsg.length > 0) {
                $('#hub_error_message').show();
                $('#hub_error_message').empty();
                $.each(errMsg, function (i, e) {
                    $('#hub_error_message').append('<div>' + e + '</div>');
                });
                $('#hub_error_message').show();
                $('html,body').scrollTop(0);
            }
            else {
                var option = <?=json_encode($option);?>;
                var record = <?=json_encode($record);?>;
                loadAjax('email='+$('#hub_email').val()+'&option='+option+'&record='+record,url_getLink,'hub_error_message');
                $('#hub_email').val('');
            }
            $('#blue-alert').show();
            return false;
        });
    } );
</script>

<div>
    <div style="padding-left: 15px;font-size: 20px;color: #404040;"><p>Access <?=$hub_projectname?> Hub</p></div>
    <div style="padding-left: 15px;color: #404040;"><?=$settings['hub_login_text']?></div>

    <div class="col-md-10">
        <div class="alert alert-info" id="blue-alert" style="display:none">
            <?=$settings['hub_login_blue_text']?>
        </div>
        <div class="alert alert-danger" id="hub_error_message" style="display: none;"></div>
        <p></p>
        <div class="panel panel-default">
            <div class="panel-heading"><h3 class="panel-title"><strong>Request Access Link</strong></h3></div>
            <div class="panel-body">
                <form role="form" id="form_login">
                    <div class="form-group">
                        <label for="exampleInputEmail1">Email Address</label>
                        <input type="email" class="form-control" id="hub_email" name="hub_email" placeholder="Type your email address.">
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary" style="font-weight: bold">Get Link</button>

                </form>
            </div>
        </div>
    </div>
</div>