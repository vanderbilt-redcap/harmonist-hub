<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include_once(__DIR__ ."/../projects.php");
include_once(__DIR__ . "/../classes/HubUpdates.php");

$constantReq = $_REQUEST['constant'];
$allUpdatesAll = $module->getProjectSetting('hub-updates')['data'];

$constant_array = [$constantReq => ""];
if($constantReq == "ALL") {
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
error_log(".........".$constantReq);
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
        <div style="padding-top: 5px;text-align: center;padding-bottom: 25px;" '.$page_break.'>
            <div><h2><strong>' . $printData[$constant]["title"] . '</strong></h2></div>
            <div><span style="font-style: italic">Updated on ' . HubUpdates::getTemplateLastUpdatedDate($module, $constant) . '</span></div>
        </div>
        <div>
            <table class="table sortable-theme-bootstrap" data-sortable>
                <tr>
                    <td colspan="5" style="text-align: left !important;">
                        <span>' . HubUpdates::getIcon(HubUpdates::CHANGED, 'pdf') . ' <span style="">' . ucfirst(HubUpdates::CHANGED) . ' (' . ($allUpdates[$constant]['TOTAL'][HubUpdates::CHANGED] ?? 0) . ')</span></span>
                        &nbsp;&nbsp;&nbsp;&nbsp;<span>' . HubUpdates::getIcon(HubUpdates::ADDED, 'pdf') . ' <span style="">' . ucfirst(HubUpdates::ADDED) . ' (' . ($allUpdates[$constant]['TOTAL'][HubUpdates::ADDED] ?? 0) . ')</span></span>
                        &nbsp;&nbsp;&nbsp;&nbsp;<span>' . HubUpdates::getIcon(HubUpdates::REMOVED, 'pdf') . ' <span style="">' . ucfirst(HubUpdates::REMOVED) . ' (' . ($allUpdates[$constant]['TOTAL'][HubUpdates::REMOVED] ?? 0) . ')</span></span>
                    </td>
                </tr>
                <tr class="section-header">
                    <th>Status</th>
                    <th>Variable / Field Name</th>
                    <th>Field Label <br><em>Field Note</em></th>
                    <th>Field Attributes<br>(Field Type, Validation, Choices, Calculations, etc.)</th>
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

if($constant == "ALL"){
    $filename = "All_Projects_Hub_Updates_".date("Y-m-d_h-i",time());
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
#Download option
$dompdf->stream($filename);
$filesize = file_put_contents(EDOC_PATH.$storedName, ob_get_contents());
?>