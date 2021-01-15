<?php
namespace Vanderbilt\HarmonistHubExternalModule;

$news_type = $module->getChoiceLabels('news_type', IEDEA_NEWITEMS);
$news_category = $module->getChoiceLabels('news_category', IEDEA_NEWITEMS);
$RecordSetNewItems = \REDCap::getData(IEDEA_NEWITEMS, 'array');
$newItems = ProjectData::getProjectInfoArray($RecordSetNewItems);
ArrayFunctions::array_sort_by_column($newItems, 'news_d',SORT_DESC);
$news_icon_color = array('fa-newspaper-o'=>'#ffbf80',	'fa-bullhorn'=>'#ccc','fa-calendar-o'=>'#ff8080','fa-bell-o'=>'#dff028',
    'fa-list-ol'=>'#b3d9ff','fa-file-o'=>'#a3a3c2','fa-trophy'=>'#9999ff','fa-exclamation-triangle'=>'#a3c2c2');

$harmonist_perm_news= \Functions\hasUserPermissions($current_user['harmonist_perms'], 9);

if(array_key_exists('message', $_REQUEST) && ($_REQUEST['message'] == 'N')){
    ?>
    <div class="container">
        <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;"
             id="succMsgContainer">Your News Item has been successfully saved.
        </div>
    </div>
    <?php
}
?>
<script>
            window.onbeforeunload = null;

    //To filter the data
    $.fn.dataTable.ext.search.push(
        function( settings, data, dataIndex ) {
            var type = $('#default-select-value').text().trim();
            var column_type = data[1];
            var category = $('#selectCat option:selected').val();
            var column_cat = data[2];

            if(type != 'Select All' && column_type == type ){
                if(category != '' && column_cat == category ){
                    return true
                }else if(category == ''){
                    return true;
                }
            }else if(type == 'Select All'){
                if(category != '' && column_cat == category ){
                    return true
                }else if(category == ''){
                    return true;
                }
            }

            return false;
        }
    );
    $(document).ready(function() {
        var type = <?=json_decode($_REQUEST['type'])?>;
        if(type != ""){
            $('#selectCat').val(type);
        }

        var loadConceptsAJAX_table = $('#table_archive').DataTable({"pageLength": 50,"order": [0, "desc"]});
        var column_publication = loadConceptsAJAX_table.column(1);
        column_publication.visible(false);
        var column_publication = loadConceptsAJAX_table.column(2);
        column_publication.visible(false);

        //when any of the filters is called upon change datatable data
        $('#default-select-value,#selectCat').change( function() {
            var table = $('#table_archive').DataTable();
            table.draw();
        } );

        //To change the text on select
        $(".dropdown-menu-custom li").click(function(){
            var selText = $(this).html();
            $(this).parents('.dropdown').find('.dropdown-toggle').html(selText+' <span class="caret" style="float: right;margin-top:8px"></span>');
            //when any of the filters is called upon change datatable data
            var table = $('#table_archive').DataTable();
            table.draw();
        });

        jQuery('[data-toggle="popover"]').popover({
            html : true,
            content: function() {
                return $(jQuery(this).data('target-selector')).html();
            },
            title: function(){
                return '<span style="padding-top:0px;">'+jQuery(this).data('title')+'<span class="close" style="line-height: 0.5;padding-top:0px;padding-left: 10px">&times;</span></span>';
            }
        }).on('shown.bs.popover', function(e){
            var popover = jQuery(this);
            jQuery(this).parent().find('div.popover .close').on('click', function(e){
                popover.popover('hide');
            });
            $('div.popover .close').on('click', function(e){
                popover.popover('hide');
            });

        });
        //We add this or the second time we click it won't work. It's a bug in bootstrap
        $('[data-toggle="popover"]').on("hidden.bs.popover", function() {
            if($(this).data("bs.popover").inState == undefined){
                //BOOTSTRAP 4
                $(this).data("bs.popover")._activeTrigger.click = false;
            }else{
                //BOOTSTRAP 3
                $(this).data("bs.popover").inState.click = false;
            }
        });

        //To prevent the popover from scrolling up on click
        $("a[rel=popover]")
            .popover()
            .click(function(e) {
                e.preventDefault();
            });

        //More/Less links
        var showChar = 510;
        var ellipsestext = "...";
        var moretext = "more";
        var lesstext = "less";
        $('.more').each(function() {
            var content = $(this).html();

            if(content.length > showChar) {

                var snippetContent = content.substr(0, showChar-1);
                var allContent = content.substr(showChar-1, content.length - showChar);

                var html = snippetContent + '<span class="moreellipses">' + ellipsestext+ '&nbsp;</span><span class="morecontent"><span>' + allContent + '</span>&nbsp;&nbsp;<a href="" class="morelink">' + moretext + '</a></span>';

                $(this).html(html);
            }

        });

        $(".morelink").click(function(){
            if($(this).hasClass("less")) {
                $(this).removeClass("less");
                $(this).html(moretext);
            } else {
                $(this).addClass("less");
                $(this).html(lesstext);
            }
            $(this).parent().prev().toggle();
            $(this).prev().toggle();
            return false;
        });


    } );
