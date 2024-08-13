<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include_once(__DIR__ ."/../projects.php");
include_once(__DIR__ . "/../classes/HubUpdates.php");

$constantReq = $_REQUEST['constant'];
$option = $_REQUEST['option'];
$filerepo = $_REQUEST['filerepo'];
if($option == "resolvedAll"){
    $allUpdatesAll = HubUpdates::compareDataDictionary($module, $pidsArray, 'resolved');
}else{
    $allUpdatesAll = $module->getProjectSetting('hub-updates')['data'];
}

$allUpdates = [];

$constant_array = [$constantReq => ""];
if($constantReq == "PDF") {
    $checked_values = $_REQUEST['checked_values'];
    $update_list = HubUpdates::getListOfChanges($checked_values);
    foreach ($update_list as $constant => $update_list_data){
        foreach ($allUpdatesAll[$constant] as $instrument => $update_data){
            foreach ($update_data as $status => $update_status_data){
                if(array_key_exists($status,$update_list_data)) {
                    foreach ($update_status_data as $var_name => $data) {
                        foreach ($update_list_data[$status] as $update_list_var_name) {
                            if ($var_name == $update_list_var_name) {
                                $allUpdates[$constant][$instrument][$status][$var_name] = $allUpdatesAll[$constant][$instrument][$status][$var_name];

                                if(!array_key_exists("TOTAL",$allUpdates[$constant])){
                                    $allUpdates[$constant]["TOTAL"] = [];
                                }
                                if(!array_key_exists($status,$allUpdates[$constant]["TOTAL"])){
                                    $allUpdates[$constant]["TOTAL"][$status] = 0;
                                }
                                $allUpdates[$constant]["TOTAL"][$status]++;
                            }
                        }
                    }
                }
            }
        }
    }
    $constant_array = $allUpdates;
}else if($constantReq == "ALL") {
    $constant_array = $allUpdatesAll;
    $allUpdates = $allUpdatesAll;
}else{
    $allUpdates[$constantReq] = $allUpdatesAll[$constantReq];
}

$printDataAll = HubUpdates::getPrintData($module, $pidsArray, $constant_array);
$printData = $printDataAll[0];
$oldValues = $printDataAll[1];

$page_num = '<style>.footer .page-number:after { content: counter(page); } .footer { position: fixed; bottom: 0px;color:grey }a{text-decoration: none;}</style>';
$page_styles = '<style>
.mainPDF{
   font-size:12pt;
}
table {
    border-collapse: collapse;
}
.table-bordered td, .table-bordered th {
    border: 1px solid #dee2e6;
}
thead tr:nth-child(1) th {
    background: white;
    position: sticky;
    top: 0;
    z-index: 10;
}

