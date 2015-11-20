'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	moment = require('moment'),
	$ = require('jquery'),
	
	Utils = require('core/js/utils/Common.js'),
	TextUtils = require('core/js/utils/Text.js'),
	Ajax = require('modules/Mail/js/Ajax.js'),
	Screens = require('core/js/Screens.js'),
	CDateModel = require('core/js/models/CDateModel.js'),
	CAddressListModel = require('core/js/models/CAddressListModel.js'),
	
	MailCache = null,
	Settings = require('modules/Mail/js/Settings.js'),
	Accounts = require('modules/Mail/js/AccountList.js'),
	MessageUtils = require('modules/Mail/js/utils/Message.js'),
	CAttachmentModel = require('modules/Mail/js/models/CAttachmentModel.js')
;

/**
 * @constructor
 */
function CMessageModel()
{
	this.accountId = ko.observable(Accounts.currentId());
	this.folder = ko.observable('');
	this.uid = ko.observable('');
	this.sUniq = '';
	
	this.subject = ko.observable('');
	this.emptySubject = ko.computed(function () {
		return ($.trim(this.subject()) === '');
	}, this);
	this.subjectForDisplay = ko.computed(function () {
		return this.emptySubject() ? TextUtils.i18n('MAILBOX/EMPTY_SUBJECT') : this.subject();
	}, this);
	this.messageId = ko.observable('');
	this.size = ko.observable(0);
	this.friendlySize = ko.computed(function () {
		return TextUtils.getFriendlySize(this.size());
	}, this);
	this.textSize = ko.observable(0);
	this.oDateModel = new CDateModel();
	this.fullDate = ko.observable('');
	this.oFrom = new CAddressListModel();
	this.fullFrom = ko.observable('');
	this.oTo = new CAddressListModel();
	this.to = ko.observable('');
	this.fromOrToText = ko.observable('');
	this.oCc = new CAddressListModel();
	this.cc = ko.observable('');
	this.oBcc = new CAddressListModel();
	this.bcc = ko.observable('');
	this.oSender = new CAddressListModel();
	this.oReplyTo = new CAddressListModel();
	
	this.seen = ko.observable(false);
	
	this.flagged = ko.observable(false);
	this.partialFlagged = ko.observable(false);
	this.answered = ko.observable(false);
	this.forwarded = ko.observable(false);
	this.hasAttachments = ko.observable(false);
	this.hasIcalAttachment = ko.observable(false);
	this.hasVcardAttachment = ko.observable(false);
	this.showCalendarIcon = ko.computed(function () {
		return false;//AppData.User.AllowCalendar && this.hasIcalAttachment();
	}, this);

	this.folderObject = ko.computed(function () {
		this.requireMailCache();
		return MailCache.getFolderByFullName(this.accountId(), this.folder());
	}, this);
	this.threadsAllowed = ko.computed(function () {
		var
			oFolder = this.folderObject(),
			bFolderWithoutThreads = oFolder && (oFolder.type() === Enums.FolderTypes.Drafts || 
				oFolder.type() === Enums.FolderTypes.Spam || oFolder.type() === Enums.FolderTypes.Trash)
		;
		return Settings.useThreads() && !bFolderWithoutThreads;
	}, this);
	this.otherSendersAllowed = ko.computed(function () {
		var oFolder = this.folderObject();
		return oFolder && (oFolder.type() !== Enums.FolderTypes.Drafts) && (oFolder.type() !== Enums.FolderTypes.Sent);
	}, this);
	
	this.threadPart = ko.observable(false);
	this.threadPart.subscribe(function () {
		if (this.threadPart())
		{
			this.partialFlagged(false);
			this.threadSenders(false);
		}
	}, this);
	this.threadParentUid = ko.observable('');
	
	this.threadUids = ko.observableArray([]);
	this.threadSenders = ko.observableArray([]);
	this.threadMoreSendersText = ko.observable('');
	this.threadSendersText = ko.computed(function () {
		if (this.otherSendersAllowed())
		{
			var aSenders = this.threadSenders();

			if (aSenders.length > 3)
			{
				this.threadMoreSendersText(TextUtils.i18n('MAILBOX/THREAD_MORE_SENDERS', {'COUNT': aSenders.length - 1}));
				return ', ' + aSenders[0];
			}
			else
			{
				this.threadMoreSendersText('');
			}
			
			if (aSenders.length > 0)
			{
				aSenders.unshift('');
				return aSenders.join(', ');
			}
		}
		return '';
	}, this);
	this.threadSendersVisible = ko.computed(function () {
		return this.threadSendersText().length > 0;
	}, this);
	this.threadMoreSendersVisible = ko.computed(function () {
		return this.threadMoreSendersText().length > 0;
	}, this);
	this.threadCount = ko.computed(function () {
		return this.threadUids().length;
	}, this);
	this.threadUnreadCount = ko.observable(0);
	this.threadOpened = ko.observable(false);
	this.threadLoading = ko.observable(false);
	this.threadLoadingVisible = ko.computed(function () {
		return this.threadsAllowed() && this.threadOpened() && this.threadLoading();
	}, this);
	this.threadCountVisible = ko.computed(function () {
		return this.threadsAllowed() && this.threadCount() > 0 && !this.threadLoading();
	}, this);
	this.threadCountHint = ko.computed(function () {
		if (this.threadCount() > 0)
		{
			if (this.threadOpened())
			{
				return  TextUtils.i18n('MAILBOX/THREAD_TOOLTIP_FOLD');
			}
			else
			{
				if (this.threadUnreadCount() > 0)
				{
					return  TextUtils.i18n('MAILBOX/THREAD_TOOLTIP_HAS_UNSEEN_PLURAL', {}, null, this.threadUnreadCount());
				}
				else
				{
					return  TextUtils.i18n('MAILBOX/THREAD_TOOLTIP_UNFOLD');
				}
			}
		}
		return '';
	}, this);
	this.threadCountForLoad = ko.observable(5);
	this.threadNextLoadingVisible = ko.observable(false);
	this.threadNextLoadingLinkVisible = ko.observable(false);
	this.threadFunctionLoadNext = null;
	this.threadShowAnimation = ko.observable(false);
	this.threadHideAnimation = ko.observable(false);
	
	this.importance = ko.observable(Enums.Importance.Normal);
	this.draftInfo = ko.observableArray([]);
	this.hash = ko.observable('');
	this.downloadLink = ko.computed(function () {
		return (this.hash().length > 0) ? Utils.getDownloadLinkByHash(this.accountId(), this.hash()) : '';
	}, this);

	this.completelyFilled = ko.observable(false);

	this.checked = ko.observable(false);
	this.checked.subscribe(function (bChecked) {
		this.requireMailCache();
		if (!this.threadOpened() && MailCache.useThreadsInCurrentList())
		{
			var
				oFolder = MailCache.folderList().getFolderByFullName(this.folder())
			;
			_.each(this.threadUids(), function (sUid) {
				var oMessage = oFolder.oMessages[sUid];
				if (oMessage)
				{
					oMessage.checked(bChecked);
				}
			});
		}
	}, this);
	this.selected = ko.observable(false);
	this.deleted = ko.observable(false); // temporary removal until it was confirmation from the server to delete

	this.trimmed = ko.observable(false);
	this.trimmedTextSize = ko.observable(0);
	this.inReplyTo = ko.observable('');
	this.references = ko.observable('');
	this.readingConfirmation = ko.observable('');
	this.sensitivity = ko.observable(Enums.Sensitivity.Nothing);
	this.isPlain = ko.observable(false);
	this.text = ko.observable('');
	this.textBodyForNewWindow = ko.observable('');
	this.$text = null;
	this.rtl = ko.observable(false);
	this.hasExternals = ko.observable(false);
	this.isExternalsShown = ko.observable(false);
	this.isExternalsAlwaysShown = ko.observable(false);
	this.foundCids = ko.observableArray([]);
	this.attachments = ko.observableArray([]);
	this.usesAttachmentString = false;
	this.allAttachmentsHash = '';
	this.safety = ko.observable(false);
	this.sourceHeaders = ko.observable('');

	this.date = ko.observable('');
	
	this.textRaw = ko.observable('');
	
	this.domMessageForPrint = ko.observable(null);
	
	this.Custom = {};
}

