<?php
use \Exception;

class Passthru {

    /**
     * @param \Plugin\Record $record
     * @param string $surveyFormName
     * @param bool $dontCreateForm
     * @return string
     */
    public static function passthruToSurvey($record_id,$project_id, $surveyFormName = "", $dontCreateForm = false, $instance = "1") {
        // Check to make sure instance is greater than or equal to 1.  Instance with value "" causes weird behavior i.e.  2 instances getting created with "" and 0.
        $instance = is_numeric($instance) ? (int)$instance : 1;

        $Proj = new \Project($project_id);
        $event_id = $Proj->firstEventId;

        // Get survey_id, form status field
        list($surveyFormName, $surveyId) = self::getSurveyFormAndId($project_id, $surveyFormName);
        if($surveyId == "") {
            if($dontCreateForm) {
                return false;
            }
            else {
                die("Error: Survey ID not found<br />{$record_id} : $surveyFormName<br />");
            }
        }


        ## Search for a participant and response id for the given survey and record
        $sql = "SELECT p.participant_id, p.hash, r.return_code, r.response_id, COALESCE(p.participant_email,'NULL') as participant_email
				FROM redcap_surveys_participants p, redcap_surveys_response r
				WHERE p.survey_id = '$surveyId'
					AND p.participant_id = r.participant_id
					AND r.record = '".prep($record_id)."'
					AND r.instance='$instance'";
        //echo "$sql<br/>";
        $participantQuery = db_query($sql);
        $rows = [];
        while($row = db_fetch_assoc($participantQuery)) {
            $rows[] = $row;
        }
        $participantId = $rows[0]['participant_id'];
        $responseId = $rows[0]['response_id'];
        ## Create participant and return code if doesn't exist yet
        if($participantId == "" || $responseId == "") {
            $hash = self::generateUniqueRandomSurveyHash();

            ## Insert a participant row for this survey
            $sql = "INSERT INTO redcap_surveys_participants (survey_id, event_id, participant_email, participant_identifier, hash)
					VALUES ($surveyId,".prep($event_id).", '', null, '$hash')";
            //echo "$sql<br/>";
            if(!db_query($sql)) echo "Error: ".db_error()." <br />$sql<br />";
            $participantId = db_insert_id();

            ## Insert a response row for this survey and record
            $returnCode = generateRandomHash();

            $sql = "INSERT INTO redcap_surveys_response (participant_id, record, instance, first_submit_time, return_code)
					VALUES ($participantId, ".prep($record_id).", '$instance', NULL,'$returnCode')";

            echo "$sql<br/>";
            if(!db_query($sql)) echo "Error: ".db_error()." <br />$sql<br />";
            $responseId = db_insert_id();
        }
        ## Reset response status if it already exists
        else {
            ## If more than one exists, delete any that are responses to public survey links
            if(db_num_rows($participantQuery) > 1) {
                foreach($rows as $thisRow) {
                    if($thisRow["participant_email"] == "NULL" && $thisRow["response_id"] != "") {
                        $sql = "DELETE FROM redcap_surveys_response
								WHERE response_id = ".$thisRow["response_id"];
                        if(!db_query($sql)) echo "Error: ".db_error()." <br />$sql<br />";
                    }
                    else {
                        $row = $thisRow;
                    }
                }
            }
            else {
                $row = $rows[0];
            }
            $returnCode = $row['return_code'];
            $hash = $row['hash'];
            $participantId = "";

            if($returnCode == "") {
                $returnCode = generateRandomHash();
            }

            ## If this is only as a public survey link, generate new participant row
            if($row["participant_email"] == "NULL") {
                $hash = self::generateUniqueRandomSurveyHash();

                ## Insert a participant row for this survey
                $sql = "INSERT INTO redcap_surveys_participants (survey_id, event_id, participant_email, participant_identifier, hash)
						VALUES ($surveyId,".prep($event_id).", '', null, '$hash')";

                if(!db_query($sql)) echo "Error: ".db_error()." <br />$sql<br />";
                $participantId = db_insert_id();
            }

            // Set the response as incomplete in the response table, update participantId if on public survey link
            $sql = "UPDATE redcap_surveys_participants p, redcap_surveys_response r
					SET r.completion_time = null,
						r.first_submit_time = NULL,
						r.return_code = '".prep($returnCode)."'".
                ($participantId == "" ? "" : ", r.participant_id = '$participantId'")."
					WHERE p.survey_id = $surveyId
						AND p.event_id = ".prep($event_id)."
						AND r.participant_id = p.participant_id
						AND r.record = '".prep($record_id)."'
						AND r.instance = '$instance'";
            db_query($sql);
        }
        $surveyLink = APP_PATH_SURVEY_FULL . "?s=$hash";

        @db_query("COMMIT");

        if($dontCreateForm) {
            return $surveyLink;
        }
        else {
            // Set the response as incomplete in the data table
            $sql = "UPDATE redcap_data
				SET value = '0'
				WHERE project_id = ".prep($project_id)."
					AND record = '".prep($record_id)."'
					AND event_id = ".prep($event_id)."
					AND field_name = '{$surveyFormName}_complete' 
					AND instance =" . $instance;
            $q = db_query($sql);
            // Log the event (if value changed)
            if ($q && db_affected_rows() > 0) {
                if(function_exists("log_event")) {
                    \log_event($sql,"redcap_data","UPDATE",$record_id,"{$surveyFormName}_complete = '0'","Update record");
                }
                else {
                    \Logging::logEvent($sql,"redcap_data","UPDATE",$record_id,"{$surveyFormName}_complete = '0'","Update record");
                }
            }

//			echo "Return $returnCode ~ $surveyLink <br />";
            ## Build invisible self-submitting HTML form to get the user to the survey
            echo "<html><body>
				<form name='passthruform' action='$surveyLink' method='post' enctype='multipart/form-data'>
				".($returnCode == "NULL" ? "" : "<input type='hidden' value='".$returnCode."' name='__code'/>")."
				<input type='hidden' value='1' name='__prefill' />
				</form>
				<script type='text/javascript'>
					document.passthruform.submit();
				</script>
				</body>
				</html>";
            return false;
        }
    }

    public static function generateUniqueRandomSurveyHash() {
        ## Generate a random hash and verify it's unique
        do {
            $hash = generateRandomHash(10);

            $sql = "SELECT p.hash
					FROM redcap_surveys_participants p
					WHERE p.hash = '$hash'";

            $result = db_query($sql);

            $hashExists = (db_num_rows($result) > 0);
        } while($hashExists);

        return $hash;
    }

    public static function getSurveyFormAndId($project_id, $formName = "") {
        // Get survey_id, form status field, and save and return setting
        $sql = "SELECT s.survey_id, s.form_name, s.save_and_return
		 		FROM redcap_projects p, redcap_surveys s, redcap_metadata m
					WHERE p.project_id = ".$project_id."
						AND p.project_id = s.project_id
						AND m.project_id = p.project_id
						AND s.form_name = m.form_name
						".($formName != "" ? (is_numeric($formName) ? "AND s.survey_id = '$formName'" : "AND s.form_name = '$formName'") : "")
            ." LIMIT 1";

        $q = db_query($sql);
        $formName = db_result($q, 0, 'form_name');
        $surveyId = db_result($q, 0, 'survey_id');

        return array($formName, $surveyId);
    }
}

?>