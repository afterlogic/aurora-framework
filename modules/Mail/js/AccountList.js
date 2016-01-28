'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	TextUtils = require('core/js/utils/Text.js'),
	Types = require('core/js/utils/Types.js'),
	
	App = require('core/js/App.js'),
	MainTab = App.isNewTab() && window.opener ? window.opener.MainTabMailMethods : null,
	Routing = require('core/js/Routing.js'),
	Storage = require('core/js/Storage.js'),
	UserSettings = require('core/js/Settings.js'),
	
	Popups = require('core/js/Popups.js'),
	ConfirmPopup = require('core/js/popups/ConfirmPopup.js'),
	CreateAccountPopup = require('modules/Mail/js/popups/CreateAccountPopup.js'),
	
	Ajax = require('modules/Mail/js/Ajax.js'),
	LinksUtils = require('modules/Mail/js/utils/Links.js'),
	Settings = require('modules/Mail/js/Settings.js'),
	CAccountModel = require('modules/Mail/js/models/CAccountModel.js'),
	CIdentityModel = require('modules/Mail/js/models/CIdentityModel.js'),
	CFetcherListModel = require('modules/Mail/js/models/CFetcherListModel.js')
;

/**
 * @constructor
 * @param {number} iDefaultAccountId
 */
function CAccountListModel(iDefaultAccountId)
{
	this.defaultId = ko.observable(iDefaultAccountId);
	this.currentId = ko.observable(iDefaultAccountId);
	this.editedId = ko.observable(iDefaultAccountId);
	
	this.isCurrentAllowsMail = ko.observable(true);
	
	this.bLockEditedWhenCurrentChange = true;

	this.currentId.subscribe(function(value) {
		var oCurrentAccount = this.getCurrent();
		if (oCurrentAccount)
		{
			oCurrentAccount.requestExtensions();

			this.checkIfMailAllowed();

			if (!this.bLockEditedWhenCurrentChange)
			{
				// deferred execution to edited account has changed a bit later and did not make a second request 
				// of the folder list of the same account.
				_.delay(_.bind(function () {
					this.editedId(value);
				}, this), 1000);
			}
		}
	}, this);

	this.collection = ko.observableArray([]);
}

CAccountListModel.prototype.checkIfMailAllowed = function ()
{
	var oCurrentAccount = this.getCurrent();
	this.isCurrentAllowsMail(oCurrentAccount.allowMail());
};

/**
 * Sets a flag that indicates whether it is necessary to change editable account during changing the current account.
 * No need to change the edited account during application first running.
 */
CAccountListModel.prototype.unlockEditedWhenCurrentChange = function ()
{
	this.bLockEditedWhenCurrentChange = false;
};

/**
 * Changes current account. Sets hash to show new account data.
 * 
 * @param {number} iNewCurrentId
 * @param {boolean} bPassToMail
 */
CAccountListModel.prototype.changeCurrentAccount = function (iNewCurrentId, bPassToMail)
{
	var
		oCurrentAccount = this.getCurrent(),
		oNewCurrentAccount = this.getAccount(iNewCurrentId)
	;

	if (oNewCurrentAccount && this.currentId() !== iNewCurrentId)
	{
		oCurrentAccount.isCurrent(false);
		this.currentId(iNewCurrentId);
		oNewCurrentAccount.isCurrent(true);
	}
	
	if (bPassToMail)
	{
		Routing.setHash(LinksUtils.getInbox());
	}
};

/**
 * Changes editable account.
 * 
 * @param {number} iNewEditedId
 */
CAccountListModel.prototype.changeEditedAccount = function (iNewEditedId)
{
	var
		oEditedAccount = this.getEdited(),
		oNewEditedAccount = this.getAccount(iNewEditedId)
	;
	
	if (oNewEditedAccount && this.editedId() !== iNewEditedId)
	{
		oEditedAccount.isEdited(false);
		this.editedId(iNewEditedId);
		oNewEditedAccount.isEdited(true);
	}
};

