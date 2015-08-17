
/**
 * @constructor
 */
function CCommonSettingsViewModel()
{
	this.allowWebMail = AppData.App.AllowWebMail;
	this.isRtl = Utils.isRTL();
	
	this.aSkins = AppData.App.Themes;
	this.selectedSkin = ko.observable(AppData.User.DefaultTheme);

	this.aLanguages = AppData.App.Languages;
	this.selectedLanguage = ko.observable(AppData.User.DefaultLanguage);

	this.loading = ko.observable(false);

	this.rangeOfNumbers = [10, 20, 30, 50, 75, 100, 150, 200];
	
	this.messagesPerPageValues = ko.observableArray(this.rangeOfNumbers);
	this.messagesPerPage = ko.observable(this.rangeOfNumbers[0]);
	this.setMessagesPerPage(AppData.User.MailsPerPage);
	
	this.contactsPerPageValues = ko.observableArray(this.rangeOfNumbers);
	this.contactsPerPage = ko.observable(this.rangeOfNumbers[0]);
	this.setContactsPerPage(AppData.User.ContactsPerPage);
	
	this.autocheckmailInterval = ko.observable(AppData.User.AutoCheckMailInterval);

	this.timeFormat = ko.observable(AppData.User.defaultTimeFormat());
	this.aDateFormats = Utils.getDateFormatsForSelector();
	this.dateFormat = ko.observable(AppData.User.DefaultDateFormat);

	this.useThreads = ko.observable(AppData.User.useThreads());
	this.saveRepliedToCurrFolder = ko.observable(AppData.User.SaveRepliedToCurrFolder);
	this.allowChangeInputDirection = ko.observable(AppData.User.AllowChangeInputDirection);
	
	this.desktopNotifications = ko.observable(AppData.User.DesktopNotifications);

	this.desktopNotifications.subscribe(function (bChecked) {
		var self = this;

		if (bChecked && window.Notification.permission === 'default')
		{
			window.Notification.requestPermission(function (sPermission)
			{
				if (sPermission === 'denied')
				{
					self.desktopNotifications(false);
					self.desktopNotificationsIsEnable(false);
				}
			});
		}
	}, this);
	this.desktopNotificationsIsEnable = ko.observable((window.Notification && window.Notification.permission !== 'denied'));
	this.isMailto = ko.observable(App.browser.firefox || App.browser.chrome);
	
	this.emailNotification = ko.observable(AppData.User.EmailNotification);
	this.aAccountsEmails = AppData.Accounts.getAccountsEmails();

	this.defaultAccount = AppData.Accounts.getDefault();
	
	this.defaultAccountCanBeRemoved = ko.computed(function () {
		return this.defaultAccount.canBeRemoved();
	}, this);
	
	this.defaultAccountRemoveHint = ko.computed(function () {
		return this.defaultAccount.removeHint();
	}, this);
	
	this.bAllowContacts = AppData.User.ShowContacts;
	this.bAllowCalendar = AppData.User.AllowCalendar;
	this.bAllowThreads = AppData.User.ThreadsEnabled;
	
	this.firstState = this.getState();
}

CCommonSettingsViewModel.prototype.TemplateName = 'Settings_CommonSettingsViewModel';

CCommonSettingsViewModel.prototype.TabName = Enums.SettingsTab.Common;

CCommonSettingsViewModel.prototype.TabTitle = Utils.i18n('SETTINGS/TAB_COMMON');

CCommonSettingsViewModel.prototype.onApplyBindings = function ()
{
	this.init();
	this.updateFirstState();
};

CCommonSettingsViewModel.prototype.onShow = function ()
{
	this.aAccountsEmails = AppData.Accounts.getAccountsEmails();
};

/**
 * @param {number} iMpp
 */
CCommonSettingsViewModel.prototype.setMessagesPerPage = function (iMpp)
{
	var aValues = this.rangeOfNumbers;
	
	if (-1 === _.indexOf(aValues, iMpp))
	{
		aValues = _.sortBy(_.union(aValues, [iMpp]), function (oVal) {
			return oVal;
		}, this) ;
	}
	this.messagesPerPageValues(aValues);
	
	this.messagesPerPage(iMpp);
};

/**
 * @param {number} iCpp
 */
CCommonSettingsViewModel.prototype.setContactsPerPage = function (iCpp)
{
	var aValues = this.rangeOfNumbers;
	
	if (-1 === _.indexOf(aValues, iCpp))
	{
		aValues = _.sortBy(_.union(aValues, [iCpp]), function (oVal) {
			return oVal;
		}, this) ;
	}
	this.contactsPerPageValues(aValues);
	
	this.contactsPerPage(iCpp);
};

