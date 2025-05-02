<?php

namespace Vanderbilt\HarmonistHubExternalModule;

use REDCap;

class MessageHandler
{
    const UPDATE_MESSAGE = 'U';
    const SAVE_MESSAGE = 'S';
    const NEW_MESSAGE = 'N';
    const DELETE_MESSAGE = 'D';
    private $message = "";

    public function fetchMessage($type, $message_type)
    {
        switch ($type) {
            case 'writingGroup':
                $text = "The writing group member";
                break;
            case 'concept':
                $text = "The concept";
                if($message_type == "P") {
                    $text = "Abstracts & publications";
                    $message_type = self::UPDATE_MESSAGE;
                } elseif ($message_type == "L") {
                    $text = "Linked documents";
                    $message_type = self::UPDATE_MESSAGE;
                } elseif ($message_type == "O") {
                    $text = "A new output";
                    $message_type = self::NEW_MESSAGE;
                }elseif ($message_type == "D") {
                    $text = "A new linked document";
                    $message_type = self::NEW_MESSAGE;
                }
                break;
            case 'dataDownloadsUser':
                $text = "The user";
                if($message_type == "D") {
                    $message_type = self::DELETE_MESSAGE;
                }
                break;
        }
        $this->message = $this->decorateMessage($message_type, $text);
        return $this->message;
    }

    private function decorateMessage($message_type, $text)
    {
        switch ($message_type) {
            case self::UPDATE_MESSAGE:
                return "$text data has been updated.";
            case self::SAVE_MESSAGE:
                return "$text data has been saved.";
            case self::NEW_MESSAGE:
                return "$text has been added.";
            case self::DELETE_MESSAGE:
                return "$text has been removed.";
        }
    }
}
