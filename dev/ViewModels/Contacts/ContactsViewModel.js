
/**
 * @constructor
 */
function CContactsViewModel()
{
	this.isPublic = bExtApp;

    this.contactCount = ko.observable(0);
	this.uploaderArea = ko.observable(null);
	this.bDragActive = ko.observable(false);
	this.bDragActiveComp = ko.computed(function () {
		return this.bDragActive();
	}, this);

	this.importingHelpLink = App.getHelpLink('ImportingContacts');
	this.allowSendEmails = ko.computed(function () {
		return AppData.App.AllowWebMail && AppData.Accounts.isCurrentAllowsMail();
	}, this);
	this.loadingList = ko.observable(false);
	this.preLoadingList = ko.observable(false);
	this.loadingList.subscribe(function (bLoading) {
		this.preLoadingList(bLoading);
	}, this);
	this.loadingViewPane = ko.observable(false);
	
	this.showPersonalContacts = ko.observable(false);
	this.showGlobalContacts = ko.observable(false);
	this.showSharedToAllContacts = ko.observable(false);

	this.showAllContacts = ko.computed(function () {
		return 1 < [this.showPersonalContacts() ? '1' : '',
			this.showGlobalContacts() ? '1' : '',
			this.showSharedToAllContacts() ? '1' : ''
		].join('').length;
	}, this);

	this.recivedAnimShare = ko.observable(false).extend({'autoResetToFalse': 500});
	this.recivedAnimUnshare = ko.observable(false).extend({'autoResetToFalse': 500});

	this.isGlobalGroupSelect = ko.observable(true);
	this.isAllOrSubOrGlobalGroupSelect = ko.observable(true);
	this.selectedGroupType = ko.observable(Enums.ContactsGroupListType.Personal);
	this.selectedGroupType.subscribe(function (sGroup) {
		this.isGlobalGroupSelect(sGroup === Enums.ContactsGroupListType.Global);
		this.isAllOrSubOrGlobalGroupSelect(sGroup === Enums.ContactsGroupListType.SubGroup || sGroup === Enums.ContactsGroupListType.Global || sGroup === Enums.ContactsGroupListType.All);
	}, this);

	this.selectedGroupInList = ko.observable(null);

	this.selectedGroupInList.subscribe(function () {
		var oPrev = this.selectedGroupInList();
		if (oPrev)
		{
			oPrev.selected(false);
		}
	}, this, 'beforeChange');

	this.selectedGroupInList.subscribe(function (oGroup) {
		if (oGroup && this.showPersonalContacts())
		{
			oGroup.selected(true);

			this.selectedGroupType(Enums.ContactsGroupListType.SubGroup);

			this.requestContactList();
		}
	}, this);

	this.selectedGroup = ko.observable(null);
	this.selectedContact = ko.observable(null);
	this.selectedGroupContactsList = ko.observable(null);
	
	this.currentGroupId = ko.observable('');

	this.oContactModel = new CContactModel();
	this.oGroupModel = new CGroupModel();

	this.oContactImportViewModel = new CContactsImportViewModel(this);

	this.selectedOldItem = ko.observable(null);
	this.selectedItem = ko.computed({
		'read': function () {
			return this.selectedContact() || this.selectedGroup() || null;
		},
		'write': function (oItem) {
			if (oItem instanceof CContactModel)
			{
				this.oContactImportViewModel.visibility(false);
				this.selectedGroup(null);
				this.selectedContact(oItem);
			}
			else if (oItem instanceof CGroupModel)
			{
				this.oContactImportViewModel.visibility(false);
				this.selectedContact(null);
				this.selectedGroup(oItem);
				this.currentGroupId(oItem.idGroup());
			}
			else
			{
				this.selectedGroup(null);
				this.selectedContact(null);
			}

			this.loadingViewPane(false);
		},
		'owner': this
	});

	this.sortOrder = ko.observable(true);
	this.sortType = ko.observable(Enums.ContactSortType.Name);

	this.collection = ko.observableArray([]);
	this.contactUidForRequest = ko.observable('');
	this.collection.subscribe(function () {
		if (this.collection().length > 0 && this.contactUidForRequest() !== '')
		{
			this.requestContact(this.contactUidForRequest());
			this.contactUidForRequest('');
		}
	}, this);
	
	this.isSearchFocused = ko.observable(false);
	this.searchInput = ko.observable('');
	this.search = ko.observable('');

	this.groupFullCollection = ko.observableArray([]);

	this.selectedContact.subscribe(function (oContact) {
		if (oContact)
		{
			var aGroupsId = oContact.groups();
			_.each(this.groupFullCollection(), function (oItem) {
				oItem.checked(oItem && 0 <= Utils.inArray(oItem.Id(), aGroupsId));
			});
		}
	}, this);

	this.selectedGroupType.subscribe(function (iValue) {

		if (Enums.ContactsGroupListType.All === iValue && !this.showAllContacts())
		{
			this.selectedGroupType(Enums.ContactsGroupListType.Personal);
		}
		else if (Enums.ContactsGroupListType.Personal === iValue && !this.showPersonalContacts() && this.showGlobalContacts())
		{
			this.selectedGroupType(Enums.ContactsGroupListType.Global);
		}
		else if (Enums.ContactsGroupListType.Global === iValue && !this.showGlobalContacts() && this.showPersonalContacts())
		{
			this.selectedGroupType(Enums.ContactsGroupListType.Personal);
		}
		else if (Enums.ContactsGroupListType.Personal === iValue || Enums.ContactsGroupListType.Global === iValue || 
				Enums.ContactsGroupListType.SharedToAll === iValue || Enums.ContactsGroupListType.All === iValue)
		{
			this.selectedGroupInList(null);
			this.selectedItem(null);
			this.selector.listCheckedOrSelected(false);
			this.requestContactList();
		}
	}, this);

	this.pageSwitcherLocked = ko.observable(false);
	this.oPageSwitcher = new CPageSwitcherViewModel(0, AppData.User.ContactsPerPage);
	this.oPageSwitcher.currentPage.subscribe(function () {
		var
			iType = this.selectedGroupType(),
			sGroupId = (iType === Enums.ContactsGroupListType.SubGroup) ? this.currentGroupId() : ''
		;
		
		if (!this.pageSwitcherLocked())
		{
			App.Routing.setHash(App.Links.contacts(iType, sGroupId, this.search(), this.oPageSwitcher.currentPage()));
		}
	}, this);
	this.currentPage = ko.observable(1);

	this.search.subscribe(function (sValue) {
		this.searchInput(sValue);
	}, this);

	this.searchSubmitCommand = Utils.createCommand(this, function () {
		var
			iType = this.selectedGroupType(),
			sGroupId = (iType === Enums.ContactsGroupListType.SubGroup) ? this.currentGroupId() : ''
		;
		
		App.Routing.setHash(App.Links.contacts(iType, sGroupId, this.searchInput()));
	});

	this.selector = new CSelector(this.collection, _.bind(function (oItem) {
		if (oItem)
		{
			var
				iType = this.selectedGroupType(),
				sGroupId = (iType === Enums.ContactsGroupListType.SubGroup) ? this.currentGroupId() : ''
			;
			App.Routing.setHash(App.Links.contacts(iType, sGroupId, this.search(), this.oPageSwitcher.currentPage(), oItem.sId));
		}
	}, this), _.bind(this.executeDelete, this), _.bind(this.onContactDblClick, this));

	this.checkAll = this.selector.koCheckAll();
	this.checkAllIncomplite = this.selector.koCheckAllIncomplete();

	this.allowContactsSharing = !!AppData.App.AllowContactsSharing;

	this.isCheckedOrSelected = ko.computed(function () {
		return 0 < this.selector.listCheckedOrSelected().length;
	}, this);
	this.isEnableAddContacts = this.isCheckedOrSelected;
	this.isEnableRemoveContactsFromGroup = this.isCheckedOrSelected;
	this.isEnableDeleting = this.isCheckedOrSelected;
	this.isEnableSharing = this.isCheckedOrSelected;
	this.visibleShareCommand = ko.computed(function () {
		return this.showPersonalContacts() && this.showSharedToAllContacts() && 
				(this.selectedGroupType() === Enums.ContactsGroupListType.Personal);
	}, this);
	this.visibleUnshareCommand = ko.computed(function () {
		return this.showPersonalContacts() && this.showSharedToAllContacts() && 
				(this.selectedGroupType() === Enums.ContactsGroupListType.SharedToAll);
	}, this);
	this.isSelectedGroupTypeNotGlobal = ko.computed(function () {
		return this.selectedGroupType() !== Enums.ContactsGroupListType.Global;
	}, this);

    this.isExport = ko.computed(function () {
        return this.contactCount();
    }, this);

	/*this.canBeSave = ko.computed(function () {
		var oItem = this.selectedItem();
		return oItem ? oItem.canBeSave() : false;
	}, this);*/

	this.newContactCommand = Utils.createCommand(this, this.executeNewContact, this.isSelectedGroupTypeNotGlobal);
	this.newGroupCommand = Utils.createCommand(this, this.executeNewGroup);
	this.addContactsCommand = Utils.createCommand(this, Utils.emptyFunction, this.isEnableAddContacts);
	this.deleteCommand = Utils.createCommand(this, this.executeDelete, this.isEnableDeleting);
	this.shareCommand = Utils.createCommand(this, this.executeShare, this.isEnableSharing);
	this.removeFromGroupCommand = Utils.createCommand(this, this.executeRemoveFromGroup, this.isEnableRemoveContactsFromGroup);
	this.importCommand = Utils.createCommand(this, this.executeImport);
	this.exportCSVCommand = Utils.createCommand(this, this.executeCSVExport, this.isExport);
	this.exportVCFCommand = Utils.createCommand(this, this.executeVCFExport, this.isExport);
	this.saveCommand = Utils.createCommand(this, this.executeSave);
	this.updateSharedToAllCommand = Utils.createCommand(this, this.executeUpdateSharedToAll, function () {
		return (1 === this.selector.listCheckedOrSelected().length);
	});

	this.newMessageCommand = Utils.createCommand(this, function () {
		
		var 
			aList = this.selector.listCheckedOrSelected(),
			aText = []
		;
		
		if (_.isArray(aList) && 0 < aList.length)
		{
			aText = _.map(aList, function (oItem) {
				return oItem.EmailAndName();
			});

			aText = _.compact(aText);
			App.Api.composeMessageToAddresses(aText.join(', '));
		}

	}, function () {
		return 0 < this.selector.listCheckedOrSelected().length;
	});

	this.selector.listCheckedOrSelected.subscribe(function (aList) {
		this.oGroupModel.newContactsInGroupCount(aList.length);
	}, this);

	this.isSearch = ko.computed(function () {
		return this.search() !== '';
	}, this);
	this.isEmptyList = ko.computed(function () {
		return 0 === this.collection().length;
	}, this);
	this.inGroup = ko.computed(function () {
		return Enums.ContactsGroupListType.SubGroup === this.selectedGroupType();
	}, this);

	this.searchText = ko.computed(function () {
		return Utils.i18n('CONTACTS/INFO_SEARCH_RESULT', {
			'SEARCH': this.search()
		});
	}, this);
	
	this.mobileApp = bMobileApp;
	this.selectedPanel = ko.observable(Enums.MobilePanel.Items);
	this.selectedItem.subscribe(function () {
		
		var bViewGroup = this.selectedItem() && this.selectedItem() instanceof CGroupModel &&
				!this.selectedItem().isNew();
		
		if (this.selectedItem() && !bViewGroup)
		{
			this.gotoViewPane();
		}
		else
		{
			this.gotoContactList();
		}
	}, this);
}

