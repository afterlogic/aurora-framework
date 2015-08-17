

/**
 * @constructor
 */
function CMessageSender()
{
	this.replyText = ko.observable('');
	this.replyDraftUid = ko.observable('');

	this.postponedMailData = null;
}


/**
 * @param {string} sReplyText
 * @param {string} sDraftUid
 */
CMessageSender.prototype.setReplyData = function (sReplyText, sDraftUid)
{
	this.replyText(sReplyText);
	this.replyDraftUid(sDraftUid);
};

/**
 * @param {string} sAction
 * @param {Object} oParameters
 * @param {boolean} bSaveMailInSentItems
 * @param {boolean} bShowLoading
 * @param {Function} fMessageSendResponseHandler
 * @param {Object} oMessageSendResponseContext
 * @param {boolean=} bPostponedSending = false
 */
CMessageSender.prototype.send = function (sAction, oParameters, bSaveMailInSentItems, bShowLoading,
											fMessageSendResponseHandler, oMessageSendResponseContext, bPostponedSending)
{
	var
		iAccountID = oParameters.AccountID,
		oFolderList = App.MailCache.oFolderListItems[iAccountID],
		sLoadingMessage = '',
		sSentFolder = oFolderList ? oFolderList.sentFolderFullName() : '',
		sDraftFolder = oFolderList ? oFolderList.draftsFolderFullName() : '',
		sCurrEmail = AppData.Accounts.getEmail(iAccountID),
		bSelfRecipient = (oParameters.To.indexOf(sCurrEmail) > -1 || oParameters.Cc.indexOf(sCurrEmail) > -1 || 
			oParameters.Bcc.indexOf(sCurrEmail) > -1),
		oParentApp = (AppData.SingleMode && window.opener && window.opener.App) ? window.opener.App : App
	;
	
	if (AppData.User.SaveRepliedToCurrFolder && !bSelfRecipient && Utils.isNonEmptyArray(oParameters.DraftInfo, 3))
	{
		sSentFolder = oParameters.DraftInfo[2];
	}
	
	oParameters.Action = sAction;
	oParameters.ShowReport = bShowLoading;
	
	switch (sAction)
	{
		case 'MessageSend':
			sLoadingMessage = Utils.i18n('COMPOSE/INFO_SENDING');
			if (bSaveMailInSentItems)
			{
				oParameters.SentFolder = sSentFolder;
			}
			if (oParameters.DraftUid !== '')
			{
				oParameters.DraftFolder = sDraftFolder;
				oParentApp.MailCache.removeOneMessageFromCacheForFolder(oParameters.AccountID, oParameters.DraftFolder, oParameters.DraftUid);
				oParentApp.Routing.replaceHashWithoutMessageUid(oParameters.DraftUid);
			}
			break;
		case 'MessageSave':
			sLoadingMessage = Utils.i18n('COMPOSE/INFO_SAVING');
			oParameters.DraftFolder = sDraftFolder;
			App.MailCache.savingDraftUid(oParameters.DraftUid);
			oParentApp.MailCache.startMessagesLoadingWhenDraftSaving(oParameters.AccountID, oParameters.DraftFolder);
			oParentApp.Routing.replaceHashWithoutMessageUid(oParameters.DraftUid);
			break;
	}
	
	if (bShowLoading)
	{
		App.Api.showLoading(sLoadingMessage);
	}
	
	if (bPostponedSending)
	{
		this.postponedMailData = {
			'Parameters': oParameters,
			'MessageSendResponseHandler': fMessageSendResponseHandler,
			'MessageSendResponseContext': oMessageSendResponseContext
		};
	}
	else
	{
		App.Ajax.send(oParameters, fMessageSendResponseHandler, oMessageSendResponseContext);
	}
};

/**
 * @param {string} sDraftUid
 */
