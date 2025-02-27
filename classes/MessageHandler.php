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

    public function fetchWritingGroupMessage($message_type)
    {
        return "The writing group member data has been updated.";
//        switch ($message_type) {
//            case UPDATE_MESSAGE:
//                return "The writing group member data has been updated.";
//            case SAVE_MESSAGE:
//                return "The writing group member data has been saved.";
//            case NEW_MEMBER_MESSAGE:
//                return "The writing group new member has been added.";
//        }
    }
}