/**
 * 
 * @param {?} mValue
 * @param {Object} oElement
 */
CContactsViewModel.prototype.groupDropdownToggle = function (mValue, oElement) {
	this.currentGroupDropdown(mValue);
};

CContactsViewModel.prototype.gotoGroupList = function ()
{
	this.changeSelectedPanel(Enums.MobilePanel.Groups);
};

CContactsViewModel.prototype.gotoContactList = function ()
{
	this.changeSelectedPanel(Enums.MobilePanel.Items);
	return true;
};

CContactsViewModel.prototype.gotoViewPane = function ()
{
	this.changeSelectedPanel(Enums.MobilePanel.View);
};

CContactsViewModel.prototype.backToContactList = function ()
{
	var
		iType = this.selectedGroupType(),
		sGroupId = (iType === Enums.ContactsGroupListType.SubGroup) ? this.currentGroupId() : ''
	;

	App.Routing.setHash(App.Links.contacts(iType, sGroupId, this.search(), this.oPageSwitcher.currentPage()));
};

/**
 * @param {number} iPanel
 */
CContactsViewModel.prototype.changeSelectedPanel = function (iPanel)
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
 */
CContactsViewModel.prototype.executeSave = function (oData)
{
	var
		oParameters = {},
		aList = []
	;

	if (oData === this.selectedItem() && this.selectedItem().canBeSave())
	{
		if (oData instanceof CContactModel && !oData.readOnly())
		{
			_.each(this.groupFullCollection(), function (oItem) {
				if (oItem && oItem.checked())
				{
					aList.push(oItem.Id());
				}
			});

			oData.groups(aList);

			oParameters = oData.toObject();
			oParameters.Action = oData.isNew() ? 'ContactCreate' : 'ContactUpdate';
			
			if (oData.isNew())
			{
				oParameters.SharedToAll = (Enums.ContactsGroupListType.SharedToAll === this.selectedGroupType()) ? '1' : '0';
			}
			else
			{
				oParameters.SharedToAll = oData.sharedToAll() ? '1' : '0';
			}

			if (oData.edited())
			{
				oData.edited(false);
			}

			if (oData.isNew())
			{
				this.selectedItem(null);
			}
			
			if (this.selectedItem())
			{
				App.ContactsCache.clearInfoAboutEmail(this.selectedItem().email());
			}

			if (this.selectedGroupType() === Enums.ContactsGroupListType.Global || this.selectedGroupType() === Enums.ContactsGroupListType.All) {
				this.recivedAnimUnshare(true);
			}

			App.Ajax.send(oParameters, this.onContactCreateResponse, this);
		}
		else if (oData instanceof CGroupModel && !oData.readOnly())
		{
			oParameters = oData.toObject();
			oParameters.Action = oData.isNew() ? 'ContactsGroupCreate' : 'ContactsGroupUpdate';

			this.gotoGroupList();
			
			if (oData.edited())
			{
				oData.edited(false);
			}

			if (oData.isNew() && !this.mobileApp)
			{
				this.selectedItem(null);
			}

			App.Ajax.send(oParameters, this.onGroupCreateResponse, this);
		}
	}
	else
	{
		App.Api.showError(Utils.i18n('CONTACTS/ERROR_EMPTY_CONTACT'));
	}
};

