'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	TextUtils = require('modules/Core/js/utils/Text.js'),
	Types = require('modules/Core/js/utils/Types.js'),
	UrlUtils = require('modules/Core/js/utils/Url.js'),
	
	Ajax = require('modules/Mail/js/Ajax.js'),
	WindowOpener = require('modules/Core/js/WindowOpener.js'),
	
	CAbstractFileModel = require('modules/Core/js/models/CAbstractFileModel.js')
;

/**
 * @constructor
 * @extends CCommonFileModel
 * @param {number} iAccountId
 */
function CAttachmentModel(iAccountId)
{
	this.folderName = ko.observable('');
	this.messageUid = ko.observable('');
	
	this.cid = ko.observable('');
	this.contentLocation = ko.observable('');
	this.inline = ko.observable(false);
	this.linked = ko.observable(false);
	this.mimePartIndex = ko.observable('');

	this.messagePart = ko.observable(null);
	
	CAbstractFileModel.call(this, 'Mail');
	
	this.isMessageType = ko.computed(function () {
		this.type();
		this.mimePartIndex();
		return (this.type() === 'message/rfc822' && this.mimePartIndex() !== '');
	}, this);
	
	this.accountId(iAccountId);
}

_.extendOwn(CAttachmentModel.prototype, CAbstractFileModel.prototype);

CAttachmentModel.prototype.dataObjectName = 'Object/CApiMailAttachment';

/**
 * @returns {CAttachmentModel}
 */
CAttachmentModel.prototype.getInstance = function ()
{
	return new CAttachmentModel();
};

CAttachmentModel.prototype.getCopy = function ()
{
	var oCopy = new CAttachmentModel();
	
	oCopy.copyProperties(this);
	
	return oCopy;
};

CAttachmentModel.prototype.copyProperties = function (oSource)
{
	this.fileName(oSource.fileName());
	this.tempName(oSource.tempName());
	this.size(oSource.size());
	this.hash(oSource.hash());
	this.type(oSource.type());
	this.cid(oSource.cid());
	this.contentLocation(oSource.contentLocation());
	this.inline(oSource.inline());
	this.linked(oSource.linked());
	this.thumb(oSource.thumb());
	this.thumbnailSrc(oSource.thumbnailSrc());
	this.thumbnailLoaded(oSource.thumbnailLoaded());
	this.statusText(oSource.statusText());
	this.uploaded(oSource.uploaded());
	this.iframedView(oSource.iframedView());
};

CAttachmentModel.prototype.isVisibleViewLink = function ()
{
	return this.uploaded() && !this.uploadError() && (this.isViewMimeType() || this.isMessageType());
};

/**
 * Parses attachment data from server.
 *
 * @param {AjaxAttachmenResponse} oData
 */
CAttachmentModel.prototype.additionalParse = function (oData)
{
	this.mimePartIndex(Types.pString(oData.MimePartIndex));

	this.cid(Types.pString(oData.CID));
	this.contentLocation(Types.pString(oData.ContentLocation));
	this.inline(!!oData.IsInline);
	this.linked(!!oData.IsLinked);
};

/**
 * @param {string} sFolderName
 * @param {string} sMessageUid
 */
CAttachmentModel.prototype.setMessageData = function (sFolderName, sMessageUid)
{
	this.folderName(sFolderName);
	this.messageUid(sMessageUid);
};

/**
 * @param {Object} oResult
 * @param {Object} oRequest
 */
CAttachmentModel.prototype.onGetMessageResponse = function (oResult, oRequest)
{
	var
		oParameters = oRequest.Parameters || {},
		oResult = oResult.Result,
		CMessageModel = require('modules/Mail/js/models/CMessageModel.js'),
		oMessage = new CMessageModel()
	;
	
	if (oResult && this.oNewWindow)
	{
		oMessage.parse(oResult, oParameters.AccountID, false, true);
		this.messagePart(oMessage);
		this.messagePart().viewMessage(this.oNewWindow);
		this.oNewWindow = undefined;
	}
};

/**
 * Starts viewing attachment on click.
 */
CAttachmentModel.prototype.viewFile = function ()
{
	if (this.isMessageType())
	{
		this.viewMessageFile();
	}
	else
	{
		this.viewCommonFile();
	}
};

/**
 * Starts viewing attachment on click.
 */
CAttachmentModel.prototype.viewMessageFile = function ()
{
	var
		oWin = null,
		sLoadingText = '<div style="margin: 30px; text-align: center; font: normal 14px Tahoma;">' + 
			TextUtils.i18n('CORE/INFO_LOADING') + '</div>'
	;
	
	oWin = WindowOpener.open('', this.fileName());
	if (oWin)
	{
		if (this.messagePart())
		{
			this.messagePart().viewMessage(oWin);
		}
		else
		{
			$(oWin.document.body).html(sLoadingText);
			this.oNewWindow = oWin;

			Ajax.send('GetMessage', {
				'Folder': this.folderName(),
				'Uid': this.messageUid(),
				'Rfc822MimeIndex': this.mimePartIndex()
			}, this.onGetMessageResponse, this);
		}
		
		oWin.focus();
	}
};

/**
 * Starts viewing attachment on click.
 */
CAttachmentModel.prototype.viewCommonFile = function ()
{
	var
		oWin = null,
		sUrl = UrlUtils.getAppPath() + this.viewLink()
	;
	
	if (this.visibleViewLink() && this.viewLink().length > 0 && this.viewLink() !== '#')
	{
		sUrl = UrlUtils.getAppPath() + this.viewLink();

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
 * @param {Object} oResult
 * @param {string} sFileUid
 */
CAttachmentModel.prototype.fillDataAfterUploadComplete = function (oResult, sFileUid)
{
	this.cid(sFileUid);
	this.tempName(oResult.Result.Attachment.TempName);
	this.type(oResult.Result.Attachment.MimeType);
	this.size(oResult.Result.Attachment.Size);
	this.hash(oResult.Result.Attachment.Hash);
	this.iframedView(oResult.Result.Attachment.Iframed);
};

/**
 * Parses contact attachment data from server.
 *
 * @param {AjaxFileDataResponse} oData
 */
CAttachmentModel.prototype.parseFromUpload = function (oData)
{
	this.fileName(oData.Name.toString());
	this.tempName(oData.TempName ? oData.TempName.toString() : this.fileName());
	this.type(oData.MimeType.toString());
	this.size(Types.pInt(oData.Size));

	this.hash(oData.Hash);

	this.uploadUid(this.hash());
	this.uploaded(true);
	
	this.uploadStarted(false);
};

CAttachmentModel.prototype.errorFromUpload = function ()
{
	this.uploaded(true);
	this.uploadError(true);
	this.uploadStarted(false);
	this.statusText(TextUtils.i18n('CORE/ERROR_UPLOAD_UNKNOWN'));
};

module.exports = CAttachmentModel;
