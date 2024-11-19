function startDDProjects(){
    $('#installbtn').prop('disabled', true);
    $.ajax({
        url: startDDProjects_url,
        data: "&pid="+pid,
        type: 'POST',
        success: function(returnData) {
            var data = JSON.parse(returnData);
            if (data.status == 'success') {
                $('#create_spinner').removeClass('fa fa-spinner fa-spin');
                window.location = getMessageLetterUrl(indexPage_url, "D");
            }
        }
    });
}

$(document).ready(function()
{
    $('input[type=checkbox]').each(function (i) {
        addRemoveSelectedClass($(this).is(':checked'),$(this).val());
    });
});

/**
 * function that adds a letter in the url to display a message
 * @param letter
 * @returns {string}
 */
function getMessageLetterUrl(url, letter){
    if (url.substring(url.length-1) == "#")
    {
        url = url.substring(0, url.length-1);
    }

    if(url.match(/(&message=)([A-Z]{1})/)){
        url = url.replace( /(&message=)([A-Z]{1})/, "&message="+letter );
    }else{
        url = url + "&message="+letter;
    }
    return url;
}

/**
 * Function to add/remove the selected class an to un/select the doubles at the same time
 * @param id
 */
function checkselectDoubles(id){
    var rowname = $("#name_"+id).text();
    var checked = $('#record_id_'+id).is(':checked');
    if (!checked) {
        $('#record_id_' + id).prop("checked", true);
        $('.desTable [record_id="' + id + '"]').addClass('rowSelected');
        $('.step_4_table [record_id="'+id+'"]').removeClass("hidden");
    } else {
        $('#record_id_' + id).prop("checked", false);
        $('.desTable [record_id="' + id + '"]').removeClass('rowSelected');
        $('.step_4_table [record_id="'+id+'"]').addClass("hidden");
    }
    checked = $('#record_id_'+id).is(':checked');
    var table_id = $('#record _id_'+id).closest('tr').attr("parent_table");

    //to un/select the doubles at the same time
    putRemove_double_selectClass('D_A','D',checked, id, rowname);

    //Add/Remove class to selected item
    addRemoveSelectedClass(checked,id);

    //to remove loaded text
    $('#num_selected'+table_id).text("");

    //Check or uncheck the 'Select All' checkbox
    var table = id.split('_')[0];
    if (($('[parent_table='+table+']').length-1) == $('[chk_name=chk_table_'+table+']:checked').length) {
        $('[name=chkAll_'+table+']').prop("checked", true);
    }else{
        $('[name=chkAll_'+table+']').prop("checked", false);
    }
}

function checkselect(id){
    var checked = $('#'+id).is(':checked');
    if (!checked) {
        $('#' + id).prop("checked", true);
        $('[row="' + id + '"]').addClass('rowSelected');
    } else {
        $('#' + id).prop("checked", false);
        $('[row="' + id + '"]').removeClass('rowSelected');
    }

    //Update the counter label
    var table = id.split('-')[0];
    checkTableCounter(table);

    //Check or uncheck the 'Select All' checkbox
    var constant = id.split('-')[0];
    if ($('[parent_table='+constant+']').length == $('[chk_name=chk_table_'+constant+']:checked').length) {
        $('#ckb_'+constant).prop("checked", true);
    }else{
        $('#ckb_'+constant).prop("checked", false);
    }

}

function getIcon(status){
    var icon = "fa-pencil-alt";
    if(status == "changed"){
        icon = "fa-pencil-alt";
    }else if(status == "added"){
        icon = "fa-plus";
    }else if(status == "removed"){
        icon = "fa-minus";
    }

    var icon_legend = '<a href="#" data-toggle="tooltip" title="'+status+'" data-placement="top" class="custom-tooltip" style="vertical-align: -2px;"><span class="label '+status+'" title="'+status+'"><i class="fas '+icon+'" aria-hidden="true"></i></span></a>';
    return icon_legend;
}

/**
 * Function that loads the SOP table
 * @param data, data we send to the ajax
 * @param url, url of the ajax file
 * @param loadAJAX, where we load our content
 */
function loadAjax(data, url, loadAJAX){
    $('#errMsgContainer').hide();
    $('#succMsgContainer').hide();
    $('#warnMsgContainer').hide();
    if(data != '') {
        $.ajax({
            type: "POST",
            url: url,
            data:data
            ,
            error: function (xhr, status, error) {
                alert(xhr.responseText);
            },
            success: function (result) {
                jsonAjax = jQuery.parseJSON(result);

                if(jsonAjax.html != '' && jsonAjax.html != undefined) {
                    $("#" + loadAJAX).html(jsonAjax.html);
                }

                if(jsonAjax.number_updates != '' && jsonAjax.number_updates != undefined && jsonAjax.number_updates != "0"){
                    $('#succMsgContainer').show();
                    $('#succMsgContainer').html(' <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a> <strong>Success!</strong> '+jsonAjax.number_updates+' NEW Latest update/s were saved.');
                }

                if(jsonAjax.variablesInfo != '' && jsonAjax.variablesInfo != undefined){
                    var value = jsonAjax.variablesInfo;
                    $.each(jsonAjax.variablesInfo, function (i, object) {;
                        if(object.display == "none"){
                            $("#"+i+"_row").hide();
                        }else{
                            $("#"+i+"_row").show();
                        };
                    });
                }

                //If table sortable add function
                if(jsonAjax.sortable == "true"){
                    $("#"+loadAJAX+"_table").tablesorter();
                }

                //Error Messages (Successful, Warning and Error)
                if(jsonAjax.succmessage != '' && jsonAjax.succmessage != undefined ){
                    $('#succMsgContainer').show();
                    $('#succMsgContainer').html(jsonAjax.succmessage);
                }else if(jsonAjax.warnmessage != '' && jsonAjax.warnmessage != undefined ){
                    $('#warnMsgContainer').show();
                    $('#warnMsgContainer').html(jsonAjax.warnmessage);
                }else if(jsonAjax.errmessage != '' && jsonAjax.errmessage != undefined ){
                    $('#errMsgContainer').show();
                    $('#errMsgContainer').html(jsonAjax.errmessage);
                }

                $('.divModalLoading').hide();
            }
        });
    }
}