CMessageModel.prototype.requireMailCache = function ()
{
	if (MailCache === null)
	{
		MailCache = require('modules/Mail/js/Cache.js');
	}
};

/**
 * @param {Object} oWin
 */
CMessageModel.prototype.viewMessage = function (oWin)
{
//	var
//		oDomText = this.getDomText(Utils.Common.getAppPath()),
//		sHtml = ''
//	;
//	
//	this.textBodyForNewWindow(oDomText.html());
//	sHtml = $(this.domMessageForPrint()).html();
//	
//	if (oWin)
//	{
//		$(oWin.document.body).html(sHtml);
//		oWin.focus();
//		_.each(this.attachments(), function (oAttach) {
//			var oLink = $(oWin.document.body).find("[data-hash='download-" + oAttach.hash() + "']");
//			oLink.on('click', _.bind(oAttach.downloadFile, oAttach));
//			
//			oLink = $(oWin.document.body).find("[data-hash='view-" + oAttach.hash() + "']");
//			oLink.on('click', _.bind(oAttach.viewFile, oAttach));
//		}, this);
//	}
};

/**
 * Fields accountId, folder, oTo & oFrom should be filled.
 */
CMessageModel.prototype.fillFromOrToText = function ()
{
	this.requireMailCache();
	var
		oFolder = MailCache.getFolderByFullName(this.accountId(), this.folder()),
		oAccount = Accounts.getAccount(this.accountId())
	;
	
	if (oFolder.type() === Enums.FolderTypes.Drafts || oFolder.type() === Enums.FolderTypes.Sent)
	{
		this.fromOrToText(this.oTo.getDisplay(TextUtils.i18n('MESSAGE/ME_RECIPIENT'), oAccount.email()));
	}
	else
	{
		this.fromOrToText(this.oFrom.getDisplay(TextUtils.i18n('MESSAGE/ME_SENDER'), oAccount.email()));
	}
};

