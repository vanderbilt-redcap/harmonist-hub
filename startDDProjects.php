<?php
namespace Vanderbilt\HarmonistHubExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

$project_id = $_REQUEST['pid'];
$hub_projectname = $module->getProjectSetting('hub-projectname');
$hub_profile = $module->getProjectSetting('hub-profile');
#hardcoded value for now.
$hub_profile =  "solo";
$userPermission = $module->getProjectSetting('user-permission',$project_id);
$module->setProjectSetting('hub-mapper',$project_id);

#PID MAPPER
$module->setPIDMapperProject($project_id);

$projects_array = REDCapManagement::getProjectsContantsArray();
$projects_titles_array = REDCapManagement::getProjectsTitlesArray();
$projects_array_repeatable = REDCapManagement::getProjectsRepeatableArray();

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
        4=>'tracking_number_assignment_survey'
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

$custom_record_label_array = array(0=>"[table_name]",1=>"[list_name]",2=>'<span style=\'color:#[dashboard_color]\'><b>[concept_id]</b> [contact_link]</span>',
                        3=>'[contact_name], [request_type] (Due: [due_d])',4=>"[request_id], [response_person]",5=>'[sop_hubuser]',6=>'',
                        7=>'([region_name], [region_code])',8=>'[firstname] [lastname]',9=>'[group_abbr], [group_name]', 10=>'[help_question]',
                        11=>'',12=>'',13=>'[download_id], [downloader_id]', 14=>'[type]',15=>'',16=>'[available_variable], [available_status]',17=>'',
                        18=>'[action_ts], [action_step]',19=>'', 20=>'',21=>'',22=>'',23=>'',24=>'',25=>'',
                        26=>'<span style=\'color:#[dashboard_color]\'><b>([producedby_region:value]) [output_year] [output_type]</b> | [output_title]', 27=>'([name])',28=>'');

$projects_array_hooks = array(0=>'1',1=>'1',2=>'1',3=>'1',4=>'1',5=>'1',6=>'1',
    7=>'0',8=>'1',9=>'0', 10=>'0',11=>'1',12=>'0',13=>'0',
    14=>'0',15=>'0',16=>'0',17=>'0',18=>'0',19=>'0',
    20=>'0',21=>'0',22=>'0',23=>'0',24=>'0',25=>'0',26=>'0',27=>'1',28=>'0');