CContactsViewModel.prototype.executeNewContact = function ()
{
	if (this.showPersonalContacts()) {
		var oGr = this.selectedGroupInList();
		this.oContactModel.switchToNew();
		this.oContactModel.groups(oGr ? [oGr.Id()] : []);
		this.selectedItem(this.oContactModel);
		this.selector.itemSelected(null);
		this.gotoViewPane();
	}
};

CContactsViewModel.prototype.executeNewGroup = function ()
{
	this.oGroupModel.switchToNew();
	this.selectedItem(this.oGroupModel);
	this.selector.itemSelected(null);
	this.gotoViewPane();
};

CContactsViewModel.prototype.executeDelete = function ()
{
	var iGroupType = this.selectedGroupType();
	if (iGroupType === Enums.ContactsGroupListType.Personal || iGroupType === Enums.ContactsGroupListType.SharedToAll)
	{
		var
			aChecked = _.filter(this.selector.listCheckedOrSelected(), function (oItem) {
				return !oItem.ReadOnly();
			}),
			iCount = aChecked.length,
			sConfirmText = Utils.i18n('CONTACTS/CONFIRM_DELETE_CONTACT_PLURAL', {}, null, iCount),
			fDeleteContacts = _.bind(function (bResult) {
				if (bResult) {
					this.deleteContacts(aChecked);
				}
			}, this)
			;

		App.Screens.showPopup(ConfirmPopup, [sConfirmText, fDeleteContacts]);
	}
	else if (iGroupType === Enums.ContactsGroupListType.SubGroup)
	{
		this.removeFromGroupCommand();
	}
};

CContactsViewModel.prototype.deleteContacts = function (aChecked)
{
	var
		self = this,
		oMainContact = this.selectedContact(),
		aContactsId = _.map(aChecked, function (oItem) {
			return oItem.Id();
		})
	;

	if (0 < aContactsId.length)
	{
		this.preLoadingList(true);

		_.each(aChecked, function (oContact) {
			if (oContact)
			{
				App.ContactsCache.clearInfoAboutEmail(oContact.Email());

				if (oMainContact && !oContact.IsGroup() && !oContact.ReadOnly() && !oMainContact.readOnly() && oMainContact.idContact() === oContact.Id())
				{
					oMainContact = null;
					this.selectedContact(null);
				}
			}
		}, this);

		_.each(this.collection(), function (oContact) {
			if (-1 < Utils.inArray(oContact, aChecked))
			{
				oContact.deleted(true);
			}
		});

		_.delay(function () {
			self.collection.remove(function (oItem) {
				return oItem.deleted();
			});
		}, 500);

		App.Ajax.send({
			'Action': 'ContactDelete',
			'ContactsId': aContactsId.join(','),
			'SharedToAll': (Enums.ContactsGroupListType.SharedToAll === this.selectedGroupType()) ? '1' : '0'
		}, this.requestContactList, this);
		
		App.ContactsCache.markVcardsNonexistentByUid(aContactsId);
	}
};

