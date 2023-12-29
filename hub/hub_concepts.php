<?php
namespace Vanderbilt\HarmonistHubExternalModule;
$wg_type = $_REQUEST['type'];
$concepts_table = "";
$RecordSetConcetps = \REDCap::getData($pidsArray['HARMONIST'], 'array', null);
$concepts = $module->escape(ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConcetps));
if (!empty($concepts)) {
    $concepts_table .= '<table class="table table_requests sortable-theme-bootstrap concepts-table" data-sortable id="sortable_table">'.
        '<colgroup>'.
        '<col>'.
        '<col>'.
        '<col>'.
        '<col>'.
        '<col>'.
        '</colgroup>'.
        '<thead><tr>'.
        '<th class="sorted_class" data-sorted="true" data-sorted-direction="descending">ID</th>'.
        '<th class="sorted_class" data-sorted="true">Working Group</th>'.
        '<th class="sorted_class" data-sorted="true">Tags</th>'.
        '<th class="sorted_class" data-sorted="true">Active</th>'.
        '<th class="sorted_class" data-sorted="true">Working Group 2</th>'.
        '<th class="sorted_class">Contact</th>'.
        '<th class="sorted_class">Concept Title</th>'.
        '<th class="sorting_disabled" data-sortable="false">Concept</th>'.
        '</tr></thead><tbody>';

    foreach ($concepts as $concept) {
        $id_people = $concept['contact_link'];
        $name = "";
        if(!empty($id_people)){
            $name = \Vanderbilt\HarmonistHubExternalModule\getPeopleName($pidsArray['PEOPLE'], $id_people);
        }
        $tags = "";
        foreach ($concept['concept_tags'] as $tag=>$value){
            if($value == 1) {
                $tags .= $tag.",";
            }
        }
        $concepts_table .= '<tr wg_id="'.$concept['wg_link'].'">'.
            '<td style="" data-order="'.$concept['concept_id'].'"><strong>' . $concept['concept_id'].'</strong></td>' .
            '<td>' . $concept['wg_link'].'</td>' .
            '<td>' . htmlspecialchars($tags,ENT_QUOTES).'</td>' .
            '<td>' . $concept['active_y'].'</td>' .
            '<td>' . $concept['wg2_link'].'</td>' .
            '<td style="">' . htmlspecialchars($name,ENT_QUOTES). '</td>' .
            '<td><a href="'.$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=ttl&record='.$concept['record_id'].'">' . $concept['concept_title'] . '</a></td>' ;

        #Only check if they are final
        $row = "";
        $RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', array('record_id' => $concept['record_id']));
        $sop = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP)[0];
        if(!empty($sop["status"]) && in_array('1',$sop["status"]) && !empty($sop["pdf_file"])) {
            #SOP Files from Builder SOP project
            $edoc_data = $sop["pdf_file"];
        }
        if(empty($row['doc_name'])){
            #SOP Files from Concept Sheets project
            $edoc_data = $concept["datasop_file"];
        }

        $file_concept ='';
        if($concept["concept_file"] != ""){
            $file_concept = \Vanderbilt\HarmonistHubExternalModule\getFileLink($module, $pidsArray['PROJECTS'], $concept["concept_file"],'1','', $secret_key, $secret_iv, $current_user['record_id'],"");
        }
        $concepts_table .= '<td style="text-align: center;">'.$file_concept.'</td>';

        $concepts_table .= '</tr>';
    }
    $concepts_table .= '</tbody></table>';
}else{
    $concepts_table = '<div class="concepts-table-notfound">No concepts found.</div>';
}

$date = new \DateTime();
$export_name = "concepts_".$date->format('Y-m-d H:i:s');

$harmonist_perm_new_concept = \Vanderbilt\HarmonistHubExternalModule\hasUserPermissions($current_user['harmonist_perms'], 2);
?>

<?php
if(array_key_exists('message', $_REQUEST)){
    if($_REQUEST['message'] == 'N'){
        ?>
        <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;"
             id="succMsgContainer">Your concepts has been successfully added.
        </div>
        <?php
    }
}

$img = \Vanderbilt\HarmonistHubExternalModule\getFile($module, $pidsArray['PROJECTS'], $settings['hub_logo_pdf'],'src');

