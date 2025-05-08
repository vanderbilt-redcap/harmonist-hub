<?php
namespace Vanderbilt\HarmonistHubExternalModule;

class HubDataDownloadsUsers extends Model
{
    private $successUserList = [];
    private $errorUserList = [];
    public function __construct(HarmonistHubExternalModule $module, $peoplePid)
    {
        $hub_mapper = $module->getProjectSetting('hub-mapper', $peoplePid);
        parent::__construct($module, $hub_mapper);
        $this->loadUserList();
    }

    public function setErrorUserList($errorUserList): void
    {
        $this->errorUserList = $errorUserList;
    }

    public function getErrorUserList(): array
    {
        return $this->errorUserList;
    }

    public function setSuccessUserList($successUserList): void
    {
        $this->successUserList = $successUserList;
    }

    public function getSuccessUserList(): array
    {
        return $this->successUserList;
    }

    public function getUserDataEntryLink($recordId, $projectId): string
    {
        return $this->getDatEntryLink($recordId, $projectId);
    }

    public function loadUserList(): void
    {
        $params = [
            'project_id' => $this->getPidsArray()['PEOPLE'],
            'return_format' => 'json-array',
            'fields' => [
                'record_id',
                'active_y',
                'email',
                'firstname',
                'lastname',
                'person_region',
                'harmonist_regperm',
                'allowgetdata_y',
                'harmonistadmin_y',
                'redcap_name'
            ]
        ];
        $peopleData = $this->module->escape(\REDCap::getData($params));
        if (!empty($peopleData)) {
            foreach ($peopleData as $person) {
                $errorPemissionList = [];
                if($this->canUserDownloadData($person['allowgetdata_y___1'])) {
                    $errorPemissionList = $this->checkUserName($person['redcap_name'], $errorPemissionList);
                    $errorPemissionList = $this->isUserActive($person['active_y'], $errorPemissionList);
                    $errorPemissionList = $this->doesUserHaveHubAccess($person['harmonist_regperm'], $errorPemissionList);

                    $person = $this->decorateUserRegion($person);

                    $this->decorateAllUserLists($person, $errorPemissionList);
                }
            }
        }
    }
    public function removeUserFromDataDownloads($userId): void
    {
        $this->removeDataDownloadPermission($userId);
//        $this->removeUserFromDataDonwloadsProject($userId);
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
            throw new Exception("ERROR. Something went wrong while trying to save data to database.");
        }
    }

    private function removeUserFromDataDonwloadsProject($userId): void
    {
        $params = [
            'project_id' => $this->getPidsArray()['PEOPLE'],
            'return_format' => 'json-array',
            'records' => [$userId],
            'fields' => [
                'redcap_name',
                'email',
                'firstname',
                'lastname'
            ]
        ];
        $userData = $this->module->escape(\REDCap::getData($params))[0];
        if(!empty($userData) && $this->isUserInDataDownloads($userData['redcap_name'])){
            if(filter_var($userData['redcap_name'], FILTER_VALIDATE_EMAIL)){
                #USER ID IS EMAIL
                #TODO
            }else{
                #USER ID
                $q = $this->module->query("DELETE FROM redcap_user_rights WHERE project_id = ? and username = ?", [$this->getPidsArray()['DATADOWNLOADUSERS'],$userData['redcap_name']]);

                #Logs
                $message = "User ".$userData['redcap_name']." removed by ".USERID;
                \REDCap::logEvent($message, "Deletion from Data Downloads User Management", null, null, null, $this->getPidsArray()['DATADOWNLOADUSERS']);
            }
        }
    }

    private function addUsernameOnProject($record, $userName): void
    {
        $Proj = new \Project($this->getPidsArray()['PEOPLE']);
        $event_id = $Proj->firstEventId;
        $array = [];
        $array[$record][$event_id]['redcap_name'] = $userName;

        $params = [
            'project_id' => $this->getPidsArray()['PEOPLE'],
            'redcap_name' => 'array',
            'data' => $array,
            'overwriteBehavior' => "overwrite",
            'dateFormat' => "YMD",
            'type' => "flat"
        ];
        $results = \REDCap::saveData($params);
        if(array_key_exists("errors", $results) && !empty($results["errors"])) {
            throw new Exception("ERROR. Something went wrong while trying to save data to database.");
        }
    }

    private function addUserToDataDonwloadsProject($userId, $userName): void
    {
        $params = [
            'project_id' => $this->getPidsArray()['PEOPLE'],
            'return_format' => 'json-array',
            'records' => [$userId],
            'fields' => [
                'email',
                'firstname',
                'lastname'
            ]
        ];
        $userData = $this->module->escape(\REDCap::getData($params))[0];
        if(!empty($userData) && !$this->isUserInDataDownloads($userName)){
            if(filter_var($userName, FILTER_VALIDATE_EMAIL)){
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
                               [$this->getPidsArray()['DATADOWNLOADUSERS'], $userName, 1, 1, 1, 1, 1, 1, $data_entry]);

                #Logs
                $message = "User ".$userName." added by ".USERID;
                \REDCap::logEvent($message, "Added user from Data Downloads User Management", null, null, null, $this->getPidsArray()['DATADOWNLOADUSERS']);
            }
        }
    }

    private function canUserDownloadData($personDownload): bool
    {
        if ($personDownload == "1") {
            return true;
        }
        return false;
    }

    private function checkUserName($username, $errorPemissionList): array
    {
        if(!empty($username)){
            if(!$this->isUserInDataDownloads($username)){
                $errorPemissionList[] = "User <strong><em>".$username."</em></strong> has data downloads activated but is <strong>not in Data Downloads Project</strong>.";
            }
            if($this->isUserExpired($username)){
                $errorPemissionList[] = "<strong>REDCap account has expired</strong> for user <strong><em>".$username."</em></strong> .";
            }
            if(!$this->doesUserExistInREDCap($username)){
                $errorPemissionList[] = "Username <strong><em>".$username."</em> doesn't exist in REDCap</strong>.";
                $errorPemissionList["usernameMissing"] = true;
            }
        }else{
            $errorPemissionList[] = "User has downloads activated but the username is empty.";
            $errorPemissionList["usernameMissing"] = true;
        }
        return $errorPemissionList;
    }

    private function isUserInDataDownloads($username): bool
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

        $q = $this->module->query($sql,[$username, $this->getPidsArray()['DATADOWNLOADUSERS']]);
        if ($row = $q->fetch_assoc()) {
            return true;
        }
        return false;
    }

    private function isUserExpired($username): bool
    {
        $sql = "SELECT * 
                FROM `redcap_user_rights` 
                WHERE username = ? 
                AND expiration IS NOT NULL";

        $q = $this->module->query($sql,[$username]);
        if ($row = $q->fetch_assoc()) {
            return true;
        }
        return false;
    }
    private function doesUserExistInREDCap($username): bool
    {
        $sql = "SELECT * 
                FROM `redcap_user_rights` 
                WHERE username = ?";

        $q = $this->module->query($sql,[$username]);
        if ($row = $q->fetch_assoc()) {
            return true;
        }
        return false;
    }

    private function isUserActive($personActive, $errorPemissionList): array
    {
        if($personActive == "0" || empty($personActive)){
            $errorPemissionList[] = "User is <strong>not active</strong>.";
        }
        return $errorPemissionList;
    }

    private function doesUserHaveHubAccess($personPermission, $errorPemissionList): array
    {
        if($personPermission == "0"){
            $errorPemissionList[] = "User has <strong>no Hub Access Permission</strong>.";
        }
        return $errorPemissionList;
    }

    private function decorateUserRegion($person): array
    {
        if($person['person_region'] != null){
            $params = [
                'project_id' => $this->getPidsArray()['REGIONS'],
                'return_format' => 'json-array',
                'records' => $person['person_region'],
                'fields' => [
                    'region_code'
                ]
            ];
            $personRegion = $this->module->escape(\REDCap::getData($params))[0];
            $regionCode = "";
            if(!empty($personRegion)){
                $regionCode = "(".$personRegion['region_code'].")";
            }
            $person['region_code'] = $regionCode;
        }
        return $person;
    }

    private function decorateAllUserLists($person, $errorPemissionList): void
    {
        if(empty($errorPemissionList)){
            $this->successUserList[] = $person;
        }else{
            $person['error_permission_list'] = $errorPemissionList;
            $this->errorUserList[] = $person;
        }
    }
}
?>
