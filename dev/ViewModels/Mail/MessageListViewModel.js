
/**
 * @constructor
 * 
 * @param {Function} fOpenMessageInNewWindowBinded
 */
function CMessageListViewModel(fOpenMessageInNewWindowBinded)
{
	this.isPublic = bExtApp;

	this.uploaderArea = ko.observable(null);
	this.bDragActive = ko.observable(false);
	this.bDragActiveComp = ko.computed(function () {
		return this.bDragActive();
	}, this);

	this.openMessageInNewWindowBinded = fOpenMessageInNewWindowBinded;
	
	this.isFocused = ko.observable(false);

	this.messagesContainer = ko.observable(null);

	this.searchInput = ko.observable('');
	this.searchInputFrom = ko.observable('');
	this.searchInputTo = ko.observable('');
	this.searchInputSubject = ko.observable('');
	this.searchInputText = ko.observable('');
	this.searchSpan = ko.observable('');
	this.highlightTrigger = ko.observable('');

	this.currentMessage = App.MailCache.currentMessage;
	this.currentMessage.subscribe(function () {
		this.isFocused(false);
		this.selector.itemSelected(this.currentMessage());
	}, this);

	this.folderList = App.MailCache.folderList;
	this.folderList.subscribe(this.onFolderListSubscribe, this);
	this.folderFullName = ko.observable('');
	this.folderType = ko.observable(Enums.FolderTypes.User);
	this.filters = ko.observable('');

	this.uidList = App.MailCache.uidList;
	this.uidList.subscribe(function () {
		if (this.uidList().searchCountSubscription)
		{
			this.uidList().searchCountSubscription.dispose();
			this.uidList().searchCountSubscription = undefined;
		}
		this.uidList().searchCountSubscription = this.uidList().resultCount.subscribe(function () {
			if (this.uidList().resultCount() >= 0)
			{
				this.oPageSwitcher.setCount(this.uidList().resultCount());
			}
		}, this);
		
		if (this.uidList().resultCount() >= 0)
		{
			this.oPageSwitcher.setCount(this.uidList().resultCount());
		}
	}, this);

	this.useThreads = ko.computed(function () {
		var
			oFolder = this.folderList().currentFolder(),
			bFolderWithoutThreads = oFolder && oFolder.withoutThreads(),
			bNotSearchOrFilters = this.uidList().search() === '' && this.uidList().filters() === ''
		;
		
		return AppData.User.useThreads() && !bFolderWithoutThreads && bNotSearchOrFilters;
	}, this);

	this.collection = App.MailCache.messages;
	
	this._search = ko.observable('');
	this.search = ko.computed({
		'read': function () {
			return Utils.trim(this._search());
		},
		'write': this._search,
		'owner': this
	});

	this.isEmptyList = ko.computed(function () {
		return this.collection().length === 0;
	}, this);

	this.isNotEmptyList = ko.computed(function () {
		return this.collection().length !== 0;
	}, this);

	this.isSearch = ko.computed(function () {
		return this.search().length > 0;
	}, this);

	this.isUnseenFilter = ko.computed(function () {
		return this.filters() === Enums.FolderFilter.Unseen;
	}, this);

	this.isLoading = App.MailCache.messagesLoading;

	this.isError = App.MailCache.messagesLoadingError;

	this.visibleInfoLoading = ko.computed(function () {
		return !this.isSearch() && this.isLoading();
	}, this);
	this.visibleInfoSearchLoading = ko.computed(function () {
		return this.isSearch() && this.isLoading();
	}, this);
	this.visibleInfoSearchList = ko.computed(function () {
		return this.isSearch() && !this.isUnseenFilter() && !this.isLoading() && !this.isEmptyList();
	}, this);
	this.visibleInfoMessageListEmpty = ko.computed(function () {
		return !this.isLoading() && !this.isSearch() && (this.filters() === '') && this.isEmptyList() && !this.isError();
	}, this);
	this.visibleInfoStarredFolderEmpty = ko.computed(function () {
		return !this.isLoading() && !this.isSearch() && (this.filters() === Enums.FolderFilter.Flagged) && this.isEmptyList() && !this.isError();
	}, this);
	this.visibleInfoSearchEmpty = ko.computed(function () {
		return this.isSearch() && !this.isUnseenFilter() && this.isEmptyList() && !this.isError() && !this.isLoading();
	}, this);
	this.visibleInfoMessageListError = ko.computed(function () {
		return !this.isSearch() && this.isError();
	}, this);
	this.visibleInfoSearchError = ko.computed(function () {
		return this.isSearch() && this.isError();
	}, this);
	this.visibleInfoUnseenFilterList = ko.computed(function () {
		return this.isUnseenFilter() && (this.isLoading() || !this.isEmptyList());
	}, this);
	this.visibleInfoUnseenFilterEmpty = ko.computed(function () {
		return this.isUnseenFilter() && this.isEmptyList() && !this.isError() && !this.isLoading();
	}, this);

	this.searchText = ko.computed(function () {

		return Utils.i18n('MAILBOX/INFO_SEARCH_RESULT', {
			'SEARCH': this.calculateSearchStringForDescription(),
			'FOLDER': this.folderList().currentFolder() ? this.folderList().currentFolder().displayName() : ''
		});
		
	}, this);

	this.unseenFilterText = ko.computed(function () {

		if (this.search() === '')
		{
			return Utils.i18n('MAILBOX/INFO_UNSEEN_FILTER_RESULT', {
				'FOLDER': this.folderList().currentFolder() ? this.folderList().currentFolder().displayName() : ''
			});
		}
		else
		{
			return Utils.i18n('MAILBOX/INFO_SEARCH_UNSEEN_FILTER_RESULT', {
				'SEARCH': this.calculateSearchStringForDescription(),
				'FOLDER': this.folderList().currentFolder() ? this.folderList().currentFolder().displayName() : ''
			});
		}
		
	}, this);

	this.unseenFilterEmptyText = ko.computed(function () {

		if (this.search() === '')
		{
			return Utils.i18n('MAILBOX/INFO_UNSEEN_FILTER_EMPTY');
		}
		else
		{
			return Utils.i18n('MAILBOX/INFO_SEARCH_UNSEEN_FILTER_EMPTY');
		}
		
	}, this);

	this.isEnableGroupOperations = ko.observable(false).extend({'throttle': 250});

	this.selector = new CSelector(
		this.collection,
		_.bind(this.routeForMessage, this),
		_.bind(this.onDeletePress, this),
		_.bind(this.onMessageDblClick, this),
		_.bind(this.onEnterPress, this)
	);

	this.checkedUids = ko.computed(function () {
		var
			aChecked = this.selector.listChecked(),
			aCheckedUids = _.map(aChecked, function (oItem) {
				return oItem.uid();
			}),
			oFolder = App.MailCache.folderList().currentFolder(),
			aThreadCheckedUids = oFolder ? oFolder.getThreadCheckedUidsFromList(aChecked) : [],
			aUids = _.union(aCheckedUids, aThreadCheckedUids)
		;

		return aUids;
	}, this);
	
	this.checkedOrSelectedUids = ko.computed(function () {
		var aChecked = this.checkedUids();
		if (aChecked.length === 0 && App.MailCache.currentMessage() && !App.MailCache.currentMessage().deleted())
		{
			aChecked = [App.MailCache.currentMessage().uid()];
		}
		return aChecked;
	}, this);

	ko.computed(function () {
		this.isEnableGroupOperations(0 < this.selector.listCheckedOrSelected().length);
	}, this);

	this.checkAll = this.selector.koCheckAll();
	this.checkAllIncomplite = this.selector.koCheckAllIncomplete();

	this.pageSwitcherLocked = ko.observable(false);
	this.oPageSwitcher = new CPageSwitcherViewModel(0, AppData.User.MailsPerPage);
	this.oPageSwitcher.currentPage.subscribe(function (iPage) {
		var
			sFolder = this.folderList().currentFolderFullName(),
			sUid = !bMobileApp && this.currentMessage() ? this.currentMessage().uid() : '',
			sSearch = this.search()
		;
		
		if (!this.pageSwitcherLocked())
		{
			this.changeRoutingForMessageList(sFolder, iPage, sUid, sSearch, this.filters());
		}
	}, this);
	this.currentPage = ko.observable(0);
	
	// to the message list does not twitch
	if (App.browser.firefox || App.browser.ie)
	{
		this.listChangedThrottle = ko.observable(false).extend({'throttle': 10});
	}
	else
	{
		this.listChangedThrottle = ko.observable(false);
	}
	
	this.firstCompleteCollection = ko.observable(true);
	this.collection.subscribe(function () {
		if (this.collection().length > 0)
		{
			this.firstCompleteCollection(false);
		}
	}, this);
	this.currentAccountId = AppData.Accounts.currentId;
	this.listChanged = ko.computed(function () {
		return [
			this.firstCompleteCollection(),
			this.currentAccountId(),
			this.folderFullName(),
			this.filters(),
			this.search(),
			this.oPageSwitcher.currentPage()
		];
	}, this);
	
	this.listChanged.subscribe(function() {
		this.listChangedThrottle(!this.listChangedThrottle());
	}, this);

	this.bAdvancedSearch = ko.observable(false);
	this.searchAttachmentsCheckbox = ko.observable(false);
	this.searchAttachments = ko.observable('');
	this.searchAttachments.subscribe(function(sText) {
		this.searchAttachmentsCheckbox(!!sText);
	}, this);
	
	this.searchAttachmentsFocus = ko.observable(false);
	this.searchFromFocus = ko.observable(false);
	this.searchSubjectFocus = ko.observable(false);
	this.searchToFocus = ko.observable(false);
	this.searchTextFocus = ko.observable(false);
	this.searchTrigger = ko.observable(null);
	this.searchDateStartFocus = ko.observable(false);
	this.searchDateEndFocus = ko.observable(false);
	this.searchDateStartDom = ko.observable(null);
	this.searchDateStart = ko.observable('');
	this.searchDateEndDom = ko.observable(null);
	this.searchDateEnd = ko.observable('');
	this.dateFormatDatePicker = 'yy.mm.dd';
	this.attachmentsPlaceholder = ko.computed(function () {
		return Utils.i18n('MAILBOX/SEARCH_FIELD_HAS_ATTACHMENTS');
	}, this);

	_.delay(_.bind(function(){
		this.createDatePickerObject(this.searchDateStartDom());
		this.createDatePickerObject(this.searchDateEndDom());
	}, this), 1000);
	
	this.isCurrentAllowsMail = AppData.Accounts.isCurrentAllowsMail;
	
	var aAddingInfo = Utils.i18n('MAILBOX/INFO_ADDING_NEW_ACCOUNT').split(/%STARTLINK%|%ENDLINK%/);
	this.sAddingInfo1 = aAddingInfo.length > 0 ? aAddingInfo[0] : '';
	this.sAddingInfo2 = aAddingInfo.length > 1 ? aAddingInfo[1] : '';
	this.sAddingInfo3 = aAddingInfo.length > 2 ? aAddingInfo[2] : '';
}

