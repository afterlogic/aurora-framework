'use strict';

var
	ko = require('knockout'),
	
	TextUtils = require('core/js/utils/Text.js'),
	
	Ajax = require('core/js/Ajax.js'),
	App = require('core/js/App.js'),
	Api = require('core/js/Api.js'),
	Screens = require('core/js/Screens.js'),
	Routing = require('core/js/Routing.js'),
	ModulesManager = require('core/js/ModulesManager.js'),
	
	Popups = require('core/js/Popups.js'),
	ConfirmPopup = require('core/js/popups/ConfirmPopup.js'),
	ChangePasswordPopup = ModulesManager.run('ChangePassword', 'getChangePasswordPopup'),
	CreateAccountPopup = ModulesManager.run('Mail', 'getCreateAccountPopup'),
	
	Settings = require('modules/ChangePassword/js/Settings.js')
;

/**
 * @constructor
 */
function CResetPasswordView()
{
	this.oDefaultAccount = App.defaultAccount();
	this.showResetPasswordButton = ko.computed(function () {
		return !this.oDefaultAccount.allowMail();
	}, this);
	this.resetPasswordButtonText = ko.computed(function () {
		if (this.oDefaultAccount.passwordSpecified())
		{
			return TextUtils.i18n('LOGIN/BUTTON_RESET_PASSWORD');
		}
		else
		{
			return TextUtils.i18n('SETTINGS/ACCOUNT_PROPERTIES_SET_PASSWORD');
		}
	}, this);
	var aHintSetPassword = TextUtils.i18n('SETTINGS/MOBILE_HINT_SET_PASSWORD').split(/%STARTLINK%|%ENDLINK%/);
	this.sHintSetPassword1 = aHintSetPassword.length > 0 ? aHintSetPassword[0] : '';
	this.sHintSetPassword2 = aHintSetPassword.length > 1 ? aHintSetPassword[1] : '';
	this.sHintSetPassword3 = aHintSetPassword.length > 2 ? aHintSetPassword[2] : '';
}

CResetPasswordView.prototype.ViewTemplate = 'ChangePassword_ResetPasswordView';

CResetPasswordView.prototype.configureMail = function ()
{
	if (this.oDefaultAccount && !this.oDefaultAccount.allowMail() && CreateAccountPopup)
	{
		App.Screens.showPopup(CreateAccountPopup, [Enums.AccountCreationPopupType.ConnectToMail, this.oDefaultAccount.email()]);
	}
};

CResetPasswordView.prototype.resetPassword = function ()
{
	if (Settings.ResetPassHash === '' && !this.oDefaultAccount.passwordSpecified())
	{
		Popups.showPopup(ConfirmPopup, [
			TextUtils.i18n('SETTINGS/ACCOUNTS_RESET_PASSWORD_POPUP_DESC', {'EMAIL': this.oDefaultAccount.email()}),
			_.bind(this.onResetPasswordPopupAnswer, this),
			this.oDefaultAccount.passwordSpecified() ? TextUtils.i18n('SETTINGS/ACCOUNTS_RESET_PASSWORD_POPUP_TITLE') : TextUtils.i18n('SETTINGS/ACCOUNTS_SET_PASSWORD_POPUP_TITLE'),
			TextUtils.i18n('MAIN/BUTTON_SEND'),
			TextUtils.i18n('MAIN/BUTTON_CANCEL')
		]);
	}
	else
	{
		Popups.showPopup(ChangePasswordPopup, [false, this.oDefaultAccount.passwordSpecified(), function () { 
			this.oDefaultAccount.passwordSpecified(true); 
//			if (AfterLogicApi.runPluginHook)
//			{
//				AfterLogicApi.runPluginHook('api-mail-on-password-specified-success', [this.__name, this]);
//			}	
		}]);
	}
};

/**
 * @param {boolean} bReset
 */
CResetPasswordView.prototype.onResetPasswordPopupAnswer = function (bReset)
{
	if (bReset)
	{
		Screens.showLoading(TextUtils.i18n('COMPOSE/INFO_SENDING'));
		Ajax.send('Mail', 'AccountResetPassword', {'UrlHash': Routing.currentHash()}, this.onResetPassword, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CResetPasswordView.prototype.onResetPassword = function (oResponse, oRequest)
{
	Screens.hideLoading();
	if (!oResponse.Result)
	{
		Api.showErrorByCode(oResponse);
	}
	else
	{
		Screens.showReport(TextUtils.i18n('SETTINGS/ACCOUNTS_RESET_PASSWORD_INFO_AFTER'));
	}
};

module.exports = new CResetPasswordView();
