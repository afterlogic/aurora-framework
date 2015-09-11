'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	moment = require('moment'),
	$ = require('jquery'),
	
	Utils = require('core/js/utils/Common.js'),
	TextUtils = require('core/js/utils/Text.js'),
	Ajax = require('core/js/Ajax.js'),
	Storage = require('core/js/Storage.js'),
	Routing = require('core/js/Routing.js'),
	Api = require('core/js/Api.js'),
	
	MailCache = null,
	Accounts = require('modules/Mail/js/AccountList.js'),
	LinksUtils = require('modules/Mail/js/utils/Links.js'),
	CUidListModel = require('modules/Mail/js/models/CUidListModel.js'),
	CMessageModel = require('modules/Mail/js/models/CMessageModel.js')
;

/**
 * @constructor
 * @param {number} iAccountId
 */
function CFolderModel(iAccountId)
{
	this.iAccountId = iAccountId;
	this.bNamespace = false;
	this.iLevel = 0;

	/** From server **/
	this.sDelimiter = '';
	this.bExists = true;
	/** Extended **/
	this.sUidNext = '';
	this.sHash = '';
	this.messageCount = ko.observable(0);
	this.unseenMessageCount = ko.observable(0);
	this.sRealUnseenMessageCount = 0;
	this.hasExtendedInfo = ko.observable(false);
	/** Extended **/
	this.fullName = ko.observable('');
	this.fullNameHash = ko.observable('');
	this.bSelectable = true;
	this.subscribed = ko.observable(true);
	this.name = ko.observable('');
	this.nameForEdit = ko.observable('');
	this.subfolders = ko.observableArray([]);
	this.subfoldersMessagesCount = ko.observable(0);
	this.type = ko.observable(Enums.FolderTypes.User);
	/** From server **/
	
	this.bVirtual = false;	// Indicates if the folder does not exist on mail server and uses as place for filtered message list.
							// At the moment the application supports only one type of virtual folders - for starred messages.
	this.selected = ko.observable(false); // Indicates if the folder is selected on mail screen.
	this.expanded = ko.observable(false); // Indicates if subfolders are shown on mail screen.
	this.recivedAnim = ko.observable(false).extend({'autoResetToFalse': 500}); // Starts the animation for displaying moving messages to the folder on mail screen.

	this.edited = ko.observable(false); // Indicates if the folder name is edited now on settings screen.

	this.oMessages = {};
	
	this.oUids = {};

	this.aResponseHandlers = [];
	
	this.aRequestedUids = [];
	this.aRequestedThreadUids = [];
	this.requestedLists = [];
	
	this.hasChanges = ko.observable(false);
	
	this.unseenFilterCommand = Utils.createCommand(this, this.executeUnseenFilter, this.showUnseenMessages);
	
	this.relevantInformationLastMoment = null;
}

CFolderModel.prototype.requireMailCache = function ()
{
	if (MailCache === null)
	{
		MailCache = require('modules/Mail/js/Cache.js');
	}
};

/**
 * @param {number} iLevel
 */
CFolderModel.prototype.setLevel = function (iLevel)
{
	this.iLevel = iLevel;
};

/**
 * @param {string} sUid
 * @returns {Object}
 */
CFolderModel.prototype.getMessageByUid = function (sUid)
{
	return this.oMessages[sUid];
};

/**
 * @returns {Array}
 */
CFolderModel.prototype.getFlaggedMessageUids = function ()
{
	var aUids = [];
	_.each(this.oMessages, function (oMessage) {
		if (oMessage.flagged())
		{
			aUids.push(oMessage.uid());
		}
	});
	return aUids;
};

/**
 * @param {string} sUid
 */
CFolderModel.prototype.setMessageUnflaggedByUid = function (sUid)
{
	var oMessage = this.oMessages[sUid];
	if (oMessage)
	{
		oMessage.flagged(false);
	}
};

/**
 * @param {Object} oMessage
 */
