<?php

namespace Vanderbilt\HarmonistHubExternalModule;

use phpDocumentor\Reflection\Types\Boolean;
use REDCap;

class WrittingGroup
{
    private $name;
    private $email;
    private $role;

    public function __construct()
    {

    }

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
}

?>