CMessageListViewModel.prototype.addNewAccount = function ()
{
	App.Api.createMailAccount(AppData.Accounts.getEmail());
};

CMessageListViewModel.prototype.createDatePickerObject = function (oElement)
{
	$(oElement).datepicker({
		showOtherMonths: true,
		selectOtherMonths: true,
		monthNames: Utils.getMonthNamesArray(),
		dayNamesMin: Utils.i18n('DATETIME/DAY_NAMES_MIN').split(' '),
		nextText: '',
		prevText: '',
		firstDay: AppData.User.CalendarWeekStartsOn,
		showOn: 'focus',
		dateFormat: this.dateFormatDatePicker
	});

	$(oElement).mousedown(function() {
		$('#ui-datepicker-div').toggle();
	});
};

/**
 * @param {string} sFolder
 * @param {number} iPage
 * @param {string} sUid
 * @param {string} sSearch
 * @param {string} sFilters
 */
CMessageListViewModel.prototype.changeRoutingForMessageList = function (sFolder, iPage, sUid, sSearch, sFilters)
{
	var bSame = App.Routing.setHash(App.Links.mailbox(sFolder, iPage, sUid, sSearch, sFilters));
	
	if (bSame && sSearch.length > 0 && this.search() === sSearch)
	{
		this.listChangedThrottle(!this.listChangedThrottle());
	}
};

