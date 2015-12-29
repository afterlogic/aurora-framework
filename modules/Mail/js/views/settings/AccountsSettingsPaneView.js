'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	TextUtils = require('core/js/utils/Text.js'),
	Utils = require('core/js/utils/Common.js'),
	
	Api = require('core/js/Api.js'),
	
	Popups = require('core/js/Popups.js'),
	CreateAccountPopup = require('modules/Mail/js/popups/CreateAccountPopup.js'),
	
	Accounts = require('modules/Mail/js/AccountList.js'),
	Ajax = require('modules/Mail/js/Ajax.js'),
	Settings = require('modules/Mail/js/Settings.js'),
	AccountPropertiesPaneView = require('modules/Mail/js/views/settings/AccountPropertiesPaneView.js'),
	AccountFoldersPaneView = require('modules/Mail/js/views/settings/AccountFoldersPaneView.js'),
	AccountForwardPaneView = require('modules/Mail/js/views/settings/AccountForwardPaneView.js'),
	AccountAutoresponderPaneView = require('modules/Mail/js/views/settings/AccountAutoresponderPaneView.js'),
//	AccountFiltersPaneView = require('modules/Mail/js/views/settings/AccountFiltersPaneView.js'),
	AccountSignaturePaneView = require('modules/Mail/js/views/settings/AccountSignaturePaneView.js')
;

/**
 * @constructor
 */
function CAccountsSettingsPaneView()
{
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
	
	this.allowProperties = ko.observable(false);
	this.allowFolders = ko.observable(false);
	this.allowForward = ko.observable(false);
	this.allowAutoresponder = ko.observable(false);
	this.allowFilters = ko.observable(false);
	this.allowSignature = ko.observable(!Settings.AllowIdentities);
	
	this.aAccountTabs = [
		{
			name: 'properties',
			title: TextUtils.i18n('SETTINGS/ACCOUNTS_TAB_PROPERTIES'),
			view: AccountPropertiesPaneView,
			visible: this.allowProperties
		},
		{
			name: 'folders',
			title: TextUtils.i18n('SETTINGS/ACCOUNTS_TAB_MANAGE_FOLDERS'),
			view: AccountFoldersPaneView,
			visible: this.allowFolders
		},
		{
			name: 'forward',
			title: TextUtils.i18n('SETTINGS/ACCOUNTS_TAB_FORWARD'),
			view: AccountForwardPaneView,
			visible: this.allowForward
		},
		{
			name: 'autoresponder',
			title: TextUtils.i18n('SETTINGS/ACCOUNTS_TAB_AUTORESPONDER'),
			view: AccountAutoresponderPaneView,
			visible: this.allowAutoresponder
		},
//		{
//			name: 'filters',
//			title: TextUtils.i18n('SETTINGS/ACCOUNTS_TAB_FILTERS'),
//			view: AccountFiltersPaneView,
//			visible: this.allowFilters
//		},
		{
			name: 'signature',
			title: TextUtils.i18n('SETTINGS/ACCOUNTS_TAB_SIGNATURE'),
			view: AccountSignaturePaneView,
			visible: this.allowSignature
		}
	];
	
	this.currentAccountTab = ko.observable(null);
	
//	this.aIdentityTabs = ['properties', 'signature'];
//	this.aFetcherTabs = ['incoming', 'outgoing', 'signature'];
	
	Accounts.editedId.subscribe(function () {
		this.populate();
	}, this);
	this.populate();
}

CAccountsSettingsPaneView.prototype.ViewTemplate = 'Mail_Settings_AccountsSettingsPaneView';

/**
 * @param {Function} fAfterHideHandler
 * @param {Function} fRevertRouting
 */
CAccountsSettingsPaneView.prototype.hide = function (fAfterHideHandler, fRevertRouting)
{
	if (this.currentAccountTab() && $.isFunction(this.currentAccountTab().view.hide))
	{
		this.currentAccountTab().view.hide(fAfterHideHandler, fRevertRouting);
	}
	else
	{
		fAfterHideHandler();
	}
};

CAccountsSettingsPaneView.prototype.show = function ()
{
	if (this.currentAccountTab() && $.isFunction(this.currentAccountTab().view.show))
	{
		this.currentAccountTab().view.show();
	}
};

