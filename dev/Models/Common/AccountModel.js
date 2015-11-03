
/**
 * @constructor
 */
function CAccountModel()
{
	this.id = ko.observable(0);
	this.email = ko.observable('');
	this.allowMail = ko.observable(true);
	this.passwordSpecified = ko.observable(true);
	
	this.extensions = ko.observableArray([]);
	this.fetchers = ko.observable(null);
	this.identities = ko.observable(null);
	this.friendlyName = ko.observable('');
	this.incomingMailLogin = ko.observable('');
	this.incomingMailServer = ko.observable('');
	this.incomingMailPort = ko.observable(143); 
	this.incomingMailSsl = ko.observable(false); 
	this.isInternal = ko.observable(false); // If **true**, the account is hosted by bundled mailserver.
	this.isLinked = ko.observable(false); // If **true**, the account is belonged to some domain.
	this.isDefault = ko.observable(false);
	this.outgoingMailAuth = ko.observable(0);
	this.outgoingMailLogin = ko.observable('');
	this.outgoingMailServer = ko.observable('');
	this.outgoingMailPort = ko.observable(25);
	this.outgoingMailSsl = ko.observable(false);
	this.isExtended = ko.observable(false);
	this.signature = ko.observable(null);
	this.autoresponder = ko.observable(null);
	this.forward = ko.observable(null);
	this.filters = ko.observable(null);

	this.quota = ko.observable(0);
	this.usedSpace = ko.observable(0);
	this.quotaRecieved = ko.observable(false);

	this.fullEmail = ko.computed(function () {
		return Utils.Address.getFullEmail(this.friendlyName(), this.email());
	}, this);
	
	this.isCurrent = ko.observable(false);
	this.isEdited = ko.observable(false);
	
	this.extensionsRequested = ko.observable(false);
	
	this.canBeRemoved = ko.computed(function () {
		return !this.isInternal() && (!this.isDefault() || this.isDefault() && AppData.App.AllowUsersChangeEmailSettings);
	}, this);
	
	this.removeHint = ko.computed(function () {
		var
			sAndOther = '',
			sHint = ''
		;
		
		if (this.isDefault())
		{
			if (AppData.User.AllowCalendar && AppData.User.ShowContacts)
			{
				sAndOther = Utils.i18n('SETTINGS/ACCOUNTS_REMOVE_CONTACTS_CALENDARS_HINT');
			}
			else if (AppData.User.AllowCalendar)
			{
				sAndOther = Utils.i18n('SETTINGS/ACCOUNTS_REMOVE_CALENDARS_HINT');
			}
			else if (AppData.User.ShowContacts)
			{
				sAndOther = Utils.i18n('SETTINGS/ACCOUNTS_REMOVE_CONTACTS_HINT');
			}
			sHint = Utils.i18n('SETTINGS/ACCOUNTS_REMOVE_DEFAULT_HINT', {'AND_OTHER': sAndOther});
			
			if (AppData.Accounts.collection().length > 1)
			{
				sHint += Utils.i18n('SETTINGS/ACCOUNTS_REMOVE_DEFAULT_NOTSINGLE_HINT');
			}
		}
		else
		{
			sHint = Utils.i18n('SETTINGS/ACCOUNTS_REMOVE_HINT');
		}
		
		return sHint;
	}, this);
	
	this.removeConfirmation = ko.computed(function () {
		if (this.isDefault())
		{
			return this.removeHint() + Utils.i18n('SETTINGS/ACCOUNTS_REMOVE_DEFAULT_CONFIRMATION');
		}
		else
		{
			return Utils.i18n('SETTINGS/ACCOUNTS_REMOVE_CONFIRMATION');
		}
	}, this);
}

/**
 * @param {number} iId
 * @param {string} sEmail
 * @param {string} sFriendlyName
 */
CAccountModel.prototype.init = function (iId, sEmail, sFriendlyName)
{
	this.id(iId);
	this.email(sEmail);
	this.friendlyName(sFriendlyName);
	this.signature(new CSignatureModel());
};

/**
 * @param {Object} oData
 * @param {Object} oParameters
 */
