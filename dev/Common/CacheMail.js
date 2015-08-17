
/**
 * @constructor
 */
function CMailCache()
{
	this.currentAccountId = AppData.Accounts.currentId;

	this.currentAccountId.subscribe(function (iCurrAccountId) {
		var
			oAccount = AppData.Accounts.getAccount(iCurrAccountId),
			oFolderList = this.oFolderListItems[iCurrAccountId]
		;
		if (oAccount)
		{
			oAccount.quotaRecieved(false);
			
			this.messagesLoadingError(false);
			
			if (oFolderList)
			{
				this.folderList(oFolderList);
			}
			else
			{
				this.messagesLoading(oAccount.allowMail());
				this.folderList(new CFolderListModel());
				this.messages([]);
				this.currentMessage(null);
				this.getFolderList(iCurrAccountId);
			}
		}
	}, this);
	
	this.editedAccountId = AppData.Accounts.editedId;
	this.editedAccountId.subscribe(function (iEditedAccountId) {
		var oFolderList = this.oFolderListItems[iEditedAccountId];
		
		if (oFolderList)
		{
			this.editedFolderList(oFolderList);
		}
		else if (this.currentAccountId() !== iEditedAccountId)
		{
			this.editedFolderList(new CFolderListModel());
			this.getFolderList(iEditedAccountId);
		}
	}, this);
	
	this.oFolderListItems = {};

	this.quotaChangeTrigger = ko.observable(false);
	
	this.checkMailStarted = ko.observable(false);
	this.checkMailStartedAccountId = ko.observable(0);
	
	this.defaultFolderList = ko.observable(new CFolderListModel());
	
	this.folderList = ko.observable(new CFolderListModel());
	this.folderListLoading = ko.observableArray([]);
	
	this.editedFolderList = ko.observable(new CFolderListModel());

	this.newMessagesCount = ko.computed(function () {
		var
			oInbox = this.folderList().inboxFolder()
		;
		return oInbox ? oInbox.unseenMessageCount() : 0;
	}, this);
	this.newMessagesCount.subscribe(function (iMessagesCount) {
		App.mailUnseenCount(iMessagesCount > 99 ? '99+' : iMessagesCount);
	}, this);

	this.messages = ko.observableArray([]);
	this.messages.subscribe(function () {
		if (this.messages().length > 0)
		{
			this.messagesLoadingError(false);
		}
	}, this);
	
	this.uidList = ko.observable(new CUidListModel());
	this.page = ko.observable(1);
	
	this.messagesLoading = ko.observable(false);
	this.messagesLoadingError = ko.observable(false);
	
	this.currentMessage = ko.observable(null);
	this.currentMessage.subscribe(function () {
		if (this.currentMessage())
		{
			AfterLogicApi.runPluginHook('view-message', 
				[AppData.Accounts.currentId(), this.currentMessage().folder(), this.currentMessage().uid()]);
		}
	}, this);
	this.nextMessageUid = ko.computed(function () {
		var
			sCurrentUid = '',
			sNextUid = '',
			oFolder = null,
			oParentMessage = null,
			bThreadLevel = false
		;
		if (this.currentMessage() && AppData.SingleMode)
		{
			bThreadLevel = this.currentMessage().threadPart() && this.currentMessage().threadParentUid() !== '';
			oFolder = this.folderList().getFolderByFullName(this.currentMessage().folder());
			sCurrentUid = this.currentMessage().uid();
			if (AppData.ThreadLevel || bThreadLevel)
			{
				AppData.ThreadLevel = true;
				if (bThreadLevel)
				{
					oParentMessage = oFolder.getMessageByUid(this.currentMessage().threadParentUid());
					if (oParentMessage)
					{
						_.each(oParentMessage.threadUids(), function (sUid, iIndex, aCollection) {
							if (sUid === sCurrentUid && iIndex > 0)
							{
								sNextUid = aCollection[iIndex - 1];
							}
						});
						if (Utils.isUnd(sNextUid) || sNextUid === '')
						{
							sNextUid = oParentMessage.uid();
						}
					}
				}
			}
			else
			{
				_.each(this.uidList().collection(), function (sUid, iIndex, aCollection) {
					if (sUid === sCurrentUid && iIndex > 0)
					{
						sNextUid = aCollection[iIndex - 1];
					}
				});
				if (Utils.isUnd(sNextUid))
				{
					sNextUid = '';
				}
				if (sNextUid === '' && window.opener && window.opener.App && window.opener.App.Prefetcher)
				{
					window.opener.App.Prefetcher.prefetchNextPage(sCurrentUid);
				}
			}
		}
		return sNextUid;
	}, this);
	this.prevMessageUid = ko.computed(function () {
		var
			sCurrentUid = this.currentMessage() ? this.currentMessage().uid() : '',
			sPrevUid = '',
			oFolder = null,
			oParentMessage = null,
			bThreadLevel = false
		;
		if (this.currentMessage() && AppData.SingleMode)
		{
			bThreadLevel = this.currentMessage().threadPart() && this.currentMessage().threadParentUid() !== '';
			oFolder = this.folderList().getFolderByFullName(this.currentMessage().folder());
			sCurrentUid = this.currentMessage().uid();
			if (AppData.ThreadLevel || bThreadLevel)
			{
				AppData.ThreadLevel = true;
				if (bThreadLevel)
				{
					oParentMessage = oFolder.getMessageByUid(this.currentMessage().threadParentUid());
					if (oParentMessage)
					{
						_.each(oParentMessage.threadUids(), function (sUid, iIndex, aCollection) {
							if (sUid === sCurrentUid && (iIndex + 1) < aCollection.length)
							{
								sPrevUid = aCollection[iIndex + 1];
							}
						});
						if (Utils.isUnd(sPrevUid))
						{
							sPrevUid = '';
						}
					}
				}
				else if (this.currentMessage().threadCount() > 0)
				{
					sPrevUid = this.currentMessage().threadUids()[0];
				}
			}
			else
			{
				_.each(this.uidList().collection(), function (sUid, iIndex, aCollection) {
					if (sUid === sCurrentUid && (iIndex + 1) < aCollection.length)
					{
						sPrevUid = aCollection[iIndex + 1];
					}
				});
				if (Utils.isUnd(sPrevUid))
				{
					sPrevUid = '';
				}
				if (sPrevUid === '' && window.opener && window.opener.App && window.opener.App.Prefetcher)
				{
					window.opener.App.Prefetcher.prefetchPrevPage(sCurrentUid);
				}
			}
		}
		return sPrevUid;
	}, this);

	this.savingDraftUid = ko.observable('');
	this.editedDraftUid = ko.observable('');
	this.disableComposeAutosave = ko.observable(false);
	
	this.aResponseHandlers = [];

	AppData.User.useThreads.subscribe(function () {
		_.each(this.oFolderListItems, function (oFolderList) {
			_.each(oFolderList.collection(), function (oFolder) {
				oFolder.markHasChanges();
				oFolder.removeAllMessageListsFromCacheIfHasChanges();
			}, this);
		}, this);
		this.messages([]);
	}, this);
	
	this.iAutoCheckMailTimer = -1;
	
	this.waitForUnseenMessages = ko.observable(true);
	
	this.iMessageSetSeenCount = 0;	
	
	this.__name = 'CMailCache';
}

