<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once dirname(__FILE__) . "/classes/HubData.php";

$module->getSecurityHandler()->getAwsCredentialsServerVars();
$module->getSecurityHandler()->getEncryptionCredentialsServerVars();

$deleteCode = $_REQUEST['del'];
$file_name = $_REQUEST['file_name'];
$current_user = $_REQUEST['current_user'];
$deleteAwsUrl = preg_replace('/pid=(\d+)/', "pid=".$pidsArray['DATADOWNLOADUSERS'],$module->getUrl('hub/aws/AWS_deleteFile.php'))."&code=".$deleteCode;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?=$settings['hub_name_title']?></title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <meta http-equiv="Cache-control" content="public">
    <meta name="theme-color" content="#fff">
    <link rel="icon" href="<?=getFile($module, $pidsArray['PROJECTS'], $settings['hub_logo_favicon'],'favicon')?>">

    <?php include_once("head_scripts.php");?>

    <script type='text/javascript'>
        $(document).ready(function() {
            $('#deleteAwsData').submit(function () {
                if($('#deletion_rs').val() == ""){
                    $('#dataUploadError').text('Please provide a reason to continue.');
                    return false;
                }
            });
        } );
    </script>

    <style>
        table thead .glyphicon {
            color: blue;
        }
    </style>
</head>
<style>
    .dtr-control{
        width: 130px;
    }
</style>
<body>
<?php include('hub_header.php');?>
<div class="container" style="margin: 0 auto;float:none;min-height: 900px;">
    <form class="form-horizontal" action="<?=$deleteAwsUrl?>" method="post" id='deleteAwsData'>
        <div>
            <h4 class="modal-title">Delete Data Upload</h4>
        </div>
        <div>
            <br>
            <span>Provide a reason for deleting <strong><?=$file_name?></strong> data upload:</span>
            <br>
            <br>
            <textarea name="deletion_rs" id="deletion_rs" style="width: 100%;"></textarea>
            <div id="dataUploadError" class="text-error" style="padding-bottom: 10px;"></div>
            <button type="submit" form="deleteAwsData" class="btn btn-default btn-danger" id='btnModalRescheduleForm'>Delete</button>
        </div>
    </form>
    <div style="padding-top: 500px;"></div>
    <?php include('hub_footer.php'); ?>
    <br/>
</body>
</html>