CFolderModel.prototype.hideThreadMessages = function (oMessage)
{
	_.each(oMessage.threadUids(), function (sThreadUid) {
		var oMess = this.oMessages[sThreadUid];
		if (oMess)
		{
			if (!oMess.deleted())
			{
				oMess.threadShowAnimation(false);
				oMess.threadHideAnimation(true);
				
				setTimeout(function () {
					oMess.threadHideAnimation(false);
				}, 1000);
			}
		}
	}, this);
};

/**
 * @param {Object} oMessage
 */
CFolderModel.prototype.getThreadMessages = function (oMessage)
{
	var
		aLoadedMessages = [],
		aUidsForLoad = [],
		aChangedThreadUids = [],
		iCount = 0,
		oLastMessage = null,
		iShowThrottle = 50
	;
	
	_.each(oMessage.threadUids(), function (sThreadUid) {
		if (iCount < oMessage.threadCountForLoad())
		{
			var oMess = this.oMessages[sThreadUid];
			if (oMess)
			{
				if (!oMess.deleted())
				{
					oMess.markAsThreadPart(iShowThrottle, oMessage.uid());
					aLoadedMessages.push(oMess);
					aChangedThreadUids.push(oMess.uid());
					iCount++;
					oLastMessage = oMess;
				}
			}
			else
			{
				aUidsForLoad.push(sThreadUid);
				aChangedThreadUids.push(sThreadUid);
				iCount++;
			}
		}
		else
		{
			aChangedThreadUids.push(sThreadUid);
		}
	}, this);
	
	if (!oMessage.threadLoading())
	{
		this.loadThreadMessages(aUidsForLoad);
	}
	
	oMessage.changeThreadUids(aChangedThreadUids, aLoadedMessages.length);
	
	if (oLastMessage && aLoadedMessages.length < oMessage.threadUids().length)
	{
		oLastMessage.showNextLoadingLink(_.bind(oMessage.increaseThreadCountForLoad, oMessage));
	}
	
	this.addThreadUidsToUidLists(oMessage.uid(), oMessage.threadUids());
	
	return aLoadedMessages;
};

/**
 * @param {Object} oMessage
 */
CFolderModel.prototype.computeThreadData = function (oMessage)
{
	var
		iUnreadCount = 0,
		bPartialFlagged = false,
		aSenders = [],
		aEmails = [],
		sMainEmail = oMessage.oFrom.getFirstEmail()
	;
	
	_.each(oMessage.threadUids(), function (sThreadUid) {
		var
			oThreadMessage = this.oMessages[sThreadUid],
			sThreadEmail = ''
		;
		
		if (oThreadMessage && !oThreadMessage.deleted())
		{
			if (!oThreadMessage.seen())
			{
				iUnreadCount++;
			}
			if (oThreadMessage.flagged())
			{
				bPartialFlagged = true;
			}
			
			sThreadEmail = oThreadMessage.oFrom.getFirstEmail();
			if ((sThreadEmail !== sMainEmail) && (-1 === $.inArray(sThreadEmail, aEmails)))
			{
				aEmails.push(sThreadEmail);
				if (sThreadEmail === Accounts.getEmail())
				{
					aSenders.push(TextUtils.i18n('MESSAGE/ME_SENDER'));
				}
				else
				{
					aSenders.push(oThreadMessage.oFrom.getFirstDisplay());
				}
			}
		}
	}, this);
	
	oMessage.threadUnreadCount(iUnreadCount);
	oMessage.partialFlagged(bPartialFlagged);
	oMessage.threadSenders(aSenders);
};

/**
 * 
 * @param {string} sUid
 * @param {Array} aThreadUids
 */
CFolderModel.prototype.addThreadUidsToUidLists = function (sUid, aThreadUids)
{
	_.each(this.oUids, function (oUidSearchList) {
		_.each(oUidSearchList, function (oUidList) {
			oUidList.addThreadUids(sUid, aThreadUids);
		});
	});
};

/**
 * @param {Array} aUidsForLoad
 */
CFolderModel.prototype.loadThreadMessages = function (aUidsForLoad)
{
	if (aUidsForLoad.length > 0)
	{
		var
			oParameters = {
				'Action': 'MessagesGetListByUids',
				'Folder': this.fullName(),
				'Uids': aUidsForLoad
			}
		;

		Ajax.send(oParameters, this.onMessagesGetListByUidsResponse, this);
	}
};

