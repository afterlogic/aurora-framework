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
	AccountPropertiesPageView = require('modules/Mail/js/views/settings/AccountPropertiesPageView.js'),
	AccountFoldersPageView = require('modules/Mail/js/views/settings/AccountFoldersPageView.js'),
	AccountForwardPageView = require('modules/Mail/js/views/settings/AccountForwardPageView.js'),
	AccountAutoresponderPageView = require('modules/Mail/js/views/settings/AccountAutoresponderPageView.js'),
//	AccountFiltersPageView = require('modules/Mail/js/views/settings/AccountFiltersPageView.js'),
	AccountSignaturePageView = require('modules/Mail/js/views/settings/AccountSignaturePageView.js')
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
	
	this.aAccountPages = [
		{
			name: 'properties',
			title: TextUtils.i18n('SETTINGS/ACCOUNTS_TAB_PROPERTIES'),
			view: AccountPropertiesPageView,
			visible: this.allowProperties
		},
		{
			name: 'folders',
			title: TextUtils.i18n('SETTINGS/ACCOUNTS_TAB_MANAGE_FOLDERS'),
			view: AccountFoldersPageView,
			visible: this.allowFolders
		},
		{
			name: 'forward',
			title: TextUtils.i18n('SETTINGS/ACCOUNTS_TAB_FORWARD'),
			view: AccountForwardPageView,
			visible: this.allowForward
		},
		{
			name: 'autoresponder',
			title: TextUtils.i18n('SETTINGS/ACCOUNTS_TAB_AUTORESPONDER'),
			view: AccountAutoresponderPageView,
			visible: this.allowAutoresponder
		},
//		{
//			name: 'filters',
//			title: TextUtils.i18n('SETTINGS/ACCOUNTS_TAB_FILTERS'),
//			view: AccountFiltersPageView,
//			visible: this.allowFilters
//		},
		{
			name: 'signature',
			title: TextUtils.i18n('SETTINGS/ACCOUNTS_TAB_SIGNATURE'),
			view: AccountSignaturePageView,
			visible: this.allowSignature
		}
	];
	
	this.currentPage = ko.observable(this.getDefaultCurrentPage());
	
//	this.aIdentityPages = ['properties', 'signature'];
//	this.aFetcherPages = ['incoming', 'outgoing', 'signature'];
	
	Accounts.editedId.subscribe(function () {
		this.populate(Accounts.editedId());
	}, this);
	this.populate(Accounts.editedId());
}

CAccountsSettingsPaneView.prototype.ViewTemplate = 'Mail_Settings_AccountsSettingsPaneView';

/**
 * @param {Function} fAfterHideHandler
 * @param {Function} fRevertRouting
 */
CAccountsSettingsPaneView.prototype.hide = function (fAfterHideHandler, fRevertRouting)
{
	if ($.isFunction(this.currentPage.hide))
	{
		this.currentPage.hide(fAfterHideHandler, fRevertRouting);
	}
	else
	{
		fAfterHideHandler();
	}
};

CAccountsSettingsPaneView.prototype.show = function ()
{
	if ($.isFunction(this.currentPage.show))
	{
		this.currentPage.show();
	}
};

CAccountsSettingsPaneView.prototype.getDefaultCurrentPage = function ()
{
	var oCurrentPage = _.find(this.aAccountPages, function (oPage) {
		return oPage.visible();
	});
	
	if (!oCurrentPage)
	{
		oCurrentPage = this.aAccountPages[0];
	}
	
	return oCurrentPage;
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

CAccountsSettingsPaneView.prototype.showPage = function (sName)
{
	var
		oNewCurrentPage = _.find(this.aAccountPages, function (oPage) {
			return oPage.visible() && oPage.name === sName;
		});
	
	if (oNewCurrentPage)
	{
		this.currentPage(oNewCurrentPage);
	}
};

/**
 * @param {number} iAccountId
 */
CAccountsSettingsPaneView.prototype.populate = function (iAccountId)
{
	var
		oAccount = Accounts.getAccount(iAccountId)
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
		
		if (!this.currentPage().visible())
		{
			this.currentPage(this.getDefaultCurrentPage());
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
		Api.showErrorByCode(oResponse, TextUtils.i18n('SETTINGS/ERROR_SETTINGS_SAVING_FAILED'));
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
				this.populate(oAccount.id());
			}
		}	
	}
};

module.exports = new CAccountsSettingsPaneView();
