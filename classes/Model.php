<?php

namespace Vanderbilt\HarmonistHubExternalModule;

use phpDocumentor\Reflection\Types\Boolean;
use REDCap;

class Model
{
    protected $module;
    protected $projectId;
    protected $pidsArray = [];

    public function __construct(HarmonistHubExternalModule $module, $projectId)
    {
        $this->module = $module;
        $this->projectId = $projectId;
    }

    public function getPidsArray(): array
    {
        if (empty($this->pidsArray)) {
            $hubMapper = $this->module->getProjectSetting('hub-mapper', $this->projectId);
            if ($hubMapper !== "") {
                $this->pidsArray = REDCapManagement::getPIDsArray($hubMapper, $this->projectId);
            }
        }
        return $this->pidsArray;
    }

    public static function isHarmonistAdmin($currentUser, $peoplePid, $module): bool
    {
        $isHarmonistAdmin = false;
        if (isset($currentUser)) {
            $params = [
                'project_id' => $peoplePid,
                'return_format' => 'json-array',
                'records' => [$currentUser],
                'fields'=> ['harmonistadmin_y']
            ];
            $harmonistadminY = $module->escape(\REDCap::getData($params)[0]['harmonistadmin_y']);
            if ($harmonistadminY == "1") {
                $isHarmonistAdmin = true;
            }
        }
        return $isHarmonistAdmin;
    }

    public  function getProjectInfoArrayRepeatingInstruments($records, $projectId, $filterLogic = null, $option = null): array
    {
        $array = [];
        $found = [];
        $index = 0;
        if (is_array($filterLogic) && $filterLogic != null) {
            foreach ($filterLogic as $filterkey => $filterValue) {
                array_push($found, false);
            }
        }
        foreach ($records as $record => $recordArray) {
            $count = 0;
            if(is_array($filterLogic) && !empty($filterLogic)) {
                foreach ($filterLogic as $filterkey => $filterValue) {
                    $found[$count] = false;
                    $count++;
                }
            }
            foreach ($recordArray as $event => $data) {
                if ($event == 'repeat_instances') {
                    foreach ($data as $eventArray) {
                        $dataRepeat = [];
                        foreach ($eventArray as $instrument => $instrumentData) {
                            $count = 0;
                            foreach ($instrumentData as $instance => $instanceData) {
                                foreach ($instanceData as $fieldName => $value) {
                                    if (!empty($array[$index]) && !array_key_exists($fieldName, $array[$index])) {
                                        $array[$index][$fieldName] = [];
                                    }
                                    if ($value != "" && (!is_array($value) || (is_array($value) && !empty($value)))) {
                                        $dataRepeat[$fieldName][$instance] = $value;
                                        $count = 0;
                                        if(is_array($filterLogic) && !empty($filterLogic)) {
                                            foreach ($filterLogic as $filterkey => $filterValue) {
                                                if ($value == $filterValue && $fieldName == $filterkey) {
                                                    $found[$count] = true;
                                                }
                                                $count++;
                                            }
                                        }
                                    }
                                    if (ProjectData::isCheckbox($fieldName, $projectId) && $value[1] !== "") {
                                        $array[$index][$fieldName][$instance] = $value[1];
                                    }
                                }
                                $count++;
                            }
                        }
                        foreach ($dataRepeat as $field => $datai) {
                            #check if non repeatable value is empty and add repeatable value
                            #empty value or checkboxes
                            if ($array[$index][$field] == "" || (is_array(
                                        $array[$index][$field]
                                    ) && empty($array[$index][$field]))) {
                                $array[$index][$field] = $dataRepeat[$field];
                            } else {
                                if (is_array($datai) && $option == "json") {
                                    #only for the JSON format
                                    $array[$index][$field] = $dataRepeat[$field];
                                }
                            }
                        }
                    }
                } else {
                    $array[$index] = $data;
                    foreach ($data as $fname => $fvalue) {
                        $count = 0;
                        if(is_array($filterLogic) && !empty($filterLogic)) {
                            foreach ($filterLogic as $filterkey => $filtervalue) {
                                if ($fvalue == $filtervalue && $fname == $filterkey) {
                                    $found[$count] = true;
                                }
                                $count++;
                            }
                        }
                    }
                }
            }
            $foundTotal = true;
            foreach ($found as $fname => $fvalue) {
                if ($fvalue == false) {
                    $foundTotal = false;
                    break;
                }
            }
            if (!$foundTotal && $filterLogic != null) {
                unset($array[$index]);
            }

            $index++;
        }
        return $array;
    }

    public function getModule(): array
    {
        return $this->module;
    }
}