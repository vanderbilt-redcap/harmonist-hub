<?php

namespace Vanderbilt\HarmonistHubExternalModule;

use phpDocumentor\Reflection\Types\Boolean;
use REDCap;

class Concept
{
    private $pidsArray;
    private $conceptId;
    private $activeY;
    private $conceptTitle;
    private $contactLink;
    private $workingGroup;
    private $startDate;
    private $status;
    private $contact;
    private $wgLink;
    private $wg2Link;
    private $revisedY;
    private $ecApprovalD;
    private $conceptTags;
    private $tags;

    public function __construct($conceptData, $pidsArray)
    {
        $this->pidsArray = $pidsArray;
        $this->hydrateConcept($conceptData);
    }

    public function createWorkingGroup(): void
    {
        if (!empty($this->wgLink)) {
            $wgroup = \REDCap::getData($this->pidsArray['GROUP'], 'json-array', array('record_id' => $this->wgLink))[0];
        }

        if (!empty($this->wg2Link)) {
            $wgroup2 = \REDCap::getData($this->pidsArray['GROUP'], 'json-array', array('record_id' =>  $this->wg2Link))[0];
        }

        $this->workingGroup = "<em>Not specified</em>";
        if(!empty($wgroup['group_name'])){
            $groupNameTotal = $wgroup['group_name'];
            if(!empty($wgroup2['group_name'])){
                $this->workingGroup = $groupNameTotal.', '.$wgroup2['group_name'];
            }
        }else  if(!empty($wgroup2['group_name'])){
            $this->workingGroup = $wgroup2['group_name'];
        }
    }

    public function createStartDate(): void
    {
        $this->startDate = (empty($this->ecApprovalD))? "<em>Not specified</em>" : $this->ecApprovalD;
    }

    public function createStatus(): void
    {
        if($this->activeY == "Y"){
            $active = "Active";
            $activeColorButton = "text-button-approved";
        }else{
            $active = "Inactive";
            $activeColorButton = "text-button-error";
        }

        $revised = "";
        if($this->revisedY == '1'){
            $revised = '<span class="label label-as-badge badge-revised">Revised</span>';
        }

        $this->status = '<span class="label label-as-badge '.$activeColorButton.'">'.$active.'</span> '.$revised;
    }

    public function createContact(): void
    {
        $this->contact = "<em>Not specified</em>";
        if (!empty($this->contactLink)) {
            $personInfo = \REDCap::getData($this->pidsArray['PEOPLE'], 'json-array', array('record_id' => $this->contactLink))[0];
            if (!empty($personInfo)) {
                $nameConcept = '<a href="mailto:'.$personInfo['email'].'">'.$personInfo['firstname'] . ' ' . $personInfo['lastname'];
                if(!empty($person_info['person_region'])){
                    $person_region = \REDCap::getData($this->pidsArray['REGIONS'], 'json-array', array('record_id' => $personInfo['person_region']))[0];
                    if(!empty($person_region)){
                        $nameConcept .= " (".$person_region['region_code'].")";
                    }
                }
                $nameConcept .= '</a>';
                $this->contact = $nameConcept;
            }
        }
    }

    public function createTags(){
        $tags = "";
        foreach ($this->conceptTags as $tag=>$value){
            if($value == 1) {
                $tags .= $tag.",";
            }
        }
        $this->tags = htmlspecialchars($tags,ENT_QUOTES);
    }

    public function getTags(): string
    {
        return $this->tags;
    }

    public function getStartDate(): string
    {
        return $this->startDate;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getContact(): string
    {
        return $this->contact;
    }

    public function getConceptId(): string
    {
        return $this->conceptId;
    }
    public function getConceptTitle(): string
    {
        return $this->conceptTitle;
    }

    public function getWorkingGroup(): string
    {
        return $this->workingGroup;
    }

    public function getActiveY(): string
    {
        return $this->activeY;
    }

    public function getContactLink(): string
    {
        return $this->contactLink;
    }

    public function getWgLink(): string
    {
        return $this->wgLink;
    }

    public function getWg2Link(): string
    {
        return $this->wg2Link;
    }

    public function getRevisedY(): string
    {
        return $this->revisedY;
    }

    public function getEcApprovalD(): string
    {
        return $this->ecApprovalD;
    }

    private function hydrateConcept($conceptData){
        $this->conceptId = $conceptData['concept_id'];
        $this->activeY = $conceptData['active_y'];
        $this->conceptTitle = $conceptData['concept_title'];
        $this->contactLink = $conceptData['contact_link'];
        $this->wgLink = $conceptData['wg_link'];
        $this->wg2Link = $conceptData['wg2_link'];
        $this->revisedY = $conceptData['revised_y'][0];
        $this->ecApprovalD = $conceptData['ec_approval_d'];
        $this->conceptTags = $conceptData['concept_tags'];
        $this->createWorkingGroup();
        $this->createStartDate();
        $this->createStatus();
        $this->createContact();
        $this->createTags();
    }
}

?>
