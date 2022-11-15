<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once "projects.php";

$edoc = $_REQUEST['edoc'];

$q = $module->query("SELECT doc_name, stored_name FROM redcap_edocs_metadata WHERE doc_id = ?",[$edoc]) ;
$row_concept_file = $q->fetch_assoc();

header('Content-type: application/pdf');
header('Content-Disposition: inline; filename="'.$row_concept_file['doc_name'].'"');
header('Content-Transfer-Encoding: binary');
header('Accept-Ranges: bytes');
@readfile($module->framework->getSafePath($row_concept_file['stored_name'], EDOC_PATH));
?>