CContactsViewModel.prototype.executeRemoveFromGroup = function ()
{
	var
		self = this,
		oGroup = this.selectedGroupInList(),
		aChecked = this.selector.listCheckedOrSelected(),
		aContactsId = _.map(aChecked, function (oItem) {
			return oItem.ReadOnly() ? '' : oItem.Id();
		})
	;

	aContactsId = _.compact(aContactsId);

	if (oGroup && 0 < aContactsId.length)
	{
		this.preLoadingList(true);

		_.each(this.collection(), function (oContact) {
			if (-1 < Utils.inArray(oContact, aChecked))
			{
				oContact.deleted(true);
			}
		});

		_.delay(function () {
			self.collection.remove(function (oItem) {
				return oItem.deleted();
			});
		}, 500);

		App.Ajax.send({
			'Action': 'ContactsRemoveFromGroup',
			'GroupId': oGroup.Id(),
			'ContactsId': aContactsId.join(',')
		}, this.requestContactList, this);
	}
};

CContactsViewModel.prototype.executeImport = function ()
{
	this.selectedItem(null);
	this.oContactImportViewModel.visibility(true);
	this.selector.itemSelected(null);
	this.selectedGroupType(Enums.ContactsGroupListType.Personal);
	this.gotoViewPane();
};

CContactsViewModel.prototype.executeCSVExport = function ()
{
	App.Api.downloadByUrl(Utils.getExportContactsLink('csv'));
};

CContactsViewModel.prototype.executeVCFExport = function ()
{
	App.Api.downloadByUrl(Utils.getExportContactsLink('vcf'));
};

CContactsViewModel.prototype.executeCancel = function ()
{
	var
		oData = this.selectedItem()
	;

	if (oData)
	{
		if (oData instanceof CContactModel && !oData.readOnly())
		{
			if (oData.isNew())
			{
				this.selectedItem(null);
			}
			else if (oData.edited())
			{
				oData.edited(false);
			}
		}
		else if (oData instanceof CGroupModel && !oData.readOnly())
		{
			if (oData.isNew())
			{
				this.selectedItem(null);
			}
			else if (oData.edited())
			{
				this.selectedItem(this.selectedOldItem());
				oData.edited(false);
			}
			this.gotoGroupList();
		}
	}

	this.oContactImportViewModel.visibility(false);
};

/**
 * @param {Object} oGroup
 * @param {Array} aContactIds
 */
CContactsViewModel.prototype.executeAddContactsToGroup = function (oGroup, aContactIds)
{
	if (oGroup && _.isArray(aContactIds) && 0 < aContactIds.length)
	{
		oGroup.recivedAnim(true);

		this.executeAddContactsToGroupId(oGroup.Id(), aContactIds);
	}
};

/**
 * @param {string} sGroupId
 * @param {Array} aContactIds
 */
CContactsViewModel.prototype.executeAddContactsToGroupId = function (sGroupId, aContactIds)
{
	if (sGroupId && _.isArray(aContactIds) && 0 < aContactIds.length)
	{
		App.Ajax.send({
			'Action': 'ContactsAddToGroup',
			'GroupId': sGroupId,
			'ContactsId': aContactIds
		}, this.onContactsAddToGroupResponse, this);
	}
};

CContactsViewModel.prototype.onContactsAddToGroupResponse = function () {
	this.requestContactList();
	if (this.selector.itemSelected())
	{
		this.requestContact(this.selector.itemSelected().sId);
	}
};

/**
 * @param {Object} oGroup
 */
CContactsViewModel.prototype.executeAddSelectedContactsToGroup = function (oGroup)
{
	var
		aList = this.selector.listCheckedOrSelected(),
		aContactIds = []
	;

	if (oGroup && _.isArray(aList) && 0 < aList.length)
	{
		_.each(aList, function (oItem) {
			if (oItem && !oItem.IsGroup())
			{
				aContactIds.push([oItem.Id(), oItem.Global() ? '1' : '0']);
			}
		}, this);
	}

	this.executeAddContactsToGroup(oGroup, aContactIds);
};

/**
 * @param {Object} oContact
 */
CContactsViewModel.prototype.groupsInContactView = function (oContact)
{
	var
		aResult = [],
		aGroupIds = []
	;

	if (oContact && !oContact.groupsIsEmpty())
	{
		aGroupIds = oContact.groups();
		aResult = _.filter(this.groupFullCollection(), function (oItem) {
			return 0 <= Utils.inArray(oItem.Id(), aGroupIds);
		});
	}

	return aResult;
};

CContactsViewModel.prototype.onShow = function ()
{
	this.selector.useKeyboardKeys(true);
	
	this.oPageSwitcher.show();
	this.oPageSwitcher.perPage(AppData.User.ContactsPerPage);

	if (this.oJua)
	{
		this.oJua.setDragAndDropEnabledStatus(true);
	}
};

CContactsViewModel.prototype.onHide = function ()
{
	this.selector.listCheckedOrSelected(false);
	this.selector.useKeyboardKeys(false);
	this.selectedItem(null);
	
	this.oPageSwitcher.hide();

	if (this.oJua)
	{
		this.oJua.setDragAndDropEnabledStatus(false);
	}
};

