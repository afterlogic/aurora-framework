'use strict';

var
	$ = require('jquery'),
	ko = require('knockout'),
	
	Types = require('core/js/utils/Types.js'),
	
	AppData = window.pSevenAppData,
	
	bRtl = $('html').hasClass('rtl')
;

module.exports = {
	Modules: {
		Auth: {
			AllowRegistration: false,
			AllowPasswordReset: false,
			LoginDescription: '',
			AllowLanguageOnLogin: false,
			FlagsLangSelect: false,
			LoginStyleImage: '',
			LoginFormType: 0,// Enums.LoginFormType.Email,
			LoginAtDomainValue: '',
			LoginSignMeType: 1,// Enums.LoginSignMeType.DefaultOn,
			DemoWebMailLogin: '',
			DemoWebMailPassword: '',
			RegistrationQuestions: [],
			RegistrationDomains: [],
			CustomLoginUrl: AppData.App ? AppData.App.CustomLoginUrl : true
		},
		OpenPgp: {
			enableOpenPgp: ko.observable(true)
		},
		Mail: {
			ShowQuotaBar: true, //todo: account level
			useThreads: ko.observable(AppData.User ? AppData.User.UseThreads : true),
			MailsPerPage: 20,
			AutoSave: true,
			AllowAutosaveInDrafts: true,
			AutoSaveIntervalSeconds: 60,
			AllowCompose: true,
			AllowUsersChangeEmailSettings: AppData.App ? AppData.App.AllowUsersChangeEmailSettings : true,
			MailExpandFolders: true,
			AllowFetcher: true,//AppData.User ? AppData.User.AllowFetcher : true,
			AllowIdentities: !!AppData.AllowIdentities,
			MaxPrefetchBodiesSize: 50000,
			DefaultFontName: 'Tahoma',
			DefaultFontSize: 3,
			AllowInsertImage: true,
			AllowChangeInputDirection: AppData.User ? AppData.User.AllowChangeInputDirection : true,
			ImageUploadSizeLimit: 0,
			AutosignOutgoingEmails: false,
			JoinReplyPrefixes: true,
			SaveRepliedToCurrFolder: AppData.User ? AppData.User.SaveRepliedToCurrFolder : false,
			AttachmentSizeLimit: 0,
			ComposeToolbarOrder: ['back', 'send', 'save', 'importance', 'MailSensitivity', 'confirmation', 'OpenPgp'],
			ThreadsEnabled: AppData.User ? AppData.User.ThreadsEnabled : true,
			AllowAppRegisterMailto: AppData.App ? AppData.App.AllowAppRegisterMailto : false,
			AllowUsersAddNewAccounts: AppData.App ? AppData.App.AllowUsersAddNewAccounts : false
		},
		Contacts: {
			Storages: ['personal', 'global', 'shared'],
			ContactsPerPage: 20,
			ImportingContactsLink: AppData && AppData['Links'] && AppData['Links']['ImportingContacts'] ? AppData['Links']['ImportingContacts'] : ''
		},
		Calendar: {
			CalendarPubHash: AppData.CalendarPubHash,
			AllowCalendar: true,
			CalendarSharing: true,
			CalendarDefaultTab: AppData.User && AppData.User.Calendar ? AppData.User.Calendar.CalendarDefaultTab.toString() : '3',
			CalendarShowWeekEnds: AppData.User && AppData.User.Calendar ? !!AppData.User.Calendar.CalendarShowWeekEnds: true,
			CalendarWeekStartsOn: AppData.User && AppData.User.Calendar ? AppData.User.Calendar.CalendarWeekStartsOn.toString() : '0',
			CalendarShowWorkDay: AppData.User && AppData.User.Calendar ? !!AppData.User.Calendar.CalendarShowWorkDay : true,
			CalendarWorkDayStarts: AppData.User && AppData.User.Calendar ? AppData.User.Calendar.CalendarWorkDayStarts : '9',
			CalendarWorkDayEnds: AppData.User && AppData.User.Calendar ? AppData.User.Calendar.CalendarWorkDayEnds : '18',
			CalendarAppointments: true
		},
		Files: {
			FileStoragePubHash: AppData.FileStoragePubHash,
			IsCollaborationSupported: true,
			AllowFilesSharing: true,
			FileStoragePubParams: {Name: ''},
			ShowQuotaBar: false,
			FileSizeLimit: 0,
			filesEnable: ko.observable(true)
		},
		Helpdesk: {
			IsHelpdeskAgent: false,
			HelpdeskIframeUrl: '',
			helpdeskSignature: ko.observable(''),
			helpdeskSignatureEnable: ko.observable(false),
			HelpdeskThreadId: '',
			HelpdeskThreadAction: '',
			ThreadsPerPage: 10,
			HelpdeskStyleImage: '',
			HelpdeskForgotHash: '',
			SocialFacebook: '',
			SocialGoogle: '',
			SocialTwitter: '',
			SocialEmail: '',
			SocialIsLoggedIn: false,
			HelpdeskUserEmail: '',
			AllowHelpdeskNotifications: AppData.User ? AppData.User.AllowHelpdeskNotifications : false,
			HelpdeskRedirect: AppData.HelpdeskRedirect,
			HelpdeskSiteName: AppData.HelpdeskSiteName,
			HelpdeskActivatedEmail: AppData.HelpdeskActivatedEmail // showReport(Utils.i18n('HELPDESK/ACCOUNT_ACTIVATED'));
		},
		Settings: {
			TabsOrder: ['common', 'mail', 'accounts', 'contacts', 'calendar', 'cloud-storage', 'mobile_sync', 'outlook_sync', 'helpdesk', 'pgp']
		},
		dsbld_Phone: {
			VoiceProvider: '',
			SipRealm: '192.168.0.59',
			SipImpi: '102',
			SipPassword: 'user02',
			SipWebsocketProxyUrl: 'ws://192.168.0.59:8088/ws'
//			SipOutboundProxyUrl: ''
		},
		dsbld_SessionTimeout: {
			TimeoutSeconds: 20
		},
		MailSensitivity: {},
		ChangePassword: {
			PasswordMinLength: AppData.App ? AppData.App.PasswordMinLength : 0,
			PasswordMustBeComplex: AppData.App ? AppData.App.PasswordMustBeComplex : false
		},
		MobileSync: {},
		OutlookSync: {
			OutlookSyncPlugin32: AppData.Links && AppData.Links.OutlookSyncPlugin32 ? AppData.Links.OutlookSyncPlugin32 : '',
			OutlookSyncPlugin64: AppData.Links && AppData.Links.OutlookSyncPlugin64 ? AppData.Links.OutlookSyncPlugin64 : '',
			OutlookSyncPluginReadMore: AppData.Links && AppData.Links.OutlookSyncPluginReadMore ? AppData.Links.OutlookSyncPluginReadMore : ''
		}
	},
	EntryModule: 'Mail',
	Language: 'English',
	CustomLogo: '',
	defaultTimeFormat: ko.observable('0'),
	DefaultDateFormat: 'DD/MM/YYYY',
	DateFormats: AppData.App ? AppData.App.DateFormats : [],
	IsFilesSupported: AppData.User ? AppData.User.IsFilesSupported : true,
	DefaultFontName: 'Tahoma',
	IdUser: AppData.IdUser,
	AllowSaveAsPdf: false,
	ZipAttachments: false,
	SiteName: AppData.App ? AppData.App.SiteName : 'AfterLogic WebMail',
	SocialName: AppData.User && Types.isNonEmptyString(AppData.User.SocialName) ? AppData.User.SocialName : '',
	IsRTL: bRtl,
	CsrfToken: AppData.Token,
	DesktopNotifications: AppData.User ? AppData.User.DesktopNotifications : true,
	AllowPrefetch: true,
	IsDemo: false,
	IdleSessionTimeout: 0,
	TenantHash: AppData.TenantHash,
	AutoRefreshIntervalMinutes: AppData.User ? AppData.User.AutoCheckMailInterval : 1,
	AllowMobile: AppData.AllowMobile,
	IsMobile: AppData.IsMobile,
	AttachmentSizeLimit: 0,
	ClientDebug: true,
	Themes: AppData.App ? AppData.App.Themes : [],
	DefaultTheme: AppData.User ? AppData.User.DefaultTheme : 'Default',
	Languages: AppData.App ? AppData.App.Languages : [],
	DefaultLanguage: AppData.User ? AppData.User.DefaultLanguage : 'English',
	AllowUsersChangeInterfaceSettings: AppData.App ? AppData.App.AllowUsersChangeInterfaceSettings : false,
	GoogleAnalyticsAccount: AppData.App ? AppData.App.GoogleAnalyticsAccount : '',
	IosDetectOnLogin: AppData.App ? AppData.App.IosDetectOnLogin : '',
	AllowIosProfile: AppData.App ? AppData.App.AllowIosProfile : '',
	LastErrorCode: Types.pString(AppData.LastErrorCode),
	
	update: function (iAutoRefreshIntervalMinutes, sDefaultTheme, sDefaultLanguage, sDefaultTimeFormat, sDesktopNotifications) {
		this.AutoRefreshIntervalMinutes = iAutoRefreshIntervalMinutes;
		this.DefaultTheme = sDefaultTheme;
		this.DefaultLanguage = sDefaultLanguage;
		this.defaultTimeFormat(sDefaultTimeFormat);
		this.DesktopNotifications = sDesktopNotifications === '1';
	}
};
