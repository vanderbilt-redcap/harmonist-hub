<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include_once(__DIR__ . "/REDCapManagement.php");
include_once(__DIR__ . "/../simplediff-modified/simplediff.php");

class HubUpdates{
    const CHANGED = 'changed';
    const ADDED = 'added';
    const REMOVED = 'removed';

    public static function compareDataDictionary($module, $pidsArray, $option=''): array
    {
        $allItems = array();
        $constants_array = REDCapManagement::getProjectsConstantsArray();
        foreach ($constants_array as $constant){
            $path = $module->framework->getModulePath()."csv/".$constant.".csv";
            $old = \REDCap::getDataDictionary($pidsArray[$constant], 'array', false);
            $new = $module->dataDictionaryCSVToMetadataArray($path);
            $possiblyChanged = array();
            $removed = array();
            $added = array();
            $changed = array();

            if(is_array($old) && is_array($new)){
                $removed = array_diff_key($old, $new);
                $added = array_diff_key($new, $old);
                $possiblyChanged = array_intersect_key($new, $old);
            }else{
                error_log("IeDEA warning PID: ".$pidsArray[$constant]." constant: ".$constant);
            }

            if(!empty($added)) {
                foreach ($added as $key => $value) {
                    foreach ($value as $fieldType => $dataValue) {
                        if ($fieldType == "select_choices_or_calculations" && strtolower($value['field_type']) == "sql" && $value['select_choices_or_calculations'] != "") {
                            $sql['sql'] = $value[$fieldType];
                            $sql['changed'] = false;
                            $sql = self::changeSQLDataTable("", $sql);
                            if ($sql['changed']) {
                                $added[$key][$fieldType] = $sql['sql'];
                            }
                        }
                    }
                }
            }
            if(!empty($possiblyChanged)) {
                foreach ($possiblyChanged as $key => $value) {
                    if ($old[$key] != $value) {
                        $hasValueChanged = false;
                        $hasSQLChanged = false;
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
                                    $sql['sql'] = $possiblyChanged[$key][$fieldType];
                                    $sql['changed'] = false;
                                    $sql = self::changeSQLDataTable($old[$key][$fieldType], $sql);
                                    $sql = self::compareSQL($old[$key][$fieldType], $sql);
                                    if($sql['changed']){
                                        $hasValueChanged = true;
                                        $hasSQLChanged = true;
                                        $value[$fieldType] = $sql['sql'];
                                    }
                                }else{
                                    $hasValueChanged = true;
                                }
                            }
                        }
                        if($hasValueChanged){
                            if($old[$key]['field_type'] == 'sql' && !$hasSQLChanged){
                                #Add original SQL values as the new one has different PIDs and it detects them as changes
                                $value['select_choices_or_calculations'] = $old[$key]['select_choices_or_calculations'];
                            }
                            $changed[$key] = $value;
                        }
                    }
                }
                $result = array();
                $result = self::custom_array_merge($module, $constant, $result, $changed, self::CHANGED, $option);
                $result = self::custom_array_merge($module, $constant, $result, $added, self::ADDED, $option);
                $result = self::custom_array_merge($module, $constant, $result, $removed, self::REMOVED, $option);
                if(!empty($result)){
                    $allItems[$constant] = $result;
                }
            }
        }

        return $allItems;
    }

    public static function compareSQL($sqlOld, $sqlNew): array
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

    public static function changeSQLDataTable($sqlOld, $sqlNew): array
    {
        if(str_contains($sqlOld, 'redcap_data')){
            $sql_redcap_data = $sqlOld;
        }else if(str_contains($sqlNew['sql'], 'redcap_data')){
            $sql_redcap_data = $sqlNew['sql'];
        }else if(empty($sqlOld)){
            //A new SQL has been added, check if it needs to be readjusted
            $sql_redcap_data = $sqlNew['sql'];
        }
        $sql_redcap_data_compare = $sql_redcap_data;

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

        if($sql_redcap_data != $sql_redcap_data_compare){
            if(str_contains($sqlOld, 'redcap_data') || empty($sqlOld)){
                $sqlNew['changed'] = true;
            }
            $sqlNew['sql'] = $sql_redcap_data;
        }
        return $sqlNew;
    }

    public static function getListOfChanges($checked_values): array
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
        return $update_list;
    }

    public static function updateDataDictionary($module, $pidsArray, $checked_values): void
    {
        $update_list = self::getListOfChanges($checked_values);
        $constants_array = REDCapManagement::getProjectsConstantsArray();
        foreach ($constants_array as $constant) {
            if(array_key_exists($constant, $update_list)) {
                $path = $module->framework->getModulePath() . "csv/" . $constant . ".csv";
                $old = \REDCap::getDataDictionary($pidsArray[$constant], 'array', false);
                $new = $module->dataDictionaryCSVToMetadataArray($path);

                self::saveFieldData($module, $update_list[$constant], $old, $new, $pidsArray[$constant], $constant, $pidsArray[$constant]);
            }
        }
    }

    public static function saveFieldData($module, $update_list, $old, $new, $project_id, $constant, $project_id_map): void
    {
        $projects_array = REDCapManagement::getProjectsConstantsArray();
        $projects_array_repeatable = REDCapManagement::getProjectsRepeatableArray();
        $projects_array_surveys = REDCapManagement::getProjectsSurveysArray();
        $save_data = $old;
        foreach ($update_list as $status => $statusData) {
            foreach ($statusData as $index => $variable) {
                if ($status == self::CHANGED) {
                    if($new[$variable]['field_type'] == "sql"){
                        if($old[$variable]['field_type'] != "sql"){
                            $old[$variable]['select_choices_or_calculations'] = $new[$variable]['select_choices_or_calculations'];
                        }
                        //Update SQL with new redcap_data tables and pids SQL
                        $sql['sql'] = $new[$variable]['select_choices_or_calculations'];
                        $sql['changed'] = false;
                        $sql = self::changeSQLDataTable($old[$variable]['select_choices_or_calculations'], $sql);
                        $sql = self::compareSQL($old[$variable]['select_choices_or_calculations'], $sql);
                        if($sql['changed']){
                            $new[$variable]['select_choices_or_calculations'] = $sql['sql'];
                        }
                    }
                    $save_data[$variable] = $new[$variable];

                    #Log Data
                    $newChanges = json_encode(array_diff_assoc($new[$variable], $old[$variable]),JSON_PRETTY_PRINT);
                    $oldChanges = json_encode(array_diff_assoc($old[$variable], $new[$variable]),JSON_PRETTY_PRINT);
                    \REDCap::logEvent("Hub Updates: CHANGED [".$variable."] on ".$constant." (PID #".$project_id.")", "*OLD:\n".$oldChanges."\n\n*NEW:\n".$newChanges, null,null,null,$project_id_map);
                } else if ($status == self::ADDED) {
                    $next_field_name = self::getNextFieldName($variable, $new, $old);
                    $save_data_aux = [];
                    $data = "";
                    $var_found = false;
                    foreach($save_data as $varname => $value){
                        if($varname == $next_field_name){
                            $save_data_aux[$variable] = $new[$variable];
                            #Log Data
                            $data = json_encode($save_data_aux[$variable],JSON_PRETTY_PRINT);
                            $var_found = true;
                        }
                        $save_data_aux[$varname] = $save_data[$varname];
                    }
                    #If variable not found, its in a new instrument
                    if(!$var_found && is_array($new[$variable]) && !empty($new[$variable]) && empty($old[$variable])){
                        $save_data_aux[$variable] = $new[$variable];
                        #Log Data
                        $data = json_encode($save_data_aux[$variable],JSON_PRETTY_PRINT);

                        #Add Repeatable Instrument and Surveys if any
                        $index = array_search($constant, $projects_array);
                        REDCapManagement::addRepeatableInstrument($module, $projects_array_repeatable[$index], $project_id);
                        REDCapManagement::createSurveys($module, $projects_array_surveys, $index, $project_id);
                        \REDCap::logEvent("Hub Updates: ADDED New Istrument ".$save_data_aux[$variable]['form_name']."  on  ".$constant." (PID #".$project_id.")", $save_data_aux[$variable]['form_name'], null,null,null,$project_id_map);
                    }
                    $save_data = $save_data_aux;
                    \REDCap::logEvent("Hub Updates: ADDED [".$variable."]  on  ".$constant." (PID #".$project_id.")", $data, null,null,null,$project_id_map);
                } else if ($status == self::REMOVED) {
                    \REDCap::logEvent("Hub Updates: REMOVED [".$variable."]  on  ".$constant." (PID #".$project_id.")", json_encode($save_data[$variable],JSON_PRETTY_PRINT), null,null,null,$project_id_map);
                    unset($save_data[$variable]);
                }
            }
        }
        $save_data = \MetaData::convertFlatMetadataToDDarray($save_data);
        $sql_errors = \MetaData::save_metadata($save_data, false, false, $project_id);
    }

    public static function getNextFieldName($variable, $new, $old): string
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

    public static function getResolvedList($module, $status=''): array
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

    public static function parseArray($choices): array
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

    public static function custom_array_merge($module, $constant, $result, $data, $type, $option=''): array
    {
        if(!empty($data)) {
            $resolved_list = self::getResolvedList($module);

            $is_empty = true;
            $total = 0;
            foreach ($data as $key => $value) {
                $resolved_found = false;
                if(!empty($resolved_list) && is_array($resolved_list)) {
                    foreach ($resolved_list[$constant] as $key_resolved => $value_resolved) {
                        if ($value_resolved['field_name'] == $key) {
                            $resolved_found = true;
                        }
                    }
                }

                #Save data only
                #if it's NOT in the resolved list, option blank, show as an update
                #if it's in the resoved list, option resolved, show updates
                if(
                    ($option == '' && !$resolved_found)
                    ||
                    ($option == 'resolved' && $resolved_found)
                ){
                    $is_empty = false;
                    $result[$value['form_name']][$type][$key] = $value;
                    $total++;
                }
            }
            #make sure we have values to save before adding the total legend
            if(!$is_empty && $option == ''){
                if(!array_key_exists('TOTAL',$result))
                    $result["TOTAL"] = array();
                $result["TOTAL"][$type] = $total;
                $result["TOTAL"]["total"] += $result["TOTAL"][$type];
            }

            array_merge($result);
        }
        return $result;
    }

    public static function getIcon($status, $option = null): string
    {
        $icon = "fa-pencil-alt";
        $iconPDF = "#";
        $color = "";
        if($status == self::CHANGED){
            $icon = "fa-pencil-alt";
            $iconPDF = "#";
        }else if($status == self::ADDED){
            $icon = "fa-plus";
            $iconPDF = "+";
        }else if($status == self::REMOVED){
            $icon = "fa-minus";
            $iconPDF = "-";
            $color = "style='color:#fff'";
        }

        $icon_legend = '<a href="#" data-toggle="tooltip" title="'.$status.'" data-placement="top" class="custom-tooltip" style="vertical-align: -2px;"><span class="label '.$status.'" title="'.$status.'"><i class="fas '.$icon.'" aria-hidden="true"></i></span></a>';
        if($option == "pdf"){
            $icon_legend = '<span class="label '.$status.' labeltext">'.$iconPDF.'</span>';
        }
        return $icon_legend;
    }

    public static function getFieldName($new, $old, $status, $var, $option = ""): string
    {
       if($status == self::CHANGED) {
           if ($new[$var] !== $old[$var]) {
               $color = "class='mb-2 bg-warning';";
               $col = '<div $color id="bg-warning">' . self::checkTagsExistAndAreClosed($new[$var],) . '</div>';
               $col .= '<div class="text-muted" style="text-decoration: line-through;">' . self::checkTagsExistAndAreClosed($old[$var]) . '</div>';
           } else {
               $col = "<div class='mb-2'>" . self::checkTagsExistAndAreClosed($old[$var]) . "</div>";
           }
           if ($var == "field_name" && $old['form_name'] != "" && $new['form_name'] !== $old['form_name']) {
               $col .= "<small class='d-flex' style='font-size:12px;'>Form name: <span class='text-muted' style='text-decoration: line-through;padding-left:5px;'>".ucwords(str_replace('_', ' ', $old['form_name']))."</span><small>";
           }
           $col .= self::getFieldLabel($new, $old, self::CHANGED,'Show the field ONLY if: ','branching_logic', $option);
       }else {
           $col = self::checkTextLengthAndSplit($option, $new['field_name']);
           if ($new['branching_logic'] != "") {
               $col .= "<small class='d-flex' style='font-size:12px;'>Show the field ONLY if: " . filter_tags($new['branching_logic']) . "</small>";
           }
       }
       return $col;

    }
    public static function getFieldLabel($new, $old, $status, $string, $var, $option = ''): string
    {
        if($status == self::CHANGED) {
            $col = "";
            if($option == "pdf"){
                $col .= "<div style='width: 70%'>";
            }

            if ($new[$var] !== $old[$var]) {
                if ($old == "") {
                    $color = "class='mb-2 text-light p-1' style='background-color:#5d9451; font-size:12px;';";
                    $col .= "<div $color>$string " . filter_tags($new[$var]) . "</div>";
                } else if ($new[$var] == "") {
                    $color = "class='mb-2 p-1 bg-warning' style='font-size:12px;';";
                    $col .= "<small class='mb-2 d-flex text-light p-1' style='background-color:#cb410b; font-size:12px; text-decoration:line-through;'>$string" . filter_tags($old[$var]) . "</small>";
                } else {
                    $color = "class='mb-2 bg-warning p-1' style='font-size:12px;';";
                    $col .= "<div $color>$string " . filter_tags($new[$var]). "</div>";
                    $col .= "<small class='mb-2 p-1 d-flex' style='font-size:12px; text-decoration:line-through;'>$string " . filter_tags($old[$var]) . "</small>";
                }
            } else if ($old[$var] != "") {
                $col .= "<small class='d-flex mb-2'><div><i class='text-muted'>$string </i><i class='text-info'> " . filter_tags($old[$var]) . "</i></div></small>";
            }
        }else{
            $col = "";

            if ($new['section_header'] != "") {
                $col .= "<div class='mb-2' style='font-size:12px;'>Section Header: " . filter_tags($new['section_header']) . "</div>";
            }

            if($option == "pdf"){
                $col .= strip_tags($new['field_label']);
            }else{
                $col .= $new['field_label'];
            }


            if ($new['field_note'] != "") {
                $col .= "<small class='d-flex'>Field Note: " . filter_tags($new['field_note']) . "</small>";
            }
        }
        return $col;
    }
    public static function getFieldAttributes($value): string
    {
        $col = "";
        global $lang;
        $choices = self::parseArray($value['select_choices_or_calculations']);
        $col .= $value['field_type'];

        if ($value['text_validation_type_or_show_slider_number'] != "") {
            if ($value['text_validation_type_or_show_slider_number'] == 'int') $value['text_validation_type_or_show_slider_number'] = 'integer';
            elseif ($value['text_validation_type_or_show_slider_number'] == 'float') $value['text_validation_type_or_show_slider_number'] = 'number';
            elseif (in_array($value['text_validation_type_or_show_slider_number'], array('date', 'datetime', 'datetime_seconds'))) $value['text_validation_type_or_show_slider_number'] .= '_ymd';
            $col .= " (" . filter_tags($value['text_validation_type_or_show_slider_number']);
            if ($value['text_validation_min'] != "") {
                $col .= ", Min:" . filter_tags($value['text_validation_min']);
            }
            if ($value['text_validation_max'] != "") {
                $col .= ", Max: " . filter_tags($value['text_validation_max']);
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
            $col .= "<br /> Field Annotation: " . filter_tags($value['field_annotation']);
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
                $col .= '<td>' . filter_tags($value['select_choices_or_calculations']) . '</td>';
                $col .= '</tr>';
                $col .= '</table>';
            } elseif ($value['field_type'] == 'sql') {
                $col .= '<table border="0" cellpadding="2" cellspacing="0" class="ReportTableWithBorder"><tr><td>' . filter_tags($value['select_choices_or_calculations']) . '</td></tr></table>';
            } else {
                $col .= '<table border="0" cellpadding="2" cellspacing="0" class="ReportTableWithBorder">';
                foreach ($choices as $val => $label) {
                    $col .= '<tr valign="top">';
                    if ($value['field_type'] == 'checkbox') {
                        $col .= '<td>' . filter_tags($val) . '</td>';
                    } else {
                        $col .= '<td>' . filter_tags($val) . '</td>';
                    }

                    $col .= '<td>' . filter_tags($label) . '</td>';
                    $col .= '</tr>';
                }
                $col .= '</table>';
            }
        }
        return $col;
    }

    public static function getFieldAttributesChanged($new, $old): string
    {
        $col = "";
        $choices = self::parseArray($new['select_choices_or_calculations']);
        $oldChoices = self::parseArray($old['select_choices_or_calculations']);

        if ($new['field_type'] == 'select') $new['field_type'] = 'dropdown';
        elseif ($new['field_type'] == 'textarea') $new['field_type'] = 'notes';

        if($new['field_type'] !== $old['field_type']){
            if($old['field_type'] == ""){
                $color = "class='mb-2 text-light p-1 d-inline-block' style='background-color:#5d9451 !important; font-size:12px;';";
                $col .= "<div $color> " . filter_tags($new['field_type']) . "</div>";
            }else if($new['field_type'] == ""){
                $color = "class='mb-2 d-inline-block text-light p-1' style='background-color:#cb410b !important; font-size:12px; text-decoration:line-through;';";
                $col .= "<small $color>" . filter_tags($old['field_type']) . "</small>";
            }else{
                $color = "class='mb-2 bg-warning p-1 d-inline-block' style='font-size:12px;background-color:#ffc107 !important;';";
                $col .= "<div $color>" . filter_tags($new['field_type']) . "</div>";
                $col .= "<small class='mb-2 p-1 d-inline-block' style='font-size:12px; text-decoration:line-through;'>" . filter_tags($old['field_type']) . "</small>";
            }
        } else if ($old['field_type'] != "") {
            $col .= "<div class='d-inline-block mr-1 mb-2'>" . filter_tags($old['field_type']) . "</div>";
        }

        if($new['text_validation_type_or_show_slider_number'] !== $old['text_validation_type_or_show_slider_number']){
            if($old['text_validation_type_or_show_slider_number'] == ""){
                //New item
                $color = "class='mb-2 text-light p-1 d-inline-block' style='background-color:#5d9451 !important; font-size:12px;';";
                $col .= "<div $color> (" . filter_tags($new['text_validation_type_or_show_slider_number']);
                if ($new['text_validation_min'] != "") {
                    $col .= ", Min:" . filter_tags($new['text_validation_max']);
                }
                if ($new['text_validation_min'] != "") {
                    $col .= ", Max: " . filter_tags($new['text_validation_max']);
                }
                $col .= ") </div>";
            }elseif($new['text_validation_type_or_show_slider_number'] == ""){
                //removed
                $color = "class='mb-2 d-inline-block text-light p-1' style='background-color:#cb410b !important; font-size:12px; text-decoration:line-through;';";
                $col .= "<div $color> (" . filter_tags($old['text_validation_type_or_show_slider_number']);
                if ($old['text_validation_min'] != "") {
                    $col .= ", Min:" . filter_tags($old['text_validation_max']);
                }
                if ($old['text_validation_min'] != "") {
                    $col .= ", Max: " . filter_tags($old['text_validation_max']);
                }
                $col .= ") </div>";
            }else{
                $color = "class='mb-2 bg-warning p-1 d-inline-block' style='font-size:12px;background-color:#ffc107 !important;';";
                $col .= "<div $color> (" . filter_tags($new['text_validation_type_or_show_slider_number']);
                if ($new['text_validation_min'] != "") {
                    $col .= ", Min:" . filter_tags($new['text_validation_max']);
                }
                if ($new['text_validation_min'] != "") {
                    $col .= ", Max: " . filter_tags($new['text_validation_max']);
                }
                $col .= ") </div>";

                $col .= "<div class='ml-1 d-inline-block' style='font-size:12px; text-decoration:line-through;'> (" . filter_tags($old['text_validation_type_or_show_slider_number']);
                if ($old['text_validation_min'] != "") {
                    $col .= ", Min:" . filter_tags($old['text_validation_max']);
                }
                if ($old['text_validation_min'] != "") {
                    $col .= ", Max: " . filter_tags($old['text_validation_max']);
                }
                $col .= ") </div>";
            }

        } else if ($old['text_validation_type_or_show_slider_number'] != "") {
            $color = 'class="d-inline-block mr-1 mb-2" style="font-size:12px;"';
            $col .= "<div $color> (" . filter_tags($old['text_validation_type_or_show_slider_number']);
            if ($old['text_validation_min'] != "") {
                $col .= ", Min:" . filter_tags($old['text_validation_max']);
            }
            if ($old['text_validation_min'] != "") {
                $col .= ", Max: " . filter_tags($old['text_validation_max']);
            }
            $col .= ") </div>";
        }

        if($new['required_field'] !== $old['required_field']){
            if($old['required_field'] == ""){
                $color = "class='mb-2 text-light p-1 ml-2 d-inline-block' style='background-color:#5d9451 !important; font-size:12px;';";
                $col .= "<small $color> Required </small>";
            }elseif($new['required_field'] == ""){
                $color = "class='mb-2 ml-2 d-inline-block text-light p-1' style='background-color:#cb410b !important; font-size:12px; text-decoration:line-through;';";
                $col .= "<small $color> Required </small>";
            }
        }else if($old['required_field'] != ""){
            $color = 'class="d-inline-block mr-1 mb-2" style="font-size:12px;"';
            $col .= "<small $color> Required</small>";
        }

        if ($new['identifier'] !== $old['identifier']) {
            if ($old['identifier'] == "") {
                $color = "class='mb-2 text-light p-1 ml-2 d-inline-block' style='background-color:#5d9451 !important; font-size:12px;';";
                $col .= "<small $color> Identifier </small>";
            } elseif ($new['identifier'] == "") {
                $color = "class='mb-2 ml-2 d-inline-block text-light p-1' style='background-color:#cb410b !important; font-size:12px; text-decoration:line-through;';";
                $col .= "<small $color> Identifier </small>";
            }
        } else if ($old['identifier'] != "") {
            $color = 'class="d-inline-block mr-1 mb-2" style="font-size:12px;"';
            $col .= "<small $color> Identifier</small>";
        }

        if($new['field_annotation'] !== $old['field_annotation']){
            if($old['field_annotation'] == ""){
                $color = "class='mb-2 text-light p-1 d-block' style='background-color:#5d9451 !important; font-size:12px;';";
                $col .= "<small $color>Field Annotation: " . filter_tags($new['field_annotation']) . "</small>";
            }elseif($new['field_annotation'] == ""){
                $color = "class='mb-2 d-block text-light p-1' style='background-color:#cb410b !important; font-size:12px; text-decoration:line-through;';";
                $col .= "<small $color>Field Annotation: " . filter_tags($old['field_annotation']) . "</small>";
            }else{
                $color = "class='mb-2 bg-warning p-1 d-inline-block' style='font-size:12px;background-color:#ffc107 !important;';";
                $col .= "<div $color>" . filter_tags($new['field_annotation']) . "</div>";
                $col .= "<small class='mb-2 p-1 d-inline-block' style='font-size:12px; text-decoration:line-through;'>" . filter_tags($old['field_annotation']) . "</small>";
            }
        }else if($old['field_annotation'] != ""){
            $col .= "<div class='d-inline-block mr-1 mb-2'>" . filter_tags($old['field_annotation']) . "</div>";
        }

        if($new['select_choices_or_calculations'] !== $old['select_choices_or_calculations']){
            if($new['field_type'] == 'calc'){
                $col .= '<table>';
                $col .= '<tr>';
                $col .= '<th> Calculation </th>';
                $col .= '</tr>';
                $col .= '<tr>';
                $col .= "<td class='bg-warning' style='background-color:#ffc107 !important;'>" . filter_tags($new['select_choices_or_calculations']) . "</td>";
                $col .= '</tr>';
                $col .= '<tr>';
                $col .= "<td style='background-color:#cb410b !important; text-decoration:line-through;'>" . filter_tags($old['select_choices_or_calculations']) . "</td>";
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
                        $col .= '<td>' . filter_tags($val) . '</td>';
                        $col .= '<td>' . filter_tags($new['field_type']) . '</td>';
                    }elseif($label !== $oldValue){
                        if($oldValue == ""){
                            $col .= "<td class='text-light' style='background-color:#5d9451 !important;'>" . filter_tags($val) ."</td>";
                            $col .= "<td class='text-light' style='background-color:#5d9451 !important;'>" . filter_tags($label) . "</td>";
                        }elseif($label == ""){
                            $col .= "<td class='text-light' style='background-color:#cb410b !important; text-decoration:line-through;'>" . filter_tags($val) . "</td>";
                            $col .= "<td class='text-light' style='background-color:#cb410b !important; text-decoration:line-through;'>" . filter_tags($oldValue) . "</td>";
                        }elseif($label !== $oldValue){
                            $col .= "<td class='bg-warning' style='background-color:#ffc107 !important;'>" . filter_tags($val) . "</td>";
                            $col .= "<td class='bg-warning' style='background-color:#ffc107 !important;'>" . filter_tags($label) . "</td>";
                            $col .= "<td class='text-light' style='background-color:#cb410b !important; text-decoration: line-through;'>" . filter_tags($oldValue) . "</td>";
                        }
                    } else {
                        $col .= "<td>" . filter_tags($val) . "</td>";
                        $col .= "<td>" . filter_tags($label) . "</td>";
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
                $col .= "<td>" . filter_tags($old['select_choices_or_calculations']) . "</td>";
                $col .= '</tr>';
                $col .= '</table>';
            }elseif($old['field_type'] == 'sql'){
                $col .= '<table border="0" cellpadding="2" cellspacing="0" class="ReportTableWithBorder"><tr><td>' . filter_tags($old['select_choices_or_calculations']) . '</td></tr></table>';
            }else{
                $col .= '<table border="0" cellpadding="2" cellspacing="0" class="ReportTableWithBorder">';
                foreach ($oldChoices as $val => $label) {
                    $col .= '<tr valign="top">';
                    if ($old['field_type'] == 'checkbox' && $old['select_choices_or_calculations'] != $new['select_choices_or_calculations']) {
                        $col .= '<td>' . filter_tags($val) . '</td>';
                        $col .= '<td>'.filter_tags($label . $old['field_type']) . '</td>';
                    } else {
                        $col .= "<td>" . filter_tags($val) . "</td>";
                        $col .= "<td>" . filter_tags($label) . "</td>";
                    }
                }
                $col .= '</table>';
            }
        }
        return $col;
    }

    public static function getTemplateLastUpdatedDate($module, $constant, $resolved_date = null): string
    {
        $dateTemplateLastUpdated = date("F d Y H:i:s", filemtime($module->framework->getModulePath()."csv/".$constant.".csv"));
        if($resolved_date != null){
            if(strtotime($dateTemplateLastUpdated) > strtotime($resolved_date)) {
                #Files has been updated
                return "<span class='hub-update-last-updated-recent-date-badge badge' style='margin-left: 10px'>NEW CHANGES</span>";
            }
        }else{
            if(strtotime($dateTemplateLastUpdated) < strtotime('-30 days')) {
                #If past 30 days show in red
                return "<span class='hub-update-last-updated-past-date'>".$dateTemplateLastUpdated."</span>";
            }
            return $dateTemplateLastUpdated;
        }
        return "";
    }

    public static function getPrintData($module, $pidsArray, $constantArray): array
    {
        $printData = [];
        $oldValues = [];
        foreach ($constantArray as $constant => $project_data) {
            $oldValues[$constant] = \REDCap::getDataDictionary($pidsArray[$constant], 'array', false);

            $Proj = $module->getProject($pidsArray[$constant]);
            $gotoredcap = htmlentities(APP_PATH_WEBROOT_ALL . "Design/data_dictionary_codebook.php?pid=" . $pidsArray[$constant], ENT_QUOTES);
            $printData[$constant]['title'] = $Proj->getTitle();
            $printData[$constant]['gotoredcap'] = $gotoredcap;
            $printData[$constant]['pid'] = $pidsArray[$constant];
        }

        return [$printData,$oldValues];
    }

    /**
     * Function that checks if the html has all tags closed. This is made so user can see the issues and the PDF can be printed.
     * NOT: return as text
     * YES: return as html
     * @param $html
     * @return string
     */
    public static function checkTagsExistAndAreClosed($html): string
    {
        preg_match_all('#<([a-zA-Z0-9]+)(?: .*)?(?<![/|/ ])>#iU', $html, $result);
        $openedtags = $result[1];
        preg_match_all('#</([a-zA-Z0-9]+)>#iU', $html, $result);
        $closedtags = $result[1];
        $len_opened = count($openedtags);

        $tagsClosed = true;
        foreach ($openedtags as $index => $tagO) {
            if (!in_array($tagO, $closedtags)) {
                $tagsClosed = false;
            }
        }

        if ($tagsClosed) {
            return filter_tags($html);
        }

        return htmlspecialchars($html,ENT_QUOTES);
    }

    public static function checkTextLengthAndSplit($option, $text){
        if($option == "pdf" && strlen($text) > 15){
            $field_name_length = strlen($text);
            $field_name_part1 = substr($text, 0, 15);
            $field_name_part2 = substr($text, 15, $field_name_length);
            $text = $field_name_part1."<br>".$field_name_part2;
        }
        return $text;
    }
}
?>