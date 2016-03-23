'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	TextUtils = require('modules/Core/js/utils/Text.js'),
	Utils = require('modules/Core/js/utils/Common.js'),
	
	Ajax = require('modules/Core/js/Ajax.js'),
	App = require('modules/Core/js/App.js'),
	UserSettings = require('modules/Core/js/Settings.js'),
	
	Settings = require('modules/Auth/js/Settings.js'),
	
	CAbstractScreenView = require('modules/Core/js/views/CAbstractScreenView.js'),
	CForgotView = require('modules/Auth/js/views/CForgotView.js'),
	CLoginView = require('modules/Auth/js/views/CLoginView.js'),
	CRegisterView = require('modules/Auth/js/views/CRegisterView.js')
;

/**
 * @constructor
 */
function CWrapLoginView()
{
	CAbstractScreenView.call(this);
	
	this.browserTitle = ko.observable(TextUtils.i18n('AUTH/HEADING_BROWSER_TAB'));
	
	this.bSocialInviteMode = typeof Utils.getRequestParam('invite-auth') === 'string';
	this.socialInviteTitle = TextUtils.i18n('AUTH/HEADING_SOCIAL_INVITE', {'SITENAME': UserSettings.SiteName});
	this.socialInviteText = TextUtils.i18n('AUTH/INFO_SOCIAL_INVITE');
	
	this.rtl = ko.observable(UserSettings.isRTL);
	
	this.bAllowRegistration = Settings.AllowRegistration;
	this.bAllowResetPassword = Settings.AllowResetPassword;
	
	this.oLoginView = new CLoginView();
	if (this.bAllowRegistration)
	{
		this.oRegisterView = new CRegisterView();
	}
	if (this.bAllowResetPassword)
	{
		this.oForgotView = new CForgotView();
	}
	this.gotoForgot = this.bAllowResetPassword ? this.oForgotView.gotoForgot : ko.observable(false);
	this.gotoRegister = ko.observable(false);

	this.emailVisible = this.oLoginView.emailVisible;
	this.loginVisible = this.oLoginView.loginVisible;
	this.sInfoText = Settings.InfoText;

	this.aLanguages = UserSettings.LanguageList;
	this.currentLanguage = ko.observable(UserSettings.Language);
	
	this.bAllowChangeLanguage = Settings.AllowChangeLanguage && !App.isMobile();
	this.bUseFlagsLanguagesView = Settings.UseFlagsLanguagesView;

	this.sCustomLogoUrl = Settings.CustomLogoUrl;
	
//	if (AfterLogicApi.runPluginHook)
//	{
//		AfterLogicApi.runPluginHook('view-model-defined', [this.__name, this]);
//	}
}

_.extendOwn(CWrapLoginView.prototype, CAbstractScreenView.prototype);

CWrapLoginView.prototype.ViewTemplate = 'Auth_WrapLoginView';
CWrapLoginView.prototype.__name = 'CWrapLoginView';

CWrapLoginView.prototype.onShow = function ()
{
	this.oLoginView.onShow();
};

CWrapLoginView.prototype.onBind = function ()
{
	this.oLoginView.onBind();
};

/**
 * @param {string} sLanguage
 */
CWrapLoginView.prototype.changeLanguage = function (sLanguage)
{
	if (sLanguage && this.bAllowChangeLanguage)
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

module.exports = new CWrapLoginView();