/**
 * @param {Array} aChangedThreadUids
 * @param {number} iLoadedMessagesCount
 */
CMessageModel.prototype.changeThreadUids = function (aChangedThreadUids, iLoadedMessagesCount)
{
	this.threadUids(aChangedThreadUids);
	this.threadLoading(iLoadedMessagesCount < Math.min(this.threadUids().length, this.threadCountForLoad()));
};

/**
 * @param {Function} fLoadNext
 */
CMessageModel.prototype.showNextLoadingLink = function (fLoadNext)
{
	if (this.threadNextLoadingLinkVisible())
	{
		this.threadNextLoadingVisible(true);
		this.threadFunctionLoadNext = fLoadNext;
	}
};

CMessageModel.prototype.increaseThreadCountForLoad = function ()
{
	this.threadCountForLoad(this.threadCountForLoad() + 5);
	this.requireMailCache();
	MailCache.showOpenedThreads(this.folder());
};

CMessageModel.prototype.loadNextMessages = function ()
{
	if (this.threadFunctionLoadNext)
	{
		this.threadFunctionLoadNext();
		this.threadNextLoadingLinkVisible(false);
		this.threadFunctionLoadNext = null;
	}
};

/**
 * @param {number} iShowThrottle
 * @param {string} sParentUid
 */
CMessageModel.prototype.markAsThreadPart = function (iShowThrottle, sParentUid)
{
	var self = this;
	
	this.threadPart(true);
	this.threadParentUid(sParentUid);
	this.threadUids([]);
	this.threadNextLoadingVisible(false);
	this.threadNextLoadingLinkVisible(true);
	this.threadFunctionLoadNext = null;
	this.threadHideAnimation(false);
	
	setTimeout(function () {
		self.threadShowAnimation(true);
	}, iShowThrottle);
};

