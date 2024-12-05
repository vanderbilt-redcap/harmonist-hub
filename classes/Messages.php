<?php

namespace Vanderbilt\HarmonistHubExternalModule;

use Project;
use REDCap;


class Messages
{
    public static function getHubUpdatesMessage($letter)
    {
        $message = [
            'S' => "The Data Dictionary has been successfully updated.",
            'R' => "The variables have been successfully <strong>added</strong> to the resolved list.",
            'U' => "The variables have been successfully <strong>removed</strong> from the resolved list.",
            'L' => "The Data Dictionary has been successfully updated.",
            'T' => "<strong>" . ProjectData::HUB_SURVEY_THEME_NAME . "</strong> has been successfully updated. Check the logs to see which surveys have been updated.",
            'V' => "The surveys have been succesfully created. Check the logs to see which instruments have been updated.",
            'E' => "The module and settings have been enabled on the projects.",
        ];
        return $message[$letter];
    }
}

?>

