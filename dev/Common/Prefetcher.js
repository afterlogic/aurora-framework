
/**
 * @constructor
 */
function CPrefetcher()
{
	this.prefetchStarted = ko.observable(false);
	this.serverInitializationsDone = ko.observable(false);
	this.helpdeskInitialized = ko.observable(false);
	this.fetchersIdentitiesPrefetched = ko.observable(false);
	
	this.init();
}

CPrefetcher.prototype.init = function ()
{
	setInterval(_.bind(function () {
		this.start();
	}, this), 10000);
};

CPrefetcher.prototype.start = function ()
{
	if (AppData.Auth && !AppData.SingleMode && !App.InternetConnectionError && !App.Ajax.hasOpenedRequests())
	{
		this.prefetchStarted(false);
		this.prefetchAll();
	}
};

CPrefetcher.prototype.prefetchAll = function ()
{
	this.prefetchFetchersIdentities();
	
	if (AppData.App.AllowPrefetch)
	{
		this.startMessagesPrefetch();

		this.startThreadListPrefetch();
		
		this.doServerInitializations();
		
		this.prefetchStarredMessageList();
		
		this.startOtherPagesPrefetch();

		this.prefetchUnseenMessageList();

		this.prefetchAccountQuota();

		this.startOtherFoldersPrefetch();

		this.prefetchCalendarList();

		this.initHelpdesk();
	}
	else
	{
		this.doServerInitializations();
		
		this.prefetchStarredMessageList();

		this.prefetchAccountQuota();

		this.prefetchCalendarList();

		this.initHelpdesk();
	}
};

CPrefetcher.prototype.prefetchCalendarList = function ()
{
	if (!this.prefetchStarted())
	{
		this.prefetchStarted(App.CalendarCache.firstRequestCalendarList());
	}
};

CPrefetcher.prototype.prefetchFetchersIdentities = function ()
{
	if (!AppData.SingleMode && !this.fetchersIdentitiesPrefetched() && !this.prefetchStarted() && (AppData.User.AllowFetcher || AppData.AllowIdentities))
	{
		AppData.Accounts.populateFetchersIdentities();
		this.fetchersIdentitiesPrefetched(true);
		this.prefetchStarted(true);
	}	
};

CPrefetcher.prototype.initHelpdesk = function ()
{
	if (AppData.User.IsHelpdeskSupported && !this.prefetchStarted() && !this.helpdeskInitialized())
	{
		App.Screens.initHelpdesk();
		this.helpdeskInitialized(true);
		this.prefetchStarted(true);
	}
};

CPrefetcher.prototype.doServerInitializations = function ()
{
	if (!AppData.SingleMode && !this.prefetchStarted() && !this.serverInitializationsDone())
	{
		App.Ajax.send({'Action': 'SystemDoServerInitializations'});
		this.serverInitializationsDone(true);
		this.prefetchStarted(true);
	}
};

CPrefetcher.prototype.prefetchStarredMessageList = function ()
{
	if (!this.prefetchStarted())
	{
		var
			oFolderList = App.MailCache.folderList(),
			oInbox = oFolderList ? oFolderList.inboxFolder() : null,
			oRes = null
		;
		
		if (oInbox && !oInbox.hasChanges())
		{
			oRes = App.MailCache.requestMessageList(oInbox.fullName(), 1, '', Enums.FolderFilter.Flagged, false, false);
			if (oRes.RequestStarted)
			{
				this.prefetchStarted(true);
			}
		}
	}
};

CPrefetcher.prototype.prefetchUnseenMessageList = function ()
{
	if (!this.prefetchStarted())
	{
		var
			oFolderList = App.MailCache.folderList(),
			oInbox = oFolderList ? oFolderList.inboxFolder() : null,
			oRes = null
		;
		
		if (oInbox && !oInbox.hasChanges())
		{
			oRes = App.MailCache.requestMessageList(oInbox.fullName(), 1, '', Enums.FolderFilter.Unseen, false, false);
			if (oRes.RequestStarted)
			{
				this.prefetchStarted(true);
			}
		}
	}
};

