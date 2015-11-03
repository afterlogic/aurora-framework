/**
 * @param {?=} oParent
 *
 * @constructor
 */
function CFetcherSignatureViewModel(oParent)
{
	this.defaultAccountId = AppData.Accounts.defaultId;

	this.idFetcher = ko.observable(null);

	this.fetcher = ko.observable(null);

	this.signature = ko.observable('');

	this.loading = ko.observable(false);

	this.type = ko.observable(false);
	this.useSignature = ko.observable(0);

	this.oHtmlEditor = new CHtmlEditorViewModel(true);
	this.enableImageDragNDrop = ko.observable(false);

	this.enabled = oParent.oFetcherIncoming.isEnabled;
	this.enabled.subscribe(function () {
		this.oHtmlEditor.isEnable(this.enabled());
	}, this);

	this.signature.subscribe(function () {
		this.oHtmlEditor.setText(this.signature());
	}, this);

	this.firstState = null;
}

CFetcherSignatureViewModel.prototype.__name = 'CFetcherSignatureViewModel';

CFetcherSignatureViewModel.prototype.onSaveClick = function ()
{
	var oParameters = {
		'Action': 'AccountFetcherUpdate',
		'AccountID': this.defaultAccountId(),
		'FetcherID': this.idFetcher(),
		'SignatureOptions': this.useSignature(),
		'Signature': this.oHtmlEditor.getNotDefaultText()
	};

	this.loading(true);

	App.Ajax.send(oParameters, this.onAccountFetcherUpdateResponse, this);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CFetcherSignatureViewModel.prototype.onAccountFetcherUpdateResponse = function (oResponse, oRequest)
{
	this.loading(false);

	if (!oResponse.Result)
	{
		App.Api.showErrorByCode(oResponse, Utils.i18n('SETTINGS/ERROR_SETTINGS_SAVING_FAILED'));
	}
	else
	{
		App.Api.showReport(Utils.i18n('SETTINGS/COMMON_REPORT_UPDATED_SUCCESSFULLY'));
		this.updateFirstState();
		AppData.Accounts.populateFetchers();
	}
};

/**
 * @param {Object} oFetcher
 */
CFetcherSignatureViewModel.prototype.populate = function (oFetcher)
{
	if (oFetcher)
	{
		this.fetcher(oFetcher);
		this.idFetcher(oFetcher.id());
		this.signature(oFetcher.signature());
		this.useSignature(oFetcher.signatureOptions());

		setTimeout(function () {
			this.updateFirstState();
		}.bind(this), 1);
	}
};

/**
 * @param {Array} aParams
 * @param {Object} oAccount
 */
CFetcherSignatureViewModel.prototype.onShow = function (aParams, oAccount)
{
	this.oHtmlEditor.initCrea(this.signature(), false, '');
	this.oHtmlEditor.setActivitySource(this.useSignature);
	this.oHtmlEditor.resize();
	this.enableImageDragNDrop(this.oHtmlEditor.editorUploader.isDragAndDropSupported() && !App.browser.ie10AndAbove);
	
	this.updateFirstState();
};

CFetcherSignatureViewModel.prototype.getState = function ()
{
	return [
		this.type(),
		this.useSignature(),
		this.oHtmlEditor.getNotDefaultText()
	].join(':');
};

CFetcherSignatureViewModel.prototype.updateFirstState = function()
{
	this.firstState = this.getState();
};

CFetcherSignatureViewModel.prototype.isChanged = function()
{
	return !!this.firstState && this.getState() !== this.firstState;
};