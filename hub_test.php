<?php
namespace Vanderbilt\HarmonistHubExternalModule;

#AUTHORIZED PAGE
#ONLY USER BASCOME HAS PERMISSIONS
#TODO: extend this with a test class that has each test
if (defined('USERID') && (USERID == 'bascome' || USERID == 'site_admin')) {
    if (!$module->getSecurityHandler()->setHasNoauthOnUrl()) {
        $pidsArray = $module->getSecurityHandler()->getPidsArray();
        $settings = $module->getSecurityHandler()->getSettingsData();

        echo "<div style='margin: auto;width: 80%;'>";
        echo "<div style='text-align: center;'><h2>".strtoupper($settings['hub_name'])." HUB TESTING PAGE</h2></div>";
        echo "<div style='text-align: center;'>This page is designed to test several features safely.</div>";

        echo "<div style='padding-top: 50px'>";

        #Define which test to activate
        $testOption = "motnh";

        if ($testOption == "requests") {
            echo "<div>This test checks that the number of requests on hearder matches with the number of requests displayed.</div>";

            $hubData = new HubData(
                $module,
                $module->getSecurityHandler()->getTokenSessionName(),
                $module->getSecurityHandler()->getTokenSession(),
                $pidsArray
            );
            $current_user = $hubData->getCurrentUser();
            $requests = $hubData->getAllRequests();
            $person_region = $hubData->getPersonRegion();
            print_array("*User Region: ".$person_region['region_code']);

            $instance = $current_user['person_region'];
            $open_requests_values = [];
            $open_requests_ids = [];
            $request_type = $module->getChoiceLabels('request_type', $pidsArray['RMANAGER']);

            $numberOfOpenRequest = $module->escape(numberOfOpenRequest($requests,$current_user['person_region'],$person_region['voteregion_y'],$settings['pastrequest_dur']));
            print_array("......Total OPEN REQUESTS: ".$numberOfOpenRequest);

            foreach ($requests as $req){
                if ((array_key_exists('type', $_REQUEST) && $_REQUEST['type'] != "" && $req['request_type'] == $_REQUEST['type']) || !array_key_exists('type', $_REQUEST) || (array_key_exists('type', $_REQUEST) && $_REQUEST['type'] == "")) {
                    if (!hideRequestForNonVoters($settings['pastrequest_dur'], $req, $person_region['voteregion_y'])) {
                        if (showClosedRequest($settings, $req, $current_user['person_region'])) {
                            //COMPLETED REQUESTS
                           print_array("COMPLETED: ".$req['request_id'].", ".$request_type[$req['request_type']]);
                        } else if ($current_user['pendingpanel_y___1'] == '1' && showPendingRequest($commentDetails[$req['request_id']], $current_user['person_region'], $req) && $current_user['pendingpanel_y'][0] == '1' && $req['region_response_status'][$current_user['person_region']] != '2') {
                            //PENDING REQUESTS
                            print_array("PENDING: ".$req['request_id'].", ".$request_type[$req['request_type']]);
                        } else if (showOpenRequest($req, $current_user['person_region']) && $req['region_response_status'][$current_user['person_region']] != '2') {
                            //OPEN REQUESTS
                            print_array("OPEN: ".$req['request_id'].", ".$request_type[$req['request_type']]);
                        }
                    }
                }
            }
        }else{
            $params = [
                'project_id' => $pidsArray['RMANAGER'],
                'return_format' => 'array',
                'filterLogic' => "[approval_y] = '1'",
                'filterType' => "RECORD"
            ];
            $RecordSetRM = \REDCap::getData($params);
            $requests = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRM, $pidsArray['SOP']);
            ArrayFunctions::array_sort_by_column($requests, 'due_d', SORT_ASC);

            $numberDaysInCurrentMonth = cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'));
            $expire_date = date('Y-m-d', strtotime(date('Y-m-d') . "-" . $numberDaysInCurrentMonth . " days"));
            $RecordSetReq = \REDCap::getData($pidsArray['RMANAGER'], 'array', null, null, null, null, false, false, false, "[finalize_y] <> '' and [final_d] <>'' and datediff ([final_d], '" . $expire_date . "', \"d\", true) <= 0");
            $requests_hub = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetReq, $pidsArray['RMANAGER']);
            ArrayFunctions::array_sort_by_column($requests_hub, 'final_d', SORT_ASC);

            $RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', null);
            $sops = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP, $pidsArray['SOP'], array('sop_active' => '1', 'sop_finalize_y' => array(1 => '1')));
            ArrayFunctions::array_sort_by_column($sops, 'sop_due_d', SORT_ASC);

            $params = [
                'project_id' => $pidsArray['SOP'],
                'return_format' => 'array',
                'filterLogic' => "[sop_active] = '1' and [sop_finalize_y(1)] = '1'",
                'filterType' => "RECORD"
            ];
            $RecordSetSOP = \REDCap::getData($params);
            $sops = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP, $pidsArray['SOP']);
            ArrayFunctions::array_sort_by_column($sops, 'sop_due_d', SORT_ASC);

            $message = AllCrons::runCronMonthlyDigest(
                $module,
                $pidsArray,
                $requests,
                $requests_hub,
                $sops,
                $settings,
                true
            );
        }
        echo "</div></div>";
    }
}
?>