CContactsViewModel.prototype.onApplyBindings = function ()
{
	this.selector.initOnApplyBindings(
		'.contact_sub_list .item',
		'.contact_sub_list .selected.item',
		'.contact_sub_list .item .custom_checkbox',
		$('.contact_list', this.$viewModel),
		$('.contact_list_scroll.scroll-inner', this.$viewModel)
	);

	var self = this;

	this.$viewModel.on('click', '.content .item.add_to .dropdown_helper .item', function () {

		if ($(this).hasClass('new-group'))
		{
			self.executeNewGroup();
		}
		else
		{
			self.executeAddSelectedContactsToGroup(ko.dataFor(this));
		}
	});

	this.showPersonalContacts(!!AppData.User.ShowPersonalContacts);
	this.showGlobalContacts(!!AppData.User.ShowGlobalContacts);
	this.showSharedToAllContacts(!!AppData.App.AllowContactsSharing);
	
	this.selectedGroupType.valueHasMutated();
	
	this.oContactImportViewModel.onApplyBindings(this.$viewModel);
	this.requestGroupFullList();

	this.hotKeysBind();

	this.initUploader();
};

CContactsViewModel.prototype.hotKeysBind = function ()
{
	var bFirstContactFlag = false;

	$(document).on('keydown', _.bind(function(ev) {
		var
			nKey = ev.keyCode,
			oFirstContact = this.collection()[0],
			bListIsFocused = this.isSearchFocused(),
			bFirstContactSelected = false,
			bIsContactsScreen = App.Screens.currentScreen() === Enums.Screens.Contacts
		;

		if (bIsContactsScreen && !Utils.isTextFieldFocused() && !bListIsFocused && ev && nKey === Enums.Key.s)
		{
			ev.preventDefault();
			this.searchFocus();
		}

		else if (oFirstContact)
		{
			bFirstContactSelected = oFirstContact.selected();

			if (oFirstContact && bListIsFocused && ev && nKey === Enums.Key.Down)
			{
				this.isSearchFocused(false);
				this.selector.itemSelected(oFirstContact);

				bFirstContactFlag = true;
			}
			else if (!bListIsFocused && bFirstContactFlag && bFirstContactSelected && ev && nKey === Enums.Key.Up)
			{
				this.isSearchFocused(true);
				this.selector.itemSelected(false);
				
				bFirstContactFlag = false;
			}
			else if (bFirstContactSelected)
			{
				bFirstContactFlag = true;
			}
			else if (!bFirstContactSelected)
			{
				bFirstContactFlag = false;
			}
		}
	}, this));
};

CContactsViewModel.prototype.requestContactList = function ()
{
	this.loadingList(true);

	App.Ajax.send({
		'Action': (Enums.ContactsGroupListType.Global === this.selectedGroupType()) ? 'ContactGlobalList' : 'ContactList',
		'Offset': (this.oPageSwitcher.currentPage() - 1) * AppData.User.ContactsPerPage,
		'Limit': AppData.User.ContactsPerPage,
		'SortField': this.sortType(),
		'SortOrder': this.sortOrder() ? '1' : '0',
		'Search': this.search(),
		'GroupId': this.selectedGroupInList() ? this.selectedGroupInList().Id() : '',
		'SharedToAll': (Enums.ContactsGroupListType.SharedToAll === this.selectedGroupType()) ? '1' : '0',
		'SharedToAll1': (Enums.ContactsGroupListType.SharedToAll === this.selectedGroupType()) ? '1' : '0',
		'All': (Enums.ContactsGroupListType.All === this.selectedGroupType()) ? '1' : '0'
	}, this.onContactListResponse, this);
};

CContactsViewModel.prototype.requestGroupFullList = function ()
{
	App.Ajax.send({
		'Action': 'ContactsGroupFullList'
	}, this.onGroupListResponse, this);
};

/**
 * @param {string} sUid
 */
CContactsViewModel.prototype.requestContact = function (sUid)
{
	this.loadingViewPane(true);
	
	var oItem = _.find(this.collection(), function (oItm) {
		return oItm.sId === sUid;
	});
	
	if (oItem)
	{
		this.selector.itemSelected(oItem);
		App.Ajax.send({
			'Action': oItem.Global() ? 'ContactGlobal' : 'ContactGet',
			'ContactId': oItem.Id(),
			'SharedToAll': oItem.IsSharedToAll() ? '1' : '0'
		}, this.onContactGetResponse, this);
	}
};

/**
 * @param {string} sItemId
 * @param {boolean} bGlobal
 */
CContactsViewModel.prototype.requestContactById = function (sItemId, bGlobal)
{
	this.loadingViewPane(true);

	if (sItemId)
	{
		App.Ajax.send({
			'Action': bGlobal ? 'ContactGlobal' : 'ContactGet',
			'ContactId': sItemId
		}, this.onContactGetResponse, this);
	}
};

/**
 * @param {Object} oData
 */
CContactsViewModel.prototype.editGroup = function (oData)
{
	var oGroup = new CGroupModel();
	oGroup.populate(oData);
	this.selectedOldItem(oGroup);
	oData.edited(true);
};

/**
 * @param {number} iType
 */
CContactsViewModel.prototype.changeGroupType = function (iType)
{
	App.Routing.setHash(App.Links.contacts(iType));
};

/**
 * @param {Object} oData
 */
CContactsViewModel.prototype.onViewGroupClick = function (oData)
{
	App.Routing.setHash(App.Links.contacts(Enums.ContactsGroupListType.SubGroup, oData.Id()));
};

/**
 * @param {Array} aParams
 */