$projects_array_module_emailalerts = array(
    3=> array(
        "datapipeEmail_var" => "[contact_email], Contact Email
        [cc_email1], CC Email 1
        [cc_email2], CC Email 2",
        "emailFromForm_var" => "",
        "emailSender_var" => $hub_projectname." Hub",
        "datapipe_var" => "[request_id], Request ID
        [request_type], Request Type
        [request_title], Request Title
        [request_conf], Conference
        [assoc_concept], Concept Tracking Number
        [wg_name], ".$hub_projectname." WG1
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
        [mr_assigned], Assigned Tracking Number
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
        [__SURVEYLINK_tracking_number_assignment_survey],Tracking Number Assignment Survey",
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
            13 => "tracking_number_assignment_survey",
            14 => "tracking_number_assignment_survey",
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
            9 => $hub_projectname." Request #[request_id] approved by [EXECUTIVE COMMITTEE NAME]: [request_type], [contact_name]",
            10 => $hub_projectname." Request #[request_id] voting incomplete: [request_type], [contact_name]",
            11 => $hub_projectname." Request #[request_id] post-approval final steps: [request_type], [contact_name]",
            12 => "New [request_type] needs MR: Request #[request_id], [contact_name]",
            13 => "New ".$hub_projectname." Concept approved: [mr_assigned], [contact_name]",
            14 => "New ".$hub_projectname." Concept: [mr_assigned], [contact_name]",
            15 => $hub_projectname." Request #[request_id] approved by [EXECUTIVE COMMITTEE NAME]: [request_type], [contact_name]"
        ),
        "email-text" => array
        (
            0 => '<h2>New Submission</h2>
<p>Thank you for submitting a review request to the '.$hub_projectname.' [EXECUTIVE COMMITTEE NAME]. This email serves as your confirmation that the request has been submitted to the system. An '.$hub_projectname.' Hub Admin will review your request and contact you with any followup questions. You will be notified once the request is distributed to the '.$hub_projectname.' [EXECUTIVE COMMITTEE NAME].</p>
<p><strong>Request Tracking ID:</strong>&nbsp; [request_id]</p>
<p><strong>Review Type:</strong>&nbsp; [request_type]</p>
<p><strong>Contact Person:</strong>&nbsp; [contact_name], [contact_email]</p>
<p><strong>Request Title:</strong>&nbsp; [request_title]</p>
<p><strong>Description:</strong>&nbsp; [request_description]</p>
<p><span style="color: #000000;"><strong>Link to review/edit submission #[request_id]:</strong> </span>[__SURVEYLINK_request]</p>
<p>&nbsp;</p>
<p><span style="color: #999999; font-size: 11px;">This email has been automatically generated by the <a href="'.$module->getUrl('index.php').'">'.$hub_projectname.' Hub system</a>. If someone incorrectly submitted this request on your behalf or if you believe you received this email in error, please contact <a href="mailto:'.$hub_projectname.'@fake.com">'.$hub_projectname.'@fake.com</a>.</span></p>',
            1 => '<h2>Submission Posted</h2>
<p>Your request has been reviewed by <strong>[reviewer_id]</strong> and will now be displayed on the '.$hub_projectname.' Hub. This will begin the '.$hub_projectname.' [EXECUTIVE COMMITTEE NAME] review process.</p>
<p><strong><span style="color: #e74c3c;">Due Date Assigned</span>:</strong>&nbsp;[due_d]</p>
<p><strong>Request Tracking ID:</strong>&nbsp; [request_id]</p>
<p><strong>Review Type:</strong>&nbsp; [request_type]</p>
<p><strong>Contact Person:</strong>&nbsp; [contact_name], [contact_email]</p>
<p><strong>Request Title:</strong>&nbsp; [request_title]</p>
<p><strong>Description:</strong>&nbsp; [request_description]</p>
<p><strong>Public Admin Notes:</strong>&nbsp; [admin_review_notes]</p>
<p><span style="color: #000000;"><strong>Link to review/edit submission #[request_id]:</strong> </span>[__SURVEYLINK_request]</p>
<p>&nbsp;</p>
<p><span style="color: #999999; font-size: 11px;">This email has been automatically generated by the <a href="'.$module->getUrl('index.php').'">'.$hub_projectname.' Hub system</a>. If someone incorrectly submitted this request on your behalf or if you believe you received this email in error, please contact <a href="mailto:harmonist@vumc.org">harmonist@vumc.org</a>.</span></p>',
            2 => '<h2>New Submission</h2>
<p>A new review request has been submitted and requires admin review before posting to the '.$hub_projectname.' Hub.</p>
<p><strong>Request Tracking ID:</strong>&nbsp; [request_id]</p>
<p><strong>Review Type:</strong>&nbsp; [request_type]</p>
<p><strong>Contact Person:</strong>&nbsp; [contact_name], [contact_email]</p>
<p><strong>Request Title:</strong>&nbsp; [request_title]</p>
<p><strong>Description:</strong>&nbsp; [request_description]</p>
<h2>Actions</h2>
<p><strong>1. Link to <span style="color: #e74c3c;">review/edit submission #[request_id]</span>:</strong>&nbsp;<br />[__SURVEYLINK_request]</p>
<p><strong>2. Link to Hub Admin <span style="color: #16a085;">approval page</span>:</strong><br />[__SURVEYLINK_admin_review]</p>',
            3 => '<h2>Submission Posted</h2>
<p>The following request has been reviewed by <strong>[reviewer_id]</strong> and will now be displayed on the '.$hub_projectname.' Hub.</p>
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
<p>The following request has been paused or deactivated by <strong>[reviewer_id]</strong>. It will not appear on the '.$hub_projectname.' Hub.</p>
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
<p>The following request has been rejected by <strong>[reviewer_id]</strong>. It will not appear on the '.$hub_projectname.' Hub. <strong><span style="color: #e74c3c;">The Contact Person has not been notified.</span></strong></p>
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
            6 => '<h2>Question about '.$hub_projectname.' Request</h2>
<p>Your request has been approved by <strong>[reviewer_id]</strong> and will now be displayed on the '.$hub_projectname.' Hub.</p>
<p><strong><span style="color: #e74c3c;">Due Date Assigned</span>:</strong>&nbsp;[due_d]</p>
<p><strong>Request Tracking ID:</strong>&nbsp; [request_id]</p>
<p><strong>Review Type:</strong>&nbsp; [request_type]</p>
<p><strong>Contact Person:</strong>&nbsp; [contact_name], [contact_email]</p>
<p><strong>Request Title:</strong>&nbsp; [request_title]</p>
<p><strong>Description:</strong>&nbsp; [request_description]</p>
<p><strong>Public Admin Notes:</strong>&nbsp; [admin_internal_notes]</p>
<p><span style="color: #000000;"><strong>Link to review/edit submission #[request_id]:</strong> </span>[__SURVEYLINK_request]</p>
<p>&nbsp;</p>
<p><span style="color: #999999; font-size: 11px;">This email has been automatically generated by the <a href="'.$module->getUrl('index.php').'">'.$hub_projectname.' Hub system</a>. If someone incorrectly submitted this request on your behalf or if you believe you received this email in error, please contact <a href="mailto:harmonist@vanderbilt.edu">harmonist@vanderbilt.edu</a>.</span></p>',
            7 => '<h2>Voting Complete</h2>
<p>The following request has received all regional votes. Please take action below to finalize the request or respond to votes and comments.</p>
<p><strong><span style="color: #e74c3c;">Due Date Assigned</span>:</strong>&nbsp;[due_d]</p>
<p><strong>Request Tracking ID:</strong>&nbsp; [request_id]</p>
<p><strong>Review Type:</strong>&nbsp; [request_type]</p>
<p><strong>Contact Person:</strong>&nbsp; [contact_name], [contact_email]</p>
<p><strong>Request Title:</strong>&nbsp; [request_title]</p>
<h2>Summary of Votes</h2>
<p><strong>[REGION 1]:</strong>&nbsp; [vote_ap]</p>
<p><strong>[REGION 2]:</strong>&nbsp; [vote_ca]</p>
<p><strong>[REGION 3]:</strong>&nbsp; [vote_cn]</p>
<p><strong>[REGION 4]:</strong>&nbsp; [vote_ea]</p>
<p><strong>[REGION 5]:</strong>&nbsp; [vote_na]</p>
<p><strong>[REGION 6]:</strong>&nbsp; [vote_sa]</p>
<p><strong>[REGION 7]:</strong>&nbsp; [vote_wa]</p>
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
<p><strong>[REGION 1]:</strong>&nbsp; [vote_ap]</p>
<p><strong>[REGION 2]:</strong>&nbsp; [vote_ca]</p>
<p><strong>[REGION 3]:</strong>&nbsp; [vote_cn]</p>
<p><strong>[REGION 4]:</strong>&nbsp; [vote_ea]</p>
<p><strong>[REGION 5]:</strong>&nbsp; [vote_na]</p>
<p><strong>[REGION 6]:</strong>&nbsp; [vote_sa]</p>
<p><strong>[REGION 7]:</strong>&nbsp; [vote_wa]</p>
<h2>Actions</h2>
<p><strong>1. Link to <span style="color: #31708f;">Visit Request page</span>:</strong><br /><a href="'.$module->getUrl('index.php?option=hub&amp;record=[request_id]').'">'.$module->getUrl('index.php?option=hub&amp;record=[request_id]').'</a></p>
<p><strong>2. Link to <span style="color: #16a085;">Finalize Request page</span>:</strong><br />[__SURVEYLINK_finalization_of_request]</p>',
            9 => '<h2>Request Approved by '.$hub_projectname.' [EXECUTIVE COMMITTEE NAME]</h2>
<p>Dear [contact_name],</p>
<p>We are pleased to confirm approval of your '.$hub_projectname.' [request_type], <strong>[request_title]</strong>. The '.$hub_projectname.' [EXECUTIVE COMMITTEE NAME] approval date is [final_d].</p>
<p><strong>Next Steps</strong></p>
<ol>
<li>Please check the '.$hub_projectname.' Hub for <a href="'.$module->getUrl('index.php?option=hub&amp;record=[request_id]').'">any comments or queries from the regions</a> and incorporate further revisions into your document.</li>
<li>Remove all comments and tracked changes from the document.</li>
<li>Upload a final version of your [request_type] for logging at the link below.&nbsp;</li>
</ol>
<p>[__SURVEYLINK_final_docs_request_survey]</p>
<p>Please contact [HUB ADMIN CONTACT] or [HUB ADMIN CONTACT2] if you have any questions.</p>
<p>Thank you for participating in '.$hub_projectname.' research.</p>
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
<p><strong>[REGION 1]:</strong>&nbsp; [vote_ap]</p>
<p><strong>[REGION 2]:</strong>&nbsp; [vote_ca]</p>
<p><strong>[REGION 3]:</strong>&nbsp; [vote_cn]</p>
<p><strong>[REGION 4]:</strong>&nbsp; [vote_ea]</p>
<p><strong>[REGION 5]:</strong>&nbsp; [vote_na]</p>
<p><strong>[REGION 6]:</strong>&nbsp; [vote_sa]</p>
<p><strong>[REGION 7]:</strong>&nbsp; [vote_wa]</p>
<h2>Actions</h2>
<p><strong>1. Link to <span style="color: #31708f;">Visit Request page</span>:</strong><br /><a href="'.$module->getUrl('index.php?option=hub&amp;record=[request_id]').'">'.$module->getUrl('index.php?option=hub&amp;record=[request_id]').'</a></p>
<p><strong>2. Link to <span style="color: #16a085;">Finalize Request page</span>:</strong><br />[__SURVEYLINK_finalization_of_request]</p>',
            11 => '<h2>Final Documents Requested</h2>
<p>Dear [contact_name],</p>
<p>We are pleased to confirm approval of your '.$hub_projectname.' concept,&nbsp;<strong>[request_title]</strong>. The '.$hub_projectname.' [EXECUTIVE COMMITTEE NAME] approval date is <strong>[final_d]</strong>.</p>
<p><strong>Next Steps</strong></p>
<ol>
<li style="padding-bottom: 5px;">Please check the '.$hub_projectname.' Hub for <a href="https://redcap.vanderbilt.edu/plugins/iedea/index.php?option=hub&amp;record=[request_id]">any changes requested by the Executive Comitee</a> and incorporate further revisions into your concept sheet.</li>
<li style="padding-bottom: 5px;">Remove all comments and tracked changes from the document.</li>
<li style="padding-bottom: 5px;"><strong><span style="color: #e74c3c;">Upload a final version of your concept</span></strong> to the '.$hub_projectname.' Hub using the link below. <strong>This will trigger all subsequent steps for your project.</strong></li>
</ol>
<p>&nbsp;</p>
<p>[__SURVEYLINK_final_docs_request_survey]</p>
<p>&nbsp;</p>
<p>After you have uploaded the final version of your concept, the '.$hub_projectname.' concept sheet management team will <strong>assign a concept tracking number</strong> and your concept will be logged on the Hub. You <strong>must have a tracking number before requesting '.$hub_projectname.' data.</strong>&nbsp;Once your tracking number has been assigned, you will receive a notification email with the tracking number and next steps for your project. Please contact&nbsp;[HUB ADMIN CONTACT]&nbsp;or&nbsp;[HUB ADMIN CONTACT2]&nbsp;if you have any questions.</p>
<p>This email is scheduled to repeat as a reminder&nbsp;<strong>every 7 days</strong> until documents have been uploaded.&nbsp;</p>
<p>Thank you for participating in '.$hub_projectname.' research.</p>
<p>&nbsp;</p>
<hr />
<p><em>Additional review notes (if needed):</em></p>
<p>[custom_note]</p>
<hr />
<p>&nbsp;</p>
<p><span style="color: #999999; font-size: 11px;">This email has been automatically generated by the <a href="'.$module->getUrl('index.php').'">'.$hub_projectname.' Hub system</a>. If someone incorrectly submitted this request on your behalf or if you believe you received this email in error, please contact <a href="mailto:harmonist@vumc.org">harmonist@vumc.org</a>.</span></p>',
            12 => '<h2>'.$hub_projectname.' Concept Needs Tracking Number</h2>
<p>Dear [HUB ADMIN CONTACT],</p>
<p>The following '.$hub_projectname.' concept has been approved by the [EXECUTIVE COMMITTEE NAME] on [final_d].</p>
<p><strong>[request_title]</strong> (Request ID: [request_id])</p>
<p>[contact_name], <a href="mailto:[contact_email]">[contact_email]</a></p>
<p>The author\'s final documents are attached. Please follow the link below to assign a tracking number and update the documents.</p>
<p>This email <strong>will repeat every 7 days</strong> as a reminder.</p>
<p>&nbsp;</p>
<h2>Actions</h2>
<p><strong>1. Link to <span style="color: #e74c3c;">view author submission (optional)</span>:</strong>&nbsp;<br />[__SURVEYLINK_final_docs_request_survey]</p>
<p><strong>2. Link to <span style="color: #16a085;">assign tracking number</span>:</strong><br />[__SURVEYLINK_tracking_number_assignment_survey]</p>
<p>&nbsp;</p>
<p><span style="color: #999999; font-size: 11px;">This email has been automatically generated by the <a href="'.$module->getUrl('index.php').'">'.$hub_projectname.' Hub system</a>. If someone incorrectly submitted this request on your behalf or if you believe you received this email in error, please contact <a href="mailto:harmonist@vanderbilt.edu">harmonist@vanderbilt.edu</a>.</span></p>',
            13 => '<h2>'.$hub_projectname.' Tracking Number Assigned</h2>
<p>Dear [contact_name],</p>
<p>Your '.$hub_projectname.' concept, <strong>[request_title]</strong>, has been assigned the following tracking number:</p>
<p><strong>[mr_assigned]</strong></p>
<p>A listing for your new concept will be available soon on the Concepts page of the '.$hub_projectname.' Hub (<a href="'.$module->getUrl('index.php').'">'.$module->getUrl('index.php').'</a>).</p>
<p><strong>Next Steps</strong></p>
<ol>
<li style="padding-bottom: 5px;"><strong>Data:</strong> The '.$hub_projectname.' regional data leads are cc\'d if you are requesting either '.$hub_projectname.' patient-level or site assessment data. Please follow up with them to develop an official Data Request or receive access to existing datasets, as stated in your [EXECUTIVE COMMITTEE NAME]-approved concept sheet.</li>
<li style="padding-bottom: 5px;"><strong>Collaborating Authors:</strong> We recommend including regional representatives on your writing team at an early stage of the project (don\'t wait until the end.) If you need to identify regional collaborators, send your request to [HUB ADMIN CONTACT], who can forward it to the regional coordinators.</li>
<li style="padding-bottom: 5px;"><strong>Updates:</strong> You will be automatically subscribed to an email survey that will request a brief project update (2-3 sentences) related to this concept every 90 days. These updates will be shared with the [EXECUTIVE COMMITTEE NAME] and logged in the overall '.$hub_projectname.' project tracker.</li>
<li style="padding-bottom: 5px;"><strong>Abstracts and Publications:</strong> All resulting abstracts and publications will need review and approval from your collaborating working group (if applicable) and the '.$hub_projectname.' [EXECUTIVE COMMITTEE NAME]. [EXECUTIVE COMMITTEE NAME] turnaround times are approximately 1+ weeks for abstracts and 2+ weeks for manuscripts. Actual dates are set by the '.$hub_projectname.' admin team. Please plan ahead for any conference and journal deadlines.</li>
</ol>
<p>Thank you for participating in '.$hub_projectname.' research.</p>
<p>&nbsp;</p>
<p><span style="color: #999999; font-size: 11px;">This email has been automatically generated by the <a href="'.$module->getUrl('index.php').'">'.$hub_projectname.' Hub system</a>. If someone incorrectly submitted this request on your behalf or if you believe you received this email in error, please contact <a href="mailto:harmonist@vanderbilt.edu">harmonist@vanderbilt.edu</a>.</span></p>',
            14 => '<h2>New '.$hub_projectname.' Concept: [mr_assigned]</h2>
<p>The following new '.$hub_projectname.' concept has been approved by the '.$hub_projectname.' [EXECUTIVE COMMITTEE NAME]:</p>
<p><strong>[mr_assigned]:&nbsp;</strong><em>[request_title]</em></p>
<p>&nbsp;</p>
<p>The main project contact is <strong>[contact_name]</strong> (<a href="mailto:[contact_email]">[contact_email]</a>).</p>
<p>The finalized concept sheet PDF is attached (if available). Project updates will be tracked on the <a href="'.$module->getUrl('index.php').'">'.$hub_projectname.' Hub</a>. Please archive the document or distribute for review in your regions.</p>
<p>&nbsp;</p>
<p><span style="color: #999999; font-size: 11px;">This email has been automatically generated by the <a href="'.$module->getUrl('index.php').'">'.$hub_projectname.' Hub system</a>. If you believe you received this email in error, please contact <a href="mailto:harmonist@vanderbilt.edu">harmonist@vanderbilt.edu</a>.</span></p>',
            15 => '<h2>'.$hub_projectname.' Request Approved</h2>
<p>Dear [contact_name],</p>
<p>We are pleased to confirm approval of your '.$hub_projectname.' concept,&nbsp;<strong>[request_title]</strong>. The '.$hub_projectname.' [EXECUTIVE COMMITTEE NAME] approval date is <strong>[final_d]</strong>.</p>
<p><strong>Next Steps</strong></p>
<ol>
<li style="padding-bottom: 5px;">Please check the '.$hub_projectname.' Hub for <a href="'.$module->getUrl('index.php?option=hub?record=[request_id]').'">any feedback or changes requested by the [EXECUTIVE COMMITTEE NAME]</a> and incorporate them into the final version or respond directly to the region/investigator if this is not feasible.</li>
<li style="padding-bottom: 5px;">Contact&nbsp;[HUB ADMIN CONTACT]&nbsp; (cc\'d) or&nbsp;[HUB ADMIN CONTACT2]&nbsp;with any general questions.</li>
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
<p>Thank you for your leadership of '.$hub_projectname.'&rsquo;s research.</p>
<p>&nbsp;</p>
<hr />
<p><em>Additional review notes (if needed):</em></p>
<p>[custom_note]</p>
<hr />
<p>&nbsp;</p>
<p><span style="color: #999999; font-size: 11px;">This email has been automatically generated by the <a href="'.$module->getUrl('index.php').'">'.$hub_projectname.' Hub system</a>. If someone incorrectly submitted this request on your behalf or if you believe you received this email in error, please contact <a href="mailto:harmonist@vumc.org">harmonist@vumc.org</a>.</span></p>'
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
        "email-deleted" => array
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
        "email-deactivate" => array
        (
            0 => 1,
            1 => 1,
            2 => 1,
            3 => 1,
            4 => 1,
            5 => 1,
            6 => 1,
            7 => 1,
            8 => 1,
            9 => 1,
            10 => 1,
            11 => 1,
            12 => 1,
            13 => 1,
            14 => 1,
            15 => 1
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
            9 => "To Admin: non-concept approved by EC",
            10 => "To Admin: alert voting incomplete by due_d TEST2",
            11 => "To Author: concept or fast-track approved by EC",
            12 => "To Tracking Number Team: Assign Tracking Number",
            13 => "To Author and Admins: Tracking Number Assigned",
            14 => "To PMs: notification of new concept",
            15 => "To Author: non-concept approved by EC",
        )
    )
);

$projects_array_surveys_hash = array(
    1=>array('constant'=>'ANALYTICS','instrument' => ''),
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

$pidHome = "";
$record = 1;
foreach ($projects_array as $index=>$name){
    $project_title = $hub_projectname." Hub: ".$projects_titles_array[$index];
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
            $module->addProjectToList($project_id_new, $rowtype['event_id'], 1, 'deactivate_toolkit', 1);
        }else if($hub_profile == 'basic'){
            $module->addProjectToList($project_id_new, $rowtype['event_id'], 1, 'deactivate_datadown', 1);
            $module->addProjectToList($project_id_new, $rowtype['event_id'], 1, 'deactivate_toolkit', 1);
        }else if($hub_profile == 'all'){
            #We show everything
            if(SERVER_NAME == 'redcap.vanderbilt.edu') {
                #We send an email with a list of things to set up only in the Vardebilt server
                $subject = "Data Toolkit activation request for " . $hub_projectname . " Hub";
                $message = "<div>Dear Administrator,</div><br/>" .
                    $message = "<div>A new request has been enabled to activate the Data Toolkit for <strong>" . $hub_projectname . " Hub </strong>(<em>PID " . $project_id . "</em>)</div><br/>" .
                        "<div>The following elements need to be enabled:</div>" .
                        "<ul>" .
                        "<li>Data Toolkit</li>" .
                        "<li>AWS Bucket name</li>" .
                        "<li>AWS Bucket Credentials</li>" .
                        "</ul>";
                sendEmail("harmonist@vumc.org", "noreply.harmonist@vumc.org", "noreply.harmonist@vumc.org", $subject, $message, "Not in database","New DataTolkit setup needed",$project_id_new);
            }
        }

        #ADD USER PERMISSIONS
        $fields_rights = "username=?, design=?, user_rights=?, data_export_tool=?, reports=?, graphical=?, data_logging=?, data_entry=?";
        $instrument_names = \REDCap::getInstrumentNames(null,$project_id_new);
        $data_entry = "[".implode(',1][',array_keys($instrument_names)).",1]";
        foreach ($userPermission as $user){
            if($user != null) {
                $module->query("UPDATE redcap_user_rights SET " . $fields_rights . " WHERE project_id = ?", [$user, 1, 1, 1, 1, 1, 1, $data_entry, $project_id_new]);
            }
        }

        \Records::addRecordToRecordListCache($project_id_new, $record,1);
    }else if($name == "HOME"){
        $pidHome = $project_id_new;
    }

    #Add Repeatable projects
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
        $module->enableModule($project_id_new, "harmonist-hub");
        $module->setProjectSetting('hub-mapper',$project_id, $project_id_new);
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
        $module->query("UPDATE redcap_projects SET surveys_enabled = ? WHERE project_id = ?",["1",$project_id_new]);
        foreach ($projects_array_surveys[$index] as $survey){
            $formName = ucwords(str_replace("_"," ",$survey));
            $module->query("INSERT INTO redcap_surveys (project_id,form_name,survey_enabled,save_and_return,save_and_return_code_bypass,edit_completed_response,title) VALUES (?,?,?,?,?,?,?)",[$project_id_new,$survey,1,1,1,1,$formName]);
            $surveyId = db_insert_id();
            $hash = $module->generateUniqueRandomSurveyHash();
            $Proj = new \Project($project_id_new);
            $event_id = $Proj->firstEventId;

            $module->query("INSERT INTO redcap_surveys_participants (survey_id,hash,event_id) VALUES (?,?,?)",[$surveyId,$hash,$event_id]);

            if($index != 1 && (array_key_exists($index,$projects_array_surveys_hash) && $survey == $projects_array_surveys_hash[$index]['instrument'])){
                $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'record_id', $record);
                $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'project_id', $hash);
                $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'project_constant', $projects_array_surveys_hash[$index]['constant']);
                $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'project_show_y', 0);
                $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'project_info_complete', 2);

                \Records::addRecordToRecordListCache($project_id, $record,1);
                $record++;
            }
        }
    }
    #We add the analytics
    if($index == 1){
        $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'record_id', $record);
        $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'project_id', '');
        $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'project_constant', $projects_array_surveys_hash[$index]['constant']);
        $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'project_show_y', 0);
        $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'project_info_complete', 2);

        \Records::addRecordToRecordListCache($project_id, $record,1);
        $record++;
    }

}

