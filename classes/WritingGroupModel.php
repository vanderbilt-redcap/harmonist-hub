<?php

namespace Vanderbilt\HarmonistHubExternalModule;

use phpDocumentor\Reflection\Types\Boolean;
use REDCap;

include_once (__DIR__ . "/../autoload.php");

class WritingGroupModel extends Model
{
    private $module;
    private $projectId;
    private $concept;
    private $instance;
    private $pidsArray = [];
    private $writingGroupMember = [];
    private $authorshipLimit;

    public function __construct(HarmonistHubExternalModule $module, $projectId, $concept, $instance, $authorshipLimit)
    {
        $this->module = $module;
        $this->projectId = $projectId;
        $this->concept = $concept;
        $this->instance = $instance;
        $this->authorshipLimit = $authorshipLimit;
        parent::__construct($module,$projectId);
        $this->pidsArray = $this->getPidsArray();
    }

    public function fecthAllWritingGroup():array
    {
        $this->fecthWritingGroupCore();
        $this->fecthWritingGroupByResearchGroup();
        return $this->writingGroupMember;
    }

    public function fecthWritingGroupByResearchGroup():void
    {
        if(is_array($this->concept) && array_key_exists('gmember_role', $this->concept)){
            $researchGroupName = \REDCap::getData($this->pidsArray['REGIONS'], 'json-array', array('record_id' => $this->instance),array('region_name'))[0]['region_name'];
            for($i = 1; $i < ((int)$this->authorshipLimit+1) ; $i++){
                $saveData = false;
                if($this->isHubContact('gmember_nh_'.$i, $this->instance)){
                    #Hub Contact
                    if(!$this->isDataEmpty('gmember_link_'.$i, $this->instance)) {
                        $saveData = true;
                        $writingGroupMember = $this->fetchHubContact($this->concept['gmember_link_' . $i][$this->instance]);
                    }
                }else{
                     #Not a Hub Member
                     if(!$this->isDataEmpty('gmember_firstname_'.$i, $this->instance)) {
                         $saveData = true;
                         $writingGroupMember = $this->fetchNotHubMember( 'gmember', $this->instance, "_".$i);
                     }
                }
                if($saveData){
                    $writingGroupMember->setRole($researchGroupName);
                    $writingGroupMember->setEditLink($this->fetchSurveyLink("writing_group_by_research_group",$this->instance));
                    $this->writingGroupMember[] = $writingGroupMember;
                }
            }
        }
    }

    public function fecthWritingGroupCore():void
    {
        if(is_array($this->concept) && array_key_exists('cmember_role', $this->concept)){
            $cmemberRole = $this->module->getChoiceLabels('cmember_role', $this->pidsArray['HARMONIST']);
            foreach($this->concept['cmember_role'] as $instance => $role){
                if($this->isHubContact('cmember_nh', $instance)){
                    #Hub Contact
                    $writingGroupMember = $this->fetchHubContact($this->concept['cmember_link'][$instance]);
                }else{
                    #Not a Hub Member
                    $writingGroupMember = $this->fetchNotHubMember('cmember', $instance);
                }
                $writingGroupMember->setRole($cmemberRole[$this->concept['cmember_role'][$instance]]);
                $writingGroupMember->setEditLink($this->fetchSurveyLink("writing_group_core",$instance));
                $this->writingGroupMember[] = $writingGroupMember;
            }
        }
    }

    public function isHubContact($variable, $instance):bool
    {
        if($this->concept[$variable][$instance] != 1){
            return true;
        }
        return false;
    }

    public function isDataEmpty($variable, $instance):bool
    {
        if(is_array($this->concept[$variable]) && array_key_exists($this->instance, $this->concept[$variable]) && !empty($this->concept[$variable][$instance])){
            return false;
        }
        return true;
    }

    public function fetchHubContact($recordId):WritingGroupMember
    {
        $writingGroupMember = new WritingGroupMember();
        $contactData = \REDCap::getData($this->pidsArray['PEOPLE'], 'json-array', array('record_id' => $recordId),array('email','firstname','lastname'))[0];
        $writingGroupMember->setName($contactData['firstname'].' '.$contactData['lastname']);
        $writingGroupMember->setEmail($contactData['email']);
        return $writingGroupMember;
    }

    public function fetchNotHubMember($variableName, $instance, $researchGroupVar = ""):WritingGroupMember
    {
        $writingGroupMember = new WritingGroupMember();
        $writingGroupMember->setName($this->concept[$variableName.'_firstname'.$researchGroupVar][$instance].' '.$this->concept[$variableName.'_lastname'.$researchGroupVar][$instance]);
        $writingGroupMember->setEmail($this->concept[$variableName.'_email'.$researchGroupVar][$instance]);
        return $writingGroupMember;
    }

    public function fetchSurveyLink($surveyName,$instance):string
    {
        $passthru_link = $this->module->resetSurveyAndGetCodes($this->pidsArray['HARMONIST'], $this->concept['record_id'], $surveyName, "",$instance);
        $survey_link = APP_PATH_WEBROOT_FULL . "/surveys/?s=".$this->module->escape($passthru_link['hash']);
        return $survey_link;
    }
}

?>
