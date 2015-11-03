/**
 * @constructor
 */
function CMailViewModel()
{
	this.folderList = App.MailCache.folderList;
	this.domFolderList = ko.observable(null);
	
	this.openMessageInNewWindowBinded = _.bind(this.openMessageInNewWindow, this);
	
	this.oFolderList = new CFolderListViewModel();
	this.oMessageList = new CMessageListViewModel(this.openMessageInNewWindowBinded);
	this.oMessagePane = new CMessagePaneViewModel(this.openMessageInNewWindowBinded);

	this.isEnableGroupOperations = this.oMessageList.isEnableGroupOperations;

	this.composeLink = ko.observable(App.Routing.buildHashFromArray(App.Links.compose()));
	this.composeCommand = Utils.createCommand(this, this.executeCompose, AppData.Accounts.isCurrentAllowsMail);

	this.checkMailCommand = Utils.createCommand(this, this.executeCheckMail, AppData.Accounts.isCurrentAllowsMail);
	this.checkMailIndicator = ko.observable(true).extend({ throttle: 50 });
	ko.computed(function () {
		this.checkMailIndicator(App.MailCache.checkMailStarted() || App.MailCache.messagesLoading());
	}, this);
	this.markAsReadCommand = Utils.createCommand(this.oMessageList, this.oMessageList.executeMarkAsRead, this.isEnableGroupOperations);
	this.markAsUnreadCommand = Utils.createCommand(this.oMessageList, this.oMessageList.executeMarkAsUnread, this.isEnableGroupOperations);
	this.markAllReadCommand = Utils.createCommand(this.oMessageList, this.oMessageList.executeMarkAllRead);
	this.moveToFolderCommand = Utils.createCommand(this, Utils.emptyFunction, this.isEnableGroupOperations);
//	this.copyToFolderCommand = Utils.createCommand(this, Utils.emptyFunction, this.isEnableGroupOperations);
	this.deleteCommand = Utils.createCommand(this.oMessageList, this.oMessageList.executeDelete, this.isEnableGroupOperations);
	this.selectedCount = ko.computed(function () {
		return this.oMessageList.checkedUids().length;
	}, this);
	this.emptyTrashCommand = Utils.createCommand(App.MailCache, App.MailCache.executeEmptyTrash, this.oMessageList.isNotEmptyList);
	this.emptySpamCommand = Utils.createCommand(App.MailCache, App.MailCache.executeEmptySpam, this.oMessageList.isNotEmptyList);
	this.spamCommand = Utils.createCommand(this.oMessageList, this.oMessageList.executeSpam, this.isEnableGroupOperations);
	this.notSpamCommand = Utils.createCommand(this.oMessageList, this.oMessageList.executeNotSpam, this.isEnableGroupOperations);

	this.bVisibleComposeMessage = AppData.User.AllowCompose;
	
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
		var oAccount = AppData.Accounts.getCurrent();
		return oAccount ? oAccount.extensionExists('AllowSpamFolderExtension') && !this.isSpamFolder() : false;
	}, this);
	
	this.allowedNotSpamAction = ko.computed(function () {
		var oAccount = AppData.Accounts.getCurrent();
		return oAccount ? oAccount.extensionExists('AllowSpamFolderExtension') && this.isSpamFolder() : false;
	}, this);
	
	this.isTrashFolder = ko.computed(function () {
		return this.folderList().currentFolderType() === Enums.FolderTypes.Trash;
	}, this);

	this.jqPanelHelper = null;
	
	this.mobileApp = bMobileApp;
	this.selectedPanel = ko.observable(Enums.MobilePanel.Items);
	App.MailCache.currentMessage.subscribe(function () {
		this.gotoMessagePane();
	}, this);
}

CMailViewModel.prototype.executeCompose = function ()
{
	App.Api.composeMessage();
};

CMailViewModel.prototype.executeCheckMail = function ()
{
	App.MailCache.checkMessageFlags();
	App.MailCache.executeCheckMail(true);
};

CMailViewModel.prototype.openMessageInNewWindow = function (oMessage)
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
	
	Utils.WindowOpener.openMessage(oMessage, bDraftFolder);
};

CMailViewModel.prototype.gotoFolderList = function ()
{
	this.changeSelectedPanel(Enums.MobilePanel.Groups);
};

CMailViewModel.prototype.gotoMessageList = function ()
{
	this.changeSelectedPanel(Enums.MobilePanel.Items);
	return true;
};

CMailViewModel.prototype.gotoMessagePane = function ()
{
	if (App.MailCache.currentMessage())
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
CMailViewModel.prototype.changeSelectedPanel = function (iPanel)
{
	if (this.mobileApp)
	{
		if (this.selectedPanel() !== iPanel)
		{
			this.selectedPanel(iPanel);
			if (bIsWindowsPhone || App.browser.ie)
			{
				this.$viewModel.hide();
				_.defer(_.bind(function () {
					this.$viewModel.show();
				}, this));
			}
		}
	}
};

/**
 * @param {Object} oData
 * @param {Object} oEvent
 */
CMailViewModel.prototype.resizeDblClick = function (oData, oEvent)
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
CMailViewModel.prototype.onRoute = function (aParams)
{
	this.oMessageList.onRoute(aParams);
	this.oMessagePane.onRoute(aParams);
};

CMailViewModel.prototype.onShow = function ()
{
	this.oMessageList.onShow();
};

CMailViewModel.prototype.onHide = function ()
{
	this.oMessageList.onHide();
};

CMailViewModel.prototype.onApplyBindings = function ()
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

CMailViewModel.prototype.hotKeysBind = function ()
{
	$(document).on('keydown', $.proxy(function(ev) {
		var
			sKey = ev.keyCode,
			bComputed = ev && !ev.ctrlKey && !ev.altKey && !ev.shiftKey && !Utils.isTextFieldFocused() && App.Screens.currentScreen() === Enums.Screens.Mailbox,
			oList = this.oMessageList,
			oFirstMessage = oList.collection()[0],
			bGotoSearch = oFirstMessage && App.MailCache.currentMessage() && oFirstMessage.uid() === App.MailCache.currentMessage().uid()
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
CMailViewModel.prototype.dragAndDropHelper = function (oMessage, bCtrl)
{
	if (oMessage)
	{
		oMessage.checked(true);
	}

	var
		oHelper = Utils.draggableMessages(),
		aUids = this.oMessageList.checkedOrSelectedUids(),
		iCount = aUids.length
	;
		
	oHelper.data('p7-message-list-folder', this.folderList().currentFolderFullName());
	oHelper.data('p7-message-list-uids', aUids);

	$('.count-text', oHelper).text(Utils.i18n('MAILBOX/DRAG_TEXT_PLURAL', {
		'COUNT': bCtrl ? '+ ' + iCount : iCount
	}, null, iCount));

	return oHelper;
};

/**
 * @param {Object} oToFolder
 * @param {Object} oEvent
 * @param {Object} oUi
 */
CMailViewModel.prototype.messagesDrop = function (oToFolder, oEvent, oUi)
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

CMailViewModel.prototype.searchFocus = function ()
{
	if (this.oMessageList.selector.useKeyboardKeys() && !Utils.isTextFieldFocused())
	{
		this.oMessageList.isFocused(true);
	}
};

CMailViewModel.prototype.backToList = function ()
{
	App.Routing.setPreviousHash();
};

CMailViewModel.prototype.onVolumerClick = function (oVm, oEv)
{
	oEv.stopPropagation();
};

CMailViewModel.prototype.uncheckMessages = function ()
{
	_.each(App.MailCache.messages(), function(oMessage) {
		oMessage.checked(false);
	});
};
