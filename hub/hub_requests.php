<?php
namespace Vanderbilt\HarmonistHubExternalModule;

$request_type_label = $module->getChoiceLabels('request_type', $pidsArray['RMANAGER']);

$header = getRequestHeader($hubData,$settings['vote_grid'],'0');

$title = "Requests";
$link_all_requests = '';
if($_REQUEST['type'] != ''){
    $title = $title." for ".$request_type_label[$_REQUEST['type']];
    $link_all_requests = '<a href="'.$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=hub'.'">View All Requests</a> | ';
}

$requests = $hubData->getAllRequests();

$commentDetails = $hubData->getCommentDetails();

$types_of_requests_data = [];
foreach ($requests as $req){
    if (($_REQUEST['type'] != "" && $req['request_type'] == $_REQUEST['type']) || $_REQUEST['type'] == "") {
        if (!hideRequestForNonVoters($settings, $req, $person_region)) {
            if (showClosedRequest($settings, $req, $current_user['person_region'])) {
                //COMPLETED REQUESTS
                $types_of_requests_data['completed'] .= getRequestHTML($module, $hubData, $pidsArray, $req, $commentsDetails[$req['request_id']], $request_type_label, 1, $settings['vote_visibility'], $settings['vote_grid'], '');
            } else if ($current_user['pendingpanel_y___1'] == '1' && showPendingRequest($commentsDetails[$req['request_id']], $current_user['person_region'], $req) && $current_user['pendingpanel_y'][0] == '1' && $req['region_response_status'][$current_user['person_region']] != '2') {
                //PENDING REQUESTS
                $types_of_requests_data['pending'] .= getRequestHTML($module, $hubData, $pidsArray, $req, $commentsDetails[$req['request_id']], $request_type_label, 0, $settings['vote_visibility'], $settings['vote_grid'], '');
            } else if (showOpenRequest($req, $current_user['person_region']) && $req['region_response_status'][$current_user['person_region']] != '2') {
                //OPEN REQUESTS
                $types_of_requests_data['open'] .= getRequestHTML($module, $hubData, $pidsArray, $req, $commentsDetails[$req['request_id']], $request_type_label, 0, $settings['vote_visibility'], $settings['vote_grid'], '');
            }
        }
    }
}
?>

<style>
    @media all and (-ms-high-contrast: none), (-ms-high-contrast: active) {
        .fa {
            padding-top: 3px !important;
        }
    }
</style>

<div class="container">
    <h3><?=$title;?></h3>
    <p class="hub-title"><?=$settings['hub_req_text']?></p>
    <div class="pull-right">
        <p><?php echo $link_all_requests; ?><a href="<?=$module->escape(APP_PATH_WEBROOT_FULL."surveys/?s=".$pidsArray['REQUESTLINK'])?>" target="_blank">Create New Request</a> | <a href="<?=$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=mra&type=r'?>">View Archived Requests</a></p>
    </div>
    <ul class="list-inline">
        <li><span class="label label-default_light" title="Not Started"><i class="fa fa-times text-default_light" aria-hidden="true"></i></span> <a href="#" data-toggle="tooltip" title="No regional activity on this request." data-placement="top" class="custom-tooltip" style="vertical-align: -2px;cursor:default;">Not started</a></li>
        <li><span class="label label-warning" title="In Progress"><i class="fa fa-wrench" aria-hidden="true"></i></span> <a href="#" data-toggle="tooltip" title="Your region has posted comments." data-placement="top" class="custom-tooltip" style="vertical-align: -2px;cursor:default;">In Progress</a></li>

        <?php if($settings['vote_visibility'] == "" || $settings['vote_visibility'] =="1"){?>
            <li><span class="label label-info" title="Complete"><i class="fa fa-check" aria-hidden="true"></i></span> <a href="#" data-toggle="tooltip" title="Your region voted on this request." data-placement="top" class="custom-tooltip" style="vertical-align: -2px;cursor:default;">Complete</a></li>
        <?php } else {?>
            <li><span class="label label-approved" title="Approved"><i class="fa fa-check" aria-hidden="true"></i></span> <a href="#" data-toggle="tooltip" title="Your region approved this request." data-placement="top" class="custom-tooltip" style="vertical-align: -2px;cursor:default;">Approved</a></li>
            <li><span class="label label-notapproved" title="Not Approved"><i class="fa fa-times" aria-hidden="true"></i></span> <a href="#" data-toggle="tooltip" title="Your region did not approve this request." data-placement="top" class="custom-tooltip" style="vertical-align: -2px;cursor:default;">Not Approved</a></li>
            <li><span class="label label-default" title="Abstained"><i class="fa fa-ban" aria-hidden="true"></i></span> <a href="#" data-toggle="tooltip" title="Your region abstained from voting." data-placement="top" class="custom-tooltip" style="vertical-align: -2px;cursor:default;">Abstained</a></li>
            <?php if($settings['vote_visibility'] =="3"){ ?>
                <li><span class="label label-default" title="Mixed"><i class="fa fa-clone" aria-hidden="true"></i></span> <a href="#" data-toggle="tooltip" title="Your region has different types of vote." data-placement="top" class="custom-tooltip" style="vertical-align: -2px;cursor:default;">Mixed</a></li>
            <?php } ?>
        <?php } ?>
    </ul>
</div>

<div class="container">
    <?php
    foreach (['open','pending','completed'] as $type){
        if(!empty($types_of_requests_data[$type]) && $type != "open" || $type == "open"){
            $style = 'hub_requests_'.$type;
            ?>
            <div class="panel panel-default">
                <div class="panel-heading <?=$style;?>">
                    <h3 class="panel-title">
                        <?=ucfirst($type)?> Requests
                    </h3>
                </div>

                <div class="table-responsive">
                    <table class="table table_requests sortable-theme-bootstrap" data-sortable>
                        <table class="table table_requests sortable-theme-bootstrap" data-sortable>
                            <?php
                            if($types_of_requests_data[$type] == ""){
                                ?><td>No requests available.</td><?php
                            }else {
                                echo $header;
                                ?>
                                <tbody><?php
                                echo $types_of_requests_data[$type];
                                ?></tbody><?php
                            }
                            ?>
                        </table>
                    </table>
                </div>
            </div>
            <?php
        }
    }
    ?>
</div>

<!-- MODALS -->
<div class="modal fade" id="hub_view_votes" tabindex="-1" role="dialog" aria-labelledby="Codes">
    <div class="modal-dialog" role="document" style="width: 800px">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">All Votes</h4>
            </div>
            <div class="modal-body">
                <div id="allvotes"> </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="hub_view_mixed_votes" tabindex="-1" role="dialog" aria-labelledby="Codes">
    <div class="modal-dialog" role="document" style="width: 800px">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Vote Details</h4>
            </div>
            <div class="modal-body">
                <div id="mixedvotes"> </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>