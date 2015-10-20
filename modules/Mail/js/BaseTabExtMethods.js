'use strict';

var
	Api = require('core/js/Api.js'),
	Routing = require('core/js/Routing.js'),
	
	MailUtils = require('modules/Mail/js/utils/Mail.js'),
	MailCache = require('modules/Mail/js/Cache.js'),
	Prefetcher = require('modules/Mail/js/Prefetcher.js'),
	
	aMessagesParametersFromCompose = [],
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
	getComposedMessage: function (sWindowName) {
		return aMessagesParametersFromCompose[sWindowName];
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
	searchMessagesInCurrentFolder: function () {
		MailCache.searchMessagesInCurrentFolder();
		window.focus();
	},
	getReplyData: function (sUniq) {
		var oReplyData = aReplyData[sUniq];
		delete aReplyData[sUniq];
		return oReplyData;
	},
	deleteMessage: function (sUid) {
		MailUtils.deleteMessages([sUid], function () {window.close();});
	}
};

window.BaseTabMethods = BaseTabMethods;

module.exports = {
	setReplyData: function (sUniq, oReplyData) {
		aReplyData[sUniq] = oReplyData;
	}
	
};