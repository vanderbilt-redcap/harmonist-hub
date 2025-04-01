<?php
namespace Vanderbilt\HarmonistHubExternalModule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
include_once(__DIR__ ."/../projects.php");

$excel_data = array();

$params = [
    'project_id' => $pidsArray['DATAUPLOAD'],
    'return_format' => 'json-array'
];
$dataUpload_sevenDaysYoung = \REDCap::getData($params);
$params = [
    'project_id' => $pidsArray['DATADOWNLOAD'],
    'return_format' => 'json-array'
];
$dataDownload_sevenDaysYoung = \REDCap::getData($params);
$all_data_recent_activity = array_merge($dataUpload_sevenDaysYoung, $dataDownload_sevenDaysYoung);
ArrayFunctions::array_sort_by_column($all_data_recent_activity, 'responsecomplete_ts',SORT_DESC);

foreach ($all_data_recent_activity as $recent_activity) {
    $regionType = "";
    if (array_key_exists('download_id', $recent_activity) && $recent_activity['download_id'] != "") {
        $regionType = $recent_activity['downloader_region'];
    }else if(array_key_exists('data_upload_region', $recent_activity)){
        $regionType = $recent_activity['data_upload_region'];
    }
    $comment_time ="";
    if(!empty($recent_activity['responsecomplete_ts'])){
        $dateComment = new \DateTime($recent_activity['responsecomplete_ts']);
        $dateComment->modify("+1 hours");
        $comment_time = $dateComment->format("Y-m-d H:i:s");
    }
    $conceptId = "";
    if (array_key_exists('download_id', $recent_activity) && $recent_activity['download_id'] != "") {
        $conceptId = $recent_activity['downloader_assoc_concept'];
    } else if(array_key_exists('data_assoc_concept', $recent_activity)){
        $conceptId = $recent_activity['data_assoc_concept'];
    }
    $params = [
        'project_id' => $pidsArray['HARMONIST'],
        'return_format' => 'json-array',
        'records' => [$conceptId]
    ];
    $conceptData = \REDCap::getData($params);
    $assoc_concept = "";
    $concept_title = "";
    $contact_link = "";
    if(!empty($conceptData) && array_key_exists('0', $conceptData) && !empty($conceptData[0])){
        $concept_title = $conceptData[0]['concept_title'];
        $contact_link = $conceptData[0]['contact_link'];
        $assoc_concept = $conceptData[0]['concept_id'];
    }

    $person_proposed = "";
    if(!empty($contact_link)){
        $params = [
            'project_id' => $pidsArray['PEOPLE'],
            'return_format' => 'json-array',
            'records' => [$contact_link],
            'fields' => ['firstname','lastname']
        ];
        $people_proposed = \REDCap::getData($params)[0];
        $person_proposed = trim($people_proposed['firstname'] . ' ' . $people_proposed['lastname']);
    }
    if (array_key_exists('download_id', $recent_activity) && $recent_activity['download_id'] != "") {
        #DOWNLOADS
        $activity = 'download';
        $filename = $recent_activity['download_files'];
        $params = [
            'project_id' => $pidsArray['DATAUPLOAD'],
            'return_format' => 'json-array',
            'records' => [$recent_activity['download_id']],
            'fields' => ['data_assoc_request']
        ];
        $dataRequestData = \REDCap::getData($params)[0]['data_assoc_request'];
        $data_request = "";
        if(is_array($dataRequestData) && array_key_exists('0', $dataRequestData) && is_array($dataRequestData[0]) && array_key_exists('data_assoc_request', $dataRequestData[0])){
            $data_request = \REDCap::getData($params)[0]['data_assoc_request'];
        }
        $personType = $recent_activity['downloader_id'];
    } else {
        #UPLOADS
        $activity = 'upload';
        $filename = '';
        if (array_key_exists('data_upload_zip', $recent_activity) && $recent_activity['data_upload_zip'] != "") {
            $filename = $recent_activity['data_upload_zip'];
        }
        $data_request = '';
        if (array_key_exists('data_assoc_request', $recent_activity) && $recent_activity['data_assoc_request'] != "") {
            $data_request = $recent_activity['data_assoc_request'];
        }
        $personType = '';
        if (array_key_exists('data_upload_person', $recent_activity) && $recent_activity['data_upload_person'] != "") {
            $personType = $recent_activity['data_upload_person'];
        }
    }
    $params = [
        'project_id' => $pidsArray['PEOPLE'],
        'return_format' => 'json-array',
        'records' => [$personType],
        'fields' => ['firstname','lastname', 'person_region']
    ];
    $peopleData = \REDCap::getData($params);
    $personName = "";
    $name = "";
    if(!empty($peopleData) && array_key_exists('0', $peopleData) && !empty($peopleData[0])){
        $personName = trim($peopleData[0]['firstname'] . ' ' . $peopleData[0]['lastname']);
        $personRegion = $peopleData[0]['person_region'];

        $params = [
            'project_id' => $pidsArray['REGIONS'],
            'return_format' => 'json-array',
            'records' => [$personRegion],
            'fields' => ['region_code']
        ];
        $regionCodeData = \REDCap::getData($params);
        $region_code_person = "";
        if(!empty($regionCodeData) && array_key_exists('0', $regionCodeData) && array_key_exists('region_code', $regionCodeData[0])){
            $region_code_person = $regionCodeData[0]['region_code'];
        }
        $name = $personName . " (" . $region_code_person . ")";
    }
    $person_proposed = $personName;

    $params = [
        'project_id' => $pidsArray['REGIONS'],
        'return_format' => 'json-array',
        'records' => [$regionType],
        'fields' => ['region_code']
    ];
    $regionData = \REDCap::getData($params);
    $region_code = "";
    if(!empty($regionData) && array_key_exists('0', $regionData) && array_key_exists('region_code', $regionData[0])){
        $region_code = $regionData[0]['region_code'];
    }

    $params = [
        'project_id' => $pidsArray['SOP'],
        'return_format' => 'json-array',
        'records' => [$data_request],
        'fields' => ['sop_due_d']
    ];
    $sopData = \REDCap::getData($params);

    $sop_due_d = "";
    if(!empty($sopData) && array_key_exists('0', $sopData) && array_key_exists('sop_due_d', $sopData[0])){
        $sop_due_d = $sopData[0]['sop_due_d'];
    }

    $aux = array(
        0 => $comment_time,
        1 => $activity,
        2 => $name,
        3 => $region_code,
        4 => $assoc_concept,
        5 => $filename,
        6 => $concept_title,
        7 => $person_proposed,
        8 => $sop_due_d
    );
    array_push($excel_data, $aux);

    if ($activity == "upload" && array_key_exists('deleted_y', $recent_activity) && $recent_activity['deleted_y'] == "1") {
        #DELETE
        $aux = array();
        $activity = "delete";
        if ($recent_activity['deletion_type'][0] == '1') {
            $name = "<em>Automatic</em>";
        } else if ($recent_activity['deletion_type'][0] == '2') {
            $params = [
                'project_id' => $pidsArray['PEOPLE'],
                'return_format' => 'json-array',
                'records' => [$recent_activity['deletion_hubuser']],
                'fields' => ['firstname','lastname','person_region']
            ];
            $peopleDelete = \REDCap::getData($params)[0];
            $params = [
                'project_id' => $pidsArray['REGIONS'],
                'return_format' => 'json-array',
                'records' => [$peopleDelete['person_region']],
                'fields' => ['region_code']
            ];
            $region_code_person = \REDCap::getData($params)[0]['region_code'];

            $name = trim($peopleDelete['firstname'] . ' ' . $peopleDelete['lastname']) . " (" . $region_code_person . ")";
        }

        $comment_time = "";
        if (!empty($recent_activity['deletion_ts'])) {
            $dateComment = new \DateTime($recent_activity['deletion_ts']);
            $dateComment->modify("+1 hours");
            $comment_time = $dateComment->format("Y-m-d H:i:s");
        }
        $aux = array(
            0 => $comment_time,
            1 => $activity,
            2 => $name,
            3 => $region_code,
            4 => $assoc_concept,
            5 => $recent_activity['data_assoc_request'],
            6 => $concept_title,
            7 => $person_proposed,
            8 => $sop_due_d
        );
        array_push($excel_data, $aux);
    }
}

