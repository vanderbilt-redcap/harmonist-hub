<?php

namespace Vanderbilt\HarmonistHubExternalModule;

use phpDocumentor\Reflection\Types\Boolean;
use PhpParser\Node\Expr\AssignOp\Mod;
use REDCap;

class Concept extends Model
{
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

    public function __construct($conceptData, $module, $pidsArray)
    {
        parent::__construct($module, $pidsArray['PROJECTS']);
        $this->hydrateConcept($conceptData);
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

    private function hydrateConcept($conceptData)
    {
        $this->conceptId = $conceptData['concept_id'];
        $this->activeY = $conceptData['active_y'];
        $this->conceptTitle = $conceptData['concept_title'];
        $this->conceptTags = $conceptData['concept_tags'];
        $this->contactLink = $conceptData['contact_link'];
        $this->contact2Link = $conceptData['contact2_link'];
        $this->wgLink = $conceptData['wg_link'];
        $this->wg2Link = $conceptData['wg2_link'];
        $this->revisedY = $conceptData['revised_y'][0];
        $this->ecApprovalD = $conceptData['ec_approval_d'];
        $this->participantsComplete = $conceptData['participants_complete'];
        $this->personLink = $conceptData['person_link'];
        $this->personRole = $conceptData['person_role'];
        $this->personOther = $conceptData['person_lother'];
        $this->adminUpdate = $conceptData['admin_update'];
        $this->adminupdateD = $conceptData['adminupdate_d'];
        $this->adminStatus = $conceptData['admin_status'];
        $this->updateD = $conceptData['update_d'];
        $this->projectStatus = $conceptData['project_status'];
        $this->conceptFile = $conceptData['concept_file'];
        $this->outputYear = $conceptData['output_year'];
        $this->outputTitle = $conceptData['output_title'];
        $this->outputDescription = $conceptData['output_description'];
        $this->outputType = $conceptData['output_type'];
        $this->outputVenue = $conceptData['output_venue'];
        $this->outputPmcid = $conceptData['output_pmcid'];
        $this->outputCitation = $conceptData['output_citation'];
        $this->outputFile = $conceptData['output_file'];
        $this->outputUrl = $conceptData['output_url'];
        $this->outputAuthors = $conceptData['output_authors'];
        $this->docFile = $conceptData['doc_file'];
        $this->docTitle = $conceptData['doc_title'];
        $this->docDescription = $conceptData['doc_description'];
        $this->dochiddenY = $conceptData['dochidden_y'];
        $this->docuploadDt = $conceptData['docupload_dt'];
        $this->datasopFile = $conceptData['datasop_file'];
        $this->decorateWorkingGroup();
        $this->decorateStartDate();
        $this->decorateStatus();
        $this->decorateContact();
        $this->decorateParticipants();
        $this->decorateTags();
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
}

?>
