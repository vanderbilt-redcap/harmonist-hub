<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include_once(__DIR__ . "/REDCapManagement.php");
include_once(__DIR__ . "/../simplediff-modified/simplediff.php");

class HubUpdates{
    const CHANGED = 'changed';
    const ADDED = 'added';
    const REMOVED = 'removed';

    public static function compareDataDictionary($module, $pidsArray)
    {
        $allItems = array();
        $constants_array = REDCapManagement::getProjectsContantsArray();
//        $constants_array = array(0=>"RMANAGER");
        foreach ($constants_array as $constant){
            $path = $module->framework->getModulePath()."csv/".$constant.".csv";
            $old = \REDCap::getDataDictionary($pidsArray[$constant], 'array', false);
            $new = $module->dataDictionaryCSVToMetadataArray($path);

            #TODO: ADD table changes on new SQL
            $removed = array_diff_key($old, $new);
            $added = array_diff_key($new, $old);

            $possiblyChanged = array_intersect_key($new, $old);
            $changed = array();
            foreach ($possiblyChanged as $key => $value) {
                if ($old[$key] != $value) {
                    $hasValueChanged = false;
                    foreach ($value as $fieldType => $dataValue) {
                        if (trim($dataValue) != trim($old[$key][$fieldType])) {
                            //check if they have enetered the choices with a space between the '|' separator
                            if($fieldType == "select_choices_or_calculations" && strtolower($value['field_type']) != "sql"){
                                $choicesOld = self::parseArray($old[$key][$fieldType]);
                                $choices = self::parseArray($value[$fieldType]);
                                $possiblyChangedChoicesValues = array_diff($choicesOld, $choices);
                                $possiblyChangedChoicesKey = array_diff_key($choicesOld, $choices);

                                if(!empty($possiblyChangedChoicesValues) && !empty($possiblyChangedChoicesKey)){
                                    $hasValueChanged = true;
                                }

                            }else if($fieldType == "select_choices_or_calculations" && strtolower($value['field_type']) == "sql") {
//                                print_array($key);
//                                print_array("OLD: ".$old[$key][$fieldType]);
//                                print_array("NEW: ".$possiblyChanged[$key][$fieldType]);
                                $sql['sql'] = $possiblyChanged[$key][$fieldType];
                                $sql['changed'] = false;
                                $sql = self::changeSQLDataTable($old[$key][$fieldType], $sql);
                                $sql = self::compareSQL($old[$key][$fieldType], $sql);
                                if($sql['changed']){
//                                    print_array("CHANGED: ".$sql['sql']);
                                    $hasValueChanged = true;
                                    $value[$fieldType] = $sql['sql'];
                                }

                            }else{
                                $hasValueChanged = true;
                            }
                        }
                    }
                    if($hasValueChanged){
                        $changed[$key] = $value;
                    }
                }
            }
            $result = array();
            $result = self::custom_array_merge($module, $constant, $result, $changed, self::CHANGED);
            $result = self::custom_array_merge($module, $constant, $result, $added, self::ADDED);
            $result = self::custom_array_merge($module, $constant, $result, $removed, self::REMOVED);
            if(!empty($result)){
                $allItems[$constant] = $result;
            }
        };

        return $allItems;
    }

    public static function compareSQL($sqlOld, $sqlNew)
    {
        foreach (['/project_id\s=\s(\d+)/','/project_id=(\d+)/','/\[data-table:(.*?)\]/'] as $pattern) {
            preg_match_all($pattern, $sqlOld, $matchOld);
            preg_match_all($pattern, $sqlNew['sql'], $matchNew);
            //Change pids in Admins SQL (NEW) to match the old and check for changes again
            foreach ($matchOld[0] as $index => $slqPid) {
                $pattern_replace = "/" . $matchNew[0][$index] . "/";
                if ($pattern == '/\[data-table:(.*?)\]/') {
                    $pattern_replace = "/\[data-table:" . $matchNew[1][$index] . "\]/";
                }
                $sqlNew['sql'] = preg_replace($pattern_replace, $slqPid, $sqlNew['sql'], 1);
            }
        }
        //Compare if the newly changed SQL is the same as the old if not return sql as it has changed
        if($sqlNew['sql'] != $sqlOld){
            $sqlNew['changed'] = true;
        }
        return $sqlNew;
    }

