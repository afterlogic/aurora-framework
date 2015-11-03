
/**
 * @constructor
 * 
 * @param {Object} oParent
 */
function CFetcherOutgoingViewModel(oParent)
{
	this.defaultAccountId = AppData.Accounts.defaultId;

	this.loading = ko.observable(false);

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

	this.oOutgoing = new CServerPropertiesViewModel(25, 465, 'fetcher_edit_outgoing', Utils.i18n('SETTINGS/ACCOUNT_FETCHER_SMTP_SERVER'));
	this.outgoingMailAuth = ko.observable(false);

	this.firstState = null;
}

CFetcherOutgoingViewModel.prototype.onSaveClick = function ()
{
	if (this.outgoingMailAuth() && this.isEmptyRequiredFields())
	{
		App.Api.showError(Utils.i18n('WARNING/FETCHER_CREATE_ERROR'));
	}
	else
	{
		var oParameters = {
			'Action': 'AccountFetcherUpdate',
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

		this.loading(true);

		App.Ajax.send(oParameters, this.onAccountFetcherUpdateResponse, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CFetcherOutgoingViewModel.prototype.onAccountFetcherUpdateResponse = function (oResponse, oRequest)
{
	this.loading(false);

	if (!oResponse.Result)
	{
		App.Api.showErrorByCode(oResponse, Utils.i18n('WARNING/UNKNOWN_ERROR'));
	}
	else
	{
		App.Api.showReport(Utils.i18n('SETTINGS/ACCOUNT_FETCHER_SUCCESSFULLY_SAVED'));
		this.updateFirstState();
		AppData.Accounts.populateFetchers();
	}
};

CFetcherOutgoingViewModel.prototype.populate = function (oFetcher)
{
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

		this.updateFirstState();
	}
};
CFetcherOutgoingViewModel.prototype.isEmptyRequiredFields = function ()
{
	if (this.isOutgoingEnabled() && '' === this.oOutgoing.server())
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

CFetcherOutgoingViewModel.prototype.getState = function ()
{
	return [
		this.isOutgoingEnabled(),
		this.oOutgoing.server(),
		this.oOutgoing.port(),
		this.oOutgoing.ssl(),
		this.outgoingMailAuth(),
		this.userName(),
		this.email()
	].join(':');
};

CFetcherOutgoingViewModel.prototype.updateFirstState = function()
{
	this.firstState = this.getState();
};

CFetcherOutgoingViewModel.prototype.isChanged = function()
{
	return !!this.firstState && this.getState() !== this.firstState;
};