#Get Projects ID's
$pidsArray = REDCapManagement::getPIDsArray($project_id);

#We must clear the project cache so our updates are pulled from the DB.
$module->clearProjectCache();
#Save instances in Homepage project
if($pidHome != ""){
    $Proj = new \Project($pidHome);
    $event_id = $Proj->firstEventId;

    #create the first record
    $module->addProjectToList($pidHome, $event_id, 1, 'record_id', 1);

    $RecordSetRequesLink = \REDCap::getData($project_id, 'array', null,null,null,null,false,false,false,"[project_constant]='REQUESTLINK'");
    $RequestLinkPid = ProjectData::getProjectInfoArray($RecordSetRequesLink)[0]['project_id'];
    $RecordSetSurveyPersonInfo = \REDCap::getData($project_id, 'array', null,null,null,null,false,false,false,"[project_constant]='SURVEYPERSONINFO'");
    $surveyPersonInfoPid = ProjectData::getProjectInfoArray($RecordSetSurveyPersonInfo)[0]['project_id'];

    $array_repeat_instances = array();
    $aux = array();
    $aux['links_sectionhead'] = "Hub Actions";
    $aux['links_sectionorder'] = '1';
    $aux['links_sectionicon'] = '1';
    $aux['links_text1'] = 'Create EC request';
    $aux['links_link1'] = 'https://redcap.vanderbilt.edu/surveys/?s='.$RequestLinkPid;
    $aux['links_text2'] = 'Add Hub user';
    $aux['links_link2'] = 'https://redcap.vanderbilt.edu/surveys/?s='.$surveyPersonInfoPid;

    $array_repeat_instances[1]['repeat_instances'][$event_id]['quick_links_section'][1] = $aux;
    $results = \REDCap::saveData($pidHome, 'array', $array_repeat_instances,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false, 1, false, '');

    $aux = array();
    $aux['links_sectionhead'] = "Harmonist";
    $aux['links_sectionorder'] = '5';
    $aux['links_sectionicon'] = '6';
    $aux['links_text1'] = 'About us';
    $aux['links_link1'] = 'index.php?option=abt';
    $aux['links_text2'] = 'Report a bug';
    $aux['links_link2'] = 'index.php?option=bug';
    $aux['links_stay2'] = array("1" => "1");

    $array_repeat_instances[1]['repeat_instances'][$event_id]['quick_links_section'][2] = $aux;
    $results = \REDCap::saveData($pidHome, 'array', $array_repeat_instances,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false, 1, false, '');
}

