<?php
namespace Vanderbilt\HarmonistHubExternalModule;
$date = new \DateTime();

$RecordSetUsers = \REDCap::getData($pidsArray['PEOPLE'], 'array', null);
$record_id_metrics = $this->framework->addAutoNumberedRecord($pidsArray['METRICS']);
$arrayMetrics = array();
$arrayMetrics = array(array('record_id' => $record_id_metrics));
$arrayMetrics[0]['date'] = $date->format('Y-m-d H:i:s');

/***CONCEPTS***/
$RecordSetConcepts = \REDCap::getData($pidsArray['HARMONIST'], 'array', null);
$total_concepts = count($RecordSetConcepts);
$arrayMetrics[0]['concepts'] = $total_concepts;

$RecordSetConceptsActive = \REDCap::getData($pidsArray['HARMONIST'], 'array', null, null, null, null, false, false, false, "[active_y] = 'Y'");
$number_concepts_active = count(ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConceptsActive,$pidsArray['HARMONIST']));
$arrayMetrics[0]['concepts_a'] = $number_concepts_active;

$RecordSetConceptsCompleted = \REDCap::getData($pidsArray['HARMONIST'], 'array', null, null, null, null, false, false, false, "[concept_outcome] = 1");
$number_concepts_completed = count(ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConceptsCompleted,$pidsArray['HARMONIST']));
$arrayMetrics[0]['concepts_c'] = $number_concepts_completed;

$RecordSetConceptsDiscontinued = \REDCap::getData($pidsArray['HARMONIST'], 'array', null, null, null, null, false, false, false, "[concept_outcome] = 2");
$number_concepts_discontinued = count(ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConceptsDiscontinued,$pidsArray['HARMONIST']));
$arrayMetrics[0]['concepts_d'] = $number_concepts_discontinued;

/***REQUESTS***/
$RecordSetRequests = \REDCap::getData($pidsArray['RMANAGER'], 'array', null, null, null, null, false, false, false, "[approval_y] != 9");
$total_requests = count(ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRequests,$pidsArray['RMANAGER']));
$arrayMetrics[0]['requests'] = $total_requests;

$RecordSetRequestsApproved = \REDCap::getData($pidsArray['RMANAGER'], 'array', null, null, null, null, false, false, false, "[approval_y] = 1");
$number_requests_approved = count(ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRequestsApproved,$pidsArray['RMANAGER']));
$arrayMetrics[0]['requests_a'] = $number_requests_approved;

$RecordSetRequestsRejected = \REDCap::getData($pidsArray['RMANAGER'], 'array', null, null, null, null, false, false, false, "[approval_y] = 0");
$number_requests_rejected = count(ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRequestsRejected,$pidsArray['RMANAGER']));
$arrayMetrics[0]['requests_r'] = $number_requests_rejected;

$RecordSetRequestsDeactivated = \REDCap::getData($pidsArray['RMANAGER'], 'array', null, null, null, null, false, false, false, "[approval_y] = 9");
$number_requests_deactivated = count(ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRequestsDeactivated,$pidsArray['RMANAGER']));
$arrayMetrics[0]['requests_d'] = $number_requests_deactivated;


$regions = \REDCap::getData($pidsArray['REGIONS'], 'json-array', null, null, null, null, false, false, false, "[showregion_y] = 1");

#PUBLICATIONS AND ABSTRACTS;
$publications = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConcepts,$pidsArray['HARMONIST']);

$number_publications = 0;
$number_publications_year = 0;
$number_abstracts = 0;
$number_abstracts_year = 0;
foreach ($publications as $outputs) {
    if(is_array($outputs) && array_key_exists("output_type", $outputs) && is_array($outputs["output_type"])) {
        foreach ($outputs['output_type'] as $index => $output_type) {
            if ($output_type == '1') {
                $number_publications++;
                if (is_array($outputs['output_year']) && array_key_exists($index,$outputs['output_year']) && $outputs['output_year'][$index] == $date->format('Y')) {
                    $number_publications_year++;
                }
            } else {
                if ($output_type == '2') {
                    $number_abstracts++;
                    if (is_array($outputs['output_year']) && array_key_exists($index,$outputs['output_year']) && $outputs['output_year'][$index] == $date->format('Y')) {
                        $number_abstracts_year++;
                    }
                }
            }
        }
    }
}
$arrayMetrics[0]['publications'] = $number_publications;
$arrayMetrics[0]['abstracts'] = $number_abstracts;
$arrayMetrics[0]['publications_current'] = $number_publications_year;
$arrayMetrics[0]['abstracts_current'] = $number_abstracts_year;

