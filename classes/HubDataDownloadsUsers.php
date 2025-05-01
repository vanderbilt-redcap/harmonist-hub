<?php
namespace Vanderbilt\HarmonistHubExternalModule;

class HubDataDownloadsUsers extends Model
{
    private $successUserList = [];
    private $errorUserList = [];
    public function __construct(HarmonistHubExternalModule $module, $pidsArray)
    {
        parent::__construct($module, $pidsArray['PROJECTS']);
        $this->decorateUserList();
    }

    public function setErrorUserList($errorUserList): void
    {
        $this->errorUserList = $errorUserList;
    }

    public function getErrorUserList()
    {
        return $this->errorUserList;
    }

    public function setSuccessUserList($successUserList): void
    {
        $this->successUserList = $successUserList;
    }

    public function getSuccessUserList()
    {
        return $this->successUserList;
    }

    public function decorateUserList(): void
    {
        $params = [
            'project_id' => $this->getPidsArray()['PEOPLE'],
            'return_format' => 'json-array',
            'fields' => [
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
                if($person['allowgetdata_y___1'] == "1") {
                    if(!empty($person['redcap_name'])){
                        if(!$this->isUserInDataDownloads($person['redcap_name'])){
                            $errorPemissionList[] = "User has data downloads activated but is <strong>not in Data Downloads Project</strong>.";
                        }
                        if($this->isUserExpired($person['redcap_name'])){
                            $errorPemissionList[] = "Their <strong>REDCap account has expired</strong>.";
                        }
                        if(!$this->doesUserExistInREDCap($person['redcap_name'])){
                            $errorPemissionList[] = "User <strong>doesn't exist in REDCap</strong>.";
                        }
                    }else{
                        $errorPemissionList[] = "User has downloads activated but the username is empty.";
                    }
                    if(!$this->isUserActive($person['active_y'])){
                        $errorPemissionList[] = "User is <strong>not active</strong>.";
                    }
                    if(!$this->doesUserHaveHubAccess($person['harmonist_regperm'])){
                        $errorPemissionList[] = "User has <strong>no Hub Access Permission</strong>.";
                    }

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
                    if(empty($errorPemissionList)){
                        $this->successUserList[] = $person;
                    }else{
                        $person['error_permission_list'] = $errorPemissionList;
                        $this->errorUserList[] = $person;
                    }
                }
            }
        }
    }

    private function isUserInDataDownloads($username){
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

    private function isUserExpired($username){
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
    private function doesUserExistInREDCap($username):bool
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

    private function isUserActive($personActive):bool
    {
        if($personActive == "0" || empty($personActive)){
            return false;
        }
        return true;
    }

    private function doesUserHaveHubAccess($personPermission):bool
    {
        if($personPermission == "0"){
            return false;
        }
        return true;
    }
}
?>