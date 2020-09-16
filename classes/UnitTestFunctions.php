<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once(dirname(dirname(__FILE__))."/classes/REDCapManagement.php");
require_once(dirname(dirname(__FILE__))."/classes/AllCrons.php");
require_once dirname(dirname(__FILE__)) . "/projects.php";

use ExternalModules\ExternalModules;


class UnitTestFunctions
{
    public function __construct($module){
        $this->module = $module;
//        parent::__construct();
    }

    function testCrons(){
        self::testCronDataUploadExpirationReminder();
        self::testCronDataUploadNotification();
        self::testCronMonthlyDigest();
        self::testCronDeleteAws();
    }

    function getRequestDU(){
        $request_DU = array(
            0 => array(
                'record_id' => 0,
                'responsecomplete_ts' => date('Y-m-d'),
                'data_upload_person' => '',
                'data_assoc_concept' => ''
            )
        );
        return $request_DU;
    }

    function getPeopleDown(){
        $peopleDown = array(
            1 => array('firstname' => 'Eva',
                'lastname' => 'Bascompte',
                'email' => 'eva.bascompte.moragas@vumc.org',
                'record_id' => '1'
            ),
            2 => array('firstname' => 'Stephanie',
                'lastname' => 'Duda',
                'email' => 'tephanie.duda@vumc.org',
                'record_id' => '2'
            )
        );
        return $peopleDown;
    }

    function getSettings(){
        return array('downloadreminder_dur' => 15,'downloadreminder2_dur' => 3);
    }

    function getDigestRequests(){
        $requests = array(
            0 => array(
                'record_id' => 0,
                'due_d' => date('Y-m-d'),
                'request_type' => '',
                'assoc_concept' => '',
                'final_d' => '',
                'sop_concept_id' => '0'
            )
        );
        return $requests;
    }

    function getTestOutputMessage($testMsg,$value,$valueTest){
        if($value == $valueTest) {
            return '<div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;">
                <span class="fa fa-check text-approved"></span> The test on ' . $testMsg . ' has been successful.
              </div>';
        }else{
            return '<div class="alert alert-danger fade in col-md-12">
                <span class="fa fa-times"></span> The test on ' . $testMsg . ' has been failed.
              </div>';
        }
    }

