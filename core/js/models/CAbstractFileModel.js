'use strict';

var
	ko = require('knockout'),
	$ = require('jquery'),
	
	Utils = require('core/js/utils/Common.js'),
	TextUtils = require('core/js/utils/Text.js'),
	Ajax = require('core/js/Ajax.js'),
	App = require('core/js/App.js'),
	WindowOpener = require('core/js/WindowOpener.js'),
	UserSettings = require('core/js/Settings.js'),
	
	bIsIosDevice = false,
	aViewMimeTypes = [
		'image/jpeg', 'image/png', 'image/gif',
		'text/html', 'text/plain', 'text/css',
		'text/rfc822-headers', 'message/delivery-status',
		'application/x-httpd-php', 'application/javascript',
		'application/pdf'
	]
;

if ($('html').hasClass('pdf'))
{
	aViewMimeTypes.push('application/pdf');
	aViewMimeTypes.push('application/x-pdf');
}

/**
 * @constructor
 */
function CAbstractFileModel()
{
	this.isIosDevice = bIsIosDevice;

	this.isFolder = ko.observable(false);
	this.isLink = ko.observable(false);
//	this.linkType = ko.observable(Enums.FileStorageLinkType.Unknown);
//	this.linkUrl = ko.observable('');
	this.isPopupItem = ko.observable(false);
	
	this.id = ko.observable('');
	this.fileName = ko.observable('');
	this.tempName = ko.observable('');
	this.displayName = ko.observable('');
	this.extension = ko.observable('');
	this.oembed = ko.observable('');
//	this.linkType.subscribe(function (iLinkType) {
//		var sOembed = '';
//		switch (iLinkType)
//		{
//			case Enums.FileStorageLinkType.YouTube:
//				sOembed = 'YouTube';
//				break;
//			case Enums.FileStorageLinkType.Vimeo:
//				if (!App.browser.ie || App.browser.ie11)
//				{
//					sOembed = 'Vimeo';
//				}
//				break;
//			case Enums.FileStorageLinkType.SoundCloud:
//				if (!App.browser.ie || App.browser.ie10AndAbove)
//				{
//					sOembed = 'SoundCloud';
//				}
//				break;
//		}
//
//		this.oembed(sOembed);
//	}, this);
	
	this.fileName.subscribe(function (sFileName) {
		this.id(sFileName);
		this.displayName(sFileName);
		this.extension(this.isFolder() ? '' : Utils.getFileExtension(sFileName));
	}, this);
	
	this.size = ko.observable(0);
	this.friendlySize = ko.computed(function () {
		return this.size() > 0 ? TextUtils.getFriendlySize(this.size()) : '';
	}, this);
	
	this.content = ko.observable('');

	this.accountId = ko.observable(App.defaultAccountId());
	this.hash = ko.observable('');
	this.thumb = ko.observable(false);
	this.iframedView = ko.observable(false);

	this.downloadLink = ko.computed(function () {
		return Utils.getDownloadLinkByHash(this.accountId(), this.hash());
	}, this);

	this.viewLink = ko.computed(function () {
		var sUrl = Utils.getViewLinkByHash(this.accountId(), this.hash());
		return this.iframedView() ? Utils.getIframeWrappwer(this.accountId(), sUrl) : sUrl;
	}, this);

	this.thumbnailSrc = ko.observable('');
	this.thumbnailLoaded = ko.observable(false);
	this.thumbnailSessionUid = ko.observable('');

	this.thumbnailLink = ko.computed(function () {
		return this.thumb() ? Utils.getViewThumbnailLinkByHash(this.accountId(), this.hash()) : '';
	}, this);

	this.type = ko.observable('');
	this.uploadUid = ko.observable('');
	this.uploaded = ko.observable(false);
	this.uploadError = ko.observable(false);
	this.visibleImportLink = ko.computed(function () {
		return UserSettings.enableOpenPgp() && this.extension().toLowerCase() === 'asc' && this.content() !== '' && !this.isPopupItem();
	}, this);
	this.isViewMimeType = ko.computed(function () {
		return (-1 !== $.inArray(this.type(), aViewMimeTypes)) || this.iframedView();
	}, this);
	this.isMessageType = ko.observable(false);
	this.visibleViewLink = ko.computed(function () {
		return this.isVisibleViewLink() && !this.isPopupItem();
	}, this);
	this.visibleOpenLink = ko.computed(function () {
		return false;//this.linkUrl() !== '';
	}, this);
	this.visibleDownloadLink = ko.computed(function () {
		return !this.isPopupItem() && !this.visibleOpenLink();
	}, this);

	this.subFiles = ko.observableArray([]);
	this.allowExpandSubFiles = ko.observable(false);
	this.subFilesLoaded = ko.observable(false);
	this.subFilesCollapsed = ko.observable(false);
	this.subFilesStartedLoading = ko.observable(false);
	this.visibleExpandLink = ko.computed(function () {
		return this.allowExpandSubFiles() && !this.subFilesCollapsed() && !this.subFilesStartedLoading();
	}, this);
	this.visibleExpandingText = ko.computed(function () {
		return this.allowExpandSubFiles() && !this.subFilesCollapsed() && this.subFilesStartedLoading();
	}, this);
	
	this.visibleSpinner = ko.observable(false);
	this.statusText = ko.observable('');
	this.statusTooltip = ko.computed(function () {
		return this.uploadError() ? this.statusText() : '';
	}, this);
	this.progressPercent = ko.observable(0);
	this.visibleProgress = ko.observable(false);
	
	this.uploadStarted = ko.observable(false);
	this.uploadStarted.subscribe(function () {
		if (this.uploadStarted())
		{
			this.uploaded(false);
			this.visibleProgress(true);
			this.progressPercent(20);
		}
		else
		{
			this.progressPercent(100);
			this.visibleProgress(false);
			this.uploaded(true);
		}
	}, this);
	
	this.allowDrag = ko.observable(false);
	this.allowSelect = ko.observable(false);
	this.allowCheck = ko.observable(false);
	this.allowDelete = ko.observable(false);
	this.allowUpload = ko.observable(false);
	this.allowSharing = ko.observable(false);
	this.allowHeader = ko.observable(false);
	this.allowDownload = ko.observable(true);

	this.downloadTitle = ko.computed(function () {
		var sTitle = '';
		
		if (!this.allowSelect() && this.allowDownload())
		{
			sTitle = TextUtils.i18n('MESSAGE/ATTACHMENT_CLICK_TO_DOWNLOAD', {
				'FILENAME': this.fileName(),
				'SIZE': this.friendlySize()
			});
			
			if (this.friendlySize() === '')
			{
				sTitle = sTitle.replace(' ()', '');
			}
		}
		
		return sTitle;
	}, this);

//	this.oembedHtml = ko.observable('');
}

