<?php
namespace Vanderbilt\HarmonistHubExternalModule;

$record = htmlentities($_REQUEST['record'],ENT_QUOTES);

$dataUpload_sevenDaysYoung = \REDCap::getData($pidsArray['DATAUPLOAD'], 'json-array', null);
ArrayFunctions::array_sort_by_column($dataUpload_sevenDaysYoung, 'responsecomplete_ts',SORT_DESC);

$dataDownload_sevenDaysYoung = \REDCap::getData($pidsArray['DATADOWNLOAD'], 'json-array', null);
ArrayFunctions::array_sort_by_column($dataDownload_sevenDaysYoung, 'responsecomplete_ts',SORT_DESC);

$all_data_recent_activity = array_merge($dataUpload_sevenDaysYoung, $dataDownload_sevenDaysYoung);

ArrayFunctions::array_sort_by_column($all_data_recent_activity, 'responsecomplete_ts',SORT_DESC);

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
            $('#dataDownloadForm').attr('action', $('#dataDownloadForm').attr('action')+"&del="+$('#deleted_record').val());
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
            ?><a href="<?=$module->getUrl('index.php').'&NOAUTH&option=upd'?>">< Back to Submit Data</a><?php
        }else if($datareq_id != "") {
            ?><a href="<?=$module->getUrl('index.php').'&NOAUTH&option=sop&record='.$module->escape($datareq_id).'&type=s'?>">< Back to Data Request</a><?php
        }else{
            ?><a href="<?=$module->getUrl('index.php').'&NOAUTH&option=dat'?>">< Back to Data</a><?php
        }
        ?>
    </div>
    <h3>Data Activity Log <?=$datareq_title?></h3>
    <p class="hub-title"><?=filter_tags($settings['hub_data_activity_text'])?></p>
    <br>
    <div class="optionSelect">
        <div style="margin: 0 auto;width: 20%;">
            <?php if($settings['data_log_history_file'] != ""){ ?>
                <a href="<?=getFileLink($module,$pidsArray['SETTINGS'], $settings['data_log_history_file'], 'link', '1', $secret_key, $secret_iv, $current_user['record_id'], ""); ?>">
                     <button type="submit" class="btn btn-primary">Download Complete Log</button>
                </a>
            <?php } ?>
        </div>
    </div>
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
                    $regions = $module->escape(\REDCap::getData($pidsArray['REGIONS'], 'json-array'));
                    ArrayFunctions::array_sort_by_column($regions, 'region_code');
                    if (!empty($regions)) {
                        foreach ($regions as $region){
                            if($region['record_id'] == $current_user['person_region'] && $_REQUEST['type'] != ""){
                                echo "<option value='".$region['record_id']."' selected>".$region['region_code']."</option>";
                            }else{
                                echo "<option value='".$region['record_id']."'>".$region['region_code']."</option>";
                            }

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
                if(!empty($all_data_recent_activity)) {?>
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
                    $number_entries = empty($settings['datalog_numentries'])? 50 : $settings['datalog_numentries'];
                    $countEntries = 0;
                    foreach ($all_data_recent_activity as $recent_activity) {
                        if($countEntries < $number_entries) {
                            $comment_time = "";
                            if (!empty($recent_activity['responsecomplete_ts'])) {
                                $dateComment = new \DateTime($recent_activity['responsecomplete_ts']);
                                $dateComment->modify("+1 hours");
                                $comment_time = $dateComment->format("Y-m-d H:i:s");
                            }

                            if ($recent_activity['download_id'] != "") {
                                #DOWNLOADS
                                $people = \REDCap::getData(
                                    $pidsArray['PEOPLE'],
                                    'json-array',
                                    array('record_id' => $recent_activity['downloader_id']),
                                    array('firstname', 'lastname', 'person_region')
                                )[0];
                                $region_code_person = \REDCap::getData(
                                    $pidsArray['REGIONS'],
                                    'json-array',
                                    array('record_id' => $people['person_region']),
                                    array('region_code')
                                )[0]['region_code'];

                                $name = trim(
                                        $people['firstname'] . ' ' . $people['lastname']
                                    ) . " (" . $region_code_person . ")";

                                $region_code = \REDCap::getData(
                                    $pidsArray['REGIONS'],
                                    'json-array',
                                    array('record_id' => $recent_activity['downloader_region']),
                                    array('region_code')
                                )[0]['region_code'];

                                $assoc_concept = getReqAssocConceptLink(
                                    $module,
                                    $pidsArray,
                                    $recent_activity['downloader_assoc_concept']
                                );

                                $data_request = \REDCap::getData(
                                    $pidsArray['DATAUPLOAD'],
                                    'json-array',
                                    array('record_id' => $recent_activity['download_id']),
                                    array('data_assoc_request')
                                )[0]['data_assoc_request'];

                                $icon = '<i class="fa fa-fw fa-arrow-down text-info" aria-hidden="true"></i>';

                                echo '<tr><td width="150px">' . $module->escape($comment_time) . '</td>' .
                                    '<td width="105px"><i class="fa fa-fw fa-arrow-down text-info" aria-hidden="true"></i> download</td>' .
                                    '<td width="220px">' . $module->escape($name) . '</td>' .
                                    '<td width="20px">' . $module->escape($region_code) . '</td>' .
                                    '<td width="80px">' . $assoc_concept . '</td>' .
                                    '<td width="80px">' . $module->escape($data_request) . '</td>' .
                                    '<td width="220px"> ' . $module->escape($recent_activity['download_files']) . '</td>' .
                                    '<td>download</td>' .
                                    '<td width="50px"> </td>' .
                                    '<td width="50px"> </td>';
                                if ($isAdmin) {
                                    $gotoredcap = APP_PATH_WEBROOT_ALL . "DataEntry/record_home.php?pid=" . $pidsArray['DATADOWNLOAD'] . "&arm=1&id=" . $recent_activity['record_id'];
                                    echo '<td style="text-align: center;"><a href="' . $gotoredcap . '" target="_blank"> <img src="' . $module->getUrl(
                                            'img/REDCap_R_logo_transparent.png'
                                        ) . '" style="width: 18px;" alt="REDCap Logo"></a></td>';
                                }
                                echo '</tr>';
                                $countEntries++;
                            } else {
                                #UPLOADS
                                $people = \REDCap::getData(
                                    $pidsArray['PEOPLE'],
                                    'json-array',
                                    array('record_id' => $recent_activity['data_upload_person']),
                                    array('firstname', 'lastname', 'person_region')
                                )[0];
                                $region_code_person = \REDCap::getData(
                                    $pidsArray['REGIONS'],
                                    'json-array',
                                    array('record_id' => $people['person_region']),
                                    array('region_code')
                                )[0]['region_code'];

                                $name = trim(
                                        $people['firstname'] . ' ' . $people['lastname']
                                    ) . " (" . $region_code_person . ")";

                                $region_code = \REDCap::getData(
                                    $pidsArray['REGIONS'],
                                    'json-array',
                                    array('record_id' => $recent_activity['data_upload_region']),
                                    array('region_code')
                                )[0]['region_code'];

                                $assoc_concept = getReqAssocConceptLink(
                                    $module,
                                    $pidsArray,
                                    $recent_activity['data_assoc_concept']
                                );

                                $file = "";
                                $buttons = "";
                                $activity_hidden = "upload";
                                $activity = '<i class="fa fa-fw fa-arrow-up text-success" aria-hidden="true"></i> upload ';
                                if ($current_user['person_region'] == $recent_activity['data_upload_region'] || $isAdmin) {
                                    $file = getFileLink(
                                        $module,
                                        $pidsArray['PROJECTS'],
                                        $recent_activity['data_upload_pdf'],
                                        '1',
                                        '',
                                        $secret_key,
                                        $secret_iv,
                                        $current_user['record_id'],
                                        ""
                                    );
                                }
                                if ($recent_activity['deleted_y'] != "1" && ($recent_activity['data_upload_person'] == $current_user['record_id'] || $isAdmin)) {
                                    $crypt = getCrypt(
                                        "&id=" . $recent_activity['record_id'] . "&idu=" . $current_user['record_id'],
                                        'e',
                                        $secret_key,
                                        $secret_iv
                                    );
                                    $buttons = "<a href='#' onclick='$(\"#deleted_record\").val(\"" . $crypt . "\");$(\"#file_name\").val(\"" . $module->escape($recent_activity['data_upload_zip']) . "\");$(\"#modal-data-download-confirmation\").modal(\"show\");' class='fa fa-trash' style='color: #000;cursor:pointer;text-decoration: none;' title='delete'></a>";
                                }

                                echo '<tr><td width="150px">' . $module->escape($comment_time) . '</td>' .
                                    '<td width="105px">' . filter_tags($activity) . '</td>' .
                                    '<td width="220px">' . $module->escape($name) . '</td>' .
                                    '<td width="20px">' . $module->escape($region_code) . '</td>' .
                                    '<td width="80px">' . $assoc_concept . '</td>' .
                                    '<td width="80px">' . $module->escape(
                                        $recent_activity['data_assoc_request']
                                    ) . '</td>' .
                                    '<td width="220px">' . $module->escape($recent_activity['data_upload_zip']) . '</td>' .
                                    '<td>' . $module->escape($activity_hidden) . '</td>' .
                                    '<td width="50px"> ' . $file . '</td>' .
                                    '<td width="50px"> ' . $buttons . '</td>';

                                if ($isAdmin) {
                                    $gotoredcap = APP_PATH_WEBROOT_ALL . "DataEntry/record_home.php?pid=" . $module->escape(
                                            $pidsArray['DATAUPLOAD']
                                        ) . "&arm=1&id=" . $module->escape($recent_activity['record_id']);
                                    echo '<td style="text-align: center;"><a href="' . $gotoredcap . '" target="_blank"> <img src="' . $module->getUrl(
                                            'img/REDCap_R_logo_transparent.png'
                                        ) . '" style="width: 18px;" alt="REDCap Logo"></a></td>';
                                }
                                echo '</tr>';
                                $countEntries++;
                                if ($recent_activity['deleted_y'] == "1") {
                                    $activity_hidden = "delete";
                                    $activity = "<i class='fa fa-fw fa-close text-error'></i> delete";
                                    if ($recent_activity['deletion_type'][0] == '1') {
                                        $name = filter_tags("<em>Automatic</em>");
                                    } else {
                                        if ($recent_activity['deletion_type'][0] == '2') {
                                            $peopleDelete = \REDCap::getData(
                                                $pidsArray['PEOPLE'],
                                                'json-array',
                                                array('record_id' => $recent_activity['deletion_hubuser']),
                                                array('firstname', 'lastname', 'person_region')
                                            )[0];
                                            $region_code_person = \REDCap::getData(
                                                $pidsArray['REGIONS'],
                                                'json-array',
                                                array('record_id' => $peopleDelete['person_region']),
                                                array('region_code')
                                            )[0]['region_code'];

                                            $name = $module->escape(
                                                trim(
                                                    $peopleDelete['firstname'] . ' ' . $peopleDelete['lastname']
                                                ) . " (" . $region_code_person . ")"
                                            );
                                        }
                                    }

                                    $comment_time = "";
                                    if (!empty($recent_activity['deletion_ts'])) {
                                        $dateComment = new \DateTime($recent_activity['deletion_ts']);
                                        $dateComment->modify("+1 hours");
                                        $comment_time = $dateComment->format("Y-m-d H:i:s");
                                    }

                                    echo '<tr><td width="150px">' . $module->escape($comment_time) . '</td>' .
                                        '<td width="105px">' . filter_tags($activity) . '</td>' .
                                        '<td width="220px">' . filter_tags($name) . '</td>' .
                                        '<td width="20px">' . $module->escape($region_code) . '</td>' .
                                        '<td width="80px">' . $assoc_concept . '</td>' .
                                        '<td width="80px">' . $module->escape(
                                            $recent_activity['data_assoc_request']
                                        ) . '</td>' .
                                        '<td width="220px">' . $module->escape(
                                            $recent_activity['data_upload_zip']
                                        ) . '</td>' .
                                        '<td>' . $module->escape($activity_hidden) . '</td>' .
                                        '<td width="50px"> ' . $file . '</td>' .
                                        '<td width="50px"></td>';

                                    if ($isAdmin) {
                                        $gotoredcap = APP_PATH_WEBROOT_ALL . "DataEntry/record_home.php?pid=" . $pidsArray['DATAUPLOAD'] . "&arm=1&id=" . $recent_activity['record_id'];
                                        echo '<td style="text-align: center;"><a href="' . $gotoredcap . '" target="_blank"> <img src="' . $module->getUrl(
                                                'img/REDCap_R_logo_transparent.png'
                                            ) . '" style="width: 18px;" alt="REDCap Logo"></a></td>';
                                    }
                                    echo '</tr>';
                                    $countEntries++;
                                }
                            }
                        }
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
<?php
$deletedUrl = preg_replace('/pid=(\d+)/', "pid=".$pidsArray['DATADOWNLOADUSERS'],$module->getUrl('index.php').'&option=lge');
?>
<div class="modal fade" id="modal-data-download-confirmation" tabindex="-1" role="dialog" aria-labelledby="Codes">
    <form class="form-horizontal" action="<?=$deletedUrl?>" method="post" id='dataDownloadForm'>
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Delete Data</h4>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this data set?</p>
                    <p style="color:red">You will need to log in to Vanderbilt REDCap.</p>
                </div>
                <input type="hidden" id="file_name" name="file_name">
                <input type="hidden" id="deleted_record" name="deleted_record">
                <div class="modal-footer">
                    <button type="submit" form="dataDownloadForm" class="btn btn-default btn-success" id='btnModalRescheduleForm'>Continue</button>
                    <a href="#" class="btn btn-default btn-cancel" data-dismiss="modal">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>
</div>
