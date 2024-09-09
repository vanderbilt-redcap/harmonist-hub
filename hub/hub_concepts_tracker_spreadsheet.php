<?php
namespace Vanderbilt\HarmonistHubExternalModule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
require_once dirname(dirname(__FILE__))."/projects.php";


$RecordSetConcetps = \REDCap::getData($pidsArray['HARMONIST'], 'array', null);
$concepts = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConcetps,$pidsArray['HARMONIST']);
$multireg_pub = array();
$multireg_con = array();
$multireg_abs = array();
if(!empty($concepts)){
    foreach ($concepts as $concept){
        $group_name = "";
        if($concept['wg_link'] != ""){
            $group_name = \REDCap::getData($pidsArray['GROUP'], 'json-array', array('record_id' => $concept['wg_link'] ),array('group_name'))[0]['group_name'];
        }
        $ec_approval = "";
        if($concept['ec_approval_d'] != ""){
            $ec_approval = date("F d, Y",strtotime($concept['ec_approval_d']));
        }

        $lead_author = "";
        $senior_author = "";
        if(array_key_exists('person_link',$concept)){
            foreach ($concept['person_link'] as $index =>$person){
                if($concept['person_role'][$index] == "1" || $concept['person_role'][$index] == "2"){
                    $people = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array('record_id' => $person),array('firstname','lastname'))[0];
                    if($concept['person_role'][$index] == "1"){
                        $lead_author .= $people['lastname'].", ".$people['firstname']. " & ";
                    }else if($concept['person_role'][$index] == "2"){
                        $senior_author .= $people['lastname'].", ".$people['firstname']. " & ";
                    }
                }
            }
        }

        $output_title_aux = array();
        $output_index = 11;
        if(array_key_exists('output_title',$concept)){
            foreach ($concept['output_title'] as $output_title){
                $output_title_aux[$output_index] = $output_title;
                $output_index++;
            }
        }

        $admin_update_most_recent = "";
        $admin_update_previous = "";
        if(array_key_exists('adminupdate_d',$concept)){
            $date_ordered = $concept['adminupdate_d'];
            arsort($date_ordered);
            $counter = 0;
            foreach ($date_ordered as $index => $date){
                if($counter == 0){
                    $admin_update_most_recent = $concept['admin_update'][$index];
                }else if($counter == 1){
                    $admin_update_previous = $concept['admin_update'][$index];
                }
                $counter++;
            }
        }


        #CONCEPTS
        $concepts_tracker_aux = array();
        $concepts_tracker_aux[0] = $concept['concept_id'];
        $concepts_tracker_aux[1] = $group_name;
        $concepts_tracker_aux[2] = $concept['concept_title'];
        $concepts_tracker_aux[3] = $concept['start_year'];
        $concepts_tracker_aux[4] = rtrim($lead_author," & ");
        $concepts_tracker_aux[5] = rtrim($senior_author," & ");
        $concepts_tracker_aux[6] = $ec_approval;
        $concepts_tracker_aux[7] = "";
        $concepts_tracker_aux[8] = "";
        $concepts_tracker_aux[9] = $admin_update_most_recent;
        $concepts_tracker_aux[10] = $admin_update_previous;
        $concepts_tracker_aux[11] = $concept['active_y'];
        $concepts_tracker_aux[12] = $output_title_aux[11];
        $concepts_tracker_aux[13] = $output_title_aux[12];
        $concepts_tracker_aux[14] = $output_title_aux[13];
        $concepts_tracker_aux[15] = $output_title_aux[14];
        array_push($multireg_con,$concepts_tracker_aux);

        #PUBLICATIONS
        if(array_key_exists('output_year',$concept)){
            $number_pubs = count($concept['output_year']);
            $count_pub = 0;
            foreach ($concept['output_year'] as $id => $year){
                $concept_id = $concept['concept_id'];
                $count_pub++;
                if($number_pubs > 1){
                    $concept_id .= " (".$count_pub." of ".$number_pubs." publications)";
                }

                $pubs_tracker_aux = array();
                $pubs_tracker_aux[0] = $concept_id;
                $pubs_tracker_aux[1] = $concept['concept_title'];
                $pubs_tracker_aux[2] = $year;
                $pubs_tracker_aux[3] = $concept['output_authors'][$id];
                $pubs_tracker_aux[4] = $concept['output_citation'][$id];
                $pubs_tracker_aux[5] = "";
                $pubs_tracker_aux[6] = $concept['output_pmcid'][$id];
                $pubs_tracker_aux[7] = "Y";
                $pubs_tracker_aux[8] = "Y";
                array_push($multireg_pub,$pubs_tracker_aux);



            }
        }

        #ABSTRACTS
        if(array_key_exists('output_type',$concept)){
            foreach ($concept['output_type'] as $id => $abstract){
                if($abstract == "2"){

                    $abs_tracker_aux = array();
                    $abs_tracker_aux[0] = $concept['concept_id'];
                    $abs_tracker_aux[1] = $concept['output_venue'][$id];
                    $abs_tracker_aux[2] = $concept['output_year'][$id];
                    $abs_tracker_aux[3] = $concept['output_title'][$id];
                    $abs_tracker_aux[4] = $concept['output_authors'][$id];
                    $abs_tracker_aux[5] = "";
                    $abs_tracker_aux[6] = "Y";
                    array_push($multireg_abs,$abs_tracker_aux);
                }
            }
        }

    }
}

