<?php

namespace Vanderbilt\HarmonistHubExternalModule;

use phpDocumentor\Reflection\Types\Boolean;
use REDCap;


class MessageHandler
{
    private $message = "";

    public function __construct()
    {

    }

    public function fetchMessage($type, $message_type)
    {
        switch ($type) {
            case 'writingGroup':
                $this->message = $this->fetchWritingGroupMessage($message_type);
                break;
        }
        return $this->message;
    }

    public function fetchWritingGroupMessage($message_type)
    {
        switch ($message_type) {
            case 'U':
                return "The writing group member data has been updated.";
            case 'S':
                return "The writing group member data has been saved.";
            case 'N':
                return "The writing group new member has been added.";
        }
    }
}