?>
<script language="JavaScript">
    //To filter the data
    $.fn.dataTable.ext.search.push(
        function( settings, data, dataIndex ) {
            var wg = $('#selectWorkingGroup').val();
            var tags = $('#selectTags').val();
            var active = $('#concept_active').is(':checked');
            $('#concept_active').val($('#concept_active').is(':checked'));
            var column_wg = data[1];
            var column_tags = data[2];
            var column_active = data[3];
            var column_wg2 = data[4];

            if((active == true && column_active == 'Y') || active == false){
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
            }

            return false;
        }
    );

    $(document).ready(function() {
        $('#form_concepts_list').click(function (event) {
            $('<input />').attr('type', 'hidden').attr('name', 'concept_active').attr('value', $('#concept_active').is(':checked')).appendTo('#form_concepts_list');
            $('<input />').attr('type', 'hidden').attr('name', 'wg').attr('value', $('#selectWorkingGroup').val()).appendTo('#form_concepts_list');
            $('<input />').attr('type', 'hidden').attr('name', 'tags').attr('value', $('#selectTags').val()).appendTo('#form_concepts_list');
            $('<input />').attr('type', 'hidden').attr('name', 'select').attr('value', $('#options_wrapper input').val()).appendTo('#form_concepts_list');
            return true;
        });


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

        $('#sortable_table_filter').appendTo( '#options_wrapper' );
        $('#sortable_table_filter').attr( 'style','float: left;padding-left: 90px;padding-top: 5px;' );
        $('.dt-buttons').attr( 'style','float: left;' );

        var sortable_table = $('#sortable_table').DataTable();

        //we hide the columns that we use only as filters
        var column_wg = sortable_table.column(1);
        column_wg.visible(false);
        var column_tag = sortable_table.column(2);
        column_tag.visible(false);
        var column_active = sortable_table.column(3);
        column_active.visible(false);
        var column_wg2 = sortable_table.column(4);
        column_wg2.visible(false);

        //when any of the filters is called upon change datatable data
        $('#selectWorkingGroup, #concept_active, #selectTags').change( function() {
            var table = $('#sortable_table').DataTable();
            table.draw();
        } );

        reloadCode();

        $('#hub_new_concept').on('hidden.bs.modal', function () {
            top.location.hash = "triggerReloadCode";
            top.location.reload(true);

        });
    } );

    $(document).ready(function() {
        $('html,body').scrollTop(0);
        $("html,body").animate({ scrollTop: 0 }, "slow");
    });

    function reloadCode() {
        if (window.location.hash.substr(1) == "triggerReloadCode") {
            window.location.hash = "";
            $('#succMsgContainer').show();
        }
    }

</script>

<div class="container">
    <div class="optionSelect">
        <h3>Concept Sheets</h3>
            <p class="hub-title"><?=filter_tags($settings['hub_concept_text'])?></p>
    </div>

    <div class="optionSelect">
        <?php
        $button_style = "margin: 0 auto;width: 200px;";
        if($isAdmin || $harmonist_perm_new_concept){
            $button_style = "margin: 0 auto;width: 350px;";
        }
        ?>
        <div style="<?=$button_style;?>">
            <?php
            $newconcept_btn_css = "text-align:center";
            if($isAdmin && $settings['deactivate_concept_tracker'][1] != 1){
                $newconcept_btn_css = "display: inline-block";
                ?>
                <div style="display: inline-block">
                    <form method="POST" action="<?=$module->getUrl('hub/hub_concepts_tracker_spreadsheet.php').'&NOAUTH'?>" id="form_concepts_tracker">
                        <button type="submit" class="btn btn-primary"><span class="fa fa-arrow-down"></span> Concept Tracker</button>
                    </form>
                </div>
            <?php } ?>
            <?php if($isAdmin || $harmonist_perm_new_concept){?>
                <div style="<?=$newconcept_btn_css?>">
                    <a href="#" onclick="$('#hub_new_concept').modal('show');" class="btn btn-success btn-md"><span class="fa fa-plus"></span> New Concept</a>
                </div>

                <!-- MODAL NEW CONCEPT-->
                <div class="modal fade" id="hub_new_concept" tabindex="-1" role="dialog" aria-labelledby="Codes">
                    <div class="modal-dialog" role="document" style="width: 950px">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title">New Concept</h4>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" value="0" id="comment_loaded">
                                <iframe class="commentsform" id="redcap-new-frame" name="redcap-new-frame" message="N" src="<?=$module->escape(APP_PATH_WEBROOT_FULL."surveys/?s=".$pidsArray['CONCEPTLINK']."&modal=modal")?>" style="border: none;height: 810px;width: 100%;"></iframe>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
    <div style="float:right;padding-bottom:5px;">
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="concept_active" name="concept_active" checked>
            <label class="custom-control-label" for="concept_active">Active Concepts Only</label>
        </div>
    </div>
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
    <div id="loadConceptsAJAX"><?php echo $concepts_table?></div>
</div>