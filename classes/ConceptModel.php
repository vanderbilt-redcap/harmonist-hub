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
    private $isAdmin;
    private $concept;
    private $conceptData;

    public function __construct(HarmonistHubExternalModule $module, $projectId)
    {
        $this->module = $module;
        $this->projectId = $projectId;
        parent::__construct($module,$projectId);
        $this->pidsArray = $this->getPidsArray();
        $this->isAdmin = $this->isAdmin();
    }

    public function fetchConcept($recordId): Concept
    {
        if(!empty($this->pidsArray['HARMONIST'])) {
            $RecordSetTable = \REDCap::getData($this->pidsArray['HARMONIST'], 'array', array('record_id' => $recordId));
            $this->conceptData = $this->module->escape(
               $this->getProjectInfoArrayRepeatingInstruments($RecordSetTable, $this->pidsArray['HARMONIST'])[0]
            );
            $this->concept = new Concept($this->conceptData, $this->module, $this->pidsArray);
        }
        return $this->concept;
    }

    public function canUserEdit($current_user){
        if(!empty($this->concept)) {
            if ($this->concept->getContactLink() == $current_user || $this->concept->getContact2Link() == $current_user || $this->isAdmin) {
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
