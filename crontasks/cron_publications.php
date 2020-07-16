<?php
include_once(__DIR__ ."/../projects.php");

$isAdmin = false;
if(array_key_exists('isAdmin', $_REQUEST) && ($_REQUEST['isAdmin'] == '1')){
    $isAdmin = true;
}
$today = strtotime(date("Y-m-d"));
if(strtotime($settings['publications_lastupdate']) < $today || $settings['publications_lastupdate'] == "" || $isAdmin) {
    $RecordSetConceptSheets = \REDCap::getData(IEDEA_HARMONIST, 'array', null,null,null,null,false,false,false,"[output_year] <> ''");
    $concepts = getProjectInfoArrayRepeatingInstruments($RecordSetConceptSheets);

    $RecordSetExtraOut = \REDCap::getData(IEDEA_EXTRAOUTPUTS, 'array', null);
    $extra_outputs = getProjectInfoArray($RecordSetExtraOut);
    array_sort_by_column($extra_outputs, 'output_year', SORT_DESC);

    $abstracts_publications_type = $module->getChoiceLabels('output_type', IEDEA_HARMONIST);
    $abstracts_publications_badge = array("1" => "badge-manuscript", "2" => "badge-abstract", "3" => "badge-poster", "4" => "badge-presentation", "5" => "badge-report", "99" => "badge-other");
    $abstracts_publications_badge_text = array("1" => "badge-manuscript-text", "2" => "badge-abstract-text", "3" => "badge-poster-text", "4" => "badge-presentation-text", "5" => "badge-report-text", "99" => "badge-other-text");

    if (!empty($concepts)) {
        $table_array['data'] = array();
        $records = 0;
        foreach ($concepts as $concept) {
            $output_year = $concept['output_year'];
            arsort($output_year);
            foreach ($output_year as $index => $value) {
                $records++;
                $instance = $index;
                if ($index == '') {
                    $instance = 1;
                }

                $available = '';
                if (!empty($concept['output_citation'][$index])) {
                    $available = $concept['output_citation'][$index] . " ";
                }
                if (!empty($concept['output_pmcid'][$index])) {
                    $available .= 'PMCID: <a href="https://www.ncbi.nlm.nih.gov/pmc/articles/' . $concept['output_pmcid'][$index] . '" target="_blank">' . $concept['output_pmcid'][$index] . '<i class="fa fa-fw fa-external-link" aria-hidden="true"></i></a>';
                }
                if (!empty($concept['output_url'][$index])) {
                    $available .= ' <a href="' . $concept['output_url'][$index] . '" target="_blank">Link<i class="fa fa-fw fa-external-link" aria-hidden="true"></i></a>';
                }

                $file = '';
                if ($concept['output_file'][$index] != "") {
                    $file = getFileLink($module, $concept['output_file'][$index], '1', '', $secret_key, $secret_iv, $current_user['record_id'], "");
                }

                $passthru_link = $module->resetSurveyAndGetCodes(IEDEA_HARMONIST, $concept['record_id'], "output_record", "");
                $survey_link = $module->getUrl('surveyPassthru.php?&surveyLink='.APP_PATH_SURVEY_FULL . "?s=".$passthru_link['hash']);

                $edit = '<a href="#" class="btn btn-default open-codesModal" onclick="editIframeModal(\'hub_edit_pub\',\'redcap-edit-frame\',\'' . $survey_link . '\');"><em class="fa fa-pencil"></em></a>';

                $table_aux = array();
                $table_aux['concept'] = '<a href="'.$module->getUrl('index.php?pid=' . IEDEA_PROJECTS . '&option=ttl&record=' . $concept['record_id']) . '">' . $concept['concept_id'] . '</a>';
                $table_aux['year'] = '<strong>' . $concept['output_year'][$index] . '</strong>';
                $table_aux['region'] = '<span class="badge badge-pill badge-draft">MR</span>';
                $table_aux['conf'] = $concept['output_venue'][$index];
                $table_aux['type'] = $abstracts_publications_type[$concept['output_type'][$index]];
                $table_aux['title'] = '<span class="badge badge-pill ' . $abstracts_publications_badge[$concept['output_type'][$index]] . '">' . $abstracts_publications_type[$concept['output_type'][$index]] . '</span><span style="display:none">.</span> <strong>' . $concept['output_title'][$index] . '</strong><span style="display:none">.</span> </br><span class="abstract_text">' . $concept['output_authors'][$index] . '</span>';
                $table_aux['available'] = $available;
                $table_aux['file'] = $file;
                $table_aux['edit'] = $edit;

                array_push($table_array['data'], $table_aux);

            }
        }
        #Regional Content
        foreach ($extra_outputs as $output) {
            $records++;
            $type = "<span class='badge badge-pill badge-draft'>R</span>";
            if ($output['producedby_region'] == 2) {
                $type = "<span class='badge badge-pill badge-draft'>MR</span>";
            } else if ($output['producedby_region'] == 1) {
                $RecordSetMyRegion = \REDCap::getData(IEDEA_REGIONS, 'array', array('record_id' => $output['lead_region']));
                $my_region = getProjectInfoArray($RecordSetMyRegion)[0]['region_code'];
                $type = "<span class='badge badge-pill badge-draft'>R</span><div><i>" . $my_region . "</i></div>";
            }

            $available = '';
            if (!empty($output['output_citation'])) {
                $available = $output['output_citation'] . " ";
            }
            if (!empty($output['output_pmcid'])) {
                $available .= 'PMCID: <a href="https://www.ncbi.nlm.nih.gov/pmc/articles/' . $output['output_pmcid'] . '" target="_blank">' . $output['output_pmcid'] . '<i class="fa fa-fw fa-external-link" aria-hidden="true"></i></a>';
            }
            if (!empty($output['output_url'])) {
                $available .= ' <a href="' . $output['output_url'] . '" target="_blank">Link<i class="fa fa-fw fa-external-link" aria-hidden="true"></i></a>';
            }
            $file = '';
            if ($output['output_file'] != "") {
                $file = getFileLink($module, $output['output_file'], '1', '', $secret_key, $secret_iv, $current_user['record_id'], "");
            }

            $passthru_link = $module->resetSurveyAndGetCodes(IEDEA_EXTRAOUTPUTS, $output['record_id'], "output_record", "");
            $survey_link = $module->getUrl('surveyPassthru.php?&surveyLink='.APP_PATH_SURVEY_FULL . "?s=".$passthru_link['hash']);
            $edit = '<a href="#" class="btn btn-default open-codesModal" onclick="editIframeModal(\'hub_edit_pub\',\'redcap-edit-frame\',\'' . $survey_link . '\');"><em class="fa fa-pencil"></em></a>';

            $table_aux = array();
            $table_aux['concept'] = '<i>None</i>';
            $table_aux['year'] = $output['output_year'];
            $table_aux['region'] = $type;
            $table_aux['conf'] = $output['output_venue'];
            $table_aux['type'] = $abstracts_publications_type[$output['output_type']];
            $table_aux['title'] = '<span class="badge badge-pill ' . $abstracts_publications_badge[$output['output_type']] . '">' . $abstracts_publications_type[$output['output_type']] . '</span><span style="display:none">.</span> <strong>' . $output['output_title'] . '</strong><span style="display:none">.</span> </br><span class=n"abstract_text">' . $output['output_authors'] . '</span>';
            $table_aux['available'] = $available;
            $table_aux['file'] = $file;
            $table_aux['edit'] = $edit;

            array_push($table_array['data'], $table_aux);
        }
        $table_array["draw"] = 10;
        $table_array["recordsTotal"] = $records;
        $table_array["recordsFiltered"] = $records;
    }


    #create and save file with json
    $filename = "jsoncopy_file_publications_" . date("YmdsH") . ".txt";
    $storedName = date("YmdsH") . "_pid" . IEDEA_SETTINGS . "_" . getRandomIdentifier(6) . ".txt";

    $file = fopen(EDOC_PATH . $storedName, "wb");
    fwrite($file, json_encode($table_array, JSON_HEX_QUOT | JSON_HEX_TAG));
    fclose($file);

    $output = file_get_contents(EDOC_PATH . $storedName);
    $filesize = file_put_contents(EDOC_PATH . $storedName, $output);
    //Save document on DB
    $module->query("INSERT INTO redcap_edocs_metadata (stored_name,doc_name,doc_size,file_extension,mime_type,gzipped,project_id,stored_date) VALUES (?,?,?,?,?,?,?,?)",[$storedName,$filename,$filesize,'txt','application/octet-stream','0',IEDEA_SETTINGS, date('Y-m-d h:i:s')]);
    $docId = db_insert_id();

    //Add document DB ID to project
    $Proj = new \Project(IEDEA_SETTINGS);
    $event_id = $Proj->firstEventId;
    $json = json_encode(array(array('record_id' => 1, 'publications_json' => $docId,'publications_lastupdate' => date("Y-m-d H:m:s"))));
    $results = \Records::saveData(IEDEA_SETTINGS, 'json', $json, 'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
    \Records::addRecordToRecordListCache(IEDEA_SETTINGS, 1, $event_id);

}
?>