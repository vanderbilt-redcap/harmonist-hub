<?php
require_once dirname(dirname(__FILE__))."/base.php";

if($project_id == IEDEA_DATAMODEL){
    checkAndUpdatJSONCopyProject('0a');
}else if($project_id == IEDEA_CODELIST){
    checkAndUpdatJSONCopyProject('0b');
}

?>