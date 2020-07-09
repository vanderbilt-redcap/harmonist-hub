<?php
include_once(__DIR__ ."/../projects.php");
$date = new DateTime();

$record_id_metrics = $module->framework->addAutoNumberedRecord(IEDEA_METRICS);
$arrayMetrics = array(array('record_id' => $record_id_metrics));
$arrayMetrics[0]['date'] = $date->format('Y-m-d H:i:s');


/***CONCEPTS***/
$RecordSetConcepts = \REDCap::getData(IEDEA_HARMONIST, 'array', null);
$total_concepts = count($RecordSetConcepts);
$arrayMetrics[0]['concepts'] = $total_concepts;

$RecordSetConceptsActive = \REDCap::getData(IEDEA_HARMONIST, 'array', null,null,null,null,false,false,false,"[active_y] = 'Y'");
$number_concepts_active = count(getProjectInfoArrayRepeatingInstruments($RecordSetConceptsActive));
$arrayMetrics[0]['concepts_a'] = $number_concepts_active;

$RecordSetConceptsCompleted = \REDCap::getData(IEDEA_HARMONIST, 'array', null,null,null,null,false,false,false,"[concept_outcome] = 1");
$number_concepts_completed = count(getProjectInfoArrayRepeatingInstruments($RecordSetConceptsCompleted));
$arrayMetrics[0]['concepts_c'] = $number_concepts_completed;

$RecordSetConceptsDiscontinued = \REDCap::getData(IEDEA_HARMONIST, 'array', null,null,null,null,false,false,false,"[concept_outcome] = 2");
$number_concepts_discontinued = count(getProjectInfoArrayRepeatingInstruments($RecordSetConceptsDiscontinued));
$arrayMetrics[0]['concepts_d'] = $number_concepts_discontinued;

/***REQUESTS***/
$RecordSetRequests = \REDCap::getData(IEDEA_RMANAGER, 'array', null,null,null,null,false,false,false,"[approval_y] != 9");
$total_requests = count(getProjectInfoArray($RecordSetRequests));
$arrayMetrics[0]['requests'] = $total_requests;

$RecordSetRequestsApproved = \REDCap::getData(IEDEA_RMANAGER, 'array', null,null,null,null,false,false,false,"[approval_y] = 1");
$number_requests_approved = count(getProjectInfoArray($RecordSetRequestsApproved));
$arrayMetrics[0]['requests_a'] = $number_requests_approved;

$RecordSetRequestsRejected = \REDCap::getData(IEDEA_RMANAGER, 'array', null,null,null,null,false,false,false,"[approval_y] = 0");
$number_requests_rejected = count(getProjectInfoArray($RecordSetRequestsRejected));
$arrayMetrics[0]['requests_r'] = $number_requests_rejected;

$RecordSetRequestsDeactivated = \REDCap::getData(IEDEA_RMANAGER, 'array', null,null,null,null,false,false,false,"[approval_y] = 9");
$number_requests_deactivated = count(getProjectInfoArray($RecordSetRequestsDeactivated));
$arrayMetrics[0]['requests_d'] = $number_requests_deactivated;


$RecordSetRegions = \REDCap::getData(IEDEA_REGIONS, 'array', null,null,null,null,false,false,false,"[showregion_y] = 1");
$regions = getProjectInfoArray($RecordSetRegions);



#PUBLICATIONS AND ABSTRACTS;
$publications = getProjectInfoArrayRepeatingInstruments($RecordSetConcepts);

$number_publications = 0;
$number_publications_year = 0;
$number_abstracts = 0;
$number_abstracts_year = 0;
foreach ($publications as $outputs){
    foreach ($outputs['output_type'] as $index=>$output_type){
        if($output_type == '1'){
            $number_publications++;
            if($outputs['output_year'][$index] == $date->format('Y')){
                $number_publications_year++;
            }
        }else if($output_type == '2'){
            $number_abstracts++;
            if($outputs['output_year'][$index] == $date->format('Y')){
                $number_abstracts_year++;
            }
        }
    }
}
$arrayMetrics[0]['publications'] = $number_publications;
$arrayMetrics[0]['abstracts'] = $number_abstracts;
$arrayMetrics[0]['publications_current'] = $number_publications_year;
$arrayMetrics[0]['abstracts_current'] = $number_abstracts_year;

