<?php
namespace Vanderbilt\HarmonistHubExternalModule;

class HubDataDownloadsUsers extends Model
{
    private $successUserList = [];
    private $errorUserList = [];

    private $peopleUser;

    public function __construct(HarmonistHubExternalModule $module, $peoplePid)
    {
        $hub_mapper = $module->getProjectSetting('hub-mapper', $peoplePid);
        parent::__construct($module, $hub_mapper);
        $this->loadUserList();
    }

    public function setSuccessUserList($successUserList): void
    {
        $this->successUserList = $successUserList;
    }

    public function getSuccessUserList(): array
    {
        return $this->successUserList;
    }

    public function getErrorUserList(): array
    {
        return $this->errorUserList;
    }

    public function setErrorUserList(array $errorUserList): void
    {
        $this->errorUserList = $errorUserList;
    }

    public function loadUserList(): void
    {
        $params = [
            'project_id' => $this->getPidsArray()['PEOPLE'],
            'return_format' => 'json-array'
        ];
        $peopleData = $this->module->escape(\REDCap::getData($params));
        $people = null;
        if (!empty($peopleData)) {
            foreach ($peopleData as $person) {
                if($this->canUserDownloadData($person['allowgetdata_y___1'])) {
                    $people = new People($person, $this->module, $this->getPidsArray()['PROJECTS']);
                    $this->decorateAllUserLists($people);
                }
            }
        }
    }

    public function fetchPeopleErrorUser($userId, $username="", $missing=false): ?People
    {
        if(!empty($this->errorUserList)) {
            foreach ($this->errorUserList as $index => $user) {
                if ($user->getRecordId() == $userId) {
                    if ($missing && !empty($username)) {
                        $user->setRedcapName($username);
                        $user->addUsernameOnProject();
                    }
                    $user->setRedcapName('bascome');
                    $this->peopleUser = $user;
                    return $user;
                }
            }
        }
        return null;
    }


    private function canUserDownloadData($personDownload): bool
    {
        if ($personDownload == "1") {
            return true;
        }
        return false;
    }

    private function decorateAllUserLists($people): void
    {
        if(empty($people->fetchErrorPemissionList())){
            $this->successUserList[] = $people;
        }else{
            $this->errorUserList[] = $people;
        }
    }
}
?>
