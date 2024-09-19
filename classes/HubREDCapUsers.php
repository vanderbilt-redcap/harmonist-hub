<?php
namespace Vanderbilt\HarmonistHubExternalModule;

class HubREDCapUsers
{
    public static function getUserList($module, $project_id): array
    {
        $choices = [];
        $sql = "SELECT ur.username,ui.user_firstname,ui.user_lastname
                    FROM redcap_user_rights ur, redcap_user_information ui
                    WHERE ur.project_id = ?
                            AND ui.username = ur.username
                    ORDER BY ui.ui_id";
        $result = $module->query($sql, [$project_id]);
        while ($row = $result->fetch_assoc()) {
            $row = $module->escape($row);
            $choices[] = ['value' => strtolower($row['username']), 'name' => $row['user_firstname'] . ' ' . $row['user_lastname']];
        }
        return $choices;
    }
}