/**
 * Function to Add/Remove class to selected item
 * @param checked, checked status
 * @param index, actual row
 */
function addRemoveSelectedClass(checked,index){
    if(checked) {
        $('.desTable [record_id="'+index+'"]').addClass('rowSelected');
    } else {
        $('.desTable [record_id="'+index+'"]').removeClass('rowSelected');
    }
}

/**
 * Function to un/select the doubles at the same time
 * @param char1, string to check
 * @param char2, string to check
 * @param checked, checked status
 * @param index, actual row
 * @param rowname, row name
 */
function putRemove_double_selectClass(char1, char2, checked, index, rowname){
    if(rowname.indexOf(char1) >= 0){
        var name = rowname.replace(char1,char2);
        var ant = parseInt(index.substr(index.length - 1)) - 1;
        index = index.replace(/.$/, ant);
    }else{
        var next = parseInt(index.substr(index.length - 1)) + 1;
        index = index.replace(/.$/, next);
        if($("#name_"+index).text().indexOf(char1) >= 0){
            var name = $("#name_"+index).text();
        }
    }
    if($("#name_"+index).text() == name) {
        if (checked) {
            $('#record_id_' + index).prop("checked", true);
            $('.desTable [record_id="' + index + '"]').addClass('rowSelected');
            $('.step_4_table [record_id="'+index+'"]').removeClass("hidden");
        } else {
            $('#record_id_' + index).prop("checked", false);
            $('.desTable [record_id="' + index + '"]').removeClass('rowSelected');
            $('.step_4_table [record_id="'+index+'"]').addClass("hidden");
        }
    }

    //we update the counter label
    var table = index.split('_')[0];
    checkTableCounter(table);
}

function checkTableCounter(table){
    var count = $("[chk_name='chk_table_"+table+"']:checked").length;
    var deprecated = $(".deprecated[parent_table="+table+"] ").length;
    var select_all = $("[parent_table="+table+"]").length-1;

    if(count >= select_all && deprecated > 0){
        count = count-deprecated;
        $('[name="chkAll_'+table+'"]').attr('checked',true);
    }else if(count >= select_all){
        $('[name="chkAll_'+table+'"]').attr('checked',true);
    }

    if(count>0){
        $("#counter_"+table).text(count);
    }else{
        $("#counter_"+table).text("");
    }
}

/**
 * Function that checks all checkboxes by table function
 * @param id, the attribute identification number
 */
function checkAll(id) {
    if ($("[name='chkAll_" + id + "']").not('.deprecated').prop("checked")) {
        $("[chk_name='chk_table_" + id + "']").not('.deprecated').prop("checked", true);
        $('[parent_table="' + id + '"]').not('.deprecated').addClass("rowSelected");
    } else {
        $("[chk_name='chk_table_" + id + "']").not('.deprecated').prop("checked", false);
        $('[parent_table="' + id + '"]').not('.deprecated').removeClass("rowSelected");
    }
    //to remove loaded text
    $('#num_selected' + id).text("");

    //we update the counter label
    checkTableCounter(id);
}

/**
 * Function that checks is select All is selected or not to un/mark all checkboxes by table function
 * @param id, the attribute identification number
 */
function checkAllText(id) {
    if($("[name='chkAll_"+id+"']").not(':hidden').prop("checked")) {
        $("[name='chkAll_"+id+"']").not(':hidden').prop("checked", false);
    } else {
        $("[name='chkAll_"+id+"']").not(':hidden').prop("checked", true);
    }
    checkAll(id);
}

/**
 * Function that validates if an email is in the correct format
 * @param email
 * @returns {boolean}
 */
function validateEmail(email) {
    var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(email);
}

/**
 * Function to add parameters to the URL and redirect
 * @param url, the current URL
 * @param parameter, the new parameter
 */
function addURL(url, parameter)
{
    window.location  = url+parameter;
}

/**
 * Function for the drop down menu on mobile
 */
$('ul.dropdown-menu [data-toggle=dropdown]').on('click', function(event) {
    // Avoid following the href location when clicking
    event.preventDefault();
    // Avoid having the menu to close when clicking
    event.stopPropagation();
    // If a menu is already open we close it
    $('ul.dropdown-menu [data-toggle=dropdown]').parent().removeClass('open');
    // opening the one you clicked on
    $(this).parent().addClass('open');
});

/**
 * On logout we destroy the session
 * @param goToUrl
 */
function destroy_session(goToUrl) {
    location.href = goToUrl;
}

/**
 * Function that loads the iframe url in a modal and opens it
 * @param id
 * @param idframe
 * @param survey_link
 */
function editIframeModal(id,idframe,survey_link,modalTitle = ""){
    $("#"+idframe).prop('src',survey_link);
    if(modalTitle != ""){
        $('#'+id+" .modal-title").text(modalTitle);
    }
    $('#'+id).modal('show');
}