/**
 * @param {CMessageModel} oMessage
 */
CMessageListViewModel.prototype.onEnterPress = function (oMessage)
{
	oMessage.openThread();
};

/**
 * @param {CMessageModel} oMessage
 */
CMessageListViewModel.prototype.onMessageDblClick = function (oMessage)
{
	if (!this.isSavingDraft(oMessage))
	{
		var
			oFolder = this.folderList().getFolderByFullName(oMessage.folder())
		;

		if (oFolder.type() === Enums.FolderTypes.Drafts)
		{
			App.Api.composeMessageFromDrafts(oMessage.folder(), oMessage.uid());
		}
		else
		{
			this.openMessageInNewWindowBinded(oMessage);
		}
	}
};

CMessageListViewModel.prototype.onFolderListSubscribe = function ()
{
	this.setCurrentFolder();
	this.requestMessageList();
};

/**
 * @param {Array} aParams
 */
CMessageListViewModel.prototype.onShow = function (aParams)
{
	this.selector.useKeyboardKeys(true);
	this.oPageSwitcher.show();

	if (this.oJua)
	{
		this.oJua.setDragAndDropEnabledStatus(true);
	}
};

/**
 * @param {Array} aParams
 */
CMessageListViewModel.prototype.onHide = function (aParams)
{
	this.selector.useKeyboardKeys(false);
	this.oPageSwitcher.hide();

	if (this.oJua)
	{
		this.oJua.setDragAndDropEnabledStatus(false);
	}
};

