'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	TextUtils = require('core/js/utils/Text.js'),
	
	Accounts = require('modules/Mail/js/AccountList.js'),
	MailCache = require('modules/Mail/js/Cache.js'),
	Settings = require('modules/Mail/js/Settings.js'),
	
	bMobileApp = false
;

/**
 * @constructor
 */
function CFolderListView()
{
	this.accounts = Accounts.collection; // todo: only mobile version
	
	this.mobileApp = bMobileApp;
	
	this.folderList = MailCache.folderList;
	
	this.manageFoldersHash = '#'; // todo: manage folders
	
//	this.manageFoldersHash = App.Routing.buildHashFromArray([Enums.Screens.Settings, 
//		Enums.SettingsTab.EmailAccounts, 
//		Enums.AccountSettingsTab.Folders]);

	this.quotaProc = ko.observable(-1);
	this.quotaDesc = ko.observable('');

	if (Settings.ShowQuotaBar)
	{
		ko.computed(function () {

			MailCache.quotaChangeTrigger();

			var
				oAccount = Accounts.getCurrent(),
				iQuota = oAccount ? oAccount.quota() : 0,
				iUsed = oAccount ? oAccount.usedSpace() : 0,
				iProc = 0 < iQuota ? Math.ceil((iUsed / iQuota) * 100) : -1
			;

			iProc = 100 < iProc ? 100 : iProc;

			this.quotaProc(iProc);
			this.quotaDesc(-1 < iProc ?
				TextUtils.i18n('MAILBOX/QUOTA_TOOLTIP', {
					'PROC': iProc,
					'QUOTA': TextUtils.getFriendlySize(iQuota * 1024)
				}) : '');

			return true;

		}, this);
	}
	
	this.isCurrentAllowsMail = Accounts.isCurrentAllowsMail; // todo: manage folders
}

CFolderListView.prototype.ViewTemplate = 'Mail_FolderListView';

module.exports = CFolderListView;