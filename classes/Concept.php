<?php

namespace Vanderbilt\HarmonistHubExternalModule;

use phpDocumentor\Reflection\Types\Boolean;
use REDCap;

class Concept
{
    private $pidsArray;
    private $conceptId;
    private $active_y;
    private $conceptTitle;
    private $contact_link;
    private $workingGroup;
    private $startDate;
    private $status;
    private $contact;
    private $WgLink;
    private $Wg2Link;
    private $revised_y;
    private $ec_approval_d;

    public function __construct($conceptData, $pidsArray)
    {
        $this->pidsArray = $pidsArray;
        self::createConcept($conceptData);
    }

    private function createConcept($conceptData){
        $this->conceptId = $conceptData['concept_id'];
        $this->active_y = $conceptData['active_y'];
        $this->conceptTitle = $conceptData['concept_title'];
        $this->contact_link = $conceptData['contact_link'];
        $this->WgLink = $conceptData['wg_link'];
        $this->Wg2Link = $conceptData['wg2_link'];
        $this->revised_y = $conceptData['revised_y'][0];
        $this->ec_approval_d = $conceptData['ec_approval_d'];
        self::createWorkingGroup();
        self::createStartDate();
        self::createStatus();
        self::createContact();
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

    public function createWorkingGroup(): void
    {
        if (!empty($this->WgLink)) {
            $wgroup = \REDCap::getData($this->pidsArray['GROUP'], 'json-array', array('record_id' => $this->WgLink))[0];
        }

        if (!empty($this->Wg2Link)) {
            $wgroup2 = \REDCap::getData($this->pidsArray['GROUP'], 'json-array', array('record_id' =>  $this->Wg2Link))[0];
        }

        $this->workingGroup = "<em>Not specified</em>";
        if(!empty($wgroup['group_name'])){
            $group_name_total = $wgroup['group_name'];
            if(!empty($wgroup2['group_name'])){
                $this->workingGroup = $group_name_total.', '.$wgroup2['group_name'];
            }
        }else  if(!empty($wgroup2['group_name'])){
            $this->workingGroup = $wgroup2['group_name'];
        }
    }

    public function getStartDate(): string
    {
        return $this->startDate;
    }

    public function createStartDate(): void
    {
        $this->startDate = (empty($this->ec_approval_d))? "<em>Not specified</em>" : $this->ec_approval_d;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function createStatus(): void
    {
        if($this->active_y == "Y"){
            $active = "Active";
            $active_color_button = "text-button-approved";
        }else{
            $active = "Inactive";
            $active_color_button = "text-button-error";
        }

        $revised = "";
        if($this->revised_y == '1'){
            $revised = '<span class="label label-as-badge badge-revised">Revised</span>';
        }

        $this->status = '<span class="label label-as-badge '.$active_color_button.'">'.$active.'</span> '.$revised;
    }

    public function getContact(): string
    {
        return $this->contact;
    }

    public function createContact(): void
    {
        $id_people = $this->contact_link;
        $this->contact = "<em>Not specified</em>";
        if (!empty($id_people)) {
            $person_info = \REDCap::getData($this->pidsArray['PEOPLE'], 'json-array', array('record_id' => $id_people))[0];
            if (!empty($person_info)) {
                $name_concept = '<a href="mailto:'.$person_info['email'].'">'.$person_info['firstname'] . ' ' . $person_info['lastname'];
                if(!empty($person_info['person_region'])){
                    $person_region = \REDCap::getData($this->pidsArray['REGIONS'], 'json-array', array('record_id' => $person_info['person_region']))[0];
                    if(!empty($person_region)){
                        $name_concept .= " (".$person_region['region_code'].")";
                    }
                }
                $name_concept .= '</a>';
                $this->contact = $name_concept;
            }
        }
    }
}

?>
