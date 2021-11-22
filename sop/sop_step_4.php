<div id="loader" style="display:none;"></div>

<?PHP
$dataTable = \Vanderbilt\HarmonistHubExternalModule\getTablesInfo($module, $pidsArray['DATAMODEL']);
$tableHtml = "";
if(!empty($dataTable)) {
    # Get selected rows
    $tableHtml = \Vanderbilt\HarmonistHubExternalModule\generateTablesHTML_steps($pidsArray['CODELIST'], $dataTable);
    $requested_tables = \Vanderbilt\HarmonistHubExternalModule\generateRequestedTablesList($dataTable);
}

#FIRST PAGE
$first_page = "<tr><td align='center'>";
$first_page .= "<p><span style='font-size: 16pt;font-weight: bold;'>".$settings['hub_name_long']." (".$settings['hub_name'].")</span></p>";
$first_page .= "<p><span style='font-size: 16pt;font-weight: bold;'>DATA TRANSFER REQUEST – <span preview='sop_concept_id'></span></span></p><br/><br/>";
$first_page .= "<p><span style='font-size: 16pt;font-weight: bold;' preview='sop_concept_title'></span></p><br/><br/>";
$first_page .= "<p><span style='font-size: 14pt;font-weight: bold; color:#449d44'>Data Due: <span preview='sop_due_d_preview'></span></span></p><br/>";
$first_page .= "<p><span style='border-bottom: 1px solid;font-weight: bold;font-size: 14pt' preview='sop_creator_title'></span></p>";
$first_page .= "<span style='font-size: 12pt'>";
$first_page .= "<p preview='sop_creator_name'></p>";
$first_page .= "<p preview='sop_creator_org'></p>";
$first_page .= "<p><a href='' style='text-decoration:none' preview='sop_creator_email'></a></p>";
$first_page .= "<p preview='sop_creator2_name'></p>";
$first_page .= "<p preview='sop_creator2_org'></p>";
$first_page .= "<p><a href='' style='text-decoration:none' preview='sop_creator2_email'></a></p>";
$first_page .= "<p><span style='border-bottom: 1px solid;font-weight: bold;font-size: 14pt' preview='sop_datacontact_title'></span></p>";
$first_page .= "<p preview='sop_datacontact_name'></p>";
$first_page .= "<p preview='sop_datacontact_org'></p>";
$first_page .= "<p><a href='' style='text-decoration:none' preview='sop_datacontact_email'></a></p>";
$first_page .= "<br/><br/><p><span style='font-size: 11pt;color:#999' preview='sop_version_date'></span></p><br/>";
$first_page .= "</span></td></tr></table>";

#SECOND PAGE
$second_page = "<p><span style='font-size:16pt'><strong>1. Introduction</strong></span></p>";
$second_page .= "<p><span style='font-size: 12pt'>This document provides guidance on the preparation of data files for the transfer of data for the IeDEA Concept <span preview='sop_concept_id'></span>: <span preview='sop_concept_title'></span></span></p>";
$second_page .= "<p><span style='font-size:16pt'><strong>2. Inclusion Criteria</strong></span></p>";
$second_page .= "<p><span style='font-size: 12pt' preview='sop_inclusion'></span></p>";
$second_page .= "<p><span style='font-size:16pt'><strong>3. Exclusion Criteria</strong></span></p>";
$second_page .= "<p><span style='font-size: 12pt' preview='sop_exclusion'></span></p>";
$second_page .= "<p><span style='font-size:16pt'><strong>4. Data Submission Notes</strong></span></p>";
$second_page .= "<p><span style='font-size: 12pt' preview='dataformat_prefer_text'></span></p>";
$second_page .= "<p><span style='font-size: 12pt;font-weight: bold;' preview='dataformat_notes_header'></span></p><p><span style='font-size: 12pt' preview='dataformat_notes'></span></p>";
$second_page .= "<p><span style='font-size: 12pt;font-weight: bold;' preview='sop_notes_header'></span></p><p><span style='font-size: 12pt' preview='sop_notes'></span></p>";
$second_page .= "<p><span style='font-size:16pt'><strong>5. List of Requested Tables</strong></span></p>";
$second_page .= "<p><span style='font-size: 12pt' preview='requested_tables'>".$requested_tables."</span></p>";

$html_print = '<html><body>'
    .'<br/><br/><br/>'
    .'<div class="container-fluid"><div class="row"><div class="col-md-12"></div></div></div>'
    .'<div style="width: 995px;margin:0 auto"><table style="width: 100%;">'
        .'<tr><td align="center"><img src="'.\Vanderbilt\HarmonistHubExternalModule\getFile($module, $pidsArray['PROJECTS'], $settings['hub_logo_pdf'],'src').'" style="width:200px;padding-bottom: 30px;" alt="Logo"></td></tr></table>'
        .'<table style="width:995px;font-family:Calibri;font-size:12pt">'.$first_page
        .'<br/><br/><br/>'.$second_page
        .'<p><span style="font-size:16pt"><strong>6. Requested DES Tables</strong></span></p>'
        .'<div id="preview_table">'.$tableHtml.'</div>'
    .'</div>'
    .'</body></html>';

print $html_print;

?>


