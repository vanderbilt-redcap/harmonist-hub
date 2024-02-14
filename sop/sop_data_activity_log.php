<?php
namespace Vanderbilt\HarmonistHubExternalModule;

$record = htmlentities($_REQUEST['record'],ENT_QUOTES);

### Fetch People and Region data for inclusion in upload/download details
$people = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', null, array('firstname','lastname','person_region'));

$peopleDetails = [];
foreach($people as $personDetails) {
	$peopleDetails[$personDetails["record_id"]] = $personDetails;
}

$regions = \REDCap::getData($pidsArray['REGIONS'], 'json-array', null, array('region_code'));

## Sort into list by region_code
ArrayFunctions::array_sort_by_column($regions, 'region_code');

$regionList = [];
if (!empty($regions)) {
	foreach ($regions as $region) {
        $regionList[$region["record_id"]] = $region["region_code"];
	}
}

## Pull ALL data from Data Upload
$dataUpload_sevenDaysYoung = \REDCap::getData($pidsArray['DATAUPLOAD'], 'json-array', null);

## Set up upload records array keyed by record_id
$uploadDetails = [];
foreach($dataUpload_sevenDaysYoung as $recordDetails) {
	$uploadDetails[$recordDetails["record_id"]] = $recordDetails;
}

## Pull ALL data from Data Download
$dataDownload_sevenDaysYoung = \REDCap::getData($pidsArray['DATADOWNLOAD'], 'json-array', null);

$downloadDetails = [];
## Fetch the upload details for each download record
foreach($dataDownload_sevenDaysYoung as $recordDetails) {
	$downloadDetails[$recordDetails["record_id"]] = $recordDetails;
}

$all_data_recent_activity = array_merge($dataUpload_sevenDaysYoung, $dataDownload_sevenDaysYoung);

## Sort into list by responsecomplete_ts
ArrayFunctions::array_sort_by_column($all_data_recent_activity, 'responsecomplete_ts',SORT_DESC);

$activityToPrint = [];

foreach($all_data_recent_activity as $recordDetails) {
    $activityToPrint[] = $recordDetails;

    ## Add additional deleted row to display this on activity log
    if($recordDetails['deleted_y'] == "1"){
        $deletedDataRow = [];
        foreach($recordDetails as $fieldName => $fieldValue) {
            $deletedDataRow[$fieldName] = $fieldValue;
        }
        $deletedDataRow["IS_DELETED_ROW"] = 1;
        
        $deletedDataRow["responsecomplete_ts"] = $recordDetails['deletion_ts'];
        $activityToPrint[] = $deletedDataRow;
    }
}

