'use strict';

var
	_ = require('underscore'),
	
	Ajax = require('core/js/Ajax.js'),
	
	Accounts = require('modules/Mail/js/AccountList.js'),
	Settings = require('modules/Mail/js/Settings.js'),
	MailCache = require('modules/Mail/js/Cache.js'),
	
	bSingleMode = false,
	
	Prefetcher = {},
	bFetchersIdentitiesPrefetched = false
;

Prefetcher.prefetchFetchersIdentities = function ()
{
	if (!bSingleMode && !bFetchersIdentitiesPrefetched && (Settings.AllowFetcher || Settings.AllowIdentities))
	{
		Accounts.populateFetchersIdentities();
		bFetchersIdentitiesPrefetched = true;
		
		return true;
	}
	return false;
};

Prefetcher.prefetchStarredMessageList = function ()
{
	var
		oFolderList = MailCache.folderList(),
		oInbox = oFolderList ? oFolderList.inboxFolder() : null,
		oRes = null
	;

	if (oInbox && !oInbox.hasChanges())
	{
		oRes = MailCache.requestMessageList(oInbox.fullName(), 1, '', Enums.FolderFilter.Flagged, false, false);
	}

	return oRes && oRes.RequestStarted;
};

Prefetcher.prefetchUnseenMessageList = function ()
{
	var
		oFolderList = MailCache.folderList(),
		oInbox = oFolderList ? oFolderList.inboxFolder() : null,
		oRes = null
	;

	if (oInbox && !oInbox.hasChanges())
	{
		oRes = MailCache.requestMessageList(oInbox.fullName(), 1, '', Enums.FolderFilter.Unseen, false, false);
	}

	return oRes && oRes.RequestStarted;
};

/**
 * @param {string} sCurrentUid
 */
Prefetcher.prefetchNextPage = function (sCurrentUid)
{
	var
		oUidList = MailCache.uidList(),
		iIndex = _.indexOf(oUidList.collection(), sCurrentUid),
		iPage = Math.ceil(iIndex/Settings.MailsPerPage) + 1
	;
	this.startPagePrefetch(iPage - 1);
};

/**
 * @param {string} sCurrentUid
 */
Prefetcher.prefetchPrevPage = function (sCurrentUid)
{
	var
		oUidList = MailCache.uidList(),
		iIndex = _.indexOf(oUidList.collection(), sCurrentUid),
		iPage = Math.ceil((iIndex + 1)/Settings.MailsPerPage) + 1
	;
	this.startPagePrefetch(iPage);
};

/**
 * @param {number} iPage
 */
Prefetcher.startPagePrefetch = function (iPage)
{
	var
		oCurrFolder = MailCache.folderList().currentFolder(),
		oUidList = MailCache.uidList(),
		iOffset = (iPage - 1) * Settings.MailsPerPage,
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
			oRequestData = MailCache.requestMessageList(oParams.folder, oParams.page, oParams.search, '', false, false);
		}
	}
	
	return oRequestData && oRequestData.RequestStarted;
};

Prefetcher.startOtherFoldersPrefetch = function ()
{
	var
		oFolderList = MailCache.folderList(),
		sCurrFolder = oFolderList.currentFolderFullName(),
		aFoldersFromAccount = Accounts.getCurrentFetchersAndFiltersFolderNames(),
		aSystemFolders = oFolderList ? [oFolderList.inboxFolderFullName(), oFolderList.sentFolderFullName(), oFolderList.draftsFolderFullName(), oFolderList.spamFolderFullName()] : [],
		aOtherFolders = (aFoldersFromAccount.length < 3) ? this.getOtherFolderNames(3 - aFoldersFromAccount.length) : [],
		aFolders = _.uniq(_.compact(_.union(aSystemFolders, aFoldersFromAccount, aOtherFolders))),
		bPrefetchStarted = false
	;

	_.each(aFolders, _.bind(function (sFolder) {
		if (!bPrefetchStarted && sCurrFolder !== sFolder)
		{
			bPrefetchStarted = this.startFolderPrefetch(oFolderList.getFolderByFullName(sFolder));
		}
	}, this));

	return bPrefetchStarted;
};

/**
 * @param {number} iCount
 * @returns {Array}
 */