    function testCronDataUploadExpirationReminder(){
        $settings = self::getSettings();
        $extra_days = " + 0 days";
        $extra_days2 = " + 0 days";
        $extra_days_delete = " + 0 days";
        $messageArray = array();
        foreach (self::getRequestDU() as $upload) {
            $message = AllCrons::runCronDataUploadExpirationReminder(
                $this->module,
                $upload,
                array('sop_downloaders' => '1,2'),
                self::getPeopleDown(),
                $extra_days_delete,
                $extra_days,
                $extra_days2,
                $settings,
                false
            );
            array_push($messageArray,$message);
        }

        echo $this->getTestOutputMessage('Data Upload Expiration Reminder CRON',2, $messageArray[0][$settings['downloadreminder_dur']]);

        $RecordSetDU = \REDCap::getData(IEDEA_DATAUPLOAD, 'array', null);
        $request_DU = getProjectInfoArray($RecordSetDU);

        $RecordSetSettings = \REDCap::getData(IEDEA_SETTINGS, 'array', null);
        $settings = getProjectInfoArray($RecordSetSettings)[0];

        $days_expiration = intval($settings['downloadreminder_dur']);
        $expire_number = $settings['retrievedata_expiration'] - $days_expiration;
        $extra_days = ' + ' . $expire_number . " days";
        $days_expiration2 = intval($settings['downloadreminder2_dur']);
        $expire_number2 = $settings['retrievedata_expiration'] - $days_expiration2;
        $extra_days2 = ' + ' . $expire_number2 . " days";

        $days_expiration_delete = intval($settings['retrievedata_expiration']);
        $extra_days_delete = ' + ' . $days_expiration_delete . " days";

        $messageArrayData = array();
        foreach ($request_DU as $upload) {
            $RecordSetSOP = \REDCap::getData(IEDEA_SOP, 'array', array('record_id' => $upload['data_assoc_request']));
            $sop = getProjectInfoArrayRepeatingInstruments($RecordSetSOP)[0];

            $messageArrayRealData = AllCrons::runCronDataUploadExpirationReminder(
                $this->module,
                $upload,
                $sop,
                null,
                $extra_days_delete,
                $extra_days,
                $extra_days2,
                $settings,
                false
            );

            if(!empty($messageArrayRealData)){
                array_push($messageArrayData,$messageArrayRealData);
            }
        }

        if(!empty($messageArrayData)){
            $result = array();
            foreach ($messageArrayData as $data){
                if(array_key_exists($data['concept_title'],$result)){
                    $result[$data['concept_title']]['numDownloaders'] += $data['numDownloaders'];
                }else{
                    $result[$data['concept_title']] = array();
                    $result[$data['concept_title']]['sop_id'] = array();
                    $result[$data['concept_title']][$settings['downloadreminder_dur']] = array();
                    $result[$data['concept_title']][$settings['downloadreminder2_dur']] = array();
                    $result[$data['concept_title']]['numDownloaders'] = $data['numDownloaders'];
                }
                $result['total'] += $data['numDownloaders'];
                array_push($result[$data['concept_title']]['sop_id'], $data['sop_id']);
                array_push($result[$data['concept_title']][$settings['downloadreminder_dur']], empty($data[$settings['downloadreminder_dur']])?0:$data[$settings['downloadreminder_dur']]);
                array_push($result[$data['concept_title']][$settings['downloadreminder2_dur']], empty($data[$settings['downloadreminder2_dur']])?0:$data[$settings['downloadreminder2_dur']]);
            }

            echo '<div class="alert alert-secondary fade in col-md-12">'.
                '<span class="fa fa-clock-o"></span> Today at 23:59 ET <strong>'.$result['total'].' reminders</strong> will be sent.<br>';
            foreach ($result as $concept_title => $r){
                if($concept_title != "total") {
                    echo 'The data request  "' . $concept_title . ' " has:' .
                        '<ul>' .
                        '<li>' . $result[$concept_title]['numDownloaders'] . ' downloaders total.</li>';
                    for ($i = 0; $i < count($result[$concept_title]['sop_id']); $i++) {
                        echo '<li>Draft ID #' . $result[$concept_title]['sop_id'][$i] . '</li>';
                        echo '<ul>' .
                            '<li>Reminders in ' . $settings['downloadreminder_dur'] . ' days: ' . $result[$concept_title][$settings['downloadreminder_dur']][$i] . '</li>' .
                            '<li>Reminders in ' . $settings['downloadreminder2_dur'] . ' days: ' . $result[$concept_title][$settings['downloadreminder2_dur']][$i] . '</li>' .
                            '</ul>';
                    }
                    echo '</ul>' .
                        '</ul>';
                }
        }

        echo '</div>';
        }
    }

    function testCronDataUploadNotification(){
        $settings = self::getSettings();
        $extra_days = " + 0 days";
        $messageArray = array();
        foreach (self::getRequestDU() as $upload) {
            $message = AllCrons::runCronDataUploadNotification(
                $this->module,
                $upload,
                array('sop_downloaders' => '1,2'),
                self::getPeopleDown(),
                $extra_days,
                $settings,
                false
            );
            array_push($messageArray,$message);
        }

        echo $this->getTestOutputMessage('Data Upload Notification CRON',2, $messageArray[0]['numDownloaders']);

        $RecordSetDU = \REDCap::getData(IEDEA_DATAUPLOAD, 'array', null,null,null,null,false,false,false,"[emails_sent_y(1)] = 1 AND datediff ([responsecomplete_ts], '".date('Y-m-d')."', \"d\", true) = 0");
        $total_notifications_sent_today = count(getProjectInfoArray($RecordSetDU));
        if($total_notifications_sent_today > 0) {
            echo '<div class="alert alert-secondary fade in col-md-12">' .
                '<span class="fa fa-envelope"></span> <strong>' . $total_notifications_sent_today . ' notifications</strong> were sent today.<br>' .
                '</div>';
        }
    }