#COMMENTS AND VOTES
$comments = \REDCap::getData($pidsArray['COMMENTSVOTES'], 'json-array', null);
$req_id = array();
foreach ($comments as $comments) {
    if ($comments['request_id'] != '') {
        array_push($req_id, $comments['request_id']);
    }
}
$req_id = array_unique($req_id);

$query = $this->framework->createQuery();
$query->add("SELECT record FROM ".getDataTable($pidsArray['RMANAGER'])." WHERE field_name = ? AND project_id = ? AND value = ?", ["approval_y", $pidsArray['RMANAGER'], "9"]);
$query->add('and')->addInClause('record ', $req_id);
$query->add('group by record');
$q = $query->execute();
while ($row = $q->fetch_assoc()) {
    if (($key = array_search($row['record'], $req_id)) !== false) {
        unset($req_id[$key]);
    }
}

$query = $this->framework->createQuery();
$query->add("SELECT a.record FROM ".getDataTable($pidsArray['COMMENTSVOTES'])." a INNER JOIN ".getDataTable($pidsArray['COMMENTSVOTES'])." b on a.record=b.record and a.project_id=b.project_id WHERE a.field_name = ? AND a.project_id = ? ", ["request_id", $pidsArray['COMMENTSVOTES']]);
$query->add('and')->addInClause('a.value ', $req_id);
$query->add('group by a.record');
$q = $query->execute();
$total_comments = 0;
$comments_id = array();
while ($row = $q->fetch_assoc()) {
    $total_comments++;
    array_push($comments_id, $row['record']);
}
$arrayMetrics[0]['comments'] = $total_comments;

$query = $this->framework->createQuery();
$query->add("SELECT * FROM ".getDataTable($pidsArray['COMMENTSVOTES'])." WHERE field_name = ? AND project_id = ? ", ["response_pi_level", $pidsArray['COMMENTSVOTES']]);
$query->add('and')->addInClause('record ', $comments_id);
$query->add('group by record');
$q = $query->execute();
$number_comments_pi = 0;
$number_comments_nonpi = 0;
while ($row = $q->fetch_assoc()) {
    if ($row['value'] == '1') {
        $number_comments_pi++;
    } else if ($row['value'] == '0') {
        $number_comments_nonpi++;
    }
}
$arrayMetrics[0]['comments_pi'] = $number_comments_pi;
$arrayMetrics[0]['comments_n'] = $number_comments_nonpi;

$query = $this->framework->createQuery();
$query->add("SELECT * FROM ".getDataTable($pidsArray['COMMENTSVOTES'])." WHERE field_name = ? AND project_id = ? ", ["pi_vote", $pidsArray['COMMENTSVOTES']]);
$query->add('and')->addInClause('record ', $comments_id);
$query->add('group by record');
$q = $query->execute();
$number_votes = 0;
$request_ids = array();
while ($row = $q->fetch_assoc()) {
    if ($row['value'] != '') {
        $number_votes++;
        array_push($request_ids, $row['record']);
    }
}
$arrayMetrics[0]['votes'] = $number_votes;

$query = $this->framework->createQuery();
$query->add("SELECT * FROM ".getDataTable($pidsArray['COMMENTSVOTES'])." WHERE field_name = ? AND project_id = ? ", ["vote_now", $pidsArray['COMMENTSVOTES']]);
$query->add('and')->addInClause('record ', $comments_id);
$query->add('group by record');
$q = $query->execute();
$number_votes_later = 0;
while ($row = $q->fetch_assoc()) {
    if ($row['value'] != '0') {
        $number_votes_later++;
    }
}
$arrayMetrics[0]['vote_later'] = $number_votes_later;

$comments_revision = \REDCap::getData($pidsArray['COMMENTSVOTES'], 'json-array', null, null, null, null, false, false, false, "[author_revision_y] = 1");
#get unique values from matrix column request_id (unique request ids)
$revisions = 0;
foreach ($comments_revision as $comment) {
    $approval_data = \REDCap::getData($pidsArray['RMANAGER'], 'json-array', array('request_id' => $comment['request_id']),array('approval_y'));
    if($approval_data != null){
        $approval_y = $approval_data[0]['approval_y'];
        if ($approval_y == '1') {
            $revisions++;
        }
    }
}
$arrayMetrics[0]['revisions'] = $revisions;

$RecordRequests = \REDCap::getData($pidsArray['RMANAGER'], 'array');
$requests = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordRequests,$pidsArray['RMANAGER'], array('approval_y' => '1'));

