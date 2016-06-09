'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	TextUtils = require('modules/CoreClient/js/utils/Text.js'),
	Utils = require('modules/CoreClient/js/utils/Common.js'),
	
	App = require('modules/CoreClient/js/App.js'),
	
	Popups = require('modules/CoreClient/js/Popups.js'),
	ConfirmPopup = require('modules/CoreClient/js/popups/ConfirmPopup.js'),
	CreateFolderPopup = require('modules/%ModuleName%/js/popups/CreateFolderPopup.js'),
	SetSystemFoldersPopup = require('modules/%ModuleName%/js/popups/SetSystemFoldersPopup.js'),
	
	AccountList = require('modules/%ModuleName%/js/AccountList.js'),
	Ajax = require('modules/%ModuleName%/js/Ajax.js'),
	MailCache = require('modules/%ModuleName%/js/Cache.js')
;

require('modules/%ModuleName%/js/vendors/knockout-sortable.js');

/**
 * @constructor
 */ 
function CAccountFoldersPaneView()
{
	this.highlighted = ko.observable(false).extend({'autoResetToFalse': 500});

	this.collection = ko.observableArray(MailCache.editedFolderList().collection());

	this.totalMessageCount = ko.observable(0);
	
	this.enableButtons = ko.computed(function (){
		return MailCache.editedFolderList().initialized();
	}, this);
	
	MailCache.editedFolderList.subscribe(function(oFolderList) {
		this.collection(oFolderList.collection());
		this.setTotalMessageCount();
	}, this);
	
	this.addNewFolderCommand = Utils.createCommand(this, this.addNewFolder, this.enableButtons);
	this.setSystemFoldersCommand = Utils.createCommand(this, this.setSystemFolders, this.enableButtons);
	
	this.showMovedWithMouseItem = ko.computed(function () {
		var oAccount = AccountList.getEdited();
		return oAccount ? !App.isMobile() && !oAccount.extensionExists('DisableFoldersManualSort') : false;
	}, this);
	
//	if (AfterLogicApi.runPluginHook)
//	{
//		AfterLogicApi.runPluginHook('view-model-defined', [this.__name, this]);
//	}
}

CAccountFoldersPaneView.prototype.ViewTemplate = '%ModuleName%_Settings_AccountFoldersPaneView';

CAccountFoldersPaneView.prototype.hide = function (fAfterHideHandler)
{
	var iAccountId = AccountList.editedId();
	_.delay(function () {
		MailCache.getFolderList(iAccountId);
	}, 3000);
	
	if ($.isFunction(fAfterHideHandler))
	{
		fAfterHideHandler();
	}
};

CAccountFoldersPaneView.prototype.show = function ()
{
	this.setTotalMessageCount();
};

CAccountFoldersPaneView.prototype.setTotalMessageCount = function ()
{
	var oFolderList = MailCache.editedFolderList();
	if (oFolderList.iAccountId === 0)
	{
		this.totalMessageCount(0);
	}
	else
	{
		this.totalMessageCount(oFolderList.getTotalMessageCount());
		if (!oFolderList.countsCompletelyFilled())
		{
			if (oFolderList.countsCompletelyFilledSubscribtion)
			{
				oFolderList.countsCompletelyFilledSubscribtion.dispose();
				oFolderList.countsCompletelyFilledSubscribtion = null;
			}
			oFolderList.countsCompletelyFilledSubscribtion = oFolderList.countsCompletelyFilled.subscribe(function () {
				if (oFolderList.countsCompletelyFilled())
				{
					this.totalMessageCount(oFolderList.getTotalMessageCount());
					oFolderList.countsCompletelyFilledSubscribtion.dispose();
					oFolderList.countsCompletelyFilledSubscribtion = null;
				}
			}, this);
		}
	}
};

CAccountFoldersPaneView.prototype.addNewFolder = function ()
{
	Popups.showPopup(CreateFolderPopup);
};

CAccountFoldersPaneView.prototype.setSystemFolders = function ()
{
	Popups.showPopup(SetSystemFoldersPopup);
};

/**
 * @param {Object} oFolder
 */
CAccountFoldersPaneView.prototype.onSubscribeFolderClick = function (oFolder)
{
	var
		oParameters = {
			'AccountID': AccountList.editedId(),
			'Folder': oFolder.fullName(),
			'SetAction': oFolder.subscribed() ? 0 : 1
		}
	;

	if (oFolder && oFolder.canSubscribe())
	{
		oFolder.subscribed(!oFolder.subscribed());
		Ajax.send('SubscribeFolder', oParameters, this.onResponseFolderChanges, this);
	}
};

/**
 * @param {Object} oFolder
 */
CAccountFoldersPaneView.prototype.onDeleteFolderClick = function (oFolder)
{
	var
		sWarning = TextUtils.i18n('%MODULENAME%/CONFIRM_DELETE_FOLDER'),
		oFolderList = MailCache.editedFolderList(),
		fCallBack = _.bind(oFolderList.deleteFolder, oFolderList, oFolder)
	;
	
	if (oFolder && oFolder.canDelete())
	{
		Popups.showPopup(ConfirmPopup, [sWarning, fCallBack]);
	}
	else
	{
		this.highlighted(true);
	}
};

CAccountFoldersPaneView.prototype.onResponseFolderChanges = function ()
{
	//todo
};

module.exports = new CAccountFoldersPaneView();
