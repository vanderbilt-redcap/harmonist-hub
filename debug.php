<?php
namespace Vanderbilt\HarmonistHubExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

if ($module->getUser()->isSuperUser()) {
    include_once("projects.php");

    #DEBBUG CODE HERE TO TEST DIRECTLY THINGS IN THE SERVERS

    print_array();
    getFile($module, $pidsArray['PROJECTS'], $settings['hub_logo_favicon'],'favicon')
}
?>

