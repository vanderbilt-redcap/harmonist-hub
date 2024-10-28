<?php
namespace Vanderbilt\HarmonistHubExternalModule;
$changelog = \REDCap::getData($pidsArray['CHANGELOG'], 'json-array', null);
ArrayFunctions::array_sort_by_column($changelog,'release_d',SORT_DESC);
?>

<div class="optionSelect">
    <h3>Harmonist Hub Changelog</h3>
    <p>This page describes the release versions of the <?=$settings['hub_name']?> Hub and documents key changes and additions to the platform</p>
</div>
<div class="optionSelect">
    <div class="panel panel-default" >
        <div class="panel-heading">
            <h3 class="panel-title">
                Changelog
            </h3>
        </div>
        <div id="collapse3" class="table-responsive panel-collapse collapse in" aria-expanded="true">
            <table class="table table_requests sortable-theme-bootstrap" data-sortable id="table_changelog">
                <?php
                if(!empty($changelog)) {
                    echo '<thead>'.'
                            <tr>'.'
                                <th class="sorted_class" data-sorted-direction="descending" data-sorted="true">Version</th>'.'
                                <th class="sorted_class" data-sorted-direction="descending" style="width:100px">Release Date</th>'.'
                                <th class="sorted_class" data-sorted-direction="descending">Major Feature Additions</th>'.'
                                <th class="sorted_class" data-sorted-direction="descending">Changes</th>'.'
                            </tr>'.'
                            </thead></tbody>';
                    foreach($changelog as $log){
                        $log = $module->escape($log);
                        if($log['release_d'] != '') {
                            echo '<tr>' .
                                '<td style="text-align: center">' . $module->escape($log['version_num']) . '</td>' .
                                '<td >' . $module->escape($log['release_d']) . '</td>' .
                                '<td style="width:224px">' . filter_tags($log['major_features']) . '</td>' .
                                '<td>' . filter_tags($log['changes']) . '</td>' .
                                '</tr>';
                        }
                    }
                    echo '</tbody>';
                }else{?>
                    <tbody>
                    <tr>
                        <td><span><em>No changelogs available</em></span></td>
                    </tr>
                    </tbody>
                <?php }?>
            </table>
        </div>
    </div>
</div>