    public static function changeSQLDataTable($sqlOld, $sqlNew)
    {
        if(str_contains($sqlOld, 'redcap_data')){
            $sql_redcap_data = $sqlOld;
        }else if(str_contains($sqlNew['sql'], 'redcap_data')){
            $sql_redcap_data = $sqlNew['sql'];
        }

        while (($lastPos = strpos($sql_redcap_data, 'redcap_data', $lastPos))!== false) {
            $positions[] = $lastPos;
            $lastPos = $lastPos + strlen('redcap_data');
        }
        $redcap_data_ocurrences = substr_count($sql_redcap_data, 'redcap_data');
        for($i = 0 ; $i < $redcap_data_ocurrences; $i++){
            $rest = substr($sql_redcap_data, $positions[$i], strlen($sql_redcap_data));
            $pos_pid_1 = (strpos($rest, 'project_id=')) ?? null;
            $pos_pid_2 = (strpos($rest, 'project_id = ')) ?? null;
            $table_replace = "";
            if($pos_pid_1 != null && $pos_pid_2 != null){
                if($pos_pid_1 <= $pos_pid_2 && $pos_pid_1 != null){
                    preg_match_all('/project_id=(\d+)/', $rest, $matchNew);
                    $slqPid = $matchNew[1][0];
                    $table_replace = "[data-table:" . $slqPid . "]";
                }else{
                    preg_match_all('/project_id\s=\s(\d+)/', $rest, $matchNew);
                    $slqPid = $matchNew[1][0];
                    $table_replace = "[data-table:" . $slqPid . "]";
                }
            }else if($pos_pid_1 != null){
                preg_match_all('/project_id=(\d+)/', $rest, $matchNew);
                $slqPid = $matchNew[1][0];
                $table_replace = "[data-table:" . $slqPid . "]";
            }else if($pos_pid_2 != null){
                preg_match_all('/project_id\s=\s(\d+)/', $rest, $matchNew);
                $slqPid = $matchNew[1][0];
                $table_replace = "[data-table:" . $slqPid . "]";
            }
            if($table_replace != "") {
                if(str_contains($sql_redcap_data, 'redcap_data')){
                    $sql_redcap_data = preg_replace("/redcap_data/", $table_replace, $sql_redcap_data, 1);
                }
            }
        }

        if($sql_redcap_data != $sqlOld){
            if(str_contains($sqlOld, 'redcap_data')){
                $sqlNew['changed'] = true;
            }
            $sqlNew['sql'] = $sql_redcap_data;
        }

//        if($sql_redcap_data != $sqlOld){
//            print_array("FOUND: ".$sql_redcap_data);
//            $sqlNew['changed'] = true;
//            $sqlNew['sql'] = $sql_redcap_data;
//        }
        return $sqlNew;
    }

    public static function updateDataDictionary($module, $pidsArray, $checked_values)
    {
        $hub_updates_list = explode(",", $checked_values);
        $update_list = [];
        foreach ($hub_updates_list as $updates) {
            $hub_updates = explode("-", $updates);
            if (!array_key_exists($hub_updates[0], $update_list)) {
                $update_list[$hub_updates[0]] = [];
            }
            if (!array_key_exists($hub_updates[2], $update_list[$hub_updates[0]])) {
                $update_list[$hub_updates[0]][$hub_updates[2]] = [];
            }
            array_push($update_list[$hub_updates[0]][$hub_updates[2]], $hub_updates[1]);
        }

        $constants_array = REDCapManagement::getProjectsContantsArray();
        foreach ($constants_array as $constant) {
            if(array_key_exists($constant, $update_list)) {
                $path = $module->framework->getModulePath() . "csv/" . $constant . ".csv";
                $old = \REDCap::getDataDictionary($pidsArray[$constant], 'array', false);
                $new = $module->dataDictionaryCSVToMetadataArray($path);

                self::saveFieldData($update_list[$constant], $old, $new, $pidsArray[$constant]);
            }
        }
    }