/**
 * Can be overridden.
 */
CAbstractFileModel.prototype.dataObjectName = '';

/**
 * Can be overridden.
 * 
 * @returns {boolean}
 */
CAbstractFileModel.prototype.isVisibleViewLink = function ()
{
	return this.uploaded() && !this.uploadError() && this.isViewMimeType();
};

/**
 * Parses attachment data from server.
 *
 * @param {AjaxAttachmenResponse} oData
 * @param {number} iAccountId
 */
CAbstractFileModel.prototype.parse = function (oData, iAccountId)
{
	if (oData['@Object'] === this.dataObjectName)
	{
		this.fileName(Utils.pString(oData.FileName));
		this.tempName(Utils.pString(oData.TempName));
		if (this.tempName() === '')
		{
			this.tempName(this.fileName());
		}

		this.type(Utils.pString(oData.MimeType));
		this.size(oData.EstimatedSize ? parseInt(oData.EstimatedSize, 10) : parseInt(oData.SizeInBytes, 10));
		this.content(Utils.pString(oData.Content));

		this.thumb(!!oData.Thumb);

		this.hash(Utils.pString(oData.Hash));
		this.accountId(iAccountId);
		this.allowExpandSubFiles(!!oData.Expand);
		
		this.iframedView(!!oData.Iframed);

		this.uploadUid(this.hash());
		this.uploaded(true);
		
		if ($.isFunction(this.additionalParse))
		{
			this.additionalParse(oData);
		}
	}
};