/**
 * @public
 */
CMailCache.prototype.init = function ()
{
	var oMailCache = null;
	
	App.Ajax.openedRequestsCount.subscribe(function () {
		if (App.Ajax.openedRequestsCount() === 0)
		{
			// Delay not to reset these flags between two related requests (e.g. 'FoldersGetRelevantInformation' and 'MessagesGetList')
			_.delay(_.bind(function () {
				if (App.Ajax.requests().length === 0)
				{
					this.checkMailStarted(false);
					this.folderListLoading.removeAll();
				}
			}, this), 10);
		}
	}, this);
	
	if (AppData.SingleMode && window.opener)
	{
		oMailCache = window.opener.App.MailCache;
		
		this.oFolderListItems = oMailCache.oFolderListItems;
		this.uidList(oMailCache.uidList());
		oMailCache.uidList.subscribe(_.bind(function () {
			this.uidList(oMailCache.uidList());
		}, this));
		if (window.name)
		{
			var
				iAccountId = Utils.pInt(window.name),
				oMessageParametersFromCompose
			;
			
			if (iAccountId === 0 && window.opener && window.opener.aMessagesParametersFromCompose)
			{
				oMessageParametersFromCompose = window.opener.aMessagesParametersFromCompose[window.name];
				iAccountId = oMessageParametersFromCompose ? oMessageParametersFromCompose.accountId : 0;
			}
			
			if (iAccountId !== 0)
			{
				this.currentAccountId(iAccountId);
			}
		}
	}
	
	this.currentAccountId.valueHasMutated();
};

CMailCache.prototype.getCurrentFolder = function ()
{
	return this.folderList().currentFolder();
};

/**
 * @param {number} iAccountId
 * @param {string} sFolderFullName
 */
CMailCache.prototype.getFolderByFullName = function (iAccountId, sFolderFullName)
{
	var
		oFolderList = this.oFolderListItems[iAccountId]
	;
	
	if (oFolderList)
	{
		return oFolderList.getFolderByFullName(sFolderFullName);
	}
	
	return null;
};

CMailCache.prototype.checkCurrentFolderList = function ()
{
	var
		oCurrAccount = AppData.Accounts.getCurrent(),
		oFolderList = this.oFolderListItems[oCurrAccount.id()]
	;
	
	if (oCurrAccount.allowMail() && !oFolderList && !this.messagesLoading())
	{
		this.messagesLoading(true);
		this.messagesLoadingError(false);
		this.getFolderList(oCurrAccount.id());
	}
};

/**
 * @param {number} iAccountID
 */
CMailCache.prototype.getFolderList = function (iAccountID)
{
	var
		oAccount = AppData.Accounts.getAccount(iAccountID),
		oParameters = {
			'AccountID': iAccountID,
			'Action': 'FoldersGetList'
		}
	;
	
	if (oAccount && oAccount.allowMail())
	{
		this.folderListLoading.push(iAccountID);

		App.Ajax.send(oParameters, this.onFoldersGetListResponse, this);
	}
	else if (iAccountID === this.currentAccountId())
	{
		this.messagesLoading(false);
	}
};

/**
 * @param {number} iAccountId
 * @param {string} sFullName
 * @param {string} sUid
 * @param {string} sReplyType
 */
CMailCache.prototype.markMessageReplied = function (iAccountId, sFullName, sUid, sReplyType)
{
	var
		oFolderList = this.oFolderListItems[iAccountId],
		oFolder = null
	;
	
	if (oFolderList)
	{
		oFolder = oFolderList.getFolderByFullName(sFullName);
		if (oFolder)
		{
			oFolder.markMessageReplied(sUid, sReplyType);
		}
	}
};

/**
 * @param {Object} oMessage
 */
CMailCache.prototype.hideThreads = function (oMessage)
{
	if (AppData.User.useThreads() && oMessage.folder() === this.folderList().currentFolderFullName() && !oMessage.threadOpened())
	{
		this.folderList().currentFolder().hideThreadMessages(oMessage);
	}
};

/**
 * @param {string} sFolderFullName
 */
CMailCache.prototype.showOpenedThreads = function (sFolderFullName)
{
	this.messages(this.getMessagesWithThreads(sFolderFullName, this.uidList(), this.messages()));
};

/**
 * @param {Object} oUidList
 * @returns {Boolean}
 */
CMailCache.prototype.useThreadsInCurrentList = function (oUidList)
{
	oUidList = oUidList || this.uidList();
	
	var
		oCurrFolder = this.folderList().currentFolder(),
		bFolderWithoutThreads = oCurrFolder && oCurrFolder.withoutThreads(),
		bNotSearchOrFilters = oUidList.search() === '' && oUidList.filters() === ''
	;
	
	return AppData.User.useThreads() && !bFolderWithoutThreads && bNotSearchOrFilters;
};

/**
 * @param {string} sFolderFullName
 * @param {Object} oUidList
 * @param {Array} aOrigMessages
 */
CMailCache.prototype.getMessagesWithThreads = function (sFolderFullName, oUidList, aOrigMessages)
{
	var
		aExtMessages = [],
		aMessages = [],
		oCurrFolder = this.folderList().currentFolder()
	;
	
	if (oCurrFolder && sFolderFullName === oCurrFolder.fullName() && this.useThreadsInCurrentList(oUidList))
	{
		aMessages = _.filter(aOrigMessages, function (oMess) {
			return !oMess.threadPart();
		});

		_.each(aMessages, function (oMess) {
			var aThreadMessages = [];
			aExtMessages.push(oMess);
			if (oMess.threadCount() > 0)
			{
				if (oMess.threadOpened())
				{
					aThreadMessages = this.folderList().currentFolder().getThreadMessages(oMess);
					aExtMessages = _.union(aExtMessages, aThreadMessages);
				}
				oCurrFolder.computeThreadData(oMess);
			}
		}, this);
		
		return aExtMessages;
	}
	
	return aOrigMessages;
};

