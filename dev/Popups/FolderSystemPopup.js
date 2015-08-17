/**
 * @constructor
 */
function SystemFoldersPopup()
{
	this.folders = App.MailCache.editedFolderList;
	
	this.sentFolderFullName = ko.observable('');
	this.draftsFolderFullName = ko.observable('');
	this.spamFolderFullName = ko.observable('');
	this.trashFolderFullName = ko.observable('');
	
	this.options = ko.observableArray([]);
	
	this.defaultOptionsAfterRender = Utils.defaultOptionsAfterRender;
	
	this.allowSpamFolderEditing = ko.computed(function () {
		var
			oAccount = AppData.Accounts.getEdited(),
			bAllowSpamFolderExtension = oAccount.extensionExists('AllowSpamFolderExtension')
		;
		return bAllowSpamFolderExtension && !AppData.IsMailsuite;
	}, this);
}

SystemFoldersPopup.prototype.onShow = function ()
{
	var oFolderList = App.MailCache.editedFolderList();
	
	this.options(oFolderList.getOptions(Utils.i18n('SETTINGS/ACCOUNT_FOLDERS_NO_USAGE_ASSIGNED'), false, false, false));

	this.sentFolderFullName(oFolderList.sentFolderFullName());
	this.draftsFolderFullName(oFolderList.draftsFolderFullName());
	if (this.allowSpamFolderEditing())
	{
		this.spamFolderFullName(oFolderList.spamFolderFullName());
	}
	this.trashFolderFullName(oFolderList.trashFolderFullName());
};

/**
 * @return {string}
 */
SystemFoldersPopup.prototype.popupTemplate = function ()
{
	return 'Popups_FolderSystemPopupViewModel';
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
SystemFoldersPopup.prototype.onResponseFoldersSetupSystem = function (oResponse, oRequest)
{
	if (oResponse.Result === false)
	{
		App.Api.showErrorByCode(oResponse, Utils.i18n('ACCOUNT_FOLDERS_ERROR_SETUP_SPECIAL_FOLDERS'));
		App.MailCache.getFolderList(AppData.Accounts.editedId());
	}
};

SystemFoldersPopup.prototype.onOKClick = function ()
{
	var
		oFolderList = App.MailCache.editedFolderList(),
		bHasChanges = false,
		oParameters = null
	;
	
	if (this.sentFolderFullName() !== oFolderList.sentFolderFullName())
	{
		oFolderList.sentFolderFullName(this.sentFolderFullName());
		bHasChanges = true;
	}
	if (this.draftsFolderFullName() !== oFolderList.draftsFolderFullName())
	{
		oFolderList.draftsFolderFullName(this.draftsFolderFullName());
		bHasChanges = true;
	}
	if (this.allowSpamFolderEditing() && this.spamFolderFullName() !== oFolderList.spamFolderFullName())
	{
		oFolderList.spamFolderFullName(this.spamFolderFullName());
		bHasChanges = true;
	}
	if (this.trashFolderFullName() !== oFolderList.trashFolderFullName())
	{
		oFolderList.trashFolderFullName(this.trashFolderFullName());
		bHasChanges = true;
	}
	
	if (bHasChanges)
	{
		oParameters = {
			'Action': 'FoldersSetupSystem',
			'AccountID': AppData.Accounts.editedId(),
			'Sent': oFolderList.sentFolderFullName(),
			'Drafts': oFolderList.draftsFolderFullName(),
			'Trash': oFolderList.trashFolderFullName(),
			'Spam': oFolderList.spamFolderFullName()
		};
		App.Ajax.send(oParameters, this.onResponseFoldersSetupSystem, this);
	}
	
	this.closeCommand();
};

SystemFoldersPopup.prototype.onCancelClick = function ()
{
	this.closeCommand();
};

SystemFoldersPopup.prototype.onEscHandler = function ()
{
	this.onCancelClick();
};