#EXEL SHEET
$filename = $settings['hub_name']." Hub: Data Activity - " . date("Y-m-d_hi",time()) . ".xlsx";
$storedName = date("YmdsH") . "_pid" . $pidsArray['PROJECTS'] . "_" . getRandomIdentifier(6) . ".xlsx";

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
$section_headers = array(0=>"Date",1=>"Activity",2=>"Person",3=>"Data Region",4=>"MR",
    5=>"Filename",6=>"Concept Title",7 => "Concept Lead",8 => "Data Due Date");
$section_headers_leters = array(0=>'A',1=>'B',2=>'C',3=>'D',4=>'E',5=>'F',6=>'G',7=>'H',8=>'I');
$section_headers_width = array(0=>'20',1=>'10',2=>'25',3=>'10',4=>'10',5=>'40',6=>'60',7=>'25',8=>'20');
$section_centered = array(0=>'0',1=>'0',2=>'0',3=>'1',4=>'1',5=>'0',6=>'0',7=>'0',8=>'1');
$section_hyperlink = array(0=>'0',1=>'0',2=>'0',3=>'0',4=>'1',5=>'0',6=>'0',7=>'0',8=>'0');
$row_number = 1;
$sheet = ExcelFunctions::getExcelHeaders($sheet,$section_headers,$section_headers_leters,$section_headers_width,$row_number);
$sheet->setAutoFilter('A1:I1');
$row_number++;
$sheet = ExcelFunctions::getExcelData($sheet,$excel_data,$section_headers,$section_headers_leters,$section_centered,$row_number,"1",$section_hyperlink);

#Rename sheet
$sheet->setTitle('Data Activity Log');

$writer = new Xlsx($spreadsheet);

#SAVE FILE
ob_start();
$writer->save("php://output");
$fileData = ob_get_clean();

$filePath = EDOC_PATH . $storedName;
$tempFile = fopen(EDOC_PATH . $storedName, "wb");
fwrite($tempFile, $fileData);
$docId = \REDCap::storeFile($filePath, $pidsArray['SETTINGS'], $storedName);
fclose($tempFile);

//Add document DB ID to project
$json = json_encode(array(array('record_id' => 1, 'data_log_history_file' => $docId)));
$results = \Records::saveData(
    $pidsArray['SETTINGS'],
    'json',
    $json,
    'normal',
    'YMD',
    'flat',
    '',
    true,
    true,
    true,
    false,
    true,
    array(),
    true,
    false
);