/**
 * @param {AjaxMessageResponse} oData
 * @param {number} iAccountId
 * @param {boolean} bThreadPart
 * @param {boolean} bTrustThreadInfo
 */
CMessageModel.prototype.parse = function (oData, iAccountId, bThreadPart, bTrustThreadInfo)
{
	var
		sHtml = '',
		sPlain = ''
	;
	
	if (bTrustThreadInfo)
	{
		this.threadPart(bThreadPart);
	}
	if (!this.threadPart())
	{
		this.threadParentUid('');
	}
	
	if (oData['@Object'] === 'Object/MessageListItem')
	{
		this.seen(!!oData.IsSeen);
		this.flagged(!!oData.IsFlagged);
		this.answered(!!oData.IsAnswered);
		this.forwarded(!!oData.IsForwarded);
		
		if (oData.Custom)
		{
			this.Custom = oData.Custom;
		}
	}
	
	if (oData['@Object'] === 'Object/Message' || oData['@Object'] === 'Object/MessageListItem')
	{
		this.Custom.Sensitivity = oData.Sensitivity;
		
		this.accountId(iAccountId);
		this.folder(oData.Folder);
		this.uid(Utils.pString(oData.Uid));
		this.sUniq = this.accountId() + this.folder() + this.uid();
		
		this.subject(Utils.pString(oData.Subject));
		this.messageId(Utils.pString(oData.MessageId));
		this.size(oData.Size);
		this.textSize(oData.TextSize);
		this.oDateModel.parse(oData.TimeStampInUTC);
		this.oFrom.parse(oData.From);
		this.oTo.parse(oData.To);
		this.fillFromOrToText();
		this.oCc.parse(oData.Cc);
		this.oBcc.parse(oData.Bcc);
		this.oSender.parse(oData.Sender);
		this.oReplyTo.parse(oData.ReplyTo);
		
		this.fullDate(this.oDateModel.getFullDate());
		this.fullFrom(this.oFrom.getFull());
		this.to(this.oTo.getFull());
		this.cc(this.oCc.getFull());
		this.bcc(this.oBcc.getFull());
		
		this.hasAttachments(!!oData.HasAttachments);
		this.hasIcalAttachment(!!oData.HasIcalAttachment);
		this.hasVcardAttachment(!!oData.HasVcardAttachment);
		
		if (oData['@Object'] === 'Object/MessageListItem' && bTrustThreadInfo)
		{
			this.threadUids(_.map(oData.Threads, function (iUid) {
				return iUid.toString();
			}, this));
		}
		
		this.importance(Utils.pInt(oData.Priority));
		if (!Enums.has('Importance', this.importance()))
		{
			this.importance(Enums.Importance.Normal);
		}
		this.sensitivity(Utils.pInt(oData.Sensitivity));
		if (!Enums.has('Sensitivity', this.sensitivity()))
		{
			this.sensitivity(Enums.Sensitivity.Nothing);
		}
		if (_.isArray(oData.DraftInfo))
		{
			this.draftInfo(oData.DraftInfo);
		}
		this.hash(Utils.pString(oData.Hash));

		if (oData['@Object'] === 'Object/Message')
		{
			this.trimmed(oData.Trimmed);
			this.trimmedTextSize(oData.TrimmedTextSize);
			this.inReplyTo(oData.InReplyTo);
			this.references(oData.References);
			this.readingConfirmation(oData.ReadingConfirmation);
			sHtml = Utils.pString(oData.Html);
			sPlain = Utils.pString(oData.Plain);
			if (sHtml !== '')
			{
				this.text(sHtml);
				this.isPlain(false);
			}
			else
			{
				this.textRaw(oData.PlainRaw);
				this.text(sPlain !== '' ? '<div>' + sPlain + '</div>' : '');
				this.isPlain(true);
			}
			this.$text = null;
			this.isExternalsShown(false);
			this.rtl(oData.Rtl);
			this.hasExternals(!!oData.HasExternals);
			this.foundCids(oData.FoundedCIDs);
			this.parseAttachments(oData.Attachments, iAccountId);
			this.safety(oData.Safety);
			this.sourceHeaders(oData.Headers);
			
			this.oRawIcal = oData.ICAL;
			this.oRawVcard = oData.VCARD;
			
			this.completelyFilled(true);
		}

		this.updateMomentDate();
	}
};

