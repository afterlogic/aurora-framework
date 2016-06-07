'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	FilesUtils = require('modules/Core/js/utils/Files.js'),
	TextUtils = require('modules/Core/js/utils/Text.js'),
	Types = require('modules/Core/js/utils/Types.js'),
	
	Browser = require('modules/Core/js/Browser.js'),
	WindowOpener = require('modules/Core/js/WindowOpener.js'),
	
	CAbstractFileModel = require('modules/Core/js/models/CAbstractFileModel.js'),
	CDateModel = require('modules/Core/js/models/CDateModel.js'),
	
	Popups = require('modules/Core/js/Popups.js'),
	EmbedHtmlPopup = require('modules/Core/js/popups/EmbedHtmlPopup.js'),
	
	Settings = require('modules/%ModuleName%/js/Settings.js')
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
		return TextUtils.i18n('%MODULENAME%/LABEL_OWNER_EMAIL', {
			'OWNER': this.owner()
		});
	}, this);
	
	this.lastModifiedHeaderText = ko.computed(function () {
		return TextUtils.i18n('%MODULENAME%/LABEL_LAST_MODIFIED', {
			'LASTMODIFIED': this.lastModified()
		});
	}, this);
	
	CAbstractFileModel.call(this, Settings.ServerModuleName);
	
	this.type = this.storageType;
	this.uploaded = ko.observable(true);

	this.viewLink = ko.computed(function () {
		return this.isLink() ? this.linkUrl() : FilesUtils.getViewLink(Settings.ServerModuleName, this.hash());
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
			return FilesUtils.getThumbnailLink(Settings.ServerModuleName, this.hash());
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
	this.fileName(Types.pString(oData.Name));
	this.size(Types.pInt(oData.Size));
	this.linkType(Enums.has('FileStorageLinkType', Types.pInt(oData.LinkType)) ? Types.pInt(oData.LinkType) : Enums.FileStorageLinkType.Unknown);
	this.allowDownload(false);
	if (oData.Thumb)
	{
		this.thumb(true);
		this.thumbnailSrc(Types.pString(oData.Thumb));
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
	this.fileName(Types.pString(oData.Name));
	this.id(Types.pString(oData.Id));
	this.path(Types.pString(oData.Path));
	this.fullPath(Types.pString(oData.FullPath));
	this.storageType(Types.pString(oData.Type));
	this.shared(!!oData.Shared);
	this.isExternal(!!oData.IsExternal);

	this.iframedView(!!oData.Iframed);
	
	if (this.isLink())
	{
		this.linkUrl(Types.pString(oData.LinkUrl));
		this.linkType(Types.pInt(oData.LinkType));
	}
	
	this.size(Types.pInt(oData.Size));
	oDateModel.parse(oData['LastModified']);
	this.lastModified(oDateModel.getShortDate());
	this.owner(Types.pString(oData.Owner));
	this.thumb(!!oData.Thumb);
	this.thumbnailExternalLink(Types.pString(oData.ThumbnailLink));
	this.hash(Types.pString(oData.Hash));
	this.sHtmlEmbed(oData.OembedHtml ? oData.OembedHtml : '');
	
	if (this.thumb() && this.thumbnailExternalLink() === '')
	{
		FilesUtils.thumbQueue(this.thumbnailSessionUid(), this.thumbnailLink(), this.thumbnailSrc);
	}
	
	this.content(Types.pString(oData.Content));
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