    public static function saveFieldData($update_list, $old, $new, $project_id)
    {
        $save_data = $old;
        foreach ($update_list as $status => $statusData) {
            foreach ($statusData as $index => $variable) {
                if ($status == self::CHANGED) {
                    $save_data[$variable] = $new[$variable];
                } else if ($status == self::ADDED) {
                    $next_field_name = self::getNextFieldName($variable, $new, $old);
                    $save_data_aux = [];
                    foreach($save_data as $varname => $value){
                        if($varname == $next_field_name){
                            $save_data_aux[$variable] = $new[$variable];
                        }
                        $save_data_aux[$varname] = $save_data[$varname];
                    }
                    $save_data = $save_data_aux;
                } else if ($status == self::REMOVED) {
                    unset($save_data[$variable]);
                }
            }
        }
        $save_data = \MetaData::convertFlatMetadataToDDarray($save_data);
        $sql_errors = \MetaData::save_metadata($save_data, false, false, $project_id);
    }

    public static function getNextFieldName($variable, $new, $old)
    {
        $new_var_list = array_keys($new);
        $new_var_list_index = array_search($variable,$new_var_list);
        $next_field_name = '';
        for($i = $new_var_list_index; $i <= count($new_var_list); $i++){
            if(array_key_exists($new_var_list[$i],$new) && $variable != $new_var_list[$i] && isset($old[$new_var_list[$i]])){
                $next_field_name = $new_var_list[$i];
                break;
            }
        }
        return $next_field_name;
    }

    public static function getResolvedList($module, $status='')
    {
        $hub_updates_resolved_list = $module->getProjectSetting('hub-updates-resolved-list');
        $hub_updates_resolved_list = explode(",",$hub_updates_resolved_list);
        $resolved_list = [];
        foreach ($hub_updates_resolved_list as $resolved) {
            if(!empty($resolved)) {
                $hub_updates_resolved = explode("-", $resolved);
                if (!array_key_exists($hub_updates_resolved[0], $resolved_list)) {
                    $resolved_list[$hub_updates_resolved[0]] = [];
                }
                if($status == 'resolved'){
                    $aux = ['field_name' => $hub_updates_resolved[1], 'field_status' => $hub_updates_resolved[2], 'field_type' => $hub_updates_resolved[3]];
                }else{
                    $aux = ['field_name' => $hub_updates_resolved[1], 'field_type' => $hub_updates_resolved[2]];
                }
                array_push($resolved_list[$hub_updates_resolved[0]], $aux);
            }
        }
        return $resolved_list;
    }

    public static function parseArray($choices)
    {
        $array_to_fill = array();

        $select_choices = $choices;
        $select_array = explode("|", $select_choices);
        foreach ($select_array as $key => $val) {
            $new_choices = explode(",",$val);
            $array_to_fill[trim($new_choices[0])] = trim($new_choices[1]);
        }

        return $array_to_fill;
    }

    public static function custom_array_merge($module, $constant, $result, $data, $type)
    {
        if(!empty($data)) {
            $resolved_list = self::getResolvedList($module);

            $is_empty = true;
            foreach ($data as $key => $value) {
                $resolved_found = false;
                foreach ($resolved_list[$constant] as $key_resolved => $value_resolved) {
                    if($value_resolved['field_name'] == $key){
                        $resolved_found = true;
                    }
                }
                #if it's in the resolved list, do not show as an update
                if(!$resolved_found){
                    $is_empty = false;
                    $result[$value['form_name']][$type][$key] = $value;
                }

            }
            #make sure we have values to save before adding the total legend
            if(!$is_empty){
                if(!array_key_exists('TOTAL',$result))
                    $result["TOTAL"] = array();
                $result["TOTAL"][$type] = count($data);
                $result["TOTAL"]["total"] += $result["TOTAL"][$type];
            }

            array_merge($result);
        }
        return $result;
    }

