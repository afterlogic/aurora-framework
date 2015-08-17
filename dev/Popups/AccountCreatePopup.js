/**
 * @constructor
 */
function AccountCreatePopup()
{
	this.defaultAccountId = AppData.Accounts.defaultId;

	this.loading = ko.observable(false);

	this.friendlyName = ko.observable('');
	this.email = ko.observable('');
	this.incomingMailLogin = ko.observable('');
	this.incomingLoginFocused = ko.observable(false);
	this.incomingMailPassword = ko.observable('');
	this.oIncoming = new CServerPropertiesViewModel(143, 993, 'acc_create_incoming', Utils.i18n('SETTINGS/ACCOUNT_PROPERTIES_INCOMING_MAIL'));
	
	this.outgoingMailLogin = ko.observable('');
	this.outgoingMailPassword = ko.observable('');
	this.oOutgoing = new CServerPropertiesViewModel(25, 465, 'acc_create_outgoing', Utils.i18n('SETTINGS/ACCOUNT_PROPERTIES_OUTGOING_MAIL'), this.oIncoming.server);
	
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

/**
 * @return {string}
 */
AccountCreatePopup.prototype.popupTemplate = function ()
{
	return 'Popups_AccountCreatePopupViewModel';
};

AccountCreatePopup.prototype.init = function ()
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

AccountCreatePopup.prototype.clearServers = function ()
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
AccountCreatePopup.prototype.onShow = function (iType, sEmail, fCallback)
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

AccountCreatePopup.prototype.onFirstSaveClick = function ()
{
	if (!this.isEmptyFirstFields())
	{
		var
			oParameters = {
				'Action': 'DomainGetDataByEmail',
				'Email': this.email()
			}
		;

		this.loading(true);
		
		App.Ajax.send(oParameters, this.onDomainGetDataByEmailResponse, this);
	}
	else
	{
		this.loading(false);
	}
};

AccountCreatePopup.prototype.onSecondSaveClick = function ()
{
	if (!this.isEmptySecondFields())
	{
		var
			oDefaultAccount = AppData.Accounts.getDefault(),
			bConfigureMail = this.isConnectToMailType() || !oDefaultAccount.allowMail() && oDefaultAccount.email() === this.email(),
			oParameters = {
				'Action': bConfigureMail ? 'AccountConfigureMail' : 'AccountCreate',
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

		App.Ajax.send(oParameters, this.onAccountCreateResponse, this);
	}
	else
	{
		this.loading(false);
	}
};

AccountCreatePopup.prototype.onSaveClick = function ()
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

AccountCreatePopup.prototype.onCancelClick = function ()
{
	this.closeCommand();
	this.init();
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
AccountCreatePopup.prototype.onDomainGetDataByEmailResponse = function (oResponse, oRequest)
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
AccountCreatePopup.prototype.onAccountCreateResponse = function (oResponse, oRequest)
{
	this.loading(false);

	if (!oResponse.Result)
	{
		App.Api.showErrorByCode(oResponse, Utils.i18n('WARNING/CREATING_ACCOUNT_ERROR'));
	}
	else
	{
		var
			iAccountId = Utils.pInt(oResponse.Result.IdAccount),
			oAccount = AppData.Accounts.getAccount(iAccountId) || new CAccountModel()
		;
		
		oAccount.init(iAccountId, oRequest.Email, oRequest.FriendlyName);
		oAccount.updateExtended(oRequest);
		oAccount.setExtensions(oResponse.Result.Extensions);
		
		if (oRequest.Action === 'AccountConfigureMail')
		{
			oAccount.allowMailAfterConfiguring();
			AppData.Accounts.collection.valueHasMutated();
			AppData.Accounts.checkIfMailAllowed();
			AppData.Accounts.populateIdentities();
			AppData.Accounts.currentId.valueHasMutated();
		}
		else
		{
			AppData.Accounts.addAccount(oAccount);
			AppData.Accounts.populateIdentities();
			AppData.Accounts.changeEditedAccount(iAccountId);
		}
		
		if (this.fCallback)
		{
			this.fCallback(iAccountId);
		}
		
		this.closeCommand();
	}
};

AccountCreatePopup.prototype.isEmptyFirstFields = function ()
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

AccountCreatePopup.prototype.isEmptySecondFields = function ()
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