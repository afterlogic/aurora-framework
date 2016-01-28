'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	AddressUtils = require('core/js/utils/Address.js'),
	TextUtils = require('core/js/utils/Text.js'),
	Types = require('core/js/utils/Types.js'),
	
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
function CAccountForwardPaneView()
{
	CAbstractSettingsFormView.call(this, 'Mail');
	
	this.enable = ko.observable(false);
	this.email = ko.observable('');
	this.emailFocus = ko.observable(false);

	Accounts.editedId.subscribe(function () {
		this.populate();
	}, this);
	this.populate();
}

_.extendOwn(CAccountForwardPaneView.prototype, CAbstractSettingsFormView.prototype);

CAccountForwardPaneView.prototype.ViewTemplate = 'Mail_Settings_AccountForwardPaneView';

CAccountForwardPaneView.prototype.getCurrentValues = function ()
{
	return [
		this.enable(),
		this.email()
	];
};

CAccountForwardPaneView.prototype.revert = function ()
{
	this.populate();
};

CAccountForwardPaneView.prototype.getParametersForSave = function ()
{
	var oAccount = Accounts.getEdited();
	return {
		'AccountID': oAccount.id(),
		'Enable': this.enable() ? '1' : '0',
		'Email': this.email()
	};
};

CAccountForwardPaneView.prototype.applySavedValues = function (oParameters)
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

CAccountForwardPaneView.prototype.save = function ()
{
	var
		fSaveData = function() {
			this.isSaving(true);

			this.updateSavedState();

			Ajax.send('UpdateForward', this.getParametersForSave(), this.onResponse, this);
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
CAccountForwardPaneView.prototype.onResponse = function (oResponse, oRequest)
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

CAccountForwardPaneView.prototype.populate = function ()
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
			Ajax.send('GetForward', {'AccountID': oAccount.id()}, this.onGetForwardResponse, this);
		}
	}
	
	this.updateSavedState();
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CAccountForwardPaneView.prototype.onGetForwardResponse = function (oResponse, oRequest)
{
	if (oResponse && oResponse.Result)
	{
		var
			iAccountId = Types.pInt(oResponse.AccountID),
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

module.exports = new CAccountForwardPaneView();
