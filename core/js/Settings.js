'use strict';

var
	ko = require('knockout'),
			
	AppData = window.pSevenAppData,
	
	bRtl = $('html').hasClass('rtl')
;

module.exports = {
	Modules: {
		'Auth': {
			AllowRegistration: false,
			AllowPasswordReset: false,
			LoginDescription: '',
			AllowLanguageOnLogin: false,
			FlagsLangSelect: false,
			LoginStyleImage: '',
			LoginFormType: Enums.LoginFormType.Email,
			LoginAtDomainValue: '',
			LoginSignMeType: Enums.LoginSignMeType.DefaultOn,
			DemoWebMailLogin: '',
			DemoWebMailPassword: '',
			RegistrationQuestions: [],
			RegistrationDomains: []
		},
		'Mail': {
			ShowQuotaBar: true, //todo: account level
			useThreads: ko.observable(true),
			MailsPerPage: 20,
			AllowAutosaveInDrafts: false,
			AutoSaveIntervalSeconds: 60,
			AutoCheckMailInterval: 60,
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
			enableOpenPgp: ko.observable(false),
			JoinReplyPrefixes: true,
			SaveRepliedToCurrFolder: false,
			AttachmentSizeLimit: 0
		},
		'Contacts': {
			Storages: ['personal', 'global', 'shared'],
			ContactsPerPage: 20,
			ImportingContactsLink: AppData && AppData['Links'] && AppData['Links']['ImportingContacts'] ? AppData['Links']['ImportingContacts'] : ''
		},
		'Settings': {},
		'dsbld_SessionTimeout': {
			'TimeoutSeconds': 20
		}
	},
	EntryModule: 'Mail',
	Language: 'English',
	CustomLogo: '',
	defaultTimeFormat: ko.observable(0),
	IsFilesSupported: false,
	DefaultFontName: 'Tahoma',
	enableOpenPgp: ko.observable(false),
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
	IdleSessionTimeout: 0
};