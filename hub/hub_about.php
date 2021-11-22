<?php
namespace Vanderbilt\HarmonistHubExternalModule;
$RecordSetAbout = \REDCap::getData($pidsArray['ABOUT'], 'array', null);
$about = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetAbout)[0];
?>

<div class="container">
    <h3><?=$about['about_title']?> - About Us Page</h3>
    <?=$about['about_text']?>
    <div>
        <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;display: none;" id="succMsgContainer">Your edits have been saved.</div>
    </div>
</div>
<div class="container">
    <div class="row">
        <?php
        foreach ($about['about_firstname'] as $id=>$member){
            $degree = '';
            if($about['about_degree'][$id] != ''){
                $degree = ', '.$about['about_degree'][$id];
            }

            echo '<div class="col-sm-6 col-md-2">'.
            '<div class="thumbnail">'.
                '<img src="'.\Vanderbilt\HarmonistHubExternalModule\getFile($module, $pidsArray['PROJECTS'], $about['about_photo'][$id],'src').'" alt="'.$about['about_firstname'][$id].' '.$about['about_lastname'][$id].'" class="about_portrait">'.
                '<div class="caption" style="text-align: center">'.
                    '<h4 style="min-height: 50px;">'.$about['about_firstname'][$id].' '.$about['about_lastname'][$id].$degree.'</h4>'.
                    '<p>'.$about['about_project_title'][$id].'</p>'.
                '</div>'.
            '</div>'.
        '</div>';
        }?>
    </div>
</div>