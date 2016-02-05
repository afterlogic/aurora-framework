'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	Utils = require('core/js/utils/Common.js'),
	
	App = require('core/js/App.js'),
	
	Popups = require('core/js/Popups.js'),
	CreateFolderPopup = require('modules/Mail/js/popups/CreateFolderPopup.js'),
	SetSystemFoldersPopup = require('modules/Mail/js/popups/SetSystemFoldersPopup.js'),
	
	MailCache = require('modules/Mail/js/Cache.js'),
	AccountList = require('modules/Mail/js/AccountList.js')
;

require('knockout-sortable');

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

CAccountFoldersPaneView.prototype.ViewTemplate = 'Mail_Settings_AccountFoldersPaneView';

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

module.exports = new CAccountFoldersPaneView();