CPrefetcher.prototype.startOtherPagesPrefetch = function ()
{
	if (!this.prefetchStarted())
	{
		this.startPagePrefetch(App.MailCache.page() + 1);
	}
	
	if (!this.prefetchStarted())
	{
		this.startPagePrefetch(App.MailCache.page() - 1);
	}
};

/**
 * @param {string} sCurrentUid
 */
CPrefetcher.prototype.prefetchNextPage = function (sCurrentUid)
{
	var
		oUidList = App.MailCache.uidList(),
		iIndex = _.indexOf(oUidList.collection(), sCurrentUid),
		iPage = Math.ceil(iIndex/AppData.User.MailsPerPage) + 1
	;
	this.startPagePrefetch(iPage - 1);
};

/**
 * @param {string} sCurrentUid
 */
CPrefetcher.prototype.prefetchPrevPage = function (sCurrentUid)
{
	var
		oUidList = App.MailCache.uidList(),
		iIndex = _.indexOf(oUidList.collection(), sCurrentUid),
		iPage = Math.ceil((iIndex + 1)/AppData.User.MailsPerPage) + 1
	;
	this.startPagePrefetch(iPage);
};

/**
 * @param {number} iPage
 */
CPrefetcher.prototype.startPagePrefetch = function (iPage)
{
	var
		oCurrFolder = App.MailCache.folderList().currentFolder(),
		oUidList = App.MailCache.uidList(),
		iOffset = (iPage - 1) * AppData.User.MailsPerPage,
		bPageExists = iPage > 0 && iOffset < oUidList.resultCount(),
		oParams = null,
		oRequestData = null
	;
	
	if (oCurrFolder && !oCurrFolder.hasChanges() && bPageExists)
	{
		oParams = {
			folder: oCurrFolder.fullName(),
			page: iPage,
			search: oUidList.search()
		};
		
		if (!oCurrFolder.hasListBeenRequested(oParams))
		{
			oRequestData = App.MailCache.requestMessageList(oParams.folder, oParams.page, oParams.search, '', false, false);

			if (oRequestData && oRequestData.RequestStarted)
			{
				this.prefetchStarted(true);
			}
		}
	}
};

CPrefetcher.prototype.startOtherFoldersPrefetch = function ()
{
	if (!this.prefetchStarted())
	{
		var
			oFolderList = App.MailCache.folderList(),
			sCurrFolder = oFolderList.currentFolderFullName(),
			aFoldersFromAccount = AppData.Accounts.getCurrentFetchersAndFiltersFolderNames(),
			aSystemFolders = oFolderList ? [oFolderList.inboxFolderFullName(), oFolderList.sentFolderFullName(), oFolderList.draftsFolderFullName(), oFolderList.spamFolderFullName()] : [],
			aOtherFolders = (aFoldersFromAccount.length < 3) ? this.getOtherFolderNames(3 - aFoldersFromAccount.length) : [],
			aFolders = _.uniq(_.compact(_.union(aSystemFolders, aFoldersFromAccount, aOtherFolders)))
		;
		
		_.each(aFolders, _.bind(function (sFolder) {
			if (sCurrFolder !== sFolder)
			{
				this.startFolderPrefetch(oFolderList.getFolderByFullName(sFolder));
			}
		}, this));
	}
};

/**
 * @param {number} iCount
 * @returns {Array}
 */
CPrefetcher.prototype.getOtherFolderNames = function (iCount)
{
	var
		oInbox = App.MailCache.folderList().inboxFolder(),
		aInboxSubFolders = oInbox ? oInbox.subfolders() : [],
		aOtherFolders = _.filter(App.MailCache.folderList().collection(), function (oFolder) {
			return !oFolder.isSystem();
		}, this),
		aFolders = _.first(_.union(aInboxSubFolders, aOtherFolders), iCount)
	;
	
	return _.map(aFolders, function (oFolder) {
		return oFolder.fullName();
	});
};

/**
 * @param {Object} oFolder
 */
