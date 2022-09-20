<?php
namespace Vanderbilt\HarmonistHubExternalModule;

$RecordSetFaq = \REDCap::getData($pidsArray['FAQ'], 'array', null,null,null,null,false,false,false,"[help_show_y] = '1'");
$faqs = ProjectData::getProjectInfoArray($RecordSetFaq);
ArrayFunctions::array_sort_by_column($regions, 'help_category');
$help_category = $module->getChoiceLabels('help_category', $pidsArray['FAQ']);

?>
<script>
    $(document).ready(function() {
        (function($) {
            var $form = $('#filter-form');
            var $helpBlock = $("#filter-help-block");

            //Watch for user typing to refresh the filter
            $('#filter').keyup(function() {
                var filter = $(this).val();
                $form.removeClass("has-success has-error");

                if (filter == "") {
                    $helpBlock.text("No filter applied.")
                    $('.searchable .panel').show();
                    $('.faqHeader').show();
                } else {
                    //Close any open panels
                    $('.collapse.in').removeClass('in');

                    //Hide questions, will show result later
                    $('.searchable .panel').hide();

                    var regex = new RegExp(filter, 'i');

                    var filterResult = $('.searchable .panel').filter(function() {
                        return regex.test($(this).text());
                    })

                    $('.faqHeader').hide();
                    console.log(filterResult)
                    if (filterResult) {
                        if (filterResult.length != 0) {
                            $form.addClass("has-success");
                            $helpBlock.text(filterResult.length + " question(s) found.");
                            filterResult.show();
                        } else {
                            $form.addClass("has-error").removeClass("has-success");
                            $helpBlock.text("No questions found.");
                        }

                    } else {
                        $form.addClass("has-error").removeClass("has-success");
                        $helpBlock.text("No questions found.");
                    }
                }
            })

        }($));
    });

    //
    //  This function disables the enter button
    //  because we're using a form element to filter, if a user
    //  pressed enter, it would 'submit' a form and reload the page
    //  Probably not needed here on Codepen, but necessary elsewhere
    //
    $('.noEnterSubmit').keypress(function(e) {
        if (e.which == 13) e.preventDefault();
    });
</script>
<div class="container">
    <h3>FAQ</h3>
    <p class="hub-title">This page lists frequently asked questions about the <?=$settings['hub_name']?> Hub. To submit a new question for the list, contact <a href="mailto:<?=$settings['hub_contact_email']?>"><?=$settings['hub_contact_email']?></a></p>
</div>

<!-- Filter Form -->
<div class="container">
    <div class="form-group" id="filter-form">
        <label for="filter">
            Search for a Question
        </label>
        <input id="filter" type="text" class="form-control noEnterSubmit" placeholder="Enter a keyword or phrase" />
        <small>
            <span id="filter-help-block" class="help-block">
              No filter applied.
            </span>
        </small>
    </div>
</div>

<div class="container">
    <div class="panel-group searchable" id="accordion">
        <?php
        if(!empty($faqs)) {
            foreach ($help_category as $category_id => $category_value) {
                $category_count = 0;
                foreach ($faqs as $faq) {
                    if ($faq['help_category'] == $category_id) {
                        if ($category_count == 0) {
                            echo '<div class="faqHeader">' . $help_category[$faq['help_category']] . '</div>';
                        }
                        $category_count++;
                        $collapse_id = "category_" . $category_id . "_question_" . $category_count;

                        echo '<div class="panel panel-default">
                                <div class="panel-heading">
                                    <h4 class="panel-title">
                                        <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion" href="#' . $collapse_id . '">' . $faq['help_question'] . '</a>
                                    </h4>
                                </div>
                                <div id="' . $collapse_id . '" class="panel-collapse collapse">
                                    <div class="panel-body">
                                        <div>' . $faq['help_answer'] . '</div>';

                        if ($faq['help_image'] != '') {
                            $q = $module->query("SELECT stored_name,doc_name,doc_size FROM redcap_edocs_metadata WHERE doc_id = ?",[$faq['help_image']]);
                            while ($row = $q->fetch_assoc()) {
                                echo '</br><div><img src="'.$module->getUrl('downloadFile.php?NOAUTH&code='.\Vanderbilt\HarmonistHubExternalModule\getCrypt("sname=".$row['stored_name']."&file=". urlencode($row['doc_name']),'e',$secret_key,$secret_iv)) . '" style="display: block; margin: 0 auto;" alt="Image"></div>';
                            }
                        }

                        if ($faq['help_videoformat'] == '1') {
                            echo '</br><div><iframe class="commentsform" id="redcap-video-frame" name="redcap-video-frame" src="' . $faq['help_videolink'] . '" width="520" height="345" frameborder="0" allowfullscreen style="display: block; margin: 0 auto;"></iframe></div>';
                        }else{
                            echo '</br><div class="help_embedcode">' . $faq['help_embedcode'] . '</div>';
                        }

                        echo '</div>
                            </div>
                        </div>';
                    }
                }
            }
        }
        ?>
    </div>
</div>