CAbstractFileModel.prototype.getInThumbQueue = function (sThumbSessionUid)
{
	this.thumbnailSessionUid(sThumbSessionUid);
	if(this.thumb() && (!this.linked || this.linked && !this.linked()))
	{
		Utils.thumbQueue(this.thumbnailSessionUid(), this.thumbnailLink(), this.thumbnailSrc);
	}
};

/**
 * Starts downloading attachment on click.
 */
CAbstractFileModel.prototype.downloadFile = function ()
{
	//todo: Utils.downloadByUrl in nessesary context in new window
	
	if (this.allowDownload() && this.downloadLink().length > 0 && this.downloadLink() !== '#')
	{
		Utils.downloadByUrl(this.downloadLink());
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CAbstractFileModel.prototype.onFileExpandResponse = function (oResponse, oRequest)
{
	this.subFiles([]);
	if (Utils.isNonEmptyArray(oResponse.Result))
	{
		_.each(oResponse.Result, _.bind(function (oRawFile) {
			var oFile = this.getInstance();
			oRawFile['@Object'] = this.dataObjectName;
			oFile.parse(oRawFile, this.accountId());
			this.subFiles.push(oFile);
		}, this));
		this.subFilesLoaded(true);
		this.subFilesCollapsed(true);
	}
	this.subFilesStartedLoading(false);
};

/**
 * Starts expanding attachment on click.
 */
CAbstractFileModel.prototype.expandFile = function ()
{
	if (!this.subFilesLoaded())
	{
		this.subFilesStartedLoading(true);
		Ajax.send({
			'Action': 'FileExpand',
			'RawKey': this.hash()
		}, this.onFileExpandResponse, this);
	}
	else
	{
		this.subFilesCollapsed(true);
	}
};

/**
 * Collapse attachment on click.
 */
CAbstractFileModel.prototype.collapseFile = function ()
{
	this.subFilesCollapsed(false);
};

/**
 * @returns {CAbstractFileModel}
 */
CAbstractFileModel.prototype.getInstance = function ()
{
	return new CAbstractFileModel();
};

/**
 * Starts importing attachment on click.
 */
CAbstractFileModel.prototype.importFile = function ()
{
//	var
//		sContent = this.content(),
//		fPgpCallback = _.bind(function (oPgp) {
//			if (oPgp)
//			{
//				Popups.showPopup(CImportOpenPgpKeyPopup, [oPgp, sContent]);
//			}
//		}, this)
//	;
//	
//	App.Api.pgp(fPgpCallback, AppData.User.IdUser);
};

/**
 * @param {Object} oViewModel
 * @param {Object} oEvent
 */
CAbstractFileModel.prototype.downloadOrViewByIconClick = function (oViewModel, oEvent)
{
//	if (this.oembed() !== '')
//	{
//		this.viewFile(oViewModel, oEvent);
//	}
//	else 
	if (!this.allowSelect())
	{
		this.downloadFile();
	}
};

/**
 * Can be overridden.
 * 
 * Starts viewing attachment on click.
 */
CAbstractFileModel.prototype.viewFile = function (oViewModel, oEvent)
{
	Utils.calmEvent(oEvent);
	this.viewCommonFile();
};

CAbstractFileModel.prototype.openLink = function ()
{
	WindowOpener.openTab(this.viewLink());
};

/**
 * Starts viewing attachment on click.
 */
CAbstractFileModel.prototype.viewCommonFile = function ()
{
	var
		sUrl = Utils.getAppPath() + this.viewLink(),
		oWin = null
	;

	if (this.visibleViewLink() && this.viewLink().length > 0 && this.viewLink() !== '#')
	{
//		if (this.isLink()/* && this.linkType() === Enums.FileStorageLinkType.GoogleDrive*/)
//		{
//			sUrl = this.linkUrl();
//		}

//		if (this.oembedHtml() !== '')
//		{
//			App.Api.showPlayer(this.oembedHtml());
//		}
//		else 
		if (this.iframedView())
		{
			oWin = WindowOpener.openTab(sUrl);
		}
		else
		{
			oWin = WindowOpener.open(sUrl, sUrl, false);
		}

		if (oWin)
		{
			oWin.focus();
		}
	}
};

/**
 * @param {Object} oAttachment
 * @param {*} oEvent
 * @return {boolean}
 */
CAbstractFileModel.prototype.eventDragStart = function (oAttachment, oEvent)
{
	var oLocalEvent = oEvent.originalEvent || oEvent;
	if (oAttachment && oLocalEvent && oLocalEvent.dataTransfer && oLocalEvent.dataTransfer.setData)
	{
		oLocalEvent.dataTransfer.setData('DownloadURL', this.generateTransferDownloadUrl());
	}

	return true;
};

/**
 * @return {string}
 */
CAbstractFileModel.prototype.generateTransferDownloadUrl = function ()
{
	var sLink = this.downloadLink();
	if ('http' !== sLink.substr(0, 4))
	{
		sLink = Utils.getAppPath() + sLink;
	}

	return this.type() + ':' + this.fileName() + ':' + sLink;
};

/**
 * Fills attachment data for upload.
 *
 * @param {string} sFileUid
 * @param {Object} oFileData
 */
CAbstractFileModel.prototype.onUploadSelect = function (sFileUid, oFileData)
{
	this.fileName(Utils.pString(oFileData['FileName']));
	this.type(Utils.pString(oFileData['Type']));
	this.size(Utils.pInt(oFileData['Size']));

	this.uploadUid(sFileUid);
	this.uploaded(false);
	this.visibleSpinner(false);
	this.statusText('');
	this.progressPercent(0);
	this.visibleProgress(false);
};

/**
 * Starts spinner and progress.
 */
CAbstractFileModel.prototype.onUploadStart = function ()
{
	this.visibleSpinner(true);
	this.visibleProgress(true);
};

/**
 * Fills progress upload data.
 *
 * @param {number} iUploadedSize
 * @param {number} iTotalSize
 */
CAbstractFileModel.prototype.onUploadProgress = function (iUploadedSize, iTotalSize)
{
	if (iTotalSize > 0)
	{
		this.progressPercent(Math.ceil(iUploadedSize / iTotalSize * 100));
		this.visibleProgress(true);
	}
};

/**
 * Fills data when upload has completed.
 *
 * @param {string} sFileUid
 * @param {boolean} bResponseReceived
 * @param {Object} oResult
 */
CAbstractFileModel.prototype.onUploadComplete = function (sFileUid, bResponseReceived, oResult)
{
	var
		bError = !bResponseReceived || !oResult || oResult.Error || false,
		sError = (oResult && oResult.Error === 'size') ?
			TextUtils.i18n('COMPOSE/UPLOAD_ERROR_SIZE') :
			TextUtils.i18n('COMPOSE/UPLOAD_ERROR_UNKNOWN')
	;
	
	this.visibleSpinner(false);
	this.progressPercent(0);
	this.visibleProgress(false);
	
	this.uploaded(true);
	this.uploadError(bError);
	this.statusText(bError ? sError : TextUtils.i18n('COMPOSE/UPLOAD_COMPLETE'));

	if (!bError)
	{
		this.fillDataAfterUploadComplete(oResult, sFileUid);
		
		setTimeout((function (self) {
			return function () {
				self.statusText('');
			};
		})(this), 3000);
	}
};

/**
 * Should be overriden.
 * 
 * @param {Object} oResult
 * @param {string} sFileUid
 */
CAbstractFileModel.prototype.fillDataAfterUploadComplete = function (oResult, sFileUid)
{
};

/**
 * @param {Object} oAttachmentModel
 * @param {Object} oEvent
 */
CAbstractFileModel.prototype.onImageLoad = function (oAttachmentModel, oEvent)
{
	if(this.thumb() && !this.thumbnailLoaded())
	{
		this.thumbnailLoaded(true);
		Utils.thumbQueue(this.thumbnailSessionUid());
	}
};

module.exports = CAbstractFileModel;