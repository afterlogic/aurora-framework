'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	TextUtils = require('modules/Core/js/utils/Text.js'),
	Types = require('modules/Core/js/utils/Types.js'),
	Utils = require('modules/Core/js/utils/Common.js'),
	
	Api = require('modules/Core/js/Api.js'),
	App = require('modules/Core/js/App.js'),
	CJua = require('modules/Core/js/CJua.js'),
	CSelector = require('modules/Core/js/CSelector.js'),
	ModulesManager = require('modules/Core/js/ModulesManager.js'),
	Routing = require('modules/Core/js/Routing.js'),
	Screens = require('modules/Core/js/Screens.js'),
	
	CAbstractScreenView = require('modules/Core/js/views/CAbstractScreenView.js'),
	CPageSwitcherView = require('modules/Core/js/views/CPageSwitcherView.js'),
	
	Popups = require('modules/Core/js/Popups.js'),
	ConfirmPopup = require('modules/Core/js/popups/ConfirmPopup.js'),
	
	LinksUtils = require('modules/Contacts/js/utils/Links.js'),
	
	Ajax = require('modules/Contacts/js/Ajax.js'),
	ContactsCache = require('modules/Contacts/js/Cache.js'),
	Settings = require('modules/Contacts/js/Settings.js'),
	
	CContactListItemModel = require('modules/Contacts/js/models/CContactListItemModel.js'),
	CContactModel = require('modules/Contacts/js/models/CContactModel.js'),
	CGroupModel = require('modules/Contacts/js/models/CGroupModel.js'),
	
	CImportView = require('modules/Contacts/js/views/CImportView.js')
;

/**
 * @constructor
 */