CMessageModel.prototype.updateMomentDate = function ()
{
	this.date(this.oDateModel.getShortDate(moment().clone().subtract(1, 'days').format('L') ===
		moment.unix(this.oDateModel.getTimeStampInUTC()).format('L')));
};

/**
 * @param {string=} sAppPath = ''
 * @param {boolean=} bForcedShowPictures
 * 
 * return {Object}
 */
CMessageModel.prototype.getDomText = function (sAppPath, bForcedShowPictures)
{
	var $text = this.$text;
	
	sAppPath = sAppPath || '';
	
	if (this.$text === null || sAppPath !== '')
	{
		if (this.completelyFilled())
		{
			this.$text = $(this.text());
			
			this.showInlinePictures(sAppPath);
			if (this.safety() === true)
			{
				this.alwaysShowExternalPicturesForSender();
			}
			
			if (bForcedShowPictures && this.isExternalsShown())
			{
				this.showExternalPictures();
			}
			
			$text = this.$text;
		}
		else
		{
			$text = $('');
		}
	}
	
	//returns a clone, because it uses both in the parent window and the new
	return $text.clone();
};

/**
 * @param {string=} sAppPath = ''
 * @param {boolean=} bForcedShowPictures
 * 
 * return {string}
 */
CMessageModel.prototype.getConvertedHtml = function (sAppPath, bForcedShowPictures)
{
	var oDomText = this.getDomText(sAppPath, bForcedShowPictures);
	return (oDomText.length > 0) ? oDomText.wrap('<p>').parent().html() : '';
};

/**
 * Parses attachments.
 *
 * @param {object} oData
 * @param {number} iAccountId
 */
CMessageModel.prototype.parseAttachments = function (oData, iAccountId)
{
	var aCollection = oData ? oData['@Collection'] : [];
	
	this.attachments([]);
	
	if (Utils.isNonEmptyArray(aCollection) > 0)
	{
		var sThumbSessionUid = Date.now().toString();

		this.attachments(_.map(aCollection, function (oRawAttach) {
			var oAttachment = new CAttachmentModel();
			oAttachment.parse(oRawAttach, iAccountId);
			oAttachment.getInThumbQueue(sThumbSessionUid);
			oAttachment.setMessageData(this.folder(), this.uid());
			return oAttachment;
		}, this));
	}
};

/**
 * Parses an array of email addresses.
 *
 * @param {Array} aData
 * @return {Array}
 */
CMessageModel.prototype.parseAddressArray = function (aData)
{
	var
		aAddresses = []
	;

	if (_.isArray(aData))
	{
		aAddresses = _.map(aData, function (oRawAddress) {
			var oAddress = new CAddressModel();
			oAddress.parse(oRawAddress);
			return oAddress;
		});
	}

	return aAddresses;
};

/**
 * Displays embedded images, which have cid on the list.
 * 
 * @param {string} sAppPath
 */
CMessageModel.prototype.showInlinePictures = function (sAppPath)
{
	var aAttachments = _.map(this.attachments(), function (oAttachment) {
		return {
			CID: oAttachment.cid(),
			ContentLocation: oAttachment.contentLocation(),
			ViewLink: oAttachment.viewLink()
		};
	});
	
	MessageUtils.showInlinePictures(this.$text, aAttachments, this.foundCids(), sAppPath);
};

/**
 * Displays external images.
 */
CMessageModel.prototype.showExternalPictures = function ()
{
	MessageUtils.showExternalPictures(this.$text);
	
	this.isExternalsShown(true);
};

/**
 * Sets a flag that external images are always displayed.
 */
