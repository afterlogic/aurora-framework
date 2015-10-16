'use strict';

var
	ko = require('knockout'),
	$ = require('jquery'),
			
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
			useThreads: ko.observable(true),
			MailsPerPage: 20,
			AllowAutosaveInDrafts: false,
			AutoSaveIntervalSeconds: 60,
			AllowCompose: true,
			AllowUsersChangeEmailSettings: true,
			MailExpandFolders: true,
			ThreadLevel: true,
			AllowFetcher: false,
			AllowIdentities: false,
			MaxPrefetchBodiesSize: 50000,
			DefaultFontName: 'Tahoma',
			DefaultFontSize: 3,
			AllowInsertImage: true,
			AllowChangeInputDirection: true,
			ImageUploadSizeLimit: 0,
			getUseSaveMailInSentItems: function () { return false; },
			getSaveMailInSentItems: function () { return false; },
			AutosignOutgoingEmails: false,
			JoinReplyPrefixes: true,
			SaveRepliedToCurrFolder: false,
			AttachmentSizeLimit: 0
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
			CalendarDefaultTab: 1,
			CalendarShowWeekEnds: true,
			CalendarWeekStartsOn: 7,
			CalendarShowWorkDay: true,
			CalendarWorkDayStarts: '09',
			CalendarWorkDayEnds: '18',
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
			SocialIsLoggedIn: false
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
		}
	},
	EntryModule: 'Mail',
	Language: 'English',
	CustomLogo: '',
	defaultTimeFormat: ko.observable(0),
	DefaultDateFormat: 'DD/MM/YYYY',
	IsFilesSupported: true,
	DefaultFontName: 'Tahoma',
	IdUser: AppData.IdUser,
	AllowSaveAsPdf: false,
	ZipAttachments: false,
	SiteName: 'AfterLogic WebMail',
	IsRTL: bRtl,
	CsrfToken: AppData.Token,
	DesktopNotifications: true,
	AllowPrefetch: true,
	IsDemo: false,
	DefaultLanguage: 'English',
	Languages: [{name: 'English', value: 'English'}, {name: 'Русский', value: 'Russian'}],
	IdleSessionTimeout: 0,
	TenantHash: AppData.TenantHash,
	AutoRefreshIntervalMinutes: 1
};