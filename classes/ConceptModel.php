<?php

namespace Vanderbilt\HarmonistHubExternalModule;

use phpDocumentor\Reflection\Types\Boolean;
use REDCap;

include_once (__DIR__ . "/../autoload.php");

class ConceptModel extends Model
{
    private $module;
    private $projectId;
    private $pidsArray = [];
    private $concept;
    private $conceptData;

    public function __construct(HarmonistHubExternalModule $module, $projectId)
    {
        $this->module = $module;
        $this->projectId = $projectId;
        parent::__construct($module,$projectId);
        $this->pidsArray = $this->getPidsArray();
    }

    public function fetchConcept($recordId): Concept
    {
        if(!empty($this->pidsArray['HARMONIST'])) {
            $RecordSetTable = \REDCap::getData($this->pidsArray['HARMONIST'], 'array', array('record_id' => $recordId));
            $this->conceptData = $this->module->escape(
               $this->getProjectInfoArrayRepeatingInstruments($RecordSetTable, $this->pidsArray['HARMONIST'])[0]
            );
            $this->concept = new Concept($this->conceptData, $this->pidsArray);
        }
        return $this->concept;
    }

     public function getConceptData(): array
    {
        return $this->conceptData;
    }
}

?>
