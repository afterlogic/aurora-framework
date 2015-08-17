
/**
 * @constructor
 * 
 * @param {Object} oParent
 */
function CFetcherIncomingViewModel(oParent)
{
	this.bShown = false;
	
	this.loading = ko.observable(false);

	this.idFetcher = ko.observable(null);

	this.isEnabled = ko.observable(true);

	this.incomingMailLogin = ko.observable('');
	this.incomingMailPassword = ko.observable('');
	this.oIncoming = new CServerPropertiesViewModel(110, 995, 'fetcher_edit_incoming', Utils.i18n('SETTINGS/ACCOUNT_FETCHER_POP3_SERVER'));

	this.sFetcherFolder = '';
	this.folder = ko.observable('');
	this.options = ko.observableArray([]);
	App.MailCache.folderList.subscribe(function () {
		this.populateOptions();
	}, this);

	this.leaveMessagesOnServer = ko.observable(false);

	this.passwordIsSelected = ko.observable(false);

	this.defaultOptionsAfterRender = Utils.defaultOptionsAfterRender;

	this.firstState = null;
}

CFetcherIncomingViewModel.prototype.onShow = function ()
{
	this.bShown = true;
	this.populateOptions();
};

CFetcherIncomingViewModel.prototype.populateOptions = function ()
{
	if (this.bShown)
	{
		this.options(App.MailCache.folderList().getOptions('', true, false, false));
		if (this.sFetcherFolder !== this.folder())
		{
			this.folder(this.sFetcherFolder);
			this.updateFirstState();
		}
	}
};

CFetcherIncomingViewModel.prototype.onHide = function ()
{
	this.bShown = false;
};

CFetcherIncomingViewModel.prototype.onSaveClick = function ()
{
	if (this.isEmptyRequiredFields())
	{
		App.Api.showError(Utils.i18n('WARNING/FETCHER_CREATE_ERROR'));
	}
	else
	{
		var oParameters = {
			'Action': 'AccountFetcherUpdate',
			'AccountID': AppData.Accounts.defaultId(),
			'FetcherID': this.idFetcher(),
			'IsEnabled': this.isEnabled() ? 1 : 0,
			'Folder': this.folder(),
			'IncomingMailServer': this.oIncoming.server(),
			'IncomingMailPort': this.oIncoming.getIntPort(),
			'IncomingMailSsl': this.oIncoming.getIntSsl(),
			'IncomingMailPassword': (this.incomingMailPassword() === '') ? '******' : this.incomingMailPassword(),
			'LeaveMessagesOnServer': this.leaveMessagesOnServer() ? 1 : 0
		};

		this.loading(true);

		App.Ajax.send(oParameters, this.onAccountFetcherUpdateResponse, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CFetcherIncomingViewModel.prototype.onAccountFetcherUpdateResponse = function (oResponse, oRequest)
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

CFetcherIncomingViewModel.prototype.populate = function (oFetcher)
{
	if (oFetcher)
	{
		this.sFetcherFolder = oFetcher.folder();

		this.idFetcher(oFetcher.id());

		this.isEnabled(oFetcher.isEnabled());

		this.folder(oFetcher.folder());
		this.oIncoming.set(oFetcher.incomingMailServer(), oFetcher.incomingMailPort(), oFetcher.incomingMailSsl());
		this.incomingMailLogin(oFetcher.incomingMailLogin());
		this.incomingMailPassword('******');
		this.leaveMessagesOnServer(oFetcher.leaveMessagesOnServer());

		this.updateFirstState();
	}
};
CFetcherIncomingViewModel.prototype.isEmptyRequiredFields = function ()
{
	switch ('')
	{
		case this.oIncoming.server():
			this.oIncoming.focused(true);
			return true;
		case this.incomingMailPassword():
			this.passwordIsSelected(true);
			return true;
		default:
			return false;
	}
};

CFetcherIncomingViewModel.prototype.getState = function ()
{
	return [
		this.isEnabled(),
		this.oIncoming.server(),
		this.oIncoming.port(),
		this.oIncoming.ssl(),
		this.incomingMailPassword(),
		this.folder(),
		this.leaveMessagesOnServer()
	].join(':');
};

CFetcherIncomingViewModel.prototype.updateFirstState = function()
{
	this.firstState = this.getState();
};

CFetcherIncomingViewModel.prototype.isChanged = function()
{
	return !!this.firstState && this.getState() !== this.firstState;
};