<?php
namespace Vanderbilt\HarmonistHubExternalModule;


class HubData
{
    public $session_name;
    public $is_admin;
    public $person_region;
    public $requests;
    public $number_open_requests;
	
	private $pidsArray;

    function __construct($module,$name,$token, $pidsArray) {
        $this->session_name = $name;
		error_log("***SESSION: ".$this->session_name);
		$this->pidsArray = $pidsArray;

        self::setCurrentUser($module, $pidsArray['PEOPLE'], $token);
        self::setPersonRegion($module, $pidsArray['REGIONS']);
    }

    public function getCurrentUser()
    {
        return $_SESSION[$this->session_name]['current_user'];
    }
    public function setCurrentUser($module,$project_id,$token)
    {
        if(empty($_SESSION[$this->session_name]['current_user']) && !empty($token)){
            $_SESSION[$this->session_name]['current_user'] = $module->escape(\REDCap::getData($project_id, 'json-array', null,null,null,null,false,false,false,"[access_token] = '".$token."'")[0]);
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
        if (empty($_SESSION[$this->session_name]['person_region'])) {
            $_SESSION[$this->session_name]['person_region'] = $module->escape(\REDCap::getData($project_id, 'json-array', array('record_id' => $_SESSION[$this->session_name]['current_user']['person_region']))[0]);
        }
    }
    public function getAllRegions()
    {
        $project_id = $this->pidsArray['REGIONS'];
        $last_logged_event = \Project::getLastLoggedEvent($project_id, true);
        if (empty($_SESSION[$this->session_name]['regions']) || ($_SESSION[$this->session_name]['regions']['last_logged_event'] != $last_logged_event)) {
            $regions = \REDCap::getData($project_id, 'json-array', null,null,null,null,false,false,false,"[showregion_y] =1");
            ArrayFunctions::array_sort_by_column($regions, 'region_code');
            $_SESSION[$this->session_name]['regions'] = $regions;
            $_SESSION[$this->session_name]['regions']['last_logged_event'] = $last_logged_event;
        }
        return $_SESSION[$this->session_name]['regions'];
    }
    public function getAllRequests()
    {
		$project_id = $this->pidsArray['RMANAGER'];
		
		## Check if session data is already set and if a logged event (record update/creation) has happened since the cache was set
		$last_logged_event = \Project::getLastLoggedEvent($project_id, true);
		if (empty($_SESSION[$this->session_name]['requests']) || ($_SESSION[$this->session_name]['requests']['last_logged_event'] != $last_logged_event)) {
			$RecordSetRM = \REDCap::getData($project_id, 'array', null);
			$requests = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRM, array('approval_y' => 1));
			ArrayFunctions::array_sort_by_column($requests, 'due_d');
			$_SESSION[$this->session_name]['requests'] = $requests;
			$_SESSION[$this->session_name]['requests']['last_logged_event'] = $last_logged_event;
		}
		
        return $_SESSION[$this->session_name]['requests'];
    }
}