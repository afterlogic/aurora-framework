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
	Modules: {
		Auth: {
			AllowChangeLanguage: AppData.App ? !!AppData.App.AllowLanguageOnLogin : false,
			AllowRegistration: AppData.App ? !!AppData.App.AllowRegistration : false,
			AllowResetPassword: AppData.App ? !!AppData.App.AllowPasswordReset : false,
			CustomLoginUrl: AppData.App ? Types.pString(AppData.App.CustomLoginUrl) : '',
			CustomLogoUrl: Types.pString(AppData.LoginStyleImage),
			DemoLogin: AppData.App ? Types.pString(AppData.App.DemoWebMailLogin) : '',
			DemoPassword: AppData.App ? Types.pString(AppData.App.DemoWebMailPassword) : '',
			InfoText: AppData.App ? Types.pString(AppData.App.LoginDescription) : '',
			LoginAtDomain: AppData.App ? Types.pString(AppData.App.LoginAtDomainValue) : '',
			LoginFormType: AppData.App ? Types.pInt(AppData.App.LoginFormType) : 0, // 0 - email, 3 - login, 4 - both
			LoginSignMeType: AppData.App ? Types.pInt(AppData.App.LoginSignMeType) : 0, // 0 - off, 1 - on, 2 - don't use
			RegistrationDomains: AppData.App && $.isArray(AppData.App.RegistrationDomains) ? AppData.App.RegistrationDomains : [],
			RegistrationQuestions: AppData.App && $.isArray(AppData.App.RegistrationQuestions) ? AppData.App.RegistrationQuestions : [],
			UseFlagsLanguagesView: AppData.App ? !!AppData.App.FlagsLangSelect : false
		},
		OpenPgp: { // AppData.App.AllowOpenPGP
			enableOpenPgp: ko.observable(AppData.User ? !!AppData.User.EnableOpenPgp : true)
		},
		Mail: { // AppData.App.AllowWebMail
			AllowAddNewAccounts: AppData.App ? !!AppData.App.AllowUsersAddNewAccounts : false,
			AllowAppRegisterMailto: AppData.App ? !!AppData.App.AllowAppRegisterMailto : false,
			AllowAutosaveInDrafts: AppData.Mail ? !!AppData.Mail.AllowAutosaveInDrafts : true, // ??? changes in OpenPgp
			AllowChangeEmailSettings: AppData.App ? !!AppData.App.AllowUsersChangeEmailSettings : true,
			AllowChangeInputDirection: AppData.Mail ? !!AppData.Mail.AllowChangeInputDirection : true,
			AllowExpandFolders: !!AppData.MailExpandFolders,
			AllowFetchers: AppData.User ? !!AppData.User.AllowFetcher : true,
			AllowIdentities: !!AppData.AllowIdentities,
			AllowInsertImage: AppData.App ? !!AppData.App.AllowInsertImage : true,
			AllowSaveMessageAsPdf: !!AppData.AllowSaveAsPdf,
			AllowThreads: AppData.User ? !!AppData.User.ThreadsEnabled : true,
			AllowZipAttachments: !!AppData.ZipAttachments,
			AutoSave: AppData.App ? !!AppData.App.AutoSave : true, // ??? uses in OpenPgp
			AutoSaveIntervalSeconds: 60, // add to settings
			AutosignOutgoingEmails: AppData.User ? !!AppData.User.AutosignOutgoingEmails : false,
			ComposeToolbarOrder: ['back', 'send', 'save', 'importance', 'MailSensitivity', 'confirmation', 'OpenPgp'], // add to settings
			DefaultFontName: Types.pString(AppData.HtmlEditorDefaultFontName) || 'Tahoma',
			DefaultFontSize: Types.pInt(AppData.HtmlEditorDefaultFontSize) || 3,
			ImageUploadSizeLimit: AppData.App ? Types.pInt(AppData.App.ImageUploadSizeLimit) : 0,
			JoinReplyPrefixes: AppData.App ? !!AppData.App.JoinReplyPrefixes : true,
			MailsPerPage: AppData.Mail ? Types.pInt(AppData.Mail.MailsPerPage) : 20,
			MaxMessagesBodiesSizeToPrefetch: 50000, // add to settings
			SaveRepliesToCurrFolder: AppData.Mail ? !!AppData.Mail.SaveRepliesToCurrFolder : false,
			useThreads: ko.observable(AppData.Mail ? !!AppData.Mail.UseThreads : true)
		},
		Contacts: {
			ContactsPerPage: AppData.User ? Types.pInt(AppData.User.ContactsPerPage) : 20,
			ImportContactsLink: AppData.Links ? Types.pString(AppData.Links.ImportingContacts) : '',
			Storages: ['personal', 'global', 'shared'] // AppData.User.ShowPersonalContacts, AppData.User.ShowGlobalContacts, AppData.App.AllowContactsSharing
		},
		Calendar: { // AppData.User.AllowCalendar
			AllowAppointments: AppData.User ? !!AppData.User.CalendarAppointments : true,
			AllowShare: AppData.User ? !!AppData.User.CalendarSharing : true,
			DefaultTab: AppData.User && AppData.User.Calendar ? Types.pString(AppData.User.Calendar.CalendarDefaultTab) : '3', // 1 - day, 2 - week, 3 - month
			HighlightWorkingDays: AppData.User && AppData.User.Calendar ? !!AppData.User.Calendar.CalendarShowWeekEnds: true,
			HighlightWorkingHours: AppData.User && AppData.User.Calendar ? !!AppData.User.Calendar.CalendarShowWorkDay : true,
			PublicCalendarId: Types.pString(AppData.CalendarPubHash),
			WeekStartsOn: AppData.User && AppData.User.Calendar ? Types.pString(AppData.User.Calendar.CalendarWeekStartsOn) : '0', // 0 - sunday
			WorkdayEnds: AppData.User && AppData.User.Calendar ? Types.pString(AppData.User.Calendar.CalendarWorkDayEnds) : '18',
			WorkdayStarts: AppData.User && AppData.User.Calendar ? Types.pString(AppData.User.Calendar.CalendarWorkDayStarts) : '9'
		},
		Files: { // AppData.User.IsFilesSupported
			enableModule: ko.observable(true),//AppData.User.FilesEnable
			AllowCollaboration: AppData.User ? !!AppData.User.IsCollaborationSupported : true,
			AllowSharing: AppData.User ? !!AppData.User.AllowFilesSharing : true,
			PublicHash: Types.pString(AppData.FileStoragePubHash),
			PublicName: AppData.FileStoragePubParams ? Types.pString(AppData.FileStoragePubParams.Name) : '',
			UploadSizeLimitMb: AppData.App ? Types.pString(AppData.App.FileSizeLimit) : 0
		},
		Helpdesk: { // AppData.User.IsHelpdeskSupported
			ActivatedEmail: Types.pString(AppData.HelpdeskActivatedEmail), // todo: showReport(Utils.i18n('HELPDESK/ACCOUNT_ACTIVATED'));
			AllowEmailNotifications: AppData.User ? !!AppData.User.AllowHelpdeskNotifications : false,
			AllowFacebookAuth: !!AppData.SocialFacebook,
			AllowGoogleAuth: !!AppData.SocialGoogle,
			AllowTwitterAuth: !!AppData.SocialTwitter,
			AfterThreadsReceivingAction: Types.pString(AppData.HelpdeskThreadAction), // add, close
			ClientDetailsUrl: Types.pString(AppData.HelpdeskIframeUrl),
			ClientSiteName: Types.pString(AppData.HelpdeskSiteName), // todo
			ForgotHash: Types.pString(AppData.HelpdeskForgotHash),
			IsAgent: AppData.User ? !!AppData.User.IsHelpdeskAgent : false,
			LoginLogoUrl: Types.pString(AppData.HelpdeskStyleImage),
			SelectedThreadId: Types.pInt(AppData.HelpdeskThreadId),
			signature: ko.observable(AppData.User ? Types.pString(AppData.User.HelpdeskSignature) : ''),
			SocialEmail: Types.pString(AppData.SocialEmail),
			SocialIsLoggedIn: !!AppData.SocialIsLoggedIn, // ???
			ThreadsPerPage: 10, // add to settings
			UserEmail: AppData.User ? Types.pString(AppData.User.Email) : '',
			useSignature: ko.observable(AppData.User ? !!AppData.User.HelpdeskSignatureEnable : false)
		},
		Settings: {
			TabsOrder: ['common', 'mail', 'accounts', 'contacts', 'calendar', 'cloud-storage', 'mobile_sync', 'outlook_sync', 'helpdesk', 'pgp'] // add to settings
		},
		SimpleChat: {
			enableModule: ko.observable(AppData.SimpleChat ? !!AppData.SimpleChat.EnableModule : false)
		},
		dsbld_Phone: {
			SipImpi: AppData.User ? Types.pString(AppData.User.SipImpi) : '102',
			SipOutboundProxyUrl: AppData.User ? Types.pString(AppData.User.SipOutboundProxyUrl) : '',
			SipPassword: AppData.User ? Types.pString(AppData.User.SipPassword) : 'user02',
			SipRealm: AppData.User ? Types.pString(AppData.User.SipRealm) : '192.168.0.59',
			SipWebsocketProxyUrl: AppData.User ? Types.pString(AppData.User.SipWebsocketProxyUrl) : 'ws://192.168.0.59:8088/ws',
			VoiceProvider: AppData.User ? Types.pString(AppData.User.VoiceProvider) : ''
		},
		dsbld_SessionTimeout: {
			TimeoutSeconds: AppData.App ? Types.pInt(AppData.App.IdleSessionTimeout) : 0
		},
		MailSensitivity: {},
		ChangePassword: {
			PasswordMinLength: AppData.App ? Types.pInt(AppData.App.PasswordMinLength) : 0,
			PasswordMustBeComplex: AppData.App ? !!AppData.App.PasswordMustBeComplex : false
		},
		MobileSync: {}, // AppData.User.MobileSyncEnable
		OutlookSync: { // AppData.User.OutlookSyncEnable
			Plugin32DownloadLink: AppData.Links ? Types.pString(AppData.Links.OutlookSyncPlugin32) : '',
			Plugin64DownloadLink: AppData.Links ? Types.pString(AppData.Links.OutlookSyncPlugin64) : '',
			PluginReadMoreLink: AppData.Links ? Types.pString(AppData.Links.OutlookSyncPluginReadMore) : ''
		}
	},
	
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
	TenantHash: Types.pString(AppData.TenantHash) || UrlUtils.getRequestParam('helpdesk') || '0804e764',
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
