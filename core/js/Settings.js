'use strict';

var
	ko = require('knockout'),
			
	AppData = window.pSevenAppData,
	
	bRtl = $('html').hasClass('rtl')
;

module.exports = {
	Modules: {
		'Auth': {},
		'Mail': {},
		'Contacts': {},
		'Settings': {},
		'dsbld_SessionTimeout': {
			'TimeoutSeconds': 20
		}
	},
	EntryModule: 'Contacts',
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