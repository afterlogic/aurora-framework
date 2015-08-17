
/**
 * @param {?=} oParent
 *
 * @constructor
 */ 
function CAccountFoldersViewModel(oParent)
{
	this.parent = oParent;

	this.highlighted = ko.observable(false).extend({'autoResetToFalse': 500});

	this.collection = ko.observableArray(App.MailCache.editedFolderList().collection());

	this.totalMessageCount = ko.observable(0);
	
	this.enableButtons = ko.computed(function (){
		return App.MailCache.editedFolderList().bInitialized();
	}, this);
	
	App.MailCache.editedFolderList.subscribe(function(value){
		this.collection(value.collection());
		this.setTotalMessageCount();
	}, this);
	
	this.addNewFolderCommand = Utils.createCommand(this, this.onAddNewFolderClick, this.enableButtons);
	this.systemFoldersCommand = Utils.createCommand(this, this.onSystemFoldersClick, this.enableButtons);
	
	this.showMovedWithMouseItem = ko.computed(function () {
		var oAccount = AppData.Accounts.getEdited();
		return oAccount ? !bMobileDevice && !oAccount.extensionExists('DisableFoldersManualSort') : false;
	}, this);
	
	if (AfterLogicApi.runPluginHook)
	{
		AfterLogicApi.runPluginHook('view-model-defined', [this.__name, this]);
	}
}

CAccountFoldersViewModel.prototype.__name = 'CAccountFoldersViewModel';

CAccountFoldersViewModel.prototype.onHide = function ()
{
	var iAccountId = AppData.Accounts.editedId();
	_.delay(function () {
		App.MailCache.getFolderList(iAccountId);
	}, 3000);
};

CAccountFoldersViewModel.prototype.onShow = function ()
{
	this.setTotalMessageCount();
};

CAccountFoldersViewModel.prototype.setTotalMessageCount = function ()
{
	var oFolderList = App.MailCache.editedFolderList();
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
CAccountFoldersViewModel.prototype.isChanged = function ()
{
	return false;
};

CAccountFoldersViewModel.prototype.onAddNewFolderClick = function ()
{
	App.Screens.showPopup(FolderCreatePopup);
};

CAccountFoldersViewModel.prototype.onSystemFoldersClick = function ()
{
	App.Screens.showPopup(SystemFoldersPopup);
};