<?php

namespace Vanderbilt\HarmonistHubExternalModule;

use phpDocumentor\Reflection\Types\Boolean;
use REDCap;

include_once(__DIR__ . "/../autoload.php");

class ConceptModel extends Model
{
    private $isAdmin;
    private $concept;
    private $conceptData;

    public function __construct(HarmonistHubExternalModule $module, $projectId)
    {
        parent::__construct($module, $projectId);
    }

    public function fetchConcept($recordId, $authorshipLimit = null): Concept
    {
        if (!empty($this->getPidsArray()['HARMONIST'])) {
            $params = [
                'project_id' => $this->getPidsArray()['HARMONIST'],
                'return_format' => 'array',
                'records' => [$recordId]
            ];
            $RecordSetTable = REDCap::getData($params);
            $this->conceptData = $this->module->escape(
                $this->getProjectInfoArrayRepeatingInstruments($RecordSetTable, $this->getPidsArray()['HARMONIST'])[0]
            );
            $this->concept = new Concept($this->conceptData, $this->module, $this->getPidsArray(), $authorshipLimit);
        }
        return $this->concept;
    }

    public function fetchAllConcepts(): array
    {
        $allConcepts = [];
        if (!empty($this->getPidsArray()['HARMONIST'])) {
            $params = [
                'project_id' => $this->getPidsArray()['HARMONIST'],
                'return_format' => 'array'
            ];
            $RecordSetTable = REDCap::getData($params);
            $allConcepts = $this->module->escape(
                $this->getProjectInfoArrayRepeatingInstruments($RecordSetTable, $this->getPidsArray()['HARMONIST'])
            );
        }
        return $allConcepts;
    }

    public function getConceptData(): array
    {
        return $this->conceptData;
    }
}

?>