CCommonSettingsViewModel.prototype.init = function ()
{
	this.selectedSkin(AppData.User.DefaultTheme);
	this.selectedLanguage(AppData.User.DefaultLanguage);
	this.setMessagesPerPage(AppData.User.MailsPerPage);
	this.setContactsPerPage(AppData.User.ContactsPerPage);
	this.autocheckmailInterval(AppData.User.AutoCheckMailInterval);
	this.timeFormat(AppData.User.defaultTimeFormat());
	this.dateFormat(AppData.User.DefaultDateFormat);
	this.useThreads(AppData.User.useThreads());
	this.saveRepliedToCurrFolder(AppData.User.SaveRepliedToCurrFolder);
	this.allowChangeInputDirection(AppData.User.AllowChangeInputDirection);
	this.desktopNotifications(AppData.User.DesktopNotifications);
	this.emailNotification(AppData.User.EmailNotification);
};

CCommonSettingsViewModel.prototype.getState = function ()
{
	var aState = [
		this.selectedSkin(),
		this.selectedLanguage(),
		this.messagesPerPage(), 
		this.contactsPerPage(),
		this.autocheckmailInterval(),
		this.timeFormat(),
		this.dateFormat(),
		this.useThreads(),
		this.saveRepliedToCurrFolder(),
		this.allowChangeInputDirection(),
		this.desktopNotifications(),
		this.emailNotification()
	];
	
	return aState.join(':');
};

CCommonSettingsViewModel.prototype.updateFirstState = function ()
{
	this.firstState = this.getState();
};

CCommonSettingsViewModel.prototype.isChanged = function ()
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

/**
 * Parses the response from the server. If the settings are normally stored, then updates them. 
 * Otherwise an error message.
 * 
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CCommonSettingsViewModel.prototype.onResponse = function (oResponse, oRequest)
{
	var bNeedReload = false;

	this.loading(false);

	if (oResponse.Result === false)
	{
		App.Api.showErrorByCode(oResponse, Utils.i18n('SETTINGS/ERROR_SETTINGS_SAVING_FAILED'));
	}
	else
	{
		App.CalendarCache.calendarSettingsChanged(true);

		bNeedReload = (oRequest.DefaultTheme !== AppData.User.DefaultTheme ||
			oRequest.DefaultLanguage !== AppData.User.DefaultLanguage);
		
		if (bNeedReload)
		{
			window.location.reload();
		}
		else
		{
			this.setMessagesPerPage(oRequest.MailsPerPage);
			this.setContactsPerPage(oRequest.ContactsPerPage);

			AppData.User.updateCommonSettings(oRequest.MailsPerPage, oRequest.ContactsPerPage,
				oRequest.AutoCheckMailInterval,
				oRequest.DefaultTheme, oRequest.DefaultLanguage, oRequest.DefaultDateFormat,
				oRequest.DefaultTimeFormat, oRequest.UseThreads, oRequest.SaveRepliedMessagesToCurrentFolder,
				oRequest.DesktopNotifications, oRequest.AllowChangeInputDirection, oRequest.EmailNotification);

			App.Api.showReport(Utils.i18n('SETTINGS/COMMON_REPORT_UPDATED_SUCCESSFULLY'));
		}
	}
};

/**
 * Sends a request to the server to save the settings.
 */
CCommonSettingsViewModel.prototype.onSaveClick = function ()
{
	var
		oParameters = {
			'Action': 'UserSettingsUpdate',
			'MailsPerPage': Utils.pInt(this.messagesPerPage()),
			'ContactsPerPage': Utils.pInt(this.contactsPerPage()),
			'AutoCheckMailInterval': Utils.pInt(this.autocheckmailInterval()),
			'DefaultTheme': this.selectedSkin(),
			'DefaultLanguage': this.selectedLanguage(),
			'DefaultDateFormat': this.dateFormat(),
			'DefaultTimeFormat': this.timeFormat(),
			'UseThreads': this.useThreads() ? '1' : '0',
			'SaveRepliedMessagesToCurrentFolder': this.saveRepliedToCurrFolder() ? '1' : '0',
			'AllowChangeInputDirection': this.allowChangeInputDirection() ? '1' : '0',
			'DesktopNotifications': this.desktopNotifications() ? '1' : '0',
			'EmailNotification': this.emailNotification()
		}
	;

	this.loading(true);
	this.updateFirstState();
	App.Ajax.send(oParameters, this.onResponse, this);
};

CCommonSettingsViewModel.prototype.registerMailto = function ()
{
	Utils.registerMailto();
};

CCommonSettingsViewModel.prototype.removeDefaultAccount = function ()
{
	this.defaultAccount.remove();
};
