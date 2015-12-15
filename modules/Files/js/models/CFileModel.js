'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	FilesUtils = require('core/js/utils/Files.js'),
	TextUtils = require('core/js/utils/Text.js'),
	Utils = require('core/js/utils/Common.js'),
	
	Browser = require('core/js/Browser.js'),
	WindowOpener = require('core/js/WindowOpener.js'),
	CAbstractFileModel = require('core/js/models/CAbstractFileModel.js'),
	CDateModel = require('core/js/models/CDateModel.js'),
	
	Popups = require('core/js/Popups.js'),
	EmbedHtmlPopup = require('core/js/popups/EmbedHtmlPopup.js')
;

/**
 * @constructor
 * @extends CCommonFileModel
 */
function CFileModel()
{
	this.id = ko.observable('');
	this.fileName = ko.observable('');
	this.storageType = ko.observable(Enums.FileStorageType.Personal);
	this.lastModified = ko.observable('');
	
	this.path = ko.observable('');
	this.fullPath = ko.observable('');
	
	this.selected = ko.observable(false);
	this.checked = ko.observable(false);

	this.isExternal = ko.observable(false);
	this.isLink = ko.observable(false);
	this.linkType = ko.observable(0);
	this.linkUrl = ko.observable('');
	this.thumbnailExternalLink = ko.observable('');
	this.embedType = ko.observable('');
	this.linkType.subscribe(function (iLinkType) {
		var sEmbedType = '';
		switch (iLinkType)
		{
			case Enums.FileStorageLinkType.YouTube:
				sEmbedType = 'YouTube';
				break;
			case Enums.FileStorageLinkType.Vimeo:
				if (!Browser.ie || Browser.ie11)
				{
					sEmbedType = 'Vimeo';
				}
				break;
			case Enums.FileStorageLinkType.SoundCloud:
				if (!Browser.ie || Browser.ie10AndAbove)
				{
					sEmbedType = 'SoundCloud';
				}
				break;
		}
		this.hasHtmlEmbed(sEmbedType !== '');
		this.embedType(sEmbedType);
	}, this);
	
	this.deleted = ko.observable(false); // temporary removal until it was confirmation from the server to delete
	this.recivedAnim = ko.observable(false).extend({'autoResetToFalse': 500});
	this.shared = ko.observable(false);
	this.owner = ko.observable('');
	
	this.ownerHeaderText = ko.computed(function () {
		return TextUtils.i18n('FILESTORAGE/OWNER_HEADER_EMAIL', {
			'OWNER': this.owner()
		});
	}, this);
	
	this.lastModifiedHeaderText = ko.computed(function () {
		return TextUtils.i18n('FILESTORAGE/OWNER_HEADER_LAST_MODIFIED_DATE_TEXT', {
			'LASTMODIFIED': this.lastModified()
		});
	}, this);
	
	CAbstractFileModel.call(this);
	
	this.type = this.storageType;
	this.uploaded = ko.observable(true);

	this.downloadLink = ko.computed(function () {
		return FilesUtils.getDownloadLink('Files', this.hash());
	}, this);

	this.viewLink = ko.computed(function () {
		
		if (this.isLink())
		{
			return this.linkUrl();
		}
		else
		{
			return FilesUtils.getViewLink('Files', this.hash());
		}
		
	}, this);

	this.isViewable = ko.computed(function () {
		
		var 
			bResult = false,
			aViewableArray = [
				'JPEG', 'JPG', 'PNG', 'GIF', 'HTM', 'HTML', 'TXT', 'CSS', 'ASC', 'JS', 'PDF', 'BMP'
			]
		;
		
		if (_.indexOf(aViewableArray, this.extension().toUpperCase()) >= 0)
		{
			bResult = true;
		}

		return (this.iframedView() || bResult || (this.isLink())) && !this.isPopupItem();

	}, this);
	
	this.visibleViewLink = ko.computed(function () {
		return (this.embedType() !== '' || this.linkUrl() === '') && this.isViewable();
	}, this);
	this.visibleOpenLink = ko.computed(function () {
		return this.linkUrl() !== '';
	}, this);
	this.visibleDownloadLink = ko.computed(function () {
		return !this.isPopupItem() && !this.visibleOpenLink();
	}, this);

	this.thumbnailLink = ko.computed(function () {
		
		if (this.isExternal() || (this.isLink() && (this.linkType() === Enums.FileStorageLinkType.GoogleDrive || this.linkType() === Enums.FileStorageLinkType.YouTube || this.linkType() === Enums.FileStorageLinkType.Vimeo || this.linkType() === Enums.FileStorageLinkType.SoundCloud)))
		{
			return this.thumbnailExternalLink();
		}
		else
		{
			return this.thumb() ? FilesUtils.getThumbnailLink('Files', this.hash()) : '';
		}
	}, this);

	this.canShare = ko.computed(function () {
		return (this.storageType() === Enums.FileStorageType.Personal || this.storageType() === Enums.FileStorageType.Corporate);
	}, this);
	
	this.sHtmlEmbed = ko.observable('');
}