ArrayFunctions::array_sort_by_column($multireg_con,0);
ArrayFunctions::array_sort_by_column($multireg_pub,2);
ArrayFunctions::array_sort_by_column($multireg_abs,2);

#EXEL SHEET
$filename = "Tracker - multiregional - " . date("F Y") . ".xlsx";

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

///MULTIREG CONCEPTS///
#SECTION HEADERS
$section_headers = array(0=>"Tracking No",1=>"Working Group/Supplement",2=>"Concept title",3=>"Year",4=>"Lead Author",5=>"IeDEA senior author",
    6=>"Date EC approval",7=>"Date SG concept circulated",8=>"Date SG comments due",9=>"Most Recent Update",10=>"Previous Update",11=>"Active?",12=>"Paper 1 Title",13=>"Paper 2 Title",14=>"Paper 3 Title",15=>"Paper 4 Title");
$section_headers_leters = array(0=>'A',1=>'B',2=>'C',3=>'D',4=>'E',5=>'F',6=>'G',7=>'H',8=>'I',9=>'J',10=>'K',11=>'L',12=>'M',13=>'N',14=>'O',15=>'P');
$section_headers_width = array(0=>'14',1=>'20',2=>'30',3=>'10',4=>'25',5=>'15',6=>'15',7=>'15',8=>'15',9=>'30',10=>'30',11=>'10',12=>'20',13=>'20',14=>'20',15=>'20');
$section_centered = array(0=>'0',1=>'0',2=>'0',3=>'1',4=>'0',5=>'0',6=>'0',7=>'0',8=>'0',9=>'0',10=>'0',11=>'1',12=>'0',13=>'0',14=>'0',15=>'0');
$row_number = 1;
$sheet = ExcelFunctions::getExcelHeaders($sheet,$section_headers,$section_headers_leters,$section_headers_width,$row_number);
$sheet->setAutoFilter('A1:K1');
$row_number++;
$sheet = ExcelFunctions::getExcelData($sheet,$multireg_con,$section_headers,$section_headers_leters,$section_centered,$row_number,"1");

#Rename sheet
$sheet->setTitle('MultiReg concepts');

///MULTIREG PUBLICATIONS///
$p_sheet = $spreadsheet->createSheet(1);
$p_sheet->setTitle('MultiReg publications');

#SECTION HEADERS
$section_headers = array(0=>$settings['hub_name']."No",1=>"Title",2=>"Year published",3=>"Full Author List",4=>"Citation",5=>"Deadline PMCID",6=>"PMCID",7=>"NIH slide received (Y/N)",8=>"Paper filed? Y/N");
$section_headers_leters = array(0=>'A',1=>'B',2=>'C',3=>'D',4=>'E',5=>'F',6=>'G',7=>'H',8=>'I');
$section_headers_width = array(0=>'14',1=>'30',2=>'10',3=>'30',4=>'20',5=>'14',6=>'14',7=>'8',8=>'8');
$section_centered = array(0=>'0',1=>'0',2=>'1',3=>'0',4=>'0',5=>'0',6=>'0',7=>'1',8=>'1');
$row_number = 1;
$sheet = ExcelFunctions::getExcelHeaders($p_sheet,$section_headers,$section_headers_leters,$section_headers_width,$row_number);
$sheet->setAutoFilter('A1:G1');
$sheet->getRowDimension($row_number)->setRowHeight(40);
$row_number++;
$sheet = ExcelFunctions::getExcelData($p_sheet,$multireg_pub,$section_headers,$section_headers_leters,$section_centered,$row_number,"2");


///MULTIREG CONF ABSTRACTS///
$mca_sheet = $spreadsheet->createSheet(2);
$mca_sheet->setTitle('MultiReg conf abstracts');

#SECTION HEADERS
$section_headers = array(0=>$settings['hub_name']." No",1=>"Conference Acronym",2=>"Conference Year",3=>"Title",4=>"Full author list",5=>"Comments",6=>"Abstract filed in \"Conferences\" dropbox?");
$section_headers_leters = array(0=>'A',1=>'B',2=>'C',3=>'D',4=>'E',5=>'F',6=>'G');
$section_headers_width = array(0=>'14',1=>'15',2=>'10',3=>'30',4=>'40',5=>'14',6=>'14');
$section_centered = array(0=>'0',1=>'0',2=>'1',3=>'0',4=>'0',5=>'0',6=>'1');
$row_number = 1;
$sheet = ExcelFunctions::getExcelHeaders($mca_sheet,$section_headers,$section_headers_leters,$section_headers_width,$row_number);
$sheet->setAutoFilter('A1:F1');
$row_number++;
$sheet = ExcelFunctions::getExcelData($mca_sheet,$multireg_abs,$section_headers,$section_headers_leters,$section_centered,$row_number,"2");


$writer = new Xlsx($spreadsheet);

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="'.$filename.'"');
$writer->save("php://output");
?>