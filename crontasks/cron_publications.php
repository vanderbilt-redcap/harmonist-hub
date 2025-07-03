<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once dirname(dirname(__FILE__))."/projects.php";

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

    $user_record = empty($current_user) ? null : $current_user['record_id'];

    if(!isset($secret_key) || !isset($secret_iv)){
        $secret_key = "";
        $secret_iv = "";
    }

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
                    if (array_key_exists('output_file',$concept) && is_array($concept['output_file']) && array_key_exists($index,$concept['output_file']) && $concept['output_file'][$index] !== "") {
                        $file = getFileLink($moduleAux, $pidsArray['PROJECTS'], $concept['output_file'][$index], '1', '', $secret_key, $secret_iv, $user_record, "");
                    }

                    $passthru_link = $moduleAux->resetSurveyAndGetCodes($pidsArray['HARMONIST'], $concept['record_id'], "outputs", "",$index);
                    $survey_link = APP_PATH_WEBROOT_FULL . "/surveys/?s=".$moduleAux->escape($passthru_link['hash']);

                    $edit = '<a href="#" class="btn btn-default open-codesModal" onclick="editIframeModal(\'hub_edit_pub\',\'redcap-edit-frame\',\'' . $survey_link . '\');"><em class="fa fa-pencil"></em></a>';

                    $badge = "";
                    $output_type = "";
                    if(is_array($concept['output_type']) && array_key_exists($index,$concept['output_type']) && !empty($concept['output_type'][$index])) {
                        if(array_key_exists($concept['output_type'][$index], $abstracts_publications_badge)){
                            $badge = $abstracts_publications_badge[$concept['output_type'][$index]];
                        }
                        if(array_key_exists($concept['output_type'][$index], $abstracts_publications_type)){
                            $output_type = htmlentities($abstracts_publications_type[$concept['output_type'][$index]]);
                        }
                    }

                    $authors = "";
                    if(is_array($concept['output_authors']) && array_key_exists($index,$concept['output_authors'])) {
                        $authors = $concept['output_authors'][$index];
                    }
                    $title = "";
                    if(is_array($concept['output_title']) && array_key_exists($index,$concept['output_title'])) {
                        $title = $concept['output_title'][$index];
                    }
                    $venue = "";
                    if(is_array($concept['output_venue']) && array_key_exists($index,$concept['output_venue'])) {
                        $venue = htmlentities($concept['output_venue'][$index]);
                    }
                    $year = "";
                    if(is_array($concept['output_year']) && array_key_exists($index,$concept['output_year'])) {
                        $year = htmlentities($concept['output_year'][$index]);
                    }

                    $table_aux = array();
                    $table_aux['concept'] = '<a href="' . $moduleAux->getUrl('index.php') .'&NOAUTH&pid=' . $pidsArray['PROJECTS'] . '&option=ttl&record=' . $concept['record_id']. '">' . htmlentities($concept['concept_id']) . '</a>';
                    $table_aux['year'] = '<strong>' . $year . '</strong>';
                    $table_aux['region'] = '<span class="badge badge-pill badge-draft">'.$pubtext3.'</span>';
                    $table_aux['conf'] = $venue;
                    $table_aux['type'] = $output_type;
                    $table_aux['title'] = '<span class="badge badge-pill ' . $badge . '">' . $output_type . '</span><span style="display:none">.</span> <strong>' . htmlentities($title) . '</strong><span style="display:none">.</span> </br><span class="abstract_text">' . htmlentities($authors) . '</span>';
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
            } else if ($output['producedby_region'] == 1 && !empty($output['lead_region'])) {
                $regionData = \REDCap::getData($pidsArray['REGIONS'], 'json-array', array('record_id' => $output['lead_region']),array('region_code'));
                $my_region = "";
                if(!empty($regionData) && array_key_exists(0, $regionData) && array_key_exists($output['region_code'], $regionData[0])) {
                    $my_region = $regionData[0]['region_code'];
                }
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
                $file = getFileLink($moduleAux, $pidsArray['PROJECTS'], $output['output_file'], '1', '', $secret_key, $secret_iv, $user_record, "");
            }

            $passthru_link = $moduleAux->resetSurveyAndGetCodes($pidsArray['EXTRAOUTPUTS'], $output['record_id'], "output_record", "");
            $edit = "";
            if(is_array($passthru_link) && array_key_exists('hash', $passthru_link)) {
                $survey_link = APP_PATH_WEBROOT_FULL . "/surveys/?s=".$moduleAux->escape($passthru_link['hash']);
                $edit = '<a href="#" class="btn btn-default open-codesModal" onclick="editIframeModal(\'hub_edit_pub\',\'redcap-edit-frame\',\'' . $survey_link . '\');"><em class="fa fa-pencil"></em></a>';
            }

            $output_type = "";
            $badge = "";
            if(!empty($output['output_type'])) {
                if(array_key_exists($output['output_type'], $abstracts_publications_badge)){
                    $badge = $abstracts_publications_badge[$output['output_type']];
                }
                if(array_key_exists($output['output_type'], $abstracts_publications_type)){
                    $output_type = htmlentities($abstracts_publications_type[$output['output_type']]);
                }
            }
            $table_aux = array();
            $table_aux['concept'] = '<i>None</i>';
            $table_aux['year'] = htmlentities($output['output_year']);
            $table_aux['region'] = $type;
            $table_aux['conf'] = htmlentities($output['output_venue']);
            $table_aux['type'] = $output_type;
            $table_aux['title'] = '<span class="badge badge-pill ' . $badge . '">' . $output_type . '</span> <strong>' . $output['output_title'] . '</strong></br><span class="abstract_text">' . $output['output_authors'] . '</span>';
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
    $storedName = date("YmdsH") . "_pid" . $pidsArray['SETTINGS'] . "_" . getRandomIdentifier(6) . ".txt";

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
?>