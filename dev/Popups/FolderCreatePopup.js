/**
 * @constructor
 */
function FolderCreatePopup()
{
	this.loading = ko.observable(false);
	App.MailCache.folderListLoading.subscribe(function () {
		var bListLoading = App.MailCache.folderListLoading.indexOf(App.MailCache.editedFolderList().iAccountId) !== -1;
		if (!bListLoading && this.loading())
		{
			if (this.fCallback)
			{
				this.fCallback(this.folderName(), this.parentFolder());
			}
			this.loading(false);
			this.closeCommand();
		}
	}, this);

	this.options = ko.observableArray([]);

	this.parentFolder = ko.observable('');
	this.folderName = ko.observable('');
	this.folderNameFocus = ko.observable(false);
	
	this.fCallback = null;

	this.defaultOptionsAfterRender = Utils.defaultOptionsAfterRender;
}
/**
 * @return {string}
 */
FolderCreatePopup.prototype.popupTemplate = function ()
{
	return 'Popups_FolderCreatePopupViewModel';
};

/**
 * @param {Function} fCallback
 */
FolderCreatePopup.prototype.onShow = function (fCallback)
{
	this.options(App.MailCache.editedFolderList().getOptions(Utils.i18n('SETTINGS/ACCOUNT_FOLDERS_NO_PARENT'), true, false, true));
	
	this.fCallback = fCallback;
	this.folderName('');
	this.folderNameFocus(true);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
FolderCreatePopup.prototype.onResponseFolderCreate = function (oResponse, oRequest)
{
	if (!oResponse.Result)
	{
		this.loading(false);
		App.Api.showErrorByCode(oResponse, Utils.i18n('SETTINGS/ACCOUNT_FOLDERS_CANT_CREATE_FOLDER'));
	}
	else
	{
		App.MailCache.getFolderList(AppData.Accounts.editedId());
	}
};

FolderCreatePopup.prototype.onOKClick = function ()
{
	var
		parentFolder = (this.parentFolder() === '' ? App.MailCache.editedFolderList().sNamespaceFolder : this.parentFolder()),
		oParameters = {
			'Action': 'FolderCreate',
			'AccountID': AppData.Accounts.editedId(),
			'FolderNameInUtf8': this.folderName(),
			'FolderParentFullNameRaw': parentFolder,
			'Delimiter': App.MailCache.editedFolderList().sDelimiter
		}
	;

	this.folderNameFocus(false);
	this.loading(true);

	App.Ajax.send(oParameters, this.onResponseFolderCreate, this);
};

FolderCreatePopup.prototype.onCancelClick = function ()
{
	if (!this.loading())
	{
		if (this.fCallback)
		{
			this.fCallback('', '');
		}
		this.closeCommand();
	}
};

FolderCreatePopup.prototype.onEscHandler = function ()
{
	this.onCancelClick();
};
