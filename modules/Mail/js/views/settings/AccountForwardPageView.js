'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	AddressUtils = require('core/js/utils/Address.js'),
	TextUtils = require('core/js/utils/Text.js'),
	Utils = require('core/js/utils/Common.js'),
	
	Api = require('core/js/Api.js'),
	Screens = require('core/js/Screens.js'),
	ModulesManager = require('core/js/ModulesManager.js'),
	CAbstractSettingsFormView = ModulesManager.run('Settings', 'getAbstractSettingsFormViewClass'),
	
	Popups = require('core/js/Popups.js'),
	AlertPopup = require('core/js/popups/AlertPopup.js'),
	
	Accounts = require('modules/Mail/js/AccountList.js'),
	Ajax = require('modules/Mail/js/Ajax.js')
;

/**
 * @constructor
 */
function CAccountForwardPageView()
{
	CAbstractSettingsFormView.call(this);
	
	this.enable = ko.observable(false);
	this.email = ko.observable('');
	this.emailFocus = ko.observable(false);

	Accounts.editedId.subscribe(function () {
		this.populate();
	}, this);
	this.populate();
}

_.extendOwn(CAccountForwardPageView.prototype, CAbstractSettingsFormView.prototype);

CAccountForwardPageView.prototype.ViewTemplate = 'Mail_Settings_AccountForwardPageView';

CAccountForwardPageView.prototype.getCurrentValues = function ()
{
	return [
		this.enable(),
		this.email()
	];
};

CAccountForwardPageView.prototype.revert = function ()
{
	this.populate();
};

CAccountForwardPageView.prototype.getParametersForSave = function ()
{
	var oAccount = Accounts.getEdited();
	return {
		'AccountID': oAccount.id(),
		'Enable': this.enable() ? '1' : '0',
		'Email': this.email()
	};
};

CAccountForwardPageView.prototype.applySavedValues = function (oParameters)
{
	var
		oAccount = Accounts.getEdited(),
		oForward = oAccount.forward()
	;
	
	if (oForward)
	{
		oForward.enable = oParameters.Enable === '1';
		oForward.email = oParameters.Email;
	}
};

CAccountForwardPageView.prototype.save = function ()
{
	var
		fSaveData = function() {
			this.isSaving(true);

			this.updateSavedState();

			Ajax.send('AccountForwardUpdate', this.getParametersForSave(), this.onResponse, this);
		}.bind(this)
	;

	if (this.enable() && this.email() === '')
	{
		this.emailFocus(true);
	}
	else if (this.enable() && this.email() !== '')
	{
		if (!AddressUtils.isCorrectEmail(this.email()))
		{
			Popups.showPopup(AlertPopup, [TextUtils.i18n('COMPOSE/WARNING_INPUT_CORRECT_EMAILS') + ' ' + this.email()]);
		}
		else
		{
			fSaveData();
		}
	}
	else
	{
		fSaveData();
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CAccountForwardPageView.prototype.onResponse = function (oResponse, oRequest)
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
		
		Screens.showReport(TextUtils.i18n('SETTINGS/ACCOUNT_FORWARD_SUCCESS_REPORT'));
	}
};

CAccountForwardPageView.prototype.populate = function ()
{
	var oAccount = Accounts.getEdited();
	
	if (oAccount)
	{
		if (oAccount.forward() !== null)
		{
			this.enable(oAccount.forward().enable);
			this.email(oAccount.forward().email);
		}
		else
		{
			Ajax.send('AccountForwardGet', {'AccountID': oAccount.id()}, this.onAccountForwardGetResponse, this);
		}
	}
	
	this.updateSavedState();
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CAccountForwardPageView.prototype.onAccountForwardGetResponse = function (oResponse, oRequest)
{
	if (oResponse && oResponse.Result)
	{
		var
			iAccountId = Utils.pInt(oResponse.AccountID),
			oAccount = Accounts.getAccount(iAccountId),
			oForward = new CForwardModel()
		;

		if (oAccount)
		{
			oForward.parse(iAccountId, oResponse.Result);
			oAccount.forward(oForward);

			if (iAccountId === Accounts.editedId())
			{
				this.populate();
			}
		}
	}
};

module.exports = new CAccountForwardPageView();