#COMMENTS AND VOTES
$RecordSetComments = \REDCap::getData(IEDEA_COMMENTSVOTES, 'array', null);
$comments = getProjectInfoArray($RecordSetComments);
$req_id = array();
foreach ($comments as $comments){
    if($comments['request_id'] != ''){
        array_push($req_id,$comments['request_id']);
    }
}
$req_id = array_unique($req_id);

$query = $module->framework->createQuery();
$query->add("SELECT record FROM redcap_data WHERE field_name = ? AND project_id = ? AND 'value' = ?",["approval_y",IEDEA_RMANAGER,"9"]);
$query->add('and')->addInClause('record ', $req_id);
$query->add('group by record');
$q = $query->execute();
while($row = $q->fetch_assoc()){
    if (($key = array_search($row['record'], $req_id)) !== false) {
        unset($req_id[$key]);
    }
}

$query = $module->framework->createQuery();
$query->add("SELECT a.record FROM redcap_data a INNER JOIN redcap_data b on a.record=b.record and a.project_id=b.project_id WHERE a.field_name = ? AND a.project_id = ? ",["request_id",IEDEA_COMMENTSVOTES]);
$query->add('and')->addInClause('a.value ', $req_id);
$query->add('group by a.record');
$q = $query->execute();
$total_comments = 0;
$comments_id = array();
while($row = $q->fetch_assoc()){
    $total_comments++;
    array_push($comments_id,$row['record']);
}
$arrayMetrics[0]['comments'] = $total_comments;

$query = $module->framework->createQuery();
$query->add("SELECT * FROM redcap_data WHERE field_name = ? AND project_id = ? ",["response_pi_level",IEDEA_COMMENTSVOTES]);
$query->add('and')->addInClause('record ', $comments_id);
$query->add('group by record');
$q = $query->execute();
$number_comments_pi = 0;
$number_comments_nonpi = 0;
while($row = $q->fetch_assoc()){
    if($row['value'] == '1'){
        $number_comments_pi++;
    }else if($row['value'] == '0'){
        $number_comments_nonpi++;
    }
}
$arrayMetrics[0]['comments_pi'] = $number_comments_pi;
$arrayMetrics[0]['comments_n'] = $number_comments_nonpi;

$query = $module->framework->createQuery();
$query->add("SELECT * FROM redcap_data WHERE field_name = ? AND project_id = ? ",["pi_vote",IEDEA_COMMENTSVOTES]);
$query->add('and')->addInClause('record ', $comments_id);
$query->add('group by record');
$q = $query->execute();
$number_votes = 0;
$request_ids = array();
while($row = $q->fetch_assoc()){
    if($row['value'] != ''){
        $number_votes++;
        array_push($request_ids,$row['record']);
    }
}
$arrayMetrics[0]['votes'] = $number_votes;

$query = $module->framework->createQuery();
$query->add("SELECT * FROM redcap_data WHERE field_name = ? AND project_id = ? ",["vote_now",IEDEA_COMMENTSVOTES]);
$query->add('and')->addInClause('record ', $comments_id);
$query->add('group by record');
$q = $query->execute();
$number_votes_later = 0;
while($row = $q->fetch_assoc()){
    if($row['value'] != '0'){
        $number_votes_later++;
    }
}
$arrayMetrics[0]['vote_later'] = $number_votes_later;

$RecordSetComments = \REDCap::getData(IEDEA_COMMENTSVOTES, 'array', null,null,null,null,false,false,false,"[author_revision_y] = 1");
$comments_revision = getProjectInfoArray($RecordSetComments);
#get unique values from matrix column request_id (unique request ids)
$revisions = 0;
foreach ($comments_revision as $comment){
    $RecordSetRM = \REDCap::getData(IEDEA_RMANAGER, 'array', array('request_id' => $comment['request_id']));
    $approval_y = getProjectInfoArray($RecordSetRM)[0]['approval_y'];
    if($approval_y == '1'){
        $revisions++;
    }
}
$arrayMetrics[0]['revisions'] = $revisions;

$RecordRequests = \REDCap::getData(IEDEA_RMANAGER, 'array');
$requests = getProjectInfoArrayRepeatingInstruments($RecordRequests,array('approval_y' => '1'));

