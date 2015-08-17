/**
 * @constructor
 */
function FileStorageFolderCreatePopup()
{
	this.fCallback = null;
	this.folderName = ko.observable('');
	this.folderName.focus = ko.observable(false);
	this.folderName.error = ko.observable('');

	this.folderName.subscribe(function () {
		this.folderName.error('');
	}, this);
}

/**
 * @param {Function} fCallback
 */
FileStorageFolderCreatePopup.prototype.onShow = function (fCallback)
{
	this.folderName('');
	this.folderName.focus(true);
	this.folderName.error('');
	
	if (Utils.isFunc(fCallback))
	{
		this.fCallback = fCallback;
	}
};

/**
 * @return {string}
 */
FileStorageFolderCreatePopup.prototype.popupTemplate = function ()
{
	return 'Popups_FileStorage_FolderCreatePopupViewModel';
};

FileStorageFolderCreatePopup.prototype.onOKClick = function ()
{
	this.folderName.error('');
	
	if (this.fCallback)
	{
		var sError = this.fCallback(this.folderName());
		if (sError)
		{
			this.folderName.error('' + sError);
		}
		else
		{
			this.closeCommand();
		}
	}
	else
	{
		this.closeCommand();
	}
};

FileStorageFolderCreatePopup.prototype.onCancelClick = function ()
{
	this.closeCommand();
};