CMessageModel.prototype.alwaysShowExternalPicturesForSender = function ()
{
	if (this.completelyFilled())
	{
		this.isExternalsAlwaysShown(true);
		if (!this.isExternalsShown())
		{
			this.showExternalPictures();
		}
	}
};

CMessageModel.prototype.openThread = function ()
{
	if (this.threadCountVisible())
	{
		var sFolder = this.folder();

		this.threadOpened(!this.threadOpened());
		this.requireMailCache();
		if (this.threadOpened())
		{
			MailCache.showOpenedThreads(sFolder);
		}
		else
		{
			MailCache.hideThreads(this);
			setTimeout(function () {
				MailCache.showOpenedThreads(sFolder);
			}, 500);
		}
	}
};

CMessageModel.prototype.downloadAllAttachments = function ()
{
	if (this.allAttachmentsHash !== '')
	{
		Utils.downloadByUrl(Utils.getDownloadLinkByHash(this.accountId(), this.allAttachmentsHash));
	}
	else
	{
		var
			aNotInlineAttachments = _.filter(this.attachments(), function (oAttach) {
				return !oAttach.linked();
			}),
			aHashes = _.map(aNotInlineAttachments, function (oAttach) {
				return oAttach.hash();
			})
		;

		Ajax.send('GetAttachmentsZipHash', { 'Hashes': aHashes }, this.onGetAttachmentsZipHashResponse, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CMessageModel.prototype.onGetAttachmentsZipHashResponse = function (oResponse, oRequest)
{
	if (oResponse.Result)
	{
		this.allAttachmentsHash = Utils.pString(oResponse.Result);
		Utils.downloadByUrl(Utils.getDownloadLinkByHash(this.accountId(), this.allAttachmentsHash));
	}
};

CMessageModel.prototype.saveAttachmentsToFiles = function ()
{
	var
		aNotInlineAttachments = _.filter(this.attachments(), function (oAttach) {
			return !oAttach.linked();
		}),
		aHashes = _.map(aNotInlineAttachments, function (oAttach) {
			return oAttach.hash();
		})
	;

	App.filesRecievedAnim(true);
	Ajax.send('SaveAttachmentsToFiles', { 'Attachments': aHashes }, this.onSaveAttachmentsToFilesResponse, this);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CMessageModel.prototype.onSaveAttachmentsToFilesResponse = function (oResponse, oRequest)
{
	var
		oParameters = JSON.parse(oRequest.Parameters),
		iSavedCount = 0,
		iTotalCount = oParameters.Attachments.length
	;
	
	if (oResponse.Result)
	{
		_.each(oParameters.Attachments, function (sHash) {
			if (oResponse.Result[sHash] !== undefined)
			{
				iSavedCount++;
			}
		});
	}
	
	if (iSavedCount === 0)
	{
		Screens.showError(TextUtils.i18n('MESSAGE/ERROR_ATTACHMENTS_SAVED_TO_FILES'));
	}
	else if (iSavedCount < iTotalCount)
	{
		Screens.showError(TextUtils.i18n('MESSAGE/WARNING_ATTACHMENTS_SAVED_TO_FILES', {
			'SAVED_COUNT': iSavedCount,
			'TOTAL_COUNT': iTotalCount
		}));
	}
	else
	{
		Screens.showReport(TextUtils.i18n('MESSAGE/REPORT_ATTACHMENTS_SAVED_TO_FILES'));
	}
};

CMessageModel.prototype.downloadAllAttachmentsSeparately = function ()
{
	_.each(this.attachments(), function (oAttach) {
		if (!oAttach.linked())
		{
			oAttach.downloadFile();
		}
	});
};

/**
 * Uses for logging.
 * 
 * @returns {Object}
 */
CMessageModel.prototype.toJSON = function ()
{
	return {
		uid: this.uid(),
		accountId: this.accountId(),
		to: this.to(),
		subject: this.subject(),
		threadPart: this.threadPart(),
		threadUids: this.threadUids(),
		threadOpened: this.threadOpened()
	};
};

module.exports = CMessageModel;