CContactsViewModel.prototype.onRoute = function (aParams)
{
	var
		oParams = App.Links.parseContacts(aParams),
		aGroupTypes = [Enums.ContactsGroupListType.Personal, Enums.ContactsGroupListType.SharedToAll, Enums.ContactsGroupListType.Global, Enums.ContactsGroupListType.All],
		sCurrentGroupId = (this.selectedGroupType() === Enums.ContactsGroupListType.SubGroup) ?  this.currentGroupId() : '',
		bGroupOrSearchChanged = this.selectedGroupType() !== oParams.Type || sCurrentGroupId !== oParams.GroupId || this.search() !== oParams.Search,
		bGroupFound = true,
		bRequestContacts = false
	;
	
	this.pageSwitcherLocked(true);
	if (bGroupOrSearchChanged)
	{
		this.oPageSwitcher.clear();
	}
	else
	{
		this.oPageSwitcher.setPage(oParams.Page, AppData.User.ContactsPerPage);
	}
	this.pageSwitcherLocked(false);
	if (oParams.Page !== this.oPageSwitcher.currentPage())
	{
		App.Routing.replaceHash(App.Links.contacts(oParams.Type, oParams.GroupId, oParams.Search, this.oPageSwitcher.currentPage()));
	}
	if (this.currentPage() !== oParams.Page)
	{
		this.currentPage(oParams.Page);
		bRequestContacts = true;
	}
	
	if (-1 !== Utils.inArray(oParams.Type, aGroupTypes))
	{
		this.selectedGroupType(oParams.Type);
	}
	else if (sCurrentGroupId !== oParams.GroupId || oParams.Uid === '')
	{
		bGroupFound = this.viewGroup(oParams.GroupId);
		if (bGroupFound)
		{
			bRequestContacts = false;
		}
		else
		{
			App.Routing.replaceHash(App.Links.contacts());
		}
	}
	
	if (this.search() !== oParams.Search)
	{
		this.search(oParams.Search);
		bRequestContacts = true;
	}
	
	this.contactUidForRequest('');
	if (oParams.Uid)		
	{
		if (this.collection().length === 0)
		{
			this.contactUidForRequest(oParams.Uid);
		}
		else
		{
			this.requestContact(oParams.Uid);
		}
	}
	else
	{
		this.selector.itemSelected(null);
		this.gotoContactList();
	}

	if (bRequestContacts)
	{
		this.requestContactList();
	}

	this.createNewContact();
};

/**
 * @param {string} sGroupId
 */
CContactsViewModel.prototype.viewGroup = function (sGroupId)
{
	var
		oGroup = _.find(this.groupFullCollection(), function (oItem) {
			return oItem && oItem.Id() === sGroupId;
		})
	;

	if (oGroup)
	{
		this.oGroupModel.clear();
		this.oGroupModel
			.idGroup(oGroup.Id())
			.name(oGroup.Name())
		;
		if (oGroup.IsOrganization())
		{
			this.requestGroup(oGroup);
		}

		this.selectedGroupInList(oGroup);
		this.selectedItem(this.oGroupModel);
		this.selector.itemSelected(null);
		this.selector.listCheckedOrSelected(false);
		
		App.Ajax.send({
			'Action': 'ContactsGroupEvents',
			'GroupId': sGroupId
		}, this.onGroupEventsResponse, this);
	}
	
	return !!oGroup;
};

/**
 * @param {string} sGroupId
 */
CContactsViewModel.prototype.deleteGroup = function (sGroupId)
{
	if (sGroupId)
	{
		App.Ajax.send({
			'Action': 'ContactsGroupDelete',
			'GroupId': sGroupId
		}, this.requestGroupFullList, this);

		this.selectedGroupType(Enums.ContactsGroupListType.Personal);

		this.groupFullCollection.remove(function (oItem) {
			return oItem && oItem.Id() === sGroupId;
		});
	}
};

/**
 * @param {Object} oGroup
 */
CContactsViewModel.prototype.mailGroup = function (oGroup)
{
	if (oGroup)
	{
		App.Ajax.send({
			'Action': 'ContactList',
			'Offset': 0,
			'Limit': 99,
			'SortField': Enums.ContactSortType.Email,
			'SortOrder': true ? '1' : '0',
			'GroupId': oGroup.idGroup()
		}, function (oData) {

			if (oData && oData['Result'] && oData['Result']['List'])
			{
				var
					iIndex = 0,
					iLen = 0,
					aText = [],
					oObject = null,
					aList = [],
					aResultList = oData['Result']['List']
					;

				for (iLen = aResultList.length; iIndex < iLen; iIndex++)
				{
					if (aResultList[iIndex] && 'Object/CContactListItem' === Utils.pExport(aResultList[iIndex], '@Object', ''))
					{
						oObject = new CContactListItemModel();
						oObject.parse(aResultList[iIndex]);

						aList.push(oObject);
					}
				}

				aText = _.map(aList, function (oItem) {
					return oItem.EmailAndName();
				});

				aText = _.compact(aText);
				App.Api.composeMessageToAddresses(aText.join(', '));
			}

		}, this);
	}
};

/**
 * @param {Object} oContact
 */
CContactsViewModel.prototype.dragAndDropHelper = function (oContact)
{
	if (oContact)
	{
		oContact.checked(true);
	}

	var
		oSelected = this.selector.itemSelected(),
		oHelper = Utils.draggableMessages(),
		nCount = this.selector.listCheckedOrSelected().length,
		aUids = 0 < nCount ? _.map(this.selector.listCheckedOrSelected(), function (oItem) {
			return [oItem.Id(), oItem.Global() ? '1' : '0'];
		}) : []
	;

	if (oSelected && !oSelected.checked())
	{
		oSelected.checked(true);
	}

	oHelper.data('p7-contatcs-type', this.selectedGroupType());
	oHelper.data('p7-contatcs-uids', aUids);
	
	$('.count-text', oHelper).text(Utils.i18n('CONTACTS/DRAG_TEXT_PLURAL', {
		'COUNT': nCount
	}, null, nCount));

	return oHelper;
};

/**
 * @param {Object} oToGroup
 * @param {Object} oEvent
 * @param {Object} oUi
 */
