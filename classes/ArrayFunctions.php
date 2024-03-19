<?php
namespace Vanderbilt\HarmonistHubExternalModule;


class ArrayFunctions
{
    public static function array_filter_empty($array)
    {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $value = self::array_filter_empty($value);
            }
            if (is_array($value) && empty($value)) {
                unset($array[$key]);
            }
        }
        return $array;
    }

    public static function multi_array_diff($arr1, $arr2){
        $arrDiff = array();
        foreach($arr1 as $key => $val) {
            if(isset($arr2[$key])){
                if(is_array($val)){
                    $arrDiff[$key] = self::multi_array_diff($val, $arr2[$key]);
                }else{
                    if(in_array($val, $arr2)!= 1){
                        $arrDiff[$key] = $val;
                    }
                }
            }else if(isset($val)){
                $arrDiff[$key] = $val;
            }
        }
        return $arrDiff;
    }

    public static function array_sort_by_column(&$arr, $col, $dir = SORT_ASC) {
        $sort_col = array();
        foreach ($arr as $key=> $row) {
            $sort_col[$key] = $row[$col];
        }
        array_multisort($sort_col, $dir, $arr);
    }
}
?>