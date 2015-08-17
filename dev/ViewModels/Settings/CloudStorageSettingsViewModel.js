/**
 * @constructor
 */
function CCloudStorageSettingsViewModel()
{
	this.loading = ko.observable(false);
	this.allowFiles = AppData.User.IsFilesSupported;	

	this.enableFiles = ko.observable(AppData.User.filesEnable());
	
	this.firstState = this.getState();
	
	if (AfterLogicApi.runPluginHook)
	{
		AfterLogicApi.runPluginHook('view-model-defined', [this.__name, this]);
	}	
}

CCloudStorageSettingsViewModel.prototype.__name = 'CCloudStorageSettingsViewModel';

CCloudStorageSettingsViewModel.prototype.TemplateName = 'Settings_CloudStorageSettingsViewModel';

CCloudStorageSettingsViewModel.prototype.TabName = Enums.SettingsTab.CloudStorage;

CCloudStorageSettingsViewModel.prototype.TabTitle = Utils.i18n('SETTINGS/TAB_CLOUD_STORAGE');

CCloudStorageSettingsViewModel.prototype.getState = function()
{
	var sState = [
		this.enableFiles()
	];
	return sState.join(':');
};

CCloudStorageSettingsViewModel.prototype.updateFirstState = function()
{
	this.firstState = this.getState();
};

CCloudStorageSettingsViewModel.prototype.isChanged = function()
{
	if (this.firstState && this.getState() !== this.firstState)
	{
		return true;
	}
	else
	{
		return false;
	}
};

CCloudStorageSettingsViewModel.prototype.init = function ()
{
	this.enableFiles(AppData.User.filesEnable());
};

CCloudStorageSettingsViewModel.prototype.onApplyBindings = function ()
{
};

CCloudStorageSettingsViewModel.prototype.onShow = function ()
{
};

CCloudStorageSettingsViewModel.prototype.onRoute = function ()
{
};

CCloudStorageSettingsViewModel.prototype.onSaveClick = function ()
{
	var
		oParameters = {
			'Action': 'UserSettingsUpdate',
			'FilesEnable': this.enableFiles() ? '1' : '0'
		}
	;

	this.loading(true);
	this.updateFirstState();
	App.Ajax.send(oParameters, this.onResponse, this);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CCloudStorageSettingsViewModel.prototype.onResponse = function (oResponse, oRequest)
{
	this.loading(false);

	if (oResponse.Result === false)
	{
		App.Api.showErrorByCode(oResponse, Utils.i18n('SETTINGS/ERROR_SETTINGS_SAVING_FAILED'));
	}
	else
	{
		AppData.User.filesEnable(this.enableFiles());
		App.Api.showReport(Utils.i18n('SETTINGS/COMMON_REPORT_UPDATED_SUCCESSFULLY'));
	}
};