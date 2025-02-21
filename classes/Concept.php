<?php

namespace Vanderbilt\HarmonistHubExternalModule;

use phpDocumentor\Reflection\Types\Boolean;
use PhpParser\Node\Expr\AssignOp\Mod;
use REDCap;

class Concept extends Model
{
    private $recordId;
    private $conceptId;
    private $activeY;
    private $conceptTitle;
    private $conceptTags;
    private $contactLink;
    private $contact2Link;
    private $workingGroup;
    private $startDate;
    private $status;
    private $contact;
    private $wgLink;
    private $wg2Link;
    private $revisedY;
    private $ecApprovalD;
    private $tags;
    private $outputType;
    private $participantsComplete;
    private $participants;
    private $personLink = [];
    private $personRole;
    private $personOther = [];
    private $adminUpdate;
    private $adminStatus;
    private $adminupdateD;
    private $updateD;
    private $projectStatus;
    private $conceptFile;
    private $outputYear;
    private $outputTitle;
    private $outputDescription;
    private $outputVenue;
    private $outputPmcid;
    private $outputCitation;
    private $outputFile;
    private $outputUrl;
    private $outputAuthors;
    private $docFile;
    private $docTitle;
    private $docDescription;
    private $dochiddenY;
    private $docuploadDt;
    private $datasopFile;
    private $conceptData;
    private $authorshipLimit;
    private $gmember_role;
    private $gmember_nh;
    private $gmember_link;
    private $gmember_firstname;
    private $gmember_lastname;
    private $gmember_email;
    private $cmember_role;
    private $cmember_nh;
    private $cmember_link;
    private $cmember_firstname;
    private $cmember_lastname;
    private $cmember_email;

    public function __construct($conceptData, HarmonistHubExternalModule $module, $pidsArray, $authorshipLimit)
    {
        parent::__construct($module, $pidsArray['PROJECTS']);
        $this->conceptData = $conceptData;
        $this->authorshipLimit = $authorshipLimit;
        $this->hydrateConcept();
    }

    public function createConceptFile($edoc, $currentUserId, $secret_key, $secret_iv)
    {
        return new File($edoc, $this->module, $currentUserId, $secret_key, $secret_iv);
    }

    public function canUserEdit($current_user): bool
    {
        if ($this->getContactLink() == $current_user ||
            $this->getContact2Link() == $current_user ||
            $this->isHarmonistAdmin($current_user, $this->pidsArray['PEOPLE'], $this->module)
        ) {
            return true;
        }
        return false;
    }

    public function getRecordId()
    {
        return $this->recordId;
    }

    public function setRecordId($recordId): void
    {
        $this->recordId = $recordId;
    }

    public function getAuthorshipLimit()
    {
        return $this->authorshipLimit;
    }

    public function setAuthorshipLimit($authorshipLimit): void
    {
        $this->authorshipLimit = $authorshipLimit;
    }

    public function getDatasopFile()
    {
        return $this->datasopFile;
    }

    public function setDatasopFile($datasopFile): void
    {
        $this->datasopFile = $datasopFile;
    }

    public function getOutputDescription()
    {
        return $this->outputDescription;
    }

    public function setOutputDescription($outputDescription): void
    {
        $this->outputDescription = $outputDescription;
    }

    public function getOutputTitle()
    {
        return $this->outputTitle;
    }

    public function setOutputTitle($outputTitle): void
    {
        $this->outputTitle = $outputTitle;
    }

    public function setOutputAuthors($outputAuthors): void
    {
        $this->outputAuthors = $outputAuthors;
    }

    public function getOutputAuthors()
    {
        return $this->outputAuthors;
    }

    public function getConceptFile()
    {
        return $this->conceptFile;
    }

    public function setConceptFile($conceptFile): void
    {
        $this->conceptFile = $conceptFile;
    }

    public function getOutputYear()
    {
        return $this->outputYear;
    }

    public function setOutputYear($outputYear): void
    {
        $this->outputYear = $outputYear;
    }

    public function getOutputVenue()
    {
        return $this->outputVenue;
    }

    public function setOutputVenue($outputVenue): void
    {
        $this->outputVenue = $outputVenue;
    }

    public function getOutputPmcid()
    {
        return $this->outputPmcid;
    }

    public function setOutputPmcid($outputPmcid): void
    {
        $this->outputPmcid = $outputPmcid;
    }

    public function getOutputCitation()
    {
        return $this->outputCitation;
    }

    public function setOutputCitation($outputCitation): void
    {
        $this->outputCitation = $outputCitation;
    }

    public function getOutputFile()
    {
        return $this->outputFile;
    }

    public function setOutputFile($outputFile): void
    {
        $this->outputFile = $outputFile;
    }

    public function getOutputUrl()
    {
        return $this->outputUrl;
    }

