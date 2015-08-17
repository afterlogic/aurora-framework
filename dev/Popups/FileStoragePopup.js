/**
 * @constructor
 */
function FileStoragePopup()
{
	this.fileStorageViewModel = new CFileStorageViewModel(true);
	this.fileStorageViewModel.onSelectClickPopupBinded = _.bind(this.onSelectClick, this);
	this.fCallback = null;
}

/**
 * @param {Function} fCallback
 */
FileStoragePopup.prototype.onShow = function (fCallback)
{
	if (Utils.isFunc(fCallback))
	{
		this.fCallback = fCallback;
	}
	this.fileStorageViewModel.onShow();
};

/**
 * @param {Object} $viewModel
 */
FileStoragePopup.prototype.onApplyBindings = function ($viewModel)
{
	this.fileStorageViewModel.onApplyBindings($viewModel);
};

/**
 * @return {string}
 */
FileStoragePopup.prototype.popupTemplate = function ()
{
	return 'Popups_FileStorage_FileStoragePopupViewModel';
};

FileStoragePopup.prototype.onSelectClick = function ()
{
	var
		aItems = this.fileStorageViewModel.selector.listCheckedAndSelected(),
		aFileItems = _.filter(aItems, function (oItem) {
			return !oItem.isFolder();
		}, this)
	;
	
	if (this.fCallback)
	{
		this.fCallback(aFileItems);
	}
	this.closeCommand();
};

FileStoragePopup.prototype.onCancelClick = function ()
{
	this.closeCommand();
};