/**
 * Function to delete an upload. First asks for confirmation
 * @param recordid
 */
function deleteUpload(recordid){
    $('#succMsgContainer').hide();
    $('#id').val(recordid);
    $('#aws-delete-file-modal').modal('show');
}

/**
 * Function that Activates/Deactivates continue button depending on which step we are
 * @param step, number of step we are in
 */
function checkStep(step){
    if(step == '1'){
        $('#setup_show_option_2').hide();
        $('#setup_show_option_3').hide();
        if($('[name=optradio]:checked').val() == '1'){
            $('#setup_show_all_option').hide();
        }else if($('[name=optradio]:checked').val() == '2'){
            $('#setup_show_all_option').show();
            $('#setup_show_option_2').show();
        }if($('[name=optradio]:checked').val() == '3'){
            $('#setup_show_all_option').show();
            $('#setup_show_option_3').show();
        }

        if ($('#selectConcept').val() !== "" && $('#selectConcept').val() !== "0" && $('[name=optradio]:checked').val() !== undefined) {
            if($('[name=optradio]:checked').val() == '2' || $('[name=optradio]:checked').val() == '3'){
                if($('#selectSOP_'+$('[name=optradio]:checked').val()).val() !== "" && $('#selectSOP_'+$('[name=optradio]:checked').val()).val() !== "0" && $('#selectSOP_'+$('[name=optradio]:checked').val()).val() !== undefined){
                    $('#save_continue_'+step).prop('disabled', false);
                }else{
                    $('#save_continue_'+step).prop('disabled', true);
                }
            }else{
                $('#save_continue_'+step).prop('disabled', false);
            }
        }else{
            $('#save_continue_'+step).prop('disabled', true);
        }
    }else if(step == '2'){
        if($('input[type=checkbox]:checked').length > 0){
            $('#save_continue_'+step).prop('disabled', false);
        }else{
            $('#save_continue_'+step).prop('disabled', true);
        }
    }else if(step == '3'){
        if($('#sop_datacontact').val() != "Select Name" && $('#sop_due_d').val() !== "" && $('#sop_due_d').val() !== "0" && $('#sop_due_d').val().length == 10 && (($('#sortable2 > li:visible').length !== "" && ($('#sortable2 > li:visible').length > 0 || $( "#sortable2" ).sortable( "toArray" ).length > 0)) || $("#sop_downloaders_dummy___1").is(':checked'))){
            $('#save_continue_'+step).prop('disabled', false);
        }else{
            $('#save_continue_'+step).prop('disabled', true);
        }
    }
}

/**
 * When loading a concept we load the option on the selected concept
 */
function checkConcept(){
    if($('[name=optradio]:checked').val() == '2' || $('[name=optradio]:checked').val() == '3'){
        if($('#selectSOP_'+$('[name=optradio]:checked').val()).val() !== "" && $('#selectSOP_'+$('[name=optradio]:checked').val()).val() !== "0" ){
            var concept_id = $('#selectSOP_'+$('[name=optradio]:checked').val()+" option:selected").attr('concept');
            $('#selectConcept').val(concept_id);
            $('#save_continue_1').prop('disabled', false);
        }
    }
}

/**
 * Function to save the data of the different steps as well as load it on the following steps
 * @param data
 * @param url
 * @param loadAjax
 * @param step
 */
