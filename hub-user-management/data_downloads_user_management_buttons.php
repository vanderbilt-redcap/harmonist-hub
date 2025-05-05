<div id="btn_wrapper" class="container-fluid p-y-1" style="float:left;">
    <div id="selectAll_wrapper" style="float:left;margin-top: 10px;margin-left: 10px;">
        <input type="checkbox" id="ckb_user" name="chkAll_user" onclick="checkAll('user');" style="cursor: pointer;">
        <a href="#" style="cursor: pointer;font-size: 14px;font-weight: normal;" onclick="checkAllText('user');">Select All</a>
    </div>
    <div id="admin_wrapper" style="float:left;padding-left: 15px;
    padding-top: 11px;">
        <input type="checkbox" id="admin_only" name="admin_only" style="cursor: pointer;float: left;margin-right: 5px;box-shadow: none;">
        <label for="admin_only" style="font-weight: normal;margin-top: 1px;">Admins Only</label>
    </div>
    <button type="button" class="btn btn-danger float-right btnClassConfirm" id="remove_user" style="margin-right: 10px;">Remove User</button>
</div>