    public function setOutputUrl($outputUrl): void
    {
        $this->outputUrl = $outputUrl;
    }

    public function getDocFile()
    {
        return $this->docFile;
    }

    public function gsetDocFile($docFile): void
    {
        $this->docFile = $docFile;
    }

    public function getDocTitle()
    {
        return $this->docTitle;
    }

    public function setDocTitle($docTitle): void
    {
        $this->docTitle = $docTitle;
    }

    public function getDocDescription()
    {
        return $this->docDescription;
    }

    public function setDocDescription($docDescription): void
    {
        $this->docDescription = $docDescription;
    }

    public function getDochiddenY()
    {
        return $this->dochiddenY;
    }

    public function setDochiddenY($dochiddenY): void
    {
        $this->dochiddenY = $dochiddenY;
    }

    public function getDocuploadDt()
    {
        return $this->docuploadDt;
    }

    public function setDocuploadDt($docuploadDt): void
    {
        $this->docuploadDt = $docuploadDt;
    }

    public function getProjectStatus()
    {
        return $this->projectStatus;
    }

    public function setProjectStatus($projectStatus): void
    {
        $this->projectStatus = $projectStatus;
    }

    public function getAdminUpdate()
    {
        return $this->adminUpdate;
    }

    public function setAdminUpdate($adminUpdate): void
    {
        $this->adminUpdate = $adminUpdate;
    }

    public function getAdminupdateD()
    {
        return $this->adminupdateD;
    }

    public function setAdminupdateD($adminupdateD): void
    {
        $this->adminupdateD = $adminupdateD;
    }

    public function getUpdateD()
    {
        return $this->updateD;
    }

    public function setUpdateD($updateD): void
    {
        $this->updateD = $updateD;
    }

    public function getAdminStatus()
    {
        return $this->adminStatus;
    }

    public function setAdminStatus($adminStatus): void
    {
        $this->adminStatus = $adminStatus;
    }

    public function getOutputType()
    {
        return $this->outputType;
    }

    public function setOutputType($outputType): void
    {
        $this->outputType = $outputType;
    }

    public function getParticipantsComplete()
    {
        return $this->participantsComplete;
    }

    public function setParticipantsComplete($participantsComplete): void
    {
        $this->participantsComplete = $participantsComplete;
    }

    public function getParticipants()
    {
        return $this->participants;
    }

    public function setParticipants($participants): void
    {
        $this->participants = $participants;
    }

    public function getPersonLink(): array
    {
        return $this->personLink;
    }

    public function setPersonLink(array $personLink): void
    {
        $this->personLink = $personLink;
    }

    public function getPersonOther(): array
    {
        return $this->personOther;
    }

