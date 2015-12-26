'use strict';

var
	ko = require('knockout'),
	
	TextUtils = require('core/js/utils/Text.js'),
	
	Api = require('core/js/Api.js'),
	Browser = require('core/js/Browser.js'),
	Screens = require('core/js/Screens.js'),
	
	Ajax = require('modules/Mail/js/Ajax.js'),
	CHtmlEditorView = require('modules/Mail/js/views/CHtmlEditorView.js')
;

/**
 * @param {?=} oParent
 *
 * @constructor
 */ 
function CAccountSignaturePageView(oParent)
{
	this.parent = oParent;

	this.account = ko.observable(0);

	this.type = ko.observable(false);
	this.useSignature = ko.observable(0);
	this.signature = ko.observable('');

	this.loading = ko.observable(false);

	this.account.subscribe(function () {
		this.getSignature();
	}, this);
	
	this.oHtmlEditor = new CHtmlEditorView(true);
	this.enableImageDragNDrop = ko.observable(false);

	this.enabled = ko.observable(true);

	this.signature.subscribe(function () {
		this.oHtmlEditor.setText(this.signature());
	}, this);
	
	this.getSignature();
	
	this.firstState = null;
}

CAccountSignaturePageView.prototype.ViewTemplate = 'Mail_Settings_AccountSignaturePageView';

CAccountSignaturePageView.prototype.__name = 'CAccountSignaturePageView';

/**
 * @param {Object} oAccount
 */
CAccountSignaturePageView.prototype.onShow = function (oAccount)
{
	this.account(oAccount);

	this.oHtmlEditor.initCrea(this.signature(), false, '');
	this.oHtmlEditor.setActivitySource(this.useSignature);
	this.oHtmlEditor.resize();
	this.enableImageDragNDrop(this.oHtmlEditor.editorUploader.isDragAndDropSupported() && !Browser.ie10AndAbove);
	
	this.updateFirstState();
};

CAccountSignaturePageView.prototype.getState = function ()
{
	var aState = [
		this.type(),
		this.useSignature(),
		this.oHtmlEditor.getText()
	];
	return aState.join(':');
};

CAccountSignaturePageView.prototype.updateFirstState = function ()
{
	this.firstState = this.getState();
};

CAccountSignaturePageView.prototype.isChanged = function ()
{
	return this.firstState && this.getState() !== this.firstState;
};
	
CAccountSignaturePageView.prototype.getSignature = function ()
{
	if (this.account())
	{
		if (this.account().signature() !== null)
		{
			this.type(this.account().signature().type());
			this.useSignature(this.account().signature().options());
			this.signature(this.account().signature().signature());
			this.updateFirstState();
		}
		else
		{
			Ajax.send('AccountSignatureGet', {'AccountID': this.account().id()}, this.onAccountSignatureGetResponse, this);
		}
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CAccountSignaturePageView.prototype.onAccountSignatureGetResponse = function (oResponse, oRequest)
{
	var
		oSignature = null,
		iAccountId = parseInt(oResponse.AccountID, 10)
	;
	
	if (!oResponse.Result)
	{
		Api.showErrorByCode(oResponse);
	}
	else
	{
		if (this.account() && iAccountId === this.account().id())
		{
			oSignature = new CSignatureModel();
			oSignature.parse(iAccountId, oResponse.Result);

			this.account().signature(oSignature);

			this.type(this.account().signature().type());
			this.useSignature(this.account().signature().options());
			this.signature(this.account().signature().signature());
			this.updateFirstState();
		}
	}
};

CAccountSignaturePageView.prototype.prepareParameters = function ()
{
	return {
		'AccountID': this.account().id(),
		'Type': this.type() ? 1 : 0,
		'Options': this.useSignature(),
		'Signature': this.signature()
	};
};

/**
 * @param {Object} oParameters
 */
CAccountSignaturePageView.prototype.saveData = function (oParameters)
{
	this.updateFirstState();
	Ajax.send('AccountSignatureUpdate', oParameters, this.onAccountSignatureUpdateResponse, this);
};

CAccountSignaturePageView.prototype.onSaveClick = function ()
{
	if (this.account())
	{
		this.loading(true);

		this.signature(this.oHtmlEditor.getNotDefaultText());
		
		this.account().signature().type(this.type());
		this.account().signature().options(this.useSignature());
		this.account().signature().signature(this.signature());
		
		this.saveData(this.prepareParameters());
	}
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
	this.loading(false);

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
