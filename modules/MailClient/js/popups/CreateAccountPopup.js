'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	TextUtils = require('modules/CoreClient/js/utils/Text.js'),
	Types = require('modules/CoreClient/js/utils/Types.js'),
	
	Api = require('modules/CoreClient/js/Api.js'),
	CAbstractPopup = require('modules/CoreClient/js/popups/CAbstractPopup.js'),
	
	AccountList = require('modules/%ModuleName%/js/AccountList.js'),
	Ajax = require('modules/%ModuleName%/js/Ajax.js'),
	
	CServerPropertiesView = require('modules/%ModuleName%/js/views/CServerPropertiesView.js')
;

/**
 * @constructor
 */
function CCreateAccountPopup()
{
	CAbstractPopup.call(this);
	
	this.defaultAccountId = AccountList.defaultId;

	this.loading = ko.observable(false);

	this.friendlyName = ko.observable('');
	this.email = ko.observable('');
	this.incomingMailLogin = ko.observable('');
	this.incomingLoginFocused = ko.observable(false);
	this.incomingMailPassword = ko.observable('');
	this.oIncoming = new CServerPropertiesView(143, 993, 'acc_create_incoming', TextUtils.i18n('%MODULENAME%/LABEL_IMAP_SERVER'));
	
	this.outgoingMailLogin = ko.observable('');
	this.outgoingMailPassword = ko.observable('');
	this.oOutgoing = new CServerPropertiesView(25, 465, 'acc_create_outgoing', TextUtils.i18n('%MODULENAME%/LABEL_SMTP_SERVER'), this.oIncoming.server);
	
	this.useSmtpAuthentication = ko.observable(true);
	this.friendlyNameFocus = ko.observable(false);
	this.emailFocus = ko.observable(false);
	this.incomingPasswordFocus = ko.observable(false);
	this.incomingMailFocus = ko.observable(false);

	this.isFirstStep = ko.observable(true);
	this.isFirstTitle = ko.observable(true);
	this.isConnectToMailType = ko.observable(false);
	
	this.isFirstStep.subscribe(function (bValue) {
		if (!bValue)
		{
			this.clearServers();
		}
	}, this);

	this.incomingLoginFocused.subscribe(function () {
		if (this.incomingLoginFocused() && this.incomingMailLogin() === '')
		{
			this.incomingMailLogin(this.email());
		}
	}, this);
}


_.extendOwn(CCreateAccountPopup.prototype, CAbstractPopup.prototype);

CCreateAccountPopup.prototype.PopupTemplate = '%ModuleName%_Settings_CreateAccountPopup';

CCreateAccountPopup.prototype.init = function ()
{
	this.isFirstTitle(true);
	
	this.friendlyName('');
	this.email('');
	this.incomingMailLogin('');
	this.incomingLoginFocused(false);
	this.incomingMailPassword('');
	this.outgoingMailLogin('');
	this.outgoingMailPassword('');
	this.oOutgoing.focused(false);

	this.clearServers();
};

CCreateAccountPopup.prototype.clearServers = function ()
{
	this.oIncoming.clear();
	this.oOutgoing.clear();
	this.useSmtpAuthentication(true);
};

/**
 * @param {number} iType
 * @param {string} sEmail
 * @param {Function=} fCallback
 */
CCreateAccountPopup.prototype.onShow = function (iType, sEmail, fCallback)
{
	this.fCallback = fCallback;
	
	this.init();
	
	switch (iType)
	{
		case Enums.AccountCreationPopupType.TwoSteps:
			this.isFirstStep(true);
			this.emailFocus(true);
			break;
		case Enums.AccountCreationPopupType.OneStep:
		case Enums.AccountCreationPopupType.ConnectToMail:
			this.isFirstStep(false);
			this.email(sEmail);
			this.incomingMailLogin(sEmail);
			this.incomingPasswordFocus(true);
			break;
	}
	
	this.isConnectToMailType(iType === Enums.AccountCreationPopupType.ConnectToMail);
};

CCreateAccountPopup.prototype.onHide = function ()
{
	this.init();
};

CCreateAccountPopup.prototype.onFirstSaveClick = function ()
{
	if (!this.isEmptyFirstFields())
	{
		this.loading(true);
		
		Ajax.send('GetDomainData', { 'Email': this.email() }, this.onDomainGetDataByEmailResponse, this);
	}
	else
	{
		this.loading(false);
	}
};

