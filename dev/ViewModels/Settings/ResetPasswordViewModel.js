
/**
 * @constructor
 */
function CResetPasswordViewModel()
{
	this.oDefaultAccount = AppData.Accounts.getDefault();
	this.showResetPasswordButton = ko.computed(function () {
		return !this.oDefaultAccount.allowMail();
	}, this);
	this.resetPasswordButtonText = ko.computed(function () {
		if (this.oDefaultAccount.passwordSpecified())
		{
			return Utils.i18n('LOGIN/BUTTON_RESET_PASSWORD');
		}
		else
		{
			return Utils.i18n('SETTINGS/ACCOUNT_PROPERTIES_SET_PASSWORD');
		}
	}, this);
	var aHintSetPassword = Utils.i18n('SETTINGS/MOBILE_HINT_SET_PASSWORD').split(/%STARTLINK%|%ENDLINK%/);
	this.sHintSetPassword1 = aHintSetPassword.length > 0 ? aHintSetPassword[0] : '';
	this.sHintSetPassword2 = aHintSetPassword.length > 1 ? aHintSetPassword[1] : '';
	this.sHintSetPassword3 = aHintSetPassword.length > 2 ? aHintSetPassword[2] : '';
}

CResetPasswordViewModel.prototype.configureMail = function ()
{
	App.Api.showConfigureMailPopup();
};

CResetPasswordViewModel.prototype.resetPassword = function ()
{
	var oDefaultAccount = AppData.Accounts.getDefault();
	
	if (App.sResetPassHash === '' && !oDefaultAccount.passwordSpecified())
	{
		App.Screens.showPopup(ConfirmPopup, [
			Utils.i18n('SETTINGS/ACCOUNTS_RESET_PASSWORD_POPUP_DESC', {'EMAIL': oDefaultAccount.email()}),
			_.bind(this.onResetPasswordPopupAnswer, this),
			oDefaultAccount.passwordSpecified() ? Utils.i18n('SETTINGS/ACCOUNTS_RESET_PASSWORD_POPUP_TITLE') : Utils.i18n('SETTINGS/ACCOUNTS_SET_PASSWORD_POPUP_TITLE'),
			Utils.i18n('MAIN/BUTTON_SEND'),
			Utils.i18n('MAIN/BUTTON_CANCEL')
		]);
	}
	else
	{
		App.Api.showChangeDefaultAccountPasswordPopup();
	}
};

/**
 * @param {boolean} bReset
 */
CResetPasswordViewModel.prototype.onResetPasswordPopupAnswer = function (bReset)
{
	if (bReset)
	{
		App.Api.showLoading(Utils.i18n('COMPOSE/INFO_SENDING'));
		App.Ajax.send({'Action': 'AccountResetPassword', 'UrlHash': App.Routing.currentHash()}, this.onResetPassword, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CResetPasswordViewModel.prototype.onResetPassword = function (oResponse, oRequest)
{
	App.Api.hideLoading();
	if (!oResponse.Result)
	{
		App.Api.showErrorByCode(oResponse);
	}
	else
	{
		App.Api.showReport(Utils.i18n('SETTINGS/ACCOUNTS_RESET_PASSWORD_INFO_AFTER'));
	}
};