function loadAjax_steps(data,url,loadAjax,step){
    if(step == '0' && $('[name=optradio]:checked').val() == '1'){
        //New Step we clean up possible previous load
        resetData();
        //New Step we collapse table from step 2
        $('.step2_collapse').removeClass('in');
    }

    $.ajax({
        type: "POST",
        url: url,
        data: data,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            jsonAjax = jQuery.parseJSON(result);
            var optradio = 0;
            for (var key in jsonAjax) {
                if (jsonAjax.hasOwnProperty(key) && jsonAjax[key] != "" && jsonAjax[key] != null) {
                    if(step == '3'){
                        if(key == "sop_tablefields"){
                            reset_table_preview();
                            update_table_fields(jsonAjax[key]);
                        }else if(key == "sop_creator_email" || key == "sop_creator2_email" || key == "sop_datacontact_email"){
                            $('[preview=' + key+"]").text(jsonAjax[key]);
                            $('#'+key).attr('href',"mailto:"+jsonAjax[key]);
                        }else if(key == "sop_creator" || key == "sop_creator2" || key == "sop_datacontact"){
                            $('[preview=' + key+"]").text(jsonAjax[key]);
                            $('#'+key).attr('href',"mailto:"+jsonAjax[key]);
                            if(key == "sop_datacontact" && jsonAjax[key] != "" && jsonAjax[key] != "Select Name"){
                                $('[preview=sop_datacontact_title]').text("Data Contact");
                            }
                            if((key == "sop_creator" || key == "sop_creator2")&& jsonAjax[key] != "" && jsonAjax[key] != "Select Name"){
                                $('[preview=sop_creator_title]').text("Research Contact(s)");
                            }
                        }
                        else if(key == "sop_concept_title"){
                            $('#selectConcept').val(jsonAjax[key]);
                            $('[preview=' + key+"]").html(jsonAjax[key]);
                        }
                        else if(key == "sop_due_d" || key == "sop_hubuser" || key == "sop_due_d_preview") {
                            $('#' + key).val(jsonAjax[key]);
                            $('[preview=' + key+"]").html(jsonAjax[key]);
                        }else if(key == "sop_inclusion" || key == "sop_exclusion" || key == "sop_notes" || key == "dataformat_notes") {
                            tinymce.get(key).setContent(jsonAjax[key]);
                            tinymce.get(key).setContent(tinymce.get(key).getContent({ format: 'text' }));
                            $('[name=' + key + ']').val(jsonAjax[key]);
                            $('[preview=' + key+"]").html(tinymce.get(key).getContent());
                            if(key == "sop_notes"){
                                $('[preview=' + key + "_header]").html("General notes: &nbsp;");
                            }else if(key == "dataformat_notes") {
                                $('[preview=' + key+"_header]").html("File format notes:");
                            }
                        }else if(key == 'sop_downloaders' && jsonAjax[key] != "") {
                            update_downloaders_list(jsonAjax[key]);
                        }else if(key.match("dataformat_prefer___") != null && jsonAjax[key] != "") {
                            update_preferred_format(key,jsonAjax[key]);
                        }else if(key == "dataformat_prefer_text" && jsonAjax[key] != "") {
                            $('[preview=' + key + "]").html("<strong>Preferred file format:<\/strong> <p></p>" + jsonAjax[key]);
                        }else if(key == "sop_downloaders_dummy___1" && jsonAjax[key] != "" && jsonAjax[key][0] == "1"){
                            $('#'+key).prop('checked',true);
                        }else if(key == "concept_id") {
                            $('[name = step_concept_id]').html(jsonAjax[key]+":");
                        }else if(key == "record_id") {
                            $('[name = step_sop]').html("Data Request #"+jsonAjax[key]);
                        }else{
                            $('#' + key).val(jsonAjax[key]);
                            $('#'+key).text(jsonAjax[key]);
                            $('[preview=' + key+"]").val(jsonAjax[key]);
                            $('[preview=' + key+"]").text(jsonAjax[key]);
                        }
                    }else{
                        //Update Data
                        if(key == "sop_tablefields") {
                            if(jsonAjax[key] != ""){
                                reset_table_preview();
                                update_table_fields_checks(jsonAjax[key]);
                                update_table_fields(jsonAjax[key]);
                            }
                        }else if(key == "sop_name" && step==1) {
                            $('#selectSOP_' + $('[name=optradio]:checked').val() + ' option:selected').text(jsonAjax[key]);
                        }else if(key == "concept_id_select" && step==1) {
                            $('#selectSOP_' + $('[name=optradio]:checked').val() + ' option:selected').attr('concept_id', jsonAjax[key]);
                        }else if(key == "sop_concept_id" && step==1){
                                $('#selectSOP_' + $('[name=optradio]:checked').val() + ' option:selected').attr('concept', jsonAjax[key]);
                        }else if(key == "concept_id") {
                            $('[name = step_concept_id]').html(jsonAjax[key]+":");
                        }else if(key == "record_id") {
                            $('[name = step_sop]').html("Data Request #"+jsonAjax[key]);
                        }else if(key == "sop_inclusion" || key == "sop_exclusion" || key == "sop_notes" || key == "dataformat_notes") {
                            tinymce.get(key).setContent(jsonAjax[key]);
                            tinymce.get(key).setContent(tinymce.get(key).getContent({ format: 'text' }));
                            $('[name=' + key + ']').val(jsonAjax[key]);
                            $('[preview=' + key+"]").html(tinymce.get(key).getContent());
                            if(key == "sop_notes"){
                                $('[preview=' + key + "_header]").html("General notes: &nbsp;");
                            }else if(key == "dataformat_notes") {
                                $('[preview=' + key+"_header]").html("File format notes:");
                            }
                        }else if(key == 'sop_downloaders' && jsonAjax[key] != "") {
                            update_downloaders_list(jsonAjax[key]);
                        }else if(key == "sop_creator_email" || key == "sop_creator2_email" || key == "sop_datacontact_email"){
                            $('[preview=' + key+"]").text(jsonAjax[key]);
                            $('[preview=' + key+"]").attr('href',"mailto:"+jsonAjax[key]);
                        }else if(key == "sop_due_d" || key == "sop_due_d_preview" || key == "sop_hubuser" || key == "sop_creator" || key == "sop_creator2" || key == "sop_datacontact" || key == "sopCreator_region") {
                            $('#' + key).val(jsonAjax[key]);
                            $('[preview=' + key+"]").html(jsonAjax[key]);
                            if(key == "sop_datacontact" && jsonAjax[key] != "" && jsonAjax[key] != "Select Name"){
                                $('[preview=sop_datacontact_title]').text("Data Contact");
                            }
                            if((key == "sop_creator" || key == "sop_creator2")&& jsonAjax[key] != "" && jsonAjax[key] != "Select Name"){
                                $('[preview=sop_creator_title]').text("Research Contact(s)");
                            }
                        }else if(key == "sop_extrapdf"){
                            getFileFieldElement(jsonAjax[key]);
                        }else if(key == "sop_discuss"){
                            $('#selectSOP_' + $('[name=optradio]:checked').val()).val(jsonAjax[key]);
                        }else if(key == "selectConcept"){
                            $('#selectConcept').val(jsonAjax[key]);
                        }else if(key == "optradio"){
                            $('#optradio_'+jsonAjax[key]).prop('checked',true);
                            $('#setup_show_option_' + jsonAjax[key]).show();
                            $('#setup_show_all_option').show();
                            optradio = jsonAjax[key];
                        }else if(key.match("dataformat_prefer___") != null && jsonAjax[key] != "") {
                            update_preferred_format(key,jsonAjax[key]);
                        }else if(key == "dataformat_prefer_text" && jsonAjax[key] != ""){
                            $('[preview=' + key+"]").html("<strong>Preferred file format:<\/strong> <p><\/p>"+jsonAjax[key]);
                        }else if(key == "sop_downloaders_dummy___1" && jsonAjax[key] != "" && jsonAjax[key][0] == "1"){
                            $('#'+key).prop('checked',true);
                        }else if(key == "save_option"){
                            $('#save_option').val(jsonAjax[key]);
                            $('#optradio_3').attr('checked',true);
                            $('#setup_show_option_3').show();
                            $('#setup_show_all_option').show();
                        }else if(key == "select" && jsonAjax[key] != ""){
                            $('#selectSOP_3').html(jsonAjax[key]);
                        }else{
                            $('#'+key).val(jsonAjax[key]);
                            $('#'+key).text(jsonAjax[key]);
                            $('[preview=' + key+"]").val(jsonAjax[key]);
                            $('[preview=' + key+"]").text(jsonAjax[key]);
                        }
                    }
                }
            }
            if(optradio != (parseInt(step) + 2) && optradio > 0){
                checkStep(optradio);
            }

            if(step == 0){
                step = 2;
            }
            checkStep(parseInt(step) + 1);
            check_people_region_dragAndDrop();
            $(".deprecated").hide();
        }

    });
    //we remove the class so it does not show colors in the preview
    $('#PreviewTable tr').removeClass('rowSelected');
    $('.preview').removeClass('rowSelected');
    $('.panel-heading').removeClass('rowSelected');
    $('.rowSelected div').removeClass('rowSelected');

    if(step == '1' && $('[name=optradio]:checked').val() == '1') {
        //Clean up downloaders list
        $("#sortable2 li").appendTo('#sortable1');
        $('#sortable1').sortable('option', 'receive')(null, {item: $("#sortable2 li")});
    }
}

