'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	TextUtils = require('modules/Core/js/utils/Text.js'),
	Utils = require('modules/Core/js/utils/Common.js'),
	
	App = require('modules/Core/js/App.js'),
	
	CAbstractScreenView = require('modules/Core/js/views/CAbstractScreenView.js'),
	
	Ajax = require('modules/SimpleChatClient/js/Ajax.js')
;

/**
 * @constructor
 */
function CSimpleChatView()
{
	CAbstractScreenView.call(this);
	
	this.browserTitle = ko.observable(TextUtils.i18n('SIMPLECHAT/HEADING_BROWSER_TAB'));
	
	this.bAllowReply = (App.getUserRole() === Enums.UserRole.PowerUser);
	
	this.page = ko.observable(1);
	this.posts = ko.observableArray([]);
	this.hasMore = ko.observable(false);
	
	// Quick Reply Part
	
	this.domQuickReply = ko.observable(null);
	this.replyText = ko.observable('');
	this.replyTextFocus = ko.observable(false);
	this.replySendingStarted = ko.observable(false);
	this.replySavingStarted = ko.observable(false);
	this.replyAutoSavingStarted = ko.observable(false);
	
	this.isQuickReplyActive = ko.computed(function () {
		return this.replyText().length > 0 || this.replyTextFocus();
	}, this);
	this.replyLoadingText = ko.computed(function () {
		if (this.replySendingStarted())
		{
			return TextUtils.i18n('CORE/INFO_SENDING');
		}
		else if (this.replySavingStarted())
		{
			return TextUtils.i18n('MAIL/INFO_SAVING');
		}
		return '';
	}, this);
	
	this.sendQuickReplyCommand = Utils.createCommand(this, this.executeSendQuickReply);
	
	this.saveButtonText = ko.computed(function () {
		return this.replyAutoSavingStarted() ? TextUtils.i18n('MAIL/ACTION_SAVE_IN_PROGRESS') : TextUtils.i18n('MAIL/ACTION_SAVE');
	}, this);
}

_.extendOwn(CSimpleChatView.prototype, CAbstractScreenView.prototype);

CSimpleChatView.prototype.ViewTemplate = 'SimpleChatClient_MainView';

CSimpleChatView.prototype.onShow = function ()
{
	this.getMessages(1);
};

CSimpleChatView.prototype.showMore = function ()
{
	this.page(this.page() + 1);
	this.getMessages(this.page());
};

CSimpleChatView.prototype.getMessages = function (iPage)
{
	Ajax.send('GetMessages', {Page: 1, PerPage: 0}, this.onGetMessagesResponse, this);
};

CSimpleChatView.prototype.onGetMessagesResponse = function (oResponse, oRequest)
{
	if (oResponse.Result && _.isArray(oResponse.Result.Collection))
	{
		var
			iCount = oResponse.Result.Count,
			aMessages = oResponse.Result.Collection,
			oParameters = JSON.parse(oRequest.Parameters),
			bHasMore = oParameters.PerPage > 0 && oParameters.Page * oParameters.PerPage < iCount
//			aNewMessages = [],
//			aPosts = this.posts()
		;
		console.log(oParameters.Page * oParameters.PerPage < iCount, oParameters.Page, oParameters.PerPage, iCount);
		this.hasMore(bHasMore);
//		console.log('oParameters.Page', oParameters.Page);
//		if (oParameters.Page !== 1)
//		{
//			console.log('aMessages', aMessages);
//			console.log('aPosts', aPosts);
//		}
//		_.each(aMessages, function (oMessage) {
//			var oFoundMessage = _.find(aPosts, function (oMsg) {
////				if (oParameters.Page !== 1)
////				{
////					console.log(oMsg.name === oMessage.name && oMsg.text === oMessage.text, oMsg.name, oMessage.name, oMsg.text, oMessage.text);
////				}
//				return oMsg.name === oMessage.name && oMsg.text === oMessage.text;
//			});
//			if (oParameters.Page !== 1)
//			{
//				console.log('oFoundMessage', oFoundMessage, 'oMessage', oMessage);
//			}
//			if (!oFoundMessage)
//			{
//				aNewMessages.push(oMessage);
//			}
//		});
//		if (oParameters.Page === 1)
//		{
//			aMessages = _.union(aNewMessages, this.posts());
//		}
//		else
//		{
//			aMessages = _.union(this.posts(), aNewMessages);
//		}
		this.posts(aMessages);
	}
	this.setTimer();
};

CSimpleChatView.prototype.setTimer = function ()
{
	clearTimeout(this.iTimer);
	this.iTimer = setTimeout(_.bind(this.getMessages, this, 1), 2000);
};

CSimpleChatView.prototype.executeSendQuickReply = function ()
{
	if (this.bAllowReply)
	{
		this.posts.push({name: App.getUserName(), text: this.replyText()});
		Ajax.send('PostMessage', {'Message': this.replyText()}, this.setTimer, this);
		this.replyText('');
	}
};

module.exports = new CSimpleChatView();
