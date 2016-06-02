'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	TextUtils = require('modules/Core/js/utils/Text.js'),
	Types = require('modules/Core/js/utils/Types.js'),
	Utils = require('modules/Core/js/utils/Common.js'),
	
	Api = require('modules/Core/js/Api.js'),
	ModulesManager = require('modules/Core/js/ModulesManager.js'),
	Screens = require('modules/Core/js/Screens.js'),
	
	CAbstractSettingsFormView = ModulesManager.run('Settings', 'getAbstractSettingsFormViewClass'),
	
	AccountList = require('modules/Mail/js/AccountList.js'),
	Ajax = require('modules/Mail/js/Ajax.js')
;

/**
 * @constructor
 * 
 * @param {Object} oParent
 * @param {boolean} bCreate
 */
function CIdentityPropertiesPaneView(oParent, bCreate)
{
	CAbstractSettingsFormView.call(this, 'Mail');
	
	this.identity = ko.observable(null);
	
	this.defaultAccountId = AccountList.defaultId;
	this.oParent = oParent;
	this.bCreate = bCreate;

	this.disableCheckbox = ko.observable(false);

	this.enabled = ko.observable(true);
	this.isDefault = ko.observable(false);
	this.email = ko.observable('');
	this.loyal = ko.observable(false);
	this.friendlyName = ko.observable('');
	this.friendlyNameHasFocus = ko.observable(false);
}

_.extendOwn(CIdentityPropertiesPaneView.prototype, CAbstractSettingsFormView.prototype);

CIdentityPropertiesPaneView.prototype.ViewTemplate = 'Mail_Settings_IdentityPropertiesPaneView';

CIdentityPropertiesPaneView.prototype.__name = 'CIdentityPropertiesPaneView';

/**
 * @param {Object} oIdentity
 */
CIdentityPropertiesPaneView.prototype.show = function (oIdentity)
{
	this.identity(oIdentity && !oIdentity.FETCHER ? oIdentity : null);
	this.populate();
};

CIdentityPropertiesPaneView.prototype.getCurrentValues = function ()
{
	return [
		this.friendlyName(),
		this.email()
	];
};

CIdentityPropertiesPaneView.prototype.getParametersForSave = function ()
{
	if (this.identity())
	{
		var
			oParameters = {
				'AccountID': this.identity().accountId(),
				'Default': this.isDefault() ? 1 : 0,
				'FriendlyName': this.friendlyName(),
				'Loyal': this.identity().loyal() ? 1 : 0
			}
		;

		if (!this.identity().loyal())
		{
			_.extendOwn(oParameters, {
				'Email': this.email(),
				'Enabled': this.enabled() ? 1 : 0
			});

			if (!this.bCreate)
			{
				oParameters.IdIdentity = this.identity().id();
			}
		}

		return oParameters;
	}
	
	return {};
};

CIdentityPropertiesPaneView.prototype.save = function ()
{
	if (this.email() === '')
	{
		Screens.showError(Utils.i18n('MAIL/ERROR_IDENTITY_FIELDS_BLANK'));
	}
	else
	{
		this.isSaving(true);

		this.updateSavedState();

		Ajax.send(this.bCreate ? 'CreateIdentity' : 'UpdateIdentity', this.getParametersForSave(), this.onResponse, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CIdentityPropertiesPaneView.prototype.onResponse = function (oResponse, oRequest)
{
	this.isSaving(false);

	if (!oResponse.Result)
	{
		Api.showErrorByCode(oResponse, TextUtils.i18n('MAIL/ERROR_IDENTITY_ADDING'));
	}
	else
	{
		var
			oParameters = oRequest.Parameters,
			iAccountId = Types.pInt(oParameters.AccountID),
			oAccount = 0 < iAccountId ? AccountList.getAccount(iAccountId) : null
		;
		
		AccountList.populateIdentities();
		
		if (this.bCreate && $.isFunction(this.oParent.closePopup))
		{
			this.oParent.closePopup();
		}

		if (oParameters.Loyal === 1 && oAccount)
		{
			oAccount.updateExtended(oParameters);
			oAccount.isExtended(false);
		}

		this.disableCheckbox(this.isDefault());
		
		Screens.showReport(TextUtils.i18n('CORE/REPORT_SETTINGS_UPDATE_SUCCESS'));
	}
};

CIdentityPropertiesPaneView.prototype.populate = function ()
{
	var oIdentity = this.identity();
	
	if (oIdentity)
	{
		this.enabled(oIdentity.enabled());
		this.isDefault(oIdentity.isDefault());
		this.email(oIdentity.email());
		this.loyal(oIdentity.loyal());
		this.friendlyName(oIdentity.friendlyName());

		this.disableCheckbox(oIdentity.isDefault());

		setTimeout(function () {
			this.updateSavedState();
		}.bind(this), 1);
	}
};

CIdentityPropertiesPaneView.prototype.remove = function ()
{
	if (this.identity() && !this.identity().loyal())
	{
		var oParameters = {
			'AccountID': this.identity().accountId(),
			'IdIdentity': this.identity().id()
		};

		Ajax.send('DeleteIdentity', oParameters, this.onAccountIdentityDeleteResponse, this);

		if (!this.bCreate && $.isFunction(this.oParent.onRemoveIdentity))
		{
			this.oParent.onRemoveIdentity();
		}
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CIdentityPropertiesPaneView.prototype.onAccountIdentityDeleteResponse = function (oResponse, oRequest)
{
	if (!oResponse.Result)
	{
		Api.showErrorByCode(oResponse, Utils.i18n('MAIL/ERROR_IDENTITY_DELETING'));
	}
	AccountList.populateIdentities();
};

CIdentityPropertiesPaneView.prototype.cancel = function ()
{
	if ($.isFunction(this.oParent.cancelPopup))
	{
		this.oParent.cancelPopup();
	}
};

module.exports = CIdentityPropertiesPaneView;
