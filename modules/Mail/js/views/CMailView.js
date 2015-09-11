'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	$ = require('jquery'),
	
	Utils = require('core/js/utils/Common.js'),
	TextUtils = require('core/js/utils/Text.js'),
	Routing = require('core/js/Routing.js'),
	WindowOpener = require('core/js/WindowOpener.js'),
	
	LinksUtils = require('modules/Mail/js/utils/Links.js'),
	Accounts = require('modules/Mail/js/AccountList.js'),
	MailCache  = require('modules/Mail/js/Cache.js'),
	Settings  = require('modules/Mail/js/Settings.js'),
	CFolderListView = require('modules/Mail/js/views/CFolderListView.js'),
	CMessageListView = require('modules/Mail/js/views/CMessageListView.js'),
	CMessagePaneView = require('modules/Mail/js/views/CMessagePaneView.js'),
	
	bMobileApp = false
;

/**
 * @constructor
 */
function CMailView()
{
	this.folderList = MailCache.folderList;
	this.domFolderList = ko.observable(null);
	
	this.openMessageInNewWindowBinded = _.bind(this.openMessageInNewWindow, this);
	
	this.oFolderList = new CFolderListView();
	this.oMessageList = new CMessageListView(this.openMessageInNewWindowBinded);
	this.oMessagePane = new CMessagePaneView(this.openMessageInNewWindowBinded);

	this.isEnableGroupOperations = this.oMessageList.isEnableGroupOperations;

	this.composeLink = ko.observable(Routing.buildHashFromArray(LinksUtils.getCompose()));
	this.composeCommand = Utils.createCommand(this, this.executeCompose, Accounts.isCurrentAllowsMail);

	this.checkMailCommand = Utils.createCommand(this, this.executeCheckMail, Accounts.isCurrentAllowsMail);
	this.checkMailIndicator = ko.observable(true).extend({ throttle: 50 });
	ko.computed(function () {
		this.checkMailIndicator(MailCache.checkMailStarted() || MailCache.messagesLoading());
	}, this);
	this.markAsReadCommand = Utils.createCommand(this.oMessageList, this.oMessageList.executeMarkAsRead, this.isEnableGroupOperations);
	this.markAsUnreadCommand = Utils.createCommand(this.oMessageList, this.oMessageList.executeMarkAsUnread, this.isEnableGroupOperations);
	this.markAllReadCommand = Utils.createCommand(this.oMessageList, this.oMessageList.executeMarkAllRead);
	this.moveToFolderCommand = Utils.createCommand(this, function () {}, this.isEnableGroupOperations);
//	this.copyToFolderCommand = Utils.createCommand(this, function () {}, this.isEnableGroupOperations);
	this.deleteCommand = Utils.createCommand(this.oMessageList, this.oMessageList.executeDelete, this.isEnableGroupOperations);
	this.selectedCount = ko.computed(function () {
		return this.oMessageList.checkedUids().length;
	}, this);
	this.emptyTrashCommand = Utils.createCommand(MailCache, MailCache.executeEmptyTrash, this.oMessageList.isNotEmptyList);
	this.emptySpamCommand = Utils.createCommand(MailCache, MailCache.executeEmptySpam, this.oMessageList.isNotEmptyList);
	this.spamCommand = Utils.createCommand(this.oMessageList, this.oMessageList.executeSpam, this.isEnableGroupOperations);
	this.notSpamCommand = Utils.createCommand(this.oMessageList, this.oMessageList.executeNotSpam, this.isEnableGroupOperations);

	this.bVisibleComposeMessage = Settings.AllowCompose;
	
	this.isVisibleReplyTool = ko.computed(function () {
		return (this.folderList().currentFolder() &&
			this.folderList().currentFolderFullName().length > 0 &&
			this.folderList().currentFolderType() !== Enums.FolderTypes.Drafts &&
			this.folderList().currentFolderType() !== Enums.FolderTypes.Sent);
	}, this);

	this.isVisibleForwardTool = ko.computed(function () {
		return (this.folderList().currentFolder() &&
			this.folderList().currentFolderFullName().length > 0 &&
			this.folderList().currentFolderType() !== Enums.FolderTypes.Drafts);
	}, this);

	this.isSpamFolder = ko.computed(function () {
		return this.folderList().currentFolderType() === Enums.FolderTypes.Spam;
	}, this);
	
	this.allowedSpamAction = ko.computed(function () {
		var oAccount = Accounts.getCurrent();
		return oAccount ? oAccount.extensionExists('AllowSpamFolderExtension') && !this.isSpamFolder() : false;
	}, this);
	
	this.allowedNotSpamAction = ko.computed(function () {
		var oAccount = Accounts.getCurrent();
		return oAccount ? oAccount.extensionExists('AllowSpamFolderExtension') && this.isSpamFolder() : false;
	}, this);
	
	this.isTrashFolder = ko.computed(function () {
		return this.folderList().currentFolderType() === Enums.FolderTypes.Trash;
	}, this);

	this.jqPanelHelper = null;
	
	this.mobileApp = bMobileApp;
	this.selectedPanel = ko.observable(Enums.MobilePanel.Items);
	MailCache.currentMessage.subscribe(function () {
		this.gotoMessagePane();
	}, this);
}

