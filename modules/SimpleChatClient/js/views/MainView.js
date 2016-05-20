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
	
	this.posts = ko.observableArray([]);
	
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
	this.getMessages();
};

CSimpleChatView.prototype.getMessages = function ()
{
	Ajax.send('GetMessages', {}, this.onGetMessagesResponse, this);
};

CSimpleChatView.prototype.onGetMessagesResponse = function (oResponse)
{
	if (_.isArray(oResponse.Result))
	{
		this.posts(oResponse.Result);
	}
};

CSimpleChatView.prototype.setTimer = function ()
{
	clearTimeout(this.iTimer);
	this.iTimer = setTimeout(_.bind(this.getMessages, this));
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
