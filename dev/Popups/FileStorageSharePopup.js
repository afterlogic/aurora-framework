/**
 * @constructor
 */
function FileStorageSharePopup()
{
	this.item = null;
	this.pub = ko.observable('');
	this.pubFocus = ko.observable(false);
}

/**
 * @param {Object} oItem
 */
FileStorageSharePopup.prototype.onShow = function (oItem)
{
	this.item = oItem;
	
	this.pub('');
		
	App.Ajax.send({
			'Action': 'FilesCreatePublicLink',
			'Account': AppData.Accounts.defaultId(),
			'Type': oItem.storageType(),
			'Path': oItem.path(),
			'Name': oItem.fileName(),
			'Size': oItem.size(),
			'IsFolder': oItem.isFolder() ? '1' : '0'
		}, this.onFilesCreatePublicLinkResponse, this
	);
};

/**
 * @param {Object} oData
 * @param {Object} oParameters
 */
FileStorageSharePopup.prototype.onFilesCreatePublicLinkResponse = function (oData, oParameters)
{
	if (oData.Result)
	{
		this.pub(oData.Result);
		this.pubFocus(true);
		this.item.shared(true);
	}
};

/**
 * @return {string}
 */
FileStorageSharePopup.prototype.popupTemplate = function ()
{
	return 'Popups_FileStorage_SharePopupViewModel';
};

FileStorageSharePopup.prototype.onOKClick = function ()
{
	this.closeCommand();
};

/**
 * @param {Object} oData
 * @param {Object} oParameters
 */
FileStorageSharePopup.prototype.onFilesDeletePublicLinkResponse = function (oData, oParameters)
{
	this.closeCommand();
};

FileStorageSharePopup.prototype.onCancelSharingClick = function ()
{
	if (this.item)
	{
		App.Ajax.send({
				'Action': 'FilesPublicLinkDelete',
				'Account': AppData.Accounts.defaultId(),
				'Type': this.item.storageType(),
				'Path': this.item.path(),
				'Name': this.item.fileName()
			}, this.onFilesDeletePublicLinkResponse, this);
		this.item.shared(false);
	}
};