CContactsViewModel.prototype.contactsDrop = function (oToGroup, oEvent, oUi)
{
	if (oToGroup)
	{
		var
			oHelper = oUi && oUi.helper ? oUi.helper : null,
			aUids = oHelper ? oHelper.data('p7-contatcs-uids') : null
		;

		if (null !== aUids)
		{
			Utils.uiDropHelperAnim(oEvent, oUi);
			this.executeAddContactsToGroup(oToGroup, aUids);
		}
	}
};

CContactsViewModel.prototype.contactsDropToGroupType = function (iGroupType, oEvent, oUi)
{
	var
		oHelper = oUi && oUi.helper ? oUi.helper : null,
		iType = oHelper ? oHelper.data('p7-contatcs-type') : null,
		aUids = oHelper ? oHelper.data('p7-contatcs-uids') : null
	;

	if (iGroupType !== iType)
	{
		if (null !== iType && null !== aUids)
		{
			Utils.uiDropHelperAnim(oEvent, oUi);
			this.executeShare();
		}
	}
};

CContactsViewModel.prototype.searchFocus = function ()
{
	if (this.selector.useKeyboardKeys() && !Utils.isTextFieldFocused())
	{
		this.isSearchFocused(true);
	}
};

CContactsViewModel.prototype.onContactDblClick = function ()
{
	var oContact = this.selectedContact();
	if (oContact)
	{
		App.Api.composeMessageToAddresses(oContact.email());
	}
};

CContactsViewModel.prototype.onClearSearchClick = function ()
{
	// initiation empty search
	this.searchInput('');
	this.searchSubmitCommand();
};

/**
 * @param {Object} oResult
 * @param {Object} oRequest
 */
CContactsViewModel.prototype.onContactGetResponse = function (oResult, oRequest)
{
	if (oResult && oResult.Action && oResult.Result)
	{
		var
			oObject = new CContactModel(),
			oSelected  = this.selector.itemSelected()
			;

		oObject.parse(oResult.Result);

		if (oSelected && oSelected.Id() === oObject.idContact())
		{
			this.selectedItem(oObject);
		}
	}
};

/**
 * @param {Object} oResult
 * @param {Object} oRequest
 */
CContactsViewModel.prototype.onContactCreateResponse = function (oResult, oRequest)
{
	if (oResult && oResult.Action && oResult.Result)
	{
		App.Api.showReport(oResult.Action === 'ContactCreate' ?
			Utils.i18n('CONTACTS/REPORT_CONTACT_SUCCESSFULLY_ADDED') : Utils.i18n('CONTACTS/REPORT_CONTACT_SUCCESSFULLY_UPDATED'));
			
		this.requestContactList();
	}
};

CContactsViewModel.prototype.onContactListResponse = function (oResult, oRequest)
{
	if (oResult && oResult.Action && oResult.Result)
	{
		var
			iIndex = 0,
			iLen = 0,
			aList = [],
			oSelected  = this.selector.itemSelected(),
			oSubSelected  = null,
			aChecked = this.selector.listChecked(),
			aCheckedIds = (aChecked && 0 < aChecked.length) ? _.map(aChecked, function (oItem) {
				return oItem.Id();
			}) : [],
			oObject = null
			;

		for (iLen = oResult.Result.List.length; iIndex < iLen; iIndex++)
		{
			if (oResult.Result.List[iIndex] && 'Object/CContactListItem' === Utils.pExport(oResult.Result.List[iIndex], '@Object', ''))
			{
				oObject = new CContactListItemModel();
				oObject.parse(oResult.Result.List[iIndex]);

				aList.push(oObject);
			}
		}

		if (oSelected)
		{
			oSubSelected = _.find(aList, function (oItem) {
				return oSelected.Id() === oItem.Id();
			});
		}

		if (aCheckedIds && 0 < aCheckedIds.length)
		{
			_.each(aList, function (oItem) {
				oItem.checked(-1 < Utils.inArray(oItem.Id(), aCheckedIds));
			});
		}

		this.collection(aList);
		this.loadingList(false);
		this.oPageSwitcher.setCount(Utils.pInt(oResult.Result.ContactCount));

		if (oSubSelected)
		{
			this.selector.itemSelected(oSubSelected);
			this.requestContact(oSelected.Id());
		}

		this.selectedGroupContactsList(oResult.Result.List);

		if (oSelected) {
			this.requestContact(oSelected.Id());
		}

        this.contactCount(oResult.Result.ContactCount);
	}
};

CContactsViewModel.prototype.viewAllMails = function ()
{
	var
		aContactsList = this.selectedGroupContactsList(),
		sSearchRequest = 'email:'
	;

	if (aContactsList)
	{
		_.each(aContactsList, function(oContact, iContactKey)
		{
			_.each(oContact.Emails, function(sEmail, iEmailKey)
			{
				sSearchRequest = sSearchRequest + sEmail + ',';
			});
		});

		App.MailCache.searchMessagesInInbox(sSearchRequest);
	}
};

CContactsViewModel.prototype.onGroupListResponse = function (oResult, oRequest)
{
	if (oResult && oResult.Action && oResult.Result)
	{
		var
			iIndex = 0,
			iLen = 0,
			aList = [],
			oSelected  = _.find(this.groupFullCollection(), function (oItem) {
				return oItem.selected();
			}),
			oObject = null
			;

		this.groupFullCollection(aList);
		
		for (iLen = oResult.Result.length; iIndex < iLen; iIndex++)
		{
			if (oResult.Result[iIndex] && 'Object/CContactListItem' === Utils.pExport(oResult.Result[iIndex], '@Object', ''))
			{
				oObject = new CContactListItemModel();
				oObject.parse(oResult.Result[iIndex]);

				if (oObject.IsGroup())
				{
					if (oSelected && oSelected.Id() === oObject.Id())
					{
						this.selectedGroupInList(oObject);
					}

					aList.push(oObject);
				}
			}
		}

		this.groupFullCollection(aList);
	}
};

