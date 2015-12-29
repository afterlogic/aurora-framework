'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	TextUtils = require('core/js/utils/Text.js'),
	
	Api = require('core/js/Api.js'),
	Browser = require('core/js/Browser.js'),
	Screens = require('core/js/Screens.js'),
	ModulesManager = require('core/js/ModulesManager.js'),
	CAbstractSettingsFormView = ModulesManager.run('Settings', 'getAbstractSettingsFormViewClass'),
	
	Accounts = require('modules/Mail/js/AccountList.js'),
	Ajax = require('modules/Mail/js/Ajax.js'),
	CHtmlEditorView = require('modules/Mail/js/views/CHtmlEditorView.js')
;

/**
 * @constructor
 */ 
function CAccountSignaturePageView()
{
	CAbstractSettingsFormView.call(this);
	
	this.bInitialized = false;
	
	this.type = ko.observable(false);
	this.useSignature = ko.observable('0');
	this.signature = ko.observable('');

	this.oHtmlEditor = new CHtmlEditorView(true);
	this.enableImageDragNDrop = ko.observable(false);

	this.enabled = ko.observable(true);

	Accounts.editedId.subscribe(function () {
		this.populate();
	}, this);
	this.populate();
}

_.extendOwn(CAccountSignaturePageView.prototype, CAbstractSettingsFormView.prototype);

CAccountSignaturePageView.prototype.ViewTemplate = 'Mail_Settings_AccountSignaturePageView';

CAccountSignaturePageView.prototype.__name = 'CAccountSignaturePageView';

CAccountSignaturePageView.prototype.show = function ()
{
	this.populate();
	_.defer(this.init.bind(this));
};

CAccountSignaturePageView.prototype.init = function ()
{
	if (!this.bInitialized)
	{
		this.oHtmlEditor.initCrea(this.signature(), false, '');
		this.oHtmlEditor.setActivitySource(this.useSignature);
		this.oHtmlEditor.resize();
		this.enableImageDragNDrop(this.oHtmlEditor.editorUploader.isDragAndDropSupported() && !Browser.ie10AndAbove);
		this.bInitialized = true;
	}
};

CAccountSignaturePageView.prototype.getCurrentValues = function ()
{
	this.signature(this.oHtmlEditor.getNotDefaultText());
	return [
		this.type(),
		this.useSignature(),
		this.signature()
	];
};

CAccountSignaturePageView.prototype.revert = function ()
{
	this.populate();
};

CAccountSignaturePageView.prototype.getParametersForSave = function ()
{
	var oAccount = Accounts.getEdited();
	this.signature(this.oHtmlEditor.getNotDefaultText());
	return {
		'AccountID': oAccount ? oAccount.id() : 0,
		'Type': this.type() ? 1 : 0,
		'Options': this.useSignature(),
		'Signature': this.signature()
	};
};

CAccountSignaturePageView.prototype.applySavedValues = function (oParameters)
{
	var
		oAccount = Accounts.getEdited(),
		oSignature = oAccount ? oAccount.signature() : null
	;
	if (oSignature)
	{
		oSignature.type(oParameters.Type === 1);
		oSignature.options(oParameters.Options);
		oSignature.signature(oParameters.Signature);
	}
};

CAccountSignaturePageView.prototype.populate = function ()
{
	var
		oAccount = Accounts.getEdited(),
		oSignature = oAccount ? oAccount.signature() : null
	;
	
	if (oAccount)
	{
		if (oSignature !== null)
		{
			this.type(oSignature.type());
			this.useSignature(!!oSignature.options() ? '1' : '0');
			this.signature(oSignature.signature());
			this.oHtmlEditor.setText(this.signature());
		}
		else
		{
			Ajax.send('AccountSignatureGet', {'AccountID': oAccount.id()}, this.onAccountSignatureGetResponse, this);
		}
	}
	
	this.updateSavedState();
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CAccountSignaturePageView.prototype.onAccountSignatureGetResponse = function (oResponse, oRequest)
{
	if (oResponse && oResponse.Result)
	{
		var
			iAccountId = Utils.pInt(oResponse.AccountID),
			oAccount = Accounts.getAccount(iAccountId),
			oSignature = new CSignatureModel()
		;

		if (oAccount)
		{
			oSignature.parse(iAccountId, oResponse.Result);
			oAccount.signature(oSignature);

			if (iAccountId === Accounts.editedId())
			{
				this.populate();
			}
		}
	}
};

CAccountSignaturePageView.prototype.save = function ()
{
	this.isSaving(true);
	
	this.updateSavedState();
	
	Ajax.send('AccountSignatureUpdate', this.getParametersForSave(), this.onResponse, this);
};

/**
 * Parses the response from the server. If the settings are normally stored, then updates them. 
 * Otherwise an error message.
 * 
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CAccountSignaturePageView.prototype.onAccountSignatureUpdateResponse = function (oResponse, oRequest)
{
	if (oResponse.Result)
	{
		Screens.showReport(TextUtils.i18n('SETTINGS/COMMON_REPORT_UPDATED_SUCCESSFULLY'));
	}
	else
	{
		Api.showErrorByCode(oResponse, TextUtils.i18n('SETTINGS/ERROR_SETTINGS_SAVING_FAILED'));
	}
};

module.exports = new CAccountSignaturePageView();
