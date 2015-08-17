$(function() {

	$('#IdUsersNewUserButton').click(function(){
		document.location = AP_INDEX + '?new&tab=users';
	});

	$('#IdUsersInviteUserButton').click(function(){
		document.location = AP_INDEX + '?invite&tab=users';
	});

	$('#IdUsersDeleteButton').click(function(){
		var oChecked = $('#table_form input:checkbox[name="chCollection[]"]:checked');
		if (0 < oChecked.length)
		{
			if (confirm(Lang.DeleteUserConfirm)) {
				$('#table_form #action').val('delete');
				$('#table_form').submit();
			}
		}
		else
		{
			OnlineMsgError(Lang.NoUsersSelected);
		}
	});
	$('#IdUsersDisableUserButton').click(function(){
		var oChecked = $('#table_form input:checkbox[name="chCollection[]"]:checked');
		if (0 < oChecked.length)
		{
			$('#table_form #action').val('disable');
			$('#table_form').submit();
		}
		else
		{
			OnlineMsgError(Lang.NoUsersSelected);
		}

	});
	$('#IdUsersEnableUserButton').click(function(){
		var oChecked = $('#table_form input:checkbox[name="chCollection[]"]:checked');
		if (0 < oChecked.length)
		{
			$('#table_form #action').val('enable');
			$('#table_form').submit();
		}
		else
		{
			OnlineMsgError(Lang.NoUsersSelected);
		}
	});

	var oForm = $('#main_form');
	if (oForm && 0 < oForm.length && 'edit' === oForm.find('[name="QueryAction"]').val())
	{
		oForm.find('.wm_secondary_info').hide();
	}
	
	var
		bAllowWebmail = typeof(AllowWebMail) !== 'undefined' ? (AllowWebMail === '1') : false,
		bIsAType = typeof(IsAType) !== 'undefined' ? (IsAType === '1') : false;
		koAP = function () {
			this.chAllowMail = ko.observable(bAllowWebmail);
			this.allowWebMail = ko.observable(bAllowWebmail && bIsAType);
		}
	;
	
	ko.applyBindings(new koAP());
});
