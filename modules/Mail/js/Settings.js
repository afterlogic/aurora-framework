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
	AllowIdentities: false
};