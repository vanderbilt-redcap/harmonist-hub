<?php
namespace Vanderbilt\HarmonistHubExternalModule;


class HubData
{
    public $session_name;
    public $is_admin;
    public $person_region;
    public $requests;
    public $number_open_requests;

    function __construct($module,$name,$token, $pidsArray) {
        $this->session_name = $name;

        self::setCurrentUser($module, $pidsArray['PEOPLE'], $token);
        self::setPersonRegion($module, $pidsArray['REGIONS']);
        self::setAllRequests($pidsArray['RMANAGER']);
    }

    public function getCurrentUser()
    {
        return $_SESSION['current_user'][$this->session_name];
    }
    public function setCurrentUser($module,$project_id,$token)
    {
        if(empty($_SESSION['current_user'][$this->session_name])){
            $_SESSION['current_user'][$this->session_name] = $module->escape(\REDCap::getData($project_id, 'json-array', null,null,null,null,false,false,false,"[access_token] = '".$token."'")[0]);
            ## Check if current user is an Admin
            $this->is_admin = false;
            if($this->current_user['harmonistadmin_y'] == '1'){
                $this->is_admin = true;
            }
        }
    }
    public function getIsAdmin()
    {
        return $this->is_admin;
    }
    public function getPersonRegion()
    {
        return $_SESSION['person_region'][$this->session_name];
    }
    public function setPersonRegion($module,$project_id)
    {
        if (empty($_SESSION['person_region'][$this->session_name])) {
            $_SESSION['person_region'][$this->session_name] = $module->escape(\REDCap::getData($project_id, 'json-array', array('record_id' => $_SESSION['current_user'][$this->session_name]['person_region']))[0]);
        }
    }
    public function getAllRequests()
    {
        return $_SESSION['requests'][$this->session_name];
    }
    public function setAllRequests($project_id)
    {
        if (empty($_SESSION['requests'][$this->session_name])) {
            $RecordSetRM = \REDCap::getData($project_id, 'array', null);
            $requests = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRM, array('approval_y' => 1));
            ArrayFunctions::array_sort_by_column($requests, 'due_d');
            $_SESSION['requests'][$this->session_name] = $requests;
        }
    }
}