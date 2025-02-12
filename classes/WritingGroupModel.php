<?php

namespace Vanderbilt\HarmonistHubExternalModule;

use phpDocumentor\Reflection\Types\Boolean;
use REDCap;

include_once(__DIR__ . "/WritingGroup.php");

class WritingGroupModel
{
    private $module;
    private $projectId;
    private $pidsArray = [];
    private $writingGroup = [];

    public function __construct(HarmonistHubExternalModule $module, $projectId)
    {
        $this->module = $module;
        $this->projectId = $projectId;
        $this->pidsArray = self::getPidsArray();
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

    public function fecthAllWritingGroup($concept, $researchGroup):array
    {
        self::fecthWritingGroupCore($concept);
        self::fecthWritingGroupByResearchGroup($concept, $researchGroup);
        print_array($this->writingGroup);
        return $this->writingGroup;
    }

    public function fecthWritingGroupByResearchGroup($concept, $researchGroup):void
    {
        if(is_array($concept) && array_key_exists('gmember_role', $concept)){
            $authorshipLimit = \REDCap::getData($this->pidsArray['SETTINGS'], 'json-array', array('record_id' => 1),array('authorship_limit'))[0]['authorship_limit'];
            for($i = 1; $i < ((int)$authorshipLimit+1) ; $i++){
                $writingGroup = new WrittingGroup();
                $saveData = false;
                if($concept['gmember_nh_'.$i][$researchGroup] != 1){
                    #Hub Contact
                    if(is_array($concept['gmember_link_'.$i]) && array_key_exists($researchGroup, $concept['gmember_link_'.$i]) && !empty($concept['gmember_link_'.$i][$researchGroup])) {
                        $saveData = true;
                        $writingGroup = self::saveHubContact($writingGroup, $concept['gmember_link_' . $i][$researchGroup]);
                    }
                }else{
                     #Not a Hub Member
                     if(is_array($concept['gmember_firstname_'.$i]) && array_key_exists($researchGroup, $concept['gmember_firstname_'.$i]) && !empty($concept['gmember_firstname_'.$i][$researchGroup])) {
                         $saveData = true;
                         $writingGroup = self::saveNotHubMember($writingGroup, $concept, 'gmember', $researchGroup);
                     }
                }
                if($saveData){
                    $researchGroupName = \REDCap::getData($this->pidsArray['REGION'], 'json-array', array('record_id' => $researchGroup),array('region_name'))[0];
                    $writingGroup->setRole($researchGroupName);
                    $this->writingGroup[] = $writingGroup;
                }

            }
        }
    }

    public function fecthWritingGroupCore($concept):void
    {
        if(is_array($concept) && array_key_exists('cmember_role', $concept)){
            $cmemberRole = $this->module->getChoiceLabels('cmember_role', $this->pidsArray['HARMONIST']);
            foreach($concept['cmember_role'] as $instance => $role){
                $writingGroup = new WrittingGroup();
                if($concept['cmember_nh'][$instance] != 1){
                    #Hub Contact
                    $writingGroup = self::saveHubContact($writingGroup, $concept['cmember_link'][$instance]);
                }else{
                    #Not a Hub Member
                    $writingGroup = self::saveNotHubMember($writingGroup, $concept, 'cmember', $instance);
                }
                $writingGroup->setRole($cmemberRole[$concept['cmember_role'][$instance]]);
                $this->writingGroup[] = $writingGroup;
            }
        }
    }

    public function saveHubContact($writingGroup,$recordId):WrittingGroup
    {
        $contactData = \REDCap::getData($this->pidsArray['PEOPLE'], 'json-array', array('record_id' => $recordId),array('email','firstname','lastname'))[0];
        $writingGroup->setName($contactData['firstname'].' '.$contactData['lastname']);
        $writingGroup->setEmail($contactData['email']);
        return $writingGroup;
    }

    public function saveNotHubMember($writingGroup, $concept, $variableName, $instance):WrittingGroup
    {
        $writingGroup->setName($concept[$variableName.'_firstname'][$instance].' '.$concept[$variableName.'_lastname'][$instance]);
        $writingGroup->setEmail($concept[$variableName.'_email'][$instance]);
        return $writingGroup;
    }
}

?>
