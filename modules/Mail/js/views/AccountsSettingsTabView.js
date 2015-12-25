'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	TextUtils = require('core/js/utils/Text.js'),
	
	Popups = require('core/js/Popups.js'),
	
	ModulesManager = require('core/js/ModulesManager.js'),
	CAbstractSettingsTabView = ModulesManager.run('Settings', 'getAbstractSettingsTabViewClass'),
	
	Accounts = require('modules/Mail/js/AccountList.js'),
	Settings = require('modules/Mail/js/Settings.js'),
	CreateAccountPopup = require('modules/Mail/js/popups/CreateAccountPopup.js')
;

/**
 * @constructor
 */
function CAccountsSettingsTabView()
{
	CAbstractSettingsTabView.call(this);

	this.bAllowAddNewAccounts = Settings.AllowUsersAddNewAccounts;
	this.bAllowIdentities = !!Settings.AllowIdentities;
	this.bAllowFetcher = !!Settings.AllowFetcher;
	
	this.accounts = Accounts.collection;
	this.onlyOneAccount = ko.computed(function () {
		var bOnlyOneAccount = this.accounts().length === 1 && !Settings.AllowUsersAddNewAccounts;
//		if (bOnlyOneAccount)
//		{
//			this.TabTitle = Utils.i18n('SETTINGS/TAB_EMAIL_ACCOUNT');
//		}
		return bOnlyOneAccount;
	}, this);
	this.title = ko.computed(function () {
		return this.onlyOneAccount() ? TextUtils.i18n('SETTINGS/TITLE_EMAIL_ACCOUNT') : TextUtils.i18n('SETTINGS/TITLE_EMAIL_ACCOUNTS');
	}, this);
	
	this.editedAccountId = Accounts.editedId;
	this.editedFetcherId = ko.observable(null);
	this.editedIdentityId = ko.observable(null);
}

_.extendOwn(CAccountsSettingsTabView.prototype, CAbstractSettingsTabView.prototype);

CAccountsSettingsTabView.prototype.ViewTemplate = 'Mail_AccountsSettingsTabView';

CAccountsSettingsTabView.prototype.addAccount = function ()
{
	Popups.showPopup(CreateAccountPopup, [Enums.AccountCreationPopupType.TwoSteps, '', _.bind(function (iAccountId) {
		this.editAccount(iAccountId);
	}, this)]);
};

CAccountsSettingsTabView.prototype.editAccount = function (sId)
{
	
};

CAccountsSettingsTabView.prototype.addIdentity = function (sId)
{
	
};

CAccountsSettingsTabView.prototype.editIdentity = function (oIdentity)
{
	
};

CAccountsSettingsTabView.prototype.addFetcher = function ()
{
	
};

CAccountsSettingsTabView.prototype.editFetcher = function (sId)
{
	
};

CAccountsSettingsTabView.prototype.connectToMail = function (sId)
{
	
};

module.exports = new CAccountsSettingsTabView();
