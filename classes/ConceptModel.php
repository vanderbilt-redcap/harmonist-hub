<?php

namespace Vanderbilt\HarmonistHubExternalModule;

use phpDocumentor\Reflection\Types\Boolean;
use REDCap;

include_once(__DIR__ . "/../autoload.php");

class ConceptModel extends Model
{
    public function __construct(HarmonistHubExternalModule $module, $projectId)
    {
        parent::__construct($module, $projectId);
    }

    public function fetchConcept($recordId, $authorshipLimit = null): Concept
    {
        $concept = null;
        if (!empty($this->getPidsArray()['HARMONIST'])) {
            $params = [
                'project_id' => $this->getPidsArray()['HARMONIST'],
                'return_format' => 'array',
                'records' => [$recordId]
            ];
            $RecordSetTable = \REDCap::getData($params);
            $conceptData = $this->module->escape(
                $this->getProjectInfoArrayRepeatingInstruments($RecordSetTable, $this->getPidsArray()['HARMONIST'])[0]
            );
            $concept = new Concept($conceptData, $this->module, $this->getPidsArray(), $authorshipLimit);
        }
        return $concept;
    }

    public function fetchAllConcepts(): array
    {
        $allConcepts = [];
        if (!empty($this->getPidsArray()['HARMONIST'])) {
            $params = [
                'project_id' => $this->getPidsArray()['HARMONIST'],
                'return_format' => 'array'
            ];
            $RecordSetTable = \REDCap::getData($params);
            $allConcepts = $this->module->escape(
                $this->getProjectInfoArrayRepeatingInstruments($RecordSetTable, $this->getPidsArray()['HARMONIST'])
            );
            foreach ($allConcepts as $conceptData) {
                $concept[$conceptData['record_id']] = new Concept($conceptData, $this->module, $this->getPidsArray(), null);
            }
        }
        return $concept;
    }
}

?>