    public function setPersonOther(array $personOther): void
    {
        $this->personOther = $personOther;
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

    public function getContact2Link(): string
    {
        return $this->contact2Link;
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

    public function getConceptTags()
    {
        return $this->conceptTags;
    }

    public function setConceptTags($conceptTags): void
    {
        $this->conceptTags = $conceptTags;
    }

    public function getGmemberRole()
    {
        return $this->gmember_role;
    }

    public function setGmemberRole($gmember_role): void
    {
        $this->gmember_role = $gmember_role;
    }

    public function getGmemberNh()
    {
        return $this->gmember_nh;
    }

    public function setGmemberNh($gmember_nh): void
    {
        $this->gmember_nh = $gmember_nh;
    }

    public function getGmemberLink()
    {
        return $this->gmember_link;
    }

    public function setGmemberLink($gmember_link): void
    {
        $this->gmember_link = $gmember_link;
    }

    public function getGmemberFirstname()
    {
        return $this->gmember_firstname;
    }

    public function setGmemberFirstname($gmember_firstname): void
    {
        $this->gmember_firstname = $gmember_firstname;
    }

    public function getGmemberLastname()
    {
        return $this->gmember_lastname;
    }

    public function setGmemberLastname($gmember_lastname): void
    {
        $this->gmember_lastname = $gmember_lastname;
    }

    public function getGmemberEmail()
    {
        return $this->gmember_email;
    }

    public function setGmemberEmail($gmember_email): void
    {
        $this->gmember_email = $gmember_email;
    }

    public function getCmemberRole()
    {
        return $this->cmember_role;
    }

    public function setCmemberRole($cmember_role): void
    {
        $this->cmember_role = $cmember_role;
    }

    public function getCmemberNh()
    {
        return $this->cmember_nh;
    }

    public function setCmemberNh($cmember_nh): void
    {
        $this->cmember_nh = $cmember_nh;
    }

    public function getCmemberLink()
    {
        return $this->cmember_link;
    }

    public function setCmemberLink($cmember_link): void
    {
        $this->cmember_link = $cmember_link;
    }

    public function getCmemberFirstname()
    {
        return $this->cmember_firstname;
    }

    public function setCmemberFirstname($cmember_firstname): void
    {
        $this->cmember_firstname = $cmember_firstname;
    }

    public function getCmemberLastname()
    {
        return $this->cmember_lastname;
    }

    public function setCmemberLastname($cmember_lastname): void
    {
        $this->cmember_lastname = $cmember_lastname;
    }

    public function getCmemberEmail()
    {
        return $this->cmember_email;
    }

    public function setCmemberEmail($cmember_email): void
    {
        $this->cmember_email = $cmember_email;
    }

    private function hydrateConcept()
    {
        $this->recordId = $this->conceptData['record_id'];
        $this->conceptId = $this->conceptData['concept_id'];
        $this->activeY = $this->conceptData['active_y'];
        $this->conceptTitle = $this->conceptData['concept_title'];
        $this->conceptTags = $this->conceptData['concept_tags'];
        $this->contactLink = $this->conceptData['contact_link'];
        $this->contact2Link = $this->conceptData['contact2_link'];
        $this->wgLink = $this->conceptData['wg_link'];
        $this->wg2Link = $this->conceptData['wg2_link'];
        $this->revisedY = $this->conceptData['revised_y'][0];
        $this->ecApprovalD = $this->conceptData['ec_approval_d'];
        $this->participantsComplete = $this->conceptData['participants_complete'];
        $this->personLink = $this->conceptData['person_link'];
        $this->personRole = $this->conceptData['person_role'];
        $this->personOther = $this->conceptData['person_lother'];
        $this->adminUpdate = $this->conceptData['admin_update'];
        $this->adminupdateD = $this->conceptData['adminupdate_d'];
        $this->adminStatus = $this->conceptData['admin_status'];
        $this->updateD = $this->conceptData['update_d'];
        $this->projectStatus = $this->conceptData['project_status'];
        $this->conceptFile = $this->conceptData['concept_file'];
        $this->outputYear = $this->conceptData['output_year'];
        $this->outputTitle = $this->conceptData['output_title'];
        $this->outputDescription = $this->conceptData['output_description'];
        $this->outputType = $this->conceptData['output_type'];
        $this->outputVenue = $this->conceptData['output_venue'];
        $this->outputPmcid = $this->conceptData['output_pmcid'];
        $this->outputCitation = $this->conceptData['output_citation'];
        $this->outputFile = $this->conceptData['output_file'];
        $this->outputUrl = $this->conceptData['output_url'];
        $this->outputAuthors = $this->conceptData['output_authors'];
        $this->docFile = $this->conceptData['doc_file'];
        $this->docTitle = $this->conceptData['doc_title'];
        $this->docDescription = $this->conceptData['doc_description'];
        $this->dochiddenY = $this->conceptData['dochidden_y'];
        $this->docuploadDt = $this->conceptData['docupload_dt'];
        $this->datasopFile = $this->conceptData['datasop_file'];
        $this->decorateWorkingGroup();
        $this->decorateStartDate();
        $this->decorateStatus();
        $this->decorateContact();
        $this->decorateParticipants();
        $this->decorateTags();

        if($this->authorshipLimit != null) {
            $this->decorateWritingGroupCore();
            $this->decorateWritingGroupByResearchGroup();
        }
    }

    private function decorateWorkingGroup(): void
    {
        if (!empty($this->wgLink)) {
            $params = [
                'project_id' => $this->getPidsArray()['GROUP'],
                'return_format' => 'json-array',
                'records' => [$this->wgLink],
                'fields' => ['group_name']
            ];
            $wgroup = REDCap::getData($params)[0];
        }

        if (!empty($this->wg2Link)) {
            $params = [
                'project_id' => $this->getPidsArray()['GROUP'],
                'return_format' => 'json-array',
                'records' => [$this->wg2Link],
                'fields' => ['group_name']
            ];
            $wgroup2 = REDCap::getData($params)[0];
        }

        $this->workingGroup = "<em>Not specified</em>";
        if (!empty($wgroup['group_name'])) {
            $groupNameTotal = $wgroup['group_name'];
            if (!empty($wgroup2['group_name'])) {
                $this->workingGroup = $groupNameTotal . ', ' . $wgroup2['group_name'];
            }
        } else {
            if (!empty($wgroup2['group_name'])) {
                $this->workingGroup = $wgroup2['group_name'];
            }
        }
    }

    public function decorateStartDate(): void
    {
        $this->startDate = (empty($this->ecApprovalD)) ? "<em>Not specified</em>" : $this->ecApprovalD;
    }

    private function decorateStatus(): void
    {
        if ($this->activeY == "Y") {
            $active = "Active";
            $activeColorButton = "text-button-approved";
        } else {
            $active = "Inactive";
            $activeColorButton = "text-button-error";
        }

        $revised = "";
        if ($this->revisedY == '1') {
            $revised = '<span class="label label-as-badge badge-revised">Revised</span>';
        }

        $this->status = '<span class="label label-as-badge ' . $activeColorButton . '">' . $active . '</span> ' . $revised;
    }

    private function decorateContact(): void
    {
        $this->contact = "<em>Not specified</em>";
        if (!empty($this->contactLink)) {
            $params = [
                'project_id' => $this->getPidsArray()['PEOPLE'],
                'return_format' => 'json-array',
                'records' => [$this->contactLink],
                'fields' => ['email', 'firstname', 'lastname', 'person_region']
            ];
            $personInfo = REDCap::getData($params)[0];
            if (!empty($personInfo)) {
                $nameConcept = '<a href="mailto:' . $personInfo['email'] . '">' . $personInfo['firstname'] . ' ' . $personInfo['lastname'];
                if (!empty($person_info['person_region'])) {
                    $params = [
                        'project_id' => $this->getPidsArray()['REGIONS'],
                        'return_format' => 'json-array',
                        'records' => [$personInfo['person_region']],
                        'fields' => ['region_code']
                    ];
                    $personRegion = REDCap::getData($params)[0];
                    if (!empty($personRegion)) {
                        $nameConcept .= " (" . $personRegion['region_code'] . ")";
                    }
                }
                $nameConcept .= '</a>';
                $this->contact = $nameConcept;
            }
        }
    }

    private function decorateParticipants(): void
    {
        $this->participants = "<em>Not specified</em>";
        if (!empty($this->participantsComplete) && is_array($this->participantsComplete)) {
            $participantList = "";
            foreach ($this->participantsComplete as $id => $participant) {
                $params = [
                    'project_id' => $this->getPidsArray()['PEOPLE'],
                    'return_format' => 'array',
                    'records' => [$this->personLink[$id]]
                ];
                $RecordSetParticipant = REDCap::getData($params);
                $participantInfo = $this->module->escape(
                    $this->getProjectInfoArrayRepeatingInstruments($RecordSetParticipant, $this->pidsArray['PEOPLE'])[0]
                );
                if (!empty($participantInfo)) {
                    #get the label from the drop down menu
                    $participantList .= '<div><a href="mailto:' . $participantInfo['email'] . '">' . $participantInfo['firstname'] . ' ' . $participantInfo['lastname'] . '</a> (' . htmlspecialchars(
                            $this->module->getChoiceLabels(
                                'person_role',
                                $this->pidsArray['HARMONIST']
                            )[$this->personRole[$id]],
                            ENT_QUOTES
                        ) . ')</div>';
                } else {
                    $participantList .= '<div>' . htmlspecialchars($this->personOther[$id], ENT_QUOTES) . '</div>';
                }
            }
            $this->participants = $participantList;
        }
    }

    private function decorateTags(): void
    {
        $this->tags = '<div style="display: inline-block;padding:0 5px 5px 5px"><em>None</em></div>';
        $conceptTagsLabels = $this->module->getChoiceLabels('concept_tags', $this->pidsArray['HARMONIST']);
        $tagData = "";
        foreach ($this->conceptTags as $tag => $value) {
            if ($value == 1) {
                $tagData .= '<div style="display: inline-block;padding:0 5px 5px 5px"><span class="label label-as-badge badge-draft"> ' . $conceptTagsLabels[$tag] . '</span></div>';
            }
        }
        if (!empty($tagData)) {
            $this->tags = $tagData;
        }
    }

    private function decorateWritingGroupCore(): void{
        $this->cmember_role = $this->conceptData['cmember_role'];
        $this->cmember_nh = $this->conceptData['cmember_nh'];
        $this->cmember_link = $this->conceptData['cmember_link'];
        $this->cmember_firstname = $this->conceptData['cmember_firstname'];
        $this->cmember_lastname = $this->conceptData['cmember_lastname'];
        $this->cmember_email = $this->conceptData['cmember_email'];
    }

    private function decorateWritingGroupByResearchGroup(): void{
        $this->gmember_role = $this->conceptData['gmember_role'];
        for($i = 1; $i < ((int)$this->authorshipLimit+1) ; $i++){
            $this->gmember_nh[$i] = $this->conceptData['gmember_nh_'.$i];
            $this->gmember_link[$i] = $this->conceptData['gmember_link_'.$i];
            $this->gmember_firstname[$i] = $this->conceptData['gmember_firstname_'.$i];
            $this->gmember_lastname[$i] = $this->conceptData['gmember_lastname_'.$i];
            $this->gmember_email[$i] = $this->conceptData['gmember_email_'.$i];
        }
    }
}

?>