/**
 * @param {Object} oUidList
 * @param {number} iOffset
 * @param {Object} oMessages
 * @param {boolean} bFillMessages
 */
CMailCache.prototype.setMessagesFromUidList = function (oUidList, iOffset, oMessages, bFillMessages)
{
	var
		aUids = oUidList.getUidsForOffset(iOffset, oMessages),
		aMessages = _.map(aUids, function (sUid) {
			return oMessages[sUid];
		}, this),
		iMessagesCount = aMessages.length
	;
	
	if (bFillMessages)
	{
		this.messages(this.getMessagesWithThreads(this.folderList().currentFolderFullName(), oUidList, aMessages));
		
		if ((iOffset + iMessagesCount < oUidList.resultCount()) &&
			(iMessagesCount < AppData.User.MailsPerPage) &&
			(oUidList.filters() !== Enums.FolderFilter.Unseen || this.waitForUnseenMessages()))
		{
			this.messagesLoading(true);
		}

		if (this.currentMessage() && (this.currentMessage().deleted() ||
			this.currentMessage().folder() !== this.folderList().currentFolderFullName()))
		{
			this.currentMessage(null);
		}
	}

	return aUids;
};

/**
 * @param {boolean} bAbortPrevious
 */
CMailCache.prototype.executeCheckMail = function (bAbortPrevious)
{
	var
		oFolderList = this.oFolderListItems[this.currentAccountId()],
		aFoldersFromAccount = AppData.Accounts.getCurrentFetchersAndFiltersFolderNames(),
		aFolders = oFolderList ? [oFolderList.inboxFolderFullName(), oFolderList.spamFolderFullName(), oFolderList.currentFolderFullName()] : [],
		iAccountID = oFolderList ? oFolderList.iAccountId : 0,
		bCurrentAccountCheckmailStarted = this.checkMailStarted() && (this.checkMailStartedAccountId() === iAccountID),
		oParameters = null
	;
	
	if (AppData.Auth && (bAbortPrevious || !App.Ajax.hasOpenedRequests('FoldersGetRelevantInformation') || !bCurrentAccountCheckmailStarted) && (aFolders.length > 0))
	{
		aFolders = _.uniq(_.compact(_.union(aFolders, aFoldersFromAccount)));
		oParameters = {
			'Action': 'FoldersGetRelevantInformation',
			'Folders': aFolders,
			'AccountID': iAccountID
		};
		
		this.checkMailStarted(true);
		this.checkMailStartedAccountId(iAccountID);
		App.Ajax.send(oParameters, this.onFoldersGetRelevantInformationResponse, this);
	}
};

CMailCache.prototype.setAutocheckmailTimer = function ()
{
	clearTimeout(this.iAutoCheckMailTimer);
	
	if (!AppData.SingleMode && AppData.User.AutoCheckMailInterval > 0)
	{
		this.iAutoCheckMailTimer = setTimeout(function () {
			if (!App.Ajax.isSearchMessages())
			{
				App.MailCache.checkMessageFlags();
				App.MailCache.executeCheckMail(false);
			}
		}, AppData.User.AutoCheckMailInterval * 60 * 1000);
	}
};