    function testCronMonthlyDigest(){

        $settings = self::getSettings();

        $message = AllCrons::runCronMonthlyDigest(
            $this->module,
            self::getDigestRequests(),
            self::getDigestRequests(),
            self::getDigestRequests(),
            $settings,
            false
        );

        echo $this->getTestOutputMessage('Monthly Digest CRON',1, $message['code_test']);

        $RecordSetReq = \REDCap::getData(IEDEA_RMANAGER, 'array', null,null,null,null,false,false,false,"[approval_y] = 1");
        $requests = getProjectInfoArrayRepeatingInstruments($RecordSetReq);
        array_sort_by_column($requests, 'due_d',SORT_ASC);

        $numberDaysInCurrentMonth = cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'));
        $expire_date = date('Y-m-d', strtotime(date('Y-m-d') ."-".$numberDaysInCurrentMonth." days"));
        $RecordSetReq = \REDCap::getData(IEDEA_RMANAGER, 'array',null,null,null,null,false,false,false,"[finalize_y] <> '' and [final_d] <>'' and datediff ([final_d], '".$expire_date."', \"d\", true) <= 0");
        $requests_hub = getProjectInfoArrayRepeatingInstruments($RecordSetReq);
        array_sort_by_column($requests_hub, 'final_d',SORT_ASC);

        $RecordSetSOP = \REDCap::getData(IEDEA_SOP, 'array', null);
        $sops = getProjectInfoArrayRepeatingInstruments($RecordSetSOP,array('sop_active' => '1', 'sop_finalize_y' => array(1=>'1')));
        array_sort_by_column($sops, 'sop_due_d',SORT_ASC);

        $message = AllCrons::runCronMonthlyDigest(
            $this->module,
            $requests,
            $requests_hub,
            $sops,
            $settings,
            false
        );

        $environment = "";
        if(ENVIRONMENT == 'DEV' || ENVIRONMENT == 'TEST'){
            $environment = " ".ENVIRONMENT;
        }

        echo '<div class="alert alert-secondary fade in col-md-12">' .
            '<span class="fa fa-list-ul"></span> Monthly Summary for <strong>'.date("F",strtotime("-1 months"))." ".date("Y",strtotime("-1 months")).$environment . '</strong>:<br>' .
            '<ul>
                <li>Active Hub Requests: '.$message['active_requests'].'</li>
                <li>Hub Requests Finalized in Past Month: '.$message['requests_finalized'].'</li>
                <li>Active Data Calls: '.$message['active_data_calls'].'</li>
            </ul>'.
            '</div>';

    }

    function testCronDeleteAws(){
        $settings = self::getSettings();
        $messageArray = array();
        $expired_date = date('Y-m-d');

        foreach (self::getRequestDU() as $upload) {
            $message = AllCrons::runCronDeleteAws(
                $this->module,
                null,
                $upload,
                array('sop_downloaders' => '1,2'),
                self::getPeopleDown(),
                $expired_date,
                $settings,
                false
            );
            array_push($messageArray,$message);
        }
        echo $this->getTestOutputMessage('Delete AWS CRON',1, $messageArray[0]['code_test'][0]);

        $RecordSetDU = \REDCap::getData(IEDEA_DATAUPLOAD, 'array', null,null,null,null,false,false,false,"[deleted_y] = 1 AND datediff ([deletion_ts], '".date('Y-m-d')."', \"d\", true) = 0");
        $total_notifications_deleted_today = count(getProjectInfoArray($RecordSetDU));
        if($total_notifications_deleted_today > 0) {
            echo '<div class="alert alert-secondary fade in col-md-12">' .
                '<span class="fa fa-trash"></span> <strong>' . $total_notifications_deleted_today . ' Data Uploads</strong> were automatically deleted today.<br>' .
                '</div>';
        }
    }

    function runCronUploadPendingDataSetData(){

    }

}

?>