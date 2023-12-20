<?php
namespace Vanderbilt\HarmonistHubExternalModule;


class ProjectData
{
    public $default_value;

    /**
     * Function that returns the info array from a specific project
     * @param $project, the project id
     * @param $info_array, array that contains the conditionals
     * @param string $type, if its single or a multidimensional array
     * @return array, the info array
     */
    public static function getProjectInfoArray($records){
        $array = array();
        foreach ($records as $event) {
            foreach ($event as $data) {
                array_push($array,$data);
            }
        }

        return $array;
    }

    public static function getProjectInfoArrayRepeatingInstruments($records,$filterLogic=null){
        $array = array();
        $found = array();
        $index=0;
        foreach ($filterLogic as $filterkey => $filtervalue){
            array_push($found, false);
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

                                }
                                $count++;
                            }
                        }
                        foreach ($datarepeat as $field=>$datai){
                            #check if non repeatable value is empty and add repeatable value
                            #empty value or checkboxes
                            if($array[$index][$field] == "" || (is_array($array[$index][$field]) && empty($array[$index][$field]))){
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
        foreach ($data_dictionary_settings as $row) {
            if($row['field_annotation'] != "" && strpos($row['field_annotation'], "@DEFAULT") !== false){
                $text = trim(explode("@DEFAULT=", $row['field_annotation'])[1],'\'"');;
                $default_value[$project_id][$row['field_name']] = $text;
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
        foreach ($instruments as $iid => $instrument_name) {
            $fields = array_keys(\REDCap::getDataDictionary($project_id, 'array', false, null, $instrument_name));
            foreach ($fields as $id => $name) {
                $data[$name] = $module->escape($data[$name]);
            }
        }
        return $data;
    }
}
?>