    public static function getIcon($status)
    {
        $icon = "fa-pencil-alt";
        $color = "";
        if($status == self::CHANGED){
            $icon = "fa-pencil-alt";
        }else if($status == self::ADDED){
            $icon = "fa-plus";
        }else if($status == self::REMOVED){
            $icon = "fa-minus";
            $color = "style='color:#fff'";
        }

        $icon_legend = '<a href="#" data-toggle="tooltip" title="'.$status.'" data-placement="top" class="custom-tooltip" style="vertical-align: -2px;"><span class="label '.$status.'" title="'.$status.'"><i class="fas '.$icon.'" aria-hidden="true"></i></span></a>';
        return $icon_legend;
    }

    public static function getFieldName($new, $old, $status, $var){

       if($status == self::CHANGED) {
           if ($new[$var] !== $old[$var]) {
               $color = "class='mb-2 bg-warning';";

               $col = "<div $color id='bg-warning'>" . $new[$var] . "</div><div class='text-muted' style=' text-decoration: line-through;'>" . $old[$var] . "</div>";
           } else {
               $col = "<div class='mb-2'>" . $new[$var] . "</div>";
           }
           $col .= self::getFieldLabel($new, $old, self::CHANGED,'Show the field ONLY if: ','branching_logic');
       }else {
           $col = $new['field_name'];
           if ($new['branching_logic'] != "") {
               $col .= "<small class='d-flex' style='font-size:12px;'>Show the field ONLY if: " . $new['branching_logic'] . "</small>";
           }
       }
        return $col;

    }
    public static function getFieldLabel($new, $old, $status, $string, $var){

        if($status == self::CHANGED) {
            $col = "";
            if ($new[$var] !== $old[$var]) {
                if ($old == "") {
                    $color = "class='mb-2 text-light p-1' style='background-color:#5d9451; font-size:12px;';";
                    $col .= "<div $color>$string " . $new[$var] . "</div>";
                } else if ($new[$var] == "") {
                    $color = "class='mb-2 p-1 bg-warning' style='font-size:12px;';";
                    $col .= "<small class='mb-2 d-flex text-light p-1' style='background-color:#cb410b; font-size:12px; text-decoration:line-through;'>$string" . $old[$var] . "</small>";
                } else {
                    $color = "class='mb-2 bg-warning p-1' style='font-size:12px;';";
                    $col .= "<div $color>$string " . $new[$var] . "</div>";
                    $col .= "<small class='mb-2 p-1 d-flex' style='font-size:12px; text-decoration:line-through;'>$string " . $old[$var] . "</small>";
                }
            } else if ($old[$var] != "") {
                $col .= "<small class='d-flex mb-2'><div><i class='text-muted'>$string </i><i class='text-info'> " . $old[$var] . "</i></div></small>";
            }
        }else{
            $col = "";

            if ($new['section_header'] != "") {
                $col .= "<div class='mb-2' style='font-size:12px;'>Section Header: " . $new['section_header'] . "</div>";
            }

            $col .= $new['field_label'];

            if ($new['field_note'] != "") {
                $col .= "<small class='d-flex'>Field Note: " . $new['field_note'] . "</small>";
            }
        }


        return $col;

    }
    public static function getFieldAttributes($value){
        $col = "";
        global $lang;
        $choices = self::parseArray($value['select_choices_or_calculations']);
        $col .= $value['field_type'];

        if ($value['text_validation_type_or_show_slider_number'] != "") {
            if ($value['text_validation_type_or_show_slider_number'] == 'int') $value['text_validation_type_or_show_slider_number'] = 'integer';
            elseif ($value['text_validation_type_or_show_slider_number'] == 'float') $value['text_validation_type_or_show_slider_number'] = 'number';
            elseif (in_array($value['text_validation_type_or_show_slider_number'], array('date', 'datetime', 'datetime_seconds'))) $value['text_validation_type_or_show_slider_number'] .= '_ymd';
            $col .= " (" . $value['text_validation_type_or_show_slider_number'];
            if ($value['text_validation_min'] != "") {
                $col .= ", Min:" . $value['text_validation_min'];
            }
            if ($value['text_validation_max'] != "") {
                $col .= ", Max: " . $value['text_validation_max'];
            }

            $col .= ")";
        }

        if ($value['required_field'] == 'y') {
            $col .= ", Required";
        }

        if ($value['identifier'] == 'y') {
            $col .= ", Identifier";
        }

        if ($value['field_annotation'] != "") {
            $col .= "<br /> Field Annotation: " . $value['field_annotation'];
        }

        if ($value['select_choices_or_calculations'] != "" && $value['field_type'] != "descriptive") {
            if ($value['field_type'] == 'slider') {
                $col .= "<br />{$lang['design_488']} " . implode(", ", \Form::parseSliderLabels($value['select_choices_or_calculations']));
            } elseif ($value['field_type'] == 'calc') {
                $col .= '<table>';
                $col .= '<tr>';
                $col .= '<th> Calculation </th>';
                $col .= '</tr>';
                $col .= '<tr>';
                $col .= '<td>' . $value['select_choices_or_calculations'] . '</td>';
                $col .= '</tr>';
                $col .= '</table>';
            } elseif ($value['field_type'] == 'sql') {
                $col .= '<table border="0" cellpadding="2" cellspacing="0" class="ReportTableWithBorder"><tr><td>' . $value['select_choices_or_calculations'] . '</td></tr></table>';
            } else {
                $col .= '<table border="0" cellpadding="2" cellspacing="0" class="ReportTableWithBorder">';
                foreach ($choices as $val => $label) {
                    $col .= '<tr valign="top">';
                    if ($value['field_type'] == 'checkbox') {
                        $col .= '<td>' . $val . '</td>';
                    } else {
                        $col .= '<td>' . $val . '</td>';
                    }

                    $col .= '<td>' . $label . '</td>';
                    $col .= '</tr>';
                }
                $col .= '</table>';
            }
        }

        return $col;
    }

