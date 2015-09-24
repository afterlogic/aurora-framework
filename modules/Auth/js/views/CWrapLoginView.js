'use strict';

var
	ko = require('knockout'),
	
	Utils = require('core/js/utils/Common.js'),
	TextUtils = require('core/js/utils/Text.js'),
	UserSettings = require('core/js/Settings.js'),
	Ajax = require('core/js/Ajax.js'),
	
	Settings = require('modules/Auth/js/Settings.js'),
	CLoginView = require('modules/Auth/js/views/CLoginView.js'),
	CRegisterView = require('modules/Auth/js/views/CRegisterView.js'),
	CForgotView = require('modules/Auth/js/views/CForgotView.js')
;

/**
 * @constructor
 */
function CWrapLoginView()
{
	this.bSocialInviteMode = typeof Utils.getRequestParam('invite-auth') === 'string';
	this.socialInviteTitle = TextUtils.i18n('LOGIN/SOCIAL_INVITE_TITLE', {'SITENAME': UserSettings.SiteName});
	this.socialInviteText = TextUtils.i18n('LOGIN/SOCIAL_INVITE_TEXT');
	
	this.rtl = ko.observable(UserSettings.isRTL);
	
	this.allowRegistration = Settings.AllowRegistration;
	this.allowPasswordReset = Settings.AllowPasswordReset;
	
	this.oLoginView = new CLoginView();
	if (this.allowRegistration)
	{
		this.oRegisterView = new CRegisterView();
	}
	if (this.allowPasswordReset)
	{
		this.oForgotView = new CForgotView();
	}
	this.gotoForgot = this.allowPasswordReset ? this.oForgotView.gotoForgot : ko.observable(false);
	this.gotoRegister = ko.observable(false);

	this.emailVisible = this.oLoginView.emailVisible;
	this.loginVisible = this.oLoginView.loginVisible;
	this.loginDescription = ko.observable(Settings.LoginDescription);

	this.aLanguages = UserSettings.Languages;
	this.currentLanguage = ko.observable(UserSettings.DefaultLanguage);
	
	this.allowLanguages = ko.observable(Settings.AllowLanguageOnLogin);
	this.viewLanguagesAsDropdown = ko.observable(!Settings.FlagsLangSelect);

	this.loginCustomLogo = ko.observable(Settings.LoginStyleImage);
	
//	if (AfterLogicApi.runPluginHook)
//	{
//		AfterLogicApi.runPluginHook('view-model-defined', [this.__name, this]);
//	}
}

CWrapLoginView.prototype.ViewTemplate = 'Auth_WrapLoginView';
CWrapLoginView.prototype.__name = 'CWrapLoginView';

CWrapLoginView.prototype.onShow = function ()
{
	if (this.oLoginView.onShow)
	{
		this.oLoginView.onShow();
	}
};

CWrapLoginView.prototype.onApplyBindings = function ()
{
	if (this.oLoginView.onApplyBindings)
	{
		this.oLoginView.onApplyBindings();
	}
};

/**
 * @param {string} sLanguage
 */
CWrapLoginView.prototype.changeLanguage = function (sLanguage)
{
	if (sLanguage && this.allowLanguages())
	{
		this.currentLanguage(sLanguage);
		this.oLoginView.changingLanguage(true);

		Ajax.send({
			'Action': 'SystemUpdateLanguageOnLogin',
			'Language': sLanguage
		}, function () {
			window.location.reload();
		}, this);
	}
};

module.exports = CWrapLoginView;