function resetData(){
    reset_table_fields();
    $('#sop_inclusion').html('');
    $('#sop_exclusion').html('');
    $('#sop_notes').html('');
    $('#dataformat_notes').html('');
    $('#sop_creator_email').html('');
    $('[preview=sop_creator_email]').text('');
    $('[preview=sop_creator_email]').attr('href',"");
    $('#sop_due_d').val('');
    $('[preview=sop_due_d]').html('');
    $('#researchContact_name').val('');
    $('[preview=researchContact_name]').html('');
    $('#selectSOP_' + $('[name=optradio]:checked').val()).val('');
}

function reset_table_fields(){
    $('.desTable input[type="checkbox"]').prop('checked', false);
    $('.desTable tr').removeClass('rowSelected');
    $('.desTable tr').removeClass('in');
    $('.dataRequests').html('');
}

function reset_table_preview(){
    $('#preview_table .preview').hide();
}

/**
 * Modal to confirm we want to change the concept from the one loaded as they are different
 * @param option
 */
function changeConcept(option){
    var message = "This draft is currently associated with <strong>"+$('#selectSOP_'+option+' option:selected').attr('concept_id')+"</strong>. Are you sure you want to change it to <strong>"+$('#selectConcept option:selected').attr('concept')+"</strong>?";
    message += "<br/>The <em>Data Request name</em> will be automatically changed.";
    message += "<br/>The <em>name and email of the research contact</em> will be automatically changed.";

    $('#sop-change-concept-question').html(message);
    $('#sop-change-concept-modal').modal('show');
}

/**
 * STEP 2 check if we are missing any required variables and show a warning
 * @returns {string}
 */
function check_required_variables(){
    var str = $('#parent_table_record_id_array').val();
    var string_record_id =  str.substring(0, str.length - 1);
    var record_id_array = string_record_id.split(',');

    var variables_required_added_array = "";
    var variables_required_added_array_id = "";
    var tables_required_added_array = "";
    var any_table_required = false;
    for (var i = 0; i < record_id_array.length; i++) {
        var index = i+1;
        var any_var_checked = false;
        $("[parent_table='"+index+"']").each(function() {
            var id = $(this).attr("record_id");
            if($("#record_id_"+id).is(":checked")){
                any_var_checked = true;
            }
        });
        if(any_var_checked) {
            var any_var_required = false;
            $("[parent_table='" + index + "']").each(function () {
                var id = $(this).attr("record_id");
                if ($("#record_id_" + id).attr("variable_required") == "Y" && $("#record_id_" + id).is(":checked") == false) {
                    $('#record_id_' + id).prop("checked", true);
                    // $('[record_id=' + id+']').addClass('rowSelected');
                    any_var_required = true;
                    variables_required_added_array += $("#name_" + id).text() + ", ";
                    variables_required_added_array_id += id + ",";
                }
            });
            if(any_var_required){
                any_table_required = true;
                tables_required_added_array += $('#table_'+index).text()+ ", ";
            }
        }
    }
    //Remove the last comma and space
    variables_required_added_array = variables_required_added_array.substring(0, variables_required_added_array.length - 2);
    tables_required_added_array = tables_required_added_array.substring(0, tables_required_added_array.length - 2);
    if(any_table_required){
        $('#warnMsgContainer').show();
        var warnMessage = ' <strong>Warning!</strong> Required variables on tables <strong>['+tables_required_added_array+']</strong> were added: <em>['+variables_required_added_array+']</em>.';
        $('#warnMsgContainer').html('<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>'+warnMessage);
        $('#warningMessageVariables').val(warnMessage);

        //remove the last comma
        variables_required_added_array_id = variables_required_added_array_id.substring(0, variables_required_added_array_id.length - 1);
    }else{
        $('#warnMsgContainer').hide();
    }
    return variables_required_added_array_id;
}

/**
 * Update table fields checked in Step 3
 * @param tablefields
 */
