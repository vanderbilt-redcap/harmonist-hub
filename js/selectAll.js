function checkAll(option) {
    if ($("[name='chkAll_"+option+"']").prop("checked")) {
        $("[name='chkAll_"+option+"']").prop("checked", true);
        $("[name='chkAll_parent_"+option+"']").addClass('rowSelected');
    } else {
        $("[name='chkAll_"+option+"']").prop("checked", false);
        $("[name='chkAll_parent_"+option+"']").removeClass('rowSelected');
    }

    //Update Projects Counter
    updateCounterLabel();
}

function checkAllText(option) {
    if ($("[name='chkAll_"+option+"']").prop("checked")) {
        $("[name='chkAll_"+option+"']").prop("checked", false);
        $("[name='chkAll_parent_"+option+"']").removeClass('rowSelected');
    } else {
        $("[name='chkAll_"+option+"']").prop("checked", true);
        $("[name='chkAll_parent_"+option+"']").addClass('rowSelected');
    }

    //Update Projects Counter
    updateCounterLabel();
}

function updateCounterLabel(){
    var count = $('.rowSelected').length;
    if(count>0){
        $("#pid_total").text(count);
    }else{
        $("#pid_total").text("0");
    }
}