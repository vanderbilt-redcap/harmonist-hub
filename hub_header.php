<?php
$RecordSetCurrentUser = \REDCap::getData(IEDEA_PEOPLE, 'array', null,null,null,null,false,false,false,"[access_token] = '".$token."'");
$current_user = getProjectInfoArray($RecordSetCurrentUser)[0];
$name = $current_user['firstname'].' '.$current_user['lastname'];

$isAdmin = false;
if($current_user['harmonistadmin_y'] == '1'){//$userRights->super_user == "1"
    $isAdmin = true;
}
$RecordSetPersonRegion = \REDCap::getData(IEDEA_REGIONS, 'array', array('record_id' => $current_user['person_region']));
$person_region = getProjectInfoArray($RecordSetPersonRegion)[0];

$RecordSetRM = \REDCap::getData(IEDEA_RMANAGER, 'array',null);
$requests = getProjectInfoArrayRepeatingInstruments($RecordSetRM,array('approval_y'=>1));
array_sort_by_column($requests, 'due_d');

$request_type_label = $module->getChoiceLabels('request_type', IEDEA_RMANAGER);
$request_response_person = $module->getChoiceLabels('response_person', IEDEA_RMANAGER);
$numberOfOpenRequest = numberOfOpenRequest($requests,$current_user['person_region']);

$request_admin = "";
if($isAdmin) {
    $RecordSetRM_admin = \REDCap::getData(IEDEA_RMANAGER, 'array', null);
    $request_admin = getProjectInfoArrayRepeatingInstruments($RecordSetRM_admin);
    array_sort_by_column($request_admin, 'requestopen_ts');
    $numberOfAdminRequest = numberOfAdminRequest($request_admin);
}
?>

<nav class="navbar navbar-default" role="navigation">
    <div>
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                <span class="sr-only">Toggle navigation</span>
                <i class="icon-bar"></i>
                <i class="icon-bar"></i>
                <i class="icon-bar"></i>
            </button>
            <div class="imgNavbar">
                <a href="<?=$module->getUrl('index.php?pid='.IEDEA_PROJECTS)?>" style="text-decoration: none;float:left">
                    <img src='<?=getFile($module,$settings['hub_logo'], 'src');?>' style='width:100px;height:40px;' class='wiki_logo_img' alt="IeDEA Logo">
                </a>
                    <?php if(empty($token) || array_key_exists('sout', $_REQUEST)){ ?>
                        <a href="<?=$module->getUrl('index.php?pid='.IEDEA_PROJECTS)?>" style="text-decoration: none;float:left" class="hub_header_title">
                            <span class=""><?=$hub_projectname?> Hub</span>
                        </a>
                    <?php } ?>

            </div>
        </div>
        <?php if(!empty($token) && !array_key_exists('sout', $_REQUEST)){ ?>
        <div id="navbar" class="navbar-collapse collapse" aria-expanded="false" style="height: 1px;">
            <ul class="nav navbar-nav navbar-links">
                <li class="menu-item dropdown">
                    <a href="<?=$module->getUrl('index.php')?>" role="button" option="null">Home</a>
                </li>
            </ul>

            <ul class="nav navbar-nav navbar-links">
                <li class="menu-item dropdown">
                    <a href="<?=$module->getUrl('index.php?pid='.IEDEA_PROJECTS.'&option=hub')?>" role="button" option="hub">Requests <span class="badge label-default"><?=$numberOfOpenRequest?></span></a>
                </li>
            </ul>

            <ul class="nav navbar-nav navbar-links">
                <li class="menu-item dropdown">
                    <a href="<?=$module->getUrl('index.php?pid='.IEDEA_PROJECTS.'&option=cpt')?>"role="button" option="cpt">Concepts</a>
                </li>
            </ul>

            <ul class="nav navbar-nav navbar-links">
                <li class="menu-item dropdown">
                    <a href="<?=$module->getUrl('index.php?pid='.IEDEA_PROJECTS.'&option=out')?>"role="button" option="out">Publications</a>
                </li>
            </ul>

            <?php if($settings['deactivate_datahub'][0] != "1"){ ?>
            <ul class="nav navbar-nav navbar-links">
                <li class="menu-item dropdown">
                    <a href="<?=$module->getUrl('index.php?pid='.IEDEA_PROJECTS.'&option=dat')?>"role="button" option="dat">Data Hub</a>

                </li>
            </ul>
            <?php } ?>

            <?php if($isAdmin){ ?>
                <ul class="nav navbar-nav navbar-links">
                    <li class="menu-item dropdown">
                        <a href="<?=$module->getUrl('index.php?pid='.IEDEA_PROJECTS.'&option=adm')?>"role="button" option="adm">Admin <span class="badge label-default"><?=$numberOfAdminRequest?></span></a>
                    </li>
                </ul>
            <?php } ?>

            <?php if(!empty($token) && !array_key_exists('sout', $_REQUEST)){ ?>
                <ul class="nav navbar-nav navbar-right" style="padding-right: 40px;">
                    <li class="menu-item dropdown">
                        <a href="#" data-toggle="dropdown" class="dropdown-toggle hidden-sm" style="padding: 20px"><span class="label label-primary"><?=$person_region['region_code']?></span>&nbsp;&nbsp;<?=$name?> <span class="caret"></span></a>
                        <a href="#" data-toggle="dropdown" class="dropdown-toggle hidden-xs hidden-md hidden-lg" style="padding: 20px"><span class="label label-primary"><?=$person_region['region_code']?></span>&nbsp;&nbsp;<i class="fa fa-user" aria-hidden="true"></i> <span class="caret"></span></a>

                        <ul class="dropdown-menu">
                            <li><a href="<?=$module->getUrl('index.php?pid='.IEDEA_PROJECTS.'&option=pro')?>"><i class="fa fa-user fa-fw" aria-hidden="true"></i> Profile</a></li>
                            <li><a href="<?=$module->getUrl('index.php?pid='.IEDEA_PROJECTS.'&option=faq')?>"><i class="fa fa-support fa-fw" aria-hidden="true"></i> Help</a></li>
                            <li class="divider"></li>
                            <li><a href="#" onclick="destroy_session('index.php?sout')"><i class="fa fa-sign-out fa-fw" aria-hidden="true"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            <?php } ?>
        </div>
        <?php } ?>
    </div>
</nav>