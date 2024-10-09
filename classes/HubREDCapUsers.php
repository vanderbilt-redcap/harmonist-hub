<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include_once(__DIR__ . "/REDCapManagement.php");

class HubREDCapUsers
{
    const HUB_ROLE_ADMIN = "Hub Admin Role";
    const HUB_ROLE_USER = "Hub User Role";
    const HUB_ROLES = ["3" => "ADMIN", "1" => "USER"];
    const ADD_USER = "add_user";
    const REMOVE_USER = "remove_user";
    const CHANGE_USER = "change_user";

    public static function getUserList($module, $project_id): array
    {
        $choices = [];
        $sql = "SELECT ur.username,ui.user_firstname,ui.user_lastname,ur.role_id 
                    FROM redcap_user_rights ur, redcap_user_information ui
                    WHERE ur.project_id = ?
                            AND ui.username = ur.username
                    ORDER BY ui.ui_id";
        $result = $module->query($sql, [$project_id]);
        while ($row = $result->fetch_assoc()) {
            $row = $module->escape($row);
            $choices[] = ['value' => strtolower($row['username']), 'name' => $row['user_firstname'] . ' ' . $row['user_lastname'], 'role_id' => $row['role_id']];
        }
        return $choices;
    }

    public static function getUsersEmail($module, $user_list): array
    {
        $email_users = [];
        foreach ($user_list as $user_name) {
            $q = $module->query("SELECT user_email FROM redcap_user_information WHERE username = ?", [$user_name]);
            if($row = $q->fetch_assoc()){
                $email_users[$user_name]['email'] =  $row['user_email'];
                $email_users[$user_name]['text'] =  '';
            }
        }
        return $email_users;
    }

    public static function getUserRole($module, $role_name, $project_id, $role_type): int
    {
        if($role_name != null) {
            $q = $module->query("SELECT role_id FROM redcap_user_roles WHERE role_name = ? AND project_id = ?", [$role_name, $project_id]);
            if ($row = $q->fetch_assoc()) {
                return $row['role_id'];
            }

            $fields = "project_id, role_name, data_export_instruments, data_entry, data_import_tool, data_comparison_tool, data_logging, file_repository,
        user_rights, data_access_groups, graphical, reports, design, calendar, record_create, record_rename, record_delete,
        participants, data_quality_resolution";

            $values = self::getUserRoleData($module, $project_id, $role_type, $role_name);

            $q = $module->query("INSERT INTO redcap_user_roles
                                ($fields) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)", $values);
            $role_id = db_insert_id();

            return $role_id;
        }
        return 0;
    }

    public static function getUserRoleData($module, $project_id, $role_type, $role_name): array
    {
        $role_id = array_search($role_type, self::HUB_ROLES);
        $instrument_names = \REDCap::getInstrumentNames(null,$project_id);

        #Data entry [$instrument,$status] -> $status: 0 NO ACCESS, 1 VIEW & EDIT, 2 READ ONLY
        $data_entry = "[".implode(','.$role_id.'][',array_keys($instrument_names)).",".$role_id."]";
        $data_export_instruments = "[".implode(',1][',array_keys($instrument_names)).",1]";

        $values = [];
        switch($role_id){
            case 1:
                $values = [$project_id,$role_name,$data_export_instruments,$data_entry,0,0,0,1,0,0,1,1,0,1,1,0,0,1,1];
                break;
            case 3:
                $values = [$project_id,$role_name,$data_export_instruments,$data_entry,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1];
                break;
        }
        return $values;
    }


    public static function getAllRoles($module, $project_id): array
    {
        $roles = [];
        foreach (self::HUB_ROLES as $role_id => $role_type){
            $role_name = constant("self::HUB_ROLE_".$role_type);
            $roles[$role_name] = self::getUserRole($module, $role_name, $project_id, $role_type);
        }
        return $roles;
    }

    public static function setUserChanges($module, $pidsArray, $option, $user_list, $checked_values, $role_name, $role_type): string
    {
        $message = "";
        $email_users = "";
        if($option == self::ADD_USER){
            $email_users = self::getUsersEmail($module, $user_list);
            $message = "A";
        }

        foreach ($checked_values as $project_id) {
            $role_id = self::getUserRole($module, $role_name, $project_id, $role_type);
            foreach ($user_list as $user_name) {
                if($option == self::ADD_USER) {
                    self::addUserToProject($module, $project_id, $user_name, $role_id, USERID, $pidsArray, $role_name);
                    $gotoREDCap = APP_PATH_WEBROOT_ALL . "ProjectSetup/index.php?pid=" . $project_id;
                    $email_users[$user_name]['text'] .= "<div>PID #" . $project_id . " - <a href='" . $gotoREDCap . "'>" . $module->framework->getProject($pidsArray[array_search($project_id, $pidsArray)])->getTitle() . "</a></div>";
                }else if($option == self::REMOVE_USER){
                    self::removeUserFromProject($module, $project_id, $user_name, USERID, $pidsArray);
                    $message = "D";
                }else if($option == self::CHANGE_USER){
                    self::changeUserRole($module, $project_id, $user_name, $role_id, $pidsArray, $role_name, USERID);
                    $message = "C";
                }
            }
        }

        if($option == self::ADD_USER){
            foreach ($email_users as $user_name => $data){
                \REDCap::email($data['email'],REDCapManagement::DEFAULT_EMAIL_ADDRESS,"You have been added to a ".$settings['hub_name']." Hub Project", $data['text']);
            }
        }
        return $message;
    }

