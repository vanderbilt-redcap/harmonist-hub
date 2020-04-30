<?php
namespace Vanderbilt\DataModelBrowserExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

$project_id = $_REQUEST['pid'];
$hub_projectname = $module->getProjectSetting('hub-projectname');
$hub_profile = $module->getProjectSetting('hub-profile');

$path = $module->framework->getModulePath()."csv/PID_data_dictionary.csv";
$module->framework->importDataDictionary($project_id,$path);
$custom_record_label = "[project_constant]: [project_id]";
$module->query("UPDATE redcap_projects SET custom_record_label = ? WHERE project_id = ?",[$custom_record_label,$project_id]);

$projects_array = array(0=>'DATAMODEL',1=>'CODELIST',2=>'HARMONIST',3=>'RMANAGER',4=>'COMMENTSVOTES',5=>'SOP',6=>'SOPCOMMENTS',
                        7=>'REGIONS',8=>'PEOPLE',9=>'GROUPS', 10=>'FAQ',11=>'HOME',12=>'DATAUPLOAD',13=>'DATADOWNLOAD',
                        14=>'JSONCOPY',15=>'METRICS',16=>'DATAAVAILABILITY',17=>'ISSUEREPORTING',18=>'DATATOOLMETRICS',19=>'DATATOOLUPLOADSECURITY',
                        20=>'FAQDATASUBMISSION',21=>'CHANGELOG',22=>'FILELIBRARY',23=>'FILELIBRARYDOWN',24=>'NEWITEMS',25=>'ABOUT',26=>'EXTRAOUTPUTS',27=>'TBLCENTERREVISED',28=>'SETTINGS');

$projects_array_surveys = array(
    2=>array(
        0=>'concept_sheet',
        1=>'participants',
        2=>'admin_update',
        3=>'quarterly_update_survey',
        4=>'outputs'
    ),
    3=>array(
        0=>'request',
        1=>'admin_review',
        2=>'finalization_of_request',
        3=>'final_docs_request_survey',
        4=>'mr_assignment_survey'
    ),
    4=>array(
        0=>'comments_and_votes'
    ),
    5=>array(
        0=>'data_specification',
        1=>'dhwg_review_request',
        2=>'finalization_of_data_request',
        3=>'data_call_closure'
    ),
    6=>array(
        0=>'sop_comments'
    ),
    8=>array(
        0=>'person_information',
        1=>'user_profile'
    ),
    11=>array(
        0=>'deadlines',
        1=>'announcements'
    ),
    17=>array(
        0=>'issue_report_survey'
    ),
    22>array(
        0=>'file_information'
    ),
    24>array(
        0=>'news_item'
    ),
    26>array(
        0=>'output_record'
    ),
    27>array(
        0=>'tblcenter'
    )
);


$projects_array_show = array(0=>'1',1=>'1',2=>'1',3=>'1',4=>'1',5=>'0',6=>'1',
    7=>'1',8=>'1',9=>'1', 10=>'1',11=>'1',12=>'1',13=>'1',
    14=>'0',15=>'0',16=>'0',17=>'1',18=>'0',19=>'0',
    20=>'0',21=>'1',22=>'0',23=>'0',24=>'0',25=>'0',26=>'0',27=>'0',28=>'1');

$projects_array_name = array(0=>'0A: Data Model',1=>'0B: Code Lists',2=>'1: Concept Sheets',3=>'2: Request Manager',4=>'2B: Comments and Votes',5=>'3: Data Specifications',6=>'3B: SOP Comments',
                        7=>'4: Regions',8=>'5: People',9=>'6: Groups', 10=>'7: FAQ',11=>'8: Homepage Content',12=>'9: Data Uploads',13=>'10: Data Download Logging',
                        14=>'11: Data Standards JSON Copy',15=>'12: Metrics',16=>'13: Data Availability Worksheet',17=>'14: Issue Reporting Survey',
                        18=>'15: Data Toolkit Usage Metrics',19=>'16: Toolkit Data Upload Security',20=>'17: FAQ Data Toolkit',
                        21=>'18: Changelog',22=>'19: File Library',23=>'20: File Library Download Logging',24=>'21: News Items',25=>'22: About',26=>'23: Extra Outputs',27=>'24: tblCENTER',28=>'99: Settings');

$custom_record_label_array = array(0=>"[table_name]",1=>"[list_name]",2=>'<span style=\'color:#[dashboard_color]\'><b>[concept_id]</b> [contact_link]</span>',
                        3=>'[contact_name], [request_type] (Due: [due_d])',4=>"[request_id], [response_person]",5=>'[sop_hubuser]',6=>'',
                        7=>'([region_name], [region_code])',8=>'[firstname] [lastname]',9=>'[group_abbr], [group_name]', 10=>'[help_question]',
                        11=>'',12=>'',13=>'[download_id], [downloader_id]', 14=>'[type]',15=>'',16=>'[available_variable], [available_status]',17=>'',
                        18=>'[action_ts], [action_step]',19=>'', 20=>'',21=>'',22=>'',23=>'',24=>'',25=>'',
                        26=>'<span style=\'color:#[dashboard_color]\'><b>([producedby_region:value]) [output_year] [output_type]</b> | [output_title]', 27=>'([name])',28=>'');

$projects_array_repeatable = array(
    0=>array(0=>array('status'=>1,'instrument'=>'variable_metadata','params'=>'[variable_name]')),
    1=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    2=>array(
        0=>array('status'=>1,'instrument'=>'participants','params'=>'[person_role], [person_link]'),
        1=>array('status'=>1,'instrument'=>'admin_update','params'=>'[adminupdate_d]'),
        2=>array('status'=>1,'instrument'=>'quarterly_update_survey','params'=>'[update_d]')
    ),
    3=>array(0=>array('status'=>1,'instrument'=>'dashboard_region_status','params'=>'[responding_region]')),
    4=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    5=>array(0=>array('status'=>1,'instrument'=>'region_participation_status','params'=>'[data_region], [data_response_status]')),
    6=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    7=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    8=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    9=>array(0=>array('status'=>1,'instrument'=>'meeting','params'=>'[meeting_d]')),
    10=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    11=>array(0=>array('status'=>1,'instrument'=>'quick_links_section','params'=>'[links_sectionhead]')),
    12=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    13=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    14=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    15=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    16=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    17=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    18=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    19=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    20=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    21=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    22=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    23=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    24=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    25=>array(0=>array('status'=>1,'instrument'=>'about_members','params'=>'')),
    26=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    27=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    28=>array(0=>array('status'=>0,'instrument'=>'','params'=>''))
);

$projects_array_hooks = array(0=>'1',1=>'1',2=>'1',3=>'1',4=>'1',5=>'1',6=>'1',
    7=>'0',8=>'1',9=>'0', 10=>'0',11=>'1',12=>'0',13=>'0',
    14=>'0',15=>'0',16=>'0',17=>'0',18=>'0',19=>'0',
    20=>'0',21=>'0',22=>'0',23=>'0',24=>'0',25=>'0',26=>'0',27=>'1',28=>'0');

$projects_array_module_seamlessiframe = array(0=>'0',1=>'0',2=>'0',3=>'0',4=>'0',5=>'0',6=>'0',
    7=>'0',8=>'0',9=>'0', 10=>'0',11=>'1',12=>'0',13=>'0',
    14=>'0',15=>'0',16=>'0',17=>'0',18=>'0',19=>'0',
    20=>'0',21=>'0',22=>'0',23=>'0',24=>'0',25=>'0',26=>'0',27=>'0',28=>'0');
