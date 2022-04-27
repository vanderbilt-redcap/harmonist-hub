<?php
define('NOAUTH',true);

if(!empty($_REQUEST['surveyLink'])){?>
    <html>
    <body>
    <form id='passthruform' name='passthruform' action='<?=htmlentities($_REQUEST['surveyLink']."&".$_REQUEST['modal'],ENT_QUOTES)?>' method='post' enctype='multipart/form-data'>
            <input type='hidden' value='1' name='__prefill' />
    </form>
        <script type='text/javascript'>
        window.onload = function(){
            document.passthruform.submit();
        }

    </script>
    </body>
    </html>
<?php } ?>