function CContactsView()
{
	CAbstractScreenView.call(this);
	
	this.browserTitle = ko.observable(TextUtils.i18n('CONTACTS/HEADING_BROWSER_TAB'));
	
	this.contactCount = ko.observable(0);
	this.uploaderArea = ko.observable(null);
	this.bDragActive = ko.observable(false);
	this.bDragActiveComp = ko.computed(function () {
		return this.bDragActive();
	}, this);

	this.sImportContactsLink = Settings.ImportContactsLink;

	this.allowSendEmails = ko.observable(true);
//	this.allowSendEmails = ko.computed(function () {
//		return AppData.App.AllowWebMail && AppData.Accounts.isCurrentAllowsMail();
//	}, this);
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
	
	this.currentStorage = ko.observable('personal');
	this.allowCreateContact = ko.computed(function () {
		return this.currentStorage() !== 'global';
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
	this.selectedGroupEmails = ko.observableArray([]);
	
	this.currentGroupId = ko.observable('');

	this.oContactModel = new CContactModel();
	this.oGroupModel = new CGroupModel();
	
	this.oImportView = new CImportView(this);

	this.selectedOldItem = ko.observable(null);
	this.selectedItem = ko.computed({
		'read': function () {
			return this.selectedContact() || this.selectedGroup() || null;
		},
		'write': function (oItem) {
			if (oItem instanceof CContactModel)
			{
				this.oImportView.visibility(false);
				this.selectedGroup(null);
				this.selectedContact(oItem);
			}
			else if (oItem instanceof CGroupModel)
			{
				this.oImportView.visibility(false);
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
				oItem.checked(oItem && 0 <= $.inArray(oItem.Id(), aGroupsId));
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
	this.oPageSwitcher = new CPageSwitcherView(0, Settings.ContactsPerPage);
	this.oPageSwitcher.currentPage.subscribe(function () {
		var
			iType = this.selectedGroupType(),
			sGroupId = (iType === Enums.ContactsGroupListType.SubGroup) ? this.currentGroupId() : ''
		;
		
		if (!this.pageSwitcherLocked())
		{
			Routing.setHash(LinksUtils.getContacts(iType, sGroupId, this.search(), this.oPageSwitcher.currentPage()));
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
		
		Routing.setHash(LinksUtils.getContacts(iType, sGroupId, this.searchInput()));
	});

	this.searchMessagesInInbox = ModulesManager.run('Mail', 'getSearchMessagesInInbox');
	this.bAllowSearchMessagesInInbox = $.isFunction(this.searchMessagesInInbox);
	this.composeMessageToAddresses = ModulesManager.run('Mail', 'getComposeMessageToAddresses');
	this.bAllowComposeMessageToAddresses = $.isFunction(this.composeMessageToAddresses);
	this.selector = new CSelector(this.collection, _.bind(this.viewContact, this), _.bind(this.deleteContact, this), this.bAllowComposeMessageToAddresses ? _.bind(this.composeMessageToContact, this) : null);

	this.checkAll = this.selector.koCheckAll();
	this.checkAllIncomplite = this.selector.koCheckAllIncomplete();

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
	
	this.isExactlyOneContactSelected = ko.computed(function () {
		return 1 === this.selector.listCheckedOrSelected().length;
	}, this);

	this.newContactCommand = Utils.createCommand(this, this.executeNewContact, this.allowCreateContact);
	this.newGroupCommand = Utils.createCommand(this, this.executeNewGroup);
	this.addContactsCommand = Utils.createCommand(this, function () {}, this.isEnableAddContacts);
	this.deleteCommand = Utils.createCommand(this, this.deleteContact, this.isEnableDeleting);
	this.shareCommand = Utils.createCommand(this, this.executeShare, this.isEnableSharing);
	this.removeFromGroupCommand = Utils.createCommand(this, this.executeRemoveFromGroup, this.isEnableRemoveContactsFromGroup);
	this.importCommand = Utils.createCommand(this, this.executeImport);
	this.exportCSVCommand = Utils.createCommand(this, this.executeCSVExport, this.isExport);
	this.exportVCFCommand = Utils.createCommand(this, this.executeVCFExport, this.isExport);
	this.saveCommand = Utils.createCommand(this, this.executeSave);
	this.updateSharedToAllCommand = Utils.createCommand(this, this.executeUpdateSharedToAll, this.isExactlyOneContactSelected);
	this.composeMessageCommand = Utils.createCommand(this, this.composeMessage, this.isCheckedOrSelected);

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
		return TextUtils.i18n('CONTACTS/INFO_SEARCH_RESULT', {
			'SEARCH': this.search()
		});
	}, this);
	
	this.bVisibleDragNDropDescription = !App.isMobile();
	this.sGroupsToolbarTemplate = App.isMobile() ? 'Contacts_Toolbar_GroupsMobileView' : 'Contacts_Toolbar_GroupsView';
	this.sContactsToolbarTemplate = App.isMobile() ? 'Contacts_Toolbar_ContactsMobileView' : 'Contacts_Toolbar_ContactsView';
	this.sBeforeContactToolbarTemplate = App.isMobile() ? 'Contacts_Toolbar_ContactMobileView' : '';
	this.sContactToolbarTemplate = App.isMobile() ? '' : 'Contacts_Toolbar_ContactView';
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

_.extendOwn(CContactsView.prototype, CAbstractScreenView.prototype);

CContactsView.prototype.ViewTemplate = 'Contacts_ContactsScreenView';

/**
 * 
 * @param {?} mValue
 * @param {Object} oElement
 */
CContactsView.prototype.groupDropdownToggle = function (mValue, oElement) {
	this.currentGroupDropdown(mValue);
};

CContactsView.prototype.gotoGroupList = function ()
{
	this.changeSelectedPanel(Enums.MobilePanel.Groups);
};

CContactsView.prototype.gotoContactList = function ()
{
	this.changeSelectedPanel(Enums.MobilePanel.Items);
	return true;
};

CContactsView.prototype.gotoViewPane = function ()
{
	this.changeSelectedPanel(Enums.MobilePanel.View);
};

CContactsView.prototype.backToContactList = function ()
{
	var
		iType = this.selectedGroupType(),
		sGroupId = (iType === Enums.ContactsGroupListType.SubGroup) ? this.currentGroupId() : ''
	;

	Routing.setHash(LinksUtils.getContacts(iType, sGroupId, this.search(), this.oPageSwitcher.currentPage()));
};

/**
 * @param {number} iPanel
 */
CContactsView.prototype.changeSelectedPanel = function (iPanel)
{
	if (App.isMobile())
	{
		this.selectedPanel(iPanel);
	}
};

/**
 * @param {Object} oData
 */
CContactsView.prototype.executeSave = function (oData)
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

			if (oData.edited())
			{
				oData.edited(false);
			}

			if (this.selectedItem())
			{
				ContactsCache.clearInfoAboutEmail(this.selectedItem().email());
			}

			if (oData.isNew())
			{
				this.selectedItem(null);
			}
			
			if (this.selectedGroupType() === Enums.ContactsGroupListType.Global || this.selectedGroupType() === Enums.ContactsGroupListType.All)
			{
				this.recivedAnimUnshare(true);
			}

			oParameters = oData.toObject();
			
			if (oData.isNew())
			{
				oParameters.SharedToAll = (Enums.ContactsGroupListType.SharedToAll === this.selectedGroupType()) ? '1' : '0';
			}
			else
			{
				oParameters.SharedToAll = oData.sharedToAll() ? '1' : '0';
			}

			Ajax.send(oData.isNew() ? 'CreateContact' : 'UpdateContact', oParameters, this.onCreateContactResponse, this);
		}
		else if (oData instanceof CGroupModel && !oData.readOnly())
		{
			this.gotoGroupList();
			
			if (oData.edited())
			{
				oData.edited(false);
			}

			if (oData.isNew() && !App.isMobile())
			{
				this.selectedItem(null);
			}

			Ajax.send(oData.isNew() ? 'CreateGroup' : 'UpdateGroup', oData.toObject(), this.onCreateGroupResponse, this);
		}
	}
	else
	{
		Screens.showError(TextUtils.i18n('CONTACTS/ERROR_EMAIL_OR_NAME_BLANK'));
	}
};

CContactsView.prototype.executeNewContact = function ()
{
	if (this.showPersonalContacts())
	{
		var oGr = this.selectedGroupInList();
		this.oContactModel.switchToNew();
		this.oContactModel.groups(oGr ? [oGr.Id()] : []);
		this.selectedItem(this.oContactModel);
		this.selector.itemSelected(null);
		this.gotoViewPane();
	}
};

CContactsView.prototype.executeNewGroup = function ()
{
	this.oGroupModel.switchToNew();
	this.selectedItem(this.oGroupModel);
	this.selector.itemSelected(null);
	this.gotoViewPane();
};

CContactsView.prototype.deleteContact = function ()
{
	var iGroupType = this.selectedGroupType();
	if (iGroupType === Enums.ContactsGroupListType.Personal || iGroupType === Enums.ContactsGroupListType.SharedToAll)
	{
		var
			aChecked = _.filter(this.selector.listCheckedOrSelected(), function (oItem) {
				return !oItem.ReadOnly();
			}),
			iCount = aChecked.length,
			sConfirmText = TextUtils.i18n('CONTACTS/CONFIRM_DELETE_CONTACTS_PLURAL', {}, null, iCount),
			fDeleteContacts = _.bind(function (bResult) {
				if (bResult)
				{
					this.deleteContacts(aChecked);
				}
			}, this)
			;

		Popups.showPopup(ConfirmPopup, [sConfirmText, fDeleteContacts]);
	}
	else if (iGroupType === Enums.ContactsGroupListType.SubGroup)
	{
		this.removeFromGroupCommand();
	}
};

CContactsView.prototype.deleteContacts = function (aChecked)
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
				ContactsCache.clearInfoAboutEmail(oContact.Email());

				if (oMainContact && !oContact.IsGroup() && !oContact.ReadOnly() && !oMainContact.readOnly() && oMainContact.idContact() === oContact.Id())
				{
					oMainContact = null;
					this.selectedContact(null);
				}
			}
		}, this);

		_.each(this.collection(), function (oContact) {
			if (-1 < $.inArray(oContact, aChecked))
			{
				oContact.deleted(true);
			}
		});

		_.delay(function () {
			self.collection.remove(function (oItem) {
				return oItem.deleted();
			});
		}, 500);

		Ajax.send('DeleteContacts', {
			'ContactsId': aContactsId.join(','),
			'SharedToAll': (Enums.ContactsGroupListType.SharedToAll === this.selectedGroupType()) ? '1' : '0'
		}, this.requestContactList, this);
		
		ContactsCache.markVcardsNonexistentByUid(aContactsId);
	}
};