.table-bordered thead td, .table-bordered thead th {
    border-bottom-width: 2px;
}
.table thead th {
    vertical-align: bottom;
    border-bottom: 2px solid #dee2e6;
}
.table-bordered td, .table-bordered th {
    border: 1px solid #dee2e6;
}
.table td, .table th {
    padding: 0.75rem;
    vertical-align: top;
    border-top: 1px solid #dee2e6;
}
th {
    text-align: inherit;
    text-align: -webkit-match-parent;
}
.table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(0,0,0,0.05);
}
.label {
    width: 5px;
    height: 10px;
    display: inline;
    padding: 0.2em 0.6em 0.3em;
    font-size: 75%;
    font-weight: 700;
    line-height: 1;
    color: #fff;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 0.25em;
}
.labeltext{
    color:#fff;
    font-weight: bold;
}
.bg-warning {
    background-color: #ffc107 !important;
}
#bg-warning div{
    background-color:#ffc107 !important;
}
.title{
    font-weight: normal;
    padding-left: 15px;
}
.panel-heading{
    background-color: #D7D7D7;
    border-color: #ddd;
}
.resolved-heading td{
    background-color: #e9e9e9;
    border-color: #ddd;
    font-size: 14px;
    font-weight: normal;
    line-height: revert-layer;
}
.resolved-view-changes{
    float: right;
    padding-right: 15px;
    color: #337ab7 !important;
    font-weight: bold;
    margin-top: 3px;
}
.instrument-header{
    background-color:#f5f5f5 !important;
    font-size: 15px;
}
.section-header th{
    background-color:#ebebeb !important;
    font-weight: bold;
}
.changed{background-color:#ffc107 !important;}
.added{background-color:#5d9451 !important;}
.removed{background-color:#cb410b !important;}
tr.rowSelected td{
    background-color: #e6f5ff !important;
}
.resolved{
    background-color: #dcdcdc;
    border-color:#adacac;
}
.btn-resolved:hover {
    color: #000;
    background-color: #adacac;
    border-color:#797979;
}
.btn-resolved {
    background-color: #dcdcdc;
    border-color: #adacac !important;
    padding: 3px 10px !important;
    line-height: 1em !important;
}

</style>';

$html_pdf = '<!DOCTYPE html>
<html lang="en">
    <head>
        '.$page_styles. '
    </head>
    <body style="font-family:\'Calibri\';font-size:10pt;">'.$page_num.'
    <div class="footer"><span class="page-number">Page </span></div>';
    $pages = 0;
    foreach ($allUpdates as $constant => $project_data) {
        $pages++;
        $page_break = "";
        if($pages > 1){
            $page_break = 'style="page-break-before: always;"';
        }
        $html_pdf .= '<div class="container-fluid p-y-1" >
        <div '.$page_break.'></div>
        <div style="padding-top: 5px;text-align: center;padding-bottom: 25px;">
            <div><h2><strong>' . $printData[$constant]["title"] . '</strong></h2></div>
            <div><span style="font-style: italic">Updated on ' . HubUpdates::getTemplateLastUpdatedDate($module, $constant) . '</span></div>
        </div>
        <div>
            <table class="table sortable-theme-bootstrap" data-sortable style="width: 100%;">
                <tr style="width: 100%;">
                    <td colspan="5" style="text-align: left !important;">
                        <span>' . HubUpdates::getIcon(HubUpdates::CHANGED, 'pdf') . ' <span style="">' . ucfirst(HubUpdates::CHANGED) . ' (' . ($allUpdates[$constant]['TOTAL'][HubUpdates::CHANGED] ?? 0) . ')</span></span>
                        &nbsp;&nbsp;&nbsp;&nbsp;<span>' . HubUpdates::getIcon(HubUpdates::ADDED, 'pdf') . ' <span style="">' . ucfirst(HubUpdates::ADDED) . ' (' . ($allUpdates[$constant]['TOTAL'][HubUpdates::ADDED] ?? 0) . ')</span></span>
                        &nbsp;&nbsp;&nbsp;&nbsp;<span>' . HubUpdates::getIcon(HubUpdates::REMOVED, 'pdf') . ' <span style="">' . ucfirst(HubUpdates::REMOVED) . ' (' . ($allUpdates[$constant]['TOTAL'][HubUpdates::REMOVED] ?? 0) . ')</span></span>
                    </td>
                </tr>
                <tr class="section-header" style="width: 100%;">
                    <th style="width: 100%;">Status</th>
                    <th style="width: 100%;">Variable / Field Name</th>
                    <th style="width: 100%;">Field Label <br><em>Field Note</em></th>
                    <th style="width: 100%;">Field Attributes<br>(Field Type, Validation, Choices, Calculations, etc.)</th>
                </tr>';
        foreach ($project_data as $instrument => $instrumentData) {
            if ($instrument != "TOTAL") {
                $html_pdf .= '<tr>
                        <td colspan="5" class="instrument-header" style="text-align: left !important;">*<u>Instrument</u>: <em><strong>' . ucwords(str_replace("_", " ", $instrument)) . '</em></strong></td>
                    </tr>';
                foreach ($instrumentData as $status => $typeData) {
                    foreach ($typeData as $variable => $data) {
                        $html_pdf .= '<tr>
                                    <td>' . HubUpdates::getIcon($status, "pdf") . '</td>
                                    <td>' . HubUpdates::getFieldName($data, $oldValues[$constant][$variable], $status, 'field_name') . '</td>
                                    <td>';

                        if ($status == HubUpdates::CHANGED) {
                            $col = HubUpdates::getFieldLabel($data, $oldValues[$constant][$variable], $status, 'Section Header:', 'section_header');
                            $col .= HubUpdates::getFieldName($data, $oldValues[$constant][$variable], $status, 'field_label');
                            $col .= HubUpdates::getFieldLabel($data, $oldValues[$constant][$variable], $status, '', 'field_note');
                        } else {
                            $col = HubUpdates::getFieldLabel($data, $oldValues[$constant][$variable], $status, '', '');
                        }
                        $html_pdf .= $col;

                        $html_pdf .= '</td><td class="col-sm-4">';
                        if ($status == HubUpdates::CHANGED) {
                            $html_pdf .= HubUpdates::getFieldAttributesChanged($data, $oldValues[$constant][$variable]);
                        } else {
                            $html_pdf .= HubUpdates::getFieldAttributes($data);
                        }
                        $html_pdf .= '</td></tr>';
                    }
                }
            }
        }
        $html_pdf .= '</table></div></div>';
    }
$html_pdf .= '</html>';
//echo $html_pdf;

if($constantReq == "ALL"){
    $filename = "All_Projects_Hub_Updates_".date("Y-m-d_h-i",time());
}else if($constantReq == "PDF"){
    if($option == "resolved"){
        $filename = "Hub_Updates_Resolved_Changes_".date("Y-m-d_h-i",time());
    }else if($option == "resolvedAll"){
        $filename = "All_Projects_Resolved_Hub_Updates_".date("Y-m-d_h-i",time());
    }else{
        $filename = "Hub_Updates_Save_Changes_".date("Y-m-d_h-i",time());
    }
}else{
    $filename = $printData[$constant]["title"]."_Hub_Updates_".date("Y-m-d_h-i",time());
}

//SAVE PDF ON DB
$reportHash = $filename;
$storedName = md5($reportHash);
$filePath = EDOC_PATH.$storedName;

$dompdf = new \Dompdf\Dompdf();
$dompdf->loadHtml($html_pdf);
$dompdf->setPaper('A4', 'portrait');
$options = $dompdf->getOptions();
$options->setChroot(EDOC_PATH);
$dompdf->setOptions($options);
ob_start();
$dompdf->render();

if($filerepo == "true"){
    #Generate PDF format to Save in DB
    $output = $dompdf->output();
    $filesize = file_put_contents($filePath, $output);

    #Save document on DB
    $docId = \REDCap::storeFile($filePath, $pidsArray['PROJECTS'], $filename);
    unlink($filePath);

    #Save document in File Repository
    \REDCap::addFileToRepository($docId, $pidsArray['PROJECTS']);
    json_encode("success");
}else{
    #Download option
    $dompdf->stream($filename);
    $filesize = file_put_contents($filePath, ob_get_contents());
}
?>