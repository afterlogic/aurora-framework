
/**
 * @constructor
 */
function CMobileSyncSettingsViewModel()
{
	this.mobileSync = AppData.User.mobileSync;
	this.mobileSync.subscribe(this.onMobileSyncSubscribe, this);
	
	this.oResetPasswordViewModel = new CResetPasswordViewModel();
	
	this.enableDav = ko.observable(false);
	
	this.davLogin = ko.observable('');
	this.davServer = ko.observable('');
	
	this.davCalendars = ko.observable([]);
	this.visibleCalendars = ko.computed(function () {
		return this.davCalendars().length > 0;
	}, this);
	
	this.davPersonalContactsUrl = ko.observable('');
	this.davCollectedAddressesUrl = ko.observable('');
	this.davGlobalAddressBookUrl = ko.observable('');
	this.davSharedWithAllUrl = ko.observable('');
	
	this.bVisiblePersonalContacts = AppData.User.ShowPersonalContacts;
	this.bVisibleGlobalContacts = AppData.User.ShowGlobalContacts;
	this.bVisibleSharedWithAllContacts = !!AppData.App.AllowContactsSharing;
	
	this.bVisibleContacts = AppData.User.ShowContacts;
	this.bVisibleCalendar = AppData.User.AllowCalendar;
	this.bVisibleFiles = AppData.User.IsFilesSupported;
	this.bVisibleIosLink = bIsIosDevice;
	
	this.visibleDavViaUrls = ko.computed(function () {
		return this.visibleCalendars() || this.bVisibleContacts;
	}, this);

	this.bChanged = false;
	
	this.isDemo = AppData.User.IsDemo;

	this.credentialsHintText = ko.observable(Utils.i18n('SETTINGS/MOBILE_CREDENTIALS_TITLE', {'EMAIL': AppData.Accounts.getDefault().email()}));
}

CMobileSyncSettingsViewModel.prototype.TemplateName = 'Settings_MobileSyncSettingsViewModel';

CMobileSyncSettingsViewModel.prototype.TabName = Enums.SettingsTab.MobileSync;

CMobileSyncSettingsViewModel.prototype.TabTitle = Utils.i18n('SETTINGS/TAB_MOBILE_SYNC');

CMobileSyncSettingsViewModel.prototype.onRoute = function ()
{
	AppData.User.requestSyncSettings();
};

CMobileSyncSettingsViewModel.prototype.onMobileSyncSubscribe = function ()
{
	this.enableDav(AppData.User.MobileSyncEnable && this.mobileSync() && this.mobileSync()['EnableDav']);
	
	if (this.enableDav())
	{
		this.davLogin(this.mobileSync()['Dav']['Login']);
		this.davServer(this.mobileSync()['Dav']['Server']);

		this.davCalendars(this.mobileSync()['Dav']['Calendars']);
		
		this.davPersonalContactsUrl(this.mobileSync()['Dav']['PersonalContactsUrl']);
		this.davCollectedAddressesUrl(this.mobileSync()['Dav']['CollectedAddressesUrl']);
		this.davGlobalAddressBookUrl(this.mobileSync()['Dav']['GlobalAddressBookUrl']);
		this.davSharedWithAllUrl(this.mobileSync()['Dav']['SharedWithAllUrl']);
	}
};
