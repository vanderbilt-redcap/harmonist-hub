<?php
define('NOAUTH',true);

if(!empty($_REQUEST['surveyLink'])){?>
    <html>
    <body>
    <form id='passthruform' name='passthruform' action='<?=$_REQUEST['surveyLink']?>' method='post' enctype='multipart/form-data'>
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