/**
 * @param {?=} oParent
 *
 * @constructor
 */ 
function CAccountSignatureViewModel(oParent)
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
	
	this.oHtmlEditor = new CHtmlEditorViewModel(true);
	this.enableImageDragNDrop = ko.observable(false);

	this.enabled = ko.observable(true);

	this.signature.subscribe(function () {
		this.oHtmlEditor.setText(this.signature());
	}, this);
	
	this.getSignature();
	
	this.firstState = null;
}

CAccountSignatureViewModel.prototype.__name = 'CAccountSignatureViewModel';

/**
 * @param {Object} oAccount
 */
CAccountSignatureViewModel.prototype.onShow = function (oAccount)
{
	this.account(oAccount);

	this.oHtmlEditor.initCrea(this.signature(), false, '');
	this.oHtmlEditor.setActivitySource(this.useSignature);
	this.enableImageDragNDrop(this.oHtmlEditor.editorUploader.isDragAndDropSupported() && !App.browser.ie10AndAbove);
	
	this.updateFirstState();
};

CAccountSignatureViewModel.prototype.getState = function ()
{
	var aState = [
		this.type(),
		this.useSignature(),
		this.oHtmlEditor.getText()
	];
	return aState.join(':');
};

CAccountSignatureViewModel.prototype.updateFirstState = function ()
{
	this.firstState = this.getState();
};

CAccountSignatureViewModel.prototype.isChanged = function ()
{
	if (this.firstState && this.getState() !== this.firstState)
	{
		return true;
	}
	else
	{
		return false;
	}
};
	
CAccountSignatureViewModel.prototype.getSignature = function ()
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
			var
				oParameters = {
					'Action': 'AccountSignatureGet',
					'AccountID': this.account().id()
				}
			;
			
			App.Ajax.send(oParameters, this.onAccountSignatureGetResponse, this);
		}
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CAccountSignatureViewModel.prototype.onAccountSignatureGetResponse = function (oResponse, oRequest)
{
	var
		oSignature = null,
		iAccountId = parseInt(oResponse.AccountID, 10)
	;
	
	if (!oResponse.Result)
	{
		App.Api.showErrorByCode(oResponse);
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

CAccountSignatureViewModel.prototype.prepareParameters = function ()
{
	var
		oParameters = {
			'Action': 'AccountSignatureUpdate',
			'AccountID': this.account().id(),
			'Type': this.type() ? 1 : 0,
			'Options': this.useSignature(),
			'Signature': this.signature()
		}
	;
	
	return oParameters;
};

/**
 * @param {Object} oParameters
 */
CAccountSignatureViewModel.prototype.saveData = function (oParameters)
{
	this.updateFirstState();
	App.Ajax.send(oParameters, this.onAccountSignatureUpdateResponse, this);
};

CAccountSignatureViewModel.prototype.onSaveClick = function ()
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
CAccountSignatureViewModel.prototype.onAccountSignatureUpdateResponse = function (oResponse, oRequest)
{
	this.loading(false);

	if (oResponse.Result)
	{
		App.Api.showReport(Utils.i18n('SETTINGS/COMMON_REPORT_UPDATED_SUCCESSFULLY'));
	}
	else
	{
		App.Api.showErrorByCode(oResponse, Utils.i18n('SETTINGS/ERROR_SETTINGS_SAVING_FAILED'));
	}
};