CContactsViewModel.prototype.onGroupCreateResponse = function (oResult, oRequest)
{
	if (oResult && oResult.Action && oResult.Result)
	{
		var aCheckedIds = _.map(this.selector.listChecked(), function (oItem) {
			return [oItem.Id(), oItem.Global() ? '1' : '0'];
		});
		
		this.executeAddContactsToGroupId(
			Utils.pString(oResult.Result.IdGroup),
			aCheckedIds
		);

		if (!this.mobileApp)
		{
			this.selectedItem(null);
			this.selector.itemSelected(null);
		}

		App.Api.showReport(Utils.i18n('CONTACTS/REPORT_GROUP_SUCCESSFULLY_ADDED'));

		this.requestContactList();
		this.requestGroupFullList();
	}
};

CContactsViewModel.prototype.executeShare = function ()
{
	var
		self = this,
		aChecked = this.selector.listCheckedOrSelected(),
		oMainContact = this.selectedContact(),
		aContactsId = _.map(aChecked, function (oItem) {
			return oItem.ReadOnly() ? '' : oItem.Id();
		})
	;

	aContactsId = _.compact(aContactsId);

	if (0 < aContactsId.length)
	{
		_.each(aChecked, function (oContact) {
			if (oContact)
			{
				App.ContactsCache.clearInfoAboutEmail(oContact.Email());

				if (oMainContact && !oContact.IsGroup() && !oContact.ReadOnly() && !oMainContact.readOnly() && oMainContact.idContact() === oContact.Id())
				{
					oMainContact = null;
					this.selectedContact(null);
				}
			}
		}, this);

		_.each(this.collection(), function (oContact) {
			if (-1 < Utils.inArray(oContact, aChecked))
			{
				oContact.deleted(true);
			}
		});

		_.delay(function () {
			self.collection.remove(function (oItem) {
				return oItem.deleted();
			});
		}, 500);

		if (Enums.ContactsGroupListType.SharedToAll === this.selectedGroupType())
		{
			this.recivedAnimUnshare(true);
		}
		else
		{
			this.recivedAnimShare(true);
		}
	
		App.Ajax.send({
			'Action': 'ContactUpdateSharedToAll',
			'ContactsId': aContactsId.join(','),
			'SharedToAll': (Enums.ContactsGroupListType.SharedToAll === this.selectedGroupType()) ? '1' : '0'
		}, this.onContactUpdateSharedToAllResponse, this);
	}
};

CContactsViewModel.prototype.onContactUpdateSharedToAllResponse = function (oResult, oRequest)
{
	// TODO:
};

/**
 * @param {Object} oItem
 */
CContactsViewModel.prototype.requestGroup = function (oItem)
{
	this.loadingViewPane(true);
	
	if (oItem)
	{
		App.Ajax.send({
			'Action': 'ContactsGroup',
			'GroupId': oItem.Id()
		}, this.onGroupResponse, this);
	}
};

CContactsViewModel.prototype.onGroupResponse = function (oResult, oRequest)
{
	if (oResult && oResult.Action && oResult.Result)
	{
		var oGroup = oResult.Result;
		this.oGroupModel
			.idGroup(Utils.pString(oGroup.IdGroup))
			.name(oGroup.Name)
			.isOrganization(oGroup.IsOrganization)
			.company(oGroup.Company)
			.country(oGroup.Country)
			.state(oGroup.State)
			.city(oGroup.City)
			.street(oGroup.Street)
			.zip(oGroup.Zip)
			.phone(oGroup.Phone)
			.fax(oGroup.Fax)
			.email(oGroup.Email)
			.web(oGroup.Web)
		;
	}
};

CContactsViewModel.prototype.onGroupEventsResponse = function (oResult, oRequest)
{
	if (oResult && oResult.Action && oResult.Result)
	{
		var Events = oResult.Result;
		this.oGroupModel.events(Events);
	}
};

CContactsViewModel.prototype.reload = function ()
{
	this.requestContactList();
};

CContactsViewModel.prototype.initUploader = function ()
{
	var self = this;

	if (this.uploaderArea())
	{
		this.oJua = new Jua({
			'action': '?/Upload/Contacts/',
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
						'GroupId': self.selectedGroupType() === Enums.ContactsGroupListType.SubGroup ? self.currentGroupId() : '',
						'IsShared': self.selectedGroupType() === Enums.ContactsGroupListType.SharedToAll
					});
				}
			}
		});

		this.oJua
			.on('onComplete', _.bind(this.onContactUploadComplete, this))
			.on('onBodyDragEnter', _.bind(this.bDragActive, this, true))
			.on('onBodyDragLeave', _.bind(this.bDragActive, this, false))
		;
	}
};

CContactsViewModel.prototype.onContactUploadComplete = function (sFileUid, bResponseReceived, bResponse)
{
	var bError = !bResponseReceived || !bResponse || bResponse.Error|| bResponse.Result.Error || false;

	if (!bError)
	{
		this.reload();
	}
	else
	{
		if (bResponse.ErrorCode)
		{
			App.Api.showErrorByCode(bResponse, Utils.i18n('The file must have .CSV or .VCF extension.'));
		}
		else
		{
			App.Api.showError(Utils.i18n('WARNING/UNKNOWN_ERROR'));
		}
	}
};

CContactsViewModel.prototype.createNewContact = function ()
{
	if (App.ContactsCache.newContactParams)
	{
		this.newContactCommand();
		this.selectedItem().extented(true);
		_.each(App.ContactsCache.newContactParams, function (sValue, sKey) {
			if(this.oContactModel[sKey])
			{
				this.oContactModel[sKey](sValue);
			}
		}, this);
		App.ContactsCache.newContactParams = null;
	}
};
