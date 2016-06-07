'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	TextUtils = require('modules/Core/js/utils/Text.js'),
	UrlUtils = require('modules/Core/js/utils/Url.js'),
	
	Ajax = require('modules/Core/js/Ajax.js'),
	App = require('modules/Core/js/App.js'),
	UserSettings = require('modules/Core/js/Settings.js'),
	
	Settings = require('modules/%ModuleName%/js/Settings.js'),
	
	CAbstractScreenView = require('modules/Core/js/views/CAbstractScreenView.js'),
	CForgotView = require('modules/%ModuleName%/js/views/CForgotView.js'),
	CLoginView = require('modules/%ModuleName%/js/views/CLoginView.js'),
	CRegisterView = require('modules/%ModuleName%/js/views/CRegisterView.js')
;

/**
 * @constructor
 */
function CWrapLoginView()
{
	CAbstractScreenView.call(this);
	
	this.browserTitle = ko.observable(TextUtils.i18n('%MODULENAME%/HEADING_BROWSER_TAB'));
	
	this.bSocialInviteMode = typeof UrlUtils.getRequestParam('invite-auth') === 'string';
	this.socialInviteTitle = TextUtils.i18n('%MODULENAME%/HEADING_SOCIAL_INVITE', {'SITENAME': UserSettings.SiteName});
	this.socialInviteText = TextUtils.i18n('%MODULENAME%/INFO_SOCIAL_INVITE');
	
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

CWrapLoginView.prototype.ViewTemplate = '%ModuleName%_WrapLoginView';
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
