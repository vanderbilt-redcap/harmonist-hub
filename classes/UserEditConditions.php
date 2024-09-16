<?php
namespace Vanderbilt\HarmonistHubExternalModule;


class UserEditConditions
{
    public static function canUserEditPublication($isAdmin, $current_user, $contact_link, $contact2_link, $harmonist_perm)
    {
        if ($isAdmin || $contact_link == $current_user || $contact2_link == $current_user || $harmonist_perm) {
            return true;
        }
        return false;
    }
}
?>