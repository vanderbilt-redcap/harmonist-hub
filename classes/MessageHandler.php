<?php

namespace Vanderbilt\HarmonistHubExternalModule;

use REDCap;

class MessageHandler
{
    const UPDATE_MESSAGE = 'U';
    const SAVE_MESSAGE = 'S';
    const NEW_MEMBER_MESSAGE = 'N';
    private $message = "";

    public function fetchMessage($type, $message_type)
    {
        switch ($type) {
            case 'writingGroup':
                $this->message = $this->fetchWritingGroupMessage($message_type);
                break;
        }
        return $this->message;
    }

    private function fetchWritingGroupMessage($message_type)
    {
        switch ($message_type) {
            case $this->UPDATE_MESSAGE:
                return "The writing group member data has been updated.";
            case $this->SAVE_MESSAGE:
                return "The writing group member data has been saved.";
            case $this->NEW_MEMBER_MESSAGE:
                return "The writing group new member has been added.";
        }
    }
}
