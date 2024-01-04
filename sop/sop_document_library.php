<?php
namespace Vanderbilt\HarmonistHubExternalModule;

$fileLibrary = \REDCap::getData($pidsArray['FILELIBRARY'], 'array');

$file_tags = $module->escape($module->getChoiceLabels('file_tags', $pidsArray['FILELIBRARY']));
$upload_type = $module->escape($module->getChoiceLabels('upload_type', $pidsArray['FILELIBRARY']));

?>
<script>
    //To filter the data
    $.fn.dataTable.ext.search.push(
        function( settings, data, dataIndex ) {
            var category = $('#selectCategory option:selected').val();
            var column_category = data[2];

            if(category != 'Select All' && column_category == category ){
                return true;
            }else if(category == 'Select All'){
                return true;
            }
            return false;
        }
    );

    $(document).ready(function() {
        Sortable.init();

        var table = $('#table_archive').DataTable({
            "pageLength": 50,
            dom: "<'row'<'col-sm-3'l><'col-sm-4'f><'col-sm-5'p>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-5'i><'col-sm-7'p>>",
            "order": [[4, "desc"]]
        });

        $('#selectCategory').change( function() {
            var table = $('#table_archive').DataTable();
            table.draw();
        } );

        $('#table_archive_filter').appendTo( '#options_wrapper' );
        $('#table_archive_filter').attr( 'style','float: right;padding-right: 190px;padding-top: 5px;' );
    });
</script>

<div class="container">
    <?php
    if(array_key_exists('message', $_REQUEST) && ($_REQUEST['message'] == 'U')){?>
        <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;" id="succMsgContainer">If you've made any changes, they have been saved.</div><?php
    }else if(array_key_exists('message', $_REQUEST) && ($_REQUEST['message'] == 'S')){?>
        <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;" id="succMsgContainer">New Library File added successfully.</div><?php
    }
    ?>
</div>
<div class="container">
    <div class="backTo">
        <?php
        if($_REQUEST['type'] == "home") {
            ?><a href="<?=$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS']?>">< Back to Home</a><?php
        }else{
            ?><a href="<?=$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=dat'?>">< Back to Data</a><?php
        }

        ?>
    </div>
    <h3>Document Library</h3>
    <p class="hub-title"><?=$settings['hub_doc_librabry_text']?></p>
    <br>
    <div style="text-align: center">
        <a href="#" onclick="$('#redcap-new-file-frame').attr('src','<?=$module->escape(APP_PATH_WEBROOT_FULL."/surveys/?s=".$pidsArray['SURVEYFILELIBRARY'])?>');$('#sop_add_library_file').modal('show');" class="btn btn-success btn-md"><i class="fa fa-plus"></i> Add Library File</a>
    </div>
    <br>
    <br>
</div>
<div class="container" style="padding-bottom: 20px">
<span>Tags: </span>
    <?php
        foreach ($file_tags as $value=>$tag){
            echo '<button class="dt-button"  href="#" onclick="selectTag('.$value.')" type="button" id="tag_'.$value.'"><span>'.$tag.'</span></button> ';
        }
    ?>
</div>
<div class="container">
    <div class="optionSelect conceptSheets_optionMenu" id="options_wrapper" style="float:left">
        <div style="float:right">
            <div style="float:left;padding-left:30px;margin-top: 8px;">
                Category:
            </div>
            <div style="float:left;padding-left:10px">
                <select class="form-control" name="selectCategory" id="selectCategory">
                    <option value="Select All">Select All</option>
                    <?php
                        foreach ($upload_type as $category){
                            echo "<option value='".$category."'>".$category."</option>";
                        }
                    ?>
                </select>
            </div>
        </div>
    </div>
