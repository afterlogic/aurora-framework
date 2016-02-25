'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	TextUtils = require('core/js/utils/Text.js'),
	
	Api = require('core/js/Api.js'),
	ModulesManager = require('core/js/ModulesManager.js'),
	
	Popups = require('core/js/Popups.js'),
	CreateAccountPopup = require('modules/Mail/js/popups/CreateAccountPopup.js'),
	CreateIdentityPopup = require('modules/Mail/js/popups/CreateIdentityPopup.js'),
	CreateFetcherPopup = require('modules/Mail/js/popups/CreateFetcherPopup.js'),
	
	AccountList = require('modules/Mail/js/AccountList.js'),
	Ajax = require('modules/Mail/js/Ajax.js'),
	Settings = require('modules/Mail/js/Settings.js'),
	
	AccountAutoresponderPaneView = require('modules/Mail/js/views/settings/AccountAutoresponderPaneView.js'),
	AccountFiltersPaneView = require('modules/Mail/js/views/settings/AccountFiltersPaneView.js'),
	AccountFoldersPaneView = require('modules/Mail/js/views/settings/AccountFoldersPaneView.js'),
	AccountForwardPaneView = require('modules/Mail/js/views/settings/AccountForwardPaneView.js'),
	AccountPropertiesPaneView = require('modules/Mail/js/views/settings/AccountPropertiesPaneView.js'),
	CIdentityPropertiesPaneView = require('modules/Mail/js/views/settings/CIdentityPropertiesPaneView.js'),
	FetcherIncomingPaneView = require('modules/Mail/js/views/settings/FetcherIncomingPaneView.js'),
	FetcherOutgoingPaneView = require('modules/Mail/js/views/settings/FetcherOutgoingPaneView.js'),
	SignaturePaneView = require('modules/Mail/js/views/settings/SignaturePaneView.js')
;

/**
 * @constructor
 */
