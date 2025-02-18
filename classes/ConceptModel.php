<?php

namespace Vanderbilt\HarmonistHubExternalModule;

use phpDocumentor\Reflection\Types\Boolean;
use REDCap;

include_once (__DIR__ . "/../autoload.php");

class ConceptModel extends Model
{
    private $isAdmin;
    private $allConcepts;
    private $concept;
    private $conceptData;

    public function __construct(HarmonistHubExternalModule $module, $projectId)
    {
        parent::__construct($module,$projectId);
    }

    public function fetchConcept($recordId): Concept
    {
        if(!empty($this->getPidsArray()['HARMONIST'])) {
            $params = [
                'project_id' => $this->getPidsArray()['HARMONIST'],
                'return_format' => 'array',
                'records' => [$recordId]
            ];
            $RecordSetTable = REDCap::getData($params);
            $this->conceptData = $this->module->escape(
             $this->getProjectInfoArrayRepeatingInstruments($RecordSetTable, $this->getPidsArray()['HARMONIST'])[0]
            );
            $this->concept = new Concept($this->conceptData, $this->module, $this->getPidsArray());
        }
        return $this->concept;
    }

    public function fetchAllConcepts(): array
    {
        if(!empty($this->getPidsArray()['HARMONIST'])) {
            $params = [
                'project_id' => $this->getPidsArray()['HARMONIST'],
                'return_format' => 'array'
            ];
            $RecordSetTable = REDCap::getData($params);
            $this->allConcepts = $this->module->escape(
                $this->getProjectInfoArrayRepeatingInstruments($RecordSetTable, $this->getPidsArray()['HARMONIST'])
            );
        }
        return $this->allConcepts;
    }

    public function canUserEdit($current_user):bool
    {
        if(!empty($this->concept)) {
            if ($this->concept->getContactLink() == $current_user || $this->concept->getContact2Link() == $current_user || $this->isHarmonistAdmin($current_user)) {
                return true;
            }
        }
        return false;
    }

     public function getConceptData(): array
    {
        return $this->conceptData;
    }
}

?>