CAccountModel.prototype.onAccountGetQuotaResponse = function (oData, oParameters)
{
	if (oData && oData.Result && _.isArray(oData.Result) && 1 < oData.Result.length)
	{
		this.quota(Utils.pInt(oData.Result[1]));
		this.usedSpace(Utils.pInt(oData.Result[0]));

		App.MailCache.quotaChangeTrigger(!App.MailCache.quotaChangeTrigger());
	}
	
	this.quotaRecieved(true);
};

CAccountModel.prototype.updateQuotaParams = function ()
{
	var
		oParams = {
			'Action': 'AccountGetQuota',
			'AccountID': this.id()
		}
	;
	
	if (AppData.App && AppData.App.ShowQuotaBar && this.allowMail())
	{
		App.Ajax.send(oParams, this.onAccountGetQuotaResponse, this);
	}
};

/**
 * @param {Object} oData
 * @param {number} iDefaultId
 */
CAccountModel.prototype.parse = function (oData, iDefaultId)
{
	this.init(parseInt(oData.AccountID, 10), Utils.pString(oData.Email), Utils.pString(oData.FriendlyName));
		
	this.allowMail(!!oData.AllowMail);

	this.passwordSpecified(!!oData.IsPasswordSpecified);

	this.signature().parse(this.id(), oData.Signature);

	this.isCurrent(iDefaultId === this.id());
	this.isEdited(iDefaultId === this.id());
};

CAccountModel.prototype.requestExtensions = function ()
{
	if (!this.extensionsRequested() && App.Ajax)
	{
		var oTz = window.jstz ? window.jstz.determine() : null;
		App.Ajax.send({
			'AccountID': this.id(),
			'Action': 'SystemIsAuth',
			'ClientTimeZone': oTz ? oTz.name() : ''
		}, this.onSystemIsAuthResponse, this);
	}
};

/**
 * @param {Object} oResult
 * @param {Object} oRequest
 */
CAccountModel.prototype.onSystemIsAuthResponse = function (oResult, oRequest)
{
	var
		bResult = !!oResult.Result,
		aExtensions = bResult ? oResult.Result.Extensions : []
	;
	
	if (bResult)
	{
		this.setExtensions(aExtensions);
		this.extensionsRequested(true);
	}
};

/**
 * @param {Array} aExtensions
 */
CAccountModel.prototype.setExtensions = function(aExtensions)
{
	if (_.isArray(aExtensions))
	{
		this.extensions(aExtensions);
	}
};

/**
 * @param {string} sExtension
 * 
 * return {boolean}
 */
CAccountModel.prototype.extensionExists = function(sExtension)
{
	return (_.indexOf(this.extensions(), sExtension) === -1) ? false : true;
};

CAccountModel.prototype.allowMailAfterConfiguring = function ()
{
	if (!this.allowMail())
	{
		if (this.passwordSpecified())
		{
			App.Screens.showPopup(AlertPopup, [
				Utils.i18n('SETTINGS/ACCOUNTS_WARNING_AFTER_CONFIG_MAIL_HTML', {'EMAIL': this.email()}),
				null,
				Utils.i18n('SETTINGS/ACCOUNTS_WARNING_AFTER_CONFIG_MAIL_TITLE', {'EMAIL': this.email()})
			]);
		}
		
		this.allowMail(true);
		
		App.MailCache.getFolderList(this.id());
	}
};

/**
 * @param {?} ExtendedData
 */
