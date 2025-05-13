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
                    $this->decorateAllUserLists($person, $people->fetchErrorPemissionList());
                }
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