CMailView.prototype.executeCompose = function ()
{
	App.Api.composeMessage();
};

CMailView.prototype.executeCheckMail = function ()
{
	MailCache.checkMessageFlags();
	MailCache.executeCheckMail(true);
};

CMailView.prototype.openMessageInNewWindow = function (oMessage)
{
	var
		oFolder = this.folderList().getFolderByFullName(oMessage.folder()),
		bDraftFolder = (oFolder.type() === Enums.FolderTypes.Drafts)
	;
	
	if (this.oMessagePane.currentMessage() && this.oMessagePane.currentMessage().uid() === oMessage.uid() &&
			(this.oMessagePane.replyText() !== '' || this.oMessagePane.replyDraftUid() !== ''))
	{
		window.oReplyDataFromViewPane = {
			'ReplyText': this.oMessagePane.replyText(),
			'ReplyDraftUid': this.oMessagePane.replyDraftUid()
		};
		this.oMessagePane.replyText('');
		this.oMessagePane.replyDraftUid('');
	}
	
	WindowOpener.openMessage(oMessage, bDraftFolder);
};

CMailView.prototype.gotoFolderList = function ()
{
	this.changeSelectedPanel(Enums.MobilePanel.Groups);
};

CMailView.prototype.gotoMessageList = function ()
{
	this.changeSelectedPanel(Enums.MobilePanel.Items);
	return true;
};

CMailView.prototype.gotoMessagePane = function ()
{
	if (MailCache.currentMessage())
	{
		this.changeSelectedPanel(Enums.MobilePanel.View);
	}
	else
	{
		this.gotoMessageList();
	}
};

/**
 * @param {number} iPanel
 */
CMailView.prototype.changeSelectedPanel = function (iPanel)
{
	if (this.mobileApp)
	{
		if (this.selectedPanel() !== iPanel)
		{
			this.selectedPanel(iPanel);
		}
	}
};

/**
 * @param {Object} oData
 * @param {Object} oEvent
 */
CMailView.prototype.resizeDblClick = function (oData, oEvent)
{
	oEvent.preventDefault();
	if (oEvent.stopPropagation)
	{
		oEvent.stopPropagation();
	}
	else
	{
		oEvent.cancelBubble = true;
	}

	Utils.removeSelection();
	if (!this.jqPanelHelper)
	{
		this.jqPanelHelper = $('.MailLayout .panel_helper');
	}
	this.jqPanelHelper.trigger('resize', [600, 'max']);
};

/**
 * @param {Array} aParams
 */
CMailView.prototype.onRoute = function (aParams)
{
	this.oMessageList.onRoute(aParams);
	this.oMessagePane.onRoute(aParams);
};

CMailView.prototype.onShow = function ()
{
	this.oMessageList.onShow();
};

CMailView.prototype.onHide = function ()
{
	this.oMessageList.onHide();
};

CMailView.prototype.onApplyBindings = function ()
{
	var self = this;

	this.oMessageList.onApplyBindings(this.$viewModel);
	this.oMessagePane.onApplyBindings(this.$viewModel);

	$(this.domFolderList()).on('click', 'span.folder', function (oEvent) {
		if (self.folderList().currentFolderFullName() !== $(this).data('folder')) {
			if (oEvent.ctrlKey) {
				self.oMessageList.executeCopyToFolder($(this).data('folder'));
			}
			else {
				self.oMessageList.executeMoveToFolder($(this).data('folder'));
			}
		}
	});

	this.hotKeysBind();
};

CMailView.prototype.hotKeysBind = function ()
{
	$(document).on('keydown', $.proxy(function(ev) {
		var
			sKey = ev.keyCode,
			bComputed = ev && !ev.ctrlKey && !ev.altKey && !ev.shiftKey && !Utils.isTextFieldFocused(),// && App.Screens.currentScreen() === Enums.Screens.Mailbox,
			oList = this.oMessageList,
			oFirstMessage = oList.collection()[0],
			bGotoSearch = oFirstMessage && MailCache.currentMessage() && oFirstMessage.uid() === MailCache.currentMessage().uid()
		;
		
		if (bComputed && sKey === Enums.Key.s || bComputed && bGotoSearch && sKey === Enums.Key.Up)
		{
			ev.preventDefault();
			this.searchFocus();
		}
		else if (oList.isFocused() && ev && sKey === Enums.Key.Down && oFirstMessage)
		{
			ev.preventDefault();
			oList.isFocused(false);
			oList.routeForMessage(oFirstMessage);
		}
		else if (bComputed && sKey === Enums.Key.n)
		{
			window.location.href = '#compose';
		}
	},this));
};

/**
 * @param {Object} oMessage
 * @param {boolean} bCtrl
 */
CMailView.prototype.dragAndDropHelper = function (oMessage, bCtrl)
{
	if (oMessage)
	{
		oMessage.checked(true);
	}

	var
		oHelper = Utils.draggableItems(),
		aUids = this.oMessageList.checkedOrSelectedUids(),
		iCount = aUids.length
	;
		
	oHelper.data('p7-message-list-folder', this.folderList().currentFolderFullName());
	oHelper.data('p7-message-list-uids', aUids);

	$('.count-text', oHelper).text(TextUtils.i18n('MAILBOX/DRAG_TEXT_PLURAL', {
		'COUNT': bCtrl ? '+ ' + iCount : iCount
	}, null, iCount));

	return oHelper;
};

/**
 * @param {Object} oToFolder
 * @param {Object} oEvent
 * @param {Object} oUi
 */
CMailView.prototype.messagesDrop = function (oToFolder, oEvent, oUi)
{
	if (oToFolder)
	{
		var
			oHelper = oUi && oUi.helper ? oUi.helper : null,
			sFolder = oHelper ? oHelper.data('p7-message-list-folder') : '',
			aUids = oHelper ? oHelper.data('p7-message-list-uids') : null
		;

		if ('' !== sFolder && null !== aUids)
		{
			Utils.uiDropHelperAnim(oEvent, oUi);
			if(oEvent.ctrlKey)
			{
				this.oMessageList.executeCopyToFolder(oToFolder.fullName());
			}
			else
			{
				this.oMessageList.executeMoveToFolder(oToFolder.fullName());
			}
			
			this.uncheckMessages();
		}
	}
};

CMailView.prototype.searchFocus = function ()
{
	if (this.oMessageList.selector.useKeyboardKeys() && !Utils.isTextFieldFocused())
	{
		this.oMessageList.isFocused(true);
	}
};

CMailView.prototype.backToList = function ()
{
	Routing.setPreviousHash();
};

CMailView.prototype.onVolumerClick = function (oVm, oEv)
{
	oEv.stopPropagation();
};

CMailView.prototype.uncheckMessages = function ()
{
	_.each(MailCache.messages(), function(oMessage) {
		oMessage.checked(false);
	});
};

module.exports = CMailView;