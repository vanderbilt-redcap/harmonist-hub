<?php

namespace Vanderbilt\HarmonistHubExternalModule;

use phpDocumentor\Reflection\Types\Boolean;
use REDCap;

class WritingGroupMember
{
    private $name;
    private $email;
    private $role;
    private $editLink;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }
    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail($email): void
    {
        $this->email = $email;
    }
    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole($role): void
    {
        $this->role = $role;
    }

    public function getEditLink()
    {
        return $this->editLink;
    }

    public function setEditLink($editLink): void
    {
        $this->editLink = $editLink;
    }
}

?>
