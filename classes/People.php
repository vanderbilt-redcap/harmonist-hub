<?php

namespace Vanderbilt\HarmonistHubExternalModule;

use REDCap;

class People extends Model
{
    private $recordId;
    private $activeY;
    private $firstname;
    private $lastname;
    private $email;
    private $personRegion;
    private $harmonistRegperm;
    private $pendingpanelY;
    private $stayrequestY;
    private $allowgetdataY;
    private $redcapName;
    private $harmonistadminY;
    private $harmonistPerms;
    private $accessToken;
    private $tokenExpirationD;

    public function __construct(HarmonistHubExternalModule $module, $pidsMapper)
    {
        parent::__construct($module, $pidsMapper);
        $this->hydratePeople();
    }

    public function getRecordId()
    {
        return $this->recordId;
    }

    public function setRecordId($recordId): void
    {
        $this->recordId = $recordId;
    }

    public function getActiveY()
    {
        return $this->activeY;
    }

    public function setActiveY($activeY): void
    {
        $this->activeY = $activeY;
    }

    public function getFirstname()
    {
        return $this->firstname;
    }

    public function setFirstname($firstname): void
    {
        $this->firstname = $firstname;
    }

    public function getLastname()
    {
        return $this->lastname;
    }

    public function setLastname($lastname): void
    {
        $this->lastname = $lastname;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email): void
    {
        $this->email = $email;
    }

    public function getPersonRegion()
    {
        return $this->personRegion;
    }

    public function setPersonRegion($personRegion): void
    {
        $this->personRegion = $personRegion;
    }

    public function getHarmonistRegperm()
    {
        return $this->harmonistRegperm;
    }

    public function setHarmonistRegperm($harmonistRegperm): void
    {
        $this->harmonistRegperm = $harmonistRegperm;
    }

    public function getPendingpanelY()
    {
        return $this->pendingpanelY;
    }

    public function setPendingpanelY($pendingpanelY): void
    {
        $this->pendingpanelY = $pendingpanelY;
    }

    public function getStayrequestY()
    {
        return $this->stayrequestY;
    }

    public function setStayrequestY($stayrequestY): void
    {
        $this->stayrequestY = $stayrequestY;
    }

    public function getAllowgetdataY()
    {
        return $this->allowgetdataY;
    }

    public function setAllowgetdataY($allowgetdataY): void
    {
        $this->allowgetdataY = $allowgetdataY;
    }

    public function getRedcapName()
    {
        return $this->redcapName;
    }

    public function setRedcapName($redcapName): void
    {
        $this->redcapName = $redcapName;
    }

    public function getHarmonistadminY()
    {
        return $this->harmonistadminY;
    }

    public function setHarmonistadminY($harmonistadminY): void
    {
        $this->harmonistadminY = $harmonistadminY;
    }

    public function getHarmonistPerms()
    {
        return $this->harmonistPerms;
    }

    public function setHarmonistPerms($harmonistPerms): void
    {
        $this->harmonistPerms = $harmonistPerms;
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function setAccessToken($accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function getTokenExpirationD()
    {
        return $this->tokenExpirationD;
    }

    public function setTokenExpirationD($tokenExpirationD): void
    {
        $this->tokenExpirationD = $tokenExpirationD;
    }

    private function hydrateConcept()
    {
        $this->recordId = $this->conceptData['record_id'];
        $this->activeY = $this->conceptData['active_y'];
        $this->firstname = $this->conceptData['firstname'];
        $this->lastname = $this->conceptData['lastname'];
        $this->email = $this->conceptData['email'];
        $this->personRegion = $this->conceptData['person_region'];
        $this->harmonistRegperm = $this->conceptData['harmonist_regperm'];
        $this->pendingpanelY = $this->conceptData['pendingpanel_y'];
        $this->stayrequestY = $this->conceptData['stayrequest_y'];
        $this->allowgetdataY = $this->conceptData['allowgetdata_y'];
        $this->redcapName = $this->conceptData['redcap_name'];
        $this->harmonistadminY = $this->conceptData['harmonistadmin_y'];
        $this->harmonistPerms = $this->conceptData['harmonist_perms'];
        $this->accessToken = $this->conceptData['access_token'];
        $this->tokenExpirationD = $this->conceptData['token_expiration_d'];
    }
}

?>