CContactsView.prototype.executeRemoveFromGroup = function ()
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
			if (-1 < $.inArray(oContact, aChecked))
			{
				oContact.deleted(true);
			}
		});

		_.delay(function () {
			self.collection.remove(function (oItem) {
				return oItem.deleted();
			});
		}, 500);

		Ajax.send('RemoveContactsFromGroup', {
			'GroupId': oGroup.Id(),
			'ContactsId': aContactsId.join(',')
		}, this.requestContactList, this);
	}
};

CContactsView.prototype.executeImport = function ()
{
	this.selectedItem(null);
	this.oImportView.visibility(true);
	this.selector.itemSelected(null);
	this.selectedGroupType(Enums.ContactsGroupListType.Personal);
	this.gotoViewPane();
};

CContactsView.prototype.executeCSVExport = function ()
{
	Utils.downloadByUrl('?/Download/Contacts/DownloadContactsAsCSV/');
};

CContactsView.prototype.executeVCFExport = function ()
{
	Utils.downloadByUrl('?/Download/Contacts/DownloadContactsAsVCF/');
};

CContactsView.prototype.executeCancel = function ()
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

	this.oImportView.visibility(false);
};

/**
 * @param {Object} oGroup
 * @param {Array} aContactIds
 */
CContactsView.prototype.executeAddContactsToGroup = function (oGroup, aContactIds)
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
CContactsView.prototype.executeAddContactsToGroupId = function (sGroupId, aContactIds)
{
	if (sGroupId && _.isArray(aContactIds) && 0 < aContactIds.length)
	{
		Ajax.send('AddContactsToGroup', {
			'GroupId': sGroupId,
			'ContactsId': aContactIds
		}, this.onAddContactsToGroupResponse, this);
	}
};

CContactsView.prototype.onAddContactsToGroupResponse = function () {
	this.requestContactList();
	if (this.selector.itemSelected())
	{
		this.requestContact(this.selector.itemSelected().sId);
	}
};

/**
 * @param {Object} oGroup
 */
CContactsView.prototype.executeAddSelectedContactsToGroup = function (oGroup)
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
CContactsView.prototype.groupsInContactView = function (oContact)
{
	var
		aResult = [],
		aGroupIds = []
	;

	if (oContact && !oContact.groupsIsEmpty())
	{
		aGroupIds = oContact.groups();
		aResult = _.filter(this.groupFullCollection(), function (oItem) {
			return 0 <= $.inArray(oItem.Id(), aGroupIds);
		});
	}

	return aResult;
};

CContactsView.prototype.onShow = function ()
{
	this.selector.useKeyboardKeys(true);
	
	this.oPageSwitcher.show();
	this.oPageSwitcher.perPage(Settings.ContactsPerPage);

	if (this.oJua)
	{
		this.oJua.setDragAndDropEnabledStatus(true);
	}
};

CContactsView.prototype.onHide = function ()
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

CContactsView.prototype.onBind = function ()
{
	this.selector.initOnApplyBindings(
		'.contact_sub_list .item',
		'.contact_sub_list .selected.item',
		'.contact_sub_list .item .custom_checkbox',
		$('.contact_list', this.$viewDom),
		$('.contact_list_scroll.scroll-inner', this.$viewDom)
	);

	var self = this;

	this.$viewDom.on('click', '.content .item.add_to .dropdown_helper .item', function () {

		if ($(this).hasClass('new-group'))
		{
			self.executeNewGroup();
		}
		else
		{
			self.executeAddSelectedContactsToGroup(ko.dataFor(this));
		}
	});

	this.showPersonalContacts(-1 !== $.inArray('personal', Settings.Storages));
	this.showGlobalContacts(-1 !== $.inArray('global', Settings.Storages));
	this.showSharedToAllContacts(-1 !== $.inArray('shared', Settings.Storages));
	
	this.selectedGroupType.valueHasMutated();
	
	this.oImportView.onBind();
	this.requestGroupFullList();

	this.hotKeysBind();

	this.initUploader();
};

CContactsView.prototype.hotKeysBind = function ()
{
	var bFirstContactFlag = false;

	$(document).on('keydown', _.bind(function(ev) {
		var
			nKey = ev.keyCode,
			oFirstContact = this.collection()[0],
			bListIsFocused = this.isSearchFocused(),
			bFirstContactSelected = false
		;

		if (this.shown() && !Utils.isTextFieldFocused() && !bListIsFocused && ev && nKey === Enums.Key.s)
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

CContactsView.prototype.requestContactList = function ()
{
	this.loadingList(true);
	var sStorage = 'personal';
	switch (this.selectedGroupType())
	{
		case Enums.ContactsGroupListType.Global: sStorage = 'global'; break;
		case Enums.ContactsGroupListType.SharedToAll: sStorage = 'shared'; break;
		case Enums.ContactsGroupListType.All: sStorage = 'all'; break;
	}
	Ajax.send('GetContacts', {
		'Offset': (this.oPageSwitcher.currentPage() - 1) * Settings.ContactsPerPage,
		'Limit': Settings.ContactsPerPage,
		'SortField': this.sortType(),
		'SortOrder': this.sortOrder() ? '1' : '0',
		'Search': this.search(),
		'GroupId': this.selectedGroupInList() ? this.selectedGroupInList().Id() : '',
		'Storage': sStorage
	}, this.onGetContactsResponse, this);
};

CContactsView.prototype.requestGroupFullList = function ()
{
	Ajax.send('GetGroups', null, this.onGetGroupsResponse, this);
};

/**
 * @param {string} sUid
 */
CContactsView.prototype.requestContact = function (sUid)
{
	this.loadingViewPane(true);
	
	var
		oItem = _.find(this.collection(), function (oItm) {
			return oItm.sId === sUid;
		}),
		sStorage = 'personal'
	;
	
	if (oItem)
	{
		this.selector.itemSelected(oItem);
		if (oItem.Global())
		{
			sStorage = 'global';
		}
		else if (oItem.IsSharedToAll())
		{
			sStorage = 'shared';
		}
		Ajax.send('GetContact', {
			'ContactId': oItem.Id(),
			'Storage': sStorage
		}, this.onGetContactResponse, this);
	}
};

/**
 * @param {Object} oData
 */
CContactsView.prototype.editGroup = function (oData)
{
	var oGroup = new CGroupModel();
	oGroup.populate(oData);
	this.selectedOldItem(oGroup);
	oData.edited(true);
};

/**
 * @param {number} iType
 */
CContactsView.prototype.changeGroupType = function (iType)
{
	Routing.setHash(LinksUtils.getContacts(iType));
};

/**
 * @param {Object} oData
 */
CContactsView.prototype.onViewGroupClick = function (oData)
{
	Routing.setHash(LinksUtils.getContacts(Enums.ContactsGroupListType.SubGroup, oData.Id()));
};

/**
 * @param {Array} aParams
 */
CContactsView.prototype.onRoute = function (aParams)
{
	var
		oParams = LinksUtils.parseContacts(aParams),
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
		this.oPageSwitcher.setPage(oParams.Page, Settings.ContactsPerPage);
	}
	this.pageSwitcherLocked(false);
	if (oParams.Page !== this.oPageSwitcher.currentPage())
	{
		Routing.replaceHash(LinksUtils.getContacts(oParams.Type, oParams.GroupId, oParams.Search, this.oPageSwitcher.currentPage()));
	}
	if (this.currentPage() !== oParams.Page)
	{
		this.currentPage(oParams.Page);
		bRequestContacts = true;
	}
	
	if (-1 !== $.inArray(oParams.Type, aGroupTypes))
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
			Routing.replaceHash(LinksUtils.getContacts());
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
CContactsView.prototype.viewGroup = function (sGroupId)
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
		
		Ajax.send('GetGroupEvents', { 'GroupId': sGroupId }, this.onGetGroupEventsResponse, this);
	}
	
	return !!oGroup;
};

/**
 * @param {string} sGroupId
 */
CContactsView.prototype.deleteGroup = function (sGroupId)
{
	if (sGroupId)
	{
		Ajax.send('DeleteGroup', { 'GroupId': sGroupId }, this.requestGroupFullList, this);

		this.selectedGroupType(Enums.ContactsGroupListType.Personal);

		this.groupFullCollection.remove(function (oItem) {
			return oItem && oItem.Id() === sGroupId;
		});
	}
};

/**
 * @param {Object} oGroup
 */
CContactsView.prototype.mailGroup = function (oGroup)
{
	if (this.bAllowComposeMessageToAddresses && oGroup)
	{
		Ajax.send('GetContacts', {
			'Offset': 0,
			'Limit': 99,
			'SortField': Enums.ContactSortType.Email,
			'SortOrder': true ? '1' : '0',
			'GroupId': oGroup.idGroup()
		}, function (oResponse) {
			var
				aList = oResponse && oResponse.Result && oResponse.Result.List,
				aEmails = Types.isNonEmptyArray(aList) ? _.compact(_.map(aList, function (oRawContactItem) {
					var oContactItem = new CContactListItemModel();
					oContactItem.parse(oRawContactItem);
					return oContactItem.getFullEmail();
				})) : [],
				sEmails = aEmails.join(', ')
			;

			if (sEmails !== '')
			{
				this.composeMessageToAddresses(sEmails);
			}
		}, this);
	}
};

/**
 * @param {Object} oContact
 */
CContactsView.prototype.dragAndDropHelper = function (oContact)
{
	if (oContact)
	{
		oContact.checked(true);
	}

	var
		oSelected = this.selector.itemSelected(),
		oHelper = Utils.draggableItems(),
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
	
	$('.count-text', oHelper).text(TextUtils.i18n('CONTACTS/LABEL_DRAG_CONTACTS_PLURAL', {
		'COUNT': nCount
	}, null, nCount));

	return oHelper;
};

/**
 * @param {Object} oToGroup
 * @param {Object} oEvent
 * @param {Object} oUi
 */
CContactsView.prototype.contactsDrop = function (oToGroup, oEvent, oUi)
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

CContactsView.prototype.contactsDropToGroupType = function (iGroupType, oEvent, oUi)
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

CContactsView.prototype.searchFocus = function ()
{
	if (this.selector.useKeyboardKeys() && !Utils.isTextFieldFocused())
	{
		this.isSearchFocused(true);
	}
};

/**
 * @param {Object} oContact
 */
CContactsView.prototype.viewContact = function (oContact)
{
	if (oContact)
	{
		var
			iType = this.selectedGroupType(),
			sGroupId = (iType === Enums.ContactsGroupListType.SubGroup) ? this.currentGroupId() : ''
		;
		
		Routing.setHash(LinksUtils.getContacts(iType, sGroupId, this.search(), this.oPageSwitcher.currentPage(), oContact.sId));
	}
};

/**
 * @param {Object} oContact
 */
CContactsView.prototype.composeMessageToContact = function (oContact)
{
	var sEmail = oContact ? oContact.getFullEmail() : '';
	
	if (sEmail !== '')
	{
		this.composeMessageToAddresses(sEmail);
	}
};

CContactsView.prototype.composeMessage = function () {
	var
		aList = this.selector.listCheckedOrSelected(),
		aEmails = Types.isNonEmptyArray(aList) ? _.compact(_.map(aList, function (oItem) {
			return oItem.getFullEmail();
		})) : [],
		sEmails = aEmails.join(', ')
	;

	if (sEmails !== '')
	{
		this.composeMessageToAddresses(sEmails);
	}
};

CContactsView.prototype.onClearSearchClick = function ()
{
	// initiation empty search
	this.searchInput('');
	this.searchSubmitCommand();
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CContactsView.prototype.onGetContactResponse = function (oResponse, oRequest)
{
	var oResult = oResponse.Result;
	
	if (oResult)
	{
		var
			oObject = new CContactModel(),
			oSelected  = this.selector.itemSelected()
		;

		oObject.parse(oResult);

		if (oSelected && oSelected.Id() === oObject.idContact())
		{
			this.selectedItem(oObject);
		}
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CContactsView.prototype.onCreateContactResponse = function (oResponse, oRequest)
{
	if (oResponse && oResponse.Result)
	{
		var sReport = oResponse.Method === 'CreateContact' ?
			TextUtils.i18n('CONTACTS/REPORT_CONTACT_SUCCESSFULLY_ADDED') : TextUtils.i18n('CONTACTS/REPORT_CONTACT_SUCCESSFULLY_UPDATED');
		Utils.log('CContactsView', sReport);
		Screens.showReport(sReport);
			
		this.requestContactList();
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CContactsView.prototype.onGetContactsResponse = function (oResponse, oRequest)
{
	var oResult = oResponse.Result;
	
	if (oResult)
	{
		var
			iContactCount = Types.pInt(oResult.ContactCount),
			aNewCollection = Types.isNonEmptyArray(oResult.List) ? _.compact(_.map(oResult.List, function (oRawContactItem) {
				var oContactItem = new CContactListItemModel();
				oContactItem.parse(oRawContactItem);
				return oContactItem;
			})) : [],
			oSelected  = this.selector.itemSelected(),
			oNewSelected  = oSelected ? _.find(aNewCollection, function (oContactItem) {
				return oSelected.Id() === oContactItem.Id();
			}) : null,
			aChecked = this.selector.listChecked(),
			aCheckedIds = (aChecked && 0 < aChecked.length) ? _.map(aChecked, function (oItem) {
				return oItem.Id();
			}) : []
		;

		if (Types.isNonEmptyArray(aCheckedIds))
		{
			_.each(aNewCollection, function (oContactItem) {
				oContactItem.checked(-1 < $.inArray(oContactItem.Id(), aCheckedIds));
			});
		}

		this.collection(aNewCollection);
		this.oPageSwitcher.setCount(iContactCount);
		this.contactCount(iContactCount);

		if (oNewSelected)
		{
			this.selector.itemSelected(oNewSelected);
			this.requestContact(oNewSelected.Id());
		}

		this.selectedGroupEmails(this.selectedGroup() ? _.uniq(_.flatten(_.map(this.collection(), function (oContactItem) {
			return oContactItem.aEmails;
		}))) : []);
	}
	
	this.loadingList(false);
};

CContactsView.prototype.viewAllMails = function ()
{
	if (this.selectedGroupEmails().length > 0)
	{
		this.searchMessagesInInbox('email:' + this.selectedGroupEmails().join(','));
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CContactsView.prototype.onGetGroupsResponse = function (oResponse, oRequest)
{
	var oResult = oResponse.Result;
	
	if (oResult)
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
		
		for (iLen = oResult.length; iIndex < iLen; iIndex++)
		{
			if (oResult[iIndex])
			{
				oObject = new CContactListItemModel();
				oObject.parse(oResult[iIndex]);
				
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

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CContactsView.prototype.onCreateGroupResponse = function (oResponse, oRequest)
{
	if (oResponse && oResponse.Result)
	{
		var aCheckedIds = _.map(this.selector.listChecked(), function (oItem) {
			return [oItem.Id(), oItem.Global() ? '1' : '0'];
		});
		
		this.executeAddContactsToGroupId(Types.pString(oResponse.Result.IdGroup), aCheckedIds);

		if (!App.isMobile())
		{
			this.selectedItem(null);
			this.selector.itemSelected(null);
		}

		Utils.log('CContactsView', TextUtils.i18n('CONTACTS/REPORT_GROUP_SUCCESSFULLY_ADDED'));
		Screens.showReport(TextUtils.i18n('CONTACTS/REPORT_GROUP_SUCCESSFULLY_ADDED'));

		this.requestContactList();
		this.requestGroupFullList();
	}
};

CContactsView.prototype.executeShare = function ()
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
				ContactsCache.clearInfoAboutEmail(oContact.Email());

				if (oMainContact && !oContact.IsGroup() && !oContact.ReadOnly() && !oMainContact.readOnly() && oMainContact.idContact() === oContact.Id())
				{
					oMainContact = null;
					this.selectedContact(null);
				}
			}
		}, this);

		_.each(this.collection(), function (oContact) {
			if (-1 < $.inArray(oContact, aChecked))
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
	
		Ajax.send('UpdateShared', {
			'ContactsId': aContactsId.join(','),
			'SharedToAll': (Enums.ContactsGroupListType.SharedToAll === this.selectedGroupType()) ? '1' : '0'
		});
	}
};

/**
 * @param {Object} oItem
 */
CContactsView.prototype.requestGroup = function (oItem)
{
	this.loadingViewPane(true);
	
	if (oItem)
	{
		Ajax.send('GetGroup', {
			'GroupId': oItem.Id()
		}, this.onGetGroupResponse, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CContactsView.prototype.onGetGroupResponse = function (oResponse, oRequest)
{
	if (oResponse && oResponse.Result)
	{
		var oGroup = oResponse.Result;
		this.oGroupModel
			.idGroup(Types.pString(oGroup.IdGroup))
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

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CContactsView.prototype.onGetGroupEventsResponse = function (oResponse, oRequest)
{
	if (oResponse && oResponse.Result)
	{
		var Events = oResponse.Result;
		this.oGroupModel.events(Events);
	}
};

CContactsView.prototype.reload = function ()
{
	this.requestContactList();
};

CContactsView.prototype.initUploader = function ()
{
	var self = this;

	if (this.uploaderArea())
	{
		this.oJua = new CJua({
			'action': '?/Upload/',
			'name': 'jua-uploader',
			'queueSize': 2,
			'dragAndDropElement': this.uploaderArea(),
			'disableAjaxUpload': false,
			'disableFolderDragAndDrop': false,
			'disableDragAndDrop': false,
			'hidden': _.extendOwn({
				'Module': 'Contacts',
				'Method': 'UploadContacts',
				'Parameters':  function () {
					return JSON.stringify({
						'GroupId': self.selectedGroupType() === Enums.ContactsGroupListType.SubGroup ? self.currentGroupId() : '',
						'IsShared': self.selectedGroupType() === Enums.ContactsGroupListType.SharedToAll
					});
				}
			}, App.getCommonRequestParameters())
		});

		this.oJua
			.on('onComplete', _.bind(this.onContactUploadComplete, this))
			.on('onBodyDragEnter', _.bind(this.bDragActive, this, true))
			.on('onBodyDragLeave', _.bind(this.bDragActive, this, false))
		;
	}
};

CContactsView.prototype.onContactUploadComplete = function (sFileUid, bResponseReceived, bResponse)
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
			Api.showErrorByCode(bResponse, TextUtils.i18n('CONTACTS/ERROR_FILE_NOT_CSV_OR_VCF'));
		}
		else
		{
			Screens.showError(TextUtils.i18n('CORE/ERROR_UNKNOWN'));
		}
	}
};

CContactsView.prototype.createNewContact = function ()
{
	var oNewContactParams = ContactsCache.getNewContactParams();
	if (oNewContactParams)
	{
		this.newContactCommand();
		this.selectedItem().extented(true);
		_.each(oNewContactParams, function (sValue, sKey) {
			if(this.oContactModel[sKey])
			{
				this.oContactModel[sKey](sValue);
			}
		}, this);
	}
};

module.exports = new CContactsView();
