
/**
 * @constructor
 */
function COutLookSyncSettingsViewModel()
{
	this.outlookSync = AppData.User.outlookSync;
	this.outlookSync.subscribe(this.onOutlookSyncSubscribe, this);
	
	this.oResetPasswordViewModel = new CResetPasswordViewModel();
	
	this.visibleOutlookSync = ko.observable(false);
	
	this.server = ko.observable('');
	
	this.bChanged = false;
	this.isDemo = AppData.User.IsDemo;

	this.outlookSyncPlugin32 = App.getHelpLink('OutlookSyncPlugin32');
	this.outlookSyncPlugin64 = App.getHelpLink('OutlookSyncPlugin64');
	this.outlookSyncPluginReadMore = App.getHelpLink('OutlookSyncPluginReadMore');

	this.credentialsHintText = ko.observable(Utils.i18n('SETTINGS/OUTLOOKSYNC_CREDENTIALS_TITLE', {'EMAIL': AppData.Accounts.getDefault().email()}));
}

COutLookSyncSettingsViewModel.prototype.TemplateName = 'Settings_OutLookSyncSettingsViewModel';

COutLookSyncSettingsViewModel.prototype.TabName = Enums.SettingsTab.OutLookSync;

COutLookSyncSettingsViewModel.prototype.TabTitle = Utils.i18n('SETTINGS/TAB_OUTLOOK_SYNC');

COutLookSyncSettingsViewModel.prototype.onRoute = function ()
{
	AppData.User.requestSyncSettings();
};

COutLookSyncSettingsViewModel.prototype.onOutlookSyncSubscribe = function ()
{
	if (AppData.User.OutlookSyncEnable)
	{
		this.visibleOutlookSync(true);

		if (this.outlookSync())
		{
			this.server(this.outlookSync()['Server']);
		}
	}
};