CMailCache.prototype.checkMessageFlags = function ()
{
	var
		oInbox = this.folderList().inboxFolder(),
		aUids = oInbox ? oInbox.getFlaggedMessageUids() : [],
		oParameters = {
			'Action': 'MessagesGetFlags',
			'Folder': this.folderList().inboxFolderFullName(),
			'Uids': aUids
		}
	;
	
	if (aUids.length > 0)
	{
		App.Ajax.send(oParameters, this.onMessagesGetFlagsResponse, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CMailCache.prototype.onMessagesGetFlagsResponse = function (oResponse, oRequest)
{
	var oInbox = this.folderList().inboxFolder();
	
	if (oResponse.Result)
	{
		_.each(oResponse.Result, function (aFlags, sUid) {
			if (_.indexOf(aFlags, '\\flagged') === -1)
			{
				oInbox.setMessageUnflaggedByUid(sUid);
			}
		});
	}
	oInbox.removeFlaggedMessageListsFromCache();
	App.Prefetcher.prefetchStarredMessageList();
};

/**
 * @param {string} sFolder
 * @param {number} iPage
 * @param {string} sSearch
 * @param {string=} sFilter
 */
CMailCache.prototype.changeCurrentMessageList = function (sFolder, iPage, sSearch, sFilter)
{
	this.requestCurrentMessageList(sFolder, iPage, sSearch, sFilter, true);
};

/**
 * @param {string} sFolder
 * @param {number} iPage
 * @param {string} sSearch
 * @param {string=} sFilter
 * @param {boolean=} bFillMessages
 */
CMailCache.prototype.requestCurrentMessageList = function (sFolder, iPage, sSearch, sFilter, bFillMessages)
{
	var
		oRequestData = this.requestMessageList(sFolder, iPage, sSearch, sFilter || '', true, (bFillMessages || false)),
		iCheckmailIntervalMilliseconds = AppData.User.AutoCheckMailInterval * 60 * 1000,
		iFolderUpdateDiff = oRequestData.Folder.relevantInformationLastMoment ? moment().diff(oRequestData.Folder.relevantInformationLastMoment) : iCheckmailIntervalMilliseconds + 1
	;
	
	this.uidList(oRequestData.UidList);
	this.page(iPage);
	
	this.messagesLoading(oRequestData.RequestStarted);
	this.messagesLoadingError(false);
	
	if (!oRequestData.RequestStarted && iCheckmailIntervalMilliseconds > 0 && iFolderUpdateDiff > iCheckmailIntervalMilliseconds)
	{
		this.executeCheckMail(true);
	}
};

/**
 * @param {string} sFolder
 * @param {number} iPage
 * @param {string} sSearch
 * @param {string} sFilters
 * @param {boolean} bCurrent
 * @param {boolean} bFillMessages
 */
CMailCache.prototype.requestMessageList = function (sFolder, iPage, sSearch, sFilters, bCurrent, bFillMessages)
{
	var
		oFolderList = this.oFolderListItems[this.currentAccountId()],
		oFolder = (oFolderList) ? oFolderList.getFolderByFullName(sFolder) : null,
		bFolderWithoutThreads = oFolder && oFolder.withoutThreads(),
		bUseThreads = AppData.User.useThreads() && !bFolderWithoutThreads && sSearch === '' && sFilters === '',
		oUidList = (oFolder) ? oFolder.getUidList(sSearch, sFilters) : null,
		bCacheIsEmpty = oUidList && oUidList.resultCount() === -1,
		iOffset = (iPage - 1) * AppData.User.MailsPerPage,
		oParameters = {
			'Action': 'MessagesGetList',
			'Folder': sFolder,
			'Offset': iOffset,
			'Limit': AppData.User.MailsPerPage,
			'Search': sSearch,
			'Filters': sFilters,
			'UseThreads': bUseThreads ? '1' : '0'
		},
		bStartRequest = false,
		bDataExpected = false,
		fCallBack = bCurrent ? this.onCurrentMessagesGetListResponse : this.onMessagesGetListResponse,
		aUids = []
	;
	
	if (oFolder.type() === Enums.FolderTypes.Inbox && sFilters === '')
	{
		oParameters['InboxUidnext'] = oFolder.sUidNext;
	}
	
	if (bCacheIsEmpty && oUidList.search() === this.uidList().search() && oUidList.filters() === this.uidList().filters())
	{
		oUidList = this.uidList();
	}
	if (oUidList)
	{
		aUids = this.setMessagesFromUidList(oUidList, iOffset, oFolder.oMessages, bFillMessages);
	}
	
	if (oUidList)
	{
		bDataExpected = 
			(bCacheIsEmpty) ||
			((iOffset + aUids.length < oUidList.resultCount()) && (aUids.length < AppData.User.MailsPerPage))
		;
		bStartRequest = oFolder.hasChanges() || bDataExpected;
	}
	
	if (bStartRequest)
	{
		App.Ajax.send(oParameters, fCallBack, this);
	}
	else
	{
		this.waitForUnseenMessages(false);
	}
	
	return {UidList: oUidList, RequestStarted: bStartRequest, DataExpected: bDataExpected, Folder: oFolder};
};

CMailCache.prototype.executeEmptyTrash = function ()
{
	var oFolder = this.folderList().trashFolder();
	if (oFolder)
	{
		oFolder.emptyFolder();
	}
};

CMailCache.prototype.executeEmptySpam = function ()
{
	var oFolder = this.folderList().spamFolder();
	if (oFolder)
	{
		oFolder.emptyFolder();
	}
};

/**
 * @param {Object} oFolder
 */
CMailCache.prototype.onClearFolder = function (oFolder)
{
	if (oFolder && oFolder.selected())
	{
		this.messages.removeAll();
		this.currentMessage(null);
		var oUidList = (oFolder) ? oFolder.getUidList(this.uidList().search(), this.uidList().filters()) : null;
		if (oUidList)
		{
			this.uidList(oUidList);
		}
		else
		{
			this.uidList(new CUidListModel());
		}
		
		// FoldersGetRelevantInformation-request aborted during folder cleaning, not to get the wrong information.
		// So here indicates that chekmail is over.
		this.checkMailStarted(false);
		this.setAutocheckmailTimer();
	}
};

/**
 * @param {string} sToFolderFullName
 * @param {Array} aUids
 * @param {boolean} bAnimateRecive
 */
CMailCache.prototype.moveMessagesToFolder = function (sToFolderFullName, aUids, bAnimateRecive)
{
	if (aUids.length > 0)
	{
		var
			oCurrFolder = this.folderList().currentFolder(),
			bDraftsFolder = oCurrFolder && oCurrFolder.type() === Enums.FolderTypes.Drafts,
			aOpenedDraftUids = Utils.WindowOpener.getOpenedDraftUids(),
			bTryToDeleteEditedDraft = bDraftsFolder && _.find(aUids, _.bind(function (sUid) {
				return -1 !== Utils.inArray(sUid, aOpenedDraftUids);
			}, this)),
			oToFolder = this.folderList().getFolderByFullName(sToFolderFullName),
			oParameters = {
				'Action': 'MessageMove',
				'Folder': oCurrFolder ? oCurrFolder.fullName() : '',
				'ToFolder': sToFolderFullName,
				'Uids': aUids.join(',')
			},
			oDiffs = null,
			fMoveMessages = _.bind(function () {
				if (this.uidList().filters() === Enums.FolderFilter.Unseen && this.uidList().resultCount() > AppData.User.MailsPerPage)
				{
					this.waitForUnseenMessages(true);
				}
				
				oDiffs = oCurrFolder.markDeletedByUids(aUids);
				oToFolder.addMessagesCountsDiff(oDiffs.MinusDiff, oDiffs.UnseenMinusDiff);

				if (Utils.isUnd(bAnimateRecive) ? true : !!bAnimateRecive)
				{
					oToFolder.recivedAnim(true);
				}

				this.excludeDeletedMessages();

				oToFolder.markHasChanges();
				
				App.Ajax.send(oParameters, this.onMoveMessagesResponse, this);

				if (oToFolder && oToFolder.type() === Enums.FolderTypes.Trash)
				{
					AfterLogicApi.runPluginHook('move-messages-to-trash', 
						[AppData.Accounts.currentId(), oParameters.Folder, aUids]);
				}

				if (oToFolder && oToFolder.type() === Enums.FolderTypes.Spam)
				{
					AfterLogicApi.runPluginHook('move-messages-to-spam', 
						[AppData.Accounts.currentId(), oParameters.Folder, aUids]);
				}
			}, this)
		;

		if (oCurrFolder && oToFolder)
		{
			if (bTryToDeleteEditedDraft)
			{
				this.disableComposeAutosave(true);
				App.Screens.showPopup(ConfirmPopup, [Utils.i18n('MAILBOX/CONFIRM_MESSAGE_FOR_DELETE_IS_EDITED'), 
					_.bind(function (bOk) {
						if (bOk)
						{
							Utils.WindowOpener.closeComposesWithDraftUids(aUids);
							fMoveMessages();
						}
						this.disableComposeAutosave(false);
					}, this), 
					'', Utils.i18n('MAILBOX/BUTTON_CLOSE_DELETE_DRAFT')
				]);
			}
			else
			{
				fMoveMessages();
			}
		}
	}
};

CMailCache.prototype.copyMessagesToFolder = function (sToFolderFullName, aUids, bAnimateRecive)
{
	if (aUids.length > 0)
	{
		var
			oCurrFolder = this.folderList().currentFolder(),
			oToFolder = this.folderList().getFolderByFullName(sToFolderFullName),
			oParameters = {
				'Action': 'MessageCopy',
				'Folder': oCurrFolder ? oCurrFolder.fullName() : '',
				'ToFolder': sToFolderFullName,
				'Uids': aUids.join(',')
			}
		;

		if (oCurrFolder && oToFolder)
		{
			if (Utils.isUnd(bAnimateRecive) ? true : !!bAnimateRecive)
			{
				oToFolder.recivedAnim(true);
			}

			oToFolder.markHasChanges();

			App.Ajax.send(oParameters, this.onCopyMessagesResponse, this);

			if (oToFolder && oToFolder.type() === Enums.FolderTypes.Trash)
			{
				AfterLogicApi.runPluginHook('copy-messages-to-trash',
					[AppData.Accounts.currentId(), oParameters.Folder, aUids]);
			}

			if (oToFolder && oToFolder.type() === Enums.FolderTypes.Spam)
			{
				AfterLogicApi.runPluginHook('copy-messages-to-spam',
					[AppData.Accounts.currentId(), oParameters.Folder, aUids]);
			}
		}
	}
};

CMailCache.prototype.excludeDeletedMessages = function ()
{
	_.delay(_.bind(function () {
		
		var
			oCurrFolder = this.folderList().currentFolder(),
			iOffset = (this.page() - 1) * AppData.User.MailsPerPage
		;
		
		this.setMessagesFromUidList(this.uidList(), iOffset, oCurrFolder.oMessages, true);
		
	}, this), 500);
};

/**
 * @param {number} iAccountID
 * @param {string} sFolderFullName
 * @param {string} sDraftUid
 */
CMailCache.prototype.removeOneMessageFromCacheForFolder = function (iAccountID, sFolderFullName, sDraftUid)
{
	var
		oFolderList = this.oFolderListItems[iAccountID],
		oFolder = oFolderList ? oFolderList.getFolderByFullName(sFolderFullName) : null
	;
	
	if (oFolder && oFolder.type() === Enums.FolderTypes.Drafts)
	{
		oFolder.markDeletedByUids([sDraftUid]);
		oFolder.commitDeleted([sDraftUid]);
	}
};

/**
 * @param {number} iAccountID
 * @param {string} sFolderFullName
 */
CMailCache.prototype.startMessagesLoadingWhenDraftSaving = function (iAccountID, sFolderFullName)
{
	var
		oFolderList = this.oFolderListItems[iAccountID],
		oFolder = oFolderList ? oFolderList.getFolderByFullName(sFolderFullName) : null
	;
	
	if ((oFolder && oFolder.type() === Enums.FolderTypes.Drafts) && oFolder.selected())
	{
		this.messagesLoading(true);
	}
};

/**
 * @param {number} iAccountID
 * @param {string} sFolderFullName
 */
CMailCache.prototype.removeMessagesFromCacheForFolder = function (iAccountID, sFolderFullName)
{
	var
		oFolderList = this.oFolderListItems[iAccountID],
		oFolder = oFolderList ? oFolderList.getFolderByFullName(sFolderFullName) : null,
		sCurrFolderFullName = oFolderList ? oFolderList.currentFolderFullName() : null
	;
	if (oFolder)
	{
		oFolder.markHasChanges();
		if (this.currentAccountId() === iAccountID && sFolderFullName === sCurrFolderFullName)
		{
			this.requestCurrentMessageList(sCurrFolderFullName, this.page(), this.uidList().search(), '', true);
		}
	}
};

/**
 * @param {Array} aUids
 */
CMailCache.prototype.deleteMessages = function (aUids)
{
	var
		oCurrFolder = this.folderList().currentFolder()
	;

	if (oCurrFolder)
	{
		this.deleteMessagesFromFolder(oCurrFolder, aUids);
	}
};

/**
 * @param {Object} oFolder
 * @param {Array} aUids
 */
CMailCache.prototype.deleteMessagesFromFolder = function (oFolder, aUids)
{
	var
		oParameters = {
			'Action': 'MessageDelete',
			'Folder': oFolder.fullName(),
			'Uids': aUids.join(',')
		}
	;

	oFolder.markDeletedByUids(aUids);

	this.excludeDeletedMessages();

	App.Ajax.send(oParameters, this.onMoveMessagesResponse, this);
	
	AfterLogicApi.runPluginHook('delete-messages', 
		[AppData.Accounts.currentId(), oParameters.Folder, aUids]);
};

/**
 * @param {boolean} bAlwaysForSender
 */
CMailCache.prototype.showExternalPictures = function (bAlwaysForSender)
{
	var
		aFrom = [],
		oFolder = null
	;
		
	if (this.currentMessage())
	{
		aFrom = this.currentMessage().oFrom.aCollection;
		oFolder = this.folderList().getFolderByFullName(this.currentMessage().folder());

		if (bAlwaysForSender && aFrom.length > 0)
		{
			oFolder.alwaysShowExternalPicturesForSender(aFrom[0].sEmail);
		}
		else
		{
			oFolder.showExternalPictures(this.currentMessage().uid());
		}
	}
};

/**
 * @param {string|null} sUid
 * @param {string} sFolder
 */
CMailCache.prototype.setCurrentMessage = function (sUid, sFolder)
{
	var
		oCurrFolder = this.folderList().currentFolder(),
		oMessage = oCurrFolder && sUid ? oCurrFolder.oMessages[sUid] : null
	;
	
	if (AppData.SingleMode && (!oCurrFolder || oCurrFolder.fullName() !== sFolder))
	{
		this.folderList().setCurrentFolder(sFolder, '');
		oCurrFolder = this.folderList().currentFolder();
	}
	
	if (oMessage && !oMessage.deleted())
	{
		this.currentMessage(oMessage);
		if (!this.currentMessage().seen())
		{
			this.executeGroupOperation('MessageSetSeen', [this.currentMessage().uid()], 'seen', true);
		}
		oCurrFolder.getCompletelyFilledMessage(sUid, this.onCurrentMessageResponse, this);
	}
	else
	{
		this.currentMessage(null);
		if (AppData.SingleMode && oCurrFolder)
		{
			oCurrFolder.getCompletelyFilledMessage(sUid, this.onCurrentMessageResponse, this);
		}
	}
};

/**
 * @param {Object} oMessage
 * @param {string} sUid
 */
CMailCache.prototype.onCurrentMessageResponse = function (oMessage, sUid)
{
	var sCurrentUid = this.currentMessage() ? this.currentMessage().uid() : '';
	
	if (oMessage === null && sCurrentUid === sUid)
	{
		this.currentMessage(null);
	}
	else if (oMessage && sCurrentUid === sUid)
	{
		this.currentMessage.valueHasMutated();
	}
	else if (AppData.SingleMode && oMessage && this.currentMessage() === null)
	{
		this.currentMessage(oMessage);
	}
};

/**
 * @param {string} sFullName
 * @param {string} sUid
 * @param {Function} fResponseHandler
 * @param {Object} oContext
 */
CMailCache.prototype.getMessage = function (sFullName, sUid, fResponseHandler, oContext)
{
	var
		oFolder = this.folderList().getFolderByFullName(sFullName)
	;
	
	if (oFolder)
	{
		oFolder.getCompletelyFilledMessage(sUid, fResponseHandler, oContext);
	}
};

/**
 * @param {string} sAction
 * @param {Array} aUids
 * @param {string} sField
 * @param {boolean} bSetAction
 */
CMailCache.prototype.executeGroupOperation = function (sAction, aUids, sField, bSetAction)
{
	var
		oCurrFolder = this.folderList().currentFolder(),
		oParameters = {
			'Action': sAction,
			'Folder': oCurrFolder ? oCurrFolder.fullName() : '',
			'Uids': aUids.join(','),
			'SetAction': bSetAction ? 1 : 0
		},
		iOffset = (this.page() - 1) * AppData.User.MailsPerPage,
		iUidsCount = aUids.length,
		iStarredCount = this.folderList().oStarredFolder ? this.folderList().oStarredFolder.messageCount() : 0,
		oStarredUidList = oCurrFolder ? oCurrFolder.getUidList('', Enums.FolderFilter.Flagged) : null
	;

	if (oCurrFolder)
	{
		if (oParameters.Action === 'MessageSetSeen')
		{
			this.iMessageSetSeenCount++;
		}
		App.Ajax.send(oParameters, this.onExecuteGroupOperationResponse, this);

		oCurrFolder.executeGroupOperation(sField, aUids, bSetAction);
		
		if (oCurrFolder.type() === Enums.FolderTypes.Inbox && sField === 'flagged')
		{
			if (this.uidList().filters() === Enums.FolderFilter.Flagged)
			{
				if (!bSetAction)
				{
					this.uidList().deleteUids(aUids);
					if (this.folderList().oStarredFolder)
					{
						this.folderList().oStarredFolder.messageCount(oStarredUidList.resultCount());
					}
				}
			}
			else
			{
				oCurrFolder.removeFlaggedMessageListsFromCache();
				if (this.uidList().search() === '' && this.folderList().oStarredFolder)
				{
					if (bSetAction)
					{
						this.folderList().oStarredFolder.messageCount(iStarredCount + iUidsCount);
					}
					else
					{
						this.folderList().oStarredFolder.messageCount((iStarredCount - iUidsCount > 0) ? iStarredCount - iUidsCount : 0);
					}
				}
			}
		}
			
		if (sField === 'seen')
		{
			oCurrFolder.removeUnseenMessageListsFromCache();
		}
		
		if (this.uidList().filters() !== Enums.FolderFilter.Unseen || this.waitForUnseenMessages())
		{
			this.setMessagesFromUidList(this.uidList(), iOffset, oCurrFolder.oMessages, true);
		}
	}
};

/**
 * private
 */

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CMailCache.prototype.onExecuteGroupOperationResponse = function (oResponse, oRequest)
{
	if (oRequest.Action === 'MessageSetSeen')
	{
		this.iMessageSetSeenCount--;
		if (this.iMessageSetSeenCount < 0)
		{
			this.iMessageSetSeenCount = 0;
		}
		if (this.folderList().currentFolder() && this.iMessageSetSeenCount === 0 && (this.uidList().filters() !== Enums.FolderFilter.Unseen || this.waitForUnseenMessages()))
		{
			this.requestCurrentMessageList(this.folderList().currentFolder().fullName(), this.page(), this.uidList().search(), this.uidList().filters(), false);
		}
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CMailCache.prototype.onFoldersGetListResponse = function (oResponse, oRequest)
{
	var
		oFolderList = new CFolderListModel(),
		iAccountId = parseInt(oResponse.AccountID, 10),
		oFolderListOld = this.oFolderListItems[iAccountId],
		oNamedFolderListOld = oFolderListOld ? oFolderListOld.oNamedCollection : {}
	;		

	if (oResponse.Result === false)
	{
		App.Api.showErrorByCode(oResponse);
		
		if (oRequest.AccountID === this.currentAccountId() && this.messages().length === 0)
		{
			this.messagesLoading(false);
			this.messagesLoadingError(true);
		}
	}
	else
	{
		oFolderList.parse(iAccountId, oResponse.Result, oNamedFolderListOld);
		if (oFolderListOld)
		{
			oFolderList.oStarredFolder.messageCount(oFolderListOld.oStarredFolder.messageCount());
		}
		this.oFolderListItems[iAccountId] = oFolderList;

		setTimeout(_.bind(this.getAllFoldersRelevantInformation, this, iAccountId), 2000);

		if (this.currentAccountId() === iAccountId)
		{
			this.folderList(oFolderList);
		}
		if (this.editedAccountId() === iAccountId)
		{
			this.editedFolderList(oFolderList);
		}
		if (AppData.Accounts.defaultId() === iAccountId)
		{
			this.defaultFolderList(oFolderList);
		}
	}
	
	this.folderListLoading.remove(iAccountId);
};

/**
 * @param {Object} oFolderList
 */
CMailCache.prototype.setCurrentFolderList = function (oFolderList)
{
	var iAccountId = oFolderList.iAccountId;
	
	if (iAccountId === this.currentAccountId() && iAccountId !== this.folderList().iAccountId)
	{
		this.folderList(oFolderList);
	}
};

/**
 * @param {number} iAccountId
 */
CMailCache.prototype.getAllFoldersRelevantInformation = function (iAccountId)
{
	var
		oFolderList = this.oFolderListItems[iAccountId],
		aFolders = oFolderList ? oFolderList.getFoldersWithoutCountInfo() : [],
		oParameters = {
			'Action': 'FoldersGetRelevantInformation',
			'Folders': aFolders,
			'AccountID': iAccountId
		}
	;
	
	if (aFolders.length > 0)
	{
		App.Ajax.send(oParameters, this.onFoldersGetRelevantInformationResponse, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CMailCache.prototype.onFoldersGetRelevantInformationResponse = function (oResponse, oRequest)
{
	var
		bCheckMailStarted = false,
		iAccountId = oResponse.AccountID,
		oFolderList = this.oFolderListItems[iAccountId],
		sCurrentFolderName = this.folderList().currentFolderFullName(),
		bSameAccount = this.currentAccountId() === iAccountId
	;
	
	if (oResponse.Result === false)
	{
		App.Api.showErrorByCode(oResponse);
		if (App.Ajax.hasOpenedRequests('FoldersGetRelevantInformation'))
		{
			bCheckMailStarted = true;
		}
	}
	else
	{
		if (oFolderList)
		{
			_.each(oResponse.Result && oResponse.Result.Counts, function(aData, sFullName) {
				if (_.isArray(aData) && aData.length > 3)
				{
					var
						iCount = aData[0],
						iUnseenCount = aData[1],
						sUidNext = aData[2],
						sHash = aData[3],
						bFolderHasChanges = false,
						bSameFolder = false,
						oFolder = null
					;

					oFolder = oFolderList.getFolderByFullName(sFullName);
					if (oFolder)
					{
						bSameFolder = bSameAccount && oFolder.fullName() === sCurrentFolderName;
						bFolderHasChanges = oFolder.setRelevantInformation(sUidNext, sHash, iCount, iUnseenCount, bSameFolder);
						if (bSameFolder && bFolderHasChanges && this.uidList().filters() !== Enums.FolderFilter.Unseen)
						{
							this.requestCurrentMessageList(oFolder.fullName(), this.page(), this.uidList().search(), this.uidList().filters(), false);
							bCheckMailStarted = true;
						}
					}
				}
			}, this);
			
			oFolderList.countsCompletelyFilled(true);
		}
	}
	
	this.checkMailStarted(bCheckMailStarted);
	if (!this.checkMailStarted())
	{
		this.setAutocheckmailTimer();
	}
};

/**
 * @param {Object} oResponse
 */
CMailCache.prototype.showNotificationsForNewMessages = function (oResponse)
{
	var
		sCurrentFolderName = this.folderList().currentFolderFullName(),
		iNewLength = 0,
		sUid = '',
		oParameters = {}
	;
	
	if (oResponse.Result.New && oResponse.Result.New.length > 0)
	{
		iNewLength = oResponse.Result.New.length;
		sUid = oResponse.Result.New[0].Uid;
		oParameters = {
			action:'show',
			icon: 'skins/wm_logo_140x140.png',
			title: Utils.i18n('NOTIFICATION/NEW_MESSAGE_PLURAL', {
				'COUNT': iNewLength
			}, null, iNewLength),
			timeout: 5000,
			callback: function () {
				window.focus();
				App.Routing.setHash(App.Links.mailbox(sCurrentFolderName, 1, sUid, '', ''));
			}
		};

		if (iNewLength === 1)
		{
			oParameters.body = Utils.i18n('MESSAGE/HEADER_SUBJECT') + ': ' +
				oResponse.Result.New[0].Subject + '\r\n' +
				Utils.i18n('MESSAGE/HEADER_FROM') + ': ' +
				(_.map(oResponse.Result.New[0].From, function(oFrom, iKey)
				{
					return oFrom.DisplayName !== '' ? oFrom.DisplayName : oFrom.Email;
				})).join(', ');
		}

		App.desktopNotify(oParameters);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CMailCache.prototype.onCurrentMessagesGetListResponse = function (oResponse, oRequest)
{
	this.checkMailStarted(false);

	if (!oResponse.Result)
	{
		App.Api.showErrorByCode(oResponse);
		if (this.messagesLoading() === true && (this.messages().length === 0 || oResponse.ErrorCode !== Enums.Errors.NotDisplayedError))
		{
			this.messagesLoadingError(true);
		}
		this.messagesLoading(false);
		this.setAutocheckmailTimer();
	}
	else
	{
		this.messagesLoadingError(false);
		this.parseMessageList(oResponse, oRequest);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CMailCache.prototype.onMessagesGetListResponse = function (oResponse, oRequest)
{
	if (oResponse && oResponse.Result)
	{
		this.parseMessageList(oResponse, oRequest);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CMailCache.prototype.parseMessageList = function (oResponse, oRequest)
{
	var
		oResult = oResponse.Result,
		oFolderList = this.oFolderListItems[oResponse.AccountID],
		oFolder = null,
		oUidList = null,
		bTrustThreadInfo = (oRequest.UseThreads === '1'),
		bHasFolderChanges = false,
		bCurrentFolder = this.currentAccountId() === oResponse.AccountID &&
				this.folderList().currentFolderFullName() === oResult.FolderName,
		bCurrentList = bCurrentFolder &&
				this.uidList().search() === oResult.Search &&
				this.uidList().filters() === oResult.Filters,
		bCurrentPage = this.page() === ((oResult.Offset / AppData.User.MailsPerPage) + 1),
		aNewFolderMessages = []
	;
	
	this.showNotificationsForNewMessages(oResponse);
	
	if (oResult !== false && oResult['@Object'] === 'Collection/MessageCollection')
	{
		oFolder = oFolderList.getFolderByFullName(oResult.FolderName);
		
		// perform before getUidList, because in case of a mismatch the uid list will be pre-cleaned
		oFolder.setRelevantInformation(oResult.UidNext.toString(), oResult.FolderHash, 
			oResult.MessageCount, oResult.MessageUnseenCount, bCurrentFolder && !bCurrentList);
		bHasFolderChanges = oFolder.hasChanges();
		oFolder.removeAllMessageListsFromCacheIfHasChanges();
		
		oUidList = oFolder.getUidList(oResult.Search, oResult.Filters);
		oUidList.setUidsAndCount(oResult);
		_.each(oResult['@Collection'], function (oRawMessage) {
			var oFolderMessage = oFolder.parseAndCacheMessage(oRawMessage, false, bTrustThreadInfo);
			aNewFolderMessages.push(oFolderMessage);
		}, this);
		
		AfterLogicApi.runPluginHook('response-custom-messages', 
			[oResponse.AccountID, oFolder.fullName(), aNewFolderMessages]);

		if (bCurrentList)
		{
			this.uidList(oUidList);
			if (bCurrentPage && (oUidList.filters() !== Enums.FolderFilter.Unseen || this.waitForUnseenMessages()))
			{
				this.setMessagesFromUidList(oUidList, oResult.Offset, oFolder.oMessages, true);
				this.messagesLoading(false);
				this.waitForUnseenMessages(false);
				this.setAutocheckmailTimer();
			}
		}
		
		if (bHasFolderChanges && bCurrentFolder && (!bCurrentList || !bCurrentPage) && this.uidList().filters() !== Enums.FolderFilter.Unseen)
		{
			this.requestCurrentMessageList(this.folderList().currentFolderFullName(), this.page(), this.uidList().search(), this.uidList().filters(), false);
		}
		
		if (oFolder.type() === Enums.FolderTypes.Inbox && oUidList.filters() === Enums.FolderFilter.Flagged &&
			oUidList.search() === '' && this.folderList().oStarredFolder)
		{
			this.folderList().oStarredFolder.messageCount(oUidList.resultCount());
			this.folderList().oStarredFolder.hasExtendedInfo(true);
		}
	}
};

CMailCache.prototype.increaseStarredCount = function ()
{
	if (this.folderList().oStarredFolder)
	{
		this.folderList().oStarredFolder.increaseCountIfHasNotInfo();
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CMailCache.prototype.onMoveMessagesResponse = function (oResponse, oRequest)
{
	var
		oResult = oResponse.Result,
		oFolder = this.folderList().getFolderByFullName(oRequest.Folder),
		oToFolder = this.folderList().getFolderByFullName(oRequest.ToFolder),
		bToFolderTrash = (oToFolder && (oToFolder.type() === Enums.FolderTypes.Trash)),
		bToFolderSpam = (oToFolder && (oToFolder.type() === Enums.FolderTypes.Spam)),
		oDiffs = null,
		sConfirm = bToFolderTrash ? Utils.i18n('MAILBOX/CONFIRM_MESSAGES_DELETE_WITHOUT_TRASH') :
			Utils.i18n('MAILBOX/CONFIRM_MESSAGES_MARK_SPAM_WITHOUT_SPAM'),
		fDeleteMessages = _.bind(function (bResult) {
			if (bResult && oFolder)
			{
				this.deleteMessagesFromFolder(oFolder, oRequest.Uids.split(','));
			}
		}, this),
		oCurrFolder = this.folderList().currentFolder(),
		sCurrFolderFullName = oCurrFolder.fullName(),
		bFillMessages = false
	;
	
	if (oResult === false)
	{
		oDiffs = oFolder.revertDeleted(oRequest.Uids.split(','));
		if (oToFolder)
		{
			oToFolder.addMessagesCountsDiff(-oDiffs.PlusDiff, -oDiffs.UnseenPlusDiff);
			if (oResponse.ErrorCode === Enums.Errors.ImapQuota && (bToFolderTrash || bToFolderSpam))
			{
				App.Screens.showPopup(ConfirmPopup, [sConfirm, fDeleteMessages]);
			}
			else
			{
				App.Api.showErrorByCode(oResponse, Utils.i18n('MAILBOX/ERROR_MOVING_MESSAGES'));
			}
		}
		else
		{
			App.Api.showErrorByCode(oResponse, Utils.i18n('MAILBOX/ERROR_DELETING_MESSAGES'));
		}
		bFillMessages = true;
	}
	else
	{
		oFolder.commitDeleted(oRequest.Uids.split(','));
	}
	
	if (sCurrFolderFullName === oFolder.fullName() || oToFolder && sCurrFolderFullName === oToFolder.fullName())
	{
		oCurrFolder.markHasChanges();
		switch (this.uidList().filters())
		{
			case Enums.FolderFilter.Flagged:
				break;
			case Enums.FolderFilter.Unseen:
				if (this.waitForUnseenMessages())
				{
					this.requestCurrentMessageList(sCurrFolderFullName, this.page(), this.uidList().search(), this.uidList().filters(), bFillMessages);
				}
				break;
			default:
				this.requestCurrentMessageList(sCurrFolderFullName, this.page(), this.uidList().search(), this.uidList().filters(), bFillMessages);
				break;
		}
	}
	else if (sCurrFolderFullName !== oFolder.fullName())
	{
		App.Prefetcher.startFolderPrefetch(oFolder);
	}
	else if (oToFolder && sCurrFolderFullName !== oToFolder.fullName())
	{
		App.Prefetcher.startFolderPrefetch(oToFolder);
	}
};

CMailCache.prototype.onCopyMessagesResponse = function (oResponse, oRequest)
{
	var
		oResult = oResponse.Result,
		oFolder = this.folderList().getFolderByFullName(oRequest.Folder),
		oToFolder = this.folderList().getFolderByFullName(oRequest.ToFolder),
		oCurrFolder = this.folderList().currentFolder(),
		sCurrFolderFullName = oCurrFolder.fullName()
	;

	if (oResult === false)
	{
		App.Api.showErrorByCode(oResponse, Utils.i18n('MAILBOX/ERROR_COPYING_MESSAGES'));
	}

	if (sCurrFolderFullName === oFolder.fullName() || oToFolder && sCurrFolderFullName === oToFolder.fullName())
	{
		oCurrFolder.markHasChanges();
		this.requestCurrentMessageList(sCurrFolderFullName, this.page(), this.uidList().search(), '', false);
	}
	else if (sCurrFolderFullName !== oFolder.fullName())
	{
		App.Prefetcher.startFolderPrefetch(oFolder);
	}
	else if (oToFolder && sCurrFolderFullName !== oToFolder.fullName())
	{
		App.Prefetcher.startFolderPrefetch(oToFolder);
	}
};

/**
 * @param {string} sSearch
 */
CMailCache.prototype.searchMessagesInCurrentFolder = function (sSearch)
{
	var
		sFolder = this.folderList().currentFolderFullName() || 'INBOX',
		sUid = this.currentMessage() ? this.currentMessage().uid() : '',
		sFilters = this.uidList().filters()
	;
	
	App.Routing.setHash(App.Links.mailbox(sFolder, 1, sUid, sSearch, sFilters));
};

/**
 * @param {string} sSearch
 */
CMailCache.prototype.searchMessagesInInbox = function (sSearch)
{
	App.Routing.setHash(App.Links.mailbox(this.folderList().inboxFolderFullName() || 'INBOX', 1, '', sSearch, ''));
};

CMailCache.prototype.countMessages = function (oCountedFolder)
{
	var aSubfoldersMessagesCount = [],
		fCountRecursively = function(oFolder)
		{

			_.each(oFolder.subfolders(), function(oSubFolder, iKey) {
				if(oSubFolder.subscribed())
				{
					aSubfoldersMessagesCount.push(oSubFolder.unseenMessageCount());
					if (oSubFolder.subfolders().length && oSubFolder.subscribed())
					{
						fCountRecursively(oSubFolder);
					}
				}
			}, this);
		}
	;

	if (oCountedFolder.expanded() || oCountedFolder.bNamespace)
	{
		oCountedFolder.subfoldersMessagesCount(0);
	}
	else
	{
		fCountRecursively(oCountedFolder);
		oCountedFolder.subfoldersMessagesCount(
			_.reduce(aSubfoldersMessagesCount, function(memo, num){ return memo + num; }, 0)
		);
	}

};

CMailCache.prototype.changeDatesInMessages = function () {
	_.each(this.oFolderListItems, function (oFolderList) {
		_.each(oFolderList.oNamedCollection, function (oFolder) {
			_.each(oFolder.oMessages, function (oMessage) {
				oMessage.updateMomentDate();
			}, this);
		});
	});
};