## Post page load data lookup details
$datareq_id = '';
$datareq_title = '';
if(array_key_exists('record', $_REQUEST) && $record != ''){
    $datareq_id = $record;
    $datareq_title = "<em>(for Data Request #".$record.")</em>";
}
?>
<script>
    //To filter the data
    $.fn.dataTable.ext.search.push(
        function( settings, data, dataIndex ) {
            var region = $('#selectRegion option:selected').text();
            var activity = $('#selectActivity').text().trim();
            var column_region = data[3];
            var column_activity = data[7];

            if(region != 'Select All' && column_region == region ){
                if(activity != 'Select All' && column_activity == activity){
                    return true;
                }else if(activity == 'Select All'){
                    return true;
                }
            }else if(region == 'Select All'){
                if(activity != 'Select All' && column_activity == activity){
                    return true;
                }else if(activity == 'Select All'){
                    return true;
                }
            }

            return false;
        }
    );

    function getQueryString() {
        var key = false, res = {}, itm = null;
        // get the query string without the ?
        var qs = location.search.substring(1);
        // check for the key as an argument
        if (arguments.length > 0 && arguments[0].length > 1)
            key = arguments[0];
        // make a regex pattern to grab key/value
        var pattern = /([^&=]+)=([^&]*)/g;
        // loop the items in the query string, either
        // find a match to the argument, or build an object
        // with key/value pairs
        while (itm = pattern.exec(qs)) {
            if (key !== false && decodeURIComponent(itm[1]) === key)
                return decodeURIComponent(itm[2]);
            else if (key === false)
                res[decodeURIComponent(itm[1])] = decodeURIComponent(itm[2]);
        }

        return key === false ? res : null;
    }

    $(document).ready(function() {
        Sortable.init();

        var person_name = <?=json_encode($person_name)?>;
        if(person_name != "" && person_name != null){
            $('#table_archive').dataTable( {"pageLength": 50,"order": [0, "desc"],"oSearch": {"sSearch": person_name}});
        }else{
            $('#table_archive').dataTable( {"pageLength": 50,"order": [0, "desc"]});
        }

        $('#table_archive_filter').appendTo( '#options_wrapper' );
        $('#table_archive_filter').attr( 'style','float: left;padding-left: 170px;padding-top: 5px;' );

        var table_sort = $('#table_archive').DataTable();
        table_sort.column(7).visible(false);
        table_sort.column(5).visible(false);

        var datared_id = <?=json_encode($datareq_id)?>;
        if(datared_id != "" && datared_id != null){
            table_sort.columns( 5 ).search( datared_id ).draw();
        }

        $('#selectActivity').click( function() {
            var table = $('#table_archive').DataTable();
            table.draw();
        } );

        //To change the text on select
        $(".dropdown-menu li").click(function(){
            var selText = $(this).html();
            $(this).parents('.dropdown').find('.dropdown-toggle').html(selText+" <input type='hidden' value='"+$(this).text()+"' id='publication_type'/><span class='caret' style='float: right;margin-top:8px'></span>");
            //when any of the filters is called upon change datatable data
            var table = $('#table_archive').DataTable();
            table.draw();
        });

        //when any of the filters is called upon change datatable data
        $('#selectRegion').change( function() {
            var table = $('#table_archive').DataTable();
            table.draw();
        } );

        $('#dataDownloadForm').submit(function () {
            $("#modal-data-download-confirmation").modal("hide");
            window.location.href = addDeleteCode($('#deleted_record').val());
            return false;
        });
        $('#dataTransferDelete').submit(function () {
            if($('#dataUpReason').val() == ""){
                $('#dataUploadError').text('Please provide a reason to continue.');
            }else{
                var refresh_url = window.location.href;
                if(window.location.href.match(/(&del=)([0-9a-zA-Z]{32})/)){
                    refresh_url = window.location.href.replace( /(&del=)([0-9a-zA-Z]{32})/, '' );
                }
                CallAJAXAndShowMessage("&deletion_rs="+$('#dataUpReason').val()+'&code='+$('#deleted_record').val(),<?=json_encode($module->getUrl('hub/aws/AWS_deleteFile.php').'&NOAUTH')?>,'D',refresh_url);
            }
            return false;
        });

        var url_parameter = getQueryString("del");
        if(url_parameter != null && url_parameter != ""){
            $('#deleted_record').val(url_parameter);
            $('#modal-data-transfer-delete').modal('show');
        }
    } );
