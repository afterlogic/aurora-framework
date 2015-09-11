'use strict';

var
	Api = require('core/js/Api.js'),
	
	Popups = require('core/js/Popups.js'),
	ConfirmPopup = require('core/js/popups/ConfirmPopup.js')
;

/**
 * @constructor
 */
function CSettingsView()
{
	this.aTabs = ko.observableArray([]);
	this.aHiddenTabs = ko.observableArray([]);

	if (AppData.App.AllowUsersChangeInterfaceSettings)
	{
		this.aTabs.push(new CCommonSettingsViewModel());
	}
	
	if (AppData.App.AllowWebMail)
	{
		this.aTabs.push(new CEmailAccountsSettingsViewModel());
	}
	if (AppData.App.AllowUsersChangeInterfaceSettings && AppData.User.AllowCalendar)
	{
		this.aTabs.push(new CCalendarSettingsViewModel());
	}
	if (AppData.User.IsFilesSupported)
	{
		this.aTabs.push(new CCloudStorageSettingsViewModel());
	}
//	this.aTabs.push(new CServicesSettingsViewModel());
	if (AppData.User.MobileSyncEnable)
	{
		this.aTabs.push(new CMobileSyncSettingsViewModel());
	}
	if (AppData.User.OutlookSyncEnable)
	{
		this.aTabs.push(new COutLookSyncSettingsViewModel());
	}
	if (AppData.User.IsHelpdeskSupported)
	{
		this.aTabs.push(new CHelpdeskSettingsViewModel());
	}
	if (AppData.App.AllowOpenPgp)
	{
		this.aTabs.push(new CPgpSettingsViewModel());
	}
	
	if (AfterLogicApi && AfterLogicApi.getPluginsSettingsTabs)
	{
		_.each(AfterLogicApi.getPluginsSettingsTabs(), _.bind(function (ViewModelClass) {
			this.aTabs.push(new ViewModelClass());
		}, this));
	}

	this.tab = ko.observable(Enums.SettingsTab.Common);

	AppData.Accounts.currentId.subscribe(function () {
		App.Routing.lastSettingsHash(Enums.Screens.Settings);
	}, this);

	this.allowFolderListOrder = ko.computed(function () {
		var oAccount = AppData.Accounts.getEdited();
		return oAccount ? !oAccount.extensionExists('DisableFoldersManualSort') : false;
	}, this);
	
	this.folderListOrderUpdateDebounce = _.debounce(_.bind(this.folderListOrderUpdate, this), 3000);
	this.afterSortableFolderMoveBinded = _.bind(this.afterSortableFolderMove, this);
	
	if (AfterLogicApi.runPluginHook)
	{
		AfterLogicApi.runPluginHook('view-model-defined', [this.__name, this]);
	}
}

CSettingsView.prototype.__name = 'CSettingsView';

/**
 * @param {Array} aParams
 */
CSettingsView.prototype.onHide = function (aParams)
{
	var
		oCurrentViewModel = null,
		sTab = this.tab()
	;

	this.confirmSaving(sTab);
	oCurrentViewModel = this.getCurrentViewModel();
	if (oCurrentViewModel && Utils.isFunc(oCurrentViewModel.onHide))
	{
		oCurrentViewModel.onHide(aParams);
	}
	
	$html.removeClass('non-adjustable');
};

CSettingsView.prototype.onApplyBindings = function ()
{
	_.each(this.aTabs(), function(oViewModel){ 
		if (oViewModel && Utils.isFunc(oViewModel.onApplyBindings))
		{
			oViewModel.onApplyBindings();
		}
	});
};

/**
 * @param {Array} aParams
 */
CSettingsView.prototype.onShow = function (aParams)
{
	var oCurrentViewModel = this.getCurrentViewModel();
	
	if (oCurrentViewModel && Utils.isFunc(oCurrentViewModel.onShow))
	{
		oCurrentViewModel.onShow(aParams);
	}
	
	$html.addClass('non-adjustable');
};

/**
 * @param {string} sTab
 */
CSettingsView.prototype.viewTab = function (sTab)
{
	var
		oCommonTabModel = this.getViewModel(Enums.SettingsTab.Common),
		sDefaultTab = (!!oCommonTabModel) ? Enums.SettingsTab.Common : Enums.SettingsTab.EmailAccounts,
		oNewTab = this.getViewModel(sTab),
		bExistingTab = (-1 === Utils.inArray(sTab, Enums.SettingsTab))
	;
	
	sTab = (oNewTab && bExistingTab) ? sTab : sDefaultTab;

	this.tab(sTab);
};

/**
 * @param {Array} aParams
 */