CMessageSender.prototype.sendPostponedMail = function (sDraftUid)
{
	var
		oData = this.postponedMailData,
		oParameters = oData.Parameters,
		iAccountID = oParameters.AccountID,
		oFolderList = App.MailCache.oFolderListItems[iAccountID],
		sDraftFolder = oFolderList ? oFolderList.draftsFolderFullName() : '',
		oParentApp = (AppData.SingleMode && window.opener && window.opener.App) ? window.opener.App : App
	;
	
	if (sDraftUid !== '')
	{
		oParameters.DraftUid = sDraftUid;
		oParameters.DraftFolder = sDraftFolder;
		oParentApp.MailCache.removeOneMessageFromCacheForFolder(oParameters.AccountID, oParameters.DraftFolder, oParameters.DraftUid);
		oParentApp.Routing.replaceHashWithoutMessageUid(oParameters.DraftUid);
	}
	
	if (this.postponedMailData)
	{
		App.Ajax.send(oParameters, oData.MessageSendResponseHandler, oData.MessageSendResponseContext);
		this.postponedMailData = null;
	}
};

/**
 * @param {string} sAction
 * @param {string} sText
 * @param {string} sDraftUid
 * @param {Function} fMessageSendResponseHandler
 * @param {Object} oMessageSendResponseContext
 * @param {boolean} bRequiresPostponedSending
 */
CMessageSender.prototype.sendReplyMessage = function (sAction, sText, sDraftUid, fMessageSendResponseHandler, 
														oMessageSendResponseContext, bRequiresPostponedSending)
{
	var
		oParameters = null,
		oMessage = App.MailCache.currentMessage(),
		aRecipients = [],
		oFetcherOrIdentity = null
	;

	if (oMessage)
	{
		aRecipients = oMessage.oTo.aCollection.concat(oMessage.oCc.aCollection);
		oFetcherOrIdentity = this.getFirstFetcherOrIdentityByRecipientsOrDefault(aRecipients, oMessage.accountId());

		oParameters = this.getReplyDataFromMessage(oMessage, Enums.ReplyType.ReplyAll, oMessage.accountId(), oFetcherOrIdentity, false, sText, sDraftUid);

		oParameters.AccountID = oMessage.accountId();

		if (oFetcherOrIdentity)
		{
			oParameters.FetcherID = oFetcherOrIdentity && oFetcherOrIdentity.FETCHER ? oFetcherOrIdentity.id() : '';
			oParameters.IdentityID = oFetcherOrIdentity && !oFetcherOrIdentity.FETCHER ? oFetcherOrIdentity.id() : '';
		}

		oParameters.Bcc = '';
		oParameters.Importance = Enums.Importance.Normal;
		oParameters.Sensitivity = Enums.Sensitivity.Nothing;
		oParameters.ReadingConfirmation = '0';
		oParameters.IsQuickReply = '1';
		oParameters.IsHtml = '1';

		oParameters.Attachments = this.convertAttachmentsForSending(oParameters.Attachments);

		this.send(sAction, oParameters, AppData.User.getSaveMailInSentItems(), false,
			fMessageSendResponseHandler, oMessageSendResponseContext, bRequiresPostponedSending);
	}
};

/**
 * @param {Array} aAttachments
 * 
 * @return {Object}
 */