CCreateAccountPopup.prototype.onSecondSaveClick = function ()
{
	if (!this.isEmptySecondFields())
	{
		var
			oDefaultAccount = AccountList.getDefault(),
			bConfigureMail = this.isConnectToMailType() || !oDefaultAccount.allowMail() && oDefaultAccount.email() === this.email(),
			oParameters = {
				'AccountID': this.defaultAccountId(),
				'FriendlyName': this.friendlyName(),
				'Email': this.email(),
				'IncomingMailLogin': this.incomingMailLogin(),
				'IncomingMailPassword': this.incomingMailPassword(),
				'IncomingMailServer': this.oIncoming.server(),
				'IncomingMailPort': this.oIncoming.getIntPort(),
				'IncomingMailSsl': this.oIncoming.getIntSsl(),
				'OutgoingMailLogin': this.outgoingMailLogin(),
				'OutgoingMailPassword': this.outgoingMailPassword(),
				'OutgoingMailServer': this.oOutgoing.server(),
				'OutgoingMailPort': this.oOutgoing.getIntPort(),
				'OutgoingMailSsl': this.oOutgoing.getIntSsl(),
				'OutgoingMailAuth': this.useSmtpAuthentication() ? 2 : 0
			}
		;

		this.loading(true);

		Ajax.send(bConfigureMail ? 'ConfigureMailAccount' : 'CreateAccount', oParameters, this.onAccountCreateResponse, this);
	}
	else
	{
		this.loading(false);
	}
};

CCreateAccountPopup.prototype.onSaveClick = function ()
{
	if (!this.loading())
	{
		if (this.isFirstStep())
		{
			this.onFirstSaveClick();
		}
		else
		{
			this.onSecondSaveClick();
		}
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CCreateAccountPopup.prototype.onDomainGetDataByEmailResponse = function (oResponse, oRequest)
{
	var oResult = oResponse.Result;
	
	this.incomingMailLogin(this.email());

	if (oResult)
	{
		this.oIncoming.set(oResult.IncomingMailServer, oResult.IncomingMailPort, !!oResult.IncomingMailSsl);
		this.oOutgoing.set(oResult.OutgoingMailServer, oResult.OutgoingMailPort, !!oResult.OutgoingMailSsl);

		this.onSecondSaveClick();
	}
	else
	{
		this.loading(false);
		
		this.isFirstStep(false);
		this.isFirstTitle(false);
		this.incomingLoginFocused(true);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CCreateAccountPopup.prototype.onAccountCreateResponse = function (oResponse, oRequest)
{
	this.loading(false);

	if (!oResponse.Result)
	{
		Api.showErrorByCode(oResponse, TextUtils.i18n('%MODULENAME%/ERROR_CREATE_ACCOUNT'));
	}
	else
	{
		var
			iAccountId = Types.pInt(oResponse.Result.IdAccount),
			oAccount = AccountList.getAccount(iAccountId) || new CAccountModel(false)
		;
		
		oAccount.init(iAccountId, oRequest.Email, oRequest.FriendlyName);
		oAccount.updateExtended(oRequest);
		oAccount.setExtensions(oResponse.Result.Extensions);
		
		if (oRequest.Action === 'AccountConfigureMail')
		{
			oAccount.allowMailAfterConfiguring();
			AccountList.collection.valueHasMutated();
			AccountList.initCurrentAccount();
			AccountList.populateIdentities();
			AccountList.currentId.valueHasMutated();
		}
		else
		{
			AccountList.addAccount(oAccount);
			AccountList.populateIdentities();
			AccountList.changeEditedAccount(iAccountId);
		}
		
		if (this.fCallback)
		{
			this.fCallback(iAccountId);
		}
		
		this.closePopup();
	}
};

CCreateAccountPopup.prototype.isEmptyFirstFields = function ()
{
	switch ('')
	{
		case this.email():
			this.emailFocus(true);
			return true;
		case this.incomingMailPassword():
			this.incomingMailFocus(true);
			return true;
		default: return false;
	}
};

CCreateAccountPopup.prototype.isEmptySecondFields = function ()
{
	switch ('')
	{
		case this.email():
			this.emailFocus(true);
			return true;
		case this.incomingMailLogin():
			this.incomingLoginFocused(true);
			return true;
		case this.oIncoming.server():
			this.oIncoming.focused(true);
			return true;
		case this.oOutgoing.server():
			this.oOutgoing.focused(true);
			return true;
		default: return false;
	}
};

module.exports = new CCreateAccountPopup();
