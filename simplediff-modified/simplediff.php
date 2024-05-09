<?php
namespace Vanderbilt\HarmonistHubExternalModule;

/*
    Paul's Simple Diff Algorithm v 0.1
    (C) Paul Butler 2007 <http://www.paulbutler.org/>
    May be used and distributed under the zlib/libpng license.

    This code is intended for learning purposes; it was written with short
    code taking priority over performance. It could be used in a practical
    application, but there are a few ways it could be optimized.

    Given two arrays, the function diff will return an array of the changes.
    I won't describe the format of the array, but it will be obvious
    if you use print_r() on the result of a diff on some test data.

    htmlDiff is a wrapper for the diff command, it takes two strings and
    returns the differences in HTML. The tags used are <ins> and <del>,
    which can easily be styled with CSS.
*/

function checkSQLDifferences($old, $new){
    $array_results = array();
    $maxlen = 0;
    foreach($old as $index_old => $sql_value_old){
        #find the indexes in new from the old value
        $index_new_result = array_keys($new, $sql_value_old);
        foreach($index_new_result as $index_new){
            $array_results[$index_old][$index_new] = isset($array_results[$index_old - 1][$index_new - 1]) ? $array_results[$index_old - 1][$index_new - 1] + 1 : 1;
            if ($array_results[$index_old][$index_new] > $maxlen) {
                $maxlen = $array_results[$index_old][$index_new];
                $maxlen_old = $index_old + 1 - $maxlen;
                $maxlen_new = $index_new + 1 - $maxlen;
            }
        }
    }
    if($maxlen == 0) return array(array('old' => $old, 'new' => $new));
    return array_merge(
        checkSQLDifferences(array_slice($old, 0, $maxlen_old), array_slice($new, 0, $maxlen_new)),
        array_slice($new, $maxlen_new, $maxlen),
        checkSQLDifferences(array_slice($old, $maxlen_old + $maxlen), array_slice($new, $maxlen_new + $maxlen)));
}

function printSQLDifferences($old, $new){
    //Split strings in arrays to find all differences
    $pattern = "/[\s]+/";
    $sql_checked = checkSQLDifferences(preg_split($pattern, $old), preg_split($pattern, $new));
    $print = [];
    foreach($sql_checked as $value){
        if(is_array($value)) {
            //There are differences
            $print['old'] .= (!empty($value['old']) ? "<mark style='background-color:#ffcccc'>" . implode(' ', $value['old']) . "</mark> " : '');
            $print['new'] .= (!empty($value['new']) ? "<mark style='background-color:#ffc107'>" . implode(' ', $value['new']) . "</mark> " : '');
        }else{
            $print['old'] .=  $value . ' ';
            $print['new'] .=  $value . ' ';
        }
    }
    return $print;
}

?>