</div>
<div class="container">
    <div class="panel panel-default-archive">
        <div class="table-archive">
            <table class="table table_requests sortable-theme-bootstrap" data-sortable id="table_archive">
                <?php
                if(!empty($fileLibrary)) {?>
                    <colgroup>
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
                        <th class="sorted_class">Title</th>
                        <th class="sorted_class">Description</th>
                        <th class="sorted_class">Category</th>
                        <th class="sorted_class">Person</th>
                        <th class="sorted_class"><span style="display:block">Upload</span><span>Date</span></th>
                        <?php if($isAdmin){ ?>
                        <th class="sorted_class"><i class="fa fa-cog"></i></th>
                        <?php } ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($fileLibrary as $filel) {
                        if($filel['hidden_y'][1] != "1") {
                            $tags = '';
                            if ($filel['file_tags'] != "") {
                                $tagcount = 0;
                                $totalcount = 0;
                                foreach ($filel['file_tags'] as $tagindex=>$value) {
                                    if($value == '1') {
                                        $style = "";
                                        if ($totalcount % 2 == 0 && $tagcount == 0 && $totalcount != 0) {
                                            $style = "style='margin-top: 10px;'";
                                        }

                                        if ($tagcount == 0) {
                                            $tags .= "<div " . $style . ">";
                                        }
                                        $tags .= "<div class='tag label label-info'>" . $file_tags[$tagindex] . "</div>";
                                        $tagcount++;
                                        $totalcount++;

                                        if ($tagcount == 2) {
                                            $tags .= "</div>";
                                            $tagcount = 0;
                                        }
                                    }
                                }
                            }

                            $people = \REDCap::getData($pidsArray['PEOPLE'], 'array', array('record_id' => $filel['file_uploader']),array('firstname','lastname','email'))[0];
                            $name = trim($people['firstname'] . ' ' . $people['lastname']);

                            $file_pdf = (!is_numeric($filel['file'])) ? $filel['file_title'] : \Vanderbilt\HarmonistHubExternalModule\getOtherFilesLink($module, $filel['file'], $filel['record_id'], $current_user['record_id'], $secret_key, $secret_iv, $filel['file_title']);

                            echo '<tr><td width="250x">' .htmlspecialchars($file_pdf,ENT_QUOTES) . '</td>' .
                                '<td width="450px"><div>' . htmlspecialchars($filel['file_description'],ENT_QUOTES) . '</div><div style="padding-top: 10px">'.$module->escape($tags).'</div></td>' .
                                '<td width="100px">' . htmlspecialchars($upload_type[$filel['upload_type']],ENT_QUOTES) . '</td>' .
                                '<td width="150px"><a href="mailto:' . $module->escape($people['email']) . '">' . htmlspecialchars($name ,ENT_QUOTES). '</a></td>' .
                                '<td width="150px;">' . htmlspecialchars($filel['upload_dt'],ENT_QUOTES) . '</td>';

                            if ($isAdmin) {
                                $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['FILELIBRARY'], $filel['record_id'], "file_information","");
                                $survey_link =  $module->escape(APP_PATH_WEBROOT_FULL . "/surveys/?s=".$passthru_link['hash']);

                                $edit = '<a href="#" class="btn btn-default open-codesModal" onclick="editIframeModal(\'sop_other_files_modal\',\'redcap-edit-frame\',\'' . $survey_link . '\');"><em class="fa fa-pencil"></em></a>';
                                echo '<td width="55px">' . $edit . '</td>';
                            }
                            echo '</tr>';
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

<!-- MODAL EDIT COMMENT-->
<div class="modal fade" id="sop_other_files_modal" tabindex="-1" role="dialog" aria-labelledby="Codes">
    <div class="modal-dialog" role="document" style="width: 800px">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Edit File Record</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" value="0" id="comment_loaded">
                <iframe class="commentsform" id="redcap-edit-frame" name="redcap-edit-frame" message="U" src="" style="border: none;height: 810px;width: 100%;"></iframe>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL ADD LIBRARY-->
<div class="modal fade" id="sop_add_library_file" tabindex="-1" role="dialog" aria-labelledby="Codes">
    <div class="modal-dialog" role="document" style="width: 800px">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Add Library File</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" value="0" id="comment_loaded_file">
                <iframe class="commentsform" id="redcap-new-file-frame" name="redcap-new-file-frame" message="S" src="" style="border: none;height: 810px;width: 100%;"></iframe>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>