CSettingsView.prototype.onRoute = function (aParams)
{
	var
		oCurrentViewModel = null,
		sTab = this.tab()
	;
	
	if (_.isArray(aParams) && aParams.length > 0)
	{
		sTab = aParams[0];
	}
	else
	{
		sTab = Enums.SettingsTab.Common;
	}
	
	if (this.tab() !== sTab)
	{
		oCurrentViewModel = this.getCurrentViewModel();
		if (oCurrentViewModel && Utils.isFunc(oCurrentViewModel.onHide))
		{
			oCurrentViewModel.onHide(aParams);
		}
		this.confirmSaving(this.tab());
	}
	
	this.viewTab(sTab);

	oCurrentViewModel = this.getCurrentViewModel();
	if (oCurrentViewModel && Utils.isFunc(oCurrentViewModel.onRoute))
	{
		oCurrentViewModel.onRoute(aParams);
	}
};

/**
 * @param {string} sTab
 */
CSettingsView.prototype.confirmSaving = function (sTab)
{
	var oCurrentViewModel = this.getViewModel(sTab),
		fAction = _.bind(function (bResult) {
			if (oCurrentViewModel)
			{
				if (bResult)
				{
					if (oCurrentViewModel.onSaveClick)
					{
						oCurrentViewModel.onSaveClick();
					}
				}
				else
				{
					if (oCurrentViewModel.init)
					{
						oCurrentViewModel.init();
					}
				}
			}
		}, this);

	if (oCurrentViewModel && Utils.isFunc(oCurrentViewModel.isChanged) && oCurrentViewModel.isChanged())
	{
		Popups.showPopup(ConfirmPopup, [Utils.i18n('SETTINGS/CONFIRM_SETTINGS_SAVE'), fAction, '', Utils.i18n('SETTINGS/BUTTON_SAVE'), Utils.i18n('SETTINGS/BUTTON_DISCARD')]);
	}
};

/**
 * @param {string} sTab
 * 
 * @return {*}
 */
CSettingsView.prototype.getViewModel = function (sTab)
{
	return _.find(this.aTabs(), function (oTabModel) {
		return oTabModel.TabName === sTab;
	});
};

/**
 * @return Object
 */
CSettingsView.prototype.getCurrentViewModel = function ()
{
	return this.getViewModel(this.tab());
};

/**
 * @param {string} sTab
 */
