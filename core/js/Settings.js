'use strict';

var
	ko = require('knockout'),
			
	AppData = window.pSevenAppData,
	
	bRtl = $('html').hasClass('rtl')
;

module.exports = {
	Modules: ['mail', 'contacts', 'settings'],
	EntryModule: 'mail',
	Language: 'English',
	CustomLogo: '',
	defaultTimeFormat: ko.observable(0),
	IsFilesSupported: false,
	DefaultFontName: 'Tahoma',
	enableOpenPgp: ko.observable(false),
	IdUser: 98,
	AllowSaveAsPdf: false,
	ZipAttachments: false,
	SiteName: 'AfterLogic WebMail',
	IsRTL: bRtl,
	CsrfToken: AppData.Token,
	DesktopNotifications: true
};