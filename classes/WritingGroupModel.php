<?php

namespace Vanderbilt\HarmonistHubExternalModule;

use phpDocumentor\Reflection\Types\Boolean;
use REDCap;

include_once (__DIR__ . "/../autoload.php");

class WritingGroupModel extends Model
{
    private $concept;
    private $instance;
    private $writingGroupMember = [];
    private $authorshipLimit;

    public function __construct(HarmonistHubExternalModule $module, $projectId, $concept, $instance, $authorshipLimit)
    {
        $this->concept = $concept;
        $this->instance = $instance;
        $this->authorshipLimit = $authorshipLimit;
        parent::__construct($module,$projectId);
    }

    public function fecthWritingGroupFileName($hubName):string
    {
        $date = new \DateTime();
        return $hubName."_concept_".$this->concept['concept_id']."_writing_group_".$date->format('Y-m-d H:i:s');
    }

    public function fecthAllWritingGroup():array
    {
        $this->fecthWritingGroupCore();
        $this->fecthWritingGroupByResearchGroup();
        return $this->writingGroupMember;
    }

    private function fecthWritingGroupByResearchGroup():void
    {
        if(is_array($this->concept) && array_key_exists('gmember_role', $this->concept)){
            $params = [
                'project_id' => $this->getPidsArray()['REGIONS'],
                'return_format' => 'json-array',
                'records' => [$this->instance],
                'fields'=> ['region_name']
            ];
            $researchGroupName = \REDCap::getData($params)[0]['region_name'];
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

    private function fecthWritingGroupCore():void
    {
        if(is_array($this->concept) && array_key_exists('cmember_role', $this->concept)){
            $cmemberRole = $this->module->getChoiceLabels('cmember_role', $this->getPidsArray()['HARMONIST']);
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

    private function isHubContact($variable, $instance):bool
    {
        if($this->concept[$variable][$instance] != 1){
            return true;
        }
        return false;
    }

    private function isDataEmpty($variable, $instance):bool
    {
        if(is_array($this->concept[$variable]) && array_key_exists($this->instance, $this->concept[$variable]) && !empty($this->concept[$variable][$instance])){
            return false;
        }
        return true;
    }

    private function fetchHubContact($recordId):WritingGroupMember
    {
        $writingGroupMember = new WritingGroupMember();
        $params = [
            'project_id' => $this->getPidsArray()['PEOPLE'],
            'return_format' => 'json-array',
            'records' => [$recordId],
            'fields'=> ['email','firstname','lastname']
        ];
        $contactData = \REDCap::getData($params)[0];
        $writingGroupMember->setName($contactData['firstname'].' '.$contactData['lastname']);
        $writingGroupMember->setEmail($contactData['email']);
        return $writingGroupMember;
    }

    private function fetchNotHubMember($variableName, $instance, $researchGroupVar = ""):WritingGroupMember
    {
        $writingGroupMember = new WritingGroupMember();
        $writingGroupMember->setName($this->concept[$variableName.'_firstname'.$researchGroupVar][$instance].' '.$this->concept[$variableName.'_lastname'.$researchGroupVar][$instance]);
        $writingGroupMember->setEmail($this->concept[$variableName.'_email'.$researchGroupVar][$instance]);
        return $writingGroupMember;
    }

    private function fetchSurveyLink($surveyName,$instance):string
    {
        $passthru_link = $this->module->resetSurveyAndGetCodes($this->getPidsArray()['HARMONIST'], $this->concept['record_id'], $surveyName, "",$instance);
        $survey_link = APP_PATH_WEBROOT_FULL . "/surveys/?s=".$this->module->escape($passthru_link['hash']);
        return $survey_link;
    }
}

?>
