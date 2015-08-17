/**
 * @constructor
 * 
 * @param {Object} oParent
 * @param {boolean} bCreate
 */
function CIdentityPropertiesViewModel(oParent, bCreate)
{
	this.defaultAccountId = AppData.Accounts.defaultId;
	this.oEmailAccountsSettings = oParent;
	this.oAccountProperties = oParent.oAccountProperties;
	this.bCreate = bCreate;

	this.loading = ko.observable(false);

	this.disableCheckbox = ko.observable(false);

	this.identity = ko.observable(null);

	this.oHtmlEditor = new CHtmlEditorViewModel(true);
	//this.oHtmlEditor.isEnable(true);

	this.enabled = ko.observable(true);
	this.isDefault = ko.observable(false);
	this.email = ko.observable('');
	this.loyal = ko.observable(false);
	this.friendlyName = ko.observable('');
	this.friendlyNameHasFocus = ko.observable(false);
	this.signature = ko.observable('');
	this.useSignature = ko.observable(0);

	this.enableImageDragNDrop = ko.observable(false);
	this.signature.subscribe(function () {
		this.oHtmlEditor.setText(this.signature());
	}, this);
	/*this.enabled.subscribe(function () {
		this.oHtmlEditor.isEnable(this.enabled());
	}, this);*/

	this.firstState = null;
}

CIdentityPropertiesViewModel.prototype.__name = 'CIdentityPropertiesViewModel';

/**
 * @param {Array} aParams
 * @param {Object} oAccount
 */
CIdentityPropertiesViewModel.prototype.onShow = function (aParams, oAccount)
{
	this.oHtmlEditor.initCrea(this.signature(), false, '');
	this.oHtmlEditor.setActivitySource(this.useSignature);
	this.enableImageDragNDrop(this.oHtmlEditor.editorUploader.isDragAndDropSupported() && !App.browser.ie10AndAbove);
};

CIdentityPropertiesViewModel.prototype.onSaveClick = function ()
{
	var oParameters = {};

	this.updateFirstState();

	if (this.email() === '')
	{
		App.Api.showError(Utils.i18n('WARNING/IDENTITY_CREATE_ERROR'));
	}
	else if (this.identity().loyal())
	{
		var oAccount = AppData.Accounts.getAccount(this.identity().accountId());

		this.loading(true);
		this.signature(this.oHtmlEditor.getNotDefaultText());
		oAccount.signature().options(this.useSignature());
		oAccount.signature().signature(this.signature());

		oParameters = {
			'Action': 'AccountIdentityLoyalUpdate',
			'AccountID': oAccount.id(),
			'FriendlyName': this.friendlyName(),
			'Type': oAccount.signature().type() ? 1 : 0,
			'Signature': this.signature(),
			'Options': this.useSignature(),
			'Loyal': 1,
			'Default': this.isDefault() ? 1 : 0
		};

		App.Ajax.send(oParameters, this.onAccountIdentityUpdateResponse, this);
	}
	else
	{
		this.loading(true);
		this.signature(this.oHtmlEditor.getNotDefaultText());
		oParameters = {
			'Action': this.bCreate ? 'AccountIdentityCreate' : 'AccountIdentityUpdate',
			'AccountID': this.identity().accountId(),
			'Enabled': this.enabled() ? 1 : 0,
			'Email': this.email(),
			'Signature': this.signature(),
			'UseSignature': this.useSignature(),
			'FriendlyName': this.friendlyName(),
			'Loyal': 0,
			'Default': this.isDefault() ? 1 : 0
		};
		
		if (!this.bCreate)
		{
			oParameters.IdIdentity = this.identity().id();
		}

		this.loading(true);

		App.Ajax.send(oParameters, this.onAccountIdentityUpdateResponse, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CIdentityPropertiesViewModel.prototype.onAccountIdentityUpdateResponse = function (oResponse, oRequest)
{
	this.loading(false);
	
	if (!oResponse.Result)
	{
		App.Api.showErrorByCode(oResponse, Utils.i18n('SETTINGS/ACCOUNTS_IDENTITY_ADDING_ERROR'));
	}
	else
	{
		AppData.Accounts.populateIdentities();
		if (this.bCreate)
		{
			this.oEmailAccountsSettings.closeCommand();
		}

		if (oRequest.Loyal) {
			var
				iAccountId = Utils.pInt(oResponse.AccountID),
				oAccount = 0 < iAccountId ? AppData.Accounts.getAccount(iAccountId) : null
				;

			if (oAccount) {
				oAccount.updateExtended(oRequest);
				oAccount.isExtended(false);

				App.Api.showReport(Utils.i18n('SETTINGS/COMMON_REPORT_UPDATED_SUCCESSFULLY'));
			}
		}

		this.disableCheckbox(this.isDefault());
	}
};

/**
 * @param {Object} oIdentity
 */
CIdentityPropertiesViewModel.prototype.populate = function (oIdentity)
{
	if (oIdentity)
	{
		this.identity(oIdentity);

		this.enabled(oIdentity.enabled());
		this.isDefault(oIdentity.isDefault());
		this.email(oIdentity.email());
		this.loyal(oIdentity.loyal());
		this.friendlyName(oIdentity.friendlyName());
		this.signature(oIdentity.signature());
		this.useSignature(oIdentity.useSignature() ? 1 : 0);

		this.disableCheckbox(oIdentity.isDefault());

		setTimeout(function () {
			this.updateFirstState();
		}.bind(this), 1);
	}
};

CIdentityPropertiesViewModel.prototype.remove = function ()
{
	if (!this.identity().loyal()) {
		var oParameters = {
			'Action': 'AccountIdentityDelete',
			'AccountID': this.identity().accountId(),
			'IdIdentity': this.identity().id()
		};

		App.Ajax.send(oParameters, this.onAccountIdentityDeleteResponse, this);

		if (!this.bCreate) {
			this.oEmailAccountsSettings.onRemoveIdentity();
		}
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CIdentityPropertiesViewModel.prototype.onAccountIdentityDeleteResponse = function (oResponse, oRequest)
{
	if (!oResponse.Result)
	{
		App.Api.showErrorByCode(oResponse, Utils.i18n('SETTINGS/ACCOUNTS_IDENTITY_DELETING_ERROR'));
	}
	AppData.Accounts.populateIdentities();
};

CIdentityPropertiesViewModel.prototype.cancel = function ()
{
	if (this.bCreate)
	{
		this.oEmailAccountsSettings.cancel();
	}
};

CIdentityPropertiesViewModel.prototype.getState = function ()
{
	return [
		this.friendlyName(),
		this.email(),
		this.useSignature(),
		this.oHtmlEditor.getNotDefaultText()
	].join(':');
};

CIdentityPropertiesViewModel.prototype.updateFirstState = function()
{
	this.firstState = this.getState();
};

CIdentityPropertiesViewModel.prototype.isChanged = function()
{
	return !!this.firstState && this.getState() !== this.firstState;
};