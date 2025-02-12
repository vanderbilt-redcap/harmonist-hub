<?php

namespace Vanderbilt\HarmonistHubExternalModule;

use phpDocumentor\Reflection\Types\Boolean;
use REDCap;

include_once(__DIR__ . "/Concept.php");

class ConceptModel
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
        $this->pidsArray = self::getPidsArray();
    }

    public function getPidsArray(): array
    {
        if (empty($this->pidsArray)) {
            $hub_mapper = $this->module->getProjectSetting('hub-mapper', $this->projectId);
            if ($hub_mapper !== "") {
                $this->pidsArray = REDCapManagement::getPIDsArray($hub_mapper);
            }
        }
        return $this->pidsArray;
    }

    public function fetchConcept($record): Concept
    {
        if(!empty($this->pidsArray['HARMONIST'])) {
            $RecordSetTable = \REDCap::getData($this->pidsArray['HARMONIST'], 'array', array('record_id' => $record));
            $this->conceptData = $this->module->escape(
                ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetTable, $this->pidsArray['HARMONIST'])[0]
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