function CAccountsSettingsPaneView()
{
	this.bAllowAddNewAccounts = Settings.AllowAddNewAccounts;
	this.bAllowIdentities = !!Settings.AllowIdentities;
	this.bAllowFetchers = !!Settings.AllowFetchers;
	
	this.accounts = AccountList.collection;
	this.onlyOneAccount = ko.computed(function () {
		var bOnlyOneAccount = this.accounts().length === 1 && !Settings.AllowAddNewAccounts;
//		if (bOnlyOneAccount)
//		{
//			this.TabTitle = Utils.i18n('MAIL/LABEL_ACCOUNT_SETTINGS_TAB');
//		}
		return bOnlyOneAccount;
	}, this);
	this.title = ko.computed(function () {
		return this.onlyOneAccount() ? TextUtils.i18n('MAIL/HEADING_ACCOUNT_SETTINGS') : TextUtils.i18n('MAIL/HEADING_ACCOUNTS_SETTINGS');
	}, this);
	
	this.editedAccountId = AccountList.editedId;
	this.editedFetcher = ko.observable(null);
	this.editedFetcherId = ko.computed(function () {
		return this.editedFetcher() ? this.editedFetcher().id() : null;
	}, this);
	this.editedIdentity = ko.observable(null);
	this.editedIdentityId = ko.computed(function () {
		return this.editedIdentity() ? this.editedIdentity().id() : null;
	}, this);
	
	this.allowProperties = ko.observable(false);
	this.allowFolders = ko.observable(false);
	this.allowForward = ko.observable(false);
	this.allowAutoresponder = ko.observable(false);
	this.allowFilters = ko.observable(false);
	this.allowSignature = ko.observable(!Settings.AllowIdentities);
	
	this.aAccountTabs = [
		{
			name: 'properties',
			title: TextUtils.i18n('MAIL/LABEL_PROPERTIES_TAB'),
			view: AccountPropertiesPaneView,
			visible: this.allowProperties
		},
		{
			name: 'folders',
			title: TextUtils.i18n('MAIL/LABEL_MANAGE_FOLDERS_TAB'),
			view: AccountFoldersPaneView,
			visible: this.allowFolders
		},
		{
			name: 'forward',
			title: TextUtils.i18n('MAIL/LABEL_FORWARD_TAB'),
			view: AccountForwardPaneView,
			visible: this.allowForward
		},
		{
			name: 'autoresponder',
			title: TextUtils.i18n('MAIL/LABEL_AUTORESPONDER_TAB'),
			view: AccountAutoresponderPaneView,
			visible: this.allowAutoresponder
		},
		{
			name: 'filters',
			title: TextUtils.i18n('MAIL/LABEL_FILTERS_TAB'),
			view: AccountFiltersPaneView,
			visible: this.allowFilters
		},
		{
			name: 'signature',
			title: TextUtils.i18n('MAIL/LABEL_SIGNATURE_TAB'),
			view: SignaturePaneView,
			visible: this.allowSignature
		}
	];
	
	this.aIdentityTabs = [
		{
			name: 'properties',
			title: TextUtils.i18n('MAIL/LABEL_PROPERTIES_TAB'),
			view: new CIdentityPropertiesPaneView(),
			visible: ko.observable(true)
		},
		{
			name: 'signature',
			title: TextUtils.i18n('MAIL/LABEL_SIGNATURE_TAB'),
			view: SignaturePaneView,
			visible: ko.observable(true)
		}
	];
	
	this.aFetcherTabs = [
		{
			name: 'incoming',
			title: TextUtils.i18n('MAIL/LABEL_POP3_SETTINGS_TAB'),
			view: FetcherIncomingPaneView,
			visible: ko.observable(true)
		},
		{
			name: 'outgoing',
			title: TextUtils.i18n('MAIL/LABEL_SMTP_SETTINGS_TAB'),
			view: FetcherOutgoingPaneView,
			visible: ko.observable(true)
		},
		{
			name: 'signature',
			title: TextUtils.i18n('MAIL/LABEL_SIGNATURE_TAB'),
			view: SignaturePaneView,
			visible: ko.observable(true)
		}
	];
	
	this.currentTab = ko.observable(null);
	this.tabs = ko.computed(function () {
		if (this.editedIdentity())
		{
			return this.aIdentityTabs;
		}
		if (this.editedFetcher())
		{
			return this.aFetcherTabs;
		}
		return this.aAccountTabs;
	}, this);
	
	
	AccountList.editedId.subscribe(function () {
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
	if (this.currentTab() && $.isFunction(this.currentTab().view.hide))
	{
		this.currentTab().view.hide(fAfterHideHandler, fRevertRouting);
	}
	else
	{
		fAfterHideHandler();
	}
};

/**
 * @param {Array} aParams
 */
CAccountsSettingsPaneView.prototype.onRoute = function (aParams)
{
	var
		sType = aParams.length > 0 ? aParams[0] : 'account',
		oEditedAccount = AccountList.getEdited(),
		sHash = aParams.length > 1 ? aParams[1] : (oEditedAccount ? oEditedAccount.hash() : ''),
		sTab = aParams.length > 2 ? aParams[2] : ''
	;
	
	this.editedIdentity(sType === 'identity' ? (AccountList.getIdentityByHash(sHash) || null) : null);
	this.editedFetcher(sType === 'fetcher' ? (AccountList.getFetcherByHash(sHash) || null) : null);
	
	if (sType === 'account')
	{
		AccountList.changeEditedAccountByHash(sHash);
	}
	
	this.changeTab(sTab || this.getAutoselectedTab().name);
	
	if (this.currentTab() && $.isFunction(this.currentTab().view.show))
	{
		this.currentTab().view.show();
	}
};

CAccountsSettingsPaneView.prototype.getAutoselectedTab = function ()
{
	var oCurrentTab = _.find(this.tabs(), function (oTab) {
		return oTab.visible();
	});
	
	if (!oCurrentTab)
	{
		oCurrentTab = this.tabs()[0];
	}
	
	return oCurrentTab;
};

CAccountsSettingsPaneView.prototype.addAccount = function ()
{
	Popups.showPopup(CreateAccountPopup, [Enums.AccountCreationPopupType.TwoSteps, '', _.bind(function (iAccountId) {
		this.editAccount(iAccountId);
	}, this)]);
};

/**
 * @param {string} sHash
 */
CAccountsSettingsPaneView.prototype.editAccount = function (sHash)
{
	ModulesManager.run('Settings', 'setAddHash', [['account', sHash]]);
};

/**
 * @param {number} iAccountId
 * @param {Object} oEv
 */
CAccountsSettingsPaneView.prototype.addIdentity = function (iAccountId, oEv)
{
	oEv.stopPropagation();
	Popups.showPopup(CreateIdentityPopup, [iAccountId]);
};

/**
 * @param {string} sHash
 */
CAccountsSettingsPaneView.prototype.editIdentity = function (sHash)
{
	ModulesManager.run('Settings', 'setAddHash', [['identity', sHash]]);
};

/**
 * @param {number} iAccountId
 * @param {Object} oEv
 */
CAccountsSettingsPaneView.prototype.addFetcher = function (iAccountId, oEv)
{
	oEv.stopPropagation();
	Popups.showPopup(CreateFetcherPopup, [iAccountId]);
};

/**
 * @param {string} sHash
 */
CAccountsSettingsPaneView.prototype.editFetcher = function (sHash)
{
	ModulesManager.run('Settings', 'setAddHash', [['fetcher', sHash]]);
};

/**
 * @param {string} sId
 * @param {Object} oEv
 */
CAccountsSettingsPaneView.prototype.connectToMail = function (sId, oEv)
{
	oEv.stopPropagation();
	
	var oDefaultAccount = AccountList.getDefault();
	
	if (oDefaultAccount && !oDefaultAccount.allowMail())
	{
		Popups.showPopup(CreateAccountPopup, [Enums.AccountCreationPopupType.ConnectToMail, '', _.bind(function (iAccountId) {
			this.editAccount(iAccountId);
		}, this)]);
	}
};

/**
 * @param {string} sTabName
 */
CAccountsSettingsPaneView.prototype.changeRoute = function (sTabName)
{
	var
		oEditedAccount = AccountList.getEdited(),
		aAddHash = ['account', oEditedAccount ? oEditedAccount.hash() : '', sTabName]
	;
	if (this.editedIdentity())
	{
		aAddHash = ['identity', this.editedIdentity().hash(), sTabName];
	}
	else if (this.editedFetcher())
	{
		aAddHash = ['fetcher', this.editedFetcher().hash(), sTabName];
	}
	ModulesManager.run('Settings', 'setAddHash', [aAddHash]);
};

/**
 * @param {string} sName
 */
CAccountsSettingsPaneView.prototype.changeTab = function (sName)
{
	var
		oCurrentTab = this.currentTab(),
		oNewTab = _.find(this.tabs(), function (oTab) {
			return oTab.visible() && oTab.name === sName;
		}),
		fShowNewTab = function () {
			if (oNewTab)
			{
				if ($.isFunction(oNewTab.view.show))
				{
					oNewTab.view.show(this.editedIdentity() || this.editedFetcher());
				}
				this.currentTab(oNewTab);
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
		oAccount = AccountList.getEdited(),
		bAllowMail = !!oAccount && oAccount.allowMail(),
		bDefault = !!oAccount && oAccount.isDefault(),
		bLinked = !!oAccount && oAccount.isLinked(),
		bChangePass = !!oAccount && oAccount.extensionExists('AllowChangePasswordExtension'),
		bCanBeRemoved = !!oAccount && oAccount.canBeRemoved() && !oAccount.isDefault()
	;
	
	if (oAccount)
	{
		this.allowProperties((!bDefault || bDefault && !bLinked && Settings.AllowUsersChangeEmailSettings) && bAllowMail || !Settings.AllowIdentities || bChangePass || bCanBeRemoved);
		this.allowFolders(bAllowMail);
		this.allowForward(bAllowMail && oAccount.extensionExists('AllowForwardExtension') && oAccount.forward());
		this.allowAutoresponder(bAllowMail && oAccount.extensionExists('AllowAutoresponderExtension') && oAccount.autoresponder());
		this.allowFilters(bAllowMail && oAccount.extensionExists('AllowSieveFiltersExtension'));
		
		if (!this.currentTab() || !this.currentTab().visible())
		{
			this.currentTab(this.getAutoselectedTab());
		}
		
		if (!oAccount.isExtended())
		{
			Ajax.send('GetAccount', {AccountID: oAccount.id()}, this.onGetAccountSettingsResponse, this);
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
		Api.showErrorByCode(oResponse, TextUtils.i18n('CORE/ERROR_UNKNOWN'));
	}
	else
	{
		var
			oParameters = JSON.parse(oRequest.Parameters),
			oAccount = AccountList.getAccount(oParameters.AccountID)
		;
		
		if (oAccount)
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
