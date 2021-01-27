<?php
namespace Vanderbilt\HarmonistHubExternalModule;

require_once(dirname(dirname(__FILE__))."/classes/UnitTestFunctions.php");


$url = json_encode($module->getUrl("hub/hub_unit_test_AJAX.php?pid=".IEDEA_PROJECTS));

?>
<h3>Unit Testing</h3>
<p class="hub-title"><?=$settings['hub_unit_test_text']?></p>
<div class="optionSelect">
    <div style="margin: 0 auto 15px auto;width: 200px;">
        <div style="display: inline-block">
            <button onclick='startUnitTest(<?=$url?>)' class="btn btn-success btn-md" id="unitTestbtn">Start Unit Test</button>
        </div>
    </div>
</div>
<div>
    <div class="alert fade in col-md-12 alert-info" style="display:none" id="unitTestMsgContainer"><i class="fa fa-spinner fa-spin"></i> Test in progress...</div>
</div>
<?php
if(!empty($_GET['test']) && \Vanderbilt\HarmonistHubExternalModule\startTest($_GET['test'], $secret_key, $secret_iv, $_SESSION[$settings['hub_name'].constant(ENVIRONMENT.'_IEDEA_PROJECTS')."_unit_test_timestamp"])) {
    #Get Projects ID's
    $pidsArray = array();
    $pidsArray['HARMONIST'] = IEDEA_HARMONIST;
    $pidsArray['RMANAGER'] = IEDEA_RMANAGER;
    $pidsArray['REGIONS'] = IEDEA_REGIONS;
    $pidsArray['COMMENTSVOTES'] = IEDEA_COMMENTSVOTES;
    $pidsArray['PEOPLE'] = IEDEA_PEOPLE;
    $pidsArray['DATAUPLOAD'] = IEDEA_DATAUPLOAD;
    $pidsArray['DATADOWNLOAD'] = IEDEA_DATADOWNLOAD;
    $pidsArray['EXTRAOUTPUTS'] = IEDEA_EXTRAOUTPUTS;
    $pidsArray['DATATOOLUPLOADSECURITY'] = IEDEA_DATATOOLUPLOADSECURITY;
    $pidsArray['SOP'] = IEDEA_SOP;
    $pidsArray['SETTINGS'] = IEDEA_SETTINGS;
    $pidsArray['PROJECTS'] = IEDEA_PROJECTS;

    $unit_tests = new UnitTestFunctions($module, $pidsArray);
    $unit_tests->testCrons();
}

?>