</script>
<div class="container">
    <div class="backTo">
        <a href="<?=$module->getUrl('index.php?pid='.IEDEA_PROJECTS.'&option=dat')?>">< Back to Data</a>
    </div>
    <h3>News Archive</h3>
    <p class="hub-title"><?=$settings['hub_news_archive_text']?></p>
    <br>
    <div class="optionSelect">
        <div style="margin: 0 auto;width: 200px;">
            <?php if($isAdmin || $harmonist_perm_news){?>
                <a href="#" onclick="editIframeModal('hub_add_news','redcap-add-news','<?=APP_PATH_WEBROOT_FULL."surveys/?s=".IEDEA_SURVEYNEWS."&news_person=".$current_user['record_id']?>');" class="btn btn-success btn-md"><span class="fa fa-plus"></span> Add News</a>

                <!-- MODAL ADD NEWS-->
                <div class="modal fade" id="hub_add_news" tabindex="-1" role="dialog" aria-labelledby="Codes">
                    <div class="modal-dialog" role="document" style="width: 950px">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title">Add News</h4>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" value="0" id="comment_loaded_new">
                                <iframe class="commentsform" id="redcap-add-news" name="redcap-add-news" message="N" src="" style="border: none;height: 810px;width: 100%;"></iframe>
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
    <div class="optionSelect conceptSheets_optionMenu">
        <div style="float:right">
            <div style="float:left;padding-left:30px;margin-top: 8px;">
                Type:
            </div>
            <div style="float:left;padding-left:10px">
                <?php
                $status_type = $module->getChoiceLabels('data_response_status', IEDEA_SOP);
                $selected = ' <a href="#" data-toggle="dropdown" style="width:200px" class="dropdown-toggle form-control output_select btn-group" id="default-select-value"><span class="status-text"> Select All</span><span class="caret" style="float: right;margin-top:8px"></span></a>';
                foreach ($news_type as $index=>$status){
                    $menu .= '<li style="width:200px"><span class="fa-label status fa fa-fw '.$index.'" style="background-color:'.$news_icon_color[$index].';padding: 3px;border-radius:3px;color:#fff;font-size: 13px;height: 20px;" aria-hidden="true" status="'.$index.'"></span><span class="status-text"> '.$status.'</span></li>';
                }
                ?>
                <ul class="nav" style="margin:0;width:200px" id="data_status" name="data_status">
                    <li class="menu-item dropdown">
                        <?=$selected?>
                        <ul class="dropdown-menu output-dropdown-menu dropdown-menu-custom" style="width:200px">
                            <li>Select All</li>
                            <?=$menu?>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
        <div style="float:right">
            <div style="float:left;padding-left:30px;margin-top: 8px;">
                Category:
            </div>
            <div style="float:left;padding-left:10px">
                <select class="form-control" name="selectCat" id="selectCat">
                    <option value="">Select All</option>
                    <?php
                    if (!empty($news_category)) {
                        foreach ($news_category as $index=>$value){
                            echo "<option value='".$index."'>".$value."</option>";
                        }
                    }
                    ?>
                </select>
            </div>
        </div>
    </div>
</div>
<div class="container">
    <div class="panel panel-default-archive">
        <div class="table-responsive table-archive" style="overflow-x: hidden;">
            <table class="table table_requests sortable-theme-bootstrap" data-sortable id="table_archive">
                <?php
                if(!empty($newItems)) {?>
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
                        <th class="sorted_class" data-sorted="true" data-sorted-direction="descending">Date</th>
                        <th class="sorted_class">Type Text</th>
                        <th class="sorted_class">Category</th>
                        <th class="sorted_class">Posted by</th>
                        <th class="sorted_class">News</th>
                        <th class="sorted_class">Files</th>
                        <?php if($isAdmin || $harmonist_perm_news){?>
                        <th class="sorted_class" style="text-align: center"><em class="fa fa-cog"></em></th>
                        <?php } ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($newItems as $news) {
                        $RecordSetRegions = \REDCap::getData(IEDEA_REGIONS, 'array', array('record_id' => $news['news_person']));
                        $region_code = ProjectData::getProjectInfoArray($RecordSetRegions)[0]['region_code'];

                        echo '<tr>'.
                            '<td width="8%">'.$news['news_d'].'</td>'.
                            '<td>'.$news_type[$news['news_type']].'</td>'.
                            '<td>'.$news['news_category'].'</td>'.
                            '<td width="13%">'.\Functions\getPeopleName($news['news_person'], 'email').' ('.$region_code.')</td>'.
                            '<td style="width: 60%">'.
                                        '<div><span class="label news-label-tiny" style="background-color:'.$news_icon_color[$news['news_type']].';margin-right: 10px;" title="'.$news_type[$news['news_type']].'"><i class="fa '.$news['news_type'].'"></i></span></div>'.
                                        '<div style="padding-bottom: 10px;"><strong>'.$news['news_title'].'</strong></div>'.
                                        '<div class="more">'.$news['news']." ".'</div></td>'.
                            '<td style="width: 15%;word-break: break-all"><div>'.\Functions\getFileLink($module, $news['news_file'],'','',$secret_key,$secret_iv,$current_user['record_id'],"").'</div>'.
                            '<div>'.\Functions\getFileLink($module, $news['news_file'],'','',$secret_key,$secret_iv,$current_user['record_id'],"").' </div></td>';
                        if($isAdmin || $harmonist_perm_news){
                            $edit = "";
                            if($isAdmin || $news['news_person'] == $current_user['record_id']){
                                $passthru_link = $module->resetSurveyAndGetCodes(IEDEA_NEWITEMS, $news['record_id'], "news_item", "");
                                $survey_link = $module->getUrl('surveyPassthru.php?&surveyLink='.APP_PATH_SURVEY_FULL . "?s=".$passthru_link['hash']);

                                $edit .= '<a class="btn btn-default open-codesModal" onclick="editIframeModal(\'hub_edit_news\',\'redcap-edit-frame\',\''.$survey_link.'\');"><em class="fa fa-pencil"></em></a>';
                            }
                            echo '<td>'.$edit.'</td>';
                        }
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

<!-- MODAL EDIT NEWS-->
<div class="modal fade" id="hub_edit_news" tabindex="-1" role="dialog" aria-labelledby="Codes">
    <div class="modal-dialog" role="document" style="width: 800px">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Comments and Votes</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" value="0" id="comment_loaded">
                <iframe class="commentsform" id="redcap-edit-frame" name="redcap-edit-frame" message="N" src="" style="border: none;height: 810px;width: 100%;"></iframe>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>