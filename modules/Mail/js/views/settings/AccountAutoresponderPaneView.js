'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	TextUtils = require('modules/Core/js/utils/Text.js'),
	Types = require('modules/Core/js/utils/Types.js'),
	
	Api = require('modules/Core/js/Api.js'),
	ModulesManager = require('modules/Core/js/ModulesManager.js'),
	Screens = require('modules/Core/js/Screens.js'),
	
	CAbstractSettingsFormView = ModulesManager.run('Settings', 'getAbstractSettingsFormViewClass'),
	
	AccountList = require('modules/Mail/js/AccountList.js'),
	Ajax = require('modules/Mail/js/Ajax.js')
;

/**
 * @constructor
 */ 
function CAccountAutoresponderPaneView()
{
	CAbstractSettingsFormView.call(this, 'Mail');
	
	this.enable = ko.observable(false);
	this.subject = ko.observable('');
	this.message = ko.observable('');

	AccountList.editedId.subscribe(function () {
		this.populate();
	}, this);
	this.populate();
}

_.extendOwn(CAccountAutoresponderPaneView.prototype, CAbstractSettingsFormView.prototype);

CAccountAutoresponderPaneView.prototype.ViewTemplate = 'Mail_Settings_AccountAutoresponderPaneView';

CAccountAutoresponderPaneView.prototype.getCurrentValues = function ()
{
	return [
		this.enable(),
		this.subject(),
		this.message()	
	];
};

CAccountAutoresponderPaneView.prototype.revert = function ()
{
	this.populate();
};

CAccountAutoresponderPaneView.prototype.getParametersForSave = function ()
{
	return {
		'AccountID': AccountList.editedId(),
		'Enable': this.enable() ? '1' : '0',
		'Subject': this.subject(),
		'Message': this.message()
	};
};

CAccountAutoresponderPaneView.prototype.applySavedValues = function (oParameters)
{
	var
		oAccount = AccountList.getEdited(),
		oAutoresponder = oAccount.autoresponder()
	;

	if (oAutoresponder)
	{
		oAutoresponder.enable = oParameters.Enable === '1';
		oAutoresponder.subject = oParameters.Subject;
		oAutoresponder.message = oParameters.Message;
	}
};

CAccountAutoresponderPaneView.prototype.save = function ()
{
	this.isSaving(true);
	
	this.updateSavedState();
	
	Ajax.send('UpdateAutoresponder', this.getParametersForSave(), this.onResponse, this);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CAccountAutoresponderPaneView.prototype.onResponse = function (oResponse, oRequest)
{
	this.isSaving(false);

	if (oResponse.Result === false)
	{
		Api.showErrorByCode(oResponse, TextUtils.i18n('CORE/ERROR_SAVING_SETTINGS_FAILED'));
	}
	else
	{
		var oParameters = oRequest.Parameters;
		
		this.applySavedValues(oParameters);
		
		Screens.showReport(TextUtils.i18n('MAIL/REPORT_AUTORESPONDER_UPDATE_SUCCESS'));
	}
};

CAccountAutoresponderPaneView.prototype.populate = function()
{
	var oAccount = AccountList.getEdited();
	
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
			Ajax.send('GetAutoresponder', {'AccountID': oAccount.id()}, this.onGetAutoresponderResponse, this);
		}
	}
	
	this.updateSavedState();
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CAccountAutoresponderPaneView.prototype.onGetAutoresponderResponse = function (oResponse, oRequest)
{
	if (oResponse && oResponse.Result)
	{
		var
			oParameters = oRequest.Parameters,
			iAccountId = Types.pInt(oParameters.AccountID),
			oAccount = AccountList.getAccount(iAccountId),
			oAutoresponder = new CAutoresponderModel()
		;

		if (oAccount)
		{
			oAutoresponder.parse(iAccountId, oResponse.Result);
			oAccount.autoresponder(oAutoresponder);

			if (iAccountId === AccountList.editedId())
			{
				this.populate();
			}
		}
	}
};

module.exports = new CAccountAutoresponderPaneView();
