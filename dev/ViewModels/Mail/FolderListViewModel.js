
/**
 * @constructor
 */
function CFolderListViewModel()
{
	this.accounts = AppData.Accounts.collection;
	
	this.mobileApp = bMobileApp;
	
	this.folderList = App.MailCache.folderList;
	
	this.manageFoldersHash = App.Routing.buildHashFromArray([Enums.Screens.Settings, 
		Enums.SettingsTab.EmailAccounts, 
		Enums.AccountSettingsTab.Folders]);

	this.quotaProc = ko.observable(-1);
	this.quotaDesc = ko.observable('');

	ko.computed(function () {

		if (!AppData.App || AppData.App && !AppData.App.ShowQuotaBar)
		{
			return true;
		}

		App.MailCache.quotaChangeTrigger();

		var
			oAccount = AppData.Accounts.getCurrent(),
			iQuota = oAccount ? oAccount.quota() : 0,
			iUsed = oAccount ? oAccount.usedSpace() : 0,
			iProc = 0 < iQuota ? Math.ceil((iUsed / iQuota) * 100) : -1
		;

		iProc = 100 < iProc ? 100 : iProc;
		
		this.quotaProc(iProc);
		this.quotaDesc(-1 < iProc ?
			Utils.i18n('MAILBOX/QUOTA_TOOLTIP', {
				'PROC': iProc,
				'QUOTA': Utils.friendlySize(iQuota * 1024)
			}) : '');

		return true;
		
	}, this);
	
	this.isCurrentAllowsMail = AppData.Accounts.isCurrentAllowsMail;
}
