<?php
namespace Vanderbilt\HarmonistHubExternalModule;

global $date;
$recordId = htmlentities($_REQUEST['record'], ENT_QUOTES);
$concept = $module->getConceptModel()->fetchConcept($recordId);
$writingGroupMember = new WritingGroupModel($module, $pid, $module->getConceptModel()->getConceptData(),$current_user['person_region']);
$writingGroupMemberList = $writingGroupMember->fecthAllWritingGroup();

$date = new \DateTime();
$export_name = "concepts_".$date->format('Y-m-d H:i:s');
?>
<script language="JavaScript">
    //To filter the data
    $.fn.dataTable.ext.search.push(
        function( settings, data, dataIndex ) {
            var wg = $('#selectWorkingGroup').val();
            var tags = $('#selectTags').val();
            var column_tags = data[0];
            var column_wg = data[1];
            var column_wg2 = data[2];

            if((wg != '' && (column_wg == wg || column_wg2 == wg)) || wg == ''){
                if(tags == ''){
                    return true;
                }else if((tags != '')){
                    var tags_col = column_tags.split(",")
                    for (var row in tags_col){
                        if(tags_col[row] == tags){
                            return true;
                        }
                    }
                }
            }

            return false;
        }
    );
    $(document).ready(function() {
        Sortable.init();
        //double pagination (top & bottom)
        var table = $('#sortable_table').DataTable({"pageLength": 50,dom: "<'row'<'col-sm-3'l><'col-sm-4'f><'col-sm-5'p>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-5'i><'col-sm-7'p>>", "order": [0, "desc"]});
        var docname = <?=json_encode($export_name)?>;
        new $.fn.dataTable.Buttons( table, {
            "buttons": [
                {
                    extend: 'excel',
                    text: '<i class="fa fa-file-excel-o"></i> Excel',
                    exportOptions: {
                        columns: [0,5,6]
                    },
                    title: docname,
                    customize: function(xlsx){
                        var sheet = xlsx.xl.worksheets['sheet1.xml'];
                        var numrows = 1;
                        var clR = $('row', sheet);

                        //update Row
                        clR.each(function () {
                            var attr = $(this).attr('r');
                            var ind = parseInt(attr);
                            ind = ind + numrows;
                            $(this).attr("r", ind);
                        });

                        // Create row before data
                        $('row c ', sheet).each(function (index) {
                            var attr = $(this).attr('r');

                            var pre = attr.substring(0, 1);
                            var ind = parseInt(attr.substring(1, attr.length));
                            ind = ind + numrows;
                            $(this).attr("r", pre + ind);
                        });

                        function Addrow(index, data) {
                            var row = sheet.createElement('row');
                            row.setAttribute("r", index);
                            for (i = 0; i < data.length; i++) {
                                var key = data[i].key;
                                var value = data[i].value;

                                var c  = sheet.createElement('c');
                                c.setAttribute("t", "inlineStr");
                                c.setAttribute("s", "2");
                                c.setAttribute("r", key + index);

                                var is = sheet.createElement('is');
                                var t = sheet.createElement('t');
                                var text = sheet.createTextNode(value)

                                t.appendChild(text);
                                is.appendChild(t);
                                c.appendChild(is);

                                row.appendChild(c);
                            }

                            return row;
                        }

                        var text_selection = "";
                        if($('#concept_active').val() == "true"){
                            text_selection = "Active";
                        }else{
                            text_selection = "All";
                        }
                        if($('#selectWorkingGroup option:selected').text() != ""){
                            text_selection += " for "+$('#selectWorkingGroup option:selected').text();
                        }
                        var r1 = Addrow(1, [{ key: 'A', value: text_selection }, { key: 'B', value: '' }]);
                        var sheetData = sheet.getElementsByTagName('sheetData')[0];
                        sheetData.insertBefore(r1,sheetData.childNodes[0]);
                    }
                },
                {
                    extend: 'pdf',
                    text: '<i class="fa fa-file-pdf-o"></i> PDF',
                    exportOptions: {
                        columns: [0,5,6]
                    },
                    title: docname,
                    customize: function (doc) {
                        //Remove the title created by datatTables
                        doc.content.splice(0,1);
                        //Create a date string that we use in the footer. Format is dd-mm-yyyy
                        var now = new Date();
                        var jsDate = now.getDate()+'-'+(now.getMonth()+1)+'-'+now.getFullYear();
                        // Logo converted to base64
                        var logo = <?=json_encode($img)?>;
                        // Set page margins [left,top,right,bottom] or [horizontal,vertical]
                        // or one number for equal spread
                        doc.pageMargins = [20,100,20,30];
                        doc.defaultStyle.fontSize = 10;
                        doc.styles.tableHeader.fontSize = 10;

                        var text_selection = "";
                        if($('#concept_active').val() == "true"){
                            text_selection = "Active";
                        }else{
                            text_selection = "All";
                        }
                        if($('#selectWorkingGroup option:selected').text() != ""){
                            text_selection += " for "+$('#selectWorkingGroup option:selected').text();
                        }

                        // Create a header object
                        doc['header']=(function() {
                            return {
                                columns: [
                                    {
                                        image: logo,
                                        width: 100,
                                        alignment: 'center',
                                        margin: [-220,0]
                                    },
                                    {
                                        text: text_selection,
                                        alignment: 'left',
                                        margin: [110,60]
                                    }
                                ],
                                margin: 20
                            }
                        });
                        // Create a footer object
                        // Right side: current page and total pages
                        doc['footer']=(function(page, pages) {
                            return {
                                columns: [
                                    {
                                        alignment: 'right',
                                        text: ['page ', { text: page.toString() },	' of ',	{ text: pages.toString() }],
                                        color:'#a6a6a6'
                                    }
                                ],
                                margin: [10, 0]
                            }
                        });
                        // Change dataTable layout (Table styling)
                        // To use predefined layouts uncomment the line below and comment the custom lines below
                        // doc.content[0].layout = 'lightHorizontalLines'; // noBorders , headerLineOnly
                        var objLayout = {};
                        objLayout['hLineWidth'] = function(i) { return .5; };
                        objLayout['vLineWidth'] = function(i) { return .5; };
                        objLayout['hLineColor'] = function(i) { return '#aaa'; };
                        objLayout['vLineColor'] = function(i) { return '#aaa'; };
                        objLayout['paddingLeft'] = function(i) { return 4; };
                        objLayout['paddingRight'] = function(i) { return 4; };
                        doc.content[0].layout = objLayout;

                        doc['styles'] = {
                            userTable: {
                                margin: [0, 15, 0, 15]
                            },
                            tableHeader: {
                                bold:0,
                                fontSize:11,
                                color:'#000',
                                fillColor:'#d9d9d9',
                                alignment:'center'
                            }
                        };
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="fa fa-print"></i> Print',
                    exportOptions: {
                        columns: [0,5,6],
                        stripHtml: false
                    },
                    customize: function ( win ) {
                        $(win.document.body).css( 'font-size', '10pt' );

                        $(win.document.body).find( 'table' ).addClass( 'compact' ).css( 'font-size', 'inherit' );
                        var medias = win.document.querySelectorAll('[media="screen"]');
                        for(var i=0; i < medias.length;i++){ medias.item(i).media="all" };
                    }
                }
            ]
        } );

        table.buttons().containers().appendTo( '#options_wrapper' );
        var sortable_table = $('#sortable_table').DataTable();

        $('#sortable_table_filter').appendTo( '#options_wrapper' );
        $('#sortable_table_filter').attr( 'style','float: left;padding-left: 90px;padding-top: 5px;' );
        $('.dt-buttons').attr( 'style','float: left;' );

        //we hide the columns that we use only as filters
        var column_tag = sortable_table.column(0);
        column_tag.visible(false);
        var column_wg = sortable_table.column(1);
        column_wg.visible(false);
        var column_wg2 = sortable_table.column(2);
        column_wg2.visible(false);

        //when any of the filters is called upon change datatable data
        $('#selectWorkingGroup, #concept_active, #selectTags').change( function() {
            var table = $('#sortable_table').DataTable();
            table.draw();
        } );
    });
</script>
<div class="container">
    <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;display: none;" id="succMsgContainer">If you've made any changes, they have been saved.</div>
    <div class="backTo">
        <a href="<?=$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=ttl&record='.$recordId?>">< Back to Concept</a>
    </div>
    <?php if($concept != "") {?>
    <h3 class="concepts-title-title"><?=$concept->getConceptId().": Writing Group"?></h3>

    <?php if($isAdmin || $harmonist_perm_edit_concept){
        $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['HARMONIST'], $recordId, "concept_sheet", "");
        $survey_link = APP_PATH_WEBROOT_FULL . "/surveys/?s=".$module->escape($passthru_link['hash'])."&modal=modal";

        $gotoredcap = htmlentities(APP_PATH_WEBROOT_ALL."DataEntry/record_home.php?pid=".$pidsArray['HARMONIST']."&arm=1&id=".$recordId,ENT_QUOTES);

        $survey_queue_link = \REDCap::getSurveyQueueLink($recordId);
        ?>
        <div class="btn-group hidden-xs pull-right">
            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                Admin <span class="caret"></span>
            </button>
            <ul class="dropdown-menu">
                <li><a href="#" onclick="$('#hub_edit_concept').modal('show');">Edit Concept</a></li>
                <?php if($survey_queue_link != ''){?>
                    <li><a href="#" onclick="$('#hub_news_pubs').modal('show');">Edit News & Pubs</a></li>
                <?php } ?>
                <li role="separator" class="divider"></li>
                <li><a href="<?=$gotoredcap?>" target="_blank">Go to REDCap</a></li>
            </ul>
        </div>
        <!-- MODAL EDIT CONCEPT-->
        <div class="modal fade" id="hub_edit_concept" tabindex="-1" role="dialog" aria-labelledby="Codes">
            <div class="modal-dialog" role="document" style="width: 900px">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Edit Concept</h4>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" value="0" id="comment_loaded">
                        <iframe class="commentsform" id="redcap-concept-frame" name="redcap-concept-frame" src="<?=$survey_link?>" style="border: none;height: 810px;width: 100%;"></iframe>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-success" onclick="refreshModal('redcap-concept-frame','<?=$survey_link?>');">Back to Concept</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <script>
            function refreshModal(id,link){
                $('#'+id).attr('src', '');
                document.getElementById(id).contentWindow.location.reload(); //Reloads the Iframe
                $('#'+id).attr('src', link);
            }
        </script>
        <!-- MODAL NEWS PUBS-->
        <div class="modal fade" id="hub_news_pubs" tabindex="-1" role="dialog" aria-labelledby="Codes">
            <div class="modal-dialog" role="document" style="width: 900px">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Edit Concept Details</h4>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" value="0" id="comment_loaded_newspubs">
                        <iframe class="commentsform" id="redcap-pubs-frame" name="redcap-pubs-frame" src="<?=$survey_queue_link?>" style="border: none;height: 515px;width: 100%;"></iframe>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-success" onclick="refreshModal('redcap-pubs-frame','<?=$survey_queue_link?>');">Back to Queue</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
    <p class="hub-title concepts-title-title" style="font-weight: normal"><?=$concept->getConceptTitle()?></p>

    <table class="table table_requests sortable-theme-bootstrap" data-sortable>
        <div class="row request">
            <div class="col-md-2 col-sm-12"><strong>Working Group:</strong></div>
            <div class="col-md-6 col-sm-12"><?=$concept->getWorkingGroup()?> </div>
            <div class="col-md-4"><strong>Start Date: </strong><?=$concept->getStartDate();?> </span></div>
        </div>
        <div class="row request">
            <div class="col-md-2 col-sm-12"><strong>Contact:</strong> </div>
            <div class="col-md-6 col-sm-12"><?=$concept->getContact()?></div>
            <div class="col-md-4"><strong>Status: </strong><?=$concept->getStatus()?></div>
        </div>
    </table>

    <div class="optionSelect conceptSheets_optionMenu">
        <div style="float:left" id="options_wrapper"></div>
        <div style="float:right">
            <div style="float:left;margin-top: 8px;">
                Working groups:
            </div>
            <div style="float:left;padding-left:10px">
                <select class="form-control" name="selectWorkingGroup" id="selectWorkingGroup">
                    <option value="">Select All</option>
                    <?php
                    $wgroups = \REDCap::getData($pidsArray['GROUP'], 'json-array', null);
                    ArrayFunctions::array_sort_by_column($wgroups,'group_abbr');
                    if (!empty($wgroups)) {
                        foreach ($wgroups as $wg) {
                            if ($wg['record_id'] != "" && ($wg['group_abbr'] != "" || $wg['group_name'] != "")) {
                                $selected = '';
                                if ($wg_type == $wg['record_id']) {
                                    $selected = 'selected';
                                }
                                $wg_name = $wg['group_abbr'] . " - ".$wg['group_name'];
                                $wg_all = (strlen($wg_name) > 30) ? substr($wg_name,0,30)."..." : $wg_name;
                                echo "<option value='" . htmlspecialchars($wg['record_id'],ENT_QUOTES) . "' " . htmlspecialchars($selected,ENT_QUOTES) . ">"  . htmlspecialchars($wg_all,ENT_QUOTES) . "</option>";
                            }
                        }
                    }
                    ?>
                </select>
            </div>
            <div style="float:left;margin-top: 8px;padding-left: 10px">
                Tags:
            </div>
            <div style="float:left;padding-left:10px">
                <select class="form-control" name="selectTags" id="selectTags">
                    <option value="">Select All</option>
                    <?php
                    $concept_tags = $module->getChoiceLabels('concept_tags', $pidsArray['HARMONIST']);
                    foreach ($concept_tags as $tagid => $text){
                        $tag_text= (strlen($text) > 30) ? substr($text,0,30)."..." : $text;
                        echo "<option value='".htmlspecialchars($tagid,ENT_QUOTES)."'>".htmlspecialchars($tag_text,ENT_QUOTES)."</option>";
                    }
                    ?>
                </select>
            </div>
        </div>
    </div>
    <div>
        <table class="table table_requests sortable-theme-bootstrap concepts-table" data-sortable id="sortable_table">
            <thead>
                <tr>
                    <th class="sorted_class" data-sorted="true">Tags</th>
                    <th class="sorted_class" data-sorted="true">Working Group</th>
                    <th class="sorted_class" data-sorted="true">Working Group 2</th>
                    <th class="sorted_class" data-sorted="true" data-sorted-direction="descending">Name</th>
                    <th class="sorted_class" data-sorted="true">Email</th>
                    <th class="sorted_class" data-sorted="true">Role</th>
                    <th class="sorted_class" data-sorted="true">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php
                foreach ($writingGroupMemberList as $writingGroupMember) {
                    $edit = "";
                    if($module->getConceptModel()->canUserEdit($current_user['record_id'])){
                        $edit = '<a href="#" class="btn btn-default open-codesModal" onclick="editIframeModal(\'hub_edit_writing_group\',\'redcap-edit-frame\',\'' . $writingGroupMember->getEditLink() . '\');"><em class="fa fa-pencil"></em></a>';
                    }
                    echo "<tr wg_id=".$concept->getWgLink().">
                        <td>".$concept->getTags()."</td>
                        <td>".$concept->getWgLink()."</td>
                        <td>".$concept->getWg2Link()."</td>
                        <td>".$writingGroupMember->getName()."</td>
                        <td>".$writingGroupMember->getEmail()."</td>
                        <td>".$writingGroupMember->getRole()."</td>
                        <td>".$edit."</td>
                        </tr>";
                }
            ?>
            </tbody>
        </table>
    </div>

<?php }else{ ?>
    <div class="alert alert-warning fade in col-md-12"><em>Concept #<?=$recordId?> is not available at this time.</em></div>
<?php } ?>

