'use strict';

var
	ko = require('knockout'),
	
	Utils = require('core/js/utils/Common.js'),
	
	App = require('core/js/App.js'),
	
	Popups = require('core/js/Popups.js'),
	FolderCreatePopup,
	SystemFoldersPopup,
	
	MailCache = require('modules/Mail/js/Cache.js'),
	Accounts = require('modules/Mail/js/AccountList.js')
;

require('knockout-sortable');

/**
 * @param {?=} oParent
 *
 * @constructor
 */ 
function CAccountFoldersPageView(oParent)
{
	this.parent = oParent;

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
	
	this.addNewFolderCommand = Utils.createCommand(this, this.onAddNewFolderClick, this.enableButtons);
	this.systemFoldersCommand = Utils.createCommand(this, this.onSystemFoldersClick, this.enableButtons);
	
	this.showMovedWithMouseItem = ko.computed(function () {
		var oAccount = Accounts.getEdited();
		return oAccount ? !App.isMobile() && !oAccount.extensionExists('DisableFoldersManualSort') : false;
	}, this);
	
//	if (AfterLogicApi.runPluginHook)
//	{
//		AfterLogicApi.runPluginHook('view-model-defined', [this.__name, this]);
//	}
}

CAccountFoldersPageView.prototype.ViewTemplate = 'Mail_Settings_AccountFoldersPageView';

CAccountFoldersPageView.prototype.onHide = function ()
{
	var iAccountId = Accounts.editedId();
	_.delay(function () {
		MailCache.getFolderList(iAccountId);
	}, 3000);
};

CAccountFoldersPageView.prototype.onShow = function ()
{
	this.setTotalMessageCount();
};

CAccountFoldersPageView.prototype.setTotalMessageCount = function ()
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

/**
 * @return {boolean}
 */
CAccountFoldersPageView.prototype.isChanged = function ()
{
	return false;
};

CAccountFoldersPageView.prototype.onAddNewFolderClick = function ()
{
	Popups.showPopup(FolderCreatePopup);
};

CAccountFoldersPageView.prototype.onSystemFoldersClick = function ()
{
	Popups.showPopup(SystemFoldersPopup);
};

module.exports = new CAccountFoldersPageView();