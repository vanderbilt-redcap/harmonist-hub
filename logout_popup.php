<?php
namespace Vanderbilt\HarmonistHubExternalModule;

$timer = 1200;
if($settings['session_timeout_timer'] != ""){
    $timer = $settings['session_timeout_timer'];
}

$countdown = 60;
if($settings['session_timeout_countdown'] != ""){
    $countdown = $settings['session_timeout_countdown'];
}
?>
<!-- MODAL LOGOUT-->
<div class="modal fade" id="modal-log-out" tabindex="-1" role="dialog" aria-labelledby="Codes">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Session Timeout</h4>
            </div>
            <div class="modal-body">
                <span>You are being timed out due to inactivity.</span>
                <br
                <span style="color:red;">Please choose to stay signed in otherwise you will be logged out automatically.</span>
            </div>
            <div class="modal-footer">
                <?php $url_logout = $module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&sout';?>
                <a href="#" onclick="destroy_session(<?="'".$url_logout."'"?>)" class="btn btn-default btn-cancel" data-dismiss="modal">Log Out</a>
                <a type="submit" onclick="" class="btn btn-default btn-success" id='btnStayLoggedIn'>Stay Signed In (<span id="countdownLogOut">60</span>)</a>
            </div>
        </div>
    </div>
</div>

<script>
    var timeleft = <?=json_encode($countdown)?>;
    var timeleftcounter = timeleft;
    var showPopup = <?=json_encode($timer)?>;
    var urlLogOut = <?=json_encode($module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&sout')?>;
    var idleTimer_k = 0;
    var remTime = 0;
    var dateNowTime = new Date().getTime();
    var lastActiveTime = new Date();

    $(document).ready(function() {
        lastActiveTime = new Date();
        window.addEventListener("click", () => {
            lastActiveTime = new Date();
        });
        window.addEventListener("mousemove", () => {
            lastActiveTime = new Date();
        });
        window.addEventListener("keypress", () => {
            lastActiveTime = new Date();
        });
        window.addEventListener("scroll", () => {
            lastActiveTime = new Date();
        });
        idleTimer_k = window.setInterval(CheckIdleTime, 10000);
    });

    function CheckIdleTime() {
        //If user refreshes page but does not move mouse
        if(lastActiveTime == undefined){
            lastActiveTime = new Date();
        }
        //returns idle time every 10 seconds
        dateNowTime = new Date().getTime();
        lastActiveTime = new Date(lastActiveTime).getTime();
        remTime = Math.floor((dateNowTime-lastActiveTime)/ 1000);

        // converting from milliseconds to seconds
        if(remTime >= showPopup && !$('#modal-log-out').hasClass('in')){
            $('#modal-log-out').modal('show');
            var downloadTimer = setInterval(function(){
                $( "#btnStayLoggedIn" ).click(function() {
                    updateTimeStay()
                    clearInterval(downloadTimer);
                });
                $('#modal-log-out').on('hidden.bs.modal', function () {
                    updateTimeStay()
                    clearInterval(downloadTimer);
                })
                if(timeleftcounter <= 0 && $('#modal-log-out').hasClass('in')){
                    clearInterval(downloadTimer);
                    $('#modal-log-out').modal('hide');
                    timeleftcounter = timeleft;
                    destroy_session(urlLogOut);
                } else {
                    $('#countdownLogOut').html(timeleftcounter);
                }
                timeleftcounter -= 1;
            }, 1000);
        }
    }

    function updateTimeStay(){
        $('#modal-log-out').modal('hide');
        $('#modal-log-out').removeClass('in');
        lastActiveTime = new Date();
        lastActiveTime = new Date(this.lastActiveTime).getTime();
        dateNowTime = new Date().getTime();
        remTime = Math.floor((dateNowTime-lastActiveTime)/ 1000);
        timeleftcounter = timeleft;
    }
</script>