//
//$projects_array_module_emailalerts = array(0=>'0',1=>'0',2=>'0',3=>'1',4=>'0',5=>'0',6=>'0',
//    7=>'0',8=>'0',9=>'0', 10=>'0',11=>'0',12=>'0',13=>'0',
//    14=>'0',15=>'0',16=>'0',17=>'0',18=>'0',19=>'0',
//    20=>'0',21=>'0',22=>'0',23=>'0',24=>'0',25=>'0',26=>'0',27=>'0',28=>'0');

$projects_array_module_emailalerts = array(
    3=> array(
        "datapipe_var" => "[contact_email], Contact Email
                            [cc_email1], CC Email 1
                            [cc_email2], CC Email 2",
        "emailFromForm_var" => "",
        "emailSender_var" => "IeDEA Hub",
        "datapipeEmail_var" => "[request_id], Request ID
                                [request_type], Request Type
                                [request_title], Request Title
                                [request_conf], Conference
                                [assoc_concept], Concept MR#
                                [wg_name], IeDEA WG1
                                [request_description], Request Desc
                                [contact_email], Contact Email
                                [contact_name], Contact Name
                                [contact_region], Contact Region
                                [reviewer_id], Reviewer ID
                                [contactperson_id], Contact Person ID
                                [due_d], Due Date
                                [reviewer_id], Admin Reviewer Name
                                [admin_review_notes], Admin Review Notes
                                [approval_y], Admin Approval
                                [admin_internal_notes], Admin Internal Notes
                                [admin_noemail], No email
                                [detected_complete], All votes complete
                                [detected_complete_ts], Detected Complete TS
                                [finalize_y], Finalized Status
                                [final_d], Finalized Date
                                [finalizer_id], Finalizing Person
                                [custom_note], Custom Note to Author
                                [author_doc], Author Final Doc
                                [datarequest_type], Requested Data Types
                                [mr_assigned], Assigned MR
                                [finalconcept_doc], Final DOC
                                [finalconcept_pdf], Final PDF
                                [vote_ap], AP vote
                                [vote_ca], CA vote
                                [vote_cn], CN vote
                                [vote_ea], EA vote
                                [vote_na], NA vote
                                [vote_sa], SA vote
                                [vote_wa], WA vote",
        "surveyLink_var" => "[__SURVEYLINK_request],Request Survey
                            [__SURVEYLINK_admin_review],Admin Review Survey
                            [__SURVEYLINK_finalization_of_request], Finalization of Request
                            [__SURVEYLINK_final_docs_request_survey],Final Docs Request
                            [__SURVEYLINK_mr_assignment_survey],MR Assignment Survey",
        "formLink_var" => "",
        "emailFailed_var" => "harmonist@vanderbilt.edu",
        "form-name" => array
                        (
                            0 => "request",
                            1 => "admin_review",
                            2 => "request",
                            3 => "admin_review",
                            4 => "admin_review",
                            5 => "admin_review",
                            6 => "admin_review",
                            7 => "admin_review",
                            8 => "admin_review",
                            9 => "finalization_of_request",
                            10 => "admin_review",
                            11 => "finalization_of_request",
                            12 => "final_docs_request_survey",
                            13 => "mr_assignment_survey",
                            14 => "mr_assignment_survey",
                            15 => "finalization_of_request"
                        ),
        "form-name-event" => array
            (
                4 => "",
                5 => "",
                3 => "",
                2 => "",
                0 => "",
                1 => "",
                6 => "",
                7 => "",
                8 => "",
                9 => "",
                10 => "",
                11 => "",
                12 => "",
                13 => "",
                14 => "",
                15 => ""
            ),
        "email-from" => array
            (
                4 => "noreply@fakemail.com, ".$hub_projectname." Hub",
                0 => "noreply@fakemail.com, ".$hub_projectname." Hub",
                1 => "noreply@fakemail.com, ".$hub_projectname." Hub",
                2 => "noreply@fakemail.com, ".$hub_projectname." Hub",
                3 => "noreply@fakemail.com, ".$hub_projectname." Hub",
                5 => "noreply@fakemail.com, ".$hub_projectname." Hub",
                6 => "noreply@fakemail.com, ".$hub_projectname." Hub",
                7 => "noreply@fakemail.com, ".$hub_projectname." Hub",
                8 => "noreply@fakemail.com, ".$hub_projectname." Hub",
                9 => "noreply@fakemail.com, ".$hub_projectname." Hub",
                10 => "noreply@fakemail.com, ".$hub_projectname." Hub",
                11 => "noreply@fakemail.com, ".$hub_projectname." Hub",
                12 => "noreply@fakemail.com, ".$hub_projectname." Hub",
                13 => "noreply@fakemail.com, ".$hub_projectname." Hub",
                14 => "noreply@fakemail.com, ".$hub_projectname." Hub",
                15 => "noreply@fakemail.com, ".$hub_projectname." Hub"
            ),
        "email-to" => array
            (
                0 => "[contact_email]",
                1 => "[contact_email]",
                2 => "noreply@fakemail.com",
                3 => "noreply@fakemail.com",
                4 => "noreply@fakemail.com",
                5 => "noreply@fakemail.com",
                6 => "[contact_email]",
                7 => "noreply@fakemail.com",
                8 => "noreply@fakemail.com",
                9 => "noreply@fakemail.com",
                10 => "noreply@fakemail.com",
                11 => "[contact_email]",
                12 => "noreply@fakemail.com",
                13 => "[contact_email]",
                14 => "noreply@fakemail.com",
                15 => "noreply@fakemail.com"
            ),
    "email-cc" => array
        (
            0 => "",
            1 => "",
            2 => "",
            3 => "",
            4 => "",
            5 => "",
            6 => "",
            7 => "",
            8 => "",
            9 => "",
            10 => "",
            11 => "",
            12 => "",
            13 => "",
            14 => "",
            15 => ""
        ),
    "email-bcc" => array
        (
            4 => "",
            5 => "",
            3 => "",
            2 => "",
            0 => "",
            1 => "",
            6 => "",
            7 => "",
            8 => "",
            9 => "",
            10 => "",
            11 => "",
            12 => "",
            13 => "",
            14 => "",
            15 => ""
        ),
    "email-subject" => array
        (
            0 => $hub_projectname." Request #[request_id] received: [request_type], [contact_name]",
            1 => $hub_projectname." Request #[request_id] posted: [request_type], [contact_name]",
            2 => $hub_projectname." Request #[request_id] needs Admin review: [request_type], [contact_name]",
            3 => $hub_projectname." Request #[request_id] posted: [request_type], [contact_name]",
            4 => $hub_projectname." Request #[request_id] deactivated: [request_type], [contact_name]",
            5 => $hub_projectname." Request #[request_id] rejected: [request_type], [contact_name]",
            6 => "Admin question about ".$hub_projectname." Request #[request_id] ([request_type])",
            7 => $hub_projectname." Request #[request_id] voting complete: [request_type], [contact_name]",
            8 => $hub_projectname." Request #[request_id] voting incomplete: [request_type], [contact_name]",
            9 => $hub_projectname." Request #[request_id] approved by EC: [request_type], [contact_name]",
            10 => $hub_projectname." Request #[request_id] voting incomplete: [request_type], [contact_name]",
            11 => $hub_projectname." Request #[request_id] post-approval final steps: [request_type], [contact_name]",
            12 => "New [request_type] needs MR: Request #[request_id], [contact_name]",
            13 => "New ".$hub_projectname." Concept approved: [mr_assigned], [contact_name]",
            14 => "New ".$hub_projectname." Concept: [mr_assigned], [contact_name]",
            15 => $hub_projectname." Request #[request_id] approved by EC: [request_type], [contact_name]"
        ),
    "email-text" => array
        (
            0 => '<h2>New Submission</h2>
<p>Thank you for submitting a review request to the IeDEA Executive Committee (EC). This email serves as your confirmation that the request has been submitted to the system. An IeDEA Hub Admin will review your request and contact you with any followup questions. You will be notified once the request is distributed to the IeDEA EC.</p>
<p><strong>Request Tracking ID:</strong>&nbsp; [request_id]</p>
<p><strong>Review Type:</strong>&nbsp; [request_type]</p>
<p><strong>Contact Person:</strong>&nbsp; [contact_name], [contact_email]</p>
<p><strong>Request Title:</strong>&nbsp; [request_title]</p>
<p><strong>Description:</strong>&nbsp; [request_description]</p>
<p><span style="color: #000000;"><strong>Link to review/edit submission #[request_id]:</strong> </span>[__SURVEYLINK_request]</p>
<p>&nbsp;</p>
<p><span style="color: #999999; font-size: 11px;">This email has been automatically generated by the IeDEA Hub system (<a href="http://iedeahub.org">iedeahub.org</a>). If someone incorrectly submitted this request on your behalf or if you believe you received this email in error, please contact <a href="mailto:harmonist@vumc.org">harmonist@vumc.org</a>.</span></p>
            [1] => <h2>Submission Posted</h2>
<p>Your request has been reviewed by <strong>[reviewer_id]</strong> and will now be displayed on the IeDEA Hub. This will begin the IeDEA EC review process.</p>
<p><strong><span style="color: #e74c3c;">Due Date Assigned</span>:</strong>&nbsp;[due_d]</p>
<p><strong>Request Tracking ID:</strong>&nbsp; [request_id]</p>
<p><strong>Review Type:</strong>&nbsp; [request_type]</p>
<p><strong>Contact Person:</strong>&nbsp; [contact_name], [contact_email]</p>
<p><strong>Request Title:</strong>&nbsp; [request_title]</p>
<p><strong>Description:</strong>&nbsp; [request_description]</p>
<p><strong>Public Admin Notes:</strong>&nbsp; [admin_review_notes]</p>
<p><span style="color: #000000;"><strong>Link to review/edit submission #[request_id]:</strong> </span>[__SURVEYLINK_request]</p>
<p>&nbsp;</p>
<p><span style="color: #999999; font-size: 11px;">This email has been automatically generated by the IeDEA Hub system (<a href="http://iedeahub.org">iedeahub.org</a>). If someone incorrectly submitted this request on your behalf or if you believe you received this email in error, please contact <a href="mailto:harmonist@vumc.org">harmonist@vumc.org</a>.</span></p>',
            2 => '<h2>New Submission</h2>
<p>A new review request has been submitted and requires admin review before posting to the IeDEA Hub.</p>
<p><strong>Request Tracking ID:</strong>&nbsp; [request_id]</p>
<p><strong>Review Type:</strong>&nbsp; [request_type]</p>
<p><strong>Contact Person:</strong>&nbsp; [contact_name], [contact_email]</p>
<p><strong>Request Title:</strong>&nbsp; [request_title]</p>
<p><strong>Description:</strong>&nbsp; [request_description]</p>
<h2>Actions</h2>
<p><strong>1. Link to <span style="color: #e74c3c;">review/edit submission #[request_id]</span>:</strong>&nbsp;<br />[__SURVEYLINK_request]</p>
<p><strong>2. Link to Hub Admin <span style="color: #16a085;">approval page</span>:</strong><br />[__SURVEYLINK_admin_review]</p>',
            3 => '<h2>Submission Posted</h2>
<p>The following request has been reviewed by <strong>[reviewer_id]</strong> and will now be displayed on the IeDEA Hub.</p>
<p><strong><span style="color: #e74c3c;">Due Date Assigned</span>:</strong>&nbsp;[due_d]</p>
<p><strong>Request Tracking ID:</strong>&nbsp; [request_id]</p>
<p><strong>Review Type:</strong>&nbsp; [request_type]</p>
<p><strong>Contact Person:</strong>&nbsp; [contact_name], [contact_email]</p>
<p><strong>Request Title:</strong>&nbsp; [request_title]</p>
<p><strong>Description:</strong>&nbsp; [request_description]</p>
<p><strong>Public Admin Notes:</strong>&nbsp; [admin_review_notes]</p>
<p><strong>Internal Admin Notes:</strong>&nbsp; [admin_internal_notes]</p>
<h2>Reference Links</h2>
<p><strong>1. Link to <span style="color: #e74c3c;">review/edit submission #[request_id]</span>:</strong>&nbsp;<br />[__SURVEYLINK_request]</p>
<p><strong>2. Link to Hub Admin <span style="color: #16a085;">approval page</span>:</strong><br />[__SURVEYLINK_admin_review]</p>',
            4 => '<h2>Submission Paused/Deactivated</h2>
<p>The following request has been paused or deactivated by <strong>[reviewer_id]</strong>. It will not appear on the IeDEA Hub.</p>
<p><strong>Request Tracking ID:</strong>&nbsp; [request_id]</p>
<p><strong>Review Type:</strong>&nbsp; [request_type]</p>
<p><strong>Contact Person:</strong>&nbsp; [contact_name], [contact_email]</p>
<p><strong>Request Title:</strong>&nbsp; [request_title]</p>
<p><strong>Description:</strong>&nbsp; [request_description]</p>
<p><strong>Public Admin Notes:</strong>&nbsp; [admin_review_notes]</p>
<p><strong>Internal Admin Notes:</strong>&nbsp; [admin_internal_notes]</p>
<h2>Reference Links</h2>
<p><strong>1. Link to <span style="color: #e74c3c;">review/edit submission #[request_id]</span>:</strong>&nbsp;<br />[__SURVEYLINK_request]</p>
<p><strong>2. Link to Hub Admin <span style="color: #16a085;">approval page</span>:</strong><br />[__SURVEYLINK_admin_review]</p>',
            5 => '<h2>Submission Rejected</h2>
<p>The following request has been rejected by <strong>[reviewer_id]</strong>. It will not appear on the IeDEA Hub. <strong><span style="color: #e74c3c;">The Contact Person has not been notified.</span></strong></p>
<p><strong>Request Tracking ID:</strong>&nbsp; [request_id]</p>
<p><strong>Review Type:</strong>&nbsp; [request_type]</p>
<p><strong>Contact Person:</strong>&nbsp; [contact_name], [contact_email]</p>
<p><strong>Request Title:</strong>&nbsp; [request_title]</p>
<p><strong>Description:</strong>&nbsp; [request_description]</p>
<p><strong>Public Admin Notes:</strong>&nbsp; [admin_review_notes]</p>
<p><strong>Internal Admin Notes:</strong>&nbsp; [admin_internal_notes]</p>
<h2>Reference Links</h2>
<p><strong>1. Link to <span style="color: #e74c3c;">review/edit submission #[request_id]</span>:</strong>&nbsp;<br />[__SURVEYLINK_request]</p>
<p><strong>2. Link to Hub Admin <span style="color: #16a085;">approval page</span>:</strong><br />[__SURVEYLINK_admin_review]</p>',
            6 => '<h2>Question about IeDEA Request</h2>
<p>Your request has been approved by <strong>[reviewer_id]</strong> and will now be displayed on the IeDEA Hub.</p>
<p><strong><span style="color: #e74c3c;">Due Date Assigned</span>:</strong>&nbsp;[due_d]</p>
<p><strong>Request Tracking ID:</strong>&nbsp; [request_id]</p>
<p><strong>Review Type:</strong>&nbsp; [request_type]</p>
<p><strong>Contact Person:</strong>&nbsp; [contact_name], [contact_email]</p>
<p><strong>Request Title:</strong>&nbsp; [request_title]</p>
<p><strong>Description:</strong>&nbsp; [request_description]</p>
<p><strong>Public Admin Notes:</strong>&nbsp; [admin_internal_notes]</p>
<p><span style="color: #000000;"><strong>Link to review/edit submission #[request_id]:</strong> </span>[__SURVEYLINK_request]</p>
<p>&nbsp;</p>
<p><span style="color: #999999; font-size: 11px;">This email has been automatically generated by the IeDEA Hub system (<a href="http://iedeahub.org">iedeahub.org</a>). If someone incorrectly submitted this request on your behalf or if you believe you received this email in error, please contact <a href="mailto:stephany.duda@vanderbilt.edu">harmonist@vanderbilt.edu</a>.</span></p>',
            7 => '<h2>Voting Complete</h2>
<p>The following request has received all regional votes. Please take action below to finalize the request or respond to votes and comments.</p>
<p><strong><span style="color: #e74c3c;">Due Date Assigned</span>:</strong>&nbsp;[due_d]</p>
<p><strong>Request Tracking ID:</strong>&nbsp; [request_id]</p>
<p><strong>Review Type:</strong>&nbsp; [request_type]</p>
<p><strong>Contact Person:</strong>&nbsp; [contact_name], [contact_email]</p>
<p><strong>Request Title:</strong>&nbsp; [request_title]</p>
<h2>Summary of Votes</h2>
<p><strong>AP:</strong>&nbsp; [vote_ap]</p>
<p><strong>CA:</strong>&nbsp; [vote_ca]</p>
<p><strong>CN:</strong>&nbsp; [vote_cn]</p>
<p><strong>EA:</strong>&nbsp; [vote_ea]</p>
<p><strong>NA:</strong>&nbsp; [vote_na]</p>
<p><strong>SA:</strong>&nbsp; [vote_sa]</p>
<p><strong>WA:</strong>&nbsp; [vote_wa]</p>
<h2>Actions</h2>
<p><strong>1. Link to <span style="color: #16a085;">Finalize Request page</span>:</strong><br />[__SURVEYLINK_finalization_of_request]</p>',
            8 => '<h2>Voting Incomplete</h2>
<p>The following request is due today but has not received all votes.&nbsp;</p>
<p><strong><span style="color: #e74c3c;">Due Date Assigned</span>:</strong>&nbsp;[due_d]</p>
<p><strong>Request Tracking ID:</strong>&nbsp; [request_id]</p>
<p><strong>Review Type:</strong>&nbsp; [request_type]</p>
<p><strong>Contact Person:</strong>&nbsp; [contact_name], [contact_email]</p>
<p><strong>Request Title:</strong>&nbsp; [request_title]</p>
<h2>Summary of Votes</h2>
<p><strong>AP:</strong>&nbsp; [vote_ap]</p>
<p><strong>CA:</strong>&nbsp; [vote_ca]</p>
<p><strong>CN:</strong>&nbsp; [vote_cn]</p>
<p><strong>EA:</strong>&nbsp; [vote_ea]</p>
<p><strong>NA:</strong>&nbsp; [vote_na]</p>
<p><strong>SA:</strong>&nbsp; [vote_sa]</p>
<p><strong>WA:</strong>&nbsp; [vote_wa]</p>
<h2>Actions</h2>
<p><strong>1. Link to <span style="color: #31708f;">Visit Request page</span>:</strong><br /><a href="https://redcap.vanderbilt.edu/plugins/iedea/index.php?option=hub&amp;record=[request_id]">https://redcap.vanderbilt.edu/plugins/iedea/index.php?option=hub&amp;record=[request_id]</a></p>
<p><strong>2. Link to <span style="color: #16a085;">Finalize Request page</span>:</strong><br />[__SURVEYLINK_finalization_of_request]</p>',
            9 => '<h2>Request Approved by IeDEA EC</h2>
<p>Dear [contact_name],</p>
<p>We are pleased to confirm approval of your IeDEA multiregional [request_type], <strong>[request_title]</strong>. The IeDEA EC approval date is [final_d].</p>
<p><strong>Next Steps</strong></p>
<ol>
<li>Please check the IeDEA Hub for <a href="https://redcap.vanderbilt.edu/plugins/iedea/index.php?option=hub&amp;record=[request_id]">any comments or queries from the regions</a> and incorporate further revisions into your document.</li>
<li>Remove all comments and tracked changes from the document.</li>
<li>Upload a final version of your [request_type] for logging at the link below.&nbsp;</li>
</ol>
<p>[__SURVEYLINK_final_docs_request_survey]</p>
<p>Please contact <a href="mailto:annette.sohn@treatasia.org">Annette Sohn</a> or <a href="mailto:afreeman@jhu.edu">Aimee Freeman</a> if you have any questions.</p>
<p>Thank you for participating in IeDEA research.</p>
<hr />
<p><em>Additional review notes (optional):</em></p>
<p>[custom_note]</p>',
            10 => '<h2>Voting Incomplete</h2>
<p>The following request is due today but has not received all votes.&nbsp;</p>
<p><strong><span style="color: #e74c3c;">Due Date Assigned</span>:</strong>&nbsp;[due_d]</p>
<p><strong>Request Tracking ID:</strong>&nbsp; [request_id]</p>
<p><strong>Review Type:</strong>&nbsp; [request_type]</p>
<p><strong>Contact Person:</strong>&nbsp; [contact_name], [contact_email]</p>
<p><strong>Request Title:</strong>&nbsp; [request_title]</p>
<h2>Summary of Votes</h2>
<p><strong>AP:</strong>&nbsp; [vote_ap]</p>
<p><strong>CA:</strong>&nbsp; [vote_ca]</p>
<p><strong>CN:</strong>&nbsp; [vote_cn]</p>
<p><strong>EA:</strong>&nbsp; [vote_ea]</p>
<p><strong>NA:</strong>&nbsp; [vote_na]</p>
<p><strong>SA:</strong>&nbsp; [vote_sa]</p>
<p><strong>WA:</strong>&nbsp; [vote_wa]</p>
<h2>Actions</h2>
<p><strong>1. Link to <span style="color: #31708f;">Visit Request page</span>:</strong><br /><a href="https://redcap.vanderbilt.edu/plugins/iedea/index.php?option=hub&amp;record=[request_id]">https://redcap.vanderbilt.edu/plugins/iedea/index.php?option=hub&amp;record=[request_id]</a></p>
<p><strong>2. Link to <span style="color: #16a085;">Finalize Request page</span>:</strong><br />[__SURVEYLINK_finalization_of_request]</p>',
            11 => '<h2>Final Documents Requested</h2>
<p>Dear [contact_name],</p>
<p>We are pleased to confirm approval of your IeDEA multiregional concept,&nbsp;<strong>[request_title]</strong>. The IeDEA EC approval date is <strong>[final_d]</strong>.</p>
<p><strong>Next Steps</strong></p>
<ol>
<li style="padding-bottom: 5px;">Please check the IeDEA Hub for <a href="https://redcap.vanderbilt.edu/plugins/iedea/index.php?option=hub&amp;record=[request_id]">any changes requested by the EC</a> and incorporate further revisions into your concept sheet.</li>
<li style="padding-bottom: 5px;">Remove all comments and tracked changes from the document.</li>
<li style="padding-bottom: 5px;"><strong><span style="color: #e74c3c;">Upload a final version of your concept</span></strong> to the IeDEA Hub using the link below. <strong>This will trigger all subsequent steps for your project.</strong></li>
</ol>
<p>&nbsp;</p>
<p>[__SURVEYLINK_final_docs_request_survey]</p>
<p>&nbsp;</p>
<p>After you have uploaded the final version of your concept, the IeDEA concept sheet management team will <strong>assign a concept tracking number</strong> and your concept will be logged on the Hub. You <strong>must have a tracking number (MR number) before requesting IeDEA data.</strong>&nbsp;Once your tracking number has been assigned, you will receive a notification email with the tracking number and next steps for your project. Please contact&nbsp;<a href="mailto:annette.sohn@treatasia.org">Annette Sohn</a>&nbsp;or&nbsp;<a href="mailto:afreeman@jhu.edu">Aimee Freeman</a>&nbsp;if you have any questions.</p>
<p>This email is scheduled to repeat as a reminder&nbsp;<strong>every 7 days</strong> until documents have been uploaded.&nbsp;</p>
<p>Thank you for participating in IeDEA research.</p>
<p>&nbsp;</p>
<hr />
<p><em>Additional review notes (if needed):</em></p>
<p>[custom_note]</p>
<hr />
<p>&nbsp;</p>
<p><span style="color: #999999; font-size: 11px;">This email has been automatically generated by the IeDEA Hub system (<a href="http://iedeahub.org">iedeahub.org</a>). If someone incorrectly submitted this request on your behalf or if you believe you received this email in error, please contact <a href="mailto:harmonist@vumc.org">harmonist@vumc.org</a>.</span></p>',
            12 => '<h2>IeDEA Concept Needs MR</h2>
<p>Dear Morna and Kathleen,</p>
<p>The following IeDEA multiregional concept has been approved by the EC on [final_d].</p>
<p><strong>[request_title]</strong> (Request ID: [request_id])</p>
<p>[contact_name], <a href="mailto:[contact_email]">[contact_email]</a></p>
<p>The author\'s final documents are attached. Please follow the link below to assign a multiregional tracking number (MR#) and update the documents.</p>
<p>This email <strong>will repeat every 7 days</strong> as a reminder.</p>
<p>&nbsp;</p>
<h2>Actions</h2>
<p><strong>1. Link to <span style="color: #e74c3c;">view author submission (optional)</span>:</strong>&nbsp;<br />[__SURVEYLINK_final_docs_request_survey]</p>
<p><strong>2. Link to <span style="color: #16a085;">assign MR tracking number</span>:</strong><br />[__SURVEYLINK_mr_assignment_survey]</p>
<p>&nbsp;</p>
<p><span style="color: #999999; font-size: 11px;">This email has been automatically generated by the IeDEA Hub system (<a href="http://iedeahub.org">iedeahub.org</a>). If someone incorrectly submitted this request on your behalf or if you believe you received this email in error, please contact <a href="mailto:stephany.duda@vanderbilt.edu">stephany.duda@vanderbilt.edu</a>.</span></p>',
            13 => '<h2>IeDEA Tracking Number Assigned</h2>
<p>Dear [contact_name],</p>
<p>Your IeDEA multiregional concept, <strong>[request_title]</strong>, has been assigned the following multiregional tracking number:</p>
<p><strong>[mr_assigned]</strong></p>
<p>A listing for your new concept will be available soon on the Concepts page of the IeDEA Hub (<a href="http://iedeahub.org">iedeahub.org</a>).</p>
<p><strong>Next Steps</strong></p>
<ol>
<li style="padding-bottom: 5px;"><strong>Data:</strong> The IeDEA regional data leads are cc\'d if you are requesting either IeDEA patient-level or site assessment data. Please follow up with them to develop an official Data Request or receive access to existing datasets, as stated in your EC-approved concept sheet.</li>
<li style="padding-bottom: 5px;"><strong>Collaborating Authors:</strong> We recommend including regional representatives on your writing team at an early stage of the project (don\'t wait until the end.) If you need to identify regional collaborators, send your request to Aimee Freeman (<a href="afreeman@jhu.edu">afreeman@jhu.edu</a>), who can forward it to the regional coordinators.</li>
<li style="padding-bottom: 5px;"><strong>Updates:</strong> You will be automatically subscribed to an email survey that will request a brief project update (2-3 sentences) related to this concept every 90 days. These updates will be shared with the EC and logged in the overall IeDEA project tracker.</li>
<li style="padding-bottom: 5px;"><strong>Abstracts and Publications:</strong> All resulting abstracts and publications will need review and approval from your collaborating working group (if applicable) and the IeDEA EC. EC turnaround times are approximately 1+ weeks for abstracts and 2+ weeks for manuscripts. Actual dates are set by the IeDEA admin team. Please plan ahead for any conference and journal deadlines.</li>
</ol>
<p>Thank you for participating in IeDEA research.</p>
<p>&nbsp;</p>
<p><span style="color: #999999; font-size: 11px;">This email has been automatically generated by the IeDEA Hub system (<a href="http://iedeahub.org">iedeahub.org</a>). If someone incorrectly submitted this request on your behalf or if you believe you received this email in error, please contact <a href="mailto:stephany.duda@vanderbilt.edu">stephany.duda@vanderbilt.edu</a>.</span></p>',
            14 => '<h2>New IeDEA Concept: [mr_assigned]</h2>
<p>The following new IeDEA multiregional concept has been approved by the IeDEA Executive Committee:</p>
<p><strong>[mr_assigned]:&nbsp;</strong><em>[request_title]</em></p>
<p>&nbsp;</p>
<p>The main project contact is <strong>[contact_name]</strong> (<a href="mailto:[contact_email]">[contact_email]</a>).</p>
<p>The finalized concept sheet PDF is attached (if available). Project updates will be tracked on the IeDEA Hub (<a href="http://iedeahub.org">iedeahub.org</a>). Please archive the document or distribute for review in your regions.</p>
<p>&nbsp;</p>
<p><span style="color: #999999; font-size: 11px;">This email has been automatically generated by the IeDEA Hub system (<a href="http://iedeahub.org">iedeahub.org</a>). If you believe you received this email in error, please contact <a href="mailto:stephany.duda@vanderbilt.edu">stephany.duda@vanderbilt.edu</a>.</span></p>',
            15 => '<h2>IeDEA Request Approved</h2>
<p>Dear [contact_name],</p>
<p>We are pleased to confirm approval of your IeDEA multiregional concept,&nbsp;<strong>[request_title]</strong>. The IeDEA EC approval date is <strong>[final_d]</strong>.</p>
<p><strong>Next Steps</strong></p>
<ol>
<li style="padding-bottom: 5px;">Please check the IeDEA Hub for <a href="https://redcap.vanderbilt.edu/plugins/iedea/index.php?option=hub&amp;record=[request_id]">any feedback or changes requested by the EC</a> and incorporate them into the final version or respond directly to the region/investigator if this is not feasible.</li>
<li style="padding-bottom: 5px;">Contact&nbsp;<a href="mailto:annette.sohn@treatasia.org">Annette Sohn</a>&nbsp; (cc\'d) or&nbsp;<a href="mailto:afreeman@jhu.edu">Aimee Freeman</a>&nbsp;with any general questions.</li>
<li style="padding-bottom: 5px;">Follow these final documents instructions for your submission type:</li>
<ol>
<li>For <strong>Abstracts</strong>: Upload your <u>submitted</u> abstract at the link below. We will contact you at a later date to find out if the abstract was accepted.</li>
<li>&nbsp;</li>
<li>&nbsp;</li>
<li>&nbsp;</li>
</ol>
</ol>
<p>[__SURVEYLINK_final_docs_request_survey]</p>
<p>&nbsp;</p>
<p><em>If you do not have the final version available, you will receive a reminder email to upload your documents at a later date.</em></p>
<p>Thank you for your leadership of IeDEA&rsquo;s multiregional research.</p>
<p>&nbsp;</p>
<hr />
<p><em>Additional review notes (if needed):</em></p>
<p>[custom_note]</p>
<hr />
<p>&nbsp;</p>
<p><span style="color: #999999; font-size: 11px;">This email has been automatically generated by the IeDEA Hub system (<a href="http://iedeahub.org">iedeahub.org</a>). If someone incorrectly submitted this request on your behalf or if you believe you received this email in error, please contact <a href="mailto:harmonist@vumc.org">harmonist@vumc.org</a>.</span></p>'
        ),
    "email-attachment-variable" => array
        (
            0 => "",
            1 => "",
            2 => "",
            3 => "",
            4 => "",
            5 => "",
            6 => "",
            7 => "",
            8 => "",
            9 => "",
            10 => "",
            11 => "",
            12 => "[author_doc]",
            13 => "",
            14 => "[finalconcept_pdf]",
            15 => ""
        ),
    "email-repetitive" => array
        (
            0 => 0,
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0,
            6 => 0,
            7 => 0,
            8 => 0,
            9 => 0,
            10 => 0,
            11 => 0,
            12 => 0,
            13 => 0,
            14 => 0,
            15 => 0
        ),
    "email-condition" => array
        (
            0 => "",
            1 => "[approval_y]=1 and [admin_noemail(1)] <> '1'",
            2 => "",
            3 => "[approval_y]=1",
            4 => "[approval_y]=9",
            5 => "[approval_y]=0",
            6 => "[approval_y]=8",
            7 => "[approval_y] = '1'",
            8 => "([due_d] <> \"\") and ([finalize_y] = \"\") and ([approval_y] = 1)",
            9 => "[finalize_y]=1 and [request_type]<>1 and [request_type]<>5 and [finalize_noemail]<>'1'",
            10 => "([due_d] <> \"\") and ([finalize_y] = \"\") and ([approval_y] = 1)",
            11 => "[finalize_y]=1 and ([request_type]=1 or [request_type]=5) and [finalize_noemail]<>'1'",
            12 => "[author_doc]<>\"\" and [finaldocs_noemail] <> '1'",
            13 => "[mr_assigned] <> \"\" and [mr_noemail(1)] <> '1'",
            14 => "[mr_assigned] <> \"\" and [mr_noemail(2)] <> '1'",
            15 => "[finalize_y]=1 and ([request_type]<>1 and [request_type]<>5) and [finalize_noemail]<>'1'"
        ),
    "email-incomplete" => array
        (
            0 => 0,
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0,
            6 => 0,
            7 => 0,
            8 => 0,
            9 => 0,
            10 => 0,
            11 => 0,
            12 => 0,
            13 => 0,
            14 => 0,
            15 => 0
        ),
    "cron-send-email-on" => array
        (
            0 => "now",
            7 => "calc",
            8 => "calc",
            9 => "now",
            1 => "now",
            2 => "now",
            3 => "now",
            4 => "now",
            5 => "now",
            10 => "now",
            11 => "calc",
            12 => "calc",
            13 => "now",
            14 => "now",
            15 => "now"
        ),
    "cron-send-email-on-field" => array
        (
            0 => "",
            7 => "sum(if([vote_ap] <> \"\", 1, 0), if([vote_ca] <> \"\", 1, 0), if([vote_cn] <> \"\", 1, 0), if([vote_ea] <> \"\", 1, 0), if([vote_na] <> \"\", 1, 0), if([vote_sa] <> \"\", 1, 0), if([vote_wa] <> \"\", 1, 0)) = '7' and [finalize_y] = ''",
            8 => "(sum(if([vote_ap] <> \"\", 1, 0), if([vote_ca] <> \"\", 1, 0), if([vote_cn] <> \"\", 1, 0), if([vote_ea] <> \"\", 1, 0), if([vote_na] <> \"\", 1, 0), if([vote_sa] <> \"\", 1, 0), if([vote_wa] <> \"\", 1, 0)) < '7') and (datediff( [due_d], 'today', 'd', 'ymd', true) = 0) and ([due_d] <> \"\") and ([finalize_y] = \"\")",
            9 => "",
            1 => "",
            2 => "",
            3 => "",
            4 => "",
            5 => "",
            10 => "",
            11 => "[finalize_y]=1 and ([request_type]=1 or [request_type]=5) and [finalize_noemail]<>'1'",
            12 => "[author_doc]<>\"\" and [finaldocs_noemail] <> '1'",
            13 => "",
            14 => "",
            15 => ""
        ),
    "cron-repeat-for" => array
        (
            0 => 0,
            7 => 0,
            8 => 0,
            9 => 0,
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0,
            10 => 0,
            11 => 7,
            12 => 7,
            13 => 0,
            14 => 0,
            15 => 0
        ),
    "cron-queue-expiration-date" => array
        (
            0 => "never",
            7 => "cond",
            8 => "cond",
            9 => "never",
            1 => "never",
            2 => "never",
            3 => "never",
            4 => "never",
            5 => "never",
            10 => "never",
            11 => "cond",
            12 => "cond",
            13 => "never",
            14 => "never",
            15 => "never"
        ),
    "cron-queue-expiration-date-field" => array
        (
            0 => "[finalize_y] <> \"\"",
            7 => "[finalize_y] <> \"\"",
            8 => "([finalize_y] <> \"\") or ([approval_y] = 9) or ([approval_y] = 0)",
            9 => "",
            1 => "",
            2 => "",
            3 => "",
            4 => "",
            5 => "",
            10 => "",
            11 => "[author_doc] <> '' or [mr_assigned] <> \"\"",
            12 => "[mr_assigned] <> \"\"",
            13 => "",
            14 => "",
            15 => ""
        ),
    "alert-id" => array
        (
            0 => 0,
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            5 => 5,
            6 => 6,
            7 => 7,
            8 => 8,
            9 => 9,
            10 => 10,
            11 => 11,
            12 => 12,
            13 => 13,
            14 => 14,
            15 => 15,
            16 => 16,
            17 => 17,
            18 => 18,
            19 => 19
        ),
    "alert-name" => array(
            0 => "To Author: confirmation of initial submission",
            1 => "To Author: Admin posting confirmed",
            2 => "To Admin: heads-up of new request",
            3 => "To Admin: request posted to Hub",
            4 => "To Admin: request deactivated (not posted to Hub)",
            5 => "To Admin: request rejected (not posted to Hub)",
            7 => "To Admins: notify voting complete",
            8 => "To Admins: alert voting incomplete by due_d",
            9 => "To Steph: non-concept approved by EC",
            10 => "To Steph: alert voting incomplete by due_d TEST2",
            11 => "To Author+Annette: concept or fast-track approved by EC",
            12 => "To MR Team: Assign MR#",
            13 => "To Author and Admins: MR Assigned",
            14 => "To PMs: notification of new concept",
            15 => "To Author+Annette: non-concept approved by EC",
        )
    ));

$projects_array_surveys_hash = array(
    2=>array('constant'=>'CONCEPTLINK','instrument' => 'concept_sheet'),
    3=>array('constant'=>'REQUESTLINK','instrument' => 'request'),
    4=>array('constant'=>'SURVEYLINK','instrument' => 'comments_and_votes'),
    6=>array('constant'=>'SURVEYLINKSOP','instrument' => 'sop_comments'),
    8=>array('constant'=>'SURVEYPERSONINFO','instrument' => 'person_information'),
    17=>array('constant'=>'REPORTBUGSURVEY','instrument' => 'issue_report_survey'),
    22=>array('constant'=>'SURVEYFILELIBRARY','instrument' => 'file_information'),
    24=>array('constant'=>'SURVEYNEWS','instrument' => 'news_item'),
    27=>array('constant'=>'SURVEYTBLCENTERREVISED','instrument' => 'tblcenter')
);


$record = 1;
foreach ($projects_array as $index=>$name){
    $project_title = $hub_projectname." Hub ".$projects_array_name[$index];
    $project_id_new = $module->createProjectAndImportDataDictionary($name,$project_title);
    $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'record_id', $record);
    $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'project_id', $project_id_new);
    $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'project_constant', $name);
    $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'project_show_y', $projects_array_show[$index]);
    $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'project_info_complete', 2);

    if($custom_record_label_array[$index] != ''){
        $module->query("UPDATE redcap_projects SET custom_record_label = ? WHERE project_id = ?",[$custom_record_label_array[$index],$project_id_new]);
    }

    if($name == 'SETTINGS'){
        #Create first record
        $qtype = $module->query("SELECT b.event_id FROM  redcap_events_arms a LEFT JOIN redcap_events_metadata b ON(a.arm_id = b.arm_id) where a.project_id =?",[$project_id_new]);
        $rowtype = $qtype->fetch_assoc();
        $module->addProjectToList($project_id_new, $rowtype['event_id'], 1, 'record_id', 1);

        if($hub_profile == 'solo'){
            $module->addProjectToList($project_id_new, $rowtype['event_id'], 1, 'deactivate_datahub', 1);
            $module->addProjectToList($project_id_new, $rowtype['event_id'], 1, 'deactivate_datadown', 1);
            $module->addProjectToList($project_id_new, $rowtype['event_id'], 1, 'deactivate_tblcenter', 1);
        }else if($hub_profile == 'basic'){

        }else if($hub_profile == 'all'){

        }

        \Records::addRecordToRecordListCache($project_id_new, $record,1);
    }

    foreach($projects_array_repeatable[$index] as $repeat_event){
        if($repeat_event['status'] == 1){
            $q = $module->query("SELECT b.event_id FROM  redcap_events_arms a LEFT JOIN redcap_events_metadata b ON(a.arm_id = b.arm_id) where a.project_id = ?",[$project_id_new]);
            while ($row = $q->fetch_assoc()) {
                $event_id = $row['event_id'];
                $module->query("INSERT INTO redcap_events_repeat (event_id, form_name, custom_repeat_form_label) VALUES (?, ?, ?)",[$event_id,$repeat_event['instrument'],$repeat_event['params']]);
            }
        }
    }

    #enable modules in projects
    if($projects_array_hooks[$index] == '1') {
        #enable current module to activate hooks
        $module->enableModule($project_id_new, "");
    }
    if($projects_array_module_seamlessiframe[$index] == '1'){
        #enable modules to certain projects
        $module->enableModule($project_id_new,"seamless-iframes-module");
        $othermodule = ExternalModules::getModuleInstance("seamless-iframes-module");
        $othermodule->setProjectSetting("allowed-url-prefixes", APP_PATH_WEBROOT_FULL."external_modules/?prefix=harmonist-hub&page=index?pid=".$project_id, $project_id_new);
    }
    if(array_key_exists($index,$projects_array_module_emailalerts)){
        #enable modules to certain projects
        $module->enableModule($project_id_new,"vanderbilt_emailTrigger");
        $othermodule = ExternalModules::getModuleInstance("vanderbilt_emailTrigger");
        foreach ($projects_array_module_emailalerts[$index] as $setting_name => $setting_value){
            $othermodule->setProjectSetting($setting_name, $setting_value, $project_id_new);
        }
    }

    \Records::addRecordToRecordListCache($project_id, $record,1);
    $record++;

   #we create the surveys
    if(array_key_exists($index,$projects_array_surveys)){
        foreach ($projects_array_surveys[$index] as $survey){
            $formName = ucwords(str_replace("_"," ",$survey));
            $module->query("INSERT INTO redcap_surveys (project_id,form_name,survey_enabled,save_and_return,save_and_return_code_bypass,edit_completed_response,title) VALUES (?,?,?,?,?)",[$project_id_new,$survey,1,1,1,$formName]);
        }
    }
    if(array_key_exists($index,$projects_array_surveys_hash)){
        $hash = $module->getPublicSurveyHash($project_id_new);

        $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'record_id', $record);
        $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'project_id', $hash);
        $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'project_constant', $projects_array_surveys_hash[$index]['constant']);
        $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'project_show_y', 0);
        $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'project_info_complete', 2);

        \Records::addRecordToRecordListCache($project_id, $record,1);
        $record++;
    }


}
#Upload SQL fields to projects
include_once("projects.php");

