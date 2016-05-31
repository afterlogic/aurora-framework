'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	moment = require('moment'),
	
	TextUtils = require('modules/Core/js/utils/Text.js'),
	Types = require('modules/Core/js/utils/Types.js'),
	
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
	
	this.posts = ko.observableArray([]);
	this.gettingMore = ko.observable(false);
	this.offset = ko.observable(0);
	
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
	
	this.saveButtonText = ko.computed(function () {
		return this.replyAutoSavingStarted() ? TextUtils.i18n('MAIL/ACTION_SAVE_IN_PROGRESS') : TextUtils.i18n('MAIL/ACTION_SAVE');
	}, this);
	
	this.scrolledPostsDom = ko.observable(null);
	this.scrollTrigger = ko.observable(0);
	this.bBottom = false;
	this.posts.subscribe(function () {
		this.scrollIfNecessary(0);
	}, this);
	this.replyTextFocus.subscribe(function () {
		this.scrollIfNecessary(500);
	}, this);
}

_.extendOwn(CSimpleChatView.prototype, CAbstractScreenView.prototype);

CSimpleChatView.prototype.ViewTemplate = 'SimpleChatClient_MainView';

CSimpleChatView.prototype.scrollIfNecessary = function (iDelay)
{
	if (this.scrolledPostsDom() && this.scrolledPostsDom()[0])
	{
		var oScrolledPostsDom = this.scrolledPostsDom()[0];
		this.bBottom = (oScrolledPostsDom.clientHeight + oScrolledPostsDom.scrollTop) === oScrolledPostsDom.scrollHeight;
	}
	
	if (this.bBottom)
	{
		_.delay(_.bind(function () {
			this.scrollTrigger(this.scrollTrigger() + 1);
		}, this), iDelay);
	}
};

CSimpleChatView.prototype.onShow = function ()
{
	Ajax.send('GetPostsCount', null, function (oResponse) {
		var iCount = Types.pInt(oResponse && oResponse.Result);
		if (iCount > 10)
		{
			this.offset(iCount - 10);
		}
		this.getPosts();
	}, this);
};

CSimpleChatView.prototype.showMore = function ()
{
	if (this.offset() > 0)
	{
		this.gettingMore(true);
	}
	this.offset((this.offset() >= 10) ? this.offset() - 10 : 0);
	this.getPosts();
};

CSimpleChatView.prototype.getPosts = function ()
{
	this.clearTimer();
	Ajax.send('GetPosts', {Offset: this.offset(), Limit: this.offset() + this.posts().length + 1000}, this.onGetPostsResponse, this);
};

CSimpleChatView.prototype.addPost = function (oPost, bEnd)
{
	oPost.displayDate = this.getDisplayDate(moment.utc(oPost.date));
	oPost.displayText = oPost.text;

	App.broadcastEvent('SimpleChat::DisplayPost::before', {'Post': oPost});
	
	if (bEnd)
	{
		this.posts.push(oPost);
	}
	else
	{
		this.posts.unshift(oPost);
	}
};

CSimpleChatView.prototype.onGetPostsResponse = function (oResponse, oRequest)
{
	if (oResponse.Result && Types.isNonEmptyArray(oResponse.Result.Collection))
	{
		var
			aPosts = oResponse.Result.Collection,
			oLastPost = this.posts()[this.iLastPostIndex],
			fEqualPosts = function (oFirstPost, oSecondPost) {
				return !!oFirstPost && !!oSecondPost && oFirstPost.text === oSecondPost.text && oFirstPost.date === oSecondPost.date;
			}
		;
		if (this.posts().length === 0)
		{
			_.each(aPosts, _.bind(function (oPost) {
				this.addPost(oPost, true);
			}, this));
		}
		else if (this.posts().length !== aPosts.length || !fEqualPosts(oLastPost, aPosts[aPosts.length - 1]))
		{
			var
				oFirstPost = this.posts()[0],
				iFirstIndex = _.findIndex(aPosts, function (oPost) {
					return fEqualPosts(oFirstPost, oPost);
				}),
				iLastIndex = _.findIndex(aPosts, function (oPost) {
					return fEqualPosts(oLastPost, oPost);
				})
			;
			
			this.removeLastPosts();
			
			for (var iIndex = iFirstIndex - 1; iIndex >= 0; iIndex--)
			{
				this.addPost(aPosts[iIndex], false);
			}
			
			for (var iIndex = iLastIndex + 1; iIndex < aPosts.length; iIndex++)
			{
				this.addPost(aPosts[iIndex], true);
			}
		}
		
		this.iLastPostIndex = this.posts().length - 1;
		this.setTimer();
	}
	else if (!this.gettingMore())
	{
		this.setTimer();
	}
	this.gettingMore(false);
};

CSimpleChatView.prototype.removeLastPosts = function ()
{
	var
		iLastIndex = this.iLastPostIndex,
		iIndex = this.posts().length - 1
	;
	
	for (; iIndex > iLastIndex ; iIndex--)
	{
		this.posts.remove(this.posts()[iIndex]);
	}
};

CSimpleChatView.prototype.getDisplayDate = function (oMomentUtc)
{
	var
		oLocal = oMomentUtc.local(),
		oNow = moment()
	;
	
	if (oNow.diff(oLocal, 'days') === 0)
	{
		return oLocal.format('HH:mm:ss');
	}
	else
	{
		return oLocal.format('MMM Do HH:mm:ss');
	}
};

CSimpleChatView.prototype.clearTimer = function ()
{
	clearTimeout(this.iTimer);
};

CSimpleChatView.prototype.setTimer = function ()
{
	this.clearTimer();
	this.iTimer = setTimeout(_.bind(this.getPosts, this, 1), 3000);
};

CSimpleChatView.prototype.sendPost = function ()
{
	if (this.bAllowReply && $.trim(this.replyText()) !== '')
	{
		var sDate = moment().utc().format('YYYY-MM-DD HH:mm:ss');
		this.clearTimer();
		Ajax.send('CreatePost', {'Text': this.replyText(), 'Date': sDate}, this.setTimer, this);
		this.addPost({name: App.getUserName(), text: this.replyText(), 'date': sDate}, true);
		this.replyText('');
	}
	return false;
};

module.exports = new CSimpleChatView();
