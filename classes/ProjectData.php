<?php
namespace Vanderbilt\HarmonistHubExternalModule;


class ProjectData
{
    public $default_value;
    const HUB_SURVEY_THEME_NAME = "Hub Survey Theme";

    public static function getProjectInfoArrayRepeatingInstruments($records,$project_id,$filterLogic=null,$option=null){
        $array = array();
        $found = array();
        $index=0;
        if(is_array($filterLogic) && $filterLogic != null) {
            foreach ($filterLogic as $filterkey => $filtervalue) {
                array_push($found, false);
            }
        }
        foreach ($records as $record=>$record_array) {
            $count = 0;
            foreach ($filterLogic as $filterkey => $filtervalue){
                $found[$count] = false;
                $count++;
            }
            foreach ($record_array as $event=>$data) {
                if($event == 'repeat_instances'){
                    foreach ($data as $eventarray){
                        $datarepeat = array();
                        foreach ($eventarray as $instrument=>$instrumentdata){
                            $count = 0;
                            foreach ($instrumentdata as $instance=>$instancedata){
                                foreach ($instancedata as $field_name=>$value){
                                    if(!empty($array[$index]) && !array_key_exists($field_name,$array[$index])){
                                        $array[$index][$field_name] = array();
                                    }
                                    if($value != "" && (!is_array($value) || (is_array($value) && !empty($value)))){
                                        $datarepeat[$field_name][$instance] = $value;
                                        $count = 0;
                                        foreach ($filterLogic as $filterkey => $filtervalue){
                                            if($value == $filtervalue && $field_name == $filterkey){
                                                $found[$count] = true;
                                            }
                                            $count++;
                                        }
                                    }
                                    if(ProjectData::isCheckbox($field_name,$project_id) && $value[1] !== ""){
                                        $array[$index][$field_name][$instance] = $value[1];
                                    }
                                }
                                $count++;
                            }
                        }
                        foreach ($datarepeat as $field=>$datai){
                            #check if non repeatable value is empty and add repeatable value
                            #empty value or checkboxes
                            if($array[$index][$field] == "" || (is_array($array[$index][$field]) && empty($array[$index][$field]))){
                                $array[$index][$field] = $datarepeat[$field];
                            }else if(is_array($datai) && $option == "json"){
                                #only for the JSON format
                                $array[$index][$field] = $datarepeat[$field];
                            }
                        }
                    }
                }else{
                    $array[$index] = $data;
                    foreach ($data as $fname=>$fvalue) {
                        $count = 0;
                        foreach ($filterLogic as $filterkey => $filtervalue){
                            if($fvalue == $filtervalue && $fname == $filterkey){
                                $found[$count] = true;
                            }
                            $count++;
                        }
                    }
                }
            }
            $found_total = true;
            foreach ($found as $fname=>$fvalue) {
                if($fvalue == false){
                    $found_total = false;
                    break;
                }
            }
            if(!$found_total && $filterLogic != null){
                unset($array[$index]);
            }

            $index++;
        }
        return $array;
    }

    public function getDefaultValues($project_id){
        $data_dictionary_settings = \REDCap::getDataDictionary($project_id, 'array',false);
        $default_value = array();
        if(is_array($data_dictionary_settings) && !empty($data_dictionary_settings)) {
            foreach ($data_dictionary_settings as $row) {
                if ($row['field_annotation'] !== "" && strpos($row['field_annotation'], "@DEFAULT") !== false) {
                    $text = trim(explode("@DEFAULT=", $row['field_annotation'])[1], '\'"');
                    $default_value[$project_id][$row['field_name']] = $text;
                }
            }
        }
        $this->default_value = $default_value;
        return $this->default_value[$project_id];
    }

    public static function installDefault($module, $project_id, $event_id, $record){
        $default_values = new ProjectData;
        $dv = $default_values->getDefaultValues($project_id);
        foreach ($dv as $variable=>$value){
            $module->addProjectToList($project_id, $event_id, $record, $variable, $value);
        }
    }

    public function getHideChoice($project_id){
        $data_dictionary_settings = \REDCap::getDataDictionary($project_id, 'array',false);
        $default_value = array();
        foreach ($data_dictionary_settings as $row) {
            if($row['field_annotation'] != "" && strpos($row['field_annotation'], "@HIDECHOICE") !== false){
                $text = explode(",",trim(explode("@HIDECHOICE=", $row['field_annotation'])[1],'\'"'));
                $default_value[$project_id][$row['field_name']] = $text;
            }
        }
        return $default_value;
    }

    public static function sanitizeALLVariablesFromInstrument($module,$project_id, $instruments, $data){
        if(!empty($project_id)) {
            foreach ($instruments as $iid => $instrument_name) {
                $data_dictionary = \REDCap::getDataDictionary($project_id, 'array', false, null, $instrument_name);
                if (!empty($data_dictionary)) {
                    $fields = array_keys($data_dictionary);
                    foreach ($fields as $id => $name) {
                        $data[$name] = $module->escape($data[$name]);
                    }
                }
            }
        }
        return $data;
    }