/**
 * @param {Array} aParams
 */
CMessageListViewModel.prototype.onRoute = function (aParams)
{
	var
		oParams = App.Links.parseMailbox(aParams),
		bRouteChanged = this.currentPage() !== oParams.Page ||
			this.folderFullName() !== oParams.Folder ||
			this.filters() !== oParams.Filters || (oParams.Filters === Enums.FolderFilter.Unseen && App.MailCache.waitForUnseenMessages()) ||
			this.search() !== oParams.Search,
		bMailsPerPageChanged = AppData.User.MailsPerPage !== this.oPageSwitcher.perPage()
	;
	
	this.pageSwitcherLocked(true);
	if (this.folderFullName() !== oParams.Folder || this.search() !== oParams.Search || this.filters() !== oParams.Filters)
	{
		this.oPageSwitcher.clear();
	}
	else
	{
		this.oPageSwitcher.setPage(oParams.Page, AppData.User.MailsPerPage);
	}
	this.pageSwitcherLocked(false);
	
	if (oParams.Page !== this.oPageSwitcher.currentPage())
	{
		App.Routing.replaceHash(App.Links.mailbox(oParams.Folder, this.oPageSwitcher.currentPage(), oParams.Uid, oParams.Search, oParams.Filters));
	}

	this.currentPage(this.oPageSwitcher.currentPage());
	this.folderFullName(oParams.Folder);
	this.filters(oParams.Filters);
	this.search(oParams.Search);
	this.searchInput(this.search());
	this.searchSpan.notifySubscribers();

	this.setCurrentFolder();
	
	if (bRouteChanged || bMailsPerPageChanged || this.collection().length === 0)
	{
		if (oParams.Filters === Enums.FolderFilter.Unseen)
		{
			App.MailCache.waitForUnseenMessages(true);
		}
		this.requestMessageList();
	}

	this.highlightTrigger.notifySubscribers(true);
};

CMessageListViewModel.prototype.setCurrentFolder = function ()
{
	this.folderList().setCurrentFolder(this.folderFullName(), this.filters());
	this.folderType(App.MailCache.folderList().currentFolderType());
};

