/**
 * @constructor
 */
function FetcherAddPopup()
{
	this.loading = ko.observable(false);
	this.newFolderCreating = ko.observable(false);

	this.incomingMailLogin = ko.observable('');
	this.incomingMailPassword = ko.observable('');
	this.oIncoming = new CServerPropertiesViewModel(110, 995, 'fectcher_add_incoming', Utils.i18n('SETTINGS/ACCOUNT_FETCHER_POP3_SERVER'));

	this.folder = ko.observable('');
	this.options = ko.observableArray([]);
	App.MailCache.folderList.subscribe(function () {
		this.populateOptions();
	}, this);

	this.addNewFolderCommand = Utils.createCommand(this, this.onAddNewFolderClick);

	this.leaveMessagesOnServer = ko.observable(false);

	this.loginIsSelected = ko.observable(false);
	this.passwordIsSelected = ko.observable(false);

	this.defaultOptionsAfterRender = Utils.defaultOptionsAfterRender;
}

/**
 * @return {string}
 */
FetcherAddPopup.prototype.popupTemplate = function ()
{
	return 'Popups_FetcherAddPopupViewModel';
};

FetcherAddPopup.prototype.onShow = function ()
{
	this.bShown = true;
	this.populateOptions();
	
	this.incomingMailLogin('');
	this.incomingMailPassword('');
	this.oIncoming.clear();

	this.folder('');

	this.leaveMessagesOnServer(true);
};

FetcherAddPopup.prototype.populateOptions = function ()
{
	if (this.bShown)
	{
		this.options(App.MailCache.folderList().getOptions('', true, false, false));
	}
};

FetcherAddPopup.prototype.onHide = function ()
{
	this.bShown = false;
};

FetcherAddPopup.prototype.onSaveClick = function ()
{
	if (this.isEmptyRequiredFields())
	{
		App.Api.showError(Utils.i18n('WARNING/FETCHER_CREATE_ERROR'));
	}
	else
	{
		var oParameters = {
			'Action': 'AccountFetcherCreate',
			'AccountID': AppData.Accounts.defaultId(),
			'Folder': this.folder(),
			'IncomingMailLogin': this.incomingMailLogin(),
			'IncomingMailPassword': (this.incomingMailPassword() === '') ? '******' : this.incomingMailPassword(),
			'IncomingMailServer': this.oIncoming.server(),
			'IncomingMailPort': this.oIncoming.getIntPort(),
			'IncomingMailSsl': this.oIncoming.getIntSsl(),
			'LeaveMessagesOnServer': this.leaveMessagesOnServer() ? 1 : 0
		};

		this.loading(true);

		App.Ajax.send(oParameters, this.onAccountFetcherCreateResponse, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
FetcherAddPopup.prototype.onAccountFetcherCreateResponse = function (oResponse, oRequest)
{
	this.loading(false);

	if (!oResponse.Result)
	{
		App.Api.showErrorByCode(oResponse);
	}
	else
	{
		AppData.Accounts.populateFetchers();

		this.closeCommand();
	}
};

FetcherAddPopup.prototype.onCancelClick = function ()
{
	if (!this.newFolderCreating())
	{
		this.closeCommand();
	}
};

FetcherAddPopup.prototype.onEscHandler = function ()
{
	this.onCancelClick();
};

FetcherAddPopup.prototype.isEmptyRequiredFields = function ()
{
	switch ('')
	{
		case this.oIncoming.server():
			this.oIncoming.focused(true);
			return true;
		case this.incomingMailLogin():
			this.loginIsSelected(true);
			return true;
		case this.incomingMailPassword():
			this.passwordIsSelected(true);
			return true;
		default: return false;
	}
};

FetcherAddPopup.prototype.onAddNewFolderClick = function ()
{
	this.newFolderCreating(true);
	App.Screens.showPopup(FolderCreatePopup, [_.bind(this.chooseFolderInList, this)]);
};

/**
 * @param {string} sFolderName
 * @param {string} sParentFullName
 */
FetcherAddPopup.prototype.chooseFolderInList = function (sFolderName, sParentFullName)
{
	var
		sDelimiter = App.MailCache.folderList().sDelimiter,
		aFolder = []
	;
	
	if (sFolderName !== '' && sParentFullName !== '')
	{
		this.options(App.MailCache.folderList().getOptions('', true, false, false));
		
		_.each(this.options(), _.bind(function (oOption) {
			if (sFolderName === oOption.name)
			{
				aFolder = oOption.fullName.split(sDelimiter);
				aFolder.pop();
				if (sParentFullName === aFolder.join(sDelimiter))
				{
					this.folder(oOption.fullName);
				}
			}
		}, this));
	}
	
	this.newFolderCreating(false);
};