    public static function getFieldAttributesChanged($new, $old)
    {

        $col = "";

        $choices = self::parseArray($new['select_choices_or_calculations']);
        $oldChoices = self::parseArray($old['select_choices_or_calculations']);

        if ($new['field_type'] == 'select') $new['field_type'] = 'dropdown';
        elseif ($new['field_type'] == 'textarea') $new['field_type'] = 'notes';

        if($new['field_type'] !== $old['field_type']){
            if($old['field_type'] == ""){
                $color = "class='mb-2 text-light p-1 d-inline-block' style='background-color:#5d9451; font-size:12px;';";
                $col .= "<div $color> " . $new['field_type'] . "</div>";
            }else if($new['field_type'] == ""){
                $color = "class='mb-2 d-inline-block text-light p-1' style='background-color:#cb410b; font-size:12px; text-decoration:line-through;';";
                $col .= "<small $color>" . $old['field_type'] . "</small>";
            }else{
                $color = "class='mb-2 bg-warning p-1 d-inline-block' style='font-size:12px;';";
                $col .= "<div $color>" . $new['field_type'] . "</div>";
                $col .= "<small class='mb-2 p-1 d-inline-block' style='font-size:12px; text-decoration:line-through;'>" . $old['field_type'] . "</small>";
            }
        } else if ($old['field_type'] != "") {
            $col .= "<div class='d-inline-block mr-1 mb-2'>" . $old['field_type'] . "</div>";
        }

        if($new['text_validation_type_or_show_slider_number'] !== $old['text_validation_type_or_show_slider_number']){
            if($old['text_validation_type_or_show_slider_number'] == ""){
                //New item
                $color = "class='mb-2 text-light p-1 d-inline-block' style='background-color:#5d9451; font-size:12px;';";
                $col .= "<div $color> (" . $new['text_validation_type_or_show_slider_number'];
                if ($new['text_validation_min'] != "") {
                    $col .= ", Min:" . $new['text_validation_max'];
                }
                if ($new['text_validation_min'] != "") {
                    $col .= ", Max: " . $new['text_validation_max'];
                }
                $col .= ") </div>";
            }elseif($new['text_validation_type_or_show_slider_number'] == ""){
                //removed
                $color = "class='mb-2 d-inline-block text-light p-1' style='background-color:#cb410b; font-size:12px; text-decoration:line-through;';";
                $col .= "<div $color> (" . $old['text_validation_type_or_show_slider_number'];
                if ($old['text_validation_min'] != "") {
                    $col .= ", Min:" . $old['text_validation_max'];
                }
                if ($old['text_validation_min'] != "") {
                    $col .= ", Max: " . $old['text_validation_max'];
                }
                $col .= ") </div>";
            }else{
                $color = "class='mb-2 bg-warning p-1 d-inline-block' style='font-size:12px;';";
                $col .= "<div $color> (" . $new['text_validation_type_or_show_slider_number'];
                if ($new['text_validation_min'] != "") {
                    $col .= ", Min:" . $new['text_validation_max'];
                }
                if ($new['text_validation_min'] != "") {
                    $col .= ", Max: " . $new['text_validation_max'];
                }
                $col .= ") </div>";

                $col .= "<div class='ml-1 d-inline-block' style='font-size:12px; text-decoration:line-through;'> (" . $old['text_validation_type_or_show_slider_number'];
                if ($old['text_validation_min'] != "") {
                    $col .= ", Min:" . $old['text_validation_max'];
                }
                if ($old['text_validation_min'] != "") {
                    $col .= ", Max: " . $old['text_validation_max'];
                }
                $col .= ") </div>";
            }

        } else if ($old['text_validation_type_or_show_slider_number'] != "") {
            $color = 'class="d-inline-block mr-1 mb-2" style="font-size:12px;"';
            $col .= "<div $color> (" . $old['text_validation_type_or_show_slider_number'];
            if ($old['text_validation_min'] != "") {
                $col .= ", Min:" . $old['text_validation_max'];
            }
            if ($old['text_validation_min'] != "") {
                $col .= ", Max: " . $old['text_validation_max'];
            }
            $col .= ") </div>";
        }

        if($new['required_field'] !== $old['required_field']){
            if($old['required_field'] == ""){
                $color = "class='mb-2 text-light p-1 ml-2 d-inline-block' style='background-color:#5d9451; font-size:12px;';";
                $col .= "<small $color> Required </small>";
            }elseif($new['required_field'] == ""){
                $color = "class='mb-2 ml-2 d-inline-block text-light p-1' style='background-color:#cb410b; font-size:12px; text-decoration:line-through;';";
                $col .= "<small $color> Required </small>";
            }
        }else if($old['required_field'] != ""){
            $color = 'class="d-inline-block mr-1 mb-2" style="font-size:12px;"';
            $col .= "<small $color> Required</small>";
        }

        if ($new['identifier'] !== $old['identifier']) {
            if ($old['identifier'] == "") {
                $color = "class='mb-2 text-light p-1 ml-2 d-inline-block' style='background-color:#5d9451; font-size:12px;';";
                $col .= "<small $color> Identifier </small>";
            } elseif ($new['identifier'] == "") {
                $color = "class='mb-2 ml-2 d-inline-block text-light p-1' style='background-color:#cb410b; font-size:12px; text-decoration:line-through;';";
                $col .= "<small $color> Identifier </small>";
            }
        } else if ($old['identifier'] != "") {
            $color = 'class="d-inline-block mr-1 mb-2" style="font-size:12px;"';
            $col .= "<small $color> Identifier</small>";
        }

        if($new['field_annotation'] !== $old['field_annotation']){
            if($old['field_annotation'] == ""){
                $color = "class='mb-2 text-light p-1 d-block' style='background-color:#5d9451; font-size:12px;';";
                $col .= "<small $color>Field Annotation: " . $new['field_annotation'] . "</small>";
            }elseif($new['field_annotation'] == ""){
                $color = "class='mb-2 d-block text-light p-1' style='background-color:#cb410b; font-size:12px; text-decoration:line-through;';";
                $col .= "<small $color>Field Annotation: " . $old['field_annotation'] . "</small>";
            }else{
                $color = "class='mb-2 bg-warning p-1 d-inline-block' style='font-size:12px;';";
                $col .= "<div $color>" . $new['field_annotation'] . "</div>";
                $col .= "<small class='mb-2 p-1 d-inline-block' style='font-size:12px; text-decoration:line-through;'>" . $old['field_annotation'] . "</small>";
            }
        }else if($old['field_annotation'] != ""){
            $col .= "<div class='d-inline-block mr-1 mb-2'>" . $old['field_annotation'] . "</div>";
        }

        if($new['select_choices_or_calculations'] !== $old['select_choices_or_calculations']){
            if($new['field_type'] == 'calc'){
                $col .= '<table>';
                $col .= '<tr>';
                $col .= '<th> Calculation </th>';
                $col .= '</tr>';
                $col .= '<tr>';
                $col .= "<td class='bg-warning'>" . $new['select_choices_or_calculations'] . "</td>";
                $col .= '</tr>';
                $col .= '<tr>';
                $col .= "<td style='background-color:#cb410b; text-decoration:line-through;'>" . $old['select_choices_or_calculations'] . "</td>";
                $col .= '</tr>';
                $col .= '</table>';
            } elseif ($new['field_type'] == 'sql') {
                $sql_different = printSQLDifferences($old['select_choices_or_calculations'], $new['select_choices_or_calculations']);
                $col .= '<table border="0" cellpadding="2" cellspacing="0" class="ReportTableWithBorder">'.
                    '<tr><td>' . $sql_different['new'] . '</td></tr>'.
                    '<tr><td style="text-decoration: line-through;">' . $sql_different['old'] . '</td></tr>'.
                    '</table>';
            } else {
                $col .= '<table border="0" cellpadding="2" cellspacing="0" class="ReportTableWithBorder">';
                foreach ($choices as $val => $label) {
                    $col .= '<tr valign="top">';
                    $oldValue = $oldChoices[$val];
                    if('field_type' == 'checkbox'){
                        $col .= '<td>' . $val . '</td>';
                        $col .= '<td>' . $new['field_type'] . '</td>';
                    }elseif($label !== $oldValue){
                        if($oldValue == ""){
                            $col .= "<td class='text-light' style='background-color:#5d9451;'>" . $val ."</td>";
                            $col .= "<td class='text-light' style='background-color:#5d9451;'>" . $label . "</td>";
                        }elseif($label == ""){
                            $col .= "<td class='text-light' style='background-color:#cb410b; text-decoration:line-through;'>" . $val . "</td>";
                            $col .= "<td class='text-light' style='background-color:#cb410b; text-decoration:line-through;'>" . $oldValue . "</td>";
                        }elseif($label !== $oldValue){
                            $col .= "<td class='bg-warning'>" . $val . "</td>";
                            $col .= "<td class='bg-warning'>" . $label . "</td>";
                            $col .= "<td class='text-light' style='background-color:#cb410b; text-decoration: line-through;'>" . $oldValue . "</td>";
                        }
                    } else {
                        $col .= "<td>" . $val . "</td>";
                        $col .= "<td>" . $label . "</td>";
                    }

                }
                $col .= '</table>';
            }
        }elseif($old['select_choices_or_calculations'] != ""){
            if($old['field_type'] == 'calc'){
                $col .= '<table>';
                $col .= '<tr>';
                $col .= '<th> Calculation </th>';
                $col .= '</tr>';
                $col .= '<tr>';
                $col .= "<td>" . $old['select_choices_or_calculations'] . "</td>";
                $col .= '</tr>';
                $col .= '</table>';
            }elseif($old['field_type'] == 'sql'){
                $col .= '<table border="0" cellpadding="2" cellspacing="0" class="ReportTableWithBorder"><tr><td>' . $old['select_choices_or_calculations'] . '</td></tr></table>';
            }else{
                $col .= '<table border="0" cellpadding="2" cellspacing="0" class="ReportTableWithBorder">';
                foreach ($oldChoices as $val => $label) {
                    $col .= '<tr valign="top">';
                    if ($old['field_type'] == 'checkbox' && $old['select_choices_or_calculations'] != $new['select_choices_or_calculations']) {
                        $col .= '<td>' . $val . '</td>';
                        $col .= '<td>'.$label . $old['field_type'] . '</td>';
                    } else {
                        $col .= "<td>" . $val . "</td>";
                        $col .= "<td>" . $label . "</td>";
                    }
                }
                $col .= '</table>';
            }
        }
        return $col;
    }
}
?>