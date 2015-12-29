'use strict';

var
	ko = require('knockout'),
	
	TextUtils = require('core/js/utils/Text.js'),
	Utils = require('core/js/utils/Common.js'),
	
	Api = require('core/js/Api.js'),
	Screens = require('core/js/Screens.js'),
	
	Popups = require('core/js/Popups.js'),
	ChangePasswordPopup,
	
	Accounts = require('modules/Mail/js/AccountList.js'),
	Ajax = require('modules/Mail/js/Ajax.js'),
	Settings = require('modules/Mail/js/Settings.js'),
	CServerPropertiesViewModel = require('modules/Mail/js/views/CServerPropertiesViewModel.js')
;

/**
 * @constructor
 */ 
function CAccountPropertiesPaneView()
{
	this.bAllowChangeEmailSettings =  Settings.AllowUsersChangeEmailSettings;
	this.bAllowIdentities = Settings.AllowIdentities;
	
	this.account = ko.observable(0);
	
	this.isAllowMail = ko.observable(false);

	this.isInternal = ko.observable(true);
	this.isLinked = ko.observable(true);
	this.isDefault = ko.observable(false);
	this.removeHint = ko.observable('');
	this.canBeRemoved = ko.observable('');
	this.friendlyName = ko.observable('');
	this.email = ko.observable('');
	this.incomingMailLogin = ko.observable('');
	this.incomingMailPassword = ko.observable('');
	this.oIncoming = new CServerPropertiesViewModel(143, 993, 'acc_edit_incoming', TextUtils.i18n('SETTINGS/ACCOUNT_PROPERTIES_INCOMING_MAIL'));
	this.outgoingMailLogin = ko.observable('');
	this.outgoingMailPassword = ko.observable('');
	this.oOutgoing = new CServerPropertiesViewModel(25, 465, 'acc_edit_outgoing', TextUtils.i18n('SETTINGS/ACCOUNT_PROPERTIES_OUTGOING_MAIL'), this.oIncoming.server);

	this.loading = ko.observable(false);

	this.allowChangePassword = ko.observable(false);
	this.useSmtpAuthentication = ko.observable(false);
	
	this.incLoginFocused = ko.observable(false);
	this.incLoginFocused.subscribe(function () {
		if (this.incLoginFocused() && this.incomingMailLogin() === '')
		{
			this.incomingMailLogin(this.email());
		}
	}, this);

	this.firstState = null;
}

CAccountPropertiesPaneView.prototype.ViewTemplate = 'Mail_Settings_AccountPropertiesPaneView';

/**
 * @param {Object} oAccount
 */
CAccountPropertiesPaneView.prototype.onShow = function (oAccount)
{
	this.account(oAccount);
	this.populate();
};

CAccountPropertiesPaneView.prototype.onHide = function ()
{
	this.isAllowMail(false);
};

CAccountPropertiesPaneView.prototype.getState = function ()
{
	var aState = [
		this.friendlyName(),
		this.email(),
		this.incomingMailLogin(),
		this.oIncoming.port(),
		this.oIncoming.server(),
		this.oIncoming.ssl(),
		this.outgoingMailLogin(),
		this.oOutgoing.port(),
		this.oOutgoing.server(),
		this.oOutgoing.ssl(),
		this.useSmtpAuthentication()
	];

	return aState.join(':');
};

CAccountPropertiesPaneView.prototype.updateFirstState = function()
{
	this.firstState = this.getState();
};

CAccountPropertiesPaneView.prototype.isChanged = function()
{
	return !!this.firstState && this.getState() !== this.firstState;
};

CAccountPropertiesPaneView.prototype.populate = function ()
{
	var oAccount = this.account();
	if (oAccount)
	{	
		this.isAllowMail(oAccount.allowMail());
		
		this.allowChangePassword(oAccount.extensionExists('AllowChangePasswordExtension'));

		this.isInternal(oAccount.isInternal());
		this.isLinked(oAccount.isLinked());
		this.isDefault(oAccount.isDefault());
		this.removeHint(oAccount.removeHint());
		this.canBeRemoved(oAccount.canBeRemoved());
		this.useSmtpAuthentication(Utils.pInt(oAccount.outgoingMailAuth()) === 2 ? true : false);
		this.friendlyName(oAccount.friendlyName());
		this.email(oAccount.email());
		this.incomingMailLogin(oAccount.incomingMailLogin());
		this.oIncoming.set(oAccount.incomingMailServer(), oAccount.incomingMailPort(), oAccount.incomingMailSsl());
		this.outgoingMailLogin(oAccount.outgoingMailLogin());
		this.oOutgoing.set(oAccount.outgoingMailServer(), oAccount.outgoingMailPort(), oAccount.outgoingMailSsl());

		this.updateFirstState();
	}
	else
	{
		this.allowChangePassword(false);

		this.isLinked(true);
		this.useSmtpAuthentication(true);
		this.friendlyName('');
		this.email('');
		this.incomingMailLogin('');
		this.oIncoming.clear();
		this.outgoingMailLogin('');
		this.oOutgoing.clear();
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CAccountPropertiesPaneView.prototype.onAccountSettingsUpdateResponse = function (oResponse, oRequest)
{
	this.loading(false);

	if (!oResponse.Result)
	{
		Api.showErrorByCode(oResponse, TextUtils.i18n('SETTINGS/ERROR_SETTINGS_SAVING_FAILED'));
	}
	else
	{
		var
			iAccountId = Utils.pInt(oResponse.AccountID),
			oAccount = 0 < iAccountId ? Accounts.getAccount(iAccountId) : null
		;

		if (oAccount)
		{
			oAccount.updateExtended(oRequest);
			Screens.showReport(TextUtils.i18n('SETTINGS/COMMON_REPORT_UPDATED_SUCCESSFULLY'));
		}
	}
};

/**
 * @return Object
 */
CAccountPropertiesPaneView.prototype.prepareParameters = function ()
{
	var
		oParameters = {
			'Action': 'AccountSettingsUpdate',
			'AccountID': this.account().id(),
			'FriendlyName': this.friendlyName(),
			'Email': this.email(),
			'IncomingMailLogin': this.incomingMailLogin(),
			'IncomingMailServer': this.oIncoming.server(),
			'IncomingMailPort': this.oIncoming.getIntPort(),
			'IncomingMailSsl': this.oIncoming.getIntSsl(),
			'OutgoingMailLogin': this.outgoingMailLogin(),
			'OutgoingMailServer': this.oOutgoing.server(),
			'OutgoingMailPort': this.oOutgoing.getIntPort(),
			'OutgoingMailSsl': this.oOutgoing.getIntSsl(),
			'OutgoingMailAuth': this.useSmtpAuthentication() ? 2 : 0,
			'IncomingMailPassword': this.incomingMailPassword()
		}
	;
	
	return oParameters;
};

/**
 * @param {Object} oParameters
 */
CAccountPropertiesPaneView.prototype.saveData = function (oParameters)
{
	if (this.isAllowMail())
	{
		this.updateFirstState();
		Ajax.send(oParameters, this.onAccountSettingsUpdateResponse, this);
	}
};

/**
 * Sends a request to the server to save the settings.
 */
CAccountPropertiesPaneView.prototype.onSaveClick = function ()
{
	if (this.account() && this.isAllowMail())
	{
		this.loading(true);

		this.saveData(this.prepareParameters());
	}
};

CAccountPropertiesPaneView.prototype.onChangePasswordClick = function ()
{
	Popups.showPopup(ChangePasswordPopup, [false, true]);
};

module.exports = new CAccountPropertiesPaneView();