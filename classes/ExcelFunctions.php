<?php
namespace Vanderbilt\HarmonistHubExternalModule;


class ExcelFunctions
{
    function getExcelHeaders($sheet,$headers,$letters,$width,$row_number){
        foreach ($headers as $index=>$header) {
            $sheet->setCellValue($letters[$index] . $row_number, $header);
            $sheet->getStyle($letters[$index] . $row_number)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $sheet->getStyle($letters[$index].$row_number)->getFill()->getStartColor()->setARGB('4db8ff');
            $sheet->getStyle($letters[$index].$row_number)->getFont()->setBold( true );
            $sheet->getStyle($letters[$index].$row_number)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);
            $sheet->getStyle($letters[$index].$row_number)->getAlignment()->setHorizontal('center');
            $sheet->getStyle($letters[$index].$row_number)->getAlignment()->setWrapText(true);

            $sheet->getColumnDimension($letters[$index])->setAutoSize(false);
            $sheet->getColumnDimension($letters[$index])->setWidth($width[$index]);
        }
        return $sheet;
    }

    function getExcelData($sheet,$data_array,$headers,$letters,$section_centered,$row_number,$option){
        $found = false;
        $active_n_found = false;
        foreach ($data_array as $row => $data) {
            foreach ($headers as $index => $header) {
                $sheet->setCellValue($letters[$index].$row_number, $data[$index]);
                $sheet->getStyle($letters[$index].$row_number)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $sheet->getStyle($letters[$index].$row_number)->getAlignment()->setWrapText(true);
                if($section_centered[$index] == "1"){
                    $sheet->getStyle($letters[$index].$row_number)->getAlignment()->setHorizontal('center');
                }
                if($option == "1"){
                    if ($index == "11" && $data[$index] == "N") {
                        $active_n_found = true;
                        $sheet->getStyle($letters[$index] . $row_number)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                        $sheet->getStyle($letters[$index].$row_number)->getFill()->getStartColor()->setARGB('e6e6e6');
                    }
                }

                if ($option == "2" && $index == "2"){
                    $year = $data[$index];
                    if(array_key_exists(($row+1),$data_array) && $year != $data_array[$row+1][$index]) {
                        $found = true;
                    }
                }
            }
            if( $active_n_found && $option == '1'){
                foreach ($headers as $index=>$header) {
                    $sheet->getStyle($letters[$index] . $row_number)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                    $sheet->getStyle($letters[$index].$row_number)->getFill()->getStartColor()->setARGB('e6e6e6');
                }
                $active_n_found = false;
            }
            $row_number++;

            if($option == "2" && $found){
                foreach ($headers as $index=>$header) {
                    $sheet->setCellValue($letters[$index].$row_number,"");
                    $sheet->getStyle($letters[$index] . $row_number)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                    $sheet->getStyle($letters[$index] . $row_number)->getAlignment()->setWrapText(true);
                    $sheet->getStyle($letters[$index] . $row_number)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                    $sheet->getStyle($letters[$index] . $row_number)->getFill()->getStartColor()->setARGB('ffffcc');
                }
                $row_number++;
                $found = false;
            }
        }
        return $sheet;
    }
}