function update_table_fields_checks(tablefields){
    //Clean table data
    $('.desTable input[type="checkbox"]').prop('checked', false);
    $('.desTable tr').removeClass('rowSelected');

    //Show tables collapsed when loading content
    $('.collapse').removeClass('in');

    var sop_tablefields = tablefields.split(",");
    for (var row in sop_tablefields) {
        if(sop_tablefields[row] != ""){
            $("#record_id_"+sop_tablefields[row]).prop('checked',true);
            $(".desTable [record_id = "+sop_tablefields[row]+"]").addClass('rowSelected');

            var table = sop_tablefields[row].split('_')[0];
            //we update the counter label
            checkTableCounter(table);
        }
    }
}

/**
 * Update table fields in Step 4
 * @param tablefields
 */
function update_table_fields(tablefields){
    var sop_tablefields = tablefields.split(",");
    for (var row in sop_tablefields) {
        if(sop_tablefields[row] != "") {
            $("[record_id =" + sop_tablefields[row] + "]").show();
            var sop_tablefields_parent = sop_tablefields[row].split("_")[0];
            $('[parent_table_header=' + sop_tablefields_parent + ']').show();
        }
    }
}

function  update_preferred_format(key,preferredFormat){
    if(preferredFormat == "1"){
        $('#'+key).prop('checked',true);
    }
}

/**
 * Update connected lists in Step 3
 * @param downloaders_list
 */
function update_downloaders_list(downloaders_list){
    //Clean list data
    $("#sortable2 li").appendTo('#sortable1');
    $('#sortable1').sortable('option', 'receive')(null, { item: $("#sortable2 li") });

    var downloaders = downloaders_list.split(",");
    for (var row in downloaders) {
        $("#"+downloaders[row]).appendTo('#sortable2');
        //Trigger
        $('#sortable2').sortable('option', 'receive')(null, { item: $("#"+downloaders[row]) });
    }

}

/**
 * Show/hide people depending on the region we have selected
 */
function check_people_region_dragAndDrop(){
    var selectedRegion = $('#dropDown_region').val();
    $('#sortable1 li').each(function(){
        if(selectedRegion != ""){
            if($(this).attr('region') != selectedRegion){
                $(this).hide();
            }else{
                $(this).show();
            }
        }else{
            $(this).show();
        }
    });
}

function getAttributeValueHtml(s){
    if(typeof s == 'string'){
        s = s.replace(/"/g, '&quot;');
        s = s.replace(/'/g, '&apos;');
    }

    if (typeof s == "undefined") {
        s = "";
    }

    return s;
}

/**
 * Add some HTML to hide or show the file on add or delete file
 * @param value, file id
 */
function getFileFieldElement(value){
    if ((typeof value != "undefined") && (value !== "" && value != null)) {
        var html = '<input type="hidden" name="sop_extrapdf" >';
        html += '<button class="external-modules-configure-modal-delete-file" onclick="hideFile('+value+')">Delete File</button>';
        html += '<span id="sop_extrapdf_name"></span>';

        $.post('sop/get-edoc-name.php?edoc='+value, function(data) {
            $("#sop_extrapdf_name").html(" <em>" + data.doc_name + "</em>");
        });
    } else {
        var html = '<input type="file" name="sop_extrapdf" value="' + getAttributeValueHtml(value) + '">';
    }
    $('#sop_extrapdf_div input[name="sop_extrapdf"]').parent().html(html);
}

/**
 * We show the button to upload a new file
 * @param value, file id
 */
function hideFile(value){
    var html = ' <label class="steps_label">Upload PDF</label>';
    html += '<input type="file" name="sop_extrapdf" value="">';
    html += '<input type="hidden" name="sop_extrapdf" value="'+value+'" class="deletedFile">';
    $('#sop_extrapdf_div input[name="sop_extrapdf"]').parent().html(html);
}

/**
 * Delete a file given it's id
 * @param record
 */
function deleteFile(record) {
    $('.deletedFile').each(function() {
        $.post('sop/delete-file.php?edoc='+$(this).val()+'&record='+record, function(data) {
            if (data.status != "success") {
                // failure
                alert("The file was not able to be deleted. "+JSON.stringify(data));
            }
        });

    });
};
/**
 * Copy a data request and redirect to the editor
 * @param record_id
 */
function copy_data_request(url,urlgoto){
    $.ajax({
        type: "POST",
        url: url,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            var record = jQuery.parseJSON(result);
            window.location = urlgoto+"&record="+record;
        }
    });
}
/**
 * Generate the pdf and save it in the DB
 * @param record_id
 */
function generate_pdf(record_id,url,urlgoto){
    $.ajax({
        type: "POST",
        url: url,
        data: "&id="+record_id,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            jsonAjax = jQuery.parseJSON(result);
            // $('[name=html_pdf]').val(jsonAjax);
            $('#record_id').val($('#selectSOP_'+$('[name=optradio]:checked').val()).val());
            window.location = urlgoto+"&record="+record_id;
        }
    });
}

function deleteSOP(recordid){
    $('#succMsgContainer').hide();
    $('#active_id').val(recordid);
    $('#delete-sop-modal').modal('show');
}

function changeVisibility(recordid,visibility){
    $('#succMsgContainer').hide();
    $('#visibility_id').val(recordid);
    $('#visibility').val(visibility);

    var visibility_text = "public";
    if(visibility == '1'){
        visibility_text = "private";
    }
    $('#visibility_msg').html("Are you sure you want to make this SOP <strong>"+visibility_text+"</strong>?");
    $('#visibility-sop-modal').modal('show');
}

