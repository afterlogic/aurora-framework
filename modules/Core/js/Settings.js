'use strict';

var
	$ = require('jquery'),
	ko = require('knockout'),
	
	UrlUtils = require('modules/Core/js/utils/Url.js'),
	Types = require('modules/Core/js/utils/Types.js'),
	
	AppData = window.auroraAppData,
	
	bRtl = $('html').hasClass('rtl')
;

module.exports = {
	AllowChangeSettings: AppData.App ? !!AppData.App.AllowUsersChangeInterfaceSettings : false,
	AllowClientDebug: !!AppData.ClientDebug,
	AllowDesktopNotifications: AppData.User ? !!AppData.User.DesktopNotifications : true,
	AllowIosProfile: AppData.App ? !!AppData.App.AllowIosProfile : false, // ? IosDetectOnLogin
	AllowMobile: !!AppData.AllowMobile,
	AllowPrefetch: AppData.App ? !!AppData.App.AllowPrefetch : true,
	AttachmentSizeLimit: AppData.App ? Types.pInt(AppData.App.AttachmentSizeLimit) : 0, // Mail, Helpdesk
	AutoRefreshIntervalMinutes: AppData.User ? Types.pInt(AppData.User.AutoRefreshInterval) : 1,
	CsrfToken: Types.pString(AppData.Token),
	CustomLogoutUrl: AppData.App ? Types.pString(AppData.App.CustomLogoutUrl) : '',
	DateFormat: AppData.User ? Types.pString(AppData.User.DefaultDateFormat) : 'DD/MM/YYYY',
	DateFormatList: AppData.App && $.isArray(AppData.App.DateFormats) ? AppData.App.DateFormats : [],
	EntryModule: 'Mail', // AppData.App.DefaultTab
	GoogleAnalyticsAccount: AppData.App ? Types.pString(AppData.App.GoogleAnalyticsAccount) : '',
	IsDemo: AppData.User ? !!AppData.User.IsDemo : false,
	IsMailsuite: !!AppData.IsMailsuite,
	IsMobile: !!AppData.IsMobile,
	IsRTL: bRtl,
	Language: AppData.User ? Types.pString(AppData.User.DefaultLanguage) : (AppData.App ? Types.pString(AppData.App.DefaultLanguage) : 'English'),
	LanguageList: AppData.App && $.isArray(AppData.App.Languages) ? AppData.App.Languages : [],
	LastErrorCode: Types.pString(AppData.LastErrorCode),
	LogoUrl: Types.pString(AppData.AppStyleImage),
	RedirectToHelpdesk: !!AppData.HelpdeskRedirect, // todo
	ShowQuotaBar: AppData.App ? !!AppData.App.ShowQuotaBar : true, // Files module, Mail module
	SiteName: AppData.App ? Types.pString(AppData.App.SiteName) : 'AfterLogic WebMail',
	SocialName: AppData.User ? Types.pString(AppData.User.SocialName) : '', // Mail module
	SyncIosAfterLogin: AppData.App ? !!AppData.App.IosDetectOnLogin : false, // ? AllowIosProfile
	TenantName: Types.pString(AppData.TenantName) || UrlUtils.getRequestParam('tenant') || '0804e764',
	Theme: AppData.User ? Types.pString(AppData.User.DefaultTheme) : (AppData.App ? Types.pString(AppData.App.DefaultTheme) : 'Default'),
	ThemeList: AppData.App && $.isArray(AppData.App.Themes) ? AppData.App.Themes : [],
	timeFormat: ko.observable(AppData.User ? Types.pString(AppData.User.DefaultTimeFormat) : '0'), // 0 - 24, 1 - 12
	UserId: AppData.User ? Types.pInt(AppData.User.IdUser) : 0,
	
	// unused, should be removed
	AllowBodySize: AppData.App ? !!AppData.App.AllowBodySize : false,
	DefaultLanguageShort: AppData.User ? Types.pString(AppData.User.DefaultLanguageShort) : (AppData.App ? Types.pString(AppData.App.DefaultLanguageShort) : 'en'),
	DemoWebMail: AppData.App ? !!AppData.App.DemoWebMail : false,
	MaxBodySize: AppData.App ? Types.pInt(AppData.App.MaxBodySize) : 0,
	MaxSubjectSize: AppData.App ? Types.pInt(AppData.App.MaxSubjectSize) : 0,
	ServerUrlRewriteBase: AppData.App ? Types.pString(AppData.App.ServerUrlRewriteBase) : '',
	ServerUseUrlRewrite: AppData.App ? Types.pString(AppData.App.ServerUseUrlRewrite) : '',
	AllowVoice: AppData.User ? !!AppData.User.AllowVoice : true,
	CanLoginWithPassword: AppData.User ? !!AppData.User.CanLoginWithPassword : true,
	EmailNotification: AppData.User ? Types.pString(AppData.User.EmailNotification) : '',
	LastLogin: AppData.User ? Types.pInt(AppData.User.LastLogin) : 0,
	LoginsCount: AppData.User ? Types.pInt(AppData.User.LoginsCount) : 0,
	SipCallerID: AppData.User ? Types.pString(AppData.User.SipCallerID) : '',
	SipEnable: AppData.User ? !!AppData.User.SipEnable : true,
	TwilioEnable: AppData.User ? !!AppData.User.TwilioEnable : true,
	TwilioNumber: AppData.User ? Types.pInt(AppData.User.TwilioNumber) : 0,
	
	update: function (iAutoRefreshIntervalMinutes, sDefaultTheme, sLanguage, sTimeFormat, sDesktopNotifications) {
		this.AutoRefreshIntervalMinutes = iAutoRefreshIntervalMinutes;
		this.Theme = sDefaultTheme;
		this.Language = sLanguage;
		this.timeFormat(sTimeFormat);
		this.AllowDesktopNotifications = sDesktopNotifications === '1';
	}
};