CMessageSender.prototype.convertAttachmentsForSending = function (aAttachments)
{
	var oAttachments = {};
	
	_.each(aAttachments, function (oAttach) {
		oAttachments[oAttach.tempName()] = [
			oAttach.fileName(),
			oAttach.cid(),
			oAttach.inline() ? '1' : '0',
			oAttach.linked() ? '1' : '0',
			oAttach.contentLocation()
		];
	});
	
	return oAttachments;
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 * @param {boolean} bRequiresPostponedSending
 * 
 * @return {Object}
 */
CMessageSender.prototype.onMessageSendOrSaveResponse = function (oResponse, oRequest, bRequiresPostponedSending)
{
	var
		oParentApp = (AppData.SingleMode && window.opener && window.opener.App) ? window.opener.App : App,
		bResult = !!oResponse.Result,
		sFullName, sUid, sReplyType
	;

	if (!bRequiresPostponedSending)
	{
		App.Api.hideLoading();
	}
	
	switch (oRequest.Action)
	{
		case 'MessageSave':
			if (!bResult)
			{
				if (oRequest.ShowReport)
				{
					App.Api.showErrorByCode(oResponse, Utils.i18n('COMPOSE/ERROR_MESSAGE_SAVING'));
				}
			}
			else
			{
				if (oRequest.ShowReport && !bRequiresPostponedSending)
				{
					App.Api.showReport(Utils.i18n('COMPOSE/REPORT_MESSAGE_SAVED'));
				}

				if (!oResponse.Result.NewUid)
				{
					AppData.User.AllowAutosaveInDrafts = false;
				}
			}
			break;
		case 'MessageSend':
			if (!bResult && oResponse.ErrorCode !== Enums.Errors.NotSavedInSentItems)
			{
				App.Api.showErrorByCode(oResponse, Utils.i18n('COMPOSE/ERROR_MESSAGE_SENDING'));
			}
			else
			{
				if (!bResult && oResponse.ErrorCode === Enums.Errors.NotSavedInSentItems)
				{
					App.Api.showError(Utils.i18n('WARNING/SENT_EMAIL_NOT_SAVED'));
				}
				else if (oRequest.IsQuickReply)
				{
					App.Api.showReport(Utils.i18n('COMPOSE/REPORT_MESSAGE_SENT'));
				}
				else
				{
					oParentApp.Api.showReport(Utils.i18n('COMPOSE/REPORT_MESSAGE_SENT'));
				}

				if (_.isArray(oRequest.DraftInfo) && oRequest.DraftInfo.length === 3)
				{
					sReplyType = oRequest.DraftInfo[0];
					sUid = oRequest.DraftInfo[1];
					sFullName = oRequest.DraftInfo[2];
					App.MailCache.markMessageReplied(oRequest.AccountID, sFullName, sUid, sReplyType);
				}
			}
			
			if (oRequest.SentFolder)
			{
				oParentApp.MailCache.removeMessagesFromCacheForFolder(oRequest.AccountID, oRequest.SentFolder);
			}
			
			break;
	}

	if (oRequest.DraftFolder && !bRequiresPostponedSending)
	{
		oParentApp.MailCache.removeMessagesFromCacheForFolder(oRequest.AccountID, oRequest.DraftFolder);
	}
	
	return {Action: oRequest.Action, Result: bResult, NewUid: oResponse.Result ? oResponse.Result.NewUid : ''};
};

/**
 * @param {Object} oMessage
 * @param {string} sReplyType
 * @param {number} iAccountId
 * @param {Object} oFetcherOrIdentity
 * @param {boolean} bPasteSignatureAnchor
 * @param {string} sText
 * @param {string} sDraftUid
 * 
 * @return {Object}
 */
CMessageSender.prototype.getReplyDataFromMessage = function (oMessage, sReplyType, iAccountId,
													oFetcherOrIdentity, bPasteSignatureAnchor, sText, sDraftUid)
{
	var
		oReplyData = {
			DraftInfo: [],
			DraftUid: '',
			To: '',
			Cc: '',
			Bcc: '',
			Subject: '',
			Attachments: [],
			InReplyTo: oMessage.messageId(),
			References: this.getReplyReferences(oMessage)
		},
		aAttachmentsLink = [],
		sToAddr = oMessage.oReplyTo.getFull(),
		sTo = oMessage.oTo.getFull()
	;
	
	if (sToAddr === '' || oMessage.oFrom.getFirstEmail() === oMessage.oReplyTo.getFirstEmail() && oMessage.oReplyTo.getFirstName() === '')
	{
		sToAddr = oMessage.oFrom.getFull();
	}
	
	if (!sText || sText === '')
	{
		sText = this.replyText();
		this.replyText('');
	}
	
	if (sReplyType === 'forward')
	{
		oReplyData.Text = sText + this.getForwardMessageBody(oMessage, iAccountId, oFetcherOrIdentity);
	}
	else if (sReplyType === 'resend')
	{
		oReplyData.Text = oMessage.getConvertedHtml();
		oReplyData.Cc = oMessage.cc();
		oReplyData.Bcc = oMessage.bcc();
	}
	else
	{
		oReplyData.Text = sText + this._getReplyMessageBody(oMessage, iAccountId, oFetcherOrIdentity, bPasteSignatureAnchor);
	}
	
	if (sDraftUid)
	{
		oReplyData.DraftUid = sDraftUid;
	}
	else
	{
		oReplyData.DraftUid = this.replyDraftUid();
		this.replyDraftUid('');
	}

	switch (sReplyType)
	{
		case Enums.ReplyType.Reply:
			oReplyData.DraftInfo = [Enums.ReplyType.Reply, oMessage.uid(), oMessage.folder()];
			oReplyData.To = sToAddr;
			oReplyData.Subject = this.getReplySubject(oMessage.subject(), true);
			aAttachmentsLink = _.filter(oMessage.attachments(), function (oAttach) {
				return oAttach.linked();
			});
			break;
		case Enums.ReplyType.ReplyAll:
			oReplyData.DraftInfo = [Enums.ReplyType.ReplyAll, oMessage.uid(), oMessage.folder()];
			oReplyData.To = sToAddr;
			oReplyData.Cc = this._getReplyAllCcAddr(oMessage, iAccountId, oFetcherOrIdentity);
			oReplyData.Subject = this.getReplySubject(oMessage.subject(), true);
			aAttachmentsLink = _.filter(oMessage.attachments(), function (oAttach) {
				return oAttach.linked();
			});
			break;
		case Enums.ReplyType.Resend:
			oReplyData.DraftInfo = [Enums.ReplyType.Resend, oMessage.uid(), oMessage.folder(), oMessage.cc(), oMessage.bcc()];
			oReplyData.To = sTo;
			oReplyData.Subject = oMessage.subject();
			aAttachmentsLink = oMessage.attachments();
			break;
		case Enums.ReplyType.Forward:
			oReplyData.DraftInfo = [Enums.ReplyType.Forward, oMessage.uid(), oMessage.folder()];
			oReplyData.Subject = this.getReplySubject(oMessage.subject(), false);
			aAttachmentsLink = oMessage.attachments();
			break;
	}
	
	_.each(aAttachmentsLink, function (oAttachLink) {
		if (oAttachLink.getCopy)
		{
			var
				oCopy = oAttachLink.getCopy(),
				sThumbSessionUid = Date.now().toString()
			;
			oCopy.getInThumbQueue(sThumbSessionUid);
			oReplyData.Attachments.push(oCopy);
		}
	});

	return oReplyData;
};

/**
 * Prepares and returns references for reply message.
 *
 * @param {Object} oMessage
 * 
 * @return {string}
 */
CMessageSender.prototype.getReplyReferences = function (oMessage)
{
	var
		sRef = oMessage.references(),
		sInR = oMessage.messageId(),
		sPos = sRef.indexOf(sInR)
	;

	if (sPos === -1)
	{
		sRef += ' ' + sInR;
	}

	return sRef;
};

/**
 * @param {Object} oMessage
 * @param {number} iAccountId
 * @param {Object} oFetcherOrIdentity
 * @param {boolean} bPasteSignatureAnchor
 * 
 * @return {string}
 */
CMessageSender.prototype._getReplyMessageBody = function (oMessage, iAccountId, oFetcherOrIdentity, bPasteSignatureAnchor)
{
	var
		sReplyTitle = Utils.i18n('COMPOSE/REPLY_MESSAGE_TITLE', {
			'DATE': oMessage.oDateModel.getDate(),
			'TIME': oMessage.oDateModel.getTime(),
			'SENDER': Utils.encodeHtml(oMessage.oFrom.getFull())
		}),
		sReplyBody = '<br /><br />' + this.getSignatureText(iAccountId, oFetcherOrIdentity, bPasteSignatureAnchor) + '<br /><br />' +
			'<div data-anchor="reply-title">' + sReplyTitle + '</div><blockquote>' + oMessage.getConvertedHtml() + '</blockquote>'
	;

	return sReplyBody;
};

/**
 * @param {number} iAccountId
 * @param {Object} oFetcherOrIdentity
 * 
 * @return {string}
 */
CMessageSender.prototype.getClearSignature = function (iAccountId, oFetcherOrIdentity)
{
	var
		oAccount = AppData.Accounts.getAccount(iAccountId),
		bUseSignature = !!(oFetcherOrIdentity ? (oFetcherOrIdentity.useSignature ? oFetcherOrIdentity.useSignature() : oFetcherOrIdentity.signatureOptions()) : true),
		sSignature = ''
	;

	if (oAccount)
	{
		if (bUseSignature)
		{
			if (oFetcherOrIdentity && oFetcherOrIdentity.accountId() === oAccount.id())
			{
				sSignature = oFetcherOrIdentity.signature();
			}
			else
			{
				sSignature = (oAccount.signature() && parseInt(oAccount.signature().options())) ?
					oAccount.signature().signature() : '';
			}
		}
	}

	return sSignature;
};

/**
 * @param {number} iAccountId
 * @param {Object} oFetcherOrIdentity
 * @param {boolean} bPasteSignatureAnchor
 * 
 * @return {string}
 */
CMessageSender.prototype.getSignatureText = function (iAccountId, oFetcherOrIdentity, bPasteSignatureAnchor)
{
	var sSignature = this.getClearSignature(iAccountId, oFetcherOrIdentity);

	if (bPasteSignatureAnchor)
	{
		return '<div data-anchor="signature">' + sSignature + '</div>';
	}

	return '<div>' + sSignature + '</div>';
};

/**
 * @param {Array} aRecipients
 * @param {number} iAccountId
 * 
 * @return Object
 */
CMessageSender.prototype.getFirstFetcherOrIdentityByRecipientsOrDefault = function (aRecipients, iAccountId)
{
	var
		oAccount = AppData.Accounts.getAccount(iAccountId),
		aList = this.getAccountFetchersIdentitiesList(oAccount),
		aEqualEmailList = [],
		oFoundFetcherOrIdentity = null
	;

	_.each(aRecipients, function (oAddr) {
		if (!oFoundFetcherOrIdentity)
		{
			aEqualEmailList = _.filter(aList, function (oItem) {
				return oAddr.sEmail === oItem.email;
			});
			
			switch (aEqualEmailList.length)
			{
				case 0:
					break;
				case 1:
					oFoundFetcherOrIdentity = aEqualEmailList[0];
					break;
				default:
					oFoundFetcherOrIdentity = _.find(aEqualEmailList, function (oItem) {
						return oAddr.sEmail === oItem.email && oAddr.sName === oItem.name;
					});
					
					if (!oFoundFetcherOrIdentity)
					{
						oFoundFetcherOrIdentity = _.find(aEqualEmailList, function (oItem) {
							return oItem.isDefault;
						});
						if (!oFoundFetcherOrIdentity)
						{
							oFoundFetcherOrIdentity = aEqualEmailList[0];
						}
					}
					break;
			}
		}
	});
	
	if (!oFoundFetcherOrIdentity)
	{
		oFoundFetcherOrIdentity = _.find(aList, function (oItem) {
			return oItem.isDefault;
		});
	}
	
	return oFoundFetcherOrIdentity && oFoundFetcherOrIdentity.result;
};

/**
 * @param {Object} oAccount
 * @returns {Array}
 */
CMessageSender.prototype.getAccountFetchersIdentitiesList = function (oAccount)
{
	var aList = [];
	
	if (oAccount)
	{
		if (oAccount.fetchers())
		{
			_.each(oAccount.fetchers().collection(), function (oFtch) {
				aList.push({
					'email': oFtch.email(),
					'name': oFtch.userName(),
					'isDefault': false,
					'result': oFtch
				});
			});
		}
		
		_.each(oAccount.identities(), function (oIdnt) {
			aList.push({
				'email': oIdnt.email(),
				'name': oIdnt.friendlyName(),
				'isDefault': oIdnt.isDefault(),
				'result': oIdnt
			});
		});
	}

	return aList;
};

/**
 * @param {Object} oMessage
 * @param {number} iAccountId
 * @param {Object} oFetcherOrIdentity
 * 
 * @return {string}
 */
CMessageSender.prototype.getForwardMessageBody = function (oMessage, iAccountId, oFetcherOrIdentity)
{
	var
		sCcAddr = Utils.encodeHtml(oMessage.oCc.getFull()),
		sCcPart = (sCcAddr !== '') ? Utils.i18n('COMPOSE/FORWARD_MESSAGE_BODY_CC', {'CCADDR': sCcAddr}) : '',
		sForwardTitle = Utils.i18n('COMPOSE/FORWARD_MESSAGE_TITLE', {
			'FROMADDR': Utils.encodeHtml(oMessage.oFrom.getFull()),
			'TOADDR': Utils.encodeHtml(oMessage.oTo.getFull()),
			'CCPART': sCcPart,
			'FULLDATE': oMessage.oDateModel.getFullDate(),
			'SUBJECT': oMessage.subject()
		}),
		sForwardBody = '<br /><br />' + this.getSignatureText(iAccountId, oFetcherOrIdentity, true) + '<br /><br />' + 
			'<div data-anchor="reply-title">' + sForwardTitle + '</div><br /><br />' + oMessage.getConvertedHtml()
	;

	return sForwardBody;
};

/**
 * Prepares and returns cc address for reply message.
 *
 * @param {Object} oMessage
 * @param {number} iAccountId
 * @param {Object} oFetcherOrIdentity
 * 
 * @return {string}
 */
CMessageSender.prototype._getReplyAllCcAddr = function (oMessage, iAccountId, oFetcherOrIdentity)
{
	var
		oAddressList = new CAddressListModel(),
		aAddrCollection = _.union(oMessage.oTo.aCollection, oMessage.oCc.aCollection, 
			oMessage.oBcc.aCollection),
		oCurrAccount = _.find(AppData.Accounts.collection(), function (oAccount) {
			return oAccount.id() === iAccountId;
		}, this),
		oCurrAccAddress = new CAddressModel(),
		oFetcherAddress = new CAddressModel()
	;

	oCurrAccAddress.sEmail = oCurrAccount.email();
	oFetcherAddress.sEmail = oFetcherOrIdentity ? oFetcherOrIdentity.email() : '';
	oAddressList.addCollection(aAddrCollection);
	oAddressList.excludeCollection(_.union(oMessage.oFrom.aCollection, [oCurrAccAddress, oFetcherAddress]));

	return oAddressList.getFull();
};

/**
 * Obtains a subject of the message, which is the answer (reply or forward):
 * - adds the prefix "Re" of "Fwd" if the language is English, otherwise - their translation
 * - joins "Re" and "Fwd" prefixes if it is allowed for application in settings
 * 
 * @param {string} sSubject Subject of the message, the answer to which is composed
 * @param {boolean} bReply If **true** the prefix will be "Re", otherwise - "Fwd"
 *
 * @return {string}
 */
CMessageSender.prototype.getReplySubject = function (sSubject, bReply)
{
	var
		sRePrefix = Utils.i18n('COMPOSE/REPLY_PREFIX'),
		sFwdPrefix = Utils.i18n('COMPOSE/FORWARD_PREFIX'),
		sPrefix = bReply ? sRePrefix : sFwdPrefix,
		sReSubject = sPrefix + ': ' + sSubject
	;
	
	if (AppData.App.JoinReplyPrefixes)
	{
		sReSubject = Utils.Message.joinReplyPrefixesInSubject(sReSubject, sRePrefix, sFwdPrefix);
	}
	
	return sReSubject;
};

/**
 * @param {string} sPlain
 * 
 * @return {string}
 */
CMessageSender.prototype.getHtmlFromText = function (sPlain)
{
	return sPlain
		.replace(/&/g, '&amp;').replace(/>/g, '&gt;').replace(/</g, '&lt;')
		.replace(/\r/g, '').replace(/\n/g, '<br />')
	;
};

/*CMessageSender.prototype.isFetcherOrIdentitySameAsChiefAccount = function (iAccountId, oMessage)
{
	var
		oAccount = AppData.Accounts.getAccount(iAccountId || AppData.Accounts.currentId()),
		oAccountEmail = oAccount.email(),
		oAccountFriendlyName = oAccount.friendlyName(),
		aFetchersAndIdentities = []
	;

	if (oAccount.identities())
	{
		//aFetchersAndIdentities = oAccount.identities();
		_.each(oAccount.identities(), function (oIdentity) {
			if (!oIdentity.loyal())
			{
				aFetchersAndIdentities.unshift(oIdentity);
			}
		}, this);
	}
	if (oAccount.fetchers())
	{
		aFetchersAndIdentities = aFetchersAndIdentities.concat(oAccount.fetchers().collection());
	}

	return _.any(aFetchersAndIdentities, function (oAddr) {
		return oAddr.email() === oAccountEmail && (oAddr.friendlyName ? oAddr.friendlyName() === oAccountFriendlyName : oAddr.userName() === oAccountFriendlyName);
	});
};*/
