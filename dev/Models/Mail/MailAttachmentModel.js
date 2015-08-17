/**
 * @constructor
 * @extends CCommonFileModel
 */
function CMailAttachmentModel()
{
	this.folderName = ko.observable('');
	this.messageUid = ko.observable('');
	
	this.cid = ko.observable('');
	this.contentLocation = ko.observable('');
	this.inline = ko.observable(false);
	this.linked = ko.observable(false);
	this.mimePartIndex = ko.observable('');

	this.messagePart = ko.observable(null);
	
	CCommonFileModel.call(this);
	
	this.isMessageType = ko.computed(function () {
		this.type();
		this.mimePartIndex();
		return (this.type() === 'message/rfc822' && this.mimePartIndex() !== '');
	}, this);
}

Utils.extend(CMailAttachmentModel, CCommonFileModel);

CMailAttachmentModel.prototype.dataObjectName = 'Object/CApiMailAttachment';

/**
 * @returns {CMailAttachmentModel}
 */
CMailAttachmentModel.prototype.getInstance = function ()
{
	return new CMailAttachmentModel();
};

CMailAttachmentModel.prototype.getCopy = function ()
{
	var oCopy = new CMailAttachmentModel();
	
	oCopy.copyProperties(this);
	
	return oCopy;
};

CMailAttachmentModel.prototype.copyProperties = function (oSource)
{
	this.fileName(oSource.fileName());
	this.tempName(oSource.tempName());
	this.size(oSource.size());
	this.accountId(oSource.accountId());
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

CMailAttachmentModel.prototype.isVisibleViewLink = function ()
{
	return this.uploaded() && !this.uploadError() && (this.isViewMimeType() || this.isMessageType());
};

/**
 * Parses attachment data from server.
 *
 * @param {AjaxAttachmenResponse} oData
 */
CMailAttachmentModel.prototype.additionalParse = function (oData)
{
	this.mimePartIndex(Utils.pString(oData.MimePartIndex));

	this.cid(Utils.pString(oData.CID));
	this.contentLocation(Utils.pString(oData.ContentLocation));
	this.inline(!!oData.IsInline);
	this.linked(!!oData.IsLinked);
};

/**
 * @param {string} sFolderName
 * @param {string} sMessageUid
 */
CMailAttachmentModel.prototype.setMessageData = function (sFolderName, sMessageUid)
{
	this.folderName(sFolderName);
	this.messageUid(sMessageUid);
};

/**
 * @param {AjaxDefaultResponse} oData
 * @param {Object=} oParameters
 */
CMailAttachmentModel.prototype.onMessageGetResponse = function (oData, oParameters)
{
	var
		oResult = oData.Result,
		oMessage = new CMessageModel()
	;
	
	if (oResult && this.oNewWindow)
	{
		oMessage.parse(oResult, oData.AccountID, false, true);
		this.messagePart(oMessage);
		this.messagePart().viewMessage(this.oNewWindow);
		this.oNewWindow = undefined;
	}
};

/**
 * Starts viewing attachment on click.
 */
CMailAttachmentModel.prototype.viewFile = function ()
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
CMailAttachmentModel.prototype.viewMessageFile = function ()
{
	var
		oWin = null,
		sLoadingText = '<div style="margin: 30px; text-align: center; font: normal 14px Tahoma;">' + 
			Utils.i18n('MAIN/LOADING') + '</div>'
	;
	
	oWin = Utils.WindowOpener.open('', this.fileName());
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

			App.Ajax.send({
				'Action': 'MessageGet',
				'Folder': this.folderName(),
				'Uid': this.messageUid(),
				'Rfc822MimeIndex': this.mimePartIndex()
			}, this.onMessageGetResponse, this);
		}
		
		oWin.focus();
	}
};

/**
 * Starts viewing attachment on click.
 */
CMailAttachmentModel.prototype.viewCommonFile = function ()
{
	var
		oWin = null,
		sUrl = Utils.Common.getAppPath() + this.viewLink()
	;
	
	if (this.visibleViewLink() && this.viewLink().length > 0 && this.viewLink() !== '#')
	{
		sUrl = Utils.Common.getAppPath() + this.viewLink();

		if (this.iframedView())
		{
			oWin = Utils.WindowOpener.openTab(sUrl);
		}
		else
		{
			oWin = Utils.WindowOpener.open(sUrl, sUrl, false);
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
CMailAttachmentModel.prototype.fillDataAfterUploadComplete = function (oResult, sFileUid)
{
	this.cid(sFileUid);
	this.tempName(oResult.Result.Attachment.TempName);
	this.type(oResult.Result.Attachment.MimeType);
	this.size(oResult.Result.Attachment.Size);
	this.hash(oResult.Result.Attachment.Hash);
	this.iframedView(oResult.Result.Attachment.Iframed);
	this.accountId(oResult.AccountID);
};

/**
 * Parses contact attachment data from server.
 *
 * @param {AjaxFileDataResponse} oData
 * @param {number} iAccountId
 */
CMailAttachmentModel.prototype.parseFromUpload = function (oData, iAccountId)
{
	this.fileName(oData.Name.toString());
	this.tempName(oData.TempName ? oData.TempName.toString() : this.fileName());
	this.type(oData.MimeType.toString());
	this.size(parseInt(oData.Size, 10));

	this.hash(oData.Hash);
	this.accountId(iAccountId);

	this.uploadUid(this.hash());
	this.uploaded(true);
	
	this.uploadStarted(false);
};

CMailAttachmentModel.prototype.errorFromUpload = function ()
{
	this.uploaded(true);
	this.uploadError(true);
	this.uploadStarted(false);
	this.statusText(Utils.i18n('COMPOSE/UPLOAD_ERROR_UNKNOWN'));
};