$(document).ready(function () {
    $('#selectUserListDataTable').dataTable({
        "bPaginate": false,
        "bLengthChange": false,
        "bFilter": true,
        "bInfo": false
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
                users_info += "<li >"+$('#'+user_id).attr("user-data")+"</li>";
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
});

function removeUserFromDataDownloads(url){
    $("#dialogWarningDelete").dialog('close');
    $("#hubSpinner").dialog({modal:true, width:400});

    let data = "&checked_values_remove_user="+$("#checked_values_remove_user").val();
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