$projects_array_sql = array(
    IEDEA_DATAMODEL=>array(
        'variable_replacedby' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM redcap_data WHERE project_id=".IEDEA_DATAMODEL." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM redcap_data WHERE project_id=".IEDEA_DATAMODEL." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
        'code_list_ref' => "select record, value from redcap_data where project_id = ".IEDEA_CODELIST." and field_name = 'list_name' order by value asc"
    ),
    IEDEA_HARMONIST=>array(
        'contact_link' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_PEOPLE." GROUP BY a.record ORDER BY value",
        'wg_link' => "SELECT a.record, CONCAT( max(if(a.field_name = 'group_name', a.value, '')), ' (', max(if(a.field_name = 'group_abbr', a.value, '')), ') ' ) as value FROM redcap_data a WHERE a.project_id=".IEDEA_GROUPS." GROUP BY a.record ORDER BY value",
        'wg2_link' => "SELECT a.record, CONCAT( max(if(a.field_name = 'group_name', a.value, '')), ' (', max(if(a.field_name = 'group_abbr', a.value, '')), ') ' ) as value FROM redcap_data a WHERE a.project_id=".IEDEA_GROUPS." GROUP BY a.record ORDER BY value",
        'lead_region' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_REGIONS." GROUP BY a.record  ORDER BY value",
        'person_link' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_PEOPLE." GROUP BY a.record ORDER BY value"
    ),
    IEDEA_RMANAGER=>array(
        'assoc_concept' => "SELECT a.record, CONCAT(a.value, ' | ', b.value) FROM (SELECT record, value FROM redcap_data WHERE project_id = ".IEDEA_HARMONIST." AND field_name = 'concept_id') a JOIN (SELECT record, value FROM redcap_data where project_id = ".IEDEA_HARMONIST." and field_name = 'concept_title') b ON b.record=a.record ORDER BY a.value DESC, b.value ",
        'contact_region' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_REGIONS."  GROUP BY a.record  ORDER BY value",
        'contactperson_id' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_PEOPLE." GROUP BY a.record ORDER BY value",
        'reviewer_id' => "SELECT a.record, CONCAT(a.value, ' ', b.value) as value FROM (SELECT record, value FROM redcap_data WHERE project_id = ".IEDEA_PEOPLE." AND field_name = 'firstname') a JOIN (SELECT record, value FROM redcap_data where project_id = ".IEDEA_PEOPLE." and field_name = 'lastname') b ON b.record=a.record JOIN (SELECT record, value from redcap_data where project_id = ".IEDEA_PEOPLE." and field_name = 'harmonistadmin_y' and value = 1) c ON c.record=a.record ORDER BY a.value, b.value",
        'responding_region' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_REGIONS."  GROUP BY a.record  ORDER BY value",
        'finalizer_id' => "SELECT a.record, CONCAT(a.value, ' ', b.value) as value FROM (SELECT record, value FROM redcap_data WHERE project_id = ".IEDEA_PEOPLE." AND field_name = 'firstname') a JOIN (SELECT record, value FROM redcap_data where project_id = ".IEDEA_PEOPLE." and field_name = 'lastname') b ON b.record=a.record JOIN (SELECT record, value from redcap_data where project_id = ".IEDEA_PEOPLE." and field_name = 'harmonistadmin_y' and value = 1) c ON c.record=a.record ORDER BY a.value, b.value",
        'mr_existing' => "SELECT a.record, CONCAT(a.value, ' | ', b.value) FROM (SELECT record, value FROM redcap_data WHERE project_id = ".IEDEA_HARMONIST." AND field_name = 'concept_id') a JOIN (SELECT record, value FROM redcap_data where project_id = ".IEDEA_HARMONIST." and field_name = 'concept_title') b ON b.record=a.record ORDER BY a.value, b.value"
    ),
    IEDEA_COMMENTSVOTES=>array(
        'response_person' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_PEOPLE." GROUP BY a.record ORDER BY value",
        'response_region' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_REGIONS."  GROUP BY a.record  ORDER BY value"
    ),
    IEDEA_SOP=>array(
        'sop_hubuser' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_PEOPLE." GROUP BY a.record ORDER BY value",
        'sop_creator' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_PEOPLE." GROUP BY a.record ORDER BY value",
        'sop_creator2' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_PEOPLE." GROUP BY a.record ORDER BY value",
        'sop_datacontact' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_PEOPLE." GROUP BY a.record ORDER BY value",
        'sop_concept_id' => "SELECT a.record, CONCAT(a.value, ' | ', b.value) FROM (SELECT record, value FROM redcap_data WHERE project_id = ".IEDEA_HARMONIST." AND field_name = 'concept_id') a JOIN (SELECT record, value FROM redcap_data where project_id = ".IEDEA_HARMONIST." and field_name = 'concept_title') b ON b.record=a.record ORDER BY a.value, b.value",
        'sop_finalize_person' => "SELECT DISTINCT a.record, CONCAT(a.value, ' ', b.value) AS VALUE FROM redcap_data a LEFT JOIN redcap_data b on b.project_id = ".IEDEA_PEOPLE." and b.record = a.record and b.field_name = 'lastname' LEFT JOIN redcap_data c on c.project_id = ".IEDEA_PEOPLE." and c.record = a.record WHERE a.field_name = 'firstname' and a.project_id = ".IEDEA_PEOPLE." and ((c.field_name = 'harmonist_perms' AND c.value = '1') OR (c.field_name = 'harmonistadmin_y' AND c.value = '1')) ORDER BY a.value, b.value",
        'data_region' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_REGIONS." GROUP BY a.record  ORDER BY value"
    ),
    IEDEA_SOPCOMMENTS=>array(
        'response_person' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_PEOPLE." GROUP BY a.record ORDER BY value",
        'response_region' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_REGIONS."  GROUP BY a.record  ORDER BY value"
    ),
    IEDEA_PEOPLE=>array(
        'person_region' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_REGIONS."  GROUP BY a.record  ORDER BY value"
    ),
    IEDEA_DATAUPLOAD=>array(
        'data_assoc_concept' => "SELECT a.record, CONCAT(a.value, ' | ', b.value) FROM (SELECT record, value FROM redcap_data WHERE project_id = ".IEDEA_HARMONIST." AND field_name = 'concept_id') a JOIN (SELECT record, value FROM redcap_data where project_id = ".IEDEA_HARMONIST." and field_name = 'concept_title') b ON b.record=a.record ORDER BY a.value, b.value",
        'data_assoc_request' => "SELECT a.record, CONCAT(a.value, ' | ', b.value) FROM (SELECT record, value FROM redcap_data WHERE project_id = ".IEDEA_SOP." AND field_name = 'record_id') a JOIN (SELECT record, value FROM redcap_data where project_id = ".IEDEA_SOP." and field_name = 'sop_name') b ON b.record=a.record ORDER BY a.value, b.value",
        'data_upload_person' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_PEOPLE." GROUP BY a.record ORDER BY value",
        'data_upload_region' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_REGIONS."  GROUP BY a.record  ORDER BY value",
        'deletion_hubuser' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_PEOPLE." GROUP BY a.record ORDER BY value"
    ),
    IEDEA_DATADOWNLOAD=>array(
        'downloader_assoc_concept' => "SELECT a.record, CONCAT(a.value, ' | ', b.value) FROM (SELECT record, value FROM redcap_data WHERE project_id = ".IEDEA_HARMONIST." AND field_name = 'concept_id') a JOIN (SELECT record, value FROM redcap_data where project_id = ".IEDEA_HARMONIST." and field_name = 'concept_title') b ON b.record=a.record ORDER BY a.value, b.value",
        'downloader_id' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_PEOPLE." GROUP BY a.record ORDER BY value",
        'downloader_region' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_REGIONS."  GROUP BY a.record  ORDER BY value",
        'download_id' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'record_id', a.value, NULL)),    ' (',   max(if(a.field_name = 'responsecomplete_ts', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_DATAUPLOAD."  GROUP BY a.record  ORDER BY value"
    ),
    IEDEA_DATAAVAILABILITY=>array(
        'available_table' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'table_name', a.value, NULL)),    ' () ' ) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_DATAMODEL."  GROUP BY a.record  ORDER BY value",
        'available_variable' => "SELECT CONCAT(a.record, '|', b.instance), CONCAT(a.value, ' | ', b.value) FROM (SELECT record,value FROM redcap_data WHERE project_id=".IEDEA_DATAMODEL." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM redcap_data WHERE project_id=".IEDEA_DATAMODEL." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance"
    ),
    IEDEA_DATATOOLMETRICS=>array(
        'userregion_id' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_REGIONS."  GROUP BY a.record  ORDER BY value"
    ),
    IEDEA_FILELIBRARY=>array(
        'file_uploader' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_PEOPLE." GROUP BY a.record ORDER BY value"
    ),
    IEDEA_FILELIBRARYDOWN=>array(
        'library_download_person' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_PEOPLE." GROUP BY a.record ORDER BY value",
        'library_download_region' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_REGIONS."  GROUP BY a.record  ORDER BY value"
    ),
    IEDEA_NEWITEMS=>array(
        'news_person' => "SELECT DISTINCT a.record, CONCAT(a.value, ' ', b.value) AS  VALUE  FROM redcap_data a  LEFT JOIN redcap_data b on b.project_id = ".IEDEA_PEOPLE." and b.record = a.record and b.field_name = 'lastname'  LEFT JOIN redcap_data c on c.project_id = ".IEDEA_PEOPLE." and c.record = a.record  WHERE a.field_name = 'firstname' and a.project_id = ".IEDEA_PEOPLE." and ((c.field_name = 'harmonist_perms' AND c.value = '9') OR (c.field_name = 'harmonistadmin_y' AND c.value = '1'))  ORDER BY     a.value,      b.value"
    ),
    IEDEA_EXTRAOUTPUTS=>array(
        'lead_region' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".IEDEA_REGIONS." GROUP BY a.record  ORDER BY value"
    )
);

foreach ($projects_array_sql as $projectid=>$project){
    foreach ($project as $var=>$sql){
        $module->query("UPDATE redcap_metadata SET element_enum = ? WHERE project_id = ? AND field_name=?",[$sql,$projectid,$var]);
    }
}


echo json_encode(array(
        'status' =>'success'
    )
);
?>
