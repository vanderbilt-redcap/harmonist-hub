<?php

namespace Vanderbilt\HarmonistHubExternalModule;

use phpDocumentor\Reflection\Types\Boolean;
use REDCap;

include_once (__DIR__ . "/../autoload.php");

class WritingGroupModel extends Model
{
    private Concept $concept;
    private $instance;
    private $writingGroupMember = [];

    public function __construct(HarmonistHubExternalModule $module, $projectId, Concept $concept, $instance)
    {
        $this->concept = $concept;
        $this->instance = $instance;
        parent::__construct($module,$projectId);
    }

    public function fetchWritingGroupFileName($hubName):string
    {
        $date = new \DateTime();
        return $hubName."_concept_".$this->concept->getConceptId()."_writing_group_".$date->format('Y-m-d H:i:s');
    }

    public function fetchAllWritingGroup():array
    {
        $this->fetchWritingGroupCore();
        $this->fetchWritingGroupByResearchGroup();
        return $this->writingGroupMember;
    }

    private function fetchWritingGroupByResearchGroup():void
    {
        $params = [
            'project_id' => $this->getPidsArray()['REGIONS'],
            'return_format' => 'json-array',
            'records' => [$this->concept->getGmemberRole()],
            'fields'=> ['region_name']
        ];
        print_array("fetchWritingGroupByResearchGroup");
        print_array("getGmemberRole(): ".$this->concept->getGmemberRole());
        $researchGroupName = \REDCap::getData($params)[0]['region_name'];
        print_array("researchGroupName: ".$researchGroupName);
        for($i = 1; $i < ((int)$this->concept->getAuthorshipLimit()+1) ; $i++){
            $saveData = false;
            if($this->isHubContact($this->concept->getGmemberNh()[$i], $this->instance)){
                #Hub Contact
                print_array("HUB");
                print_array($this->concept->getGmemberLink()[$i][$this->instance]);
                print_array("i: ".$i);
                print_array("instance: ".$this->instance);
                if(!empty($this->concept->getGmemberLink()[$i][$this->instance])) {
                    print_array("IN");
                    $saveData = true;
                    $writingGroupMember = $this->fetchHubContact($this->concept->getGmemberLink()[$i][$this->instance]);
                }
            }else{
                 #Not a Hub Member
                print_array("Not a Hub Member");
                 if(!empty($this->concept->getGmemberFirstname()[$i][$this->instance])) {
                     print_array("IN");
                     $saveData = true;
                     $writingGroupMember = $this->fetchNotHubMember( 'gmember', $this->instance, $i);
                 }
            }
            if($saveData){
                $writingGroupMember->setRole($researchGroupName);
                $writingGroupMember->setEditLink($this->fetchSurveyLink($this->pidsArray['HARMONIST'], $this->concept->getRecordId(), "writing_group_by_research_group",$this->instance));
                $this->writingGroupMember[] = $writingGroupMember;
            }
        }
    }

    private function fetchWritingGroupCore():void
    {
        $cmemberRole = $this->module->getChoiceLabels('cmember_role', $this->getPidsArray()['HARMONIST']);
        foreach($this->concept->getCmemberRole() as $instance => $role){
            if($this->isHubContact($this->concept->getCmemberNh(), $instance)){
                #Hub Contact
                $writingGroupMember = $this->fetchHubContact($this->concept->getCmemberLink()[$instance]);
            }else{
                #Not a Hub Member
                $writingGroupMember = $this->fetchNotHubMember('cmember', $instance);
            }
            $writingGroupMember->setRole($cmemberRole[$role]);
            $writingGroupMember->setEditLink($this->fetchSurveyLink($this->pidsArray['HARMONIST'], $this->concept->getRecordId(),"writing_group_core",$instance));
            $this->writingGroupMember[] = $writingGroupMember;
        }
    }

    private function isHubContact($variable, $instance):bool
    {
        if($variable[$instance] != 1){
            return true;
        }
        return false;
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

        $firstName = (empty($researchGroupVar)) ? call_user_func(array( $this->concept, "get".ucfirst($variableName)."Firstname"))[$instance] : call_user_func(array( $this->concept, "get".ucfirst($variableName)."Firstname"))[$researchGroupVar][$instance];
        $lastName = (empty($researchGroupVar)) ? call_user_func(array( $this->concept, "get".ucfirst($variableName)."Lastname"))[$instance] : call_user_func(array( $this->concept, "get".ucfirst($variableName)."Lastname"))[$researchGroupVar][$instance];
        $email = (empty($researchGroupVar)) ? call_user_func(array( $this->concept, "get".ucfirst($variableName)."Email"))[$instance] : call_user_func(array( $this->concept, "get".ucfirst($variableName)."Email"))[$researchGroupVar][$instance];
        $writingGroupMember->setName($firstName.' '.$lastName);
        $writingGroupMember->setEmail($email);
        return $writingGroupMember;
    }
}

?>