$number_votes_completed_before_duedate = 0;
$number_votes_completed_after_duedate = 0;
$completerequests = 0;
$numregions = count($regions);
$completed_requests_by_all_regions = array();
foreach ($requests as $request){
    $votecount = 0;
    foreach ($regions as $region) {
        $instance = $region['record_id'];

        if ($request['region_vote_status'][$instance] != "") {
            $votecount++;
            $request_date = date("Y-m-d", strtotime($request['region_close_ts'][$instance]));
            if (strtotime($request['due_d']) <= strtotime($request_date)) {
                //if vote submitted before or on due date
                $number_votes_completed_before_duedate++;
            } else {
                $number_votes_completed_after_duedate++;
            }
        }

        if ($votecount == $numregions) {
            $completerequests++; //if the number of votes (vote count) equals the number of voting regions, then this request is complete, so increment complete counter
            array_push($completed_requests_by_all_regions, $request['request_id']);
        }
    }
}

foreach ($completed_requests_by_all_regions as $completed){
    $RecordSetRM = \REDCap::getData(IEDEA_RMANAGER, 'array', array('request_id' => $completed));
    $recordRMComplete = getProjectInfoArray($RecordSetRM)[0];
    if($recordRMComplete['detected_complete'][1] != "1") {
        $Proj = new \Project(IEDEA_RMANAGER);
        $event_id_RM = $Proj->firstEventId;
        $arrayRM = array();
        $arrayRM[$comment['request_id']][$event_id_RM]['detected_complete'] = array(1=>"1");//checkbox
        $arrayRM[$comment['request_id']][$event_id_RM]['detected_complete_ts'] = date('Y-m-d H:i:s');
        $results = \Records::saveData(IEDEA_RMANAGER, 'array', $arrayRM,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
    }
}

$number_votes = $number_votes_completed_before_duedate + $number_votes_completed_after_duedate;
$arrayMetrics[0]['votes_c'] = $number_votes_completed_before_duedate;
$number_votes_completed_before_duedate_percent = ($number_votes_completed_before_duedate/$number_votes)*100;
$arrayMetrics[0]['votes_c_percentage'] = round($number_votes_completed_before_duedate_percent,2);

$arrayMetrics[0]['votes_late'] = $number_votes_completed_after_duedate;
$number_votes_completed_after_duedate_percent = ($number_votes_completed_after_duedate/$number_votes)*100;
$arrayMetrics[0]['votes_late_percentage'] = round($number_votes_completed_after_duedate_percent,2);

//REQUESTS COMPLETED
$arrayMetrics[0]['requests_c'] = $completerequests;

#USERS
$query = $module->framework->createQuery();
$query->add("SELECT count(*) as total_registered_users FROM redcap_data WHERE field_name = ? AND project_id = ? AND value in (1,2,3)",["harmonist_regperm",IEDEA_PEOPLE]);
$q = $query->execute();
$arrayMetrics[0]['users'] = $q->fetch_assoc()['total_registered_users'];

$RecordSetUsersPi = \REDCap::getData(IEDEA_PEOPLE, 'array', null,null,null,null,false,false,false,"[harmonist_regperm] = 3");
$number_users_pi = count(getProjectInfoArray($RecordSetUsersPi));
$arrayMetrics[0]['users_pi'] = $number_users_pi;

$query = $module->framework->createQuery();
$query->add("SELECT count(*) as number_users_accesslink FROM redcap_data WHERE field_name = ? AND project_id = ? AND DATEDIFF(NOW(),value) between 0 AND 30",["last_requested_token_d",IEDEA_PEOPLE]);
$q = $query->execute();
$arrayMetrics[0]['users_access'] = $q->fetch_assoc()['number_users_accesslink'];

$RecordSetUsersAdmin = \REDCap::getData(IEDEA_PEOPLE, 'array', null,null,null,null,false,false,false,"[harmonistadmin_y] = 1");
$number_requests_admin = count(getProjectInfoArray($RecordSetUsersAdmin));
$arrayMetrics[0]['admins'] = $number_requests_admin;

$json = json_encode($arrayMetrics);
$results = \Records::saveData(IEDEA_METRICS, 'json', $json,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
\Records::addRecordToRecordListCache(IEDEA_METRICS, $record_id_metrics,1);
?>