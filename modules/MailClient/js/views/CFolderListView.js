'use strict';

var
	ko = require('knockout'),
	
	TextUtils = require('modules/CoreClient/js/utils/Text.js'),
	
	App = require('modules/CoreClient/js/App.js'),
	UserSettings = require('modules/CoreClient/js/Settings.js'),
	
	AccountList = require('modules/%ModuleName%/js/AccountList.js'),
	MailCache = require('modules/%ModuleName%/js/Cache.js')
;

/**
 * @constructor
 */
function CFolderListView()
{
	this.accounts = AccountList.collection; // todo: only mobile version
	
	this.folderList = MailCache.folderList;
	
	this.manageFoldersHash = '#'; // todo: manage folders
	
//	this.manageFoldersHash = App.Routing.buildHashFromArray([Enums.Screens.Settings, 
//		Enums.SettingsTab.EmailAccounts, 
//		Enums.AccountSettingsTab.Folders]);

	this.quotaProc = ko.observable(-1);
	this.quotaDesc = ko.observable('');

	if (UserSettings.ShowQuotaBar)
	{
		ko.computed(function () {

			MailCache.quotaChangeTrigger();

			var
				oAccount = AccountList.getCurrent(),
				iQuota = oAccount ? oAccount.quota() : 0,
				iUsed = oAccount ? oAccount.usedSpace() : 0,
				iProc = 0 < iQuota ? Math.ceil((iUsed / iQuota) * 100) : -1
			;

			iProc = 100 < iProc ? 100 : iProc;

			this.quotaProc(iProc);
			this.quotaDesc(-1 < iProc ?
				TextUtils.i18n('CORE/INFO_QUOTA', {
					'PROC': iProc,
					'QUOTA': TextUtils.getFriendlySize(iQuota * 1024)
				}) : '');

			return true;

		}, this);
	}
	
	this.isCurrentAllowsMail = AccountList.isCurrentAllowsMail; // todo: manage folders
}

CFolderListView.prototype.ViewTemplate = App.isMobile() ? '%ModuleName%_FoldersMobileView' : '%ModuleName%_FoldersView';

module.exports = CFolderListView;