CSettingsView.prototype.showTab = function (sTab)
{
	App.Routing.setHash([Enums.Screens.Settings, sTab]);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CSettingsView.prototype.onResponseFolderChanges = function (oResponse, oRequest)
{
	if (!oResponse.Result)
	{
		Api.showErrorByCode(oResponse, Utils.i18n('SETTINGS/ERROR_SETTINGS_SAVING_FAILED'));
		App.MailCache.getFolderList(AppData.Accounts.editedId());
	}
};

/**
 * @param {Object} oInputFolder
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CSettingsView.prototype.onResponseFolderRename = function (oInputFolder, oResponse, oRequest)
{
	if (!oResponse || !oResponse.Result)
	{
		Api.showErrorByCode(oResponse, Utils.i18n('SETTINGS/ERROR_SETTINGS_SAVING_FAILED'));
		App.MailCache.getFolderList(AppData.Accounts.editedId());
	}
	else if (oResponse && oInputFolder && oResponse.Result && !Utils.isUnd(oResponse.Result.FullName))
	{
		oInputFolder.fullName(oResponse.Result.FullName);
		oInputFolder.fullNameHash(oResponse.Result.FullNameHash);
	}
};

/**
 * @param {Object} oFolderToDelete
 * @param {{remove:Function}} koCollection
 * @param {boolean} bOkAnswer
 */
CSettingsView.prototype.deleteFolder = function (oFolderToDelete, koCollection, bOkAnswer)
{
	var
		oParameters = {
			'Action': 'FolderDelete',
			'AccountID': AppData.Accounts.editedId(),
			'Folder': oFolderToDelete.fullName()
		}
	;
	
	if (bOkAnswer && koCollection && oFolderToDelete)
	{
		koCollection.remove(function (oFolder) {
			if (oFolderToDelete.fullName() === oFolder.fullName())
			{
				return true;
			}
			return false;
		});
		
		App.Ajax.send(oParameters, this.onResponseFolderChanges, this);
	}
};

/**
 * @param {Object} oFolder
 */
CSettingsView.prototype.onSubscribeFolderClick = function (oFolder)
{
	var
		oParameters = {
			'Action': 'FolderSubscribe',
			'AccountID': AppData.Accounts.editedId(),
			'Folder': oFolder.fullName(),
			'SetAction': oFolder.subscribed() ? 0 : 1
		}
	;

	if (oFolder && oFolder.canSubscribe())
	{
		oFolder.subscribed(!oFolder.subscribed());
		App.Ajax.send(oParameters, this.onResponseFolderChanges, this);
	}
};

/**
 * @param {Object} oFolder
 * @param {Object} oParent
 */
CSettingsView.prototype.onDeleteFolderClick = function (oFolder, oParent)
{
	var
		sWarning = Utils.i18n('SETTINGS/ACCOUNT_FOLDERS_CONFIRMATION_DELETE'),
		collection = this.getCollectionFromParent(oParent),
		fCallBack = _.bind(this.deleteFolder, this, oFolder, collection),
		oEmailAccountsViewModel = this.getViewModel(Enums.SettingsTab.EmailAccounts)
	;
	
	if (oFolder && oFolder.canDelete())
	{
		Popups.showPopup(ConfirmPopup, [sWarning, fCallBack]);
	}
	else if (oEmailAccountsViewModel)
	{
		oEmailAccountsViewModel.oAccountFolders.highlighted(true);
	}
};

/**
 * @param {Object} oFolder
 */
CSettingsView.prototype.folderEditOnEnter = function (oFolder)
{
	if (oFolder.name() !== oFolder.nameForEdit())
	{
		var
			oParameters = {
				'Action': 'FolderRename',
				'AccountID': AppData.Accounts.editedId(),
				'PrevFolderFullNameRaw': oFolder.fullName(),
				'NewFolderNameInUtf8': oFolder.nameForEdit()
			}
		;

		App.Ajax.send(oParameters, _.bind(this.onResponseFolderRename, this, oFolder), this);
		oFolder.name(oFolder.nameForEdit());
	}
	
	oFolder.edited(false);
};

/**
 * @param {Object} oFolder
 */
CSettingsView.prototype.folderEditOnEsc = function (oFolder)
{
	oFolder.edited(false);
};

/**
 * @param {Object} oParent
 *
 * @return {Function}
 */
CSettingsView.prototype.getCollectionFromParent = function (oParent)
{
	return (oParent.subfolders) ? oParent.subfolders : oParent.collection;
};

/**
 * @param {Object} oFolder
 * @param {number} iIndex
 * @param {Object} oParent
 * 
 * @return boolean
 */
CSettingsView.prototype.canMoveFolderUp = function (oFolder, iIndex, oParent)
{
	var
		collection = this.getCollectionFromParent(oParent),
		oPrevFolder = collection()[iIndex - 1],
		oPrevFolderFullName = ''
	;
	
	if (iIndex > 0 && oPrevFolder)
	{
		oPrevFolderFullName = collection()[iIndex - 1].fullName();
	}

	return (iIndex !== 0 && oFolder &&
		oFolder.fullName() !== App.MailCache.editedFolderList().inboxFolderFullName() &&
		App.MailCache.editedFolderList().inboxFolderFullName() !== oPrevFolderFullName);
};

/**
 * @param {Object} oFolder
 * @param {number} iIndex
 * @param {Object} oParent
 * 
 * @return boolean
 */
CSettingsView.prototype.canMoveFolderDown = function (oFolder, iIndex, oParent)
{
	var collection = this.getCollectionFromParent(oParent);

	return (iIndex !== collection().length - 1 &&
		oFolder.fullName() !== App.MailCache.editedFolderList().inboxFolderFullName());
};

/**
 * @param {Object} oFolder
 * @param {number} iIndex
 * @param {Object} oParent
 */
CSettingsView.prototype.moveFolderUp = function (oFolder, iIndex, oParent)
{
	var collection = this.getCollectionFromParent(oParent);
	if (this.canMoveFolderUp(oFolder, iIndex, oParent) && collection)
	{
		collection.splice(iIndex, 1);
		collection.splice(iIndex - 1, 0, oFolder);
		
		this.folderListOrderUpdateDebounce();
	}
};

/**
 * @param {Object} oFolder
 * @param {number} iIndex
 * @param {Object} oParent
 */
CSettingsView.prototype.moveFolderDown = function (oFolder, iIndex, oParent)
{
	var collection = this.getCollectionFromParent(oParent);
	if (this.canMoveFolderDown(oFolder, iIndex, oParent) && collection)
	{
		collection.splice(iIndex, 1);
		collection.splice(iIndex + 1, 0, oFolder);
		
		this.folderListOrderUpdateDebounce();
	}
};

/**
 * @param {Object} oArguments
 */
CSettingsView.prototype.afterSortableFolderMove = function (oArguments)
{
	this.folderListOrderUpdateDebounce();
};

CSettingsView.prototype.folderListOrderUpdate = function ()
{
	var
		aLinedCollection = App.MailCache.editedFolderList().repopulateLinedCollection(),
		oParameters = {
			'Action': 'FoldersUpdateOrder',
			'AccountID': AppData.Accounts.editedId(),
			'FolderList': _.map(aLinedCollection, function (oFolder) {
				return oFolder.fullName();
			})
		}
	;
	
	App.Ajax.send(oParameters, this.onResponseFolderChanges, this);
};

CSettingsView.prototype.removeTab = function (sTabName)
{
	this.aTabs(
		_.filter(this.aTabs(), function(oTab) {
			if (oTab.TabName === sTabName)
			{
				this.aHiddenTabs.push(oTab);
			}
			return oTab.TabName !== sTabName;
		}, this)
	);
};

CSettingsView.prototype.addTab = function (sTabName)
{
	this.aHiddenTabs(
		_.filter(this.aHiddenTabs(), function(oTab) {
			if (oTab.TabName === sTabName)
			{
				this.aTabs.push(oTab);
			}
			return oTab.TabName !== sTabName;
		}, this)
	);
};

module.exports = CSettingsView;