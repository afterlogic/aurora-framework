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
	this.getPosts(1);
};

CSimpleChatView.prototype.showMore = function ()
{
	this.page(this.page() + 1);
	this.getPosts(this.page());
};

CSimpleChatView.prototype.getPosts = function (iPage)
{
	Ajax.send('GetPosts', {Offset: 0, Limit: 10}, this.onGetPostsResponse, this);
};

CSimpleChatView.prototype.onGetPostsResponse = function (oResponse, oRequest)
{
	if (oResponse.Result && _.isArray(oResponse.Result.Collection))
	{
		var
			iCount = oResponse.Result.Count,
			aPosts = oResponse.Result.Collection,
			oParameters = JSON.parse(oRequest.Parameters),
			bHasMore = oParameters.PerPage > 0 && oParameters.Page * oParameters.PerPage < iCount
//			aNewPosts = [],
//			aPosts = this.posts()
		;
//		console.log(oParameters.Page * oParameters.PerPage < iCount, oParameters.Page, oParameters.PerPage, iCount);
		this.hasMore(bHasMore);
//		console.log('oParameters.Page', oParameters.Page);
//		if (oParameters.Page !== 1)
//		{
//			console.log('aPosts', aPosts);
//			console.log('aPosts', aPosts);
//		}
//		_.each(aPosts, function (oPost) {
//			var oFoundPost = _.find(aPosts, function (oMsg) {
////				if (oParameters.Page !== 1)
////				{
////					console.log(oMsg.name === oPost.name && oMsg.text === oPost.text, oMsg.name, oPost.name, oMsg.text, oPost.text);
////				}
//				return oMsg.name === oPost.name && oMsg.text === oPost.text;
//			});
//			if (oParameters.Page !== 1)
//			{
//				console.log('oFoundPost', oFoundPost, 'oPost', oPost);
//			}
//			if (!oFoundPost)
//			{
//				aNewPosts.push(oPost);
//			}
//		});
//		if (oParameters.Page === 1)
//		{
//			aPosts = _.union(aNewPosts, this.posts());
//		}
//		else
//		{
//			aPosts = _.union(this.posts(), aNewPosts);
//		}
		this.posts(aPosts);
	}
	this.setTimer();
};

CSimpleChatView.prototype.setTimer = function ()
{
	clearTimeout(this.iTimer);
	this.iTimer = setTimeout(_.bind(this.getPosts, this, 1), 2000);
};

CSimpleChatView.prototype.executeSendQuickReply = function ()
{
	if (this.bAllowReply)
	{
		this.posts.push({name: App.getUserName(), text: this.replyText()});
		clearTimeout(this.iTimer);
		Ajax.send('CreatePost', {'Text': this.replyText()}, this.setTimer, this);
		this.replyText('');
	}
};

module.exports = new CSimpleChatView();