#Upload SQL fields to projects
$projects_array_sql = array(
    $pidsArray['DATAMODEL']=>array(
        'variable_replacedby' => array (
            'query' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM redcap_data WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM redcap_data WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '1',
            'label' => ""
        ),
        'code_list_ref' =>  array (
            'query' => "select record, value from redcap_data where project_id = ".$pidsArray['CODELIST']." and field_name = 'list_name' order by value asc",
            'autocomplete' => '0',
            'label' => ""
        )
    ),
    $pidsArray['HARMONIST']=>array(
        'contact_link' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".$pidsArray['PEOPLE']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '1',
            'label' => ""
        ),
        'contact2_link' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".$pidsArray['PEOPLE']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '1',
            'label' => ""
        ),
        'wg_link' => array (
            'query' => "SELECT a.record, CONCAT( max(if(a.field_name = 'group_name', a.value, '')), ' (', max(if(a.field_name = 'group_abbr', a.value, '')), ') ' ) as value FROM redcap_data a WHERE a.project_id=".$pidsArray['GROUP']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'wg2_link' => array (
            'query' => "SELECT a.record, CONCAT( max(if(a.field_name = 'group_name', a.value, '')), ' (', max(if(a.field_name = 'group_abbr', a.value, '')), ') ' ) as value FROM redcap_data a WHERE a.project_id=".$pidsArray['GROUP']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'lead_region' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".$pidsArray['REGIONS']." GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
            ),
        'person_link' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".$pidsArray['PEOPLE']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        )
     ),
    $pidsArray['RMANAGER']=>array(
        'assoc_concept' => array (
            'query' => "SELECT a.record, CONCAT(a.value, ' | ', b.value) FROM (SELECT record, value FROM redcap_data WHERE project_id = ".$pidsArray['HARMONIST']." AND field_name = 'concept_id') a JOIN (SELECT record, value FROM redcap_data where project_id = ".$pidsArray['HARMONIST']." and field_name = 'concept_title') b ON b.record=a.record ORDER BY a.value DESC, b.value ",
            'autocomplete' => '1',
            'label' => ""
        ),
        'wg_name' => array (
            'query' => "select record.record as record, CONCAT( max(if(group_name.field_name = 'group_name',group_name.value, '')), ' (', max(if(group_abbr.field_name = 'group_abbr', group_abbr.value, '')), ') ' ) as value from redcap_data record left join redcap_data active_y on active_y.project_id = ".$pidsArray['GROUP']." and active_y.record = record.value and active_y.field_name = 'active_y' and active_y.value ='Y' left join redcap_data group_abbr on group_abbr.project_id = ".$pidsArray['GROUP']." and group_abbr.record = record.value and group_abbr.field_name = 'group_abbr' left join redcap_data group_name on group_name.project_id = ".$pidsArray['GROUP']." and group_name.record = record.value and group_name.field_name = 'group_name' where record.field_name = 'record_id' and record.record=active_y.record and record.project_id = ".$pidsArray['GROUP']." group by record.value ORDER BY record.value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'wg2_name' => array (
            'query' => "select record.record as record, CONCAT( max(if(group_name.field_name = 'group_name',group_name.value, '')), ' (', max(if(group_abbr.field_name = 'group_abbr', group_abbr.value, '')), ') ' ) as value from redcap_data record left join redcap_data active_y on active_y.project_id = ".$pidsArray['GROUP']." and active_y.record = record.value and active_y.field_name = 'active_y' and active_y.value ='Y' left join redcap_data group_abbr on group_abbr.project_id = ".$pidsArray['GROUP']." and group_abbr.record = record.value and group_abbr.field_name = 'group_abbr' left join redcap_data group_name on group_name.project_id = ".$pidsArray['GROUP']." and group_name.record = record.value and group_name.field_name = 'group_name' where record.field_name = 'record_id' and record.record=active_y.record and record.project_id = ".$pidsArray['GROUP']." group by record.value ORDER BY record.value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'contact_region' => array (
            'query' => "select record.record as record, CONCAT( max(if(region_name.field_name = 'region_name',region_name.value, '')), ' (', max(if(region_code.field_name = 'region_code', region_code.value, '')), ') ' ) as value from redcap_data record left join redcap_data activeregion_y on activeregion_y.project_id = ".$pidsArray['REGIONS']." and activeregion_y.record = record.value and activeregion_y.field_name = 'activeregion_y' and activeregion_y.value ='1' left join redcap_data region_code on region_code.project_id = ".$pidsArray['REGIONS']." and region_code.record = record.value and region_code.field_name = 'region_code' left join redcap_data region_name on region_name.project_id = ".$pidsArray['REGIONS']." and region_name.record = record.value and region_name.field_name = 'region_name' where record.field_name = 'record_id' and record.record=activeregion_y.record and record.project_id = ".$pidsArray['REGIONS']." group by record.value ORDER BY region_name.value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'contactperson_id' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".$pidsArray['PEOPLE']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '1',
            'label' => "Contact Person:
                                  <span style='font-weight:lighter;'>[contact_name], <a href='mailto:[contact_email]'>[contact_email]</a></span>

                                    If blank, map this person to official Hub Members List
                                    <div style='font-weight:lighter;font-style:italic'>(Find the name above in the official list. If it doesn't exist, you can add this person (<a href='https://redcap.vanderbilt.edu/redcap_v8.2.1/DataEntry/record_home.php?pid=".$pidsArray['PEOPLE']."'>via REDCap</a>) or list their PI's name instead.)</div>"
        ),
        'reviewer_id' => array (
            'query' => "SELECT a.record, CONCAT(a.value, ' ', b.value) as value FROM (SELECT record, value FROM redcap_data WHERE project_id = ".$pidsArray['PEOPLE']." AND field_name = 'firstname') a JOIN (SELECT record, value FROM redcap_data where project_id = ".$pidsArray['PEOPLE']." and field_name = 'lastname') b ON b.record=a.record JOIN (SELECT record, value from redcap_data where project_id = ".$pidsArray['PEOPLE']." and field_name = 'harmonistadmin_y' and value = 1) c ON c.record=a.record ORDER BY a.value, b.value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'responding_region' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".$pidsArray['REGIONS']."  GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'finalizer_id' => array (
            'query' => "SELECT a.record, CONCAT(a.value, ' ', b.value) as value FROM (SELECT record, value FROM redcap_data WHERE project_id = ".$pidsArray['PEOPLE']." AND field_name = 'firstname') a JOIN (SELECT record, value FROM redcap_data where project_id = ".$pidsArray['PEOPLE']." and field_name = 'lastname') b ON b.record=a.record JOIN (SELECT record, value from redcap_data where project_id = ".$pidsArray['PEOPLE']." and field_name = 'harmonistadmin_y' and value = 1) c ON c.record=a.record ORDER BY a.value, b.value",
            'autocomplete' => '0',
            'label' => ""
        )
    ),
    $pidsArray['COMMENTSVOTES']=>array(
        'response_person' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".$pidsArray['PEOPLE']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'response_region' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".$pidsArray['REGIONS']."  GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        )
    ),
    $pidsArray['SOP']=>array(
        'sop_hubuser' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".$pidsArray['PEOPLE']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'sop_creator' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".$pidsArray['PEOPLE']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'sop_creator2' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".$pidsArray['PEOPLE']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'sop_datacontact' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".$pidsArray['PEOPLE']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'sop_concept_id' => array (
            'query' => "SELECT a.record, CONCAT(a.value, ' | ', b.value) FROM (SELECT record, value FROM redcap_data WHERE project_id = ".$pidsArray['HARMONIST']." AND field_name = 'concept_id') a JOIN (SELECT record, value FROM redcap_data where project_id = ".$pidsArray['HARMONIST']." and field_name = 'concept_title') b ON b.record=a.record ORDER BY a.value, b.value",
            'autocomplete' => '1',
            'label' => ""
        ),
        'sop_finalize_person' => array (
            'query' => "SELECT DISTINCT a.record, CONCAT(a.value, ' ', b.value) AS VALUE FROM redcap_data a LEFT JOIN redcap_data b on b.project_id = ".$pidsArray['PEOPLE']." and b.record = a.record and b.field_name = 'lastname' LEFT JOIN redcap_data c on c.project_id = ".$pidsArray['PEOPLE']." and c.record = a.record WHERE a.field_name = 'firstname' and a.project_id = ".$pidsArray['PEOPLE']." and ((c.field_name = 'harmonist_perms' AND c.value = '1') OR (c.field_name = 'harmonistadmin_y' AND c.value = '1')) ORDER BY a.value, b.value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'data_region' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".$pidsArray['REGIONS']." GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        )
    ),
    $pidsArray['SOPCOMMENTS']=>array(
        'response_person' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".$pidsArray['PEOPLE']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'response_region' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".$pidsArray['REGIONS']."  GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        )
    ),
    $pidsArray['PEOPLE']=>array(
        'person_region' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".$pidsArray['REGIONS']."  GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        )
    ),
    $pidsArray['DATAUPLOAD']=>array(
        'data_assoc_concept' => array (
            'query' => "SELECT a.record, CONCAT(a.value, ' | ', b.value) FROM (SELECT record, value FROM redcap_data WHERE project_id = ".$pidsArray['HARMONIST']." AND field_name = 'concept_id') a JOIN (SELECT record, value FROM redcap_data where project_id = ".$pidsArray['HARMONIST']." and field_name = 'concept_title') b ON b.record=a.record ORDER BY a.value, b.value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'data_assoc_request' => array (
            'query' => "SELECT a.record, CONCAT(a.value, ' | ', b.value) FROM (SELECT record, value FROM redcap_data WHERE project_id = ".$pidsArray['SOP']." AND field_name = 'record_id') a JOIN (SELECT record, value FROM redcap_data where project_id = ".$pidsArray['SOP']." and field_name = 'sop_name') b ON b.record=a.record ORDER BY a.value, b.value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'data_upload_person' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".$pidsArray['PEOPLE']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'data_upload_region' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".$pidsArray['REGIONS']."  GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'deletion_hubuser' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".$pidsArray['PEOPLE']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        )
    ),
    $pidsArray['DATADOWNLOAD']=>array(
        'downloader_assoc_concept' => array (
            'query' => "SELECT a.record, CONCAT(a.value, ' | ', b.value) FROM (SELECT record, value FROM redcap_data WHERE project_id = ".$pidsArray['HARMONIST']." AND field_name = 'concept_id') a JOIN (SELECT record, value FROM redcap_data where project_id = ".$pidsArray['HARMONIST']." and field_name = 'concept_title') b ON b.record=a.record ORDER BY a.value, b.value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'downloader_id' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".$pidsArray['PEOPLE']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'downloader_region' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".$pidsArray['REGIONS']."  GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'download_id' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'record_id', a.value, NULL)),    ' (',   max(if(a.field_name = 'responsecomplete_ts', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".$pidsArray['DATAUPLOAD']."  GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        )
    ),
    $pidsArray['DATAAVAILABILITY']=>array(
        'available_table' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'table_name', a.value, NULL)),    ' () ' ) as value  FROM redcap_data a  WHERE a.project_id=".$pidsArray['DATAMODEL']."  GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'available_variable' => array (
            'query' => "SELECT CONCAT(a.record, '|', b.instance), CONCAT(a.value, ' | ', b.value) FROM (SELECT record,value FROM redcap_data WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM redcap_data WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '0',
            'label' => ""
        )
    ),
    $pidsArray['DATATOOLMETRICS']=>array(
        'userregion_id' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".$pidsArray['REGIONS']."  GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        )
    ),
    $pidsArray['FILELIBRARY']=>array(
        'file_uploader' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".$pidsArray['PEOPLE']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '1',
            'label' => ""
        )
    ),
    $pidsArray['FILELIBRARYDOWN']=>array(
        'library_download_person' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'firstname', a.value, '')),    ' ',   max(if(a.field_name = 'lastname', a.value, '')),   ' | ',    max(if(a.field_name = 'email', a.value, ''))) as value  FROM redcap_data a  WHERE a.project_id=".$pidsArray['PEOPLE']." GROUP BY a.record ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'library_download_region' => array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".$pidsArray['REGIONS']."  GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        )
    ),
    $pidsArray['NEWITEMS']=>array(
        'news_person' =>  array (
            'query' => "SELECT DISTINCT a.record, CONCAT(a.value, ' ', b.value) AS  VALUE  FROM redcap_data a  LEFT JOIN redcap_data b on b.project_id = ".$pidsArray['PEOPLE']." and b.record = a.record and b.field_name = 'lastname'  LEFT JOIN redcap_data c on c.project_id = ".$pidsArray['PEOPLE']." and c.record = a.record  WHERE a.field_name = 'firstname' and a.project_id = ".$pidsArray['PEOPLE']." and ((c.field_name = 'harmonist_perms' AND c.value = '9') OR (c.field_name = 'harmonistadmin_y' AND c.value = '1'))  ORDER BY     a.value,      b.value",
            'autocomplete' => '0',
            'label' => ""
        )
    ),
    $pidsArray['EXTRAOUTPUTS']=>array(
        'lead_region' =>  array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'region_name', a.value, NULL)),    ' (',   max(if(a.field_name = 'region_code', a.value, NULL)),   ') ' ) as value  FROM redcap_data a  WHERE a.project_id=".$pidsArray['REGIONS']." GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        )
    ),
    $pidsArray['DATAMODELMETADATA']=>array(
        'index_tablename' =>  array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'table_name', a.value, NULL)),    '  ' ) as value  FROM redcap_data a  WHERE a.project_id=".$pidsArray['DATAMODEL']."  GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'patient_id_var' =>  array (
            'query' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM redcap_data WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM redcap_data WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '0',
            'label' => ""
        ),
        'default_group_var' =>  array (
            'query' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM redcap_data WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM redcap_data WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '0',
            'label' => ""
        ),
        'group_tablename' =>  array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'table_name', a.value, NULL)),    '  ' ) as value  FROM redcap_data a  WHERE a.project_id=".$pidsArray['DATAMODEL']."  GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'birthdate_var' =>  array (
            'query' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM redcap_data WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM redcap_data WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '0',
            'label' => ""
        ),
        'death_date_var' =>  array (
            'query' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM redcap_data WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM redcap_data WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '0',
            'label' => ""
        ),
        'age_date_var' =>  array (
            'query' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM redcap_data WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM redcap_data WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '0',
            'label' => ""
        ),
        'enrol_date_var' =>  array (
            'query' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM redcap_data WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM redcap_data WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '0',
            'label' => ""
        ),
        'height_table' =>  array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'table_name', a.value, NULL)),    '  ' ) as value  FROM redcap_data a  WHERE a.project_id=".$pidsArray['DATAMODEL']."  GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'height_var' =>  array (
            'query' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM redcap_data WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM redcap_data WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '0',
            'label' => ""
        ),
        'height_date' =>  array (
            'query' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM redcap_data WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM redcap_data WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '0',
            'label' => ""
        ),
        'height_units' =>  array (
            'query' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM redcap_data WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM redcap_data WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '0',
            'label' => ""
        )
    )
);

foreach ($projects_array_sql as $projectid=>$projects){
    foreach ($projects as $varid=>$options){
        foreach ($options as $optionid=>$value){
            if($optionid == 'query') {
                $module->query("UPDATE redcap_metadata SET element_enum = ? WHERE project_id = ? AND field_name=?",[$value,$projectid,$varid]);
            }
            if($optionid == 'autocomplete' && $value == '1'){
                $module->query("UPDATE redcap_metadata SET element_validation_type= ? WHERE project_id = ? AND field_name=?",["autocomplete",$projectid,$varid]);
            }
            if($optionid == 'label' && $value != "") {
                $module->query("UPDATE redcap_metadata SET element_label= ? WHERE project_id = ? AND field_name=?", [$value, $projectid, $varid]);
            }
        }
    }
}

echo json_encode(array(
        'status' =>'success'
    )
);
?>
