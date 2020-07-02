<?php
define('NOAUTH',true);
require_once dirname(dirname(__FILE__))."/base.php";

$record_id = $_REQUEST['id'];

$projectSOP = new \Plugin\Project(IEDEA_SOP);
$recordSOP = new \Plugin\RecordSet($projectSOP, array("record_id" => $record_id));
$sop = $recordSOP->getDetails()[0];

$projectRegions = new \Plugin\Project(IEDEA_REGIONS);
$RecordSetRegions = new \Plugin\RecordSet($projectRegions, array(\Plugin\RecordSet::getKeyComparatorPair($projectRegions->getFirstFieldName(),"!=") => ""));
$regions = $RecordSetRegions->getDetails();
foreach ($regions as $region){
    $instance = $region['record_id'];
    if($instance == 1){
        $instance = '';
    }
    if($sop["data_response_status"][$instance] == "") {
        $recordSOPSave = \Plugin\Record::createRecordFromId($projectSOP, $record_id);
        $recordSOPSave->updateDetails(["data_response_status" => [$instance => '0']], true);
        $recordSOPSave->updateDetails(["data_region" => [$instance => $region['record_id']]], true);
        $recordSOPSave->updateDetails(["region_participation_status_complete" => [$instance => '1']], true);
        $data = '[{"record_id":"' . $record_id . '","redcap_repeat_instrument":"region_participation_status","redcap_repeat_instance":' . $instance . '}]';
        \REDCap::saveData(IEDEA_SOP, 'json', $data);
    }else{
        break;
    }
}

$dataTable = getTablesInfo(IEDEA_DATAMODEL);
$tableHtml = "";
if(!empty($dataTable)) {
    # Get selected rows
    $tableHtml = generateTablesHTML_pdf($dataTable,$sop['sop_tablefields']);
    $requested_tables = generateRequestedTablesList_pdf($dataTable,$sop['sop_tablefields']);

    $dataTable = getTablesInfo(IEDEA_DATAMODEL);
    $tablefields = array();
    foreach( $dataTable as $data ) {
        if (!empty($data['record_id'])) {
            $sop_tablefields = explode(',',$sop['sop_tablefields']);
            foreach ($sop_tablefields as $tables){
                $table_id = explode('_',$tables);
                if($table_id[0] == $data['record_id']){
                    //the first element the id is always blank but it's saved in the json as 1
                    $id =  ($table_id[1] == 1)? "":$table_id[1];
                    if (!array_key_exists($data['table_name'], $tablefields)) {
                        $tablefields[$data['table_name']] = array();
                    }
                    array_push($tablefields[$data['table_name']],$data['variable_name'][$id]);
                }
            }
        }
    }
}

$date = new DateTime();
$sop_updated_dt = $date->format('Y-m-d H:i:s');
$recordSOP_update = \Plugin\Record::createRecordFromId($projectSOP, $record_id);
$recordSOP_update->updateDetails(["sop_updated_dt" => $sop_updated_dt], true);
if(!empty($tablefields)){
    $recordSOP_update->updateDetails(["shiny_json" => json_encode($tablefields)], true);
}
\Records::addRecordToRecordListCache($projectSOP->getProjectId(), $recordSOP_update->getId(),$projectSOP->getArmNum());

$projectConcepts = new \Plugin\Project(IEDEA_HARMONIST);
$RecordSetConcepts = new \Plugin\RecordSet($projectConcepts, array("record_id" => $sop['sop_concept_id']));
$concept_id = $RecordSetConcepts->getDetails()[0]['concept_id'];
$concept_title = $RecordSetConcepts->getDetails()[0]['concept_title'];

$projectPeople = new \Plugin\Project(IEDEA_PEOPLE);
$RecordSetPeople = new \Plugin\RecordSet($projectPeople, array("record_id" => $sop['sop_creator']));
$sop_creator_name = $RecordSetPeople->getDetails()[0]['firstname'].' '.$RecordSetPeople->getDetails()[0]['lastname'];
$sop_creator_email = $RecordSetPeople->getDetails()[0]['email'];

