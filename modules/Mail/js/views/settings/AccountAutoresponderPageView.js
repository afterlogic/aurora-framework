'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	TextUtils = require('core/js/utils/Text.js'),
	Utils = require('core/js/utils/Common.js'),
	
	Api = require('core/js/Api.js'),
	Screens = require('core/js/Screens.js'),
	ModulesManager = require('core/js/ModulesManager.js'),
	CAbstractSettingsFormView = ModulesManager.run('Settings', 'getAbstractSettingsFormViewClass'),
	
	Accounts = require('modules/Mail/js/AccountList.js'),
	Ajax = require('modules/Mail/js/Ajax.js')
;

/**
 * @constructor
 */ 
function CAccountAutoresponderPageView()
{
	CAbstractSettingsFormView.call(this);
	
	this.enable = ko.observable(false);
	this.subject = ko.observable('');
	this.message = ko.observable('');

	Accounts.editedId.subscribe(function () {
		this.populate();
	}, this);
	this.populate();
}

_.extendOwn(CAccountAutoresponderPageView.prototype, CAbstractSettingsFormView.prototype);

CAccountAutoresponderPageView.prototype.ViewTemplate = 'Mail_Settings_AccountAutoresponderPageView';

CAccountAutoresponderPageView.prototype.getCurrentValues = function ()
{
	return [
		this.enable(),
		this.subject(),
		this.message()	
	];
};

CAccountAutoresponderPageView.prototype.revert = function ()
{
	this.populate();
};

CAccountAutoresponderPageView.prototype.getParametersForSave = function ()
{
	var oAccount = Accounts.getEdited();
	return {
		'AccountID': oAccount.id(),
		'Enable': this.enable() ? '1' : '0',
		'Subject': this.subject(),
		'Message': this.message()
	};
};

CAccountAutoresponderPageView.prototype.applySavedValues = function (oParameters)
{
	var
		oAccount = Accounts.getEdited(),
		oAutoresponder = oAccount.autoresponder()
	;

	if (oAutoresponder)
	{
		oAutoresponder.enable = oParameters.Enable === '1';
		oAutoresponder.subject = oParameters.Subject;
		oAutoresponder.message = oParameters.Message;
	}
};

CAccountAutoresponderPageView.prototype.save = function ()
{
	this.isSaving(true);
	
	this.updateSavedState();
	
	Ajax.send('AccountAutoresponderUpdate', this.getParametersForSave(), this.onResponse, this);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CAccountAutoresponderPageView.prototype.onResponse = function (oResponse, oRequest)
{
	this.isSaving(false);

	if (oResponse.Result === false)
	{
		Api.showErrorByCode(oResponse, TextUtils.i18n('SETTINGS/ERROR_SETTINGS_SAVING_FAILED'));
	}
	else
	{
		var oParameters = JSON.parse(oRequest.Parameters);
		
		this.updateEditableValues(oParameters);
		
		Screens.showReport(TextUtils.i18n('SETTINGS/ACCOUNT_AUTORESPONDER_SUCCESS_REPORT'));
	}
};

CAccountAutoresponderPageView.prototype.populate = function()
{
	var oAccount = Accounts.getEdited();
	
	if (oAccount)
	{
		if (oAccount.autoresponder() !== null)
		{
			this.enable(oAccount.autoresponder().enable);
			this.subject(oAccount.autoresponder().subject);
			this.message(oAccount.autoresponder().message);
		}
		else
		{
			Ajax.send('AccountAutoresponderGet', {'AccountID': oAccount.id()}, this.onAccountAutoresponderGetResponse, this);
		}
	}
	
	this.updateSavedState();
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CAccountAutoresponderPageView.prototype.onAccountAutoresponderGetResponse = function (oResponse, oRequest)
{
	if (oResponse && oResponse.Result)
	{
		var
			iAccountId = Utils.pInt(oResponse.AccountID),
			oAccount = Accounts.getAccount(iAccountId),
			oAutoresponder = new CAutoresponderModel()
		;

		if (oAccount)
		{
			oAutoresponder.parse(iAccountId, oResponse.Result);
			oAccount.autoresponder(oAutoresponder);

			if (iAccountId === Accounts.editedId())
			{
				this.populate();
			}
		}
	}
};

module.exports = new CAccountAutoresponderPageView();
