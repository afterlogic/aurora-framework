'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	TextUtils = require('core/js/utils/Text.js'),
	
	Api = require('core/js/Api.js'),
	Screens = require('core/js/Screens.js'),
	ModulesManager = require('core/js/ModulesManager.js'),
	CAbstractSettingsFormView = ModulesManager.run('Settings', 'getAbstractSettingsFormViewClass'),
	
	Accounts = require('modules/Mail/js/AccountList.js'),
	Ajax = require('modules/Mail/js/Ajax.js'),
	CServerPropertiesViewModel = require('modules/Mail/js/views/CServerPropertiesViewModel.js')
;

/**
 * @constructor
 */
function CFetcherOutgoingPaneView()
{
	CAbstractSettingsFormView.call(this, 'Mail');
	
	this.defaultAccountId = Accounts.defaultId;

	this.fetcher = ko.observable(null);

	this.idFetcher = ko.observable(null);

	this.isEnabled = ko.observable(true);

	this.email = ko.observable('');
	this.userName = ko.observable('');
	this.isOutgoingEnabled = ko.observable(false);
	this.isOutgoingEnabled.subscribe(function (bEnabled) {
		this.oOutgoing.isEnabled(bEnabled);
	}, this);

	this.focusEmail = ko.observable(false);

	this.oOutgoing = new CServerPropertiesViewModel(25, 465, 'fetcher_edit_outgoing', TextUtils.i18n('SETTINGS/ACCOUNT_FETCHER_SMTP_SERVER'));
	this.outgoingMailAuth = ko.observable(false);

	this.firstState = null;
}

_.extendOwn(CFetcherOutgoingPaneView.prototype, CAbstractSettingsFormView.prototype);

CFetcherOutgoingPaneView.prototype.ViewTemplate = 'Mail_Settings_FetcherOutgoingPaneView';

/**
 * @param {Object} oFetcher
 */
CFetcherOutgoingPaneView.prototype.show = function (oFetcher)
{
	this.fetcher(oFetcher && oFetcher.FETCHER ? oFetcher : null);
	this.populate();
};

CFetcherOutgoingPaneView.prototype.getCurrentValues = function ()
{
	return [
		this.isOutgoingEnabled(),
		this.oOutgoing.server(),
		this.oOutgoing.port(),
		this.oOutgoing.ssl(),
		this.outgoingMailAuth(),
		this.userName(),
		this.email()
	];
};

CFetcherOutgoingPaneView.prototype.getParametersForSave = function ()
{
	if (this.fetcher())
	{
		return {
			'AccountID': this.defaultAccountId(),
			'FetcherID': this.idFetcher(),
			'Email': this.email(),
			'Name': this.userName(),
			'IsOutgoingEnabled': this.isOutgoingEnabled() ? 1 : 0,
			'OutgoingMailServer': this.oOutgoing.server(),
			'OutgoingMailPort': this.oOutgoing.getIntPort(),
			'OutgoingMailSsl': this.oOutgoing.getIntSsl(),
			'OutgoingMailAuth': this.outgoingMailAuth() ? 1 : 0
		};
	}
	
	return {};
};

CFetcherOutgoingPaneView.prototype.save = function ()
{
	if (this.isEmptyRequiredFields())
	{
		Screens.showError(TextUtils.i18n('WARNING/FETCHER_CREATE_ERROR'));
	}
	else
	{
		this.isSaving(true);

		this.updateSavedState();

		Ajax.send('UpdateFetcher', this.getParametersForSave(), this.onResponse, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CFetcherOutgoingPaneView.prototype.onResponse = function (oResponse, oRequest)
{
	this.isSaving(false);

	if (!oResponse.Result)
	{
		Api.showErrorByCode(oResponse, TextUtils.i18n('WARNING/UNKNOWN_ERROR'));
	}
	else
	{
		Accounts.populateFetchers();
		
		Screens.showReport(TextUtils.i18n('SETTINGS/ACCOUNT_FETCHER_SUCCESSFULLY_SAVED'));
	}
};

CFetcherOutgoingPaneView.prototype.populate = function ()
{
	var oFetcher = this.fetcher();
	
	if (oFetcher)
	{
		this.fetcher(oFetcher);

		this.idFetcher(oFetcher.id());

		this.isEnabled(oFetcher.isEnabled());

		this.email(oFetcher.email());
		this.userName(oFetcher.userName());
		this.isOutgoingEnabled(oFetcher.isOutgoingEnabled());

		this.oOutgoing.set(oFetcher.outgoingMailServer(), oFetcher.outgoingMailPort(), oFetcher.outgoingMailSsl());
		this.outgoingMailAuth(oFetcher.outgoingMailAuth());

		this.updateSavedState();
	}
};
CFetcherOutgoingPaneView.prototype.isEmptyRequiredFields = function ()
{
	if (this.outgoingMailAuth() && this.isOutgoingEnabled() && '' === this.oOutgoing.server())
	{
		this.oOutgoing.focused(true);
		return true;
	}
	
	if (this.outgoingMailAuth() && '' === this.email())
	{
		this.focusEmail(true);
		return true;
	}

	return false;
};

module.exports = new CFetcherOutgoingPaneView();
