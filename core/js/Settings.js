'use strict';

var
	$ = require('jquery'),
	ko = require('knockout'),
	
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
			RegistrationDomains: []
		},
		OpenPgp: {
			enableOpenPgp: ko.observable(true)
		},
		Mail: {
			ShowQuotaBar: true, //todo: account level
			useThreads: ko.observable(AppData.User ? AppData.User.UseThreads : true),
			MailsPerPage: 20,
			AllowAutosaveInDrafts: true,
			AutoSaveIntervalSeconds: 60,
			AllowCompose: true,
			AllowUsersChangeEmailSettings: true,
			MailExpandFolders: true,
			AllowFetcher: true,
			AllowIdentities: true,
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
			AllowAppRegisterMailto: AppData.User ? AppData.App.AllowAppRegisterMailto : true
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
			CalendarWeekStartsOn: AppData.User && AppData.User.Calendar ? parseInt(AppData.User.Calendar.CalendarWeekStartsOn, 10) : 7,
			CalendarShowWorkDay: AppData.User && AppData.User.Calendar ? !!AppData.User.Calendar.CalendarShowWorkDay : true,
			CalendarWorkDayStarts: AppData.User && AppData.User.Calendar ? AppData.User.Calendar.CalendarWorkDayStarts : '09',
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
			AllowHelpdeskNotifications: AppData.User ? AppData.User.AllowHelpdeskNotifications : false
		},
		Settings: {},
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
		MailSensitivity: {}
	},
	EntryModule: 'Mail',
	Language: 'English',
	CustomLogo: '',
	defaultTimeFormat: ko.observable('0'),
	DefaultDateFormat: 'DD/MM/YYYY',
	DateFormats: AppData.App.DateFormats,
	IsFilesSupported: AppData.User ? AppData.User.IsFilesSupported : true,
	DefaultFontName: 'Tahoma',
	IdUser: AppData.IdUser,
	AllowSaveAsPdf: false,
	ZipAttachments: false,
	SiteName: 'AfterLogic WebMail',
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
	Themes: AppData.App.Themes,
	DefaultTheme: AppData.User ? AppData.User.DefaultTheme : 'Default',
	Languages: AppData.App.Languages,
	DefaultLanguage: AppData.User ? AppData.User.DefaultLanguage : 'English',
	
	update: function (iAutoRefreshIntervalMinutes, sDefaultTheme, sDefaultLanguage, sDefaultTimeFormat, sDesktopNotifications) {
		this.AutoRefreshIntervalMinutes = iAutoRefreshIntervalMinutes;
		this.DefaultTheme = sDefaultTheme;
		this.DefaultLanguage = sDefaultLanguage;
		this.defaultTimeFormat(sDefaultTimeFormat);
		this.DesktopNotifications = sDesktopNotifications === '1';
	}
};
