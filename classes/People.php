<?php

namespace Vanderbilt\HarmonistHubExternalModule;

use REDCap;

class People extends Model
{
    private $recordId;
    private $peopleData;
    private $activeY;
    private $firstname;
    private $lastname;
    private $email;
    private $personRegion;
    private $harmonistRegperm;
    private $pendingpanelY;
    private $stayrequestY;
    private $allowgetdataY;
    private $redcapName;
    private $harmonistadminY;
    private $harmonistPerms;
    private $accessToken;
    private $tokenExpirationD;
    private $regionCode;
    private $errorUserList;
    private $errorPemissionList = [];

    public function __construct($peopleData, HarmonistHubExternalModule $module, $pidsMapper)
    {
        parent::__construct($module, $pidsMapper);
        $this->peopleData = $peopleData;
        $this->hydratePeople();
        $this->decorateUserRegion();
    }

    public function getRecordId()
    {
        return $this->recordId;
    }

    public function setRecordId($recordId): void
    {
        $this->recordId = $recordId;
    }

    public function getActiveY()
    {
        return $this->activeY;
    }

    public function setActiveY($activeY): void
    {
        $this->activeY = $activeY;
    }

    public function getFirstname()
    {
        return $this->firstname;
    }

    public function setFirstname($firstname): void
    {
        $this->firstname = $firstname;
    }

    public function getLastname()
    {
        return $this->lastname;
    }

    public function setLastname($lastname): void
    {
        $this->lastname = $lastname;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email): void
    {
        $this->email = $email;
    }

    public function getPersonRegion()
    {
        return $this->personRegion;
    }

    public function setPersonRegion($personRegion): void
    {
        $this->personRegion = $personRegion;
    }

    public function getHarmonistRegperm()
    {
        return $this->harmonistRegperm;
    }

    public function setHarmonistRegperm($harmonistRegperm): void
    {
        $this->harmonistRegperm = $harmonistRegperm;
    }

    public function getPendingpanelY()
    {
        return $this->pendingpanelY;
    }

    public function setPendingpanelY($pendingpanelY): void
    {
        $this->pendingpanelY = $pendingpanelY;
    }

    public function getStayrequestY()
    {
        return $this->stayrequestY;
    }

    public function setStayrequestY($stayrequestY): void
    {
        $this->stayrequestY = $stayrequestY;
    }

    public function getAllowgetdataY()
    {
        return $this->allowgetdataY;
    }

    public function setAllowgetdataY($allowgetdataY): void
    {
        $this->allowgetdataY = $allowgetdataY;
    }

    public function getRedcapName()
    {
        return $this->redcapName;
    }

    public function setRedcapName($redcapName): void
    {
        $this->redcapName = $redcapName;
    }

    public function getHarmonistadminY()
    {
        return $this->harmonistadminY;
    }

    public function setHarmonistadminY($harmonistadminY): void
    {
        $this->harmonistadminY = $harmonistadminY;
    }

    public function getHarmonistPerms()
    {
        return $this->harmonistPerms;
    }

