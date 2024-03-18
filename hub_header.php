<?php
use Vanderbilt\HarmonistHubExternalModule\ArrayFunctions;
use Vanderbilt\HarmonistHubExternalModule\ProjectData;

$RecordSetRM = \REDCap::getData($pidsArray['RMANAGER'], 'array', null,
    ["requestopen_ts","approval_y","finalize_y","region_response_status","request_id","contact_region",
        "assoc_concept","mr_temporary","contact_email","request_title","request_type","finalconcept_doc", "finalconcept_pdf",
        "author_doc","workflowcomplete_d","contact_name"]);
$requests = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRM,array('approval_y'=>1));

$request_type_label = $module->getChoiceLabels('request_type', $pidsArray['RMANAGER']);
$request_response_person = $module->getChoiceLabels('response_person', $pidsArray['RMANAGER']);
$numberOfOpenRequest = $module->escape(\Vanderbilt\HarmonistHubExternalModule\numberOfOpenRequest($requests,$current_user['person_region']));

$request_admin = "";
if($isAdmin) {
    $request_admin = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRM);
    ArrayFunctions::array_sort_by_column($request_admin, 'requestopen_ts');
    $numberOfAdminRequest = $module->escape(\Vanderbilt\HarmonistHubExternalModule\numberOfAdminRequest($request_admin));
}
?>

<nav class="navbar navbar-default" role="navigation">
    <div>
        <div class="navbar-header" style="min-height: 60px;">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                <span class="sr-only">Toggle navigation</span>
                <i class="icon-bar"></i>
                <i class="icon-bar"></i>
                <i class="icon-bar"></i>
            </button>
            <div class="imgNavbar">
                <?php
                $textStyleNoLogo = "padding-left:40px;padding-right:20px;";
                if($settings['hub_logo'] != ""){
                    $textStyleNoLogo = ""; ?>
                    <a href="<?=$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS']?>" style="text-decoration: none;float:left">
                        <img src='<?=\Vanderbilt\HarmonistHubExternalModule\getFile($module, $pidsArray['PROJECTS'], $settings['hub_logo'], 'src');?>' style='max-width:250px;height:40px;' class='wiki_logo_img' alt="<?=$hub_projectname?> Logo">
                    </a>
                <?php } ?>
                    <?php if(empty($token) || array_key_exists('sout', $_REQUEST) || $settings['hub_logo'] == ""){ ?>
                        <a href="<?=$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS']?>" style="<?=$textStyleNoLogo?>text-decoration: none;float:left" class="hub_header_title">
                            <span class=""><?=$hub_projectname?> Hub</span>
                        </a>
                    <?php } ?>

            </div>
        </div>
        <?php if(!empty($token) && !array_key_exists('sout', $_REQUEST)){ ?>
        <div id="navbar" class="navbar-collapse collapse" aria-expanded="false" style="height: 1px;">
            <ul class="nav navbar-nav navbar-links">
                <li class="menu-item dropdown">
                    <a href="<?=$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS']?>" role="button" option="null">Home</a>
                </li>
            </ul>

            <ul class="nav navbar-nav navbar-links">
                <li class="menu-item dropdown">
                    <a href="<?=$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=hub'?>" role="button" option="hub">Requests <span class="badge label-default"><?=$numberOfOpenRequest?></span></a>
                </li>
            </ul>

            <ul class="nav navbar-nav navbar-links">
                <li class="menu-item dropdown">
                    <a href="<?=$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=cpt'?>"role="button" option="cpt">Concepts</a>
                </li>
            </ul>

            <ul class="nav navbar-nav navbar-links">
                <li class="menu-item dropdown">
                    <a href="<?=$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=out'?>"role="button" option="out">Publications</a>
                </li>
            </ul>

            <?php if($settings['deactivate_datahub___1'] != "1"){ ?>
            <ul class="nav navbar-nav navbar-links">
                <li class="menu-item dropdown">
                    <a href="<?=$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=dat'?>"role="button" option="dat">Data Hub</a>

                </li>
            </ul>
            <?php } ?>

            <?php if($isAdmin){ ?>
                <ul class="nav navbar-nav navbar-links">
                    <li class="menu-item dropdown">
                        <a href="<?=$module->getUrl('index.php?NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=adm')?>"role="button" option="adm">Admin <span class="badge label-default"><?=$numberOfAdminRequest?></span></a>
                    </li>
                </ul>
            <?php } ?>

            <?php if(!empty($token) && !array_key_exists('sout', $_REQUEST)){ ?>
                <ul class="nav navbar-nav navbar-right" style="padding-right: 40px;">
                    <li class="menu-item dropdown">
                        <a href="#" data-toggle="dropdown" class="dropdown-toggle hidden-sm" style="padding: 20px"><span class="label label-primary"><?=$person_region['region_code']?></span>&nbsp;&nbsp;<?=$name?> <span class="caret"></span></a>
                        <a href="#" data-toggle="dropdown" class="dropdown-toggle hidden-xs hidden-md hidden-lg" style="padding: 20px"><span class="label label-primary"><?=$person_region['region_code']?></span>&nbsp;&nbsp;<i class="fa fa-user" aria-hidden="true"></i> <span class="caret"></span></a>

                        <ul class="dropdown-menu">
                            <li><a href="<?=$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=pro'?>"><i class="fa fa-user fa-fw" aria-hidden="true"></i> Profile</a></li>
                            <li><a href="<?=$module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&option=faq'?>"><i class="fa fa-support fa-fw" aria-hidden="true"></i> Help</a></li>
                            <li class="divider"></li>
                            <?php $url_logout = $module->getUrl('index.php').'&NOAUTH&pid='.$pidsArray['PROJECTS'].'&sout';?>
                            <li><a href="#" onclick="destroy_session(<?="'".$url_logout."'"?>)"><i class="fa fa-sign-out fa-fw" aria-hidden="true"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            <?php } ?>
        </div>
        <?php } ?>
    </div>
</nav>
<?php
if($settings['session_timeout_popup'] == 1 && $settings['session_timeout_popup'] != ''){
    include("logout_popup.php");
}
?>