/**
 * @param {Array} aMessages
 */
CFolderModel.prototype.getThreadCheckedUidsFromList = function (aMessages)
{
	var
		oFolder = this,
		aThreadUids = []
	;
	
	_.each(aMessages, function (oMessage) {
		if (oMessage.threadCount() > 0 && !oMessage.threadOpened())
		{
			_.each(oMessage.threadUids(), function (sUid) {
				var oThreadMessage = oFolder.oMessages[sUid];
				if (oThreadMessage && !oThreadMessage.deleted() && oThreadMessage.checked())
				{
					aThreadUids.push(sUid);
				}
			});
		}
	});
	
	return aThreadUids;
};

/**
 * @param {Object} oRawMessage
 * @param {boolean} bThreadPart
 * @param {boolean} bTrustThreadInfo
 */
CFolderModel.prototype.parseAndCacheMessage = function (oRawMessage, bThreadPart, bTrustThreadInfo)
{
	var
		sUid = oRawMessage.Uid.toString(),
		bNewMessage = Utils.isUnd(this.oMessages[sUid]),
		oMessage = bNewMessage ? new CMessageModel() : this.oMessages[sUid]
	;
	
	oMessage.parse(oRawMessage, this.iAccountId, bThreadPart, bTrustThreadInfo);
	if (this.type() === Enums.FolderTypes.Inbox && bNewMessage && oMessage.flagged())
	{
		this.requireMailCache();
		MailCache.increaseStarredCount();
	}
	
	this.oMessages[oMessage.uid()] = oMessage;
	
	return oMessage;
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CFolderModel.prototype.onMessagesGetListByUidsResponse = function (oResponse, oRequest)
{
	var oResult = oResponse.Result;
	
	if (oResult && oResult['@Object'] === 'Collection/MessageCollection')
	{
		_.each(oResult['@Collection'], function (oRawMessage) {
			this.parseAndCacheMessage(oRawMessage, true, true);
		}, this);
		
		this.requireMailCache();
		MailCache.showOpenedThreads(this.fullName());
	}
};

/**
 * Adds uids of requested messages.
 * 
 * @param {Array} aUids
 */
CFolderModel.prototype.addRequestedUids = function (aUids)
{
	this.aRequestedUids = _.union(this.aRequestedUids, aUids);
};

/**
 * @param {string} sUid
 */
CFolderModel.prototype.hasUidBeenRequested = function (sUid)
{
	return _.indexOf(this.aRequestedUids, sUid) !== -1;
};

/**
 * Adds uids of requested thread message headers.
 * 
 * @param {Array} aUids
 */
CFolderModel.prototype.addRequestedThreadUids = function (aUids)
{
	this.aRequestedThreadUids = _.union(this.aRequestedThreadUids, aUids);
};

/**
 * @param {string} sUid
 */
CFolderModel.prototype.hasThreadUidBeenRequested = function (sUid)
{
	return _.indexOf(this.aRequestedThreadUids, sUid) !== -1;
};

/**
 * @param {Object} oParams
 */
CFolderModel.prototype.hasListBeenRequested = function (oParams)
{
	var
		aFindedParams = _.where(this.requestedLists, oParams),
		bHasParams = aFindedParams.length > 0
	;
	
	if (!bHasParams)
	{
		this.requestedLists.push(oParams);
	}
	return bHasParams;
};

/**
 * @param {string} sUid
 * @param {string} sReplyType
 */
CFolderModel.prototype.markMessageReplied = function (sUid, sReplyType)
{
	var oMsg = this.oMessages[sUid];
	
	if (oMsg)
	{
		switch (sReplyType)
		{
			case Enums.ReplyType.Reply:
			case Enums.ReplyType.ReplyAll:
				oMsg.answered(true);
				break;
			case Enums.ReplyType.Forward:
				oMsg.forwarded(true);
				break;
		}
	}
};

CFolderModel.prototype.removeAllMessages = function ()
{
	var oUidList = null;
	
	this.oMessages = {};
	this.oUids = {};

	this.messageCount(0);
	this.unseenMessageCount(0);
	this.sRealUnseenMessageCount = 0;
	
	oUidList = this.getUidList('', '');
	oUidList.resultCount(0);
};

CFolderModel.prototype.removeAllMessageListsFromCacheIfHasChanges = function ()
{
	if (this.hasChanges())
	{
		this.oUids = {};
		this.requestedLists = [];
		this.aRequestedThreadUids = [];
		this.hasChanges(false);
	}
};

CFolderModel.prototype.removeFlaggedMessageListsFromCache = function ()
{
	_.each(this.oUids, function (oSearchUids, sSearch) {
		delete this.oUids[sSearch][Enums.FolderFilter.Flagged];
	}, this);
};

CFolderModel.prototype.removeUnseenMessageListsFromCache = function ()
{
	_.each(this.oUids, function (oSearchUids, sSearch) {
		delete this.oUids[sSearch][Enums.FolderFilter.Unseen];
	}, this);
};

/**
 * @param {string} sUidNext
 * @param {string} sHash
 * @param {number} iMsgCount
 * @param {number} iMsgUnseenCount
 * @param {boolean} bUpdateOnlyRealData
 */
CFolderModel.prototype.setRelevantInformation = function (sUidNext, sHash, iMsgCount, iMsgUnseenCount, bUpdateOnlyRealData)
{
	var hasChanges = this.hasExtendedInfo() && (this.sHash !== sHash || this.sRealUnseenMessageCount !== iMsgUnseenCount);
	
	if (!bUpdateOnlyRealData)
	{
		this.sUidNext = sUidNext;
	}
	this.sHash = sHash; // if different, either new messages were appeared, or some messages were deleted
	if (!this.hasExtendedInfo() || !bUpdateOnlyRealData)
	{
		this.messageCount(iMsgCount);
		this.unseenMessageCount(iMsgUnseenCount);
		if (iMsgUnseenCount === 0) { this.unseenMessageCount.valueHasMutated(); } //fix for folder count summing
	}
	this.sRealUnseenMessageCount = iMsgUnseenCount;
	this.hasExtendedInfo(true);

	if (hasChanges)
	{
		this.markHasChanges();
	}
	
	this.relevantInformationLastMoment = moment(); // Date and time of last updating of the folder information.
	
	return hasChanges;
};

CFolderModel.prototype.increaseCountIfHasNotInfo = function ()
{
	if (!this.hasExtendedInfo())
	{
		this.messageCount(this.messageCount() + 1);
	}
};

CFolderModel.prototype.markHasChanges = function ()
{
	this.hasChanges(true);
};

/**
 * @param {number} iDiff
 * @param {number} iUnseenDiff
 */
CFolderModel.prototype.addMessagesCountsDiff = function (iDiff, iUnseenDiff)
{
	var
		iCount = this.messageCount() + iDiff,
		iUnseenCount = this.unseenMessageCount() + iUnseenDiff
	;

	if (iCount < 0)
	{
		iCount = 0;
	}
	this.messageCount(iCount);

	if (iUnseenCount < 0)
	{
		iUnseenCount = 0;
	}
	if (iUnseenCount > iCount)
	{
		iUnseenCount = iCount;
	}
	this.unseenMessageCount(iUnseenCount);
};

/**
 * @param {Array} aUids
 */
CFolderModel.prototype.markDeletedByUids = function (aUids)
{
	var
		iMinusDiff = 0,
		iUnseenMinusDiff = 0
	;

	_.each(aUids, function (sUid)
	{
		var oMessage = this.oMessages[sUid];

		if (oMessage)
		{
			iMinusDiff++;
			if (!oMessage.seen())
			{
				iUnseenMinusDiff++;
			}
			oMessage.deleted(true);
		}

	}, this);

	this.addMessagesCountsDiff(-iMinusDiff, -iUnseenMinusDiff);
	
	return {MinusDiff: iMinusDiff, UnseenMinusDiff: iUnseenMinusDiff};
};

/**
 * @param {Array} aUids
 */
CFolderModel.prototype.revertDeleted = function (aUids)
{
	var
		iPlusDiff = 0,
		iUnseenPlusDiff = 0
	;

	_.each(aUids, function (sUid)
	{
		var oMessage = this.oMessages[sUid];

		if (oMessage && oMessage.deleted())
		{
			iPlusDiff++;
			if (!oMessage.seen())
			{
				iUnseenPlusDiff++;
			}
			oMessage.deleted(false);
		}

	}, this);

	this.addMessagesCountsDiff(iPlusDiff, iUnseenPlusDiff);

	return {PlusDiff: iPlusDiff, UnseenPlusDiff: iUnseenPlusDiff};
};

/**
 * @param {Array} aUids
 */
CFolderModel.prototype.commitDeleted = function (aUids)
{
	_.each(aUids, _.bind(function (sUid) {
		delete this.oMessages[sUid];
	}, this));
	
	_.each(this.oUids, function (oUidSearchList) {
		_.each(oUidSearchList, function (oUidList) {
			oUidList.deleteUids(aUids);
		});
	});
};

/**
 * @param {string} sSearch
 * @param {string} sFilters
 */
CFolderModel.prototype.getUidList = function (sSearch, sFilters)
{
	var
		oUidList = null
	;
	
	if (this.oUids[sSearch] === undefined)
	{
		this.oUids[sSearch] = {};
	}
	
	if (this.oUids[sSearch][sFilters] === undefined)
	{
		oUidList = new CUidListModel();
		oUidList.search(sSearch);
		oUidList.filters(sFilters);
		this.oUids[sSearch][sFilters] = oUidList;
	}
	
	return this.oUids[sSearch][sFilters];
};

/**
 * @param {number} iLevel
 * @param {string} sFullName
 */
CFolderModel.prototype.initStarredFolder = function (iLevel, sFullName)
{
	this.bVirtual = true;
	this.setLevel(iLevel);
	this.fullName(sFullName);
	this.name(TextUtils.i18n('MAIN/FOLDER_STARRED'));
	this.type(Enums.FolderTypes.Starred);
	this.initSubscriptions('');
	this.initComputedFields(true);
};

/**
 * @param {Object} oData
 * @param {string} sParentFullName
 * @param {boolean} bDisableManageSubscribe
 * @param {string} sNamespaceFolder
 */
CFolderModel.prototype.parse = function (oData, sParentFullName, bDisableManageSubscribe, sNamespaceFolder)
{
	var
		sName = '',
		aFolders = Storage.getData('folderAccordion') || []
	;

	if (oData['@Object'] === 'Object/Folder')
	{
		sName = Utils.pString(oData.Name);
		
		this.name(sName);
		this.nameForEdit(sName);
		this.fullName(Utils.pString(oData.FullNameRaw));
		this.fullNameHash(Utils.pString(oData.FullNameHash));
		this.sDelimiter = oData.Delimiter;
		this.type(oData.Type);
		this.bNamespace = (sNamespaceFolder === this.fullName());
		
		this.subscribed(oData.IsSubscribed);
		this.bSelectable = oData.IsSelectable;
		this.bExists = oData.Exists;
		
		if (oData.Extended)
		{
			this.setRelevantInformation(oData.Extended.UidNext.toString(), oData.Extended.Hash, 
				oData.Extended.MessageCount, oData.Extended.MessageUnseenCount, false);
		}

		if (_.find(aFolders, function (sFolder) { return sFolder === this.name(); }, this))
		{
			this.expanded(true);
		}

		this.initSubscriptions(sParentFullName);
		this.initComputedFields(bDisableManageSubscribe);
		
		return oData.SubFolders;
	}

	return null;
};

/**
 * @param {string} sParentFullName
 */
CFolderModel.prototype.initSubscriptions = function (sParentFullName)
{
	this.requireMailCache();
	this.unseenMessageCount.subscribe(function () {
		_.delay(_.bind(function () {
			MailCache.countMessages(this);
		},this), 1000);
	}, this);
	
	this.subscribed.subscribe(function () {
		if (sParentFullName)
		{
			var oParentFolder = MailCache.folderList().getFolderByFullName(sParentFullName);
			if(oParentFolder)
			{
				MailCache.countMessages(oParentFolder);
			}
		}
	}, this);
	
	this.edited.subscribe(function (bEdited) {
		if (bEdited === false)
		{
			this.nameForEdit(this.name());
		}
	}, this);
	
	this.hasChanges.subscribe(function () {
		this.requestedLists = [];
	}, this);
};

/**
 * @param {boolean} bDisableManageSubscribe
 */
CFolderModel.prototype.initComputedFields = function (bDisableManageSubscribe)
{
	this.routingHash = ko.computed(function () {
		// At the moment the application supports only one type of virtual folders - for starred messages.
		if (this.bVirtual)
		{
			return Routing.buildHashFromArray(LinksUtils.getMailbox(this.fullName(), 1, '', '', Enums.FolderFilter.Flagged));
		}
		else
		{
			return Routing.buildHashFromArray(['mail', this.fullName()]);
		}
	}, this);
	
	this.isSystem = ko.computed(function () {
		return this.type() !== Enums.FolderTypes.User;
	}, this);

	this.showUnseenMessages = ko.computed(function () {
		return this.type() !== Enums.FolderTypes.Drafts;
	}, this);
	
	this.withoutThreads = ko.computed(function () {
		return	this.type() === Enums.FolderTypes.Drafts || 
				this.type() === Enums.FolderTypes.Spam ||
				this.type() === Enums.FolderTypes.Trash;
	}, this);

	this.enableEmptyFolder = ko.computed(function () {
		return (this.type() === Enums.FolderTypes.Spam ||
				this.type() === Enums.FolderTypes.Trash) &&
				this.messageCount() > 0;
	}, this);

	this.virtualEmpty = ko.computed(function () {
		return this.bVirtual && this.messageCount() === 0;
	}, this);
	
	// indicates if folder has at least one subscribed subfolder
	this.hasSubscribedSubfolders = ko.computed(function () {
		return _.any(this.subfolders(), function (oFolder) {
			return oFolder.subscribed();
		});
	}, this);

	// indicates if folder can be expanded, i.e. folder is not namespace and has at least one subscribed subfolder
	this.canExpand = ko.computed(function () {
		return !this.bNamespace && this.hasSubscribedSubfolders();
	}, this);
	
	this.messageCountToShow = ko.computed(function () {
		if (this.canExpand())
		{
			return (this.showUnseenMessages()) ? this.unseenMessageCount() + this.subfoldersMessagesCount() : this.messageCount();
		}
		else
		{
			return (this.showUnseenMessages()) ? this.unseenMessageCount() : this.messageCount();
		}
	}, this);
	
	this.visible = ko.computed(function () {
		return this.subscribed() || this.isSystem() || (this.hasSubscribedSubfolders() && (!this.bExists || !this.bSelectable));
	}, this);

	this.bCanBeSelected = this.bExists && this.bSelectable;
	
	this.canSubscribe = ko.computed(function () {
		return !bDisableManageSubscribe && !this.isSystem() && this.bCanBeSelected;
	}, this);
	
	this.canDelete = ko.computed(function () {
		return (!this.isSystem() && this.hasExtendedInfo() && this.messageCount() === 0 && this.subfolders().length === 0);
	}, this);

	this.canRename = ko.computed(function () {
		return (this.bCanBeSelected && !this.isSystem());
	}, this);

	this.subscribeButtonHint = ko.computed(function () {
		if (this.canSubscribe())
		{
			return this.subscribed() ? TextUtils.i18n('SETTINGS/ACCOUNT_FOLDERS_HIDE_FOLDER_HINT') : TextUtils.i18n('SETTINGS/ACCOUNT_FOLDERS_SHOW_FOLDER_HINT');
		}
		return '';
	}, this);
	
	this.deleteButtonHint = ko.computed(function () {
		return this.canDelete() ? TextUtils.i18n('SETTINGS/ACCOUNT_FOLDERS_DELETE_FOLDER_HINT') : '';
	}, this);
	
	this.usedAs = ko.computed(function () {
		switch (this.type())
		{
			case Enums.FolderTypes.Inbox:
				return TextUtils.i18n('SETTINGS/ACCOUNT_FOLDERS_USED_AS_INBOX');
			case Enums.FolderTypes.Sent:
				return TextUtils.i18n('SETTINGS/ACCOUNT_FOLDERS_USED_AS_SENT');
			case Enums.FolderTypes.Drafts:
				return TextUtils.i18n('SETTINGS/ACCOUNT_FOLDERS_USED_AS_DRAFTS');
			case Enums.FolderTypes.Trash:
				return TextUtils.i18n('SETTINGS/ACCOUNT_FOLDERS_USED_AS_TRASH');
			case Enums.FolderTypes.Spam:
				return TextUtils.i18n('SETTINGS/ACCOUNT_FOLDERS_USED_AS_SPAM');
		}
		return '';
	}, this);

	this.displayName = ko.computed(function () {
		switch (this.type())
		{
			case Enums.FolderTypes.Inbox:
				return TextUtils.i18n('MAIN/FOLDER_INBOX');
			case Enums.FolderTypes.Sent:
				return TextUtils.i18n('MAIN/FOLDER_SENT');
			case Enums.FolderTypes.Drafts:
				return TextUtils.i18n('MAIN/FOLDER_DRAFTS');
			case Enums.FolderTypes.Trash:
				return TextUtils.i18n('MAIN/FOLDER_TRASH');
			case Enums.FolderTypes.Spam:
				return TextUtils.i18n('MAIN/FOLDER_SPAM');
		}
		return this.name();
	}, this);
	
	this.unseenMessagesTitle = ko.computed(function () {
		return this.showUnseenMessages() ? TextUtils.i18n('MAILBOX/TITLE_UNSEEN_MESSAGES_ONLY') : '';
	}, this);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CFolderModel.prototype.onMessageGetResponse = function (oResponse, oRequest)
{
	var
		oResult = oResponse.Result,
		oHand = null,
		sUid = oResult ? oResult.Uid.toString() : oRequest.Uid.toString(),
		oMessage = this.oMessages[sUid],
		bSelected = oMessage ? oMessage.selected() : false
	;
	
	if (!oResult)
	{
		if (bSelected)
		{
			Api.showErrorByCode(oResponse, TextUtils.i18n('WARNING/UNKNOWN_ERROR'));
			Routing.replaceHashWithoutMessageUid(sUid);
		}
		
		oMessage = null;
	}
	else
	{
		oMessage = this.parseAndCacheMessage(oResult, false, false);
//		if (oMessage && oMessage.ical() && oMessage.ical().isReplyType() && App.CalendarCache)
//		{
//			App.CalendarCache.calendarChanged(true);
//		}
	}

	oHand = this.aResponseHandlers[sUid];
	if (oHand)
	{
		oHand.handler.call(oHand.context, oMessage, sUid);
		delete this.aResponseHandlers[sUid];
	}
};

/**
 * @param {string} sUid
 * @param {Function} fResponseHandler
 * @param {Object} oContext
 */
CFolderModel.prototype.getCompletelyFilledMessage = function (sUid, fResponseHandler, oContext)
{
	var
		oMessage = this.oMessages[sUid],
		oParameters = {
			'Action': 'MessageGet',
			'Folder': this.fullName(),
			'Uid': sUid
		}
	;

	if (sUid.length > 0)
	{
		if (!oMessage || !oMessage.completelyFilled() || oMessage.trimmed())
		{
			if (fResponseHandler && oContext)
			{
				this.aResponseHandlers[sUid] = {handler: fResponseHandler, context: oContext};
			}
			
			Ajax.send(oParameters, this.onMessageGetResponse, this);
		}
		else if (fResponseHandler && oContext)
		{
			fResponseHandler.call(oContext, oMessage, sUid);
		}
	}
};

/**
 * @param {string} sUid
 */
CFolderModel.prototype.showExternalPictures = function (sUid)
{
	var oMessage = this.oMessages[sUid];

	if (oMessage !== undefined)
	{
		oMessage.showExternalPictures();
	}
};

/**
 * @param {string} sEmail
 */
CFolderModel.prototype.alwaysShowExternalPicturesForSender = function (sEmail)
{
	_.each(this.oMessages, function (oMessage)
	{
		var aFrom = oMessage.oFrom.aCollection;
		if (aFrom.length > 0 && aFrom[0].sEmail === sEmail)
		{
			oMessage.alwaysShowExternalPicturesForSender();
		}
	}, this);
};

/**
 * @param {string} sField
 * @param {Array} aUids
 * @param {boolean} bSetAction
 */
CFolderModel.prototype.executeGroupOperation = function (sField, aUids, bSetAction)
{
	var iUnseenDiff = 0;

	_.each(this.oMessages, function (oMessage)
	{
		if (aUids.length > 0)
		{
			_.each(aUids, function (sUid)
			{
				if (oMessage && oMessage.uid() === sUid && oMessage[sField]() !== bSetAction)
				{
					oMessage[sField](bSetAction);
					iUnseenDiff++;
				}
			});
		}
		else
		{
			oMessage[sField](bSetAction);
		}
	});

	if (aUids.length === 0)
	{
		iUnseenDiff = (bSetAction) ? this.unseenMessageCount() : this.messageCount() - this.unseenMessageCount();
	}

	if (sField === 'seen' && iUnseenDiff > 0)
	{
		if (bSetAction)
		{
			this.addMessagesCountsDiff(0, -iUnseenDiff);
		}
		else
		{
			this.addMessagesCountsDiff(0, iUnseenDiff);
		}
		this.markHasChanges();
	}
};

CFolderModel.prototype.emptyFolder = function ()
{
	var
		sWarning = TextUtils.i18n('MAILBOX/CONFIRM_EMPTY_FOLDER'),
		fCallBack = _.bind(this.clearFolder, this)
	;
	
	if (this.enableEmptyFolder())
	{
		App.Screens.showPopup(ConfirmPopup, [sWarning, fCallBack]);
	}
};

/**
 * @param {boolean} bOkAnswer
 */
CFolderModel.prototype.clearFolder = function (bOkAnswer)
{
	var
		oParameters = {
			'Action': 'FolderClear',
			'Folder': this.fullName()
		}
	;
	
	if (this.enableEmptyFolder() && bOkAnswer)
	{
		Ajax.send(oParameters);

		this.removeAllMessages();

		this.requireMailCache();
		MailCache.onClearFolder(this);
	}
};

CFolderModel.prototype.getNameWhithLevel = function ()
{
	var iLevel = this.iLevel;
	
	if (!this.bNamespace && iLevel > 0)
	{
		iLevel--;
	}
	
	return Utils.strRepeat("\u00A0", iLevel * 3) + this.name();
};

/**
 * @param {Object} oFolder
 * @param {Object} oEvent
 */
CFolderModel.prototype.onAccordion = function (oFolder, oEvent)
{
	var
		bExpanded = !this.expanded(),
		aFolders = Storage.getData('folderAccordion') || []
	;

	if (bExpanded)
	{
		aFolders.push(this.name());
	}
	else
	{
		// remove current folder from expanded folders
		aFolders = _.reject(aFolders, function (sFolder) { return sFolder === this.name(); }, this);
	}

	Storage.setData('folderAccordion', aFolders);
	this.expanded(bExpanded);

	this.requireMailCache();
	MailCache.countMessages(this);
	
	oEvent.stopPropagation();
};

CFolderModel.prototype.executeUnseenFilter = function ()
{
	var bNotChanged = false;
	if (this.showUnseenMessages())
	{
		this.requireMailCache();
		MailCache.waitForUnseenMessages(true);
		bNotChanged = Routing.setHash(LinksUtils.getMailbox(this.fullName(), 1, '', '', Enums.FolderFilter.Unseen));

		if (bNotChanged)
		{
			MailCache.changeCurrentMessageList(this.fullName(), 1, '', Enums.FolderFilter.Unseen);
		}
		return false;
	}
	return true;
};

module.exports = CFolderModel;