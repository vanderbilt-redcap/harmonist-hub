<?php
$abstracts_publications_type = $module->getChoiceLabels('output_type', $pidsArray['HARMONIST']);
$abstracts_publications_badge = array("1" => "badge-manuscript", "2" => "badge-abstract", "3" => "badge-poster", "4" => "badge-presentation", "5" => "badge-report", "99" => "badge-other");
$abstracts_publications_badge_text = array("1" => "badge-manuscript-text", "2" => "badge-abstract-text", "3" => "badge-poster-text", "4" => "badge-presentation-text", "5" => "badge-report-text", "99" => "badge-other-text");

$date = new \DateTime();
$export_name = "publications_".$date->format('Y-m-d H:i:s');
$harmonist_perm = ($current_user['harmonist_perms___10'] == 1) ? true : false;

$canEdit = false;
if($harmonist_perm || $isAdmin) {
    $canEdit = true;
}
$pubtext3 = empty($settings['pubtext3']) ? $settings['hub_name'] : $settings['pubtext3'];
$pubtext4 = empty($settings['pubtext4']) ? "Site" : $settings['pubtext4'];
$pubtext5 = empty($settings['pubtext5']) ? "Multi" : $settings['pubtext5'];
?>

<div class="container">
    <?php
    if(array_key_exists('message', $_REQUEST) && ($_REQUEST['message'] == 'E')){
        ?>
        <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;" id="succMsgContainer">Your publication has been successfully updated.</div><?php
    }
    ?>
</div>
<div class="container">
    <h3>Publications</h3>
    <p class="hub-title"><?=filter_tags($settings['hub_publications_text'])?></p>
</div>

<div class="optionSelect">
    <div style="margin: 0 auto;width: 40%;">
        <form method="POST" action="<?=$module->getUrl('hub/generate_publications_pdf.php').'&NOAUTH'?>" style="float: left;">
            <button type="submit" class="btn btn-primary">Download Manuscript List</button>
        </form>
        <form method="POST" action="<?=$module->getUrl('hub/generate_outputs_excel.php').'&NOAUTH'?>" style="float: left;padding-left: 10px;">
            <button type="submit" class="btn btn-default">Download Outputs List</button>
        </form>
    </div>
</div>
<div class="container">
    <div class="optionSelect conceptSheets_optionMenu" id="options_wrapper" style="float:left">
        <div style="float:right">
            <div style="float:left;margin-top: 8px;">
                Publication type:
            </div>
            <div style="float:left;padding-left:10px">
                <ul class="nav navbar-nav navbar-right" style="padding-right: 40px;">
                    <li class="menu-item dropdown">
                        <a href="#" data-toggle="dropdown" class="dropdown-toggle form-control output_select btn-group" id="default-select-value">Select All<span class="caret" style="float: right;margin-top:8px"></span></a>

                        <ul class="dropdown-menu output-dropdown-menu" role="tablist">
                            <?php
                            if (!empty($abstracts_publications_type)) {
                                echo '<li><a href="#" tabindex="1">Select All</a></li>';
                                foreach ($abstracts_publications_type as $index=>$publication){
                                    echo '<li><a href="#" tabindex="1"><span class="fa fa-user fa-square '.$abstracts_publications_badge_text[$index].'" aria-hidden="true"></span>'.$publication.'</a></li>';
                                }
                            }
                            ?>
                        </ul>
                    </li>
                </ul>
            </div>
            <div style="float:left;padding-left:50px;margin-top: 8px;">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="mr_active" name="mr_active" checked>
                    <label class="custom-control-label" for="mr_active"><?=empty($settings['pubtext2']) ? "Multiregional Only" : $settings['pubtext2']?></label>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="panel panel-default-archive">
        <div class="table-archive">
            <table class="table table_requests sortable-theme-bootstrap" data-sortable id="client-side-table">
                <thead>
                    <tr>
                        <th class="sorted_class">Concept</th>
                        <th class="sorted_class" data-sorted="true">Year</th>
                        <th class="sorted_class" data-sorted="false"><?=empty($settings['pubtext1']) ? "Region" : $settings['pubtext1']?></th>
                        <th class="sorted_class"><span style="display:block">Journal /</span><span>Conference</span></th>
                        <th class="sorted_class" data-sorted="false">Publication Type</th>
                        <th class="sorted_class" data-sorted="false">Title and Authors</th>
                        <th class="sorted_class" data-sorted="false">Available</th>
                        <th class="sorted_class" data-sorted="false">File</th>
                        <?php if($canEdit){ ?>
                        <th style="text-align: center" data-sorted="false"><em class="fa fa-cog"></em></th>
                        <?php } ?>
                    </tr>
                </thead>
                <tbody>
                <?php
                    if($settings["publications_json"] != "") {
                        $table = "";
                        $q = $module->query("SELECT stored_name,doc_name,doc_size,mime_type FROM redcap_edocs_metadata WHERE doc_id = ?",[$settings["publications_json"]]);
                        while ($row = $q->fetch_assoc()) {
                            $path = $module->getSafePath(EDOC_PATH.$row['stored_name'],EDOC_PATH);
                            $strJsonFileContents = file_get_contents($path);
                            $json_array = json_decode($strJsonFileContents, true);
                            foreach ($json_array['data'] as $variables){
                                $table .= "<tr>";
                                $table .= "<td>".$variables['concept']."</td>";
                                $table .= "<td>".$variables['year']."</td>";
                                $table .= "<td>".$variables['region']."</td>";
                                $table .= "<td>".$variables['conf']."</td>";
                                $table .= "<td>".$variables['type']."</td>";
                                $table .= "<td>".$variables['title']."</td>";
                                $table .= "<td>".$variables['available']."</td>";
                                $table .= "<td>".$variables['file']."</td>";
                                if($canEdit){
                                    $table .= "<td>".$variables['edit']."</td>";
                                }
                                $table .= "</tr>";
                            }
                        }
                        echo $table;
                    }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- MODAL EDIT PROCESS-->