    public function setHarmonistPerms($harmonistPerms): void
    {
        $this->harmonistPerms = $harmonistPerms;
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function setAccessToken($accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function getTokenExpirationD()
    {
        return $this->tokenExpirationD;
    }

    public function setTokenExpirationD($tokenExpirationD): void
    {
        $this->tokenExpirationD = $tokenExpirationD;
    }

    public function setErrorUserList($errorUserList): void
    {
        $this->errorUserList = $errorUserList;
    }

    public function getErrorUserList(): array
    {
        return $this->errorUserList;
    }

    public function getRegionCode()
    {
        return $this->regionCode;
    }

    public function setRegionCode($regionCode): void
    {
        $this->regionCode = $regionCode;
    }

    public function fetchErrorPemissionList(){
        $this->checkUserName();
        $this->validateUserActive();
        $this->validateUserHubAccess();
        return $this->errorPemissionList;
    }

    public function removeUserFromDataDownloads($userId): void
    {
        $this->removeDataDownloadPermission($userId);
        $this->removeUserFromDataDonwloadsProject($userId);
    }

    public function addUserToDataDownloads($userId, $userName, $missing): void
    {
        if($missing) {
            $this->addUsernameOnProject($userId, $userName);
        }
        $this->addUserToDataDonwloadsProject($userId, $userName);
    }

    private function removeDataDownloadPermission($record): void
    {
        #Uncheck variable
        $Proj = new \Project($this->getPidsArray()['PEOPLE']);
        $event_id = $Proj->firstEventId;
        $array = [];
        $array[$record][$event_id]['allowgetdata_y'] = [1 => ""];//checkbox

        $params = [
            'project_id' => $this->getPidsArray()['PEOPLE'],
            'dataFormat' => 'array',
            'data' => $array,
            'overwriteBehavior' => "overwrite",
            'dateFormat' => "YMD",
            'type' => "flat"
        ];
        $results = \REDCap::saveData($params);
        if(array_key_exists("errors", $results) && !empty($results["errors"])) {
            throw new Exception("ERROR. Something went wrong while trying to save data to database.".var_export($results["errors"]));
        }
    }

    private function removeUserFromDataDonwloadsProject(): void
    {
        if(!empty($userData) && $this->isUserInDataDownloads()){
            if(filter_var($this->redcapName, FILTER_VALIDATE_EMAIL)){
                #USER ID IS EMAIL
                #TODO
            }else{
                #USER ID
                $q = $this->module->query("DELETE FROM redcap_user_rights WHERE project_id = ? and username = ?", [$this->getPidsArray()['DATADOWNLOADUSERS'],$userData['redcap_name']]);

                #Logs
                $message = "User ".$this->redcapName." removed by ".USERID;
                \REDCap::logEvent($message, "Deletion from Data Downloads User Management", null, null, null, $this->getPidsArray()['DATADOWNLOADUSERS']);
            }
        }
    }

    private function addUsernameOnProject(): void
    {
        $Proj = new \Project($this->getPidsArray()['PEOPLE']);
        $event_id = $Proj->firstEventId;
        $array = [];
        $array[$this->recordId][$event_id]['redcap_name'] = $this->redcapName;

        $params = [
            'project_id' => $this->getPidsArray()['PEOPLE'],
            'dataFormat' => 'array',
            'data' => $array,
            'overwriteBehavior' => "overwrite",
            'dateFormat' => "YMD",
            'type' => "flat"
        ];
        $results = \REDCap::saveData($params);
        if(array_key_exists("errors", $results) && !empty($results["errors"])) {
            throw new Exception("ERROR. Something went wrong while trying to save data to database.".var_export($results["errors"]));
        }
    }

    private function addUserToDataDonwloadsProject(): void
    {
        if(!$this->isUserInDataDownloads()){
            if(filter_var($this->redcapName, FILTER_VALIDATE_EMAIL)){
                #USER ID IS EMAIL
                #TODO
            }else{
                #USER ID
                $fields_rights = "project_id, username, design, user_rights, data_export_tool, reports, graphical, data_logging, data_entry";
                $instrument_names = \REDCap::getInstrumentNames(null,$this->getPidsArray()['DATADOWNLOADUSERS']);
                #Data entry [$instrument,$status] -> $status: 0 NO ACCESS, 1 VIEW & EDIT, 2 READ ONLY
                $data_entry = "[".implode(',1][',array_keys($instrument_names)).",1]";
                $this->module->query("INSERT INTO redcap_user_rights (" . $fields_rights . ")
                                VALUES (?,?,?,?,?,?,?,?,?)",
                                     [$this->getPidsArray()['DATADOWNLOADUSERS'], $this->redcapName, 1, 1, 1, 1, 1, 1, $data_entry]);

                #Logs
                $message = "User ".$this->redcapName." added by ".USERID;
                \REDCap::logEvent($message, "Added user from Data Downloads User Management", null, null, null, $this->getPidsArray()['DATADOWNLOADUSERS']);
            }
        }
    }

    private function isUserInDataDownloads(): bool
    {
        $sql = "SELECT p.app_title
					FROM redcap_projects p
					JOIN redcap_user_rights u ON p.project_id = u.project_id
					LEFT OUTER JOIN redcap_user_roles r ON p.project_id = r.project_id AND u.role_id = r.role_id
					WHERE u.username = ? 
					AND p.date_deleted IS NULL
                    AND p.status IN (0,1) 
                    AND p.completed_time IS NULL 
                    AND p.project_id = ?";

        $q = $this->module->query($sql,[$this->redcapName, $this->getPidsArray()['DATADOWNLOADUSERS']]);
        if ($row = $q->fetch_assoc()) {
            return true;
        }
        return false;
    }

    private function checkUserName(): void
    {
        if(!empty($this->redcapName)){
            if(!$this->isUserInDataDownloads()){
                $this->errorPemissionList[] = "User <strong><em>".$this->redcapName."</em></strong> has data downloads activated but is <strong>not in Data Downloads Project</strong>.";
            }
            if($this->isUserExpired()){
                $this->errorPemissionList[] = "<strong>REDCap account has expired</strong> for user <strong><em>".$this->redcapName."</em></strong> .";
            }
            if(!$this->doesUserExistInREDCap()){
                $this->errorPemissionList[] = "Username <strong><em>".$this->redcapName."</em> doesn't exist in REDCap</strong>.";
                $this->errorPemissionList["usernameMissing"] = true;
            }
        }else{
            $this->errorPemissionList[] = "User has downloads activated but the username is empty.";
            $this->errorPemissionList["usernameMissing"] = true;
        }
    }


    private function isUserExpired(): bool
    {
        $sql = "SELECT * 
                FROM `redcap_user_rights` 
                WHERE username = ? 
                AND expiration IS NOT NULL";

        $q = $this->module->query($sql,[$this->redcapName]);
        if ($row = $q->fetch_assoc()) {
            return true;
        }
        return false;
    }
    private function doesUserExistInREDCap(): bool
    {
        $sql = "SELECT * 
                FROM `redcap_user_rights` 
                WHERE username = ?";

        $q = $this->module->query($sql,[$this->redcapName]);
        if ($row = $q->fetch_assoc()) {
            return true;
        }
        return false;
    }

    private function validateUserActive(): void
    {
        if($this->activeY == "0" || empty($this->activeY)){
            $this->errorPemissionList[] = "User is <strong>not active</strong>.";
        }
    }

    private function validateUserHubAccess(): void
    {
        if($this->harmonistRegperm == "0"){
            $this->errorPemissionList[] = "User has <strong>no Hub Access Permission</strong>.";
        }
    }


    private function hydratePeople()
    {
        $this->recordId = $this->peopleData['record_id'];
        $this->activeY = $this->peopleData['active_y'];
        $this->firstname = $this->peopleData['firstname'];
        $this->lastname = $this->peopleData['lastname'];
        $this->email = $this->peopleData['email'];
        $this->personRegion = $this->peopleData['person_region'];
        $this->harmonistRegperm = $this->peopleData['harmonist_regperm'];
        $this->pendingpanelY = $this->peopleData['pendingpanel_y'];
        $this->stayrequestY = $this->peopleData['stayrequest_y'];
        $this->allowgetdataY = $this->peopleData['allowgetdata_y'];
        $this->redcapName = $this->peopleData['redcap_name'];
        $this->harmonistadminY = $this->peopleData['harmonistadmin_y'];
        $this->harmonistPerms = $this->peopleData['harmonist_perms'];
        $this->accessToken = $this->peopleData['access_token'];
        $this->tokenExpirationD = $this->peopleData['token_expiration_d'];
    }

    private function decorateUserRegion(): void
    {
        if($this->peopleData['person_region'] != null){
            $params = [
                'project_id' => $this->getPidsArray()['REGIONS'],
                'return_format' => 'json-array',
                'records' => $this->peopleData['person_region'],
                'fields' => [
                    'region_code'
                ]
            ];
            $personRegion = $this->module->escape(\REDCap::getData($params))[0];
            $regionCode = "";
            if(!empty($personRegion)){
                $regionCode = "(".$personRegion['region_code'].")";
            }
            $this->regionCode = $regionCode;
        }
    }

}
?>