    public static function addUserToProject($module, $project_id, $user_name, $role_id, $user_name_main, $pidsArray, $role_name=""): void
    {
        $q = $module->query("SELECT role_id FROM redcap_user_rights WHERE project_id = ? AND username = ?", [$project_id,$user_name]);
        if($q->num_rows  == 0){
            $fields_rights = "project_id, username, role_id, design, user_rights, data_export_tool, reports, graphical, data_logging, data_entry";
            $instrument_names = \REDCap::getInstrumentNames(null,$project_id);
            #Data entry [$instrument,$status] -> $status: 0 NO ACCESS, 1 VIEW & EDIT, 2 READ ONLY
            $data_entry = "[".implode(',1][',array_keys($instrument_names)).",1]";
            $module->query("INSERT INTO redcap_user_rights (" . $fields_rights . ")
                    VALUES (?,?,?,?,?,?,?,?,?,?)",
                [$project_id, $user_name, $role_id, 1, 1, 1, 1, 1, 1, $data_entry]);

            if(is_int($pidsArray)){
                $title = "$user_name added by $user_name_main";
                $message = "User $user_name added on ".$module->framework->getProject($project_id)->getTitle()." (PID #".$project_id.") as $role_name by $user_name_main";
                \REDCap::logEvent($title, $message, null,null,null,$pidsArray);
            }else{
                self::addUserLogs($module, $user_name, $user_name_main, $project_id, $pidsArray, "added",  $role_name);
            }
        }
    }

    public static function removeUserFromProject($module, $project_id, $user_name, $user_name_main, $pidsArray): void
    {
        $q = $module->query("DELETE FROM redcap_user_rights WHERE project_id = ? and username = ?", [$project_id,$user_name]);
        self::addUserLogs($module, $user_name, $user_name_main, $project_id, $pidsArray, "removed");
    }

    public static function changeUserRole($module, $project_id, $user_name, $role_id, $pidsArray, $role_name, $user_name_main): void
    {
        $module->query("UPDATE redcap_user_rights SET role_id = ? WHERE username = ? AND project_id = ?",[$role_id, $user_name, $project_id]);
        self::addUserLogs($module, $user_name, $user_name_main, $project_id, $pidsArray, "changed",  $role_name);
    }

    public static function addUserLogs($module, $user_name, $user_name_main, $project_id, $pidsArray, $type,  $role_name=""): void
    {
        $projectIdConstant = array_search($project_id, $pidsArray);
        $project_title = $module->framework->getProject($pidsArray[$projectIdConstant])->getTitle();

        $title = "Hub REDCap User Management: ".$user_name." ".strtoupper($type);
        $role = "";
        if($role_name != null){
            $role = " as '".$role_name."'";
        }
        $title .= " on  ".$projectIdConstant." (PID #".$project_id.") ";

        $message = "User ".$user_name." ".$type. " on ".$project_title." (PID #".$project_id.")".$role." by ".$user_name_main;

        \REDCap::logEvent($title, $message, null,null,null,$pidsArray['PROJECTS']);
        \REDCap::logEvent($title, $message, null,null,null,$project_id);
    }

    public static function getRoleSelector($id) {
        $select = '<select class="form-select" id="'.$id.'">
                <option></option>';
        foreach (self::HUB_ROLES as $role_id => $role_type){
            $role_name = constant("self::HUB_ROLE_".$role_type);
            $select .= "<option value='".$role_id."' role_name='".$role_name."'>".$role_name."</option>";
        }
        $select .= "</select>";
        return $select;
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
        $subvalue = [];
        foreach ($search_terms as $key=>$this_term) {
            // Trim and set to lower case
            $search_terms[$key] = $this_term = trim(strtolower($this_term));
            if ($this_term == '') {
                unset($search_terms[$key]);
            } else {
                $subsqla[] = "username like ?";
                $subsqla[] = "user_firstname like ?";
                $subsqla[] = "user_lastname like ?";
                $subvalue [] = "%".$this_term."%";
                $subvalue [] = "%".$this_term."%";
                $subvalue [] = "%".$this_term."%";
            }
        }
        $subsql = implode(" or ", $subsqla);

        $q = $module->query("select distinct username, user_firstname, user_lastname, user_email
		from redcap_user_information where ($subsql) 
		order by username",$subvalue);
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