CAccountsSettingsPaneView.prototype.getAutoselectedTab = function ()
{
	var oCurrentTab = _.find(this.aAccountTabs, function (oTab) {
		return oTab.visible();
	});
	
	if (!oCurrentTab)
	{
		oCurrentTab = this.aAccountTabs[0];
	}
	
	return oCurrentTab;
};

CAccountsSettingsPaneView.prototype.addAccount = function ()
{
	Popups.showPopup(CreateAccountPopup, [Enums.AccountCreationPopupType.TwoSteps, '', _.bind(function (iAccountId) {
		this.editAccount(iAccountId);
	}, this)]);
};

CAccountsSettingsPaneView.prototype.editAccount = function (iAccountId)
{
	Accounts.changeEditedAccount(iAccountId);
};

CAccountsSettingsPaneView.prototype.addIdentity = function (sId)
{
	
};

CAccountsSettingsPaneView.prototype.editIdentity = function (oIdentity)
{
	
};

CAccountsSettingsPaneView.prototype.addFetcher = function ()
{
	
};

CAccountsSettingsPaneView.prototype.editFetcher = function (sId)
{
	
};

CAccountsSettingsPaneView.prototype.connectToMail = function (sId)
{
	
};

CAccountsSettingsPaneView.prototype.changeTab = function (sName)
{
	var
		oCurrentTab = this.currentAccountTab(),
		oNewTab = _.find(this.aAccountTabs, function (oTab) {
			return oTab.visible() && oTab.name === sName;
		}),
		fShowNewTab = function () {
			if (oNewTab)
			{
				if ($.isFunction(oNewTab.view.show))
				{
					oNewTab.view.show();
				}
				this.currentAccountTab(oNewTab);
			}
		}.bind(this),
		bShow = true
	;
	
	if (oNewTab)
	{
		if (oCurrentTab && $.isFunction(oCurrentTab.view.hide))
		{
			oCurrentTab.view.hide(fShowNewTab);
			bShow = false;
		}
	}
	else if (!oCurrentTab)
	{
		oNewTab = this.getAutoselectedTab();
	}
	
	if (bShow)
	{
		fShowNewTab();
	}
};

CAccountsSettingsPaneView.prototype.populate = function ()
{
	var
		oAccount = Accounts.getEdited()
//		bAllowMail = !!oAccount && oAccount.allowMail(),
//		bDefault = !!oAccount && oAccount.isDefault(),
//		bChangePass = !!oAccount && oAccount.extensionExists('AllowChangePasswordExtension'),
//		bCanBeRemoved =  !!oAccount && oAccount.canBeRemoved() && !oAccount.isDefault()
	;
	
	if (oAccount)
	{
//		this.allowProperties((!bDefault || bDefault && Settings.AllowUsersChangeEmailSettings) && bAllowMail || !Settings.AllowIdentities || bChangePass || bCanBeRemoved);
//		this.allowFolders(bAllowMail);
//		this.allowForward(bAllowMail && oAccount.extensionExists('AllowForwardExtension') && oAccount.forward());
//		this.allowAutoresponder(bAllowMail && oAccount.extensionExists('AllowAutoresponderExtension') && oAccount.autoresponder());
//		this.allowFilters(bAllowMail && oAccount.extensionExists('AllowSieveFiltersExtension'));
		
		this.allowProperties(!oAccount.isDefault());
		this.allowFolders(true);
		this.allowForward(true);
		this.allowAutoresponder(true);
		this.allowFilters(true);
		
		if (!this.currentAccountTab() || !this.currentAccountTab().visible())
		{
			this.currentAccountTab(this.getAutoselectedTab());
		}
		
		if (!oAccount.isExtended())
		{
			Ajax.send('GetAccountSettings', {AccountID: oAccount.id()}, this.onGetAccountSettingsResponse, this);
		}
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CAccountsSettingsPaneView.prototype.onGetAccountSettingsResponse = function (oResponse, oRequest)
{
	if (!oResponse.Result)
	{
		Api.showErrorByCode(oResponse, TextUtils.i18n('WARNING/UNKNOWN_ERROR'));
	}
	else
	{
		var
			oParameters = JSON.parse(oRequest.Parameters),
			oAccount = Accounts.getAccount(oParameters.AccountID)
		;
		
		if (!Utils.isUnd(oAccount))
		{
			oAccount.updateExtended(oResponse.Result);
			if (oAccount.id() === this.editedAccountId())
			{
				this.populate();
			}
		}	
	}
};

module.exports = new CAccountsSettingsPaneView();