CPrefetcher.prototype.startFolderPrefetch = function (oFolder)
{
	if (!this.prefetchStarted() && oFolder)
	{
		var
			iPage = 1,
			sSearch = '',
			oParams = {
				folder: oFolder.fullName(),
				page: iPage,
				search: sSearch
			},
			oRequestData = null
		;

		if (!oFolder.hasListBeenRequested(oParams))
		{
			oRequestData = App.MailCache.requestMessageList(oParams.folder, oParams.page, oParams.search, '', false, false);

			if (oRequestData && oRequestData.RequestStarted)
			{
				this.prefetchStarted(true);
			}
		}
	}
};

CPrefetcher.prototype.startThreadListPrefetch = function ()
{
	if (!this.prefetchStarted())
	{
		var
			aUidsForLoad = [],
			oCurrFolder = App.MailCache.getCurrentFolder()
		;

		_.each(App.MailCache.messages(), function (oCacheMess) {
			if (oCacheMess.threadCount() > 0)
			{
				_.each(oCacheMess.threadUids(), function (sThreadUid) {
					var oThreadMess = oCurrFolder.oMessages[sThreadUid];
					if (!oThreadMess || !oCurrFolder.hasThreadUidBeenRequested(sThreadUid))
					{
						aUidsForLoad.push(sThreadUid);
					}
				});
			}
		}, this);

		if (aUidsForLoad.length > 0)
		{
			aUidsForLoad = aUidsForLoad.slice(0, AppData.User.MailsPerPage);
			oCurrFolder.addRequestedThreadUids(aUidsForLoad);
			oCurrFolder.loadThreadMessages(aUidsForLoad);
			this.prefetchStarted(true);
		}
	}
};

CPrefetcher.prototype.startMessagesPrefetch = function ()
{
	if (!this.prefetchStarted())
	{
		var
			iAccountId = App.MailCache.currentAccountId(),
			oCurrFolder = App.MailCache.getCurrentFolder(),
			iTotalSize = 0,
			iMaxSize = AppData.App.MaxPrefetchBodiesSize,
			aUids = [],
			oParameters = null,
			iJsonSizeOf1Message = 2048,
			fFillUids = function (oMsg) {
				var
					bNotFilled = (!oMsg.deleted() && !oMsg.completelyFilled()),
					bUidNotAdded = !_.find(aUids, function (sUid) {
						return sUid === oMsg.uid();
					}, this),
					bHasNotBeenRequested = !oCurrFolder.hasUidBeenRequested(oMsg.uid())
				;

				if (iTotalSize < iMaxSize && bNotFilled && bUidNotAdded && bHasNotBeenRequested)
				{
					aUids.push(oMsg.uid());
					iTotalSize += oMsg.trimmedTextSize() + iJsonSizeOf1Message;
				}
			}
		;

		if (oCurrFolder && oCurrFolder.selected())
		{
			_.each(App.MailCache.messages(), fFillUids);
			_.each(oCurrFolder.oMessages, fFillUids);

			if (aUids.length > 0)
			{
				oCurrFolder.addRequestedUids(aUids);

				oParameters = {
					'AccountID': iAccountId,
					'Action': 'MessagesGetBodies',
					'Folder': oCurrFolder.fullName(),
					'Uids': aUids
				};

				App.Ajax.send(oParameters, this.onMessagesGetBodiesResponse, this);
				this.prefetchStarted(true);
			}
		}
	}
};

/**
 * @param {Object} oData
 * @param {Object} oParameters
 */
CPrefetcher.prototype.onMessagesGetBodiesResponse = function (oData, oParameters)
{
	var
		oFolder = App.MailCache.getFolderByFullName(oParameters.AccountID, oParameters.Folder)
	;
	
	if (_.isArray(oData.Result))
	{
		_.each(oData.Result, function (oRawMessage) {
			oFolder.parseAndCacheMessage(oRawMessage, false, false);
		});
	}
};

CPrefetcher.prototype.prefetchAccountQuota = function ()
{
	var
		oAccount = AppData.Accounts.getCurrent(),
		bShowQuotaBar = AppData.App && AppData.App.ShowQuotaBar,
		bNeedQuotaRequest = oAccount && !oAccount.quotaRecieved()
	;
	
	if (!this.prefetchStarted() && bShowQuotaBar && bNeedQuotaRequest)
	{
		oAccount.updateQuotaParams();
		this.prefetchStarted(true);
	}
};