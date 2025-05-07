$(document).ready(function () {
    $('#selectUserListDataTable').dataTable({
        "bPaginate": false,
        "bLengthChange": false,
        "bFilter": true,
        "bInfo": false,
        "order": {
            handler: false
        }
    });

    //when any of the filters is called upon change datatable data
    $('#admin_only, #selecUserListDataTable_filter').change( function() {
        $searchTerm = "";
        if( $('#admin_only').is(":checked")){
            $searchTerm = "Admin";
        }
        var table = $('#selectUserListDataTable').DataTable();
        table.columns().search($searchTerm).draw();
    } );

    $('#selectUserListDataTable_filter').insertAfter($('#admin_wrapper'));
    $('#selectUserListDataTable_filter').css("padding-left", "25%");
    $('#selectUserListDataTable_filter').css("margin-top", "10px");
    $('#selectUserListDataTable_filter').css("float", "left");

    $('#remove_user').click(function () {
        $('user_remove_list').html("");

        let checked_values = [];
        $("input[nameCheck='tablefields[]']:checked").each(function() {
            checked_values.push($(this).val());
        });

        if(checked_values.length != 0) {
            $('#checked_values_remove_user').val(checked_values);
            let users_info = "<ul>";
            checked_values.forEach((user_id) => {
                users_info += "<li style='padding: 5px;'>"+$('#'+user_id).attr("user-data")+"</li>";
            });
            users_info += "<ul>";
            $('#user_remove_list').html(users_info);
            $("#removeUsersForm").dialog({
                width: 700,
                modal: true,
                enableRemoteModule: true
            }).prev(".ui-dialog-titlebar").css("background", "#f8d7da").css("color", "#721c24")

        }else{
            $("#dialogWarning").dialog({modal:true, width:300}).prev(".ui-dialog-titlebar").css("background","#f8d7da").css("color","#721c24");
        }
        return false;
    });

    $('#add_user').click(function () {
        $('#new_user').val("");
        $('user_add_list').html("");

        let checked_values = [];
        let checked_missing = [];
        $("input[nameCheck='tablefields[]']:checked").each(function() {
            checked_values.push($(this).val());
            checked_missing.push($(this).attr("username-missing"));
        });

        if(checked_values.length != 0) {
            $('#checked_values_add_user').val(checked_values);
            $('#checked_values_missing_user').val(checked_missing);
            let users_info = "<ul>";
            checked_values.forEach((user_id) => {
                let error_list_user = $('#error-list-'+user_id).attr('error-data').split(";");
                let error_list = "<ul>";
                error_list_user.forEach((errorText) => {
                    error_list += "<li>"+errorText+"</li>";
                });
                error_list += "</ul>";
                users_info += '<li style="list-style-type: none;padding: 10px;">' + $('#' + user_id).attr("user-data");
                users_info += '<a id="data-toggle'+user_id+'" tabIndex="0" role="button" class="info-toggle" data-html="true" data-container="body" data-toggle="tooltip" data-trigger="hover" data-placement="right" style="outline: none;" title="'+error_list+'"><i class="fas fa-info-circle fa-fw" style="color:#0d6efd;margin-left:5px;" aria-hidden="true"></i></a>';
                let userNameMissing = $('#error-list-'+user_id).attr('username-missing');
                if(userNameMissing) {
                    users_info += '<input type="text" style="margin-top: 5px;" class="form-control" onkeyup="checkUserName(' + user_id + ')" name="new_user" id="user-name-' + user_id + '"></li>' +
                    ' <div id="user-list-' + user_id + '"></div>';
                }else{
                    users_info += '<input type="text" class="form-control" id="user-name-' + user_id + '" value="'+$('#'+user_id).attr('user-name')+'" disabled></li>';
                }
            });
            users_info += "<ul>";
            $('#user_add_list').html(users_info);
            $("#addUsersForm").dialog({
                width: 700,
                modal: true,
                enableRemoteModule: true
            });

        } else {
            $("#dialogWarning").dialog({
                modal: true,
                width: 300
            }).prev(".ui-dialog-titlebar").css("background", "#f8d7da").css("color", "#721c24");
        }
        return false;
    });
});

function manageUserFromDataDownloads(url,option) {
    $("#dialogWarningDelete").dialog('close');
    $("#hubSpinner").dialog({modal:true, width:400});

    let data = "&checked_values_user="+$("#checked_values_"+option+"_user").val()+"&option="+option;
    if(option == "add"){
        data += "&checked_values_missing_user="+$("#checked_values_missing_user").val();
        let userNames = [];
        $("#addUsersForm input[type=text]").each(function (el) {
            userNames.push($(this).val());
        });
        data += "&usernames="+userNames;
    }
    $.ajax({
        type: "POST",
        url: url,
        data: data,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            var message = jQuery.parseJSON(result)['message'];
            window.location = getMessageLetterUrl(window.location.href, message);
            $("#hubSpinner").dialog('close');
        }
    });
    return false;
}

function addUserFromDataDownloads(url) {
    $("#dialogWarningDelete").dialog('close');
    $("#hubSpinner").dialog({modal:true, width:400});

    let data = "&checked_values_user="+$("#checked_values_user").val();
    $.ajax({
        type: "POST",
        url: url,
        data: data,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            var message = jQuery.parseJSON(result)['message'];
            window.location = getMessageLetterUrl(window.location.href, message);
            $("#hubSpinner").dialog('close');
        }
    });
    return false;
}

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