$number_votes_completed_before_duedate = 0;
$number_votes_completed_after_duedate = 0;
$completerequests = 0;
$numregions = count($regions);
$completed_requests_by_all_regions = array();
if(is_array($requests) && !empty($requests)) {
    foreach ($requests as $request) {
        $votecount = 0;
        foreach ($regions as $region) {
            $instance = $region['record_id'];
            if(array_key_exists("region_vote_status", $request) && is_array($request['region_vote_status'])) {
                if (array_key_exists($instance, $request['region_vote_status']) && $request['region_vote_status'][$instance] != "") {
                    $votecount++;
                    $request_date = date("Y-m-d", strtotime($request['region_close_ts'][$instance]));
                    if (strtotime($request['due_d']) <= strtotime($request_date)) {
                        //if vote submitted before or on due date
                        $number_votes_completed_before_duedate++;
                    } else {
                        $number_votes_completed_after_duedate++;
                    }
                }
            }

            if ($votecount == $numregions) {
                $completerequests++; //if the number of votes (vote count) equals the number of voting regions, then this request is complete, so increment complete counter
                array_push($completed_requests_by_all_regions, $request['request_id']);
            }
        }
    }
}

foreach ($completed_requests_by_all_regions as $completed) {
    $RecordSetRM = \REDCap::getData($pidsArray['RMANAGER'], 'array', array('request_id' => $completed));
    $recordRMComplete = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRM,$pidsArray['RMANAGER'])[0];
    if ($recordRMComplete['detected_complete'][1] != "1") {
        $Proj = new \Project($pidsArray['RMANAGER']);
        $event_id_RM = $Proj->firstEventId;
        $arrayRM = array();
        $arrayRM[$comment['request_id']][$event_id_RM]['detected_complete'] = array(1 => "1");//checkbox
        $arrayRM[$comment['request_id']][$event_id_RM]['detected_complete_ts'] = date('Y-m-d H:i:s');
        $results = \Records::saveData($pidsArray['RMANAGER'], 'array', $arrayRM, 'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
        \REDCap::logEvent("Metrics Cron", "detected_complete(1) = checked", NULL, $comment['request_id'], $event_id_RM, $pidsArray['RMANAGER']);
    }
}

$number_votes = $number_votes_completed_before_duedate + $number_votes_completed_after_duedate;
$arrayMetrics[0]['votes_c'] = $number_votes_completed_before_duedate;
if($number_votes_completed_before_duedate == 0){
    $arrayMetrics[0]['votes_c_percentage'] = 0;
}else{
    $number_votes_completed_before_duedate_percent = ($number_votes_completed_before_duedate / $number_votes) * 100;
    $arrayMetrics[0]['votes_c_percentage'] = round($number_votes_completed_before_duedate_percent, 2);
}


$arrayMetrics[0]['votes_late'] = $number_votes_completed_after_duedate;
if($number_votes_completed_after_duedate == 0){
    $arrayMetrics[0]['votes_late_percentage'] = 0;
}else{
    $number_votes_completed_after_duedate_percent = ($number_votes_completed_after_duedate / $number_votes) * 100;
    $arrayMetrics[0]['votes_late_percentage'] = round($number_votes_completed_after_duedate_percent, 2);
}

//REQUESTS COMPLETED
$arrayMetrics[0]['requests_c'] = $completerequests;

#USERS
$query = $this->framework->createQuery();
$query->add("SELECT count(*) as total_registered_users FROM ".getDataTable($pidsArray['PEOPLE'])." WHERE field_name = ? AND project_id = ? AND value in (1,2,3)", ["harmonist_regperm", $pidsArray['PEOPLE']]);
$q = $query->execute();
$arrayMetrics[0]['users'] = $q->fetch_assoc()['total_registered_users'];

$number_users_pi = count(\REDCap::getData($pidsArray['PEOPLE'], 'json-array', null, null, null, null, false, false, false, "[harmonist_regperm] = 3"));
$arrayMetrics[0]['users_pi'] = $number_users_pi;

$query = $this->framework->createQuery();
$query->add("SELECT count(*) as number_users_accesslink FROM ".getDataTable($pidsArray['PEOPLE'])." WHERE field_name = ? AND project_id = ? AND DATEDIFF(NOW(),value) between 0 AND 30", ["last_requested_token_d", $pidsArray['PEOPLE']]);
$q = $query->execute();
$arrayMetrics[0]['users_access'] = $q->fetch_assoc()['number_users_accesslink'];

$number_requests_admin = count(\REDCap::getData($pidsArray['PEOPLE'], 'json-array', null, null, null, null, false, false, false, "[harmonistadmin_y] = 1"));
$arrayMetrics[0]['admins'] = $number_requests_admin;

$json = json_encode($arrayMetrics,JSON_FORCE_OBJECT);
$results = \Records::saveData($pidsArray['METRICS'], 'json', $json, 'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
\Records::addRecordToRecordListCache($pidsArray['METRICS'], $record_id_metrics, 1);
?>
