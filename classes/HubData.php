<?php
namespace Vanderbilt\HarmonistHubExternalModule;


class HubData
{
    public $session_name;
	private $pidsArray;

    function __construct($module,$name,$token, $pidsArray) {
        $this->session_name = $name;
		$this->pidsArray = $pidsArray;
		$this->module = $module;
		$this->token = $token;

        $user = self::getCurrentUser();
        $person_region = self::getPersonRegion();
    }

    public function getCurrentUser()
    {
        $project_id = $this->pidsArray['PEOPLE'];
        $last_logged_event = \Project::getLastLoggedEvent($project_id, true);
        if ((!self::doesCurrentUserExistsInSession() || self::isSessionParamEmptyORLastLoggedEvent(
                    'current_user',
                    $last_logged_event
                )) && !empty($this->token)) {
            $_SESSION[$this->session_name]['current_user'] = $this->module->escape(
                \REDCap::getData(
                    $project_id,
                    'json-array',
                    null,
                    null,
                    null,
                    null,
                    false,
                    false,
                    false,
                    "[access_token] = '" . $this->token . "'"
                )[0]
            );
            $_SESSION[$this->session_name]['last_logged_event']['current_user'] = $last_logged_event;
            ## Check if current user is an Admin
            $_SESSION[$this->session_name]['current_user']['is_admin'] = false;
            if ($_SESSION[$this->session_name]['current_user']['harmonistadmin_y'] == '1') {
                $_SESSION[$this->session_name]['current_user']['is_admin'] = true;
            }
        }
        if(!self::doesCurrentUserExistsInSession()){
            return [];
        }
        return $_SESSION[$this->session_name]['current_user'];
    }

    public function getPersonRegion()
    {
        if(self::doesHubSessionExist()) {
            $project_id = $this->pidsArray['REGIONS'];
            $last_logged_event = \Project::getLastLoggedEvent($project_id, true);
            if (self::doesCurrentUserExistsInSession() && array_key_exists('person_region', $_SESSION[$this->session_name]['current_user']) && self::isSessionParamEmptyORLastLoggedEvent('person_region', $last_logged_event)) {
                $_SESSION[$this->session_name]['person_region'] = $this->module->escape(
                    \REDCap::getData(
                        $project_id,
                        'json-array',
                        array('record_id' => $_SESSION[$this->session_name]['current_user']['person_region'])
                    )[0]
                );
                $_SESSION[$this->session_name]['last_logged_event']['person_region'] = $last_logged_event;
            }
            return $_SESSION[$this->session_name]['person_region'];
        }else{
            return [];
        }
    }
    public function getAllRegions()
    {
        if(self::doesHubSessionExist()) {
            $project_id = $this->pidsArray['REGIONS'];
            $last_logged_event = \Project::getLastLoggedEvent($project_id, true);
            if (self::isSessionParamEmptyORLastLoggedEvent('regions', $last_logged_event)) {
                $regions = \REDCap::getData($project_id, 'json-array', null,null,null,null,false,false,false,"[showregion_y] =1");
                ArrayFunctions::array_sort_by_column($regions, 'region_code');
                $_SESSION[$this->session_name]['regions'] = $regions;
                $_SESSION[$this->session_name]['last_logged_event']['regions'] = $last_logged_event;
            }
            return $_SESSION[$this->session_name]['regions'];
        }else{
            return [];
        }
    }
    public function getAllRequests()
    {
        if(self::doesHubSessionExist()) {
            $project_id = $this->pidsArray['RMANAGER'];
            ## Check if session data is already set and if a logged event (record update/creation) has happened since the cache was set
            $last_logged_event = \Project::getLastLoggedEvent($project_id, true);
            if (self::isSessionParamEmptyORLastLoggedEvent('requests', $last_logged_event)) {
                $RecordSetRM = \REDCap::getData($project_id, 'array', null);
                $requests = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRM, $project_id, array('approval_y' => 1));
                ArrayFunctions::array_sort_by_column($requests, 'due_d');
                $_SESSION[$this->session_name]['requests'] = $requests;
                $_SESSION[$this->session_name]['last_logged_event']['requests'] = $last_logged_event;
            }
            return $_SESSION[$this->session_name]['requests'];
        }else{
            return [];
        }
    }
    public function getCommentDetails()
    {
        if(self::doesHubSessionExist()) {
            $project_id = $this->pidsArray['COMMENTSVOTES'];
            $last_logged_event = \Project::getLastLoggedEvent($project_id, true);
            if (self::isSessionParamEmptyORLastLoggedEvent('reqcommentsDetailsuests', $last_logged_event)) {
                $comments = \REDCap::getData($project_id, 'json-array', null,array('request_id','vote_now','response_region','finalize_y','revision_counter', 'responsecomplete_ts'));
                $commentsDetails = [];
                foreach($comments as $commentDetails) {
                    $commentsDetails[$commentDetails["request_id"]][] = $commentDetails;
                }
                $_SESSION[$this->session_name]['commentsDetails'] = $commentsDetails;
                $_SESSION[$this->session_name]['last_logged_event']['commentsDetails'] = $last_logged_event;
            }
            return $_SESSION[$this->session_name]['commentsDetails'];
        }else{
            return [];
        }
    }

    private function doesCurrentUserExistsInSession(){
        if(isset($this->session_name) && array_key_exists($this->session_name,$_SESSION) && array_key_exists('current_user', $_SESSION[$this->session_name])) {
            return true;
        }
        return false;
    }

    private function doesHubSessionExist(){
        if(isset($this->session_name) && array_key_exists($this->session_name,$_SESSION) && is_array($_SESSION[$this->session_name])) {
            return true;
        }
        return false;
    }

    private function isSessionParamEmptyORLastLoggedEvent($param, $lastLoggedEvent){
        if(empty($_SESSION[$this->session_name][$param]) || $_SESSION[$this->session_name]['last_logged_event'][$param] != $lastLoggedEvent) {
            return true;
        }
        return false;
    }
}