</script>
<div class="container">
    <div class="backTo">
        <?php
        if(array_key_exists('message', $_REQUEST) && $_REQUEST['message'] != ''){
            if($_REQUEST['message'] == 'D') {
                ?><div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;" id="succMsgContainer">Data upload deleted successfully.</div><?php
            }
        }

        if($_REQUEST['type'] == "upload") {
            ?><a href="<?=$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=upd'?>">< Back to Submit Data</a><?php
        }else if($datareq_id != "") {
            ?><a href="<?=$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=sop&record='.$module->escape($datareq_id).'&type=s'?>">< Back to Data Request</a><?php
        }else{
            ?><a href="<?=$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=dat'?>">< Back to Data</a><?php
        }
        ?>
    </div>
    <h3>Data Activity Log <?=$datareq_title?></h3>
    <p class="hub-title"><?=filter_tags($settings['hub_data_activity_text'])?></p>
    <br>
    <div class="optionSelect conceptSheets_optionMenu">
        <div id="options_wrapper"></div>
        <div style="float:right">
            <div style="float:left;padding-left:30px;margin-top: 8px;">
                Region:
            </div>
            <div style="float:left;padding-left:10px">
                <select class="form-control" name="selectRegion" id="selectRegion">
                    <option value="">Select All</option>
                    <?php
                    foreach($regionList as $record => $regionCode) {
                        if($record == $current_user['person_region'] && $_REQUEST['type'] != ""){
                            echo "<option value='".$record."' selected>".$regionCode."</option>";
                        }else{
                            echo "<option value='".$record."'>".$regionCode."</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <div style="float:left;padding-left:30px;margin-top: 8px;">
                Activity:
            </div>
            <div style="float:left;padding-left:10px">
                <ul class="nav navbar-nav navbar-right" style="padding-right: 40px;">
                    <li class="menu-item dropdown">
                        <a href="#" data-toggle="dropdown" class="dropdown-toggle form-control output_select btn-group" id="selectActivity">Select All<span class="caret" style="float: right;margin-top:8px;"></span></a>
                        <ul class="dropdown-menu output-dropdown-menu" >
                            <li><a href="#" tabindex="1">Select All</a></li>
                            <li><a href="#" tabindex="1"><i class="fa fa-fw fa-arrow-up text-success" aria-hidden="true"></i> upload</a></li>
                            <li><a href="#" tabindex="1"><i class="fa fa-fw fa-arrow-down text-info" aria-hidden="true"></i> download</a></li>
                            <li><a href="#" tabindex="1"><i class="text-error fa fa-fw fa-close" aria-hidden="true"></i> delete</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
<div class="container">
    <div class="panel panel-default-archive">
        <div class="table-responsive table-archive" style="overflow-x: hidden;">
            <table class="table table_requests sortable-theme-bootstrap" data-sortable id="table_archive">
                <?php
                
                ### Data displayed
                if(!empty($activityToPrint)) {?>
                    <colgroup>
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                    </colgroup>
                    <thead>
                    <tr>
                        <th class="sorted_class" data-sorted="true" data-sorted-direction="descending">Date</th>
                        <th class="sorted_class">Activity</th>
                        <th class="sorted_class">Person</th>
                        <th class="sorted_class"><span style="display:block">Data</span>Region</th>
                        <th class="sorted_class">MR</th>
                        <th class="sorted_class">Data Request ID</th>
                        <th class="sorted_class">Filename</th>
                        <th class="sorted_class">Activity hidden</th>
                        <th data-sortable="false">PDF</th>
                        <th data-sortable="false"><i class="fa fa-fw fa-cog"></i></th>
                        <?php if($isAdmin){
                           ?><th data-sortable="false">REDCap</th><?php
                        }?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($activityToPrint as $recent_activity) {
                        $comment_time ="";
                        if(!empty($recent_activity['responsecomplete_ts'])){
                            $dateComment = new \DateTime($recent_activity['responsecomplete_ts']);
                            $dateComment->modify("+1 hours");
                            $comment_time = $dateComment->format("Y-m-d H:i:s");
                        }
	
						$file = "";
						$buttons = "";
                        $name = "";
                        if(array_key_exists("downloader_id", $recent_activity)) {
                            $redcapPid = $pidsArray['DATADOWNLOAD'];
	                        $person = $peopleDetails[$recent_activity["downloader_id"]];
							$region_code = $regionList[$recent_activity["downloader_region"]];
							$assoc_concept = \Vanderbilt\HarmonistHubExternalModule\getReqAssocConceptLink($module, $pidsArray, $recent_activity['downloader_assoc_concept']);
	
                            $activityColumn = '<i class="fa fa-fw fa-arrow-down text-info" aria-hidden="true"></i> download';
							$thisUpload = $uploadDetails[$recent_activity['download_id']];
							$data_request = $thisUpload["data_assoc_request"];
                            $filenameColumn = $recent_activity['download_files'];
                            $activityHiddenColumn = "download";
						}
                        else {
							$redcapPid = $pidsArray['DATAUPLOAD'];
	                        $person = $peopleDetails[$recent_activity["data_upload_person"]];
							$region_code = $regionList[$recent_activity["data_upload_region"]];
							$assoc_concept = \Vanderbilt\HarmonistHubExternalModule\getReqAssocConceptLink($module, $pidsArray, $recent_activity['data_assoc_concept']);
	      
							$activity = '<i class="fa fa-fw fa-arrow-up text-success" aria-hidden="true"></i> upload ';
							if($current_user['person_region'] == $recent_activity['data_upload_region'] || $isAdmin) {
								$file = \Vanderbilt\HarmonistHubExternalModule\getFileLink($module, $pidsArray['PROJECTS'], $recent_activity['data_upload_pdf'], '1', '', $secret_key, $secret_iv, $current_user['record_id'], "");
							}
							if($recent_activity['deleted_y'] != "1" && ($recent_activity['data_upload_person'] == $current_user['record_id'] || $isAdmin)){
								$crypt = \Vanderbilt\HarmonistHubExternalModule\getCrypt("&id=".$recent_activity['record_id']."&idu=".$current_user['record_id'],'e',$secret_key,$secret_iv);
								$buttons = "<a href='#' onclick='$(\"#deleted_record\").val(\"".$crypt."\");$(\"#modal-data-download-confirmation\").modal(\"show\");' class='fa fa-trash' style='color: #000;cursor:pointer;text-decoration: none;' title='delete'></a>";
							}
                            
                            $activityColumn = filter_tags($activity);
							$data_request = $recent_activity["data_assoc_request"];
							$filenameColumn = $recent_activity['data_upload_zip'];
							$activityHiddenColumn = "upload";
						}
                        
                        if(array_key_exists("IS_DELETED_ROW", $recent_activity)) {
                            $activityHiddenColumn = "delete";
                            $activityColumn = "<i class='fa fa-fw fa-close text-error'></i> delete";
                            $buttons = "";
							if ($recent_activity['deletion_type'][0] == '1') {
								$name = filter_tags("<em>Automatic</em>");
                                $person = false;
							} else if ($recent_activity['deletion_type'][0] == '2') {
								$person = $peopleDetails[$recent_activity["deletion_hubuser"]];
                            }
                        }
                        
                        ## Not sure what to do if person is somehow empty
                        if($person !== false && $person !== null) {
						    $region_code_person = $person[$recent_activity["person_region"]];
    						$name = trim($person['firstname'] . ' ' . $person['lastname'])." (".$region_code_person.")";
						}
	
						$adminLink = "";
						if($isAdmin){
							$gotoredcap = APP_PATH_WEBROOT_ALL . "DataEntry/record_home.php?pid=" . $module->escape($redcapPid) . "&arm=1&id=" . $module->escape($recent_activity['record_id']);
							$adminLink = '<td style="text-align: center;"><a href="' . $gotoredcap . '" target="_blank"> <img src="'.$module->getUrl('img/REDCap_R_logo_transparent.png').'" style="width: 18px;" alt="REDCap Logo"></a></td>';
						}
      
						echo '<tr><td width="150px">'.$module->escape($comment_time).'</td>';
						echo '<td width="105px">'.$activityColumn.'</td>';
						echo '<td width="220px">'.$module->escape($name).'</td>';
						echo '<td width="20px">'.$module->escape($region_code).'</td>';
						echo '<td width="80px">'.$assoc_concept.'</td>';
						echo '<td width="80px">'.$module->escape($data_request).'</td>';
						echo '<td width="220px">'.$module->escape($filenameColumn).'</td>';
						echo '<td>'.$module->escape($activityHiddenColumn).'</td>';
						echo '<td width="50px"> '.$module->escape($file).'</td>';
						echo '<td width="50px"> '.filter_tags($buttons).'</td>';
						echo $adminLink;
      
						echo '</tr>';
                    }
                    ?>
                    </tbody>
                    <?php
                }
                ?>
            </table>
        </div>
    </div>
</div>

<?php ### MODALS ?>
<div class="modal fade" id="modal-data-download-confirmation" tabindex="-1" role="dialog" aria-labelledby="Codes">
    <form class="form-horizontal" action="" method="post" id='dataDownloadForm'>
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Delete Data</h4>
                </div>
                <div class="modal-body">
                    <span>Are you sure you want to delete this data set?</span>
                    <br>
                    <span style="color:red;">You will need to log in to Vanderbilt REDCap.</span>
                </div>
                <input type="hidden" id="assoc_concept" name="assoc_concept">
                <input type="hidden" id="user" name="user">
                <div class="modal-footer">
                    <button type="submit" form="dataDownloadForm" class="btn btn-default btn-success" id='btnModalRescheduleForm'>Continue</button>
                    <a href="#" class="btn btn-default btn-cancel" data-dismiss="modal">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>
<div class="modal fade" id="modal-data-transfer-delete" tabindex="-1" role="dialog" aria-labelledby="Codes">
    <form class="form-horizontal" action="" method="post" id='dataTransferDelete'>
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close" onclick="javascript:if(window.location.href.match(/(&del=)([0-9a-zA-Z]{32})/)){window.location.href = window.location.href.replace( /(&del=)([0-9a-zA-Z]{32})/, '' );}"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Delete Data Upload</h4>
                </div>
                <div class="modal-body">
                    <span>Provide a reason for deleting this data upload:</span>
                    <br>
                    <br>
                    <textarea name="dataUpReason" id="dataUpReason" style="width: 100%;"></textarea>
                    <span id="dataUploadError" class="text-error"></span>
                </div>
                <input type="hidden" id="deleted_record" name="deleted_record">
                <div class="modal-footer">
                    <a class="btn btn-default btn-cancel" data-dismiss="modal" onclick="javascript:if(window.location.href.match(/(&del=)([0-9a-zA-Z]{32})/)){window.location.href = window.location.href.replace( /(&del=)([0-9a-zA-Z]{32})/, '' );}">Cancel</a>
                    <button type="submit" form="dataTransferDelete" class="btn btn-default btn-danger" id='btnModalRescheduleForm'>Delete</button>
                </div>
            </div>
        </div>
    </form>
</div>
</div>
