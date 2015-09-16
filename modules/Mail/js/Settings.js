'use strict';

var
	ko = require('knockout')
;

module.exports = {
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
};