Prefetcher.getOtherFolderNames = function (iCount)
{
	var
		oInbox = MailCache.folderList().inboxFolder(),
		aInboxSubFolders = oInbox ? oInbox.subfolders() : [],
		aOtherFolders = _.filter(MailCache.folderList().collection(), function (oFolder) {
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
Prefetcher.startFolderPrefetch = function (oFolder)
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
		oRequestData = MailCache.requestMessageList(oParams.folder, oParams.page, oParams.search, '', false, false);
	}

	return oRequestData && oRequestData.RequestStarted;
};

Prefetcher.startThreadListPrefetch = function ()
{
	var
		aUidsForLoad = [],
		oCurrFolder = MailCache.getCurrentFolder()
	;

	_.each(MailCache.messages(), function (oCacheMess) {
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
		aUidsForLoad = aUidsForLoad.slice(0, Settings.MailsPerPage);
		oCurrFolder.addRequestedThreadUids(aUidsForLoad);
		oCurrFolder.loadThreadMessages(aUidsForLoad);
		return true;
	}

	return false;
};

Prefetcher.startMessagesPrefetch = function ()
{
	var
		iAccountId = MailCache.currentAccountId(),
		oCurrFolder = MailCache.getCurrentFolder(),
		iTotalSize = 0,
		iMaxSize = Settings.MaxPrefetchBodiesSize,
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
		_.each(MailCache.messages(), fFillUids);
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

			Ajax.send(oParameters, this.onMessagesGetBodiesResponse, this);
			return true;
		}
	}

	return false;
};

/**
 * @param {Object} oData
 * @param {Object} oParameters
 */
Prefetcher.onMessagesGetBodiesResponse = function (oData, oParameters)
{
	var
		oFolder = MailCache.getFolderByFullName(oParameters.AccountID, oParameters.Folder)
	;
	
	if (_.isArray(oData.Result))
	{
		_.each(oData.Result, function (oRawMessage) {
			oFolder.parseAndCacheMessage(oRawMessage, false, false);
		});
	}
};

Prefetcher.prefetchAccountQuota = function ()
{
	var
		oAccount = Accounts.getCurrent(),
		bShowQuotaBar = Settings.ShowQuotaBar,
		bNeedQuotaRequest = oAccount && !oAccount.quotaRecieved()
	;
	
	if (bShowQuotaBar && bNeedQuotaRequest)
	{
		oAccount.updateQuotaParams();
		return true;
	}
	
	return false;
};

module.exports = {
	startMin: function () {
		var bPrefetchStarted = false;
		
		bPrefetchStarted = Prefetcher.prefetchFetchersIdentities();
		
		if (!bPrefetchStarted)
		{
			bPrefetchStarted = Prefetcher.prefetchStarredMessageList();
		}

		if (!bPrefetchStarted)
		{
			bPrefetchStarted = Prefetcher.prefetchAccountQuota();
		}
		
		return bPrefetchStarted;
	},
	startAll: function () {
		var bPrefetchStarted = false;
		
		bPrefetchStarted = Prefetcher.prefetchFetchersIdentities();
		
		if (!bPrefetchStarted)
		{
			bPrefetchStarted = Prefetcher.startMessagesPrefetch();
		}

		if (!bPrefetchStarted)
		{
			bPrefetchStarted = Prefetcher.startThreadListPrefetch();
		}

		if (!bPrefetchStarted)
		{
			bPrefetchStarted = Prefetcher.prefetchStarredMessageList();
		}

		if (!bPrefetchStarted)
		{
			bPrefetchStarted = Prefetcher.startPagePrefetch(MailCache.page() + 1);
		}

		if (!bPrefetchStarted)
		{
			bPrefetchStarted = Prefetcher.startPagePrefetch(MailCache.page() - 1);
		}

		if (!bPrefetchStarted)
		{
			bPrefetchStarted = Prefetcher.prefetchUnseenMessageList();
		}

		if (!bPrefetchStarted)
		{
			bPrefetchStarted = Prefetcher.prefetchAccountQuota();
		}

		if (!bPrefetchStarted)
		{
			bPrefetchStarted = Prefetcher.startOtherFoldersPrefetch();
		}
		
		return bPrefetchStarted;
	}
};
