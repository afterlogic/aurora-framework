/**
 * @constructor
 */
function FileStorageRenamePopup()
{
	this.fCallback = null;
	this.item = null;
	this.name = ko.observable('');
	this.name.focus = ko.observable(false);
	this.name.error = ko.observable('');

	this.name.subscribe(function () {
		this.name.error('');
	}, this);
}

/**
 * @param {Object} oItem
 * @param {Function} fCallback
 */
FileStorageRenamePopup.prototype.onShow = function (oItem, fCallback)
{

	this.item = oItem;
	this.item.nameForEdit(this.item.fileName());

	this.name(this.item.nameForEdit());
	this.name.focus(true);
	this.name.error('');
	
	if (Utils.isFunc(fCallback))
	{
		this.fCallback = fCallback;
	}
};

/**
 * @return {string}
 */
FileStorageRenamePopup.prototype.popupTemplate = function ()
{
	return 'Popups_FileStorage_RenamePopupViewModel';
};

FileStorageRenamePopup.prototype.onOKClick = function ()
{
	this.name.error('');
	if (this.fCallback)
	{
		this.item.nameForEdit(this.name());
		var sError = this.fCallback(this.item);
		if (sError)
		{
			this.name.error('' + sError);
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

/**
 *
 */
FileStorageRenamePopup.prototype.onCancelClick = function ()
{
	this.closeCommand();
};