    public static function getCheckboxValuesAsArray($module, $project_id, $field_name, $data, $option=""){
        $labels = $module->getChoiceLabels($field_name, $project_id);
        $values = array();
        foreach ($labels as $index => $value){
            if($data[$field_name.'___'.$index] == 1){
                if($option = 'chart'){
                    array_push($values,'1');
                }else{
                    $values[$index] = '1';
                }
            }else{
                if($option = 'chart'){
                    array_push($values,'0');
                }else{
                    $values[$index] = '0';
                }
            }
        }
        return $values;
    }

    public static function replaceSymbolsForPDF($sopData){
        $specialCharacters = ["&ge;", "&le;" ];
        $specialCharactersReplacements = ["&gt;=","&lt;="];
        $dataChanged = $sopData;

        foreach($sopData as $index => $sop){
            if(!is_array($sop)) {
                #Only check data text no checkboxes, etc.
                foreach ($specialCharacters as $specialChar => $replacementData) {
                    $dataChanged[$index] = str_replace($specialCharacters, $specialCharactersReplacements, $dataChanged[$index]);
                }
            }
        }
        return $dataChanged;
    }

    public static function isCheckbox($field_name,$project_id)
    {
        $Proj = new \Project($project_id);
        // If field is invalid, return false
        if (!isset($Proj->metadata[$field_name])) return false;
        // Array to translate back-end field type to front-end (some are different, e.g. "textarea"=>"notes")
        $fieldTypeTranslator = array('textarea'=>'notes', 'select'=>'dropdown');
        // Get field type
        $fieldType = $Proj->metadata[$field_name]['element_type'];
        // Translate field type, if needed
        if (isset($fieldTypeTranslator[$fieldType])) {
            $fieldType = $fieldTypeTranslator[$fieldType];
        }
        unset ($Proj);
        if($fieldType == "checkbox"){
            return true;
        }
        return false;
    }

    public static function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return "<span style='font-style:italic;font-size:11px;'>(".round($bytes, $precision) ." ". $units[$pow].")</span>";
    }

    public static function createSurveyTheme($module){
        $q = $module->query("SELECT theme_id FROM redcap_surveys_themes WHERE theme_name = ?", [self::HUB_SURVEY_THEME_NAME]);
        if($row = $q->fetch_assoc()){
            return $row['theme_id'];
        }else {
            $q = $module->query("INSERT INTO redcap_surveys_themes 
                                (theme_name, ui_id,theme_bg_page, theme_text_buttons, theme_text_title, theme_bg_title,
                                theme_text_question, theme_bg_question, theme_text_sectionheader, theme_bg_sectionheader) VALUES(?,?,?,?,?,?,?,?,?,?)",
                                [self::HUB_SURVEY_THEME_NAME, null, "eaeaea", "000000", "000000", "ffffff",
                                    "000000", "ffffff", "000000", "c2e4fc"]);
            $theme_id = db_insert_id();
        }
        return $theme_id;
    }

    public static function checkIfThemeExists($module, $pidsArray){
        $q = $module->query("SELECT theme_id FROM redcap_surveys_themes WHERE theme_name = ?", [self::HUB_SURVEY_THEME_NAME]);
        if($row = $q->fetch_assoc()){
           if($row['theme_id'] !== ""){
               foreach ($pidsArray as $constant => $project_id) {
                   $q_survey = $module->query("SELECT survey_id, form_name FROM redcap_surveys WHERE project_id = ? AND (theme <> ? OR theme is NULL)", [$project_id, $row['theme_id']]);
                   if($row_survey = $q_survey->fetch_assoc()){
                       return false;
                   }
               }
               return true;
           }
        }
        return false;
    }

    public static function updateThemeOnSurveys($module, $constant, $pidsArray, $theme_id=""){
        if($theme_id === "") {
            $q = $module->query("SELECT theme_id FROM redcap_surveys_themes WHERE theme_name = ?", [self::HUB_SURVEY_THEME_NAME]);
            if ($row = $q->fetch_assoc()) {
                $theme_id = $row['theme_id'];
            }
        }
        $surveys_without_theme = [];
        $q_survey = $module->query("SELECT survey_id, form_name FROM redcap_surveys WHERE project_id = ? AND (theme <> ? OR theme is NULL)", [$pidsArray[$constant],$theme_id]);
        while($row_survey = $q_survey->fetch_assoc()){
            array_push($surveys_without_theme, $row_survey['form_name']);
            $module->query("UPDATE redcap_surveys SET theme = ? WHERE survey_id = ?",[$theme_id, $row_survey['survey_id']]);
        }
        if(!empty($surveys_without_theme)){
            $data = json_encode($surveys_without_theme,JSON_PRETTY_PRINT);
            \REDCap::logEvent("Hub Updates: ".self::HUB_SURVEY_THEME_NAME." (".$theme_id.")  added on  ".$constant." (PID #".$pidsArray[$constant].")", $data, null,null,null,$pidsArray[$constant]);
            \REDCap::logEvent("Hub Updates: ".self::HUB_SURVEY_THEME_NAME." (".$theme_id.") added on  ".$constant." (PID #".$pidsArray[$constant].")", $data, null,null,null,$pidsArray['PROJECTS']);
        }
    }
}
?>