<div class="modal fade" id="hub_edit_pub" tabindex="-1" role="dialog" aria-labelledby="Codes">
    <div class="modal-dialog" role="document" style="width: 800px">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Edit Publication</h4>
            </div>
            <div class="modal-body">
                <iframe class="commentsform" id="redcap-edit-frame" message="E" name="redcap-edit-frame" src="" style="border: none;height: 810px;width: 100%;"></iframe>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<script>
    var showcolumn = <?=json_encode($canEdit)?>;
    var pubtext3 = <?=json_encode($pubtext3)?>;
    //To filter the data
    $.fn.dataTable.ext.search.push(
        function( settings, data, dataIndex ) {
            var publication = $('#default-select-value').text().trim();
            var active = $('#mr_active').is(':checked');
            var column_publication = data[4];
            var column_active = data[2];

            if(active == true && column_active == pubtext3){
                if(publication != 'Select All' && column_publication == publication ){
                    return true
                }else if(publication == 'Select All'){
                    return true;
                }
            }else if(active == false){
                if(publication != 'Select All' && column_publication == publication ){
                    return true
                }else if(publication == 'Select All'){
                    return true;
                }
            }

            return false;
        }
    );

    var table = $('#client-side-table').DataTable(
        {
            pageLength: 50,dom: "<'row'<'col-sm-3'l><'col-sm-4'f><'col-sm-5'p>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-5'i><'col-sm-7'p>>",
            order: [[ 1, "desc" ]]
        });

    if(showcolumn ==  false){
        table.column(8).visible(false);
    }


    $(document).ready(function() {
        //Fix for columns to sort correctly
        $('#client-side-table').on( 'click', 'thead th', function () {
            table.column(table.column(this).index()).data().sort();
            table.draw();
        });

        Sortable.init();

        var docname = <?=json_encode($export_name)?>;
        var logo = <?=json_encode(\Vanderbilt\HarmonistHubExternalModule\getFile($module, $pidsArray['PROJECTS'], $settings['hub_logo_pdf'],'src'))?>;

        new $.fn.dataTable.Buttons( table, {
            "buttons": [
                {
                    extend: 'excel',
                    text: '<i class="fa fa-file-excel-o"></i> Excel',
                    exportOptions: {
                        columns: 'th:not(:last-child)'
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

                        var pub_type = $('#publication_type').val();
                        if(pub_type == undefined){
                            pub_type = "Selected all";
                        }
                        var r1 = Addrow(1, [{ key: 'A', value: "Publication type: "+pub_type }, { key: 'B', value: '' }]);
                        var sheetData = sheet.getElementsByTagName('sheetData')[0];
                        sheetData.insertBefore(r1,sheetData.childNodes[0]);
                    }
                },
                {
                    extend: 'pdf',
                    text: '<i class="fa fa-file-pdf-o"></i> PDF',
                    exportOptions: {
                        columns: 'th:not(:last-child)'
                    },
                    title: docname,
                    customize: function (doc) {
                        //Remove the title created by datatTables
                        doc.content.splice(0,1);
                        //Create a date string that we use in the footer. Format is dd-mm-yyyy
                        var now = new Date();
                        var jsDate = now.getDate()+'-'+(now.getMonth()+1)+'-'+now.getFullYear();
                        // Logo converted to base64
                        // var logo = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMgAAABuCAYAAABiHVxtAAAABmJLR0QA/wD/AP+gvaeTAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAB3RJTUUH4QIJDyYKPlNTagAAIABJREFUeNrtnXl8HVW9wL9n7pJM23QJaSmBtmkLbdpCCTIgQoIboiiL4oIiTxGUhxtPQUVHVFQc5b3nBio+UdwQUcD3xAVlcaGRzQEilDYtLUkXSoGS0iTtJLnLeX+c3ySTy03u3OSmDcv5fOZzl9nO+Z3fvpyjmIBmOy4Age8xmZvtuLH7KGNKAAqwCk7nA9/LjvcdE9X353PraFjOtrzFcZtX85f5h9bszKkV3XlO79W0BDkO6dfsl9Xm2hT0pC31+NSEXjvd4p6ZFrfMsNh8/OaHd21oWM5M8tR1tpf1fmU77vRKDyrwve7n0yTYjlsHzAXmAPvJMQuYAUwHpgBpoEqIIy2EErZ+YA+wE9gCrAf+FfjeYxPJNGzHTQGHAnn5q1f61Q+EBKuBPrkmbfAoVkvJ9cNeKePXQDWQlPdNiZzfA6wKfK+/EmN8sqGR/TvbWb1gxZlPa3Xxzrxa2Z1X9OQ1QQ76NQwSiIIqSzHVghpLMzMBtYo1c6zcNw/pXPPDHQ2NZRNIEuiqML4NRAA2qaSFIPzrgKOBIwS55siERxFeFXyW03R42I7bA9wAfC/wvbYJ4PwHAQ9ECERPMBjjwKMbeCXwUCVeuH9nO080LDtrJ/wYTXIMHV6ehKu7Ghr7ajvbr31kyaGsWL869v2JVH1LnXCLPDBNiMYq88gB24ANwM+z21b91XZcsttWTRoCyW5bRaq+5WTgRuA4YJGMN1SZVJFjpPZn4GrgZ8D/Af8ANgn3nCqHJVz2SOD8VH3LWan6liTQld226pmQWMYDo+y2Vc+m6lsahCklRdqVO3e7gB0iZfplLi3BicJrVYwjB/wsu23VE5WYt0cPXjHD0lw3gKrbrRV9WtGvFQMiOXIR7pBQkFSKtIIqBdUWTFEwTWmSSr/6lAPqrz9q3cO7yuYItuPWCHedBpwKXB7z/jzwNeAXAuhnA9/bPRn1Y5Eg04DXAvsDtaJK1cmYa2M+6pbA9944wjuqgXrgJOCbI6gzzwB/AM4PfC8YK6yiKpvtuFNlLLOB04DPxXzMbmEUKbGvQsJIRlTKuXIcKBJrvhxLRnjmHuCVge/545mve5au5Jh1D3H30pXf2D+T/XhfXvFkXrEzryhHxdpPwVwrh600G6tS1ze3P/Suu5es5BXrH4pPIIWGn+241wLvjnF/T+B703meN9txq4SDxmnvCXzv5zGf+wvgDEG+YqrYGcD/Br6XrSRTsR33OuBdcWzgwPcWjYNI3wNcKZIrao+9MvC9e8c7jnuXrjysHx6qz+QYyDMuAqlWmvXpFGnNKceuf+j3lx36ci5ZXbqLVoFxHX79acwx7Ixys+cpcSAG5c6Yt2wug7u/WxjN7hGY06+BX9iOu78wpkoN688xr9s+FsdBZHw/A64qglPWeDr/z6WH8cDSFdX98CMNZKzxAUMBA0qhgazihnuWrmyIQxyMMpC4+uNTzwd3bgmPW/g1F9exUsYzCXzvV8DhoucXa+8A7rUdd2EFieTJcuZvHDBDJEghPqrxdP6odQ+zh8QpGo4yIkmNGyC7EwplxHb1AHz3gaUrUw8uOXTMBBK3Pc0Lp8UlkB1jkFAbgbcAPSNctkCIZEWFiKRvIsYywtgeB+6LOn7Ehim7PSII23bwyqo8fGuQIC01LvecArpVYpDMNLwxgFOOWL+au5euHBOBxEWWnS8gAumdCKQKuW3ge63A6aNcOhv4u+24R1ZAIscdyzMVkr6fHs22jdueUea23gRXa+PsMEaNUuSUGrMc6bMUfQU3Z+EXDy5dWfuKdQ8xmiSxRtAv4wK4ixdZGyvyCre9Hbh4lMv2A661HXe/cdp2cRnuuBmc9PE+YGuEOMYkQY5f9zB3Lz2sJQdnFVLb7sTYyEMBvZZVzEtS3QN/AjhilLiINU4E2MlLLTZhCTJ9j9GDaI2Y+Mp4bLtgb6hYkT4GwB/H85x7lqzk7saVqQzqC8UkUCahCI2IclpemSP0Y6vhXMRZtXTl+4ERpch4bZDeFxAOxxlLz3iRKfC9XuDCEpe+0Xbc/xzHq+KmeeyqEPHngVuAjPyVKuf+R5YcyjHrH0LneZ02caqikoBE+ehqWcb9m9KalDah+Ig0URq+0bbk0CkjSZGR3ph/EUqQbAWN31Kq1h2CUKO1f7cdd+U47NK9RiDSbosymXLUwxXrV7N5ydJEVvHrYudDqz+dQFuKJzBZCx1yPIbJ4HhUjvbI8ciUhNYpNEk0KRgkkjCJTENNj7J+N1FerN0vMk2pkhLzvBLOkOmAO0ZbJK4iUhEbUoh+d0TNmlKuerhJVf1EmxSdYVSeBJLC/W2VV/XV+qs5zdEYF3B4HAkcieZINEcATX8JEoc1pvPnTUWrVF6T1giRiCTRelDtysNrWpeu/GSxfiXHCZueFxDyx0GqcWeoRhBnO/Ar4MxRLj/DdtwvBb63Jm60W4hp194k+Ei/voUJjG4v5/5/LF35iiy8uVBqJICE1iJBzGe14pJzn3joSoCti5dx0Ma1Iz73y7OWXZdDoVBYaCwUWQUq/K41SrxjeTjvrqUrf3bsuoeerKSK9UKyQZ6Ncc2eChrtWUymb6bEpb8sx2DfV04WIWAfWBT43l3lSL0cfAqTJ4cSAya0GVJACk1SQyqvSWk9p2Px8q8CpAdGfubTDY3npjQLUlqTJk9KG0mUzmtS2jwzlCbynnnKuNpjq1h6byLMJGhx9PZKq5S/jyGFV9qOe+IEjbmi8ydE0jEGQn1DqM6kRKVKEkXikGAI1aOPblu47NA5W54rPXY0NLKjoXGmgv9IaG2IKg8pnR9SrcL/I4SS1LoqpXV1pQnkhaRiVcqQLweZsqGEKNHeOxH5boHv9VTqueKhG9O9FmTDFOKo1EgxKDXEhsiTymuqNFPT6M/sWNBo7WhoHEYcUhD1duCwkOslhMjSeU0aSIcEGBJdKKXQ+XK8WJk4cHmRGeCVVLHCr3HcuS3A7OdzUuhoLan1lkHOPqj2DBGHOfKk8waRLa1BcyaKlrrO9lBqIN/nUpBAqTRYIj2S+YgUGS5NupJFGP5oEiS/NxFmBGNz3NeU0eKk11Tcaxf43maMu3K0Ng9YUQaH1mMg1H3W0rB6UJ0Szp6OeJpCdSipNUo/R0WlrrM9Wkr7G4qXFxiv2DCi0FHVbXsyr7vK8WLtdRukwFOzwHbcIzHlm0dginWSmJLO1cCtwN+BjUXunfQqVkG7AWOojtY+APytguPsY5K0FPpvFrw9kTfqUOi9SqIJ/1PFsXHajobGq+s62z8gKtZbkQzgUVU6DZbSWFq8WwoszYZDNq55phwVayDG2DKVJg7bcW3bcX8M3AXcBFwgKsbCkJNiCo1+BNxjO+73K8QJ+/YhUv0pxjXvqjDHnxQEsuaQQ0nl9T2pvO4eVK/yeTGudTGpUdjevaOh0ZHvXyFu6EIbIoxIk9+OYB+NXYIEvtdXSSPPdtxjMLUMZ2OyOfPAw8DHgHOAOwpUoTpMxPmftuPOGafatU8IRPq7ldIuV2U77tEVfPWkCPIuf3Q1jRseeSCpeWyYyoPGiqco2sBXdzQ0fgZYWu77lVHddKYq95OKE0glOJrtuGFdvAvcDdRE3v2RwPdWBr737cD3fhz43gnAR4v0zcGkitdXuDKvsA1U+oECvy7Mohel2qkVtL36mUQthf5KaBfEkBqF7QRgPIjoNaxdn39qYWNsAslPBDKMoFZp23G/KOIxak99JPC9q8LrImWeV0HRnJ1G4FbbcaeMkWjjxEEmyinRRbwqwNdV8J2TikDmbVxzYxK9wSo3W5dxr3UUAF8FmNPRXnkJUgGb4yLg8wXv+13ge9+NrtxRgPT/NoKXbQVSbzEGLhsnqpybCFgEvqeBzhiXHmA7blWF7JBJl0enNOeV46YbAPoV4y3IvQLYE42nVIpAMhUgjldHRGM4zl1Ikl4xRJB7M8DXR3j8523HXTRBHq2JjPs8ElPfPjAm/uw1B0slmiDoP8TOHFXM54A9CrqVplqPm0lcVdfZrkdacXE0FauUCM6PkzgSwM957vKWdwW+t7qEMY/cOxJ4bpogFWsi1c71Ma6pxqzpFYdA9D4k9rKbxDIGgMsYwZ2ugT4FvUrzLHmmajXedPSv1XW2bxpJepSSICoG9Y3VKAVTNVeMG14V81HbR0GqJttxXz8BXqyJJJCOGNdUYTx3lWiTSoKEUqSus/1vovY8R7fdrTTdaHahUckEdtIajx1wb11n+2UhcZZLIDqGvj3mvkny3TuLEV3ge7+LQ2SB7z1dAqnOsx3XqjCBTCTX3RLL2WMW1X7BGekF7ZPAv6Id7RHi6EbTg6Z27jTU7GrU1NRYjJAAODei2o2JQAYqgFDPUa9sx00DF43w7u+XaWA/OMq5Y4G5+yAdZayGetwV8WdVaEyTToKEnLyusz0PfDQLA71ia+xC0w3syuaYXl/LjANnY+1XA3OmQK0N6UQ5r/ppXWf7I6Wkx3hVrMwYkABgGTBS+vYPRjLOR2ijrf86FyhnCR0Vk/NUvEUQPg7TecFLEFG1VnUrfUM3eUMcWtOdy2HNmsbiI5aSnzYVa0YN1qwarNlTUbNtmJ42K1iP3naJhIrVRpMgE8VhvjzC/08Cm8rkjg+XOP++MiRSnGzeiXLzhl/jrHQ4HSqw1OAkbiFXP4TMWbtgT08+T6/W7LEUS5uPwKqpQdXUwPTpWNOnG0LZrwZr9hTUfjZUJ0eD0Cl1ne29pVSrStggZdeC2I47DzhlFGmQK8c9G/jeoyUueUsZEikOQ8hOMG7EicXYMb1YpaTRpC52++fCJaiOx+jO5U7uRdOTz1PbcACzDjoApk3HqpmONa0GNb0GNcMc1swarP2mouZMQc2ogtRz0PsXdZ3tq8rZSMfaG4ONcPAvj3JZ2xg59I4S735FBYcy0RvUxFHhkhXqa34yE8hRHeu5dcHB7NT8vUfnr92jNE2HL4bdvZDLoexpqJoZIk2MJFEzpqNmTseqnYaaPRVVZ8PUFFgqVK2+HMfuiEMg+RgcqKyaA9txZ2ByZkZ636MSUS63lYofnFSm4b8vDdvuvUikFSf2SufAnbhpA2dt2Zjf9Gzvl447rKEv1ReQ7+lG93ajg92oZAo1dTrWtOmoaTUoIRJreg1qVg3WftOMNKmtZndS/aqus33d1oby8hnHs6pJuSL6KIoUxUcMxs4x9mOjeKxGaq+uIEJNtASppI1TSkIcazvuVwT2A2KDDci8hrtN7WZo/8X+yH8Dwiz2BL6Xm2CY8NGjF68gSTL37C6UPYDOZFCZAXNU26gqG5VKoVMpdDJFPpnESqchlcZKp3kmt4tVTz57/LXzFtYe1LmuqxIEEscGyZfJWZp5btQ8bAMMre1abnu8xPkFFSoyyu0FCaIqdI0WZJ42yjWHyaFHkSy6hNTRMre7MR7DxyoJjO0NjSRTiam57bu/xPRsUk0bQPVnUJkMDAxANovOZLCqq6F6CmrqdEilSSRT5FNpSKfIJRM89MgW0ko1JlEX/Hr+4kurFJy2aePk8GJFxO4bR7lsgHjp3kXhWOJ8NcblW6oNxES8iWxxNj/tmQCijB7RPQkTBUcycoTpSD3A/ZUmjscWLmFuZzuZTO4Tud7MYfrpPeind5Pf2Uv+2W50dzd61y7o6Sbf243u2YXuD1CpKqipMQb8tOls7drDQDBAGkUSvmArtey0TRv584KDx61iVcSIE/sjzeilkH2B7401xlBqAea0qHalCKmUyrg3JEicNW0rGYsJMMt4PisetG757JXvuxja3jqQz0wEFln5fCpkhpVKEl3UsZ7OhUuPzcKlWkMik8d6th/Vl4W+DPmaLGrASBOVyaDtDCqbwaoegOpq1JSp5CyLRx/pJIkiocBCkYDbbl8wZ94Jmzbo8RBIpetB3lTifMc4ABzEQLpZMTnpvrZB4vRzV4X6cUXge/9RURdchYhj9cIlpFHVObhao9EK8iizymKQxRrIoftyqH6jclkDGdTAAHogS17sk8S0Gh68414YyJBMWCQMcZCAAy1mfB2euvDehUt4ecf6MalYcVpJAoqoV2+NYWiPFcDdMZhAJTYazTOBqSbSpsW4ZmeFEPEeJmF7cOESDu1YTw7OzcLyrIipLJqswhw5TX5XPzwVoHf0kt/ZTW5XD7p7F/nubujtYctD7ezs3EY6kSCFkq17FSlLkbLUef9YuOSwl3esp23hkjETSCkVq78MjnJyiUu3TiDME8QLrsWxPyaEQCKMZHaMPlRqRfa+yUggRxiknZZBX5lFkxskEEMkGTQZJb/7c+hn+sk/tQe9o4f8zm70rm76dnSxeU0HVYnE4J7WSaVIWZBGkYapVXA9FKyWXWEVS8Wc/FmYPdjH44karR0QwxFRFeM52RjIOSHBtQgjKdXPAeKtIRzHoaCZpC0DtyXMegrkZaHphHTYeA80Wim0Bq01Vk8Gqz8H/TnUQJbte7L09/SRspRRrRQkFSSMoR56G5avWbjEO6RjvTtRKlZcYzHOHhdPlvvyCNc9PQYhp2M8slQuVo4JTDWxHTeOlOsn3r6CcQreMpOROFoXLjk/C8dkI16ALFoO8zs3qHLJYUE2k0fv7KNn+24ef7KbtKUGpUZKqUE1Ky2H+a0+vnHh0mMmikDiAjhO6LLszSQjywS9OSbCVKJNJNeNU0rbR/ydhSethChst4jL9S8LD5mdRX8mW+Aiyw5+1xF1a+h8BmObZDQ8FvQb5LcUKaVClaqQMMLf1WnUV7YtbExB8dqQ5Dg4UFyki+NwLqliyfJA+wsiLQKOAc6P6UzYXoF5nGg3bxwC6a/QWCaVBAnFcjavL7dQ8/NKk1CgMSW1oW5r1CwtqpYa/C8h/3XJPSllDPIwcDMUwCn4TyuS8BrLLCX1jXJskDgcKG6qyUExrvml7bidBfp1AqgVw/VATBAtlJLVxE+T6QRuj3Hdvk4hXxjjmu2B71XC/b43XNax2ymbNvCHBQe/NQfv02i0BrRCW1oIZIhQwkPJEqUaJb/hWfKk1JCdkSDcvHP4f0kUST1sU8//3tHQeGddZ7tfDoGMW4TbjpsEZsZ41tFyjKcvGZEWfQwFtf4KXBT4Xi5GjKWUdyjPxKa7x5G0rRV6V46JT92P1X41fzFpqM2ir0wIsudFUlh5haUgrzThufD8EMFoNIo96PsSqKOjof5BYpDrU6EE0c+xLRTw3R0Nja8E+qLZvuOJpMcx0lMxPGkAH8akLNSIhLBHsY/6ZIJ7pQ9hYl2AyQnahYkVbI8a9DFiB/kY5ycyDrIkxjV/qaAttc8lyHXzF3HG5o3cOH/xp7TmAK20eKmUdFKT15DQCq1MwDCUJsMliv7lTjh3JqxOwqIkhR4rQxgpIQw1MpN+f11n+3ei9SKj2SCZGFwoDoHEyS/6Y+B7nRM1EZNhif8SkjZFvHyxP1folRMtDUu2H8xfyJmbH+O6+YsW59AXa8Rtq4akwhAhaBIatFbkLaNaWUPSZkcWvvy6jvXBvxYuuSSJ+lkSktHksYRskhNDh75yR0PjTXWd7U9UwosVRxdOEM/FOmMS4KmKwRAmatmfmcCcEtesDXwvU0bNhS5BIPu0YOq8zR38YP6CVA7uGHLlQk7LgZaDSLBQk8tDVhN1+/7gqI71awEO71j/yxTcmRo0VhVpreISR9hu29HQmKoEgfRXkEAmQ4317n3RT0H4WZReEO7XZUhDXWJ+NJOgojCJdVkOvSAkhHwk3pHThhBykWj6YPxDG0LJoDcc27H+swCPLFwqE6RPS6Eyaa1IDTfE47YVwMfDXavGs/RoXADH6d9kkCBxVMqKu0YF4efGcGb8poKv3WdG+k/nG2fd/8xrWJKD83NRxJfvucj3kEjyw87L9bmhJNgVHevoXLiUQzrW96Y0ZybHx/0vBerrOtuLP0NKX0tJiN6YnCyOrWIz+dtEct1Xljj/GLDthbBH4Xsk3Syh1E/yWk/PaoP4eaJq1fAjG5EmEdXqu4HKPnrHgkMGn93QsY5tDY1Uw81qfA4NG/jdeFWsOJIhrjE4gxdhiyB8qWTO+4FnKuhs2Bu1Lc9p/7tgMWrzfVw3f9Hn06hXpJQyi1FrozZlC+2NiGqVC+0TQ0i7c+hLT93UoV+7afjCNvWd7cw0a/x+aJzdfdlTDcsuHw+BdFdwImZOAnzNxxhLRY10SZdJUToGdPMYF7SYVO0tmzZy84LF86pRF1UBVSiqlMmZ0jCMSHIRIgklR27INjn/lE0bd9y8YHHR94ibdh1lLBA3XFVQZFWCASt5wXgIxIpJIHHSqudMgvmLozJOhDPh3SXODxBvL/VJL0EAUihVpdRAtbIMgWBRjSKtFJZS5LUelCiFxGE+9a/eunnjtQCnjlBXHgn0fYMyVa28sshYCQasJFmV3GqNw2iNU5eQIV5KyrzngQSZqNjBh0uc/3aYCVBhe2qfSKSTNm3YXAW7q4AqZfL70yghFgalyXPUK0McXRm45Pr5i2K9S9b4vYQYHspBqaGSZFSSDAkGlLXRKsFlxiVBJG8oDmdeUqCTT8ZW8Ui67bgNmLWKR5uDL0e8XZUcy153894uWbtVlrWuSimqsahWyhCK/A7VroQytR5DkgSy8P2zNj+24Z2b468PUdfZfjdwzeji1CJjJc0REomVZEAlHx6PihV3dY046dkHTwASTESrtIr1ekbPNLgy8L2eCWAc+ySSfsKmDaGL6E5bKaotSCtFdShBlNglonqllEIpZWwTrR9/3+aOz5bzvjB9va6z/QLM4hTDpYZSZAeJIWEOIZKMSpJViQf2xtKjcci9WioPXxRGumwDkcQUe6lRGNBPKrlSyGRp1dBqA9UobKWosox6VYWiOkowEbUrb5gJV89fGPs9dZ3t0RqPN0c1gLyyGFBJBqxQYpjPLAkjRbAYIPHgeAgkbrp7nIVQqzA1HpPdSK+I3i4IP4+Rt4EAuC3wvX9NEHHsMyPd6ObWKhuFOTCflkWVEkJRiupBaWJRBVeev6Xzke/Pa+ADmzvKeldIJEn4l4ZrtFJGYgxKigQZK0EGY3sMqKR8T3D0+lXtoxFIKe9TXBH9UBymyhg2ga9w29tG67dLnD/7+TrWUCWUIrdhbePCpSzvaM9Xw1ODUsRIlUFpMmi0G/ukK62tywHO39I5pv7UmdiI1uQ/nlHJPRmSDDDcIB80zLHIqARZkptKGdpxvDpxuGVHDGmTwOwrOJn3vchWUMU6mpG3gQB43wTZHlECmbAVWiS+sxC4QOI8g21xxzqjZml19zAJMkyaMChN0qiLztiy8fEb5i8eV7+2L1zOnM71uwdU4s0ZyxjlWZVgACM1BrDIkDA2CQkyyrorlidqPAQSmeA/xnjeq4DkCyGdIobt8Z+jqVaYCsuJtD32xhpf1wP/zQhZEhbcldZgaxWRIpFPpbAt9X9v2rThJwBv37xxXJ2Z27GG7YuX07Cx7bYBlfpeZlCCJMiGhKEShjisBFmVuHe8BFJSTEcm+EcxnncUUDvJDdLxbFwawuN1QMsohvmFge/1TzAcJiwOItLjFEx2wGOjSN17QtWhWhuimCISZIohlF1TURfesfDgivVt7sY1rD3kaPpV4r8GVPKJjBjkxihPkFUJslaSHBYadXcpAlElAFyOm/A24qWTuwWSZzK13FiRNqJ2pDA++ZHg/s7A91bvhfFPSDavSEg7nEdgA0WSXsWztDXsgwLSGqq1UbWqgSmoq17Wsb5jaoX3eFr26H00rb+rM0vikkGJIYSRVYmoWrSuFIFkKzwhv4px3QW2406pFPeMItq+JLrIeG5n5MrBCwLf++NedOvqShOH9LsFs+IMmE2R+osZzWKXbitQu6gyhLLjkI51nwE4pmN9xQd+z5JXcfT6v1+TJfGnMDiYkwrF0GQJvXyjEUj/KEDMlkNAArhfx3QtepVA6DEg2oSsRBjx6HwOOH6Ey74HfKfCxLFXKwoj/f525B1rR7llN/BEsRNVEvPYsrBxQjjDMev/BkBfInl2FusZ/VxlqTO00axxAL9chLkDWcW9RHuv7biLK0EctuPOth33Zttxl1UA8fLj6MeZwJdGuOyLwIcD39MVlhylPJQV0xAiTODTQGNEa1hTgkCKrfF1RW1n+wM7GhqZ19HORLW/NJ7IiWv+9CRweZHT4yaQsjiqIEoWuDDG5TOBn4j9FluSFFxXYzvu+zBLmp4CNMV4hKo0wghxnA38YgQpfFnge5dOwPwrTGhhr4xXxjkX+HyBWv3ISPdIImFh+sdTyAJu5Wy0OZb2mvZbAThx7S3/xXP3udw8XgIpW8USIvkDcHWMW5qB/wvtkdGIJIqM8vtckVbXCBJ8Efj93tLZo2qS7bj/Dfy4yGU7gbMC3/tcgXpSSQJRe0OCRObmkwyvDN0R+F6p/QDXFfz+TF1n+6a4e5iPt9267KTw64kMd3tvPXHtLXq8EmSs7VPAwzGuOxm4z3bcA4ohUBHCeK/tuNuBH2JcxlsxBfiXlgq6yblSBNAblzhsx22wHXcDcFGRyx4Alge+96sJdB4oRl8pftw2SIGUbC6iHdwRY3xRzv2nus72a/YmIp649hZuXXYSJ669ZRPwMfk7E3UeJEcxauePwoXSwH7lGMXhucD3nrUd9z2Y4GGprQtWYGqxfwr8nxh1GXn/NEkXPx54C0Obz+wAfg58MlpHEe1b4X8yyaX2U68ugTBJ4FDbcd8lTKCwbQS+G/jeN0cwbivpkJhTOD8FLVGmipzAZB1PFTjXADNsx50jjOzfitx2W4zxhfWy3UTWWZ5o9aoYkQA/Az6AKb14oqgeajvuUuCdmA03V5ZAiieAP4j68sfA9zJxCCXKZYHfEm97hLDtEdUgJX0rJOAfAF+T9JaifSlQgfbHZHmeLK7JulHe3YeJDP8Ms9h2uJj2ocBrMJkAiwR5Cg2+LwB/CHzvmTF62GIRhu24JwJvwGQJLygXAYPOAAAXeUlEQVQxlmukbyHyK/mswuzIVYOJgk9jaHH0alGjpsgx2sqccwLfK1nqsKOhUQOX13W2fzq6ouG+aLcuO+ko4D7g4BPX3rIRQNmOOxUT2PkQ46sNb8fse3dVGXprWgzYU4i3yU1R4QQ8CJwZ+N6mmER6MaaSbyIqGftFyv0T+Grge7dVkiiKjOXfRD14GZOn7Q58b9poYw6JYUdD4xpg+b4kjNAeEWlyMfCTE9fe8mRIIEcDt4pE2M1Qmaxm9IzeKoZ2b6oSjlMPTC8XEUSHfTdms8+4SLsBuBm4KfC9u+Iioe248zD++W0yzj2C1HkZb1T1CFeUt2WM1QWE3I9Zkf4p8XxsAFYD9wW+t3NvTKztuDsx5c89mLSOgch4gjJUqVSBREjLbx2R1lUMLQaYFLikBU6JyL2/C3zv1Dgv3dHQeGBdZ/vj+1p6FBBJ8sS1t2RDG+R+zPYCuuAoZbiqAo+JKtfoj9gCrbbj3gV8QvryeuBIURNqxMPwDCa35wHRb58BgnC1jzI49OOYaHY+xnjDMVoFn1HP1mAJdeB7+2K1wnkMX7Mr7vzFcf2qUT5VEfgosVOejUkcTBbiKLBLsiGxTKpWjkfnhZz1+3xtL83JS+2l9lJ7qb3UXmovtb2sehRTP/a1SjIZVKLJCJcRjbLW2iaau9qec9FI/z+fEbYwcLg3UswlsDYPE4zaGvjeqr04RkscH/uJk2JN4Hv3TQbY2o57gMBlMfCvwPfWVKAfqhLLtQ4L9DR3tdFa23Qg8HKMay8D/LO5q60zLmAkrjIDeGKyrCdrO25aJqAz8L2c9PN4TMT9W4XR7QlshwPfApYDVwCrKjzOl2G2SfjvwPe+U3BaY7Jnb8B4Bz+DCYpVtAlsD8cEgb8DfJ3S3rSjgOswHrD3M3oWcNx+aNtxr5N5PznwvV1jeU4xt+wTwHsxAbzPYfz7cQFzICYH59sYv/pkII5q6U9hZukhmHSaw/eWiiVBwxApJyJOcqAgv1MMYQLf28JQgmDfBA55mfRjGSWyhgUuN2Pc72HsplLtdSI1q8f6gOekCjR3teVba5vCfcu3NHe15UUnVBFONOx3RISeJNKnHcgUJLQVqnW6iKoXffaw9whHGDxXJLcqOhE68s6ZwBlAsmCN2x8FvvejCPIW6r6qyLOGvb/c/hSMsbeYGtta2xQdvwJ08yFvxs4NjDoH8vm7wPfUCGMKVY7uQgIpAb9R532Ea64PfO/6USo6C5+hImMeKNIvXYATFMK/sP9yzexCe6ZIP8JrY0sQGKojDoF5CiZKnMcE7t6ACQblkRoA23HPAT4Y4c4fBc4Eam3HrQKuAr4i3Pwx4dxTMLv5bMdkds4B7hIuMgezMsYu4M+2404D/ibvbJfa5zBJ8C3y7Efk/K+lH2/BFCrNAnptx/0QJiFtJXCZ7bhbMAlzswRwxwJ3A5/GrDwSAF+QCVwG3IKJWH/bdtx6TNAyD9wr/cB23CnA24H/wtQ75IHvR5l5wWdhc4FrgYsFLu9g6TvSmGS+rQwFBC9hKCv3fIHlF2zHfVzmL2R+r8BE9z9rO+77MQmghIgotsl/YLIpvixz/j655qwITJtFLQvfeaLM6/Xye6eoM1MAV2C7HpPXFapRt8k7PiPq+9dC5geyC5uk4Qscv4rZyvsdAst7gEOFONKYpND7Bc9+KPc/isklfJXtuPcJAZwQge8ZwJ3ABZjq1W+NJuVGIpBw8vbIAH7H0MqHtcBpol+CqQCsCXzvGoayM1sD37si8L3rpCbgV5gkyO/IoPcH/hL43h7gH0IM24DvAucAdwe+9xQmhaNGiOTTwGdFFC9laGG1RcC9ge+5mMzeDcDbbcc9NfC9/2Vo4eJNge99L/C9qwPfe0gmvl44eUaI6DbMyipfC3zvk5h1hS8F3hj43loB9lRBrs8LEndhVvA4Td6zAPhr4HufkIl5GjjbdtywTjtbwISi0uPDQuiXAv+DyRb4+f03nzE/SKR/Kf3dgUmj/z0meRJMBkIWs496PdAb+F5WkP8v4hC4LPC9H0ZU5rD8+T2CJD8KfO8SwYnv2I57ECbjekpEbVqNKWZD3v06TIJop9id75c5bQMOEtjmRfX+X+DYwPc+F/ieJ/bXxbbjfjDC9aPrdV0qc/61wPd+Lcz35RHmdy6mGnB74HvXCUNGDP07A9/7G0NpS91CdB8TW6c98L0rMLVC946FQFSUy0RaL/ClwPc+yFAJ6dyIvTFdBrk7ItKWCfJsw+Tt1Ij0qbUdd0XkHQcBfwt8rx14VwGhHiA20V2YtHfE0EW47Bdsx70d+PeI1FsSuReeu9j2HobWh9otAJ4i3GUQdyOcNERqLTr+JcLd7o5MDEIwV9iOewsmU7gPk6c0v0AN6Ys6R6RdwdAie9Pke2pAWceRrgkR2gaswPfahIgAFspchomS4TsuEP076gwI+xtIGnuYZt4RsY2mYLKU8xFCnoXJ3v5cBP7TA9/7izA9gCMKYD0ghHui2Ef3R/rxD/n8QIFqFDKQsJYmnI+/hoRqO24dcFiUiVN8L5rQMN9tO26t4IcF3CSMvy/wvV+OliJUSsUqvHFaOLDIahUJTNJjguIZuU7kukWCKF8SzrUxMsD5mFRyAt/bXsDl0sDjIoqfKOj7ncB5Inkuj+j24fmRUthDRMrKc48tUCujhrQdgYcSwPdIGfHOgvfdKyrB6aJW9hWc7y1mJLfWNi2Va3KC8ItFNTsnRf52cpmQkUzFLPJABBZBhPvmIwRyQgGiwNB+iFl5X+ikOEhqYjzxJD1QoPdvFUTqlyMRIbbVBTZtNgLjrBjKMDxHKySimgiCa2BAyhAswYU90U9p1QztGd8oKvBR8v+Fge/1FuBPv0i4cJGtTWM20gs4d5Tr5gQoN0X+yxZwrFD6VBUhNht4RFSn2yMSRkeA3F/gNw/F7ZbA97oLiHaP7bgHywRvAvaIWtEhqlamgGNPKfDLZwvObyuQODC089VtBf1ZH2EQ+QiXOlpUrNVAJvC9wHbcp4AGnruiixqBKdUAjzR3tT0O/H3w7F2fBmMnrYvMT618tkWIIx+RytUR6YztuIsiNkFUklZjUtTvjiA9tuPOjDzLj9yTF6TtKMCX7gImEI7p6Wg/pIUrLv6hCNPeEenD7MD3nrYdN+x3f+B7W4GtooH8WdS+f4p6GV0I4tlIf6MJnXOIt6j6cAkiHpQo0kQRPyGqyO4CAtsNDAS+l2OohPII23GrRKr8XdSO+cD7bMedLsehQvn9EQQdKPC8hO+PLh9TFQF6d0TNW2Q77qEMraoRnntAPptCINuOWyMTn4gQSmhTnVrAbdcBPy2AW3QdzHRE2uyMqDwNtuMeJX2zInDTBXpxqGZ1RlSQb7XWNu3XWts0pbW2aWnbzBU1Be7pAyJeQ0TVCtcJSERgGtbiny3S4fURN3NoEIfLwn7Zdtz9bcedYjvuweIEyUVwZGuEKaYwC2JEcSNKCLkIE1DCVDOCF1MjLtjtoqqGuKiAPsGl3xeot+FYPyJwO1nsyLVi4z4ocDmgCBMKs8FDXPiw7bgzbcedajvu/NHWhI7m8HNNsJ3W2qbFoVsUGHjb1Hltv15xzlEWeqUApS+7bVWb7bhvE8PtaWAgu23VA6n6ln8IFbcIAGqFI90ghvmZ4gGbL1w2AN4m+umzgE7VtzwW+F7GdtwF4oVSwK5UfcuqVH3LAuCtgmR7xPOyTt53NKbWvUvUqp3SnydEnQmrJRsEwV8r1+1K1besC3zv/lR9y/3AilR9y6tS9S0r5NnnBb7XZTtuo+jSAdCTqm+5M1Xfcljkv0Dso2fE4/MyUUeTwqGfTNW3BJialyTQl6pvWRP4Xm9rbRPXBNs5x577G7n2DQKX5cAGBVuv7nsqm6pvuVTGk5B3nyoIdn3gezpV33K2jKkrVd/SJtx5jqg4c4BvyjwsFES/X66pFvi9XTx8j4vD5fUyjp3A09ltq1an6ls+JPP1TKq+ZXOqvmWX2DGzZEyhBD9QmMCmwPfuS9W33C1q46tS9S2HyLmzA9/bYTvukaLi7gKyqfqWVpEMU4HTU/UtVZiKTRe4IbttlU7VtzwqsD4l4mE7BzgjVd+yf6q+ZYeomHuEOO8B/oTJJHi13DdPgpI7sttixGwjEqRk4GukYFilAmv7IKA4apBvb7832m6pO2pQHbUdd3XcPpaIAZQcd6XmJc6qNGUGXVO2414j8KiLnPuh/LeucFX5yYZvL7UJICCZ/PYX+8TajlsjsNC2474+8v+HbcfN2I57XqXepV5CwecNUpwrrs2s2Gs/iHhrXoxMY7F4QhuBLWLYdwM3Ak+/EPaVH7Oof7EhQ1zYvFhgWGo8EzbeUjZIXBul2OTG6bTtuDNsx3217bhvtR33g5N5YqPjKqef5cJwDP2aZTvua2zHPc123AsjXqO9bjuVM/6xwKXwnko+e0QVq7W2aRkmf2d/hqLe3ZiUhQ2SzFiyPqRgaZ+qwPd6YgB9KmY5nsuBXYHvzZzExJEApo0ljbq1tikt6kEYh+rHuE5rMG7LP461/kZc2I7MF8Rcn6qMcVdhIvljzrptrW06GeMtawXuDMfaWtv0BoyHTWM8T7/FeKgaMB7RDMYjuQYTWQ+au9p0JFUnzJl7E8ab+jjwzeautjHvplUskt6JcaV6wFuau9r+E5O/8wjQ2lrbdHAk63RETiLBuGaMG/j0OOpB4Hu7MUEvEF/6SBmhlRKzhRKgWL+KPOtATPzgoXLfK5M5gEm8vEqOOzGxgn9hXOHJsXBxgWEPxn0bIkVQAaII28kY1/e/x1UDi4x/LkOJq1dGcQkTy/icnF/a3NW2A+PW/qb8/3dMUuKZwrQ/CYN1THUYt/+bI9dfJARDRQhEJi8QIgEJDjV3tf1RkPwVSKpJSPUS2Evajju/yPPPxkRu/UKAh2nOEu9gpNRp+X8/qRCL1laEzzsgEgAsfH617biNkhlaqPLV2Y67f+B7jPDcQ8Lzkb6HfWvCxD/WFEHQ8HtjsX5FEOKpyK1PNHe1PQPcoeHK42JyvMi75hfAMMzLguFp7YnCoJjASBUwCavIvIBZf/cghgdKicBwXuh2HYVgjsasNAmwsrW2aXoEJgFDwcYwHWQjkRKB5q62nZjUonXA5a21TSGx/lYk53ebu9qyzV1tvULQsyvuxWqtbfqOqDp/aO5qO1k6P12oeQrw5uautt/KTqb/gQm6HSqTEhbJfBMTqJmCiXRuAdYFvvdDAf7HMcHE5SI6WwLfu9d23BMwqR0b5Zk3YdKXAZYGvrdeUt9PwgSojhLVxAt877MyOQ2YdPV7MYHDrwEHSQByDqaS8C7MYnXdwJsC33vSdtxZmDT1AenDZZjqvB9HkOd6TODtYEza/m/lHT/CBLr+C5Nz9nUZ91rg9MD3nooSUGtt0wxRX2nuahs2D9WOe5waSpR8BpOiXoOJLlcLIkzBJCOexFDi5rGB790tKSJbgalSH/I2TLKnJarzHkyq+BeFa7fIf/MxmcGvkWf2Ac2B790vqeMrRMW6XaRnOyZ/bh4mS/Y7mIzrDcDbo7tLRepcPioSolvGclFzV9s35PwMkRKHA+c1d7VdLZLhSen7oc1dbY+01jZZwJWY1UD95q62o1prm0Iiuhi4qrmrraeA8VdMxYKhtIjeiLQISzYRPRFgUeB7Hwt87zBMenIVJm36scD3Tgu5WOB77wx875NCHGlgtty3ApOQlwI+KqkpIbJkMUl/n8DUYcDQZqAHY1LiXyuT2o+pQdhfiOc+zArqXxfkvgiTdj1DVKOXB773RSHuI4FLheNdhUmjdiVVvkUQdFCCBL73Tky9BpgCpQsD37tMYPMbTFr2ZYHv3Y5ZEvQ4JLGuoChHRybw9NbaplNba5tO/tt+R0zt871/MJSmnwx878nA9zaIK/PiwPd2APWB731CYBhm035FYDis8CjwvRvFFnwayAe+18dQTl2PXB/Oy0flmVcLAn9EpE6LuJf7MSvmf0pKHNKYKPWzge+FVainCIMoVqC0tLmrLSMIjiB52PIM5awFETiFhLZH8DHPUKrLFLE9bpTflwOrWmubLqQCbSQCyUU7FNERUwWdb7Add4vtuN+PiLKXR54zq4gum8Gsgr7ddtxrI31YVNCfNLBa6jDCXKgwnXo7cJ3tuH8XwgxF/sswuTizgRW24662HfdNge9dK/k9xwpBhHlFYbLdWwR5w5T1jbbj/jvQFfjezRG1TQuy1EeM67A58vwukZaI7ZYBFtuOu2SUeegT5HswqfWeAmZQI0mGCFe/Vb7PExj+SNSePCaNoiaCbIOerQjsdcSOCufUEoJqsh13m9Ryh/cfIog9m6Gs5miK0icwaSwbBDah6viekClEOPibIur7T+RzcWtt08IIE84X9j/S50wBfoS4WoVJj/q6/D4c+HprbdO61tqm1HgWHClVDxJFgKQgH/MzvX9KHXXJeWIw3RT43vkRr0kpA/O1mE1lbgt87yyGEvQShcAIfC9MLgu5RegxekjE/buES4f3VgW+96g4GML9QX5vO26bIEmYyFhtO+7rRMX7IkPFNl8TZ0StSLZdtuMeX6Qcc1oRA3h/TO5QlHuH9RBqNLg0d7X9sbmrzW/uanu8uastHP8GUeEUcJT09zeB7+20HffVmMKhGwPfO5ehbGNrBLV5ZqHUYngZQh6Td3U1pibnTIYS+0LYVjO0Vm+0Tui4kFlicsOaxMb4QGR84dcvANPFixWqqIjEKWwDRfAxEIadiDCpR5u72vqau9ryzV1tIbF+RSTjEuLtajZiG2nShhl4YkS9Rq6/YX7Phk22zr9bgBVy4dVy3+4inpCk1E6ASSiDoVTuvxYYk9kIEYQtlE43C3LMEE7UI8BrwyQjdoue/1nbca+Qyfq5cJRXMpS+b8sCCrcVGNg32Y77V5m8q0Qivdd23FUFkdlwwmZEpGOYFJcQQgm5XErgsm4EWOsC+28q0NdsbBZfJKsjzOmDEa6NqDaIsyDc2jkXmcNhmoAQipI8pYUFyBju8XGHfN5WaORHxl5VIP0Qm+LmKJyiDpPW2qZDxKZsFvsp9OQtAl4tdm++gLmE8OkT6ZWNMIJm+f6p1tqm+UBtc1dbW3NXWxdwicDxY4xzk6iR0t37op+ttU0zhbvcxVC1355QpxTf+2kCuGjBUVjeebp4SpKiggC8UkTy2yLPy0eIJLpvyIly7pMMlWbOkEmeL56RUMK81nbcD4je/meGKuBaxSDtAebYjnumpHZPF9tkqu24nxVd+j5R3fLAXQXEoSPeq+NlDNUieR4TZ8ZhEVspidniOWc7brGSAiUwVq21TdXiHAnrPH4pnycAj0ViSWFa/XGSlh4yr4wc+agkCXzvSYHNDExW7Csj3DsteBBK51fJmN4TsUe1SPEuQe6VkjCYithjbwcOtx3XloDvjMD3uO2xMJue84E7mrva+oAu8TL9POIVrKP48kDRsewRe+N8Uf3e3NzVtlHm9MYCPJ4iqvgPx0MgxdLdG8QgywHPnmPPzYmL9wbgYvHhI+nUi8X1mxHkSwPbUvUt92S3repO1bfcJUj8btGNLTFkl8tEafH0OJj06tVyzXrgx6n6llen6luOFelwTuB7nan6li1yzREiZrtFgkwVr8rDwFdT9S2zUvUtB4iu/ZnA9zZkt616IlXf0i5IfKZ43KZIP7aKHntMqr5lf+Fs/yP1zoMtu20VqfqW9YJsKyN2UTtDKdrHpepbkOdfHvjejaEHS2CcwKTyzwDWn2PPfUqQ9o0y1t9cE2zPZretak/VtxwvSHppdtuqXQL7e+TZJwgTuwtTd7JJVNZmsf82p+pbHs1uW/W43HOsHA/LPYtk3I9iUsGXY1LVtSD+0WLfrQ5878lUfctakWQnyfOzge/dIinuteJVWyAwtbLbVj0u4z1Axtt+jj23s7mrbWdrbVNS3j9HnAdhcHCJEOP2c+y5D0hcIwxYPyNSvQH4UHNX2yqxb/rOsee+4xx77svPsefWi6TqAz7S3NX2ZFhOMJb2/1ZraCCVW0nzAAAAAElFTkSuQmCC\n';
                        // Set page margins [left,top,right,bottom] or [horizontal,vertical]
                        // or one number for equal spread
                        doc.pageMargins = [20,100,20,30];
                        doc.defaultStyle.fontSize = 10;
                        doc.styles.tableHeader.fontSize = 10;

                        var pub_type = $('#publication_type').val();
                        if(pub_type == undefined){
                            pub_type = "Selected all";
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
                                        text: "Publication type: "+pub_type,
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
                                        text: ['page ', {text: page.toString()}, ' of ', {text: pages.toString()}],
                                        color: '#a6a6a6'
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
                        columns: 'th:not(:last-child)',
                        stripHtml: false
                    },
                    customize: function ( win ) {
                        $(win.document.body).css( 'font-size', '10pt' );

                        $(win.document.body).find( 'table' ).addClass( 'compact' ).css( 'font-size', 'inherit' );
                        $(win.document.body).find( 'table' ).attr( 'width:', '1170px' );
                        // $(win.document.body).find( 'td:nth-child(6)' ).attr( 'width', '50px' );
                        var medias = win.document.querySelectorAll('[media="screen"]');
                        for(var i=0; i < medias.length;i++){ medias.item(i).media="all" };

                        //Landscape orientation
                        var last = null;
                        var current = null;
                        var bod = [];

                        var css = '@page { size: landscape; }',
                            head = win.document.head || win.document.getElementsByTagName('head')[0],
                            style = win.document.createElement('style');

                        style.type = 'text/css';
                        style.media = 'print';

                        if (style.styleSheet)
                        {
                            style.styleSheet.cssText = css;
                        }
                        else
                        {
                            style.appendChild(win.document.createTextNode(css));
                        }

                        head.appendChild(style);

                    }
                }
            ]
        } );

        table.buttons().containers().appendTo( '#options_wrapper' );

        $('#client-side-table_filter').appendTo( '#options_wrapper' );
        $('#client-side-table_filter').attr(  'style','float: left;padding-left: 90px;padding-top: 5px;');
        $('.dt-buttons').attr( 'style','float: left;' );

        var loadConceptsAJAX_table = $('#client-side-table').DataTable();
        var column_publication = loadConceptsAJAX_table.column(4);
        column_publication.visible(false);

        //when any of the filters is called upon change datatable data
        $('#default-select-value,#mr_active').change( function() {
            var table = $('#client-side-table').DataTable();
            table.draw();
        } );

        //To change the text on select
        $(".dropdown-menu li").click(function(){
            var selText = $(this).html();
            $(this).parents('.dropdown').find('.dropdown-toggle').html(selText+" <input type='hidden' value='"+$(this).text()+"' id='publication_type'/><span class='caret' style='float: right;margin-top:8px'></span>");
            //when any of the filters is called upon change datatable data
            var table = $('#client-side-table').DataTable();
            table.draw();
        });
    } );
</script>