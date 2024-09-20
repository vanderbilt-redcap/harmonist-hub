<?php
namespace Vanderbilt\HarmonistHubExternalModule;

class HubREDCapUsers
{
    const HUB_ROLE_ADMIN = "Hub Admin Role";

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

    public static function getUserRole($module, $role_name){
        $q = $module->query("SELECT role_id FROM redcap_user_roles WHERE role_name = ?", [$role_name]);
        if($row = $q->fetch_assoc()){
            return $row['role_id'];
        }

        $fields = "project_id, role_name, data_export_tool, data_import_tool, data_comparison_tool, data_logging, email_logging, file_repository, double_data, " .
            "user_rights, design, alerts, lock_record, lock_record_multiform, lock_record_customize, data_access_groups, graphical, reports, calendar, " .
            "record_create, record_rename, record_delete, dts, participants, data_quality_design, data_quality_execute, data_quality_resolution,
		api_export, api_import, api_modules, mobile_app, mobile_app_download_data,
		random_setup, random_dashboard, random_perform, realtime_webservice_mapping, realtime_webservice_adjudicate, external_module_config,
		mycap_participants,
		data_entry, data_export_instruments";
        $values =  [];

        $q = $module->query("INSERT INTO redcap_user_roles 
                                ($fields) VALUES(?,?,?,?,?,?,?,?,?,?)",
            [$values]);
        $role_id = db_insert_id();
        return $role_id;
    }

    public static function addUserToProject($module, $project_id, $user_name, $role_name, $user_name_main, $pidsArray){
        $role_id = self::getUserRole($module, $role_name);
        $constant = array_search($project_id, $pidsArray);
        $q = $module->query("SELECT role_id FROM redcap_user_rights WHERE project_id = ? AND username = ?", [$project_id,$user_name]);
        if($q->num_rows  == 0){
            $fields_rights = "project_id, username, role_id, design, user_rights, data_export_tool, reports, graphical, data_logging, data_entry";
            $instrument_names = \REDCap::getInstrumentNames(null,$project_id);
            #Data entry [$instrument,$status] -> $status: 0 NO ACCESS, 1 VIEW & EDIT, 2 READ ONLY
            $data_entry = "[".implode(',1][',array_keys($instrument_names)).",1]";
            $module->query("INSERT INTO redcap_user_rights (" . $fields_rights . ")
                    VALUES (?,?,?,?,?,?,?,?,?)",
                [$project_id, $user_name, $role_id, 1, 1, 1, 1, 1, 1, $data_entry]);

            \REDCap::logEvent("Hub REDCap User Management: ".$user_name." added as ".$role_name." on  ".$constant." (PID #".$project_id.") by ".$user_name_main, "User Added on Project #".$project_id." by ".$user_name_main, null,null,null,$pidsArray['PROJECTS']);
            \REDCap::logEvent("Hub REDCap User Management: ".$user_name." added as ".$role_name." on  ".$constant." (PID #".$project_id.") by ".$user_name_main, "User Added on Project #".$project_id." by ".$user_name_main, null,null,null,$project_id);
        }
    }

    public static function replaceTerm($match) {
        $applyTag = function($found) {
            // the sorrounding tag can be customized here
            $tagged = sprintf('<mark style="padding:0;background-color:yellow;""><b>%s</b></mark>', $found);
            return $tagged;
        };
        $found = @$match[0];
        if(!$found) return '';
        return $applyTag($found);
    }

    public static function getTermRegExp($terms) {
        $termsReducer = function($carry, $term) {
            $quotedTerm = preg_quote($term); // we do not want to use regexps provided by the user interface
            $normalized = "($quotedTerm)"; // enclose in grouping parenthesis
            $carry[] = $normalized;
            return $carry;
        };
        $result = array_reduce($terms, $termsReducer, []);
        $regExp = sprintf('/%s/i', implode('|',$result));
        return $regExp;
    }