$RecordSetPeople = new \Plugin\RecordSet($projectPeople, array("record_id" => $sop['sop_creator2']));
$sop_creator2_name = $RecordSetPeople->getDetails()[0]['firstname'].' '.$RecordSetPeople->getDetails()[0]['lastname'];
$sop_creator2_email = $RecordSetPeople->getDetails()[0]['email'];

$RecordSetPeople = new \Plugin\RecordSet($projectPeople, array("record_id" => $sop['sop_datacontact']));
$sop_datacontact_name = $RecordSetPeople->getDetails()[0]['firstname'].' '.$RecordSetPeople->getDetails()[0]['lastname'];
$sop_datacontact_email = $RecordSetPeople->getDetails()[0]['email'];

$RecordSetSOPDetails = new \Plugin\RecordSet($projectSOP, array("record_id" => $record_id));
$data = $RecordSetSOPDetails->getDetails()[0];

$date = new DateTime($sop['sop_due_d']);
$sop_due_d = $date->format('d F Y');

#FIRST PAGE
$first_page = "<tr><td align='center'>";
$first_page .= "<p><span style='font-size: 16pt;font-weight: bold;'>".$settings['hub_name_long']." (".$settings['hub_name'].")</span></p>";
$first_page .= "<p><span style='font-size: 16pt;font-weight: bold;'>DATA TRANSFER REQUEST – <span preview='sop_concept_id'>".$concept_id."</span></p><br/><br/>";
$first_page .= "<p><span style='font-size: 16pt;font-weight: bold;'>".$concept_title."</span></p><br/><br/>";
$first_page .= "<p><span style='font-size: 14pt;font-weight: bold; color:#449d44'>Data Due: ".$sop_due_d."</span></p></span><br/>";
$first_page .= "<p><span style='border-bottom: 1px solid;font-weight: bold;font-size: 14pt'>Research Contact(s)</span>";
$first_page .= "<div>" . $sop_creator_name . "</div>";
$first_page .= "<div>" . $data['sop_creator_org'] . "</div>";
$first_page .= "<div><a href='mailto:" . $sop_creator_email . "' style='text-decoration:none'>" .$sop_creator_email . "</a></div><br/>";
if($sop['sop_creator2'] != "" && $sop['sop_creator2'] != "Select Name"){
    $first_page .= "<div>" . $sop_creator2_name. "</div>";
    $first_page .= "<div>" . $data['sop_creator2_org'] . "</div>";
    $first_page .= "<div><a href='mailto:" . $sop_creator2_email . "' style='text-decoration:none'>" .$sop_creator2_email . "</a></div><br/>";
}
if($sop['sop_datacontact'] != "" && $sop['sop_datacontact'] != "Select Name"){
    $first_page .= "<p><span style='border-bottom: 1px solid;font-weight: bold;font-size: 14pt'>Data Contact</span>";
    $first_page .= "<div>" . $sop_datacontact_name . "</div>";
    $first_page .= "<div>" . $data['sop_datacontact_org'] . "</div>";
    $first_page .= "<div><a href='mailto:" . $sop_datacontact_email . "' style='text-decoration:none'>" .$sop_datacontact_email . "</a></div><br/>";
}
$first_page .= "<span style='font-size: 12pt'>";
$first_page .= "<br/><br/><p><span style='font-size: 11pt;color:#999;'>Data Request Version: ".date('d F Y')."</span></p><br/>";
$first_page .= "</span></td></tr></table>";

#SECOND PAGE
$second_page = "<p><span style='font-size:16pt'><strong>1. Introduction</strong></span></p>";
$second_page .= "<p><span style='font-size: 12pt'>This document provides guidance on the preparation of data files for the transfer of data for the IeDEA Concept ".$concept_id.": ".$concept_title.". </span></p>";
$second_page .= "<p><span style='font-size:16pt'><strong>2. Inclusion Criteria</strong></span></p>";
$second_page .= "<p><span style='font-size: 12pt'>".$sop['sop_inclusion']."</span></p>";
$second_page .= "<p><span style='font-size:16pt'><strong>3. Exclusion Criteria</strong></span></p>";
$second_page .= "<p><span style='font-size: 12pt'>".$sop['sop_exclusion']."</span></p>";
$second_page .= "<p><span style='font-size:16pt'><strong>4. Data Submission Notes</strong></span></p>";
if($sop['dataformat_prefer'] != ""){
    $dataformat_prefer = \Plugin\Project::convertEnumToArray($projectSOP->getMetadata('dataformat_prefer')->getElementEnum());
    foreach($dataformat_prefer as $dataid => $dataformat){
        foreach($data['dataformat_prefer'] as $dataf) {
            if($dataf == $dataid){
                $dataformat_prefer_text .= $dataformat.", ";
            }
        }
    }
    $second_page .= "<p><span style='font-size: 12pt'><strong>Preferred file format:&nbsp;</strong></p><p>".rtrim($dataformat_prefer_text,", ")."</span></p>";
}
if($sop['dataformat_notes'] != ""){
    $second_page .= "<p><span style='font-size: 12pt;'><strong>File format notes:&nbsp;</strong></p><p>".$sop['dataformat_notes']."</span></p>";
}
if($sop['sop_notes'] != ""){
    $second_page .= "<p><span style='font-size: 12pt;'><strong>General notes:&nbsp;</strong></p><p>".$sop['sop_notes']."</span></p>";
}
$second_page .= "<p><span style='font-size:16pt'><strong>5. List of Requested Tables</strong></span></p>";
$second_page .= "<p><span style='font-size: 12pt'>".$requested_tables."</span></p>";

$page_num = '<style>.footer .page-number:after { content: counter(page); } .footer { position: fixed; bottom: 0px;color:grey }a{text-decoration: none;}</style>';


$img = 'data:image/png;base64,'.base64_encode(file_get_contents(loadImg($settings['hub_logo_pdf'],$secret_key,$secret_iv,'img/IeDEA-logo-200px.png','pdf')));

$html_pdf = "<html><body style='font-family:\"Calibri\";font-size:10pt;'>".$page_num
    ."<div class='footer'><span left: 0px;>".$concept_id."</span></div>"
    ."<div class='footer' style='left: 600px;'><span class='page-number'>Page </span></div>"
    ."<div class='mainPDF'><table style='width: 100%;'><tr><td align='center'><img src='".$img."' style='width:200px;padding-bottom: 30px;' alt='Logo'></td></tr></table></div>"
    ."<div class='mainPDF' id='page_html_style'><table style='width: 100%;'>".$first_page."<div style='page-break-before: always;'></div>"
    ."<div class='mainPDF'>".$second_page."<div style='page-break-before: always;'></div>"
    ."<p><span style='font-size:16pt'><strong>6. Requested DES Tables</strong></span></p>"
    .$tableHtml
    ."</div></div>"
    ."</body></html>";

$filename = $concept_id."_DataRequest_".date("Y-m-d_hi",time());

//SAVE PDF ON DB
$reportHash = $filename;
$storedName = md5($reportHash);
$filePath = EDOC_PATH.$storedName;

//DOMPDF
$dompdf = new \Dompdf\Dompdf();
$dompdf->loadHtml($html_pdf);
$dompdf->setPaper('A4', 'portrait');
ob_start();
$dompdf->render();
//#Download option
$output = $dompdf->output();
//$dompdf->stream($filename);
$filesize = file_put_contents(EDOC_PATH.$storedName, $output);
//$filesize = file_put_contents(EDOC_PATH.$storedName, ob_get_contents());

//Save document on DB
$sql = "INSERT INTO redcap_edocs_metadata (stored_name,mime_type,doc_name,doc_size,file_extension,gzipped,project_id,stored_date) VALUES
      ('".db_escape($storedName)."','".db_escape('application/octet-stream')."','".db_escape($reportHash.".pdf")."',".db_escape($filesize).",'".db_escape('.pdf')."','".db_escape('0')."','".db_escape(IEDEA_SOP)."','".db_escape(date('Y-m-d h:i:s'))."')";
db_query($sql);
$docId = db_insert_id();


//Add document DB ID to project
$project = new \Plugin\Project(IEDEA_SOP);
$record = \Plugin\Record::createRecordFromId($project,$record_id);
$record->updateDetails(['sop_finalpdf' => $docId],true);
\Records::addRecordToRecordListCache($project->getProjectId(), $record->getId(),$project->getArmNum());

//echo json_encode($html_pdf);
echo json_encode('success');
?>