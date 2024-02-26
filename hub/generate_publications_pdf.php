<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once dirname(dirname(__FILE__))."/projects.php";

$RecordSetConcetps = \REDCap::getData($pidsArray['HARMONIST'], 'array', null);
$concepts = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConcetps);
ArrayFunctions::array_sort_by_column($concepts, 'concept_id',SORT_DESC);

$years = array();
foreach ($concepts as $concept) {
    $output_year = $concept['output_year'];
    if($output_year != ""){
        foreach ($output_year as $index =>$value) {
            array_push($years, $output_year[$index]);
        }
    }
}
$years = array_unique($years);
arsort($years);

$page = "";
foreach ($years as $year){
    $page_year = '<div><strong>'.$year.'</strong></div>';
    $number = 0;
    $page_content = "";
    foreach ($concepts as $concept) {
        foreach ($concept['output_year'] as $index =>$value) {
            //only manuscripts
            if($value == $year && $concept['output_type'][$index] == '1'){
                $number++;
                $page_content .= '<div style="padding: 10px;"><table><tr><td style="vertical-align: text-top;width:30px">'.$number.'. </td><td>'.htmlentities($concept['output_authors'][$index]).". ".htmlentities($concept['output_title'][$index]).". ".$concept['output_year'][$index].
                ", ".htmlentities($concept['output_venue'][$index]).". ".htmlentities($concept['output_citation'][$index]).". <a href='https://www.ncbi.nlm.nih.gov/pmc/articles/".$concept['output_pmcid'][$index]."' target='_blank'>".htmlentities($concept['output_pmcid'][$index])."<i class='fa fa-fw fa-external-link' aria-hidden='true'></i></a>. Associated with IeDEA concept ". $concept['concept_id'].'</td></tr></table></div>';
            }
        }
    }
    //Only add if we have content for that year
    if($number != 0){
        $page .= $page_year.$page_content;
    }
}

$date = new \DateTime();
$download_date = $date->format('d M Y');

$page_num = '<style>.footer .page-number:after { content: counter(page); } .footer { position: fixed; bottom: 0px;color:grey }</style>';

$img = \Vanderbilt\HarmonistHubExternalModule\getFile($module, $pidsArray['PROJECTS'], $settings['hub_logo_pdf'],'pdf');

$html_pdf = "<html><body style='font-family:\"Calibri\";font-size:10pt;'>".$page_num
    ."<div class='footer' style='left: 600px;'><span class='page-number'>Page </span></div>"
    ."<div class='mainPDF'><table style='width: 100%;'><tr><td align='center'><img src='".$img."' style='padding-bottom: 30px;width: 100;' alt='Publications'></td></tr></table></div>"
    ."<div class='mainPDF'><table style='width: 100%;'><tr><td align='center'><strong>Manuscript List for the ".$settings['hub_name_long']." (".$settings['hub_name'].")</strong></td></tr></table></div>"
    ."<div class='mainPDF'><table style='width: 100%;'><tr><td align='center'>Downloaded ".$download_date." from <a href='".$settings['hub_organization']."' style='text-decoration:none;color: #23527c;'>".$settings['hub_organization'].".org</a></td></tr></table></div>"
    ."<div class='mainPDF'><table style='width: 100%;'><tr><td align='center'><em>".$settings['hub_pubpdf_note']."</em></td></tr></table></div>"
    ."<div class='mainPDF'>".$page."</div>"
    ."</div>"
    ."</body></html>";
//echo $html_pdf;

$filename = $settings['hub_name']." Manuscript List_".date("Y-m-d_h-i",time());

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
//#Download option
$dompdf->stream($filename);
$filesize = file_put_contents(EDOC_PATH.$storedName, ob_get_contents());

?>