'use strict';

var
	Api = require('core/js/Api.js'),
	Routing = require('core/js/Routing.js'),
	
	MailUtils = require('modules/Mail/js/utils/Mail.js'),
	MailCache = require('modules/Mail/js/Cache.js'),
	Prefetcher = require('modules/Mail/js/Prefetcher.js'),
	
	aComposedMessages = [],
	aReplyData = []
;

var BaseTabMethods = {
	showReport: function (sText) {
		Api.showReport(sText);
	},
	
	getFolderListItems: function () {
		return MailCache.oFolderListItems;
	},
	getUidList: function () {
		return MailCache.uidList;
	},
	getAccounts: function () {},
	prefetchNextPage: function (sCurrentUid) {
		Prefetcher.prefetchNextPage(sCurrentUid);
	},
	prefetchPrevPage: function (sCurrentUid) {
		Prefetcher.prefetchPrevPage(sCurrentUid);
	},
	getComposedMessageAccountId: function (sWindowName) {
		var oComposedMessage = aComposedMessages[sWindowName];
		return oComposedMessage ? oComposedMessage.accountId : 0;
	},
	getComposedMessage: function (sWindowName) {
		var oComposedMessage = aComposedMessages[sWindowName];
		delete aComposedMessages[sWindowName];
		return oComposedMessage;
	},
	removeOneMessageFromCacheForFolder: function (iAccountId, sDraftFolder, sDraftUid) {
		MailCache.removeOneMessageFromCacheForFolder(iAccountId, sDraftFolder, sDraftUid);
	},
	replaceHashWithoutMessageUid: function (sDraftUid) {
		Routing.replaceHashWithoutMessageUid(sDraftUid);
	},
	startMessagesLoadingWhenDraftSaving: function (iAccountId, sDraftFolder) {
		MailCache.startMessagesLoadingWhenDraftSaving(iAccountId, sDraftFolder);
	},
	removeMessagesFromCacheForFolder: function (iAccountID, sSentFolder) {
		MailCache.removeMessagesFromCacheForFolder(iAccountID, sSentFolder);
	},
	searchMessagesInCurrentFolder: function (sSearch) {
		MailCache.searchMessagesInCurrentFolder(sSearch);
	},
	getReplyData: function (sUniq) {
		var oReplyData = aReplyData[sUniq];
		delete aReplyData[sUniq];
		return oReplyData;
	},
	deleteMessage: function (sUid, fAfterDelete) {
		MailUtils.deleteMessages([sUid], fAfterDelete);
	}
};

window.BaseTabMethods = BaseTabMethods;

module.exports = {
	passReplyData: function (sUniq, oReplyData) {
		aReplyData[sUniq] = oReplyData;
	},
	passComposedMessage: function (sWinName, oComposedMessage) {
		aComposedMessages[sWinName] = oComposedMessage;
	}
};