CMessageListViewModel.prototype.requestMessageList = function ()
{
	var
		sFullName = this.folderList().currentFolderFullName(),
		iPage = this.oPageSwitcher.currentPage()
	;
	
	if (sFullName.length > 0)
	{
		App.MailCache.changeCurrentMessageList(sFullName, iPage, this.search(), this.filters());
	}
	else
	{
		App.MailCache.checkCurrentFolderList();
	}
};

CMessageListViewModel.prototype.calculateSearchStringFromAdvancedForm  = function ()
{
	var
		sFrom = this.searchInputFrom(),
		sTo = this.searchInputTo(),
		sSubject = this.searchInputSubject(),
		sText = this.searchInputText(),
		bAttachmentsCheckbox = this.searchAttachmentsCheckbox(),
		sAttachments = this.searchAttachments(),
		sDateStart = this.searchDateStart(),
		sDateEnd = this.searchDateEnd(),
		aOutput = [],
		fEsc = function (sText) {

			sText = $.trim(sText).replace(/"/g, '\\"');
			
			if (-1 < sText.indexOf(' ') || -1 < sText.indexOf('"'))
			{
				sText = '"' + sText + '"';
			}
			
			return sText;
		}
	;

	if (sFrom !== '')
	{
		aOutput.push('from:' + fEsc(sFrom));
	}

	if (sTo !== '')
	{
		aOutput.push('to:' + fEsc(sTo));
	}

	if (sSubject !== '')
	{
		aOutput.push('subject:' + fEsc(sSubject));
	}
	
	if (sText !== '')
	{
		aOutput.push('text:' + fEsc(sText));
	}

	if (bAttachmentsCheckbox)
	{
		aOutput.push('has:attachments');
	}

	/*if (sAttachments !== '')
	{
		aOutput.push('attachments:' + fEsc(sAttachments));
	}*/

	if (sDateStart !== '' || sDateEnd !== '')
	{
		aOutput.push('date:' + fEsc(sDateStart) + '/' + fEsc(sDateEnd));
	}

	return aOutput.join(' ');
};

CMessageListViewModel.prototype.onSearchClick = function ()
{
	var
		sFolder = this.folderList().currentFolderFullName(),
		//sUid = this.currentMessage() ? this.currentMessage().uid() : '',
		iPage = 1,
		sSearch = this.searchInput()
	;

	if (this.bAdvancedSearch())
	{
		sSearch = this.calculateSearchStringFromAdvancedForm();
		this.searchInput(sSearch);
		this.bAdvancedSearch(false);
	}
	this.changeRoutingForMessageList(sFolder, iPage, '', sSearch, this.filters());
	//this.highlightTrigger.notifySubscribers();
};

CMessageListViewModel.prototype.onRetryClick = function ()
{
	this.requestMessageList();
};

CMessageListViewModel.prototype.onClearSearchClick = function ()
{
	var
		sFolder = this.folderList().currentFolderFullName(),
		sUid = this.currentMessage() ? this.currentMessage().uid() : '',
		sSearch = '',
		iPage = 1
	;

	this.clearAdvancedSearch();
	this.changeRoutingForMessageList(sFolder, iPage, sUid, sSearch, this.filters());
};

CMessageListViewModel.prototype.onClearFilterClick = function ()
{
	var
		sFolder = this.folderList().currentFolderFullName(),
		sUid = this.currentMessage() ? this.currentMessage().uid() : '',
		sSearch = '',
		iPage = 1,
		sFilters = ''
	;

	this.clearAdvancedSearch();
	this.changeRoutingForMessageList(sFolder, iPage, sUid, sSearch, sFilters);
};

CMessageListViewModel.prototype.onStopSearchClick = function ()
{
	this.onClearSearchClick();
};

/**
 * @param {Object} oMessage
 */
CMessageListViewModel.prototype.isSavingDraft = function (oMessage)
{
	var oFolder = this.folderList().currentFolder();
	
	return (oFolder.type() === Enums.FolderTypes.Drafts) && (oMessage.uid() === App.MailCache.savingDraftUid());
};

/**
 * @param {Object} oMessage
 */
CMessageListViewModel.prototype.routeForMessage = function (oMessage)
{
	if (oMessage !== null && !this.isSavingDraft(oMessage))
	{
		var
			oFolder = this.folderList().currentFolder(),
			sFolder = this.folderList().currentFolderFullName(),
			iPage = this.oPageSwitcher.currentPage(),
			sUid = oMessage.uid(),
			sSearch = this.search()
		;
		
		if (sUid !== '')
		{
			if (bMobileApp && oFolder.type() === Enums.FolderTypes.Drafts)
			{
				App.Routing.setHash(App.Links.composeFromMessage('drafts', oMessage.folder(), oMessage.uid()));
			}
			else
			{
				this.changeRoutingForMessageList(sFolder, iPage, sUid, sSearch, this.filters());
				if (bMobileApp && App.MailCache.currentMessage() && sUid === App.MailCache.currentMessage().uid())
				{
					App.MailCache.currentMessage.valueHasMutated();
				}
			}
		}
	}
};

/**
 * @param {Object} $viewModel
 */
CMessageListViewModel.prototype.onApplyBindings = function ($viewModel)
{
	var
		self = this,
		fStopPopagation = _.bind(function (oEvent) {
			if (oEvent && oEvent.stopPropagation)
			{
				oEvent.stopPropagation();
			}
		}, this)
	;

	$('.message_list', $viewModel)
		.on('click', function ()
		{
			self.isFocused(false);
		})
		.on('click', '.message_sub_list .item .flag', function (oEvent)
		{
			self.onFlagClick(ko.dataFor(this));
			if (oEvent && oEvent.stopPropagation)
			{
				oEvent.stopPropagation();
			}
		})
		.on('dblclick', '.message_sub_list .item .flag', fStopPopagation)
		.on('click', '.message_sub_list .item .thread', fStopPopagation)
		.on('dblclick', '.message_sub_list .item .thread', fStopPopagation)
	;

	this.selector.initOnApplyBindings(
		'.message_sub_list .item',
		'.message_sub_list .item.selected',
		'.message_sub_list .item .custom_checkbox',
		$('.message_list', $viewModel),
		$('.message_list_scroll.scroll-inner', $viewModel)
	);

	this.initUploader();
};

/**
 * Puts / removes the message flag by clicking on it.
 *
 * @param {Object} oMessage
 */
CMessageListViewModel.prototype.onFlagClick = function (oMessage)
{
	if (!this.isSavingDraft(oMessage))
	{
		App.MailCache.executeGroupOperation('MessageSetFlagged', [oMessage.uid()], 'flagged', !oMessage.flagged());
	}
};

/**
 * Marks the selected messages read.
 */
CMessageListViewModel.prototype.executeMarkAsRead = function ()
{
	App.MailCache.executeGroupOperation('MessageSetSeen', this.checkedOrSelectedUids(), 'seen', true);
};

/**
 * Marks the selected messages unread.
 */
CMessageListViewModel.prototype.executeMarkAsUnread = function ()
{
	App.MailCache.executeGroupOperation('MessageSetSeen', this.checkedOrSelectedUids(), 'seen', false);
};

/**
 * Marks Read all messages in a folder.
 */
CMessageListViewModel.prototype.executeMarkAllRead = function ()
{
	App.MailCache.executeGroupOperation('MessagesSetAllSeen', [], 'seen', true);
};

/**
 * Moves the selected messages in the current folder in the specified.
 * 
 * @param {string} sToFolder
 */
CMessageListViewModel.prototype.executeMoveToFolder = function (sToFolder)
{
	App.MailCache.moveMessagesToFolder(sToFolder, this.checkedOrSelectedUids());
};

CMessageListViewModel.prototype.executeCopyToFolder = function (sToFolder)
{
	App.MailCache.copyMessagesToFolder(sToFolder, this.checkedOrSelectedUids());
};

/**
 * Calls for the selected messages delete operation. Called from the keyboard.
 * 
 * @param {Array} aMessages
 */
CMessageListViewModel.prototype.onDeletePress = function (aMessages)
{
	var aUids = _.map(aMessages, function (oMessage)
	{
		return oMessage.uid();
	});

	if (aUids.length > 0)
	{
		App.Api.deleteMessages(aUids, App);
	}
};

/**
 * Calls for the selected messages delete operation. Called by the mouse click on the delete button.
 */
CMessageListViewModel.prototype.executeDelete = function ()
{
	App.Api.deleteMessages(this.checkedOrSelectedUids(), App);
};

/**
 * Moves the selected messages from the current folder to the folder Spam.
 */
CMessageListViewModel.prototype.executeSpam = function ()
{
	var sSpamFullName = this.folderList().spamFolderFullName();

	if (this.folderList().currentFolderFullName() !== sSpamFullName)
	{
		App.MailCache.moveMessagesToFolder(sSpamFullName, this.checkedOrSelectedUids());
	}
};

/**
 * Moves the selected messages from the Spam folder to folder Inbox.
 */
CMessageListViewModel.prototype.executeNotSpam = function ()
{
	var oInbox = this.folderList().inboxFolder();

	if (oInbox && this.folderList().currentFolderFullName() !== oInbox.fullName())
	{
		App.MailCache.moveMessagesToFolder(oInbox.fullName(), this.checkedOrSelectedUids());
	}
};

CMessageListViewModel.prototype.clearAdvancedSearch = function ()
{
	this.searchInputFrom('');
	this.searchInputTo('');
	this.searchInputSubject('');
	this.searchInputText('');
	this.bAdvancedSearch(false);
	this.searchAttachmentsCheckbox(false);
	this.searchAttachments('');
	this.searchDateStart('');
	this.searchDateEnd('');
};

CMessageListViewModel.prototype.onAdvancedSearchClick = function ()
{
	this.bAdvancedSearch(!this.bAdvancedSearch());
};

CMessageListViewModel.prototype.calculateSearchStringForDescription = function ()
{
	return '<span class="part">' + Utils.encodeHtml(this.search()) + '</span>';
};

CMessageListViewModel.prototype.initUploader = function ()
{
	var self = this;

	if (this.uploaderArea())
	{
		this.oJua = new Jua({
			'action': '?/Upload/Message/',
			'name': 'jua-uploader',
			'queueSize': 2,
			'dragAndDropElement': this.uploaderArea(),
			'disableAjaxUpload': this.isPublic,
			'disableFolderDragAndDrop': this.isPublic,
			'disableDragAndDrop': this.isPublic,
			'hidden': {
				'Token': function () {
					return AppData.Token;
				},
				'AccountID': function () {
					return AppData.Accounts.currentId();
				},
				'AdditionalData':  function (oFile) {
					return JSON.stringify({
						'Folder': self.folderFullName()
					});
				}
			}
		});

		this.oJua
			.on('onDrop', _.bind(this.onFileDrop, this))
			.on('onComplete', _.bind(this.onFileUploadComplete, this))
			.on('onBodyDragEnter', _.bind(this.bDragActive, this, true))
			.on('onBodyDragLeave', _.bind(this.bDragActive, this, false))
		;
	}
};

CMessageListViewModel.prototype.onFileDrop = function (oData)
{
	if (!(oData && oData.File && oData.File.type && oData.File.type.indexOf('message/') === 0))
	{
		App.Api.showError(Utils.i18n('MAILBOX/ERROR_INCORRECT_FILE_EXTENSION'));
	}
};

CMessageListViewModel.prototype.onFileUploadComplete = function (sFileUid, bResponseReceived, oResponse)
{
	var bError = !bResponseReceived || !oResponse || oResponse.Error|| oResponse.Result.Error || false;

	if (!bError)
	{
		App.MailCache.executeCheckMail(true);
	}
	else
	{
		if (oResponse.ErrorCode && oResponse.ErrorCode === Enums.Errors.IncorrectFileExtension)
		{
			App.Api.showError(Utils.i18n('CONTACTS/ERROR_INCORRECT_FILE_EXTENSION'));
		}
		else
		{
			App.Api.showError(Utils.i18n('WARNING/ERROR_UPLOAD_FILE'));
		}
	}
};
