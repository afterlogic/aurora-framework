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
function CSignaturePaneView()
{
	CAbstractSettingsFormView.call(this, 'Mail');
	
	this.bInitialized = false;
	
	this.fetcherOrIdentity = ko.observable(null);
	
	this.useSignatureRadio = ko.observable('0');
	this.signature = ko.observable('');

	this.oHtmlEditor = new CHtmlEditorView(true);
	this.enableImageDragNDrop = ko.observable(false);

	this.enabled = ko.observable(true);

	Accounts.editedId.subscribe(function () {
		this.populate();
	}, this);
	this.populate();
}

_.extendOwn(CSignaturePaneView.prototype, CAbstractSettingsFormView.prototype);

CSignaturePaneView.prototype.ViewTemplate = 'Mail_Settings_SignaturePaneView';

CSignaturePaneView.prototype.__name = 'CSignaturePaneView';

/**
 * @param {Object} oFetcherOrIdentity
 */
CSignaturePaneView.prototype.show = function (oFetcherOrIdentity)
{
	this.fetcherOrIdentity(oFetcherOrIdentity);
	this.populate();
	_.defer(_.bind(this.init, this));
};

CSignaturePaneView.prototype.init = function ()
{
	if (!this.bInitialized)
	{
		this.oHtmlEditor.initCrea(this.signature(), false, '');
		this.oHtmlEditor.setActivitySource(this.useSignatureRadio);
		this.oHtmlEditor.resize();
		this.enableImageDragNDrop(this.oHtmlEditor.editorUploader.isDragAndDropSupported() && !Browser.ie10AndAbove);
		this.bInitialized = true;
	}
};

CSignaturePaneView.prototype.getCurrentValues = function ()
{
	this.signature(this.oHtmlEditor.getNotDefaultText());
	return [
		this.useSignatureRadio(),
		this.signature()
	];
};

CSignaturePaneView.prototype.revert = function ()
{
	this.populate();
};

CSignaturePaneView.prototype.getParametersForSave = function ()
{
	this.signature(this.oHtmlEditor.getNotDefaultText());
	
	var
		oAccount = Accounts.getEdited(),
		oParameters = {
			'AccountID': oAccount ? oAccount.id() : 0,
			'UseSignature': !!this.useSignatureRadio() ? 1 : 0,
			'Signature': this.signature()
		}
	;
	
	if (this.fetcherOrIdentity())
	{
		if (this.fetcherOrIdentity().FETCHER)
		{
			_.extendOwn(oParameters, { 'FetcherId': this.fetcherOrIdentity().id() });
		}
		else
		{
			_.extendOwn(oParameters, { 'IdentityId': this.fetcherOrIdentity().id() });
		}
	}
	
	return oParameters;
};

/**
 * @param {Object} oParameters
 */
CSignaturePaneView.prototype.applySavedValues = function (oParameters)
{
	var oAccount = Accounts.getEdited();
	
	if (oAccount)
	{
		oAccount.useSignature(!!oParameters.UseSignature);
		oAccount.signature(oParameters.Signature);
	}
};

CSignaturePaneView.prototype.populate = function ()
{
	var
		oAccount = Accounts.getEdited(),
		oSignature = this.fetcherOrIdentity() || oAccount
	;
	
	if (oSignature)
	{
		this.useSignatureRadio(oSignature.useSignature() ? '1' : '0');
		this.signature(oSignature.signature());
		this.oHtmlEditor.setText(this.signature());
	}
	else
	{
		Ajax.send('GetSignature', {'AccountID': oAccount.id()}, this.onGetSignatureResponse, this);
	}
	
	this.updateSavedState();
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CSignaturePaneView.prototype.onGetSignatureResponse = function (oResponse, oRequest)
{
	if (oResponse && oResponse.Result)
	{
		var
			iAccountId = Utils.pInt(oResponse.AccountID),
			oAccount = Accounts.getAccount(iAccountId)
		;

		if (oAccount)
		{
			this.parseSignature(oResponse.Result);

			if (iAccountId === Accounts.editedId())
			{
				this.populate();
			}
		}
	}
};

CSignaturePaneView.prototype.save = function ()
{
	this.isSaving(true);
	
	this.updateSavedState();
	
	Ajax.send('UpdateSignature', this.getParametersForSave(), this.onResponse, this);
};

/**
 * Parses the response from the server. If the settings are normally stored, then updates them. 
 * Otherwise an error message.
 * 
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CSignaturePaneView.prototype.onResponse = function (oResponse, oRequest)
{
	this.isSaving(false);
	
	if (oResponse.Result)
	{
		Screens.showReport(TextUtils.i18n('SETTINGS/COMMON_REPORT_UPDATED_SUCCESSFULLY'));
	}
	else
	{
		Api.showErrorByCode(oResponse, TextUtils.i18n('SETTINGS/ERROR_SETTINGS_SAVING_FAILED'));
	}
};

module.exports = new CSignaturePaneView();
