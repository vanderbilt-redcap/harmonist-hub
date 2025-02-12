<?php

namespace Vanderbilt\HarmonistHubExternalModule;

use phpDocumentor\Reflection\Types\Boolean;
use REDCap;

class Model
{
    private $module;
    private $projectId;
    private $pidsArray = [];
    private $isAdmin;

    public function __construct(HarmonistHubExternalModule $module, $projectId)
    {
        $this->module = $module;
        $this->projectId = $projectId;
    }

    public function getPidsArray(): array
    {
        if (empty($this->pidsArray)) {
            $hub_mapper = $this->module->getProjectSetting('hub-mapper', $this->projectId);
            if ($hub_mapper !== "") {
                $this->pidsArray = REDCapManagement::getPIDsArray($hub_mapper, $this->projectId);
            }
        }
        return $this->pidsArray;
    }

    public function isAdmin(): bool
    {
        $this->isAdmin = false;
        if (defined('USERID')) {
            $UserRights = REDCap::getUserRights(USERID)[USERID];
            if ($UserRights['user_rights'] == '1') {
                $this->isAdmin = true;
            }
        }
        return $this->isAdmin;
    }

    public  function getProjectInfoArrayRepeatingInstruments($records, $project_id, $filterLogic = null, $option = null): array
    {
        $array = array();
        $found = array();
        $index = 0;
        if (is_array($filterLogic) && $filterLogic != null) {
            foreach ($filterLogic as $filterkey => $filtervalue) {
                array_push($found, false);
            }
        }
        foreach ($records as $record => $record_array) {
            $count = 0;
            if(is_array($filterLogic) && !empty($filterLogic)) {
                foreach ($filterLogic as $filterkey => $filtervalue) {
                    $found[$count] = false;
                    $count++;
                }
            }
            foreach ($record_array as $event => $data) {
                if ($event == 'repeat_instances') {
                    foreach ($data as $eventarray) {
                        $datarepeat = array();
                        foreach ($eventarray as $instrument => $instrumentdata) {
                            $count = 0;
                            foreach ($instrumentdata as $instance => $instancedata) {
                                foreach ($instancedata as $field_name => $value) {
                                    if (!empty($array[$index]) && !array_key_exists($field_name, $array[$index])) {
                                        $array[$index][$field_name] = array();
                                    }
                                    if ($value != "" && (!is_array($value) || (is_array($value) && !empty($value)))) {
                                        $datarepeat[$field_name][$instance] = $value;
                                        $count = 0;
                                        if(is_array($filterLogic) && !empty($filterLogic)) {
                                            foreach ($filterLogic as $filterkey => $filtervalue) {
                                                if ($value == $filtervalue && $field_name == $filterkey) {
                                                    $found[$count] = true;
                                                }
                                                $count++;
                                            }
                                        }
                                    }
                                    if (ProjectData::isCheckbox($field_name, $project_id) && $value[1] !== "") {
                                        $array[$index][$field_name][$instance] = $value[1];
                                    }
                                }
                                $count++;
                            }
                        }
                        foreach ($datarepeat as $field => $datai) {
                            #check if non repeatable value is empty and add repeatable value
                            #empty value or checkboxes
                            if ($array[$index][$field] == "" || (is_array(
                                        $array[$index][$field]
                                    ) && empty($array[$index][$field]))) {
                                $array[$index][$field] = $datarepeat[$field];
                            } else {
                                if (is_array($datai) && $option == "json") {
                                    #only for the JSON format
                                    $array[$index][$field] = $datarepeat[$field];
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
            $found_total = true;
            foreach ($found as $fname => $fvalue) {
                if ($fvalue == false) {
                    $found_total = false;
                    break;
                }
            }
            if (!$found_total && $filterLogic != null) {
                unset($array[$index]);
            }

            $index++;
        }
        return $array;
    }
}