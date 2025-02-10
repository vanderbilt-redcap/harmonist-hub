<?php
namespace Vanderbilt\HarmonistHubExternalModule;
include_once(__DIR__ ."/../projects.php");

$isAdmin = false;
if(array_key_exists('isAdmin', $_REQUEST) && ($_REQUEST['isAdmin'] == '1')){
    $isAdmin = true;
    $moduleAux = $module;
    $pidsArray = REDCapManagement::getPIDsArray($pidsArray['PROJECTS']);
}else{
    $moduleAux = $this;
}
$today = strtotime(date("Y-m-d"));
if(strtotime($settings['publications_lastupdate']) < $today || $settings['publications_lastupdate'] == "" || $isAdmin) {
    $RecordSetConceptSheets = \REDCap::getData($pidsArray['HARMONIST'], 'array');
    $concepts = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConceptSheets,$pidsArray['HARMONIST'],"[output_year] <> ''");

    $extra_outputs = \REDCap::getData($pidsArray['EXTRAOUTPUTS'], 'json-array', null);
    ArrayFunctions::array_sort_by_column($extra_outputs, 'output_year', SORT_DESC);

    $abstracts_publications_type = $moduleAux->getChoiceLabels('output_type', $pidsArray['HARMONIST']);
    $abstracts_publications_badge = array("1" => "badge-manuscript", "2" => "badge-abstract", "3" => "badge-poster", "4" => "badge-presentation", "5" => "badge-report", "99" => "badge-other");

    $pubtext3 = empty($settings['pubtext3']) ? $settings['hub_name'] : $settings['pubtext3'];
    $pubtext4 = empty($settings['pubtext4']) ? "Site" : $settings['pubtext4'];
    $pubtext5 = empty($settings['pubtext5']) ? "Multi" : $settings['pubtext5'];

    if (!empty($concepts)) {
        $table_array['data'] = array();
        $records = 0;
        foreach ($concepts as $concept) {
            $output_year = $concept['output_year'];
            if(!empty($output_year)) {
                arsort($output_year);
                foreach ($output_year as $index => $value) {
                    $records++;
                    $available = '';
                    if (!empty($concept['output_citation'][$index])) {
                        $available = htmlentities($concept['output_citation'][$index]) . " ";
                    }
                    if (!empty($concept['output_pmcid'][$index])) {
                        $available .= 'PMCID: <a href="https://www.ncbi.nlm.nih.gov/pmc/articles/' . $concept['output_pmcid'][$index] . '" target="_blank">' . $concept['output_pmcid'][$index] . '<i class="fa fa-fw fa-external-link" aria-hidden="true"></i></a>';
                    }
                    if (!empty($concept['output_url'][$index])) {
                        $available .= ' <a href="' . $concept['output_url'][$index] . '" target="_blank">Link<i class="fa fa-fw fa-external-link" aria-hidden="true"></i></a>';
                    }

                    $file = '';
                    if ($concept['output_file'][$index] != "") {
                        $file = \Vanderbilt\HarmonistHubExternalModule\getFileLink($moduleAux, $pidsArray['PROJECTS'], $concept['output_file'][$index], '1', '', $secret_key, $secret_iv, $current_user['record_id'], "");
                    }

                    $passthru_link = $moduleAux->resetSurveyAndGetCodes($pidsArray['HARMONIST'], $concept['record_id'], "outputs", "",$index);
                    $survey_link = APP_PATH_WEBROOT_FULL . "/surveys/?s=".$moduleAux->escape($passthru_link['hash']);

                    $edit = '<a href="#" class="btn btn-default open-codesModal" onclick="editIframeModal(\'hub_edit_pub\',\'redcap-edit-frame\',\'' . $survey_link . '\');"><em class="fa fa-pencil"></em></a>';

                    $table_aux = array();
                    $table_aux['concept'] = '<a href="' . $moduleAux->getUrl('index.php') .'&NOAUTH&pid=' . $pidsArray['PROJECTS'] . '&option=ttl&record=' . $concept['record_id']. '">' . htmlentities($concept['concept_id']) . '</a>';
                    $table_aux['year'] = '<strong>' . htmlentities($concept['output_year'][$index]) . '</strong>';
                    $table_aux['region'] = '<span class="badge badge-pill badge-draft">'.$pubtext3.'</span>';
                    $table_aux['conf'] = htmlentities($concept['output_venue'][$index]);
                    $table_aux['type'] = htmlentities($abstracts_publications_type[$concept['output_type'][$index]]);
                    $table_aux['title'] = '<span class="badge badge-pill ' . $abstracts_publications_badge[$concept['output_type'][$index]] . '">' . htmlentities($abstracts_publications_type[$concept['output_type'][$index]]) . '</span><span style="display:none">.</span> <strong>' . htmlentities($concept['output_title'][$index]) . '</strong><span style="display:none">.</span> </br><span class="abstract_text">' . htmlentities($concept['output_authors'][$index]) . '</span>';
                    $table_aux['available'] = $available;
                    $table_aux['file'] = $file;
                    $table_aux['edit'] = $edit;

                    array_push($table_array['data'], $table_aux);

                }
            }
        }
        #Regional Content
        foreach ($extra_outputs as $output) {
            $records++;
            $type = "<span class='badge badge-pill badge-draft'>".$pubtext3."</span>";
            if ($output['producedby_region'] == 2) {
                $type = "<span class='badge badge-pill badge-draft'>".$pubtext5."</span>";
            } else if ($output['producedby_region'] == 1) {
                $my_region = \REDCap::getData($pidsArray['REGIONS'], 'json-array', array('record_id' => $output['lead_region']),array('region_code'))[0]['region_code'];
                $type = "<span class='badge badge-pill badge-draft'>".$pubtext4."</span><div><i>" . $my_region . "</i></div>";
            }

            $available = '';
            if (!empty($output['output_citation'])) {
                $available = htmlentities($output['output_citation']) . " ";
            }
            if (!empty($output['output_pmcid'])) {
                $available .= 'PMCID: <a href="https://www.ncbi.nlm.nih.gov/pmc/articles/' . $output['output_pmcid'] . '" target="_blank">' . $output['output_pmcid'] . '<i class="fa fa-fw fa-external-link" aria-hidden="true"></i></a>';
            }
            if (!empty($output['output_url'])) {
                $available .= ' <a href="' . $output['output_url'] . '" target="_blank">Link<i class="fa fa-fw fa-external-link" aria-hidden="true"></i></a>';
            }
            $file = '';
            if ($output['output_file'] != "") {
                $file = \Vanderbilt\HarmonistHubExternalModule\getFileLink($moduleAux, $pidsArray['PROJECTS'], $output['output_file'], '1', '', $secret_key, $secret_iv, $current_user['record_id'], "");
            }

            $passthru_link = $moduleAux->resetSurveyAndGetCodes($pidsArray['EXTRAOUTPUTS'], $output['record_id'], "output_record", "");
            $survey_link = APP_PATH_WEBROOT_FULL . "/surveys/?s=".$moduleAux->escape($passthru_link['hash']);
            $edit = '<a href="#" class="btn btn-default open-codesModal" onclick="editIframeModal(\'hub_edit_pub\',\'redcap-edit-frame\',\'' . $survey_link . '\');"><em class="fa fa-pencil"></em></a>';

            $table_aux = array();
            $table_aux['concept'] = '<i>None</i>';
            $table_aux['year'] = htmlentities($output['output_year']);
            $table_aux['region'] = $type;
            $table_aux['conf'] = htmlentities($output['output_venue']);
            $table_aux['type'] = htmlentities($abstracts_publications_type[$output['output_type']]);
            $table_aux['title'] = '<span class="badge badge-pill ' . $abstracts_publications_badge[$output['output_type']] . '">' . htmlentities($abstracts_publications_type[$output['output_type']]) . '</span><span style="display:none">.</span> <strong>' . htmlentities($output['output_title']) . '</strong><span style="display:none">.</span> </br><span class=n"abstract_text">' . htmlentities($output['output_authors']) . '</span>';
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
    $storedName = date("YmdsH") . "_pid" . $pidsArray['SETTINGS'] . "_" . \Vanderbilt\HarmonistHubExternalModule\getRandomIdentifier(6) . ".txt";

    $file = fopen(EDOC_PATH . $storedName, "wb");
    fwrite($file, json_encode($table_array, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_PRETTY_PRINT));
    fclose($file);

    $output = file_get_contents($moduleAux->getSafePath(EDOC_PATH.$storedName,EDOC_PATH));
    $filesize = file_put_contents(EDOC_PATH . $storedName, $output);

    //Save document on DB
    $docId = \REDCap::storeFile(EDOC_PATH . $storedName,  $pidsArray['SETTINGS'], $filename);

    //Add document DB ID to project
    $Proj = new \Project($pidsArray['SETTINGS']);
    $event_id = $Proj->firstEventId;
    $json = json_encode(array(array('record_id' => 1, 'publications_json' => $docId,'publications_lastupdate' => date("Y-m-d H:m:s"))));
    $results = \Records::saveData($pidsArray['SETTINGS'], 'json', $json, 'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
    \Records::addRecordToRecordListCache($pidsArray['SETTINGS'], 1, $event_id);
}
session_start();
?>