    public static function searchTerms($terms, $text) {

        $regExp = self::getTermRegExp($terms);
        $result = preg_replace_callback($regExp,  'self::replaceTerm', $text);
        return $result;
    }

    public static function getUserInfoAutocomplete($module, $getTerm){
        // Santize search term passed in query string
        $search_term = trim(html_entity_decode(urldecode($getTerm), ENT_QUOTES));

        // Remove any commas to allow for better searching
        $search_term = str_replace(",", "", $search_term);

        // Return nothing if search term is blank
        if ($search_term == '') exit('[]');

        // If search term contains a space, then assum multiple search terms that will be searched for independently
        if (strpos($search_term, " ") !== false) {
            $search_terms = explode(" ", $search_term);
        } else {
            $search_terms = array($search_term);
        }
        $search_terms = array_unique($search_terms);

        // Set the subquery for all search terms used
        $subsqla = [];
        foreach ($search_terms as $key=>$this_term) {
            // Trim and set to lower case
            $search_terms[$key] = $this_term = trim(strtolower($this_term));
            if ($this_term == '') {
                unset($search_terms[$key]);
            } else {
                $subsqla[] = "username like ?";
                $subsqla[] = "user_firstname like ?";
                $subsqla[] = "user_lastname like ?";
            }
        }
        $sql_value = "%".$this_term."%";
        $subsql = implode(" or ", $subsqla);

        $q = $module->query("select distinct username, user_firstname, user_lastname, user_email
		from redcap_user_information where ($subsql) 
		order by username",[$sql_value,$sql_value,$sql_value]);
        while($row = $q->fetch_assoc()){
            // Trim all, just in case
            $row['username'] = trim(strtolower($row['username']));
            $row['user_firstname'] = trim($row['user_firstname']);
            $row['user_lastname']  = trim($row['user_lastname']);
            // Set lower case versions of first/last name
            $firstname_lower = strtolower($row['user_firstname']);
            $lastname_lower  = strtolower($row['user_lastname']);
            // Get full name
            $row['user_fullname']  = trim($row['user_firstname'] . " " . $row['user_lastname']);
            // Set label
            $label = $row['username'] . ($row['user_fullname'] == '' ? '' : " ({$row['user_fullname']})");
            // Calculate search match score.
            $userMatchScore[$key] = 0;

            // Loop through each search term for this person

            // Set length of this search string
            $this_term_len = strlen($this_term);
            // For partial matches on username, first name, or last name (or email, if applicable), give +1 point for each letter
            if (strpos($row['username'], $this_term) !== false) $userMatchScore[$key] = $userMatchScore[$key]+$this_term_len;
            if (strpos($firstname_lower, $this_term) !== false) $userMatchScore[$key] = $userMatchScore[$key]+$this_term_len;
            if (strpos($lastname_lower, $this_term) !== false) $userMatchScore[$key] = $userMatchScore[$key]+$this_term_len;

            // Wrap any occurrence of search term in label with a tag
            $label = self::searchTerms($search_terms, $label);

            // Add to arrays
            $users[$key] = array('value'=>$row['username'], 'label'=>$label);
            $usernamesOnly[$key] = $row['username'];
            // If username, first name, or last name match EXACTLY, do a +100 on score.
            if (in_array($row['username'], $search_terms)) $userMatchScore[$key] = $userMatchScore[$key]+100;
            if (in_array($firstname_lower, $search_terms)) $userMatchScore[$key] = $userMatchScore[$key]+100;
            if (in_array($lastname_lower, $search_terms))  $userMatchScore[$key] = $userMatchScore[$key]+100;

            // Increment key
            $key++;
        }
        // Sort users by score, then by username
        $count_users = count($users);
        if ($count_users > 0) {
            // Sort
            array_multisort($userMatchScore, SORT_NUMERIC, SORT_DESC, $usernamesOnly, SORT_STRING, $users);
            // Limit only to X users to return
            $limit_users = 10;
            if ($count_users > $limit_users) {
                $users = array_slice($users, 0, $limit_users);
            }
        }
        return json_encode($users);
    }
}