<?PHP
namespace Vanderbilt\HarmonistHubExternalModule;
require_once dirname(dirname(__FILE__))."/projects.php";
require_once APP_PATH_DOCROOT.'Classes/Files.php';

$record_id = htmlentities($_REQUEST['record'],ENT_QUOTES);

$RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', array("record_id" => $record_id));
$sop = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP)[0];

$dataTable = \Vanderbilt\HarmonistHubExternalModule\getTablesInfo($module, $pidsArray['DATAMODEL']);
$tableHtml = "";
if(!empty($dataTable)) {
    # Get selected rows
    $tableHtml = \Vanderbilt\HarmonistHubExternalModule\generateTablesHTML_pdf($module, $pidsArray['CODELIST'], $dataTable,$sop['sop_tablefields']);
    $requested_tables = \Vanderbilt\HarmonistHubExternalModule\generateRequestedTablesList_pdf($dataTable,$sop['sop_tablefields']);
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

$date = new \DateTime($sop['sop_due_d']);
$sop_due_d = $date->format('d F Y');

#FIRST PAGE
$first_page = "<tr><td align='center'>";
$first_page .= "<p><span style='font-size: 16pt;font-weight: bold;'>".$settings['hub_name_long']." (".$settings['hub_name'].")</span></p>";
$first_page .= "<p><span style='font-size: 16pt;font-weight: bold;'>DATA TRANSFER REQUEST – <span preview='sop_concept_id'>".$concept_id."</span></p><br/><br/>";
$first_page .= "<p><span style='font-size: 16pt;font-weight: bold;'>".$concept_title."</span></p><br/><br/>";
$first_page .= "<p><span style='font-size: 14pt;font-weight: bold; color:#449d44'>Data Due Date: ".$sop_due_d."</span></p></span><br/>";
if(trim($sop_creator_name) != "" || trim($sop_creator2_name) != "") {
    $first_page .= "<p><span style='border-bottom: 1px solid;font-weight: bold;font-size: 14pt'>Research Contact(s)</span>";
    $first_page .= "<div>" . $sop_creator_name . "</div>";
    $first_page .= "<div>" . $sop_creator_email . "</div>";
    $first_page .= "<div><a href='mailto:" . $sop_creator_email . "' style='text-decoration:none'>" . $sop_creator_email . "</a></div><br/>";
    if ($sop['sop_creator2'] != "" && $sop['sop_creator2'] != "Select Name") {
        $first_page .= "<div>" . $sop_creator2_name . "</div>";
        $first_page .= "<div>" . $sop_creator2_email . "</div>";
        $first_page .= "<div><a href='mailto:" . $sop_creator2_email . "' style='text-decoration:none'>" . $sop_creator2_email . "</a></div><br/>";
    }
}
if(trim($sop['sop_datacontact']) != "" && $sop['sop_datacontact'] != "Select Name"){
    $first_page .= "<p><span style='border-bottom: 1px solid;font-weight: bold;font-size: 14pt'>Data Contact</span>";
    $first_page .= "<div>" . $sop_datacontact_name . "</div>";
    $first_page .= "<div>" . $sop_datacontact_email . "</div>";
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
        foreach($sop['dataformat_prefer'] as $dataf) {
            if($dataf == $dataid){
                $dataformat_prefer_text .= $dataformat.", ";
            }
        }
    }
    $second_page .= "<p><span style='font-size: 12pt'><strong>Preferred file format:&nbsp;</strong></p><p>".rtrim($dataformat_prefer_text,", ")."</span></p>";
}
if($sop['dataformat_notes'] != ""){
    $second_page .= "<p style='font-size: 12pt'><strong>File format notes:&nbsp;</strong></p><p>".$sop['dataformat_notes']."</p>";
}
if($sop['sop_notes'] != ""){
    $second_page .= "<p><span style='font-size: 12pt;'><strong>General notes:&nbsp;</strong></p><p>".$sop['sop_notes']."</span></p>";
}
$second_page .= "<p><span style='font-size:16pt'><strong>5. List of Requested Tables</strong></span></p>";
$second_page .= "<p><span style='font-size: 12pt'>".$requested_tables."</span></p>";

$img = base64_encode(file_get_contents($module->getSafePath(\Vanderbilt\HarmonistHubExternalModule\getFile($module, $pidsArray['PROJECTS'], $settings['hub_logo_pdf'],'pdf'))));

$page_num = '<style>a{text-decoration: none;}</style>';

$html_print = "<html><body style='font-family:\"Calibri\";font-size:12pt;'>".$page_num
    ."<div class='footer'><span left: 0px;>".$concept_id."</span></div>"
    ."<div class='footer' style='left: 600px;'><span class='page-number'>Page </span></div>"
    ."<div class='mainPDF'><table style='width: 100%;'><tr><td align='center'><img src='data:image/png;base64,".$img."' style='padding-bottom: 30px;width:200px;' alt='Logo'></td></tr></table></div>"
    ."<div class='mainPDF' style='width: 995px;margin:0 auto;'><table style='width: 100%;'>".$first_page."<div style='page-break-before: always;'></div>"
    ."<div class='mainPDF'>".$second_page.'<div style="page-break-before: always;"></div>'
    ."<p><span style='font-size:16pt'><strong>6. Requested DES Tables</strong></span></p>"
    .$tableHtml
    ."</div></div>"
    ."</body></html>";

$q = $module->query("SELECT stored_name,doc_size FROM redcap_edocs_metadata WHERE doc_id= ?",[$sop['sop_finalpdf']]);
$pdf_file='';
while ($row = $q->fetch_assoc()) {
    $pdf_file = $row['stored_name'];
}

#CREATE ZIP FILE
$filename = $concept_id."_DataRequest_".date("Y-m-d_hi",time());
$zipname = $filename.'.zip';
$zipPath = $module->getSafePath($zipname,EDOC_PATH);

$zip = new \ZipArchive();
if ( $zip->open($zipPath, \ZipArchive::CREATE) !== TRUE) {
    exit("Error creating ZIP file");
}
#Add a file to zip and rename it
$zip->addFile($module->getSafePath($pdf_file,EDOC_PATH), $filename.'.pdf');

# Add a file new file to zip using the text specified
$download_file = file_get_contents( $module->getSafePath($filename.'.html',EDOC_PATH ));
$zip->addFromString(basename($filename.'.html'),$html_print);

$zip->close();

header("Content-type: application/zip");
header("Content-Disposition: attachment; filename=$zipname");
header("Content-length: " . filesize($zipPath));
header("Pragma: no-cache");
header("Expires: 0");
ob_clean();
flush();
readfile($zipPath);
unlink($zipPath);
?>