/**
 * Fills the collection of accounts. Checks for default account. If it is not listed, 
 * then assigns a credit default the first account from the list.
 *
 * @param {number} iDefaultId
 * @param {Array} aAccounts
 */
CAccountListModel.prototype.parse = function (iDefaultId, aAccounts)
{
	var
		oAccount = null,
		bHasDefault = false,
		oDefaultAccount = null,
		oFirstMailAccount = null
	;

	if (_.isArray(aAccounts))
	{
		this.collection(_.map(aAccounts, function (oRawAccount)
		{
			var oTempAccount = new CAccountModel();
			oTempAccount.parse(oRawAccount, iDefaultId);
			if (oTempAccount.id() === iDefaultId)
			{
				bHasDefault = true;
			}
			return oTempAccount;
		}));
	}

	if (!bHasDefault && this.collection.length > 0)
	{
		oAccount = this.collection()[0];
		iDefaultId = oAccount.id();
		bHasDefault = true;
	}

	if (bHasDefault)
	{
		this.defaultId(iDefaultId);
		
		oDefaultAccount = this.getDefault();
		
		if (!oDefaultAccount.allowMail())
		{
			oFirstMailAccount = _.find(this.collection(), function (oTempAccount) {
				return oTempAccount.allowMail();
			});
		}
		
		if (oFirstMailAccount)
		{
			this.currentId(oFirstMailAccount.id());
			this.editedId(iDefaultId);
		}
		else
		{
			this.currentId(iDefaultId);
			this.editedId(iDefaultId);
		}
		
		oDefaultAccount.isDefault(true);
	}
};

/**
 * @return {boolean}
 */
CAccountListModel.prototype.hasMailAccount = function ()
{
	var oAccount = _.find(this.collection(), function (oAcct) {
		return oAcct.allowMail();
	}, this);
	
	return !!oAccount;
};

/**
 * @param {number} iId
 * 
 * @return {Object|undefined}
 */
CAccountListModel.prototype.getAccount = function (iId)
{
	var oAccount = _.find(this.collection(), function (oAcct) {
		return oAcct.id() === iId;
	}, this);
	
	/**	@type {Object|undefined} */
	return oAccount;
};

/**
 * @return {Object|undefined}
 */
CAccountListModel.prototype.getCurrent = function ()
{
	return this.getAccount(this.currentId());
};

/**
 * @return {Object|undefined}
 */
CAccountListModel.prototype.getDefault = function ()
{
	return this.getAccount(this.defaultId());
};

/**
 * @return {Object|undefined}
 */
CAccountListModel.prototype.getEdited = function ()
{
	return this.getAccount(this.editedId());
};

/**
 * @param {number=} iAccountId
 * @return {string}
 */
CAccountListModel.prototype.getEmail = function (iAccountId)
{
	iAccountId = iAccountId || this.currentId();
	
	var
		sEmail = '',
		oAccount = this.getAccount(iAccountId)
	;
	
	if (oAccount)
	{
		sEmail = oAccount.email();
	}
	
	return sEmail;
};

/**
 * @param {Object} oAccount
 */
CAccountListModel.prototype.addAccount = function (oAccount)
{
	var oCurrAccount = this.getCurrent();
	
	this.collection.push(oAccount);
	
	if (!oCurrAccount.allowMail())
	{
		this.changeCurrentAccount(oAccount.id(), false);
	}
};

/**
 * @param {number} iId
 */
CAccountListModel.prototype.deleteAccount = function (iId)
{
	if (this.currentId() === iId)
	{
		this.changeCurrentAccount(this.defaultId(), false);
	}
	
	if (this.editedId() === iId)
	{
		this.changeEditedAccount(this.defaultId());
	}
	
	this.collection.remove(function (oAcct){return oAcct.id() === iId;});
};

/**
 * @param {number} iId
 * 
 * @return {boolean}
 */
CAccountListModel.prototype.hasAccountWithId = function (iId)
{
	var oAccount = _.find(this.collection(), function (oAcct) {
		return oAcct.id() === iId;
	}, this);

	return !!oAccount;
};

CAccountListModel.prototype.populateFetchersIdentities = function ()
{
	this.populateFetchers();
	this.populateIdentities();
};

CAccountListModel.prototype.populateFetchers = function ()
{
	if (Settings.AllowFetcher)
	{
		Ajax.send('GetFetchers', { 'AccountID': this.defaultId() }, this.onGetFetchersResponse, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CAccountListModel.prototype.onGetFetchersResponse = function (oResponse, oRequest)
{
	var
		oFetcherList = null,
		oDefaultAccount = this.getDefault()
	;

	if (Types.isNonEmptyArray(oResponse.Result))
	{
		oFetcherList = new CFetcherListModel();
		oFetcherList.parse(this.defaultId(), oResponse.Result);
	}
	
	oDefaultAccount.fetchers(oFetcherList);
};

CAccountListModel.prototype.populateIdentities = function ()
{
	if (Settings.AllowIdentities && (this.isCurrentAllowsMail() || this.collection().length > 1))
	{
		Ajax.send('GetIdentities', null, this.onGetIdentitiesResponse, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CAccountListModel.prototype.onGetIdentitiesResponse = function (oResponse, oRequest)
{
	var oIdentities = {};
	
	if (Types.isNonEmptyArray(oResponse.Result))
	{
		_.each(oResponse.Result, function (oIdentityData) {
			var
				oIdentity = new CIdentityModel(),
				iAccountId = -1
			;

			oIdentity.parse(oIdentityData);
			iAccountId = oIdentity.accountId();
			if (!oIdentities[iAccountId])
			{
				oIdentities[iAccountId] = [];
			}
			oIdentities[iAccountId].push(oIdentity);
		});
	}

	_.each(this.collection(), function (oAccount) {
		var
			aIdentities = oIdentities[oAccount.id()],
			oIdentity = new CIdentityModel()
		;

		if (!Types.isNonEmptyArray(aIdentities))
		{
			aIdentities = [];
		}

		oIdentity.parse({
			'@Object': 'Object/CIdentity',
			Loyal: true,
			Default: !_.find(aIdentities, function(oIdentity){ return oIdentity.isDefault(); }),
			Email: oAccount.email(),
			Enabled: true,
			FriendlyName: oAccount.friendlyName(),
			IdAccount: oAccount.id(),
			IdIdentity: oAccount.id() * 100000,
			Signature: oAccount.signature(),
			UseSignature: oAccount.useSignature()
		});
		aIdentities.unshift(oIdentity);

		oAccount.identities(aIdentities);
	});
};

/**
 * @param {Object} oSrcAccounts
 */
CAccountListModel.prototype.populateIdentitiesFromSourceAccount = function (oSrcAccounts)
{
	if (oSrcAccounts)
	{
		_.each(this.collection(), function (oAccount) {
			var oSrcAccount = oSrcAccounts.getAccount(oAccount.id());
			if (oSrcAccount)
			{
				oAccount.fetchers(oSrcAccount.fetchers());
				oAccount.identities(oSrcAccount.identities());
				oAccount.signature(oSrcAccount.signature());
				oAccount.useSignature(oSrcAccount.useSignature());
			}
		});
	}
};

CAccountListModel.prototype.getAccountsEmails = function ()
{
	return _.uniq(_.map(this.collection(), function (oAccount) {
		return oAccount.email();
	}));
};

CAccountListModel.prototype.getAllFullEmails = function ()
{
	var aFullEmails = [];
	
	_.each(this.collection(), function (oAccount) {
		if (oAccount)
		{
			aFullEmails.push(oAccount.fullEmail());
			if (oAccount.fetchers() && Types.isNonEmptyArray(oAccount.fetchers().collection()))
			{
				_.each(oAccount.fetchers().collection(), function (oFetcher) {
					if (oFetcher.isOutgoingEnabled() && oFetcher.fullEmail() !== '')
					{
						aFullEmails.push(oFetcher.fullEmail());
					}
				});
			}
			if (Types.isNonEmptyArray(oAccount.identities()))
			{
				_.each(oAccount.identities(), function (oIdentity) {
					aFullEmails.push(oIdentity.fullEmail());
				});
			}
		}
	});
	
	return aFullEmails;
};

CAccountListModel.prototype.getCurrentFetchersAndFiltersFolderNames = function ()
{
	var
		oAccount = this.getCurrent(),
		aFolders = []
	;
	
	if (oAccount)
	{
		if (oAccount.filters())
		{
			_.each(oAccount.filters().collection(), function (oFilter) {
				aFolders.push(oFilter.folder());
			}, this);
		}

		if (oAccount.fetchers())
		{
			_.each(oAccount.fetchers().collection(), function (oFetcher) {
				aFolders.push(oFetcher.folder());
			}, this);
		}
	}
	
	return aFolders;
};

/**
 * @param {Array} aEmails
 * @returns {string}
 */
CAccountListModel.prototype.getAttendee = function (aEmails)
{
	var
		aAccountsEmails = [],
		sAttendee = ''
	;
	
	_.each(this.collection(), function (oAccount) {
		if (oAccount.isCurrent())
		{
			aAccountsEmails = _.union([oAccount.email()], oAccount.getFetchersIdentitiesEmails(), aAccountsEmails);
		}
		else
		{
			aAccountsEmails = _.union(aAccountsEmails, [oAccount.email()], oAccount.getFetchersIdentitiesEmails());
		}
	});
	
	aAccountsEmails = _.uniq(aAccountsEmails);
	
	_.each(aAccountsEmails, _.bind(function (sAccountEmail) {
		if (sAttendee === '')
		{
			var sFoundEmail = _.find(aEmails, function (sEmail) {
				return (sEmail === sAccountEmail);
			});
			if (sFoundEmail === sAccountEmail)
			{
				sAttendee = sAccountEmail;
			}
		}
	}, this));
	
	return sAttendee;
};

CAccountListModel.prototype.displaySocialWelcome = function ()
{
	var
		oDefaultAccount = this.getDefault(),
		bHasMailAccount = this.hasMailAccount()
	;
	
	if (!bHasMailAccount && oDefaultAccount && !Storage.hasData('SocialWelcomeShowed' + oDefaultAccount.id()) && UserSettings.SocialName !== '')
	{
		Popups.showPopup(ConfirmPopup, [
			TextUtils.i18n('MAILBOX/INFO_SOCIAL_WELCOME', {
				'SOCIALNAME': UserSettings.SocialName,
				'SITENAME': UserSettings.SiteName,
				'EMAIL': oDefaultAccount.email()
			}),
			function (bConfigureMail) {
				if (bConfigureMail && !oDefaultAccount.allowMail())
				{
					Popups.showPopup(CreateAccountPopup, [Enums.AccountCreationPopupType.ConnectToMail, oDefaultAccount.email()]);
				}
			},
			'',
			TextUtils.i18n('MAILBOX/BUTTON_CONFIGURE_MAIL'),
			TextUtils.i18n('MAIN/BUTTON_CLOSE')
		]);
		
		Storage.setData('SocialWelcomeShowed' + oDefaultAccount.id(), '1');
	}
};

var AccountList = new CAccountListModel(Types.pInt(window.pSevenAppData.Default));

AccountList.parse(window.pSevenAppData.Default, window.pSevenAppData.Accounts);

if (MainTab)
{
	AccountList.populateIdentitiesFromSourceAccount(MainTab.getAccountList());
}

AccountList.unlockEditedWhenCurrentChange();
AccountList.displaySocialWelcome();

module.exports = AccountList;
