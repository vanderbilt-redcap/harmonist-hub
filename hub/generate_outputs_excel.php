<?php
namespace Vanderbilt\HarmonistHubExternalModule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
require_once dirname(dirname(__FILE__))."/projects.php";

$RecordSetConcetps = \REDCap::getData($pidsArray['HARMONIST'], 'array', null);
$concepts = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConcetps);

$extra_outputs = \REDCap::getData($pidsArray['EXTRAOUTPUTS'], 'json-array', null);
if(!empty($extra_outputs)) {
    ArrayFunctions::array_sort_by_column($extra_outputs, 'output_year', SORT_DESC);
}
if(!empty($comments_sevenDaysYoung)) {
    ArrayFunctions::array_sort_by_column($comments_sevenDaysYoung, 'concept_id', SORT_DESC);
}

$abstracts_publications_type = $module->getChoiceLabels('output_type', $pidsArray['HARMONIST']);
$excel_data = array();

if(!empty($concepts)) {
    foreach ($concepts as $concept) {
        $output_year = $concept['output_year'];
        if(!empty($output_year)) {
            arsort($output_year);
        }
        foreach ($output_year as $index => $value) {
            $excel_data_aux = array();
            $excel_data_aux[0] = $abstracts_publications_type[$concept['output_type'][$index]];
            $excel_data_aux[1] = $concept['output_title'][$index];
            $excel_data_aux[2] = $concept['output_authors'][$index];
            $excel_data_aux[3] = $concept['output_venue'][$index];
            $excel_data_aux[4] = $concept['output_year'][$index];
            $excel_data_aux[5] = $concept['output_citation'][$index];
            $excel_data_aux[6] = $concept['output_pmcid'][$index];
            $excel_data_aux[7] = $concept['output_url'][$index];
            $excel_data_aux[8] = "MR";
            $excel_data_aux[9] = "H:1";
            array_push($excel_data, $excel_data_aux);
        }
    }
}
#Regional Content
if(!empty($extra_outputs)) {
    foreach ($extra_outputs as $output) {
        $excel_data_aux = array();
        $excel_data_aux[0] = $abstracts_publications_type[$output['output_type']];
        $excel_data_aux[1] = $output['output_title'];
        $excel_data_aux[2] = $output['output_authors'];
        $excel_data_aux[3] = $output['output_venue'];
        $excel_data_aux[4] = $output['output_year'];
        $excel_data_aux[5] = $output['output_citation'];
        $excel_data_aux[6] = $output['output_pmcid'];
        $excel_data_aux[7] = $output['output_url'];

        if ($output['producedby_region'] == 2) {
            $excel_data_aux[8] = "MR";
        } else if ($output['producedby_region'] == 1) {
            $my_region = \REDCap::getData($pidsArray['REGIONS'], 'json-array', array('record_id' => $output['lead_region']),array('region_code'))[0]['region_code'];
            $region = "";
            if ($my_region != "") {
                $region = " (" . $my_region . ")";
            }
            $excel_data_aux[8] = "R" . $region;
        }
        $excel_data_aux[9] = "H:27";
        array_push($excel_data, $excel_data_aux);
    }
}

#EXEL SHEET
$filename = "Outputs- " . date("Y-m-d_hi",time()) . ".xlsx";

$styleArray = array(
    'font'  => array(
        'size'  => 10,
        'name'  => 'Calibri'
    ),
    'alignment' => array(
        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
    ));

$spreadsheet = new Spreadsheet();
$spreadsheet->getDefaultStyle()->applyFromArray($styleArray);
$sheet = $spreadsheet->getActiveSheet();

#SECTION HEADERS
$section_headers = array(0=>"output_type",1=>"output_title",2=>"output_authors",3=>"output_venue",4=>"output_year",5=>"output_citation",6=>"output_pmcid",7=>"output_url",8=>"Region",9=>"Harmonist Project");
$section_headers_leters = array(0=>'A',1=>'B',2=>'C',3=>'D',4=>'E',5=>'F',6=>'G',7=>'H',8=>'I',9=>'J');
$section_headers_width = array(0=>'14',1=>'50',2=>'50',3=>'10',4=>'10',5=>'25',6=>'15',7=>'25',8=>'10',9=>'10');
$section_centered = array(0=>'0',1=>'0',2=>'0',3=>'0',4=>'1',5=>'0',6=>'0',7=>'0',8=>'1',9=>'1');
$row_number = 1;
$sheet = ExcelFunctions::getExcelHeaders($sheet,$section_headers,$section_headers_leters,$section_headers_width,$row_number);
$sheet->setAutoFilter('A1:K1');
$row_number++;
$sheet = ExcelFunctions::getExcelData($sheet,$excel_data,$section_headers,$section_headers_leters,$section_centered,$row_number,"1");

#Rename sheet
$sheet->setTitle('Outputs');

$writer = new Xlsx($spreadsheet);

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="'.$filename.'"');
$writer->save("php://output");

?>