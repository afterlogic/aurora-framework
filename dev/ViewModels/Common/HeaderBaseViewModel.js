
/**
 * @constructor
 */
function CHeaderBaseViewModel()
{
	var self = this;
	this.mobileApp = bMobileApp;
	this.mobileDevice = AppData.AllowMobile && bMobileDevice;

	this.allowWebMail = AppData.App.AllowWebMail;
	this.currentAccountId = AppData.Accounts.currentId;
	this.currentAccountId.subscribe(function () {
		_.delay(function () {
			self.changeMailLinkText();
		}, 300);
	}, this);
	
	this.tabs = App.headerTabs;

	this.mailLinkText = ko.observable('');

	this.accounts = ko.computed(function () {
		if (AppData.Accounts.collection().length === 1)
		{
			return AppData.Accounts.collection();
		}
		else
		{
			return _.filter(AppData.Accounts.collection(), function (oAccount) {
				return oAccount.allowMail();
			});
		}
	}, this);
	
	this.currentTab = App.Screens.currentScreen;

	this.isMailboxTab = ko.computed(function () {
		return this.currentTab() === Enums.Screens.Mailbox;
	}, this);

	this.helpdeskUnseenCount = App.helpdeskUnseenCount;
	this.helpdeskUnseenVisible = ko.computed(function () {
		return this.currentTab() !== Enums.Screens.Helpdesk && !!this.helpdeskUnseenCount();
	}, this);
	this.helpdeskPendingCount = App.helpdeskPendingCount;
	this.helpdeskPendingVisible = ko.computed(function () {
		return this.currentTab() !== Enums.Screens.Helpdesk && !!this.helpdeskPendingCount();
	}, this);
	this.mailUnseenCount = App.mailUnseenCount;
	this.mailUnseenVisible = ko.computed(function () {
		return this.currentTab() !== Enums.Screens.Mailbox && !!this.mailUnseenCount();
	}, this);

	this.mailboxHash = App.Routing.lastMailboxHash;
	this.settingsHash = App.Routing.lastSettingsHash;
	
	this.contactsRecivedAnim = App.ContactsCache.recivedAnim;
	this.calendarRecivedAnim = App.CalendarCache.recivedAnim;

	this.appCustomLogo = ko.observable(AppData['AppStyleImage'] || '');
	
	if (AfterLogicApi.runPluginHook)
	{
		AfterLogicApi.runPluginHook('view-model-defined', [this.__name, this]);
	}
}

CHeaderBaseViewModel.prototype.__name = 'CHeaderBaseViewModel';

CHeaderBaseViewModel.prototype.onRoute = function ()
{
	this.changeMailLinkText();
};

CHeaderBaseViewModel.prototype.changeMailLinkText = function ()
{
	var oCurrAccount = AppData.Accounts.getCurrent();
	if (oCurrAccount.allowMail())
	{
		this.mailLinkText(AppData.Accounts.getEmail());
	}
	else
	{
		this.mailLinkText(Utils.i18n('TITLE/MAILBOX_TAB'));
	}
};

CHeaderBaseViewModel.prototype.logout = function ()
{
	App.logout();
};

CHeaderBaseViewModel.prototype.switchToFullVersion = function ()
{
	App.Ajax.send({
		'Action': 'SystemSetMobile',
		'Mobile': 0
	}, function () {
		window.location.reload();
	}, this);
};