/**
 * Ajax call that after refreshes page and displays a message
 * @param data
 * @constructor
 */
function CallAJAXAndShowMessage(data,url,letter,url_window){
    console.log(data);
    console.log(url);
    $.ajax({
        type: "POST",
        url: url,
        data: data,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            window.location = getMessageLetterUrl(url_window, letter);
        }
    });
}

function save_votes(user,region,pi_level,url){
    var data = "&pi_level="+pi_level+"&region="+region+"&user="+user+"&request_id="+$('.dropdown-toggle-custom').find('.dropdown_votes').attr('request')+"&region_vote_values=";
    $('.dropdown-toggle-custom input').each(function() {
        data +=$(this).attr('id')+",";
    });
    $.ajax({
        type: "POST",
        url: url,
        data: data,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            location.reload();
        }
    });
}

function save_status(url,user,region){
    var data = "&region="+region+"&user="+user+"&record_id="+$('.dropdown-toggle-custom').find('.dropdown_votes').attr('record')+"&region_vote_values=";
    $('.dropdown-toggle-custom input').each(function() {
        data +=$(this).attr('id')+",";
    });
    $.ajax({
        type: "POST",
        url: url,
        data: data,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            location.reload();
        }
    });
}

function generate_concepts_list(data){
    $.ajax({
        type: "POST",
        url: "harmonist/concepts/generate_concepts_pdf.php",
        data: data,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {

        }
    });
}

function confirmMakePrivate(record){
    $('#sop-make-private-confirmation').modal('show');
    $('#record').val(record);
}

function confirmDataUpload(concept, user, conceptId, record){
    $('#data-submit-concept').text(conceptId);
    $('#modal-data-upload-confirmation').modal('show');
    $('#upload_record').val(record);
    $('#assoc_concept').val(concept);
    $('#user').val(user);
}

function uploadDataToolkit(data,url){
    //We make the call not asunc to avoid popup blockers
    $.ajax({
        type: "POST",
        url: url,
        data: data,
        async: false,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            var tokendt = JSON.parse(result);
            $('#modal-data-upload-confirmation').modal('hide');
            window.open('https://iedeadata.org/iedea-harmonist/?tokendt='+tokendt, '_blank');
        }
    });
}

function follow_activity(option,userid,record,url){
    var data = "&option="+option+"&userid="+userid+"&record="+record;
    $.ajax({
        type: "POST",
        url: url,
        data: data,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            var button = JSON.parse(result);
            $('#btn_follow').html(button);
        }
    });
}

function CallAJAXAndRedirect(data,url,redirect){
    $.ajax({
        type: "POST",
        url: url,
        data: data,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            window.location = redirect;
        }
    });
}

function checkRow(id){
    var checked = $('#chk_'+id).is(':checked');
    if (!checked) {
        $('#chk_' + id).prop("checked", true);
        $('#sel_'+id).addClass('rowSelected selected');
    } else {
        $('#chk_' + id).prop("checked", false);
        $('#sel_'+id).removeClass('rowSelected selected');
    }
}

function copyStringToClipboard (str) {
    // Create new element
    var el = document.createElement('textarea');
    // Set value (string to be copied)
    el.value = str;
    // Set non-editable to avoid focus and move outside of view
    el.setAttribute('readonly', '');
    el.style = {position: 'absolute', left: '-9999px'};
    document.body.appendChild(el);
    // Select text inside element
    el.select();
    // Copy text to clipboard
    document.execCommand('copy');
    // Remove temporary element
    document.body.removeChild(el);
}

function addDeleteCode(code){
    var url = window.location.href;
    if (url.substring(url.length-1) == "#")
    {
        url = url.substring(0, url.length-1);
    }
    if(window.location.href.match(/(&del=)([0-9a-zA-Z]{32})/)){
        url = url.replace( /(&del=)([0-9a-zA-Z]{32})/, "&del="+code );
    }else{
        url = url + "&del="+code;
    }
    return url;
}

/**
 * Function that changes the icon on the retrieve data configuration
 * @param id
 */
function change_icon(id){
    if($('#'+id).attr('symbol') == 0){
        $('#'+id).attr('symbol',1);
        $('#'+id+"_span").removeClass('fa-plus-square');
        $('#'+id+"_span").addClass('fa-minus-square');
    }else{
        $('#'+id).attr('symbol',0);
        $('#'+id+"_span").removeClass('fa-minus-square');
        $('#'+id+"_span").addClass('fa-plus-square');
    }
}

/**
 * Function to set up the user configuration file
 * @param data
 * @param url
 */
function setUpConfiguration(data,url){
    $.ajax({
        type: "POST",
        url: url,
        data: data,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            //window.location = redirect;
            var data = JSON.parse(result);
            $('#setup_data').text(data.data);
            $('#setup_data_div').show();
            $('#setup_message').show();
        }
    });
}

/**
 * function to download the user configuration file
 */
function downloadConfiguration(){
    filename = "configuration.php";
    text =  $('#setup_data').text();
    var element = document.createElement('a');
    element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
    element.setAttribute('download', filename);

    element.style.display = 'none';
    document.body.appendChild(element);

    element.click();

    document.body.removeChild(element);
}

/**
 * Metric function to show or display the donuts
 * @param values_array
 * @returns {boolean}
 */
function isDonutEmpty(values_array){
    var returnValue = true;
    Object.keys(values_array).forEach(function (index) {
        if(values_array[index] != "0"){
            returnValue = false;
        }
    });
    return returnValue;
}

