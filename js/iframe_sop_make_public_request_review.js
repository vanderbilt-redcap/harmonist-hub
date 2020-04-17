
$('[name=submit-btn-saverecord]').click(function() {
    var record = window.frameElement.getAttribute("record");
    $.ajax({
        type: "POST",
        url: window.frameElement.getAttribute("approot")+'/sop/sop_make_public_request_AJAX.php',
        data: "&record="+record,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            parent.location.search = 'option=smn&record='+record+'&message=P';
        }
    });
});