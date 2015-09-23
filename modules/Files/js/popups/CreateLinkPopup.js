'use strict';

var
	ko = require('knockout'),
	_ = require('underscore'),
	$ = require('jquery'),
	
	Utils = require('core/js/utils/Common.js'),
	Ajax = require('core/js/Ajax.js'),
	CFileModel = require('modules/Files/js/models/CFileModel.js')
;

/**
 * @constructor
 */
function CCreateLinkPopup()
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
	this.fileItem = ko.observable(null);
}

/**
 * @param {Function} fCallback
 */
CCreateLinkPopup.prototype.onShow = function (fCallback)
{
	this.link('');
	this.linkFocus(true);
	
	if ($.isFunction(fCallback))
	{
		this.fCallback = fCallback;
	}
	this.checkTimer = setTimeout(_.bind(this.checkUrl, this), 2000);
};

/**
 * @return {string}
 */
CCreateLinkPopup.prototype.popupTemplate = function ()
{
	return 'Files_CreateLinkPopup';
};

CCreateLinkPopup.prototype.checkUrl = function ()
{
	clearTimeout(this.checkTimer);
	if (this.link() !== this.linkPrev())
	{
		this.linkPrev(this.link());
		Ajax.send({
				'Action': 'FilesCheckUrl',
				'Url': this.link()
			},
			this.onFilesCheckUrlResponse,
			this
		);
	}
	this.checkTimer = setTimeout(_.bind(this.checkUrl, this), 1000);
};

CCreateLinkPopup.prototype.onFilesCheckUrlResponse = function (oData)
{
	var fileItem = new CFileModel();
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

CCreateLinkPopup.prototype.executeSave = function ()
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

CCreateLinkPopup.prototype.onCancelClick = function ()
{
	this.link('');
	this.linkPrev('');
	this.urlChecked(false);
	clearTimeout(this.checkTimer);
	this.closeCommand();
};

CCreateLinkPopup.prototype.onEscHandler = function ()
{
	this.onCancelClick();
};

module.exports = new CCreateLinkPopup();