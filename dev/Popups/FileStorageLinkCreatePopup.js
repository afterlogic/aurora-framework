/**
 * @constructor
 */
function FileStorageLinkCreatePopup()
{
	this.fCallback = null;
	this.link = ko.observable('');
	this.linkPrev = ko.observable('');
	this.linkFocus = ko.observable(false);
	this.checkTimeout = null;
	this.urlChecked = ko.observable(false);
	this.saveCommand = Utils.createCommand(this, this.executeSave, function () {
		return (this.urlChecked());
	});
	this.fileItem = ko.observable(new CCommonFileModel());
}

/**
 * @param {Function} fCallback
 */
FileStorageLinkCreatePopup.prototype.onShow = function (fCallback)
{
	this.link('');
	this.linkFocus(true);
	
	if (Utils.isFunc(fCallback))
	{
		this.fCallback = fCallback;
	}
	this.checkTimer = setTimeout(_.bind(this.checkUrl, this), 2000);
};

/**
 * @return {string}
 */
FileStorageLinkCreatePopup.prototype.popupTemplate = function ()
{
	return 'Popups_FileStorage_LinkCreatePopupViewModel';
};

FileStorageLinkCreatePopup.prototype.checkUrl = function ()
{
	clearTimeout(this.checkTimer);
	if (this.link() !== this.linkPrev())
	{
		this.linkPrev(this.link());
		App.Ajax.send({
				'Action': 'FilesCheckUrl',
				'Url': this.link()
			},
			this.onFilesCheckUrlResponse,
			this
		);
	}
	this.checkTimer = setTimeout(_.bind(this.checkUrl, this), 1000);
};

FileStorageLinkCreatePopup.prototype.onFilesCheckUrlResponse = function (oData)
{
	var fileItem = new CCommonFileModel();
	if (oData.Result)
	{
		fileItem.isPopupItem(true);
		fileItem.linkUrl(this.link());
		fileItem.fileName(oData.Result.Name);
		fileItem.size(oData.Result.Size);
		fileItem.linkType(oData.Result.LinkType ? oData.Result.LinkType : Enums.FileStorageLinkType.Unknown);
		fileItem.allowDownload(false);
		if (oData.Result.Thumb)
		{
			fileItem.thumb(true);
			fileItem.thumbnailSrc(oData.Result.Thumb);
		}
		this.fileItem(fileItem);
		
		this.urlChecked(true);
	}
	else
	{
		this.urlChecked(false);
	}
};

FileStorageLinkCreatePopup.prototype.executeSave = function ()
{
	if (this.fCallback)
	{
                this.fCallback(this.fileItem());
		this.link('');
		this.linkPrev('');
		this.urlChecked(false);
	}
	clearTimeout(this.checkTimer);
	this.closeCommand();
};

FileStorageLinkCreatePopup.prototype.onCancelClick = function ()
{
	this.link('');
	this.linkPrev('');
	this.urlChecked(false);
	clearTimeout(this.checkTimer);
	this.closeCommand();
};

FileStorageLinkCreatePopup.prototype.onEscHandler = function ()
{
	this.onCancelClick();
};
