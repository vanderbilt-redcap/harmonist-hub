<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once dirname(dirname(__FILE__))."/projects.php";

$record_id = htmlentities($_REQUEST['id'],ENT_QUOTES);
$Proj = new \Project($pidsArray['SOP']);
$event_id = $Proj->firstEventId;

$RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', array("record_id" => $record_id));
$sop = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP)[0];

$regions = \REDCap::getData($pidsArray['REGIONS'], 'json-array', null);
foreach ($regions as $region){
    $instance = $region['record_id'];
    if($sop["data_response_status"][$instance] == 1) {
        $array_repeat_instances = array();
        $arraySOP = array();
        $arraySOP['data_response_status'] = "0";
        $arraySOP['data_region'] = $region['record_id'];
        $arraySOP['region_participation_status_complete'] = "1";
        $array_repeat_instances[$record_id]['repeat_instances'][$event_id]['region_participation_status'][$instance] = $arraySOP;
        $results = \REDCap::saveData($pidsArray['SOP'], 'array', $array_repeat_instances,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false, 1, false, '');
    }else{
        break;
    }
}

$dataTable = \Vanderbilt\HarmonistHubExternalModule\getTablesInfo($module, $pidsArray['DATAMODEL']);
$tableHtml = "";
if(!empty($dataTable)) {
    # Get selected rows
    $tableHtml = \Vanderbilt\HarmonistHubExternalModule\generateTablesHTML_pdf($module, $pidsArray['CODELIST'], $dataTable,$sop['sop_tablefields']);
    $requested_tables = \Vanderbilt\HarmonistHubExternalModule\generateRequestedTablesList_pdf($dataTable,$sop['sop_tablefields']);

    $dataTable = \Vanderbilt\HarmonistHubExternalModule\getTablesInfo($module, $pidsArray['DATAMODEL']);
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

$date = new \DateTime();
$sop_updated_dt = $date->format('Y-m-d H:i:s');
$arraySOP = array();
$arraySOP[$record_id][$event_id]['sop_updated_dt'] = $sop_updated_dt;
if(!empty($tablefields)){
    $arraySOP[$record_id][$event_id]['shiny_json'] = json_encode($tablefields);
}

$RecordSetConcepts = \REDCap::getData($pidsArray['HARMONIST'], 'array', array("record_id" => $sop['sop_concept_id']));
$concept = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConcepts)[0];
$concept_id = $concept['concept_id'];
$concept_title = $concept['concept_title'];

$people = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array("record_id" => $sop['sop_creator']),array('firstname','lastname','email'))[0];
$sop_creator_name = $people['firstname'].' '.$people['lastname'];
$sop_creator_email = $people['email'];

$people = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array("record_id" => $sop['sop_creator2']),array('firstname','lastname','email'))[0];
$sop_creator2_name  = $people['firstname'].' '.$people['lastname'];
$sop_creator2_email = $people['email'];

$people = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array("record_id" => $sop['sop_datacontact']),array('firstname','lastname','email'))[0];
$sop_datacontact_name  = $people['firstname'].' '.$people['lastname'];
$sop_datacontact_email = $people['email'];

$RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', array("record_id" => $record_id));
$data = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP)[0];

$date = new \DateTime($sop['sop_due_d']);
$sop_due_d = $date->format('d F Y');

#FOLLOW COMMENTS
$follow_activity = $sop['follow_activity'];
$array_userid = explode(',',$follow_activity);

array_push($array_userid,$current_user['record_id']);
array_push($array_userid,$sop['sop_creator']);
array_push($array_userid,$sop['sop_creator2']);
array_push($array_userid,$sop['sop_datacontact']);
$array_userid = array_filter(array_unique($array_userid));
$string_userid = implode(",",$array_userid);
$arraySOP[$record_id][$event_id]['follow_activity'] = $string_userid;

$results = \Records::saveData($pidsArray['SOP'], 'array', $arraySOP,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
\Records::addRecordToRecordListCache($pidsArray['SOP'], $record_id,1);

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
    $dataformat_prefer = $module->getChoiceLabels('dataformat_prefer', $pidsArray['SOP']);
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

$img = \Vanderbilt\HarmonistHubExternalModule\getFile($module, $pidsArray['PROJECTS'], $settings['hub_logo_pdf'],'pdf');

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

$filename = $concept_id."_DataRequest_".date("Y-m-d_hi",time()).".pdf";

//SAVE PDF ON DB
$reportHash = $filename;
$storedName = md5($reportHash);
$filePath = APP_PATH_TEMP.$storedName;

//DOMPDF
$dompdf = new \Dompdf\Dompdf();
$dompdf->loadHtml($html_pdf);
$dompdf->setPaper('A4', 'portrait');
$options = $dompdf->getOptions();
$options->setChroot(APP_PATH_TEMP);
$dompdf->setOptions($options);
ob_start();
$dompdf->render();
//#Download option
$output = $dompdf->output();
$filesize = file_put_contents($filePath, $output);

//Save document on DB
$docId = \REDCap::storeFile($filePath, $pidsArray['SOP'], $filename);
unlink($filePath);

//Add document DB ID to project
$jsonConcepts = json_encode(array(array('record_id' => $record_id, 'sop_finalpdf' => $docId)));
$results = \Records::saveData($pidsArray['SOP'], 'json', $jsonConcepts,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
\Records::addRecordToRecordListCache($pidsArray['SOP'], $record_id,1);

echo json_encode('success');
?>