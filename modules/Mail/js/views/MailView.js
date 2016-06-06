'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	TextUtils = require('modules/Core/js/utils/Text.js'),
	Utils = require('modules/Core/js/utils/Common.js'),
	
	App = require('modules/Core/js/App.js'),
	Routing = require('modules/Core/js/Routing.js'),
	WindowOpener = require('modules/Core/js/WindowOpener.js'),
	
	CAbstractScreenView = require('modules/Core/js/views/CAbstractScreenView.js'),
	
	ComposeUtils = (App.isMobile() || App.isNewTab()) ? require('modules/%ModuleName%/js/utils/ScreenCompose.js') : require('modules/%ModuleName%/js/utils/PopupCompose.js'),
	LinksUtils = require('modules/%ModuleName%/js/utils/Links.js'),
	
	AccountList = require('modules/%ModuleName%/js/AccountList.js'),
	MailCache = require('modules/%ModuleName%/js/Cache.js'),
	Settings = require('modules/%ModuleName%/js/Settings.js'),
	
	CFolderListView = require('modules/%ModuleName%/js/views/CFolderListView.js'),
	CMessageListView = require('modules/%ModuleName%/js/views/CMessageListView.js'),
	MessagePaneView = require('modules/%ModuleName%/js/views/MessagePaneView.js')
;

/**
 * @constructor
 */
function CMailView()
{
	CAbstractScreenView.call(this);
	
	this.browserTitle = ko.computed(function () {
		return AccountList.getEmail() + ' - ' + TextUtils.i18n('%MODULENAME%/HEADING_BROWSER_TAB');
	});
	
	this.folderList = MailCache.folderList;
	this.domFoldersMoveTo = ko.observable(null);
	
	this.openMessageInNewWindowBinded = _.bind(this.openMessageInNewWindow, this);
	
	this.oFolderList = new CFolderListView();
	this.oMessageList = new CMessageListView(this.openMessageInNewWindowBinded);
	this.oMessagePane = MessagePaneView;
	MessagePaneView.openMessageInNewWindowBinded = this.openMessageInNewWindowBinded;

	this.isEnableGroupOperations = this.oMessageList.isEnableGroupOperations;

	this.composeLink = ko.observable(Routing.buildHashFromArray(LinksUtils.getCompose()));
	this.composeCommand = Utils.createCommand(this, this.executeCompose, AccountList.isCurrentAllowsMail);

	this.checkMailCommand = Utils.createCommand(this, this.executeCheckMail, AccountList.isCurrentAllowsMail);
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
		var oAccount = AccountList.getCurrent();
		return oAccount ? oAccount.extensionExists('AllowSpamFolderExtension') && !this.isSpamFolder() : false;
	}, this);
	
	this.allowedNotSpamAction = ko.computed(function () {
		var oAccount = AccountList.getCurrent();
		return oAccount ? oAccount.extensionExists('AllowSpamFolderExtension') && this.isSpamFolder() : false;
	}, this);
	
	this.isTrashFolder = ko.computed(function () {
		return this.folderList().currentFolderType() === Enums.FolderTypes.Trash;
	}, this);

	this.jqPanelHelper = null;
	
	this.sToolbarViewTemplate = App.isMobile() ? 'Mail_Messages_ToolbarMobileView' : 'Mail_Messages_ToolbarView';
	
	this.selectedPanel = ko.observable(Enums.MobilePanel.Items);
	MailCache.currentMessage.subscribe(function () {
		this.gotoMessagePane();
	}, this);
}

_.extendOwn(CMailView.prototype, CAbstractScreenView.prototype);

CMailView.prototype.ViewTemplate = 'Mail_MailView';

CMailView.prototype.executeCompose = function ()
{
	ComposeUtils.composeMessage();
};

CMailView.prototype.executeCheckMail = function ()
{
	MailCache.checkMessageFlags();
	MailCache.executeCheckMail(true);
};

/**
 * @param {object} oMessage
 */
CMailView.prototype.openMessageInNewWindow = function (oMessage)
{
	if (oMessage)
	{
		var
			sFolder = oMessage.folder(),
			sUid = oMessage.uid(),
			oFolder = this.folderList().getFolderByFullName(sFolder),
			bDraftFolder = (oFolder.type() === Enums.FolderTypes.Drafts),
			sHash = ''
		;

		if (bDraftFolder)
		{
			sHash = Routing.buildHashFromArray(LinksUtils.getComposeFromMessage('drafts', sFolder, sUid));
		}
		else
		{
			sHash = Routing.buildHashFromArray(LinksUtils.getViewMessage(sFolder, sUid));
			MessagePaneView.passReplyDataToNewTab(oMessage.sUniq);
		}

		WindowOpener.openTab('?message-newtab' + sHash);
	}
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
	if (App.isMobile())
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
	var oParams = LinksUtils.parseMailbox(aParams);
	
	AccountList.changeCurrentAccountByHash(oParams.AccountHash);
	
	this.oMessageList.onRoute(aParams);
	MessagePaneView.onRoute(aParams);
};

CMailView.prototype.onShow = function ()
{
	this.oMessageList.onShow();
	MessagePaneView.onShow();
};

CMailView.prototype.onHide = function ()
{
	this.oMessageList.onHide();
	MessagePaneView.onHide();
};

CMailView.prototype.onBind = function ()
{
	var self = this;

	this.oMessageList.onBind(this.$viewDom);
	MessagePaneView.onBind(this.$viewDom);

	$(this.domFoldersMoveTo()).on('click', 'span.folder', function (oEvent) {
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
			bComputed = ev && !ev.ctrlKey && !ev.altKey && !ev.shiftKey && !Utils.isTextFieldFocused() && this.shown(),
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
			Routing.setHash(LinksUtils.getCompose());
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

	$('.count-text', oHelper).text(TextUtils.i18n('%MODULENAME%/LABEL_DRAG_MESSAGES_PLURAL', {
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

module.exports = new CMailView();
