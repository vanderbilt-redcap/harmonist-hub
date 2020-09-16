<?php
namespace Vanderbilt\HarmonistHubExternalModule;

require_once(dirname(dirname(__FILE__))."/classes/UnitTestFunctions.php");


$url = json_encode($module->getUrl("hub_unit_test_AJAX.php?pid=".IEDEA_PROJECTS));
?>
<h3>Unit Testing</h3>
<p class="hub-title"><?=$settings['hub_unit_test_text']?></p>
<div class="optionSelect">
    <div style="margin: 0 auto 15px auto;width: 200px;">
        <div style="display: inline-block">
            <button onclick='startUnitTest(<?=$url?>)' class="btn btn-success btn-md" id="unitTestbtn">Start Unit test</button>
        </div>
    </div>
</div>
<div>
    <div class="alert fade in col-md-12" style="display: none;" id="unitTestMsgContainer"></div>
</div>
<?php
$unit_tests = new UnitTestFunctions($module);
$unit_tests->testCrons();


?>