function viewAllVotes(request_id,url){
    $.ajax({
        type: "POST",
        url: url,
        data: "request_id="+request_id,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            var data = JSON.parse(result);
            $('#allvotes').html(data);
            $('#hub_view_votes').modal('show');
        }
    });
}

function viewMixedVotes(request_id,region_id,url){
    $.ajax({
        type: "POST",
        url: url,
        data: "request_id="+request_id+"&region_id="+region_id,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            var data = JSON.parse(result);
            $('#mixedvotes').html(data);
            $('#hub_view_mixed_votes').modal('show');
        }
    });
}

function viewAllVotesData(record_id){
    $.ajax({
        type: "POST",
        url: "sop/sop_view_all_votes_AJAX.php",
        data: "record_id="+record_id,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            var data = JSON.parse(result);
            $('#allvotes').html(data);
            $('#hub_view_votes').modal('show');
        }
    });
}


function deleteDataRequest(value){
    $('#admin-modal-delete').modal('show');
    $('#index_modal_delete').val(value);

}

function changeStatus(current_region_status,status,region,notes,region_update_ts,modal){
    $('#'+modal).modal('show');
    $('#region').val(region);
    $('#status_record').val(status);
    $('#data_response_notes').val(notes);
    $('#region_update_ts').html(region_update_ts);

    $("#changeStatus .dropdown-menu-custom li").parents('.dropdown').find('.dropdown-toggle').html(current_region_status+'<span class="caret" style="float: right;margin-top:8px"></span>')
}

function selectTag(value){
    var table = $('#table_archive').DataTable();

    if($('#tag_'+value).hasClass('dt-button')){
        $('#tag_'+value).removeClass('dt-button');
        $('#tag_'+value).addClass('dt-button-info');
    }else{
        $('#tag_'+value).addClass('dt-button');
        $('#tag_'+value).removeClass('dt-button-info');
    }

    var filter_column_array = new Array();
    $( ".dt-button-info" ).each(function() {
        filter_column_array.push($(this).find("span").text());
    });

    var filter_search = "";
    var column = 1;
    if(filter_column_array != undefined){
        Object.keys(filter_column_array).forEach(function (filter) {
            filter_search += "(?=.*"+filter_column_array[filter]+")";
        });
        table.column(column).search(filter_search,true, false).draw();
    }else{
        table.column(column).search("",true, false).draw();
    }
}

function runPubsCron(url){
    $('#pubsSpinner').show();
    $('#btndataPubForm').attr('disabled','disabled');
    $.ajax({
        type: "POST",
        url: url,
        data:"isAdmin="+1,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            $('#pubsSpinner').hide();
            $('#btndataPubForm').removeAttr('disabled');
            $('#modal-publications-confirmation').hide();
            window.location.href = getMessageLetterUrl(window.location.href, "P");
        }
    });
}

function installMetadata(fields,url) {
    $("#metadataWarning").removeClass("install-metadata-box-danger");
    $("#metadataWarning").addClass("install-metadata-box-warning");
    $("#metadataWarning").html("<em class='fa fa-spinner fa-spin'></em> Installing...");
    $.post(url, { fields: fields }, function(data) {
        $("#metadataWarning").removeClass("install-metadata-box-warning");
        if (!data.match(/Exception/)) {
            $("#metadataWarning").addClass("install-metadata-box-success");
            $("#metadataWarning").html("<i class='fa fa-check' aria-hidden='true'></i> Installation Complete");
            setTimeout(function() {
                $("#metadataWarning").fadeOut(500);
            }, 3000);
        } else {
            $("#metadataWarning").addClass("install-metadata-box-danger");
            $("#metadataWarning").html("Error in installation! Metadata not updated. "+JSON.stringify(data));
        }
    });
}

function installRepeatingForms(fields,url) {
    $("#formsWarning").removeClass("install-metadata-box-danger");
    $("#formsWarning").addClass("install-metadata-box-warning");
    $("#formsWarning").html("<em class='fa fa-spinner fa-spin'></em> Installing...");
    $.post(url, { fields: fields }, function(data) {
        $("#formsWarning").removeClass("install-metadata-box-warning");
        if (!data.match(/Exception/)) {
            $("#formsWarning").addClass("install-metadata-box-success");
            $("#formsWarning").html("<i class='fa fa-check' aria-hidden='true'></i> Installation Complete");
            setTimeout(function() {
                $("#formsWarning").fadeOut(500);
            }, 3000);
        } else {
            $("#formsWarning").addClass("install-metadata-box-danger");
            $("#formsWarning").html("Error in installation! Repeating Forms not updated. "+JSON.stringify(data));
        }
    });
}

function startUnitTest(url){
    $('#unitTestbtn').prop('disabled',true);
    $('#unitTestMsgContainer').show();
    $.ajax({
        type: "POST",
        url: url,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            paramValue = jQuery.parseJSON(result);
            var Newulr = getParamUrl(window.location.href,paramValue);
            window.location.href = Newulr;
        }
    });
}

function getParamUrl(url, newParam){
    if (url.substring(url.length-1) == "#")
    {
        url = url.substring(0, url.length-1);
    }

    if(url.match(/(&test=)/)){
        var oldParam = url.split("&test=")[1];
        url = url.replace( oldParam, newParam );
    }else{
        url = url + "&test="+newParam;
    }
    return url;
}

function exploreDataToken(data,url,url_relocation){
    $.ajax({
        type: "POST",
        url: url,
        data: data,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            var tokendt = JSON.parse(result);
            window.location = url_relocation+"&option=dab&tokendab="+tokendt;
        }
    });
}