CAccountModel.prototype.updateExtended = function (ExtendedData)
{
	if (ExtendedData)
	{
		this.isExtended(true);
		
		if (Utils.isNormal(ExtendedData.FriendlyName))
		{
			this.friendlyName(ExtendedData.FriendlyName);
		}
		if (Utils.isNormal(ExtendedData.IncomingMailLogin))
		{
			this.incomingMailLogin(ExtendedData.IncomingMailLogin);
		}
		if (Utils.isNormal(ExtendedData.IncomingMailServer))
		{
			this.incomingMailServer(ExtendedData.IncomingMailServer);
		}
		if (Utils.isNormal(ExtendedData.IncomingMailPort))
		{
			this.incomingMailPort(ExtendedData.IncomingMailPort); 
		}		
		if (Utils.isNormal(ExtendedData.IncomingMailSsl))
		{
			this.incomingMailSsl(!!ExtendedData.IncomingMailSsl);
		}
		if (Utils.isNormal(ExtendedData.IsInternal))
		{
			this.isInternal(ExtendedData.IsInternal);
		}
		if (Utils.isNormal(ExtendedData.IsLinked))
		{
			this.isLinked(ExtendedData.IsLinked);
		}
		if (Utils.isNormal(ExtendedData.IsDefault))
		{
			this.isDefault(ExtendedData.IsDefault);
		}
		if (Utils.isNormal(ExtendedData.OutgoingMailAuth))
		{
			this.outgoingMailAuth(ExtendedData.OutgoingMailAuth);
		}
		if (Utils.isNormal(ExtendedData.OutgoingMailLogin))
		{
			this.outgoingMailLogin(ExtendedData.OutgoingMailLogin);
		}
		if (Utils.isNormal(ExtendedData.OutgoingMailServer))
		{
			this.outgoingMailServer(ExtendedData.OutgoingMailServer);
		}
		if (Utils.isNormal(ExtendedData.OutgoingMailPort))
		{
			this.outgoingMailPort(ExtendedData.OutgoingMailPort);
		}
		if (Utils.isNormal(ExtendedData.OutgoingMailSsl))
		{
			this.outgoingMailSsl(!!ExtendedData.OutgoingMailSsl);
		}
		this.setExtensions(ExtendedData.Extensions);
	}
};

CAccountModel.prototype.changeAccount = function()
{
	AppData.Accounts.changeCurrentAccount(this.id(), true);
};

CAccountModel.prototype.getDefaultIdentity = function()
{
	return _.find(this.identities() || [], function (oIdentity) {
		return oIdentity.isDefault();
	});
};

/**
 * @returns {Array}
 */
CAccountModel.prototype.getFetchersIdentitiesEmails = function()
{
	var
		aFetchers = this.fetchers() ? this.fetchers().collection() : [],
		aIdentities = this.identities() || [],
		aEmails = []
	;
	
	_.each(aFetchers, function (oFetcher) {
		aEmails.push(oFetcher.email());
	});
	
	_.each(aIdentities, function (oIdentity) {
		aEmails.push(oIdentity.email());
	});
	
	return aEmails;
};

/**
 * Shows popup to confirm removing if it can be removed.
 * 
 * @param {Function} fAfterRemoveHandler This function should be executed after removing the account.
 */
CAccountModel.prototype.remove = function(fAfterRemoveHandler)
{
	var fCallBack = _.bind(this.confirmedRemove, this);
	
	if (this.canBeRemoved())
	{
		this.fAfterRemoveHandler = fAfterRemoveHandler;
		App.Screens.showPopup(ConfirmPopup, [this.removeConfirmation(), fCallBack, this.email()]);
	}
};

/**
 * Sends a request to the server for deletion account if received confirmation from the user.
 * 
 * @param {boolean} bOkAnswer
 */
CAccountModel.prototype.confirmedRemove = function(bOkAnswer)
{
	var
		oParameters = {
			'Action': 'AccountDelete',
			'AccountIDToDelete': this.id()
		}
	;
	
	if (bOkAnswer)
	{
		App.Ajax.send(oParameters, this.onAccountDeleteResponse, this);
	}
	else
	{
		this.fAfterRemoveHandler = undefined;
	}
};

/**
 * Receives response from the server and removes account from js-application if removal operation on the server was successful.
 * 
 * @param {Object} oResponse Response obtained from the server.
 * @param {Object} oRequest Parameters has been transferred to the server.
 */
CAccountModel.prototype.onAccountDeleteResponse = function (oResponse, oRequest)
{
	if (!oResponse.Result)
	{
		App.Api.showErrorByCode(oResponse, Utils.i18n('WARNING/REMOVING_ACCOUNT_ERROR'));
	}
	else
	{
		App.Api.closeComposePopup();
		
		AppData.Accounts.deleteAccount(this.id());
		
		if (this.isDefault())
		{
			Utils.Common.clearAndReloadLocation(App.browser.ie8AndBelow, true);
		}
		else if (typeof this.fAfterRemoveHandler === 'function')
		{
			this.fAfterRemoveHandler();
			this.fAfterRemoveHandler = undefined;
		}
	}
};