_.extendOwn(CFileModel.prototype, CAbstractFileModel.prototype);

/**
 * @returns {CFileModel}
 */
CFileModel.prototype.getInstance = function ()
{
	return new CFileModel();
};

/**
 * @param {object} oData
 * @param {string} sLinkUrl
 */
CFileModel.prototype.parseLink = function (oData, sLinkUrl)
{
	this.isPopupItem(true);
	this.linkUrl(sLinkUrl);
	this.fileName(Utils.pString(oData.Name));
	this.size(Utils.pInt(oData.Size));
	this.linkType(Enums.has('FileStorageLinkType', Utils.pInt(oData.LinkType)) ? Utils.pInt(oData.LinkType) : Enums.FileStorageLinkType.Unknown);
	this.allowDownload(false);
	if (oData.Thumb)
	{
		this.thumb(true);
		this.thumbnailSrc(Utils.pString(oData.Thumb));
	}
};

/**
 * @param {object} oData
 * @param {boolean} bPopup
 */
CFileModel.prototype.parse = function (oData, bPopup)
{
	var oDateModel = new CDateModel();
	
	this.allowSelect(true);
	this.allowDrag(true);
	this.allowCheck(true);
	this.allowDelete(true);
	this.allowUpload(true);
	this.allowSharing(true);
	this.allowHeader(true);
	this.allowDownload(true);
	this.isPopupItem(bPopup);
		
	this.isLink(!!oData.IsLink);
	this.fileName(Utils.pString(oData.Name));
	this.id(Utils.pString(oData.Id));
	this.path(Utils.pString(oData.Path));
	this.fullPath(Utils.pString(oData.FullPath));
	this.storageType(Utils.pString(oData.Type));
	this.shared(!!oData.Shared);
	this.isExternal(!!oData.IsExternal);

	this.iframedView(!!oData.Iframed);
	
	if (this.isLink())
	{
		this.linkUrl(Utils.pString(oData.LinkUrl));
		this.linkType(Utils.pInt(oData.LinkType));
	}
	
	this.size(Utils.pInt(oData.Size));
	oDateModel.parse(oData['LastModified']);
	this.lastModified(oDateModel.getShortDate());
	this.owner(Utils.pString(oData.Owner));
	this.thumb(!!oData.Thumb);
	this.thumbnailExternalLink(Utils.pString(oData.ThumbnailLink));
	this.hash(Utils.pString(oData.Hash));
	this.sHtmlEmbed(oData.OembedHtml ? oData.OembedHtml : '');
	
	if (this.thumb() && this.thumbnailExternalLink() === '')
	{
		FilesUtils.thumbQueue(this.thumbnailSessionUid(), this.thumbnailLink(), this.thumbnailSrc);
	}
	
	this.content(Utils.pString(oData.Content));
};

/**
 * Fills attachment data for upload.
 * 
 * @param {string} sFileUid
 * @param {Object} oFileData
 * @param {string} sFileName
 * @param {string} sOwner
 * @param {string} sPath
 * @param {string} sStorageType
 */
CFileModel.prototype.onUploadSelectOwn = function (sFileUid, oFileData, sFileName, sOwner, sPath, sStorageType)
{
	var
		oDateModel = new CDateModel(),
		oDate = new Date()
	;
	
	this.onUploadSelect(sFileUid, oFileData);
	
	oDateModel.parse(oDate.getTime() /1000);
	this.fileName(sFileName);
	this.lastModified(oDateModel.getShortDate());
	this.owner(sOwner);
	this.path(sPath);
	this.storageType(sStorageType);
};

/**
 * Starts viewing attachment on click.
 */
CFileModel.prototype.viewFile = function ()
{
	if (this.sHtmlEmbed() !== '')
	{
		Popups.showPopup(EmbedHtmlPopup, [this.sHtmlEmbed()]);
	}
	else if (this.isLink())
	{
		this.viewCommonFile(this.linkUrl());
	}
	else
	{
		this.viewCommonFile();
	}
};

CFileModel.prototype.openLink = function ()
{
	WindowOpener.openTab(this.viewLink());
};

/**
 * @param {Object} oViewModel
 * @param {Object} oEvent
 */
CFileModel.prototype.onIconClick = function (oViewModel, oEvent)
{
	if (this.embedType() !== '')
	{
		this.viewFile(oViewModel, oEvent);
	}
};

module.exports = CFileModel;