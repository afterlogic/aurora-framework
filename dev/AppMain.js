
/**
 * @constructor
 */
function AppMain()
{
	AppBase.call(this);
}

_.extend(AppMain.prototype, AppBase.prototype);

AppMain.prototype.initPhone = function (bAllow)
{
	return bAllow ? new CPhone() : null;
};

AppMain.prototype.collectScreensData = function ()
{
	if (AppData.User.ShowContacts)
	{
		this.addScreenToHeader('contacts', Utils.i18n('HEADER/CONTACTS'), Utils.i18n('TITLE/CONTACTS'),
			'Contacts_ContactsViewModel', CContactsViewModel, true, this.ContactsCache.recivedAnim);
	}

	if (AppData.User.AllowCalendar)
	{
		this.addScreenToHeader('calendar', Utils.i18n('HEADER/CALENDAR'), Utils.i18n('TITLE/CALENDAR'),
			'Calendar_CalendarViewModel', CCalendarViewModel, true, this.CalendarCache.recivedAnim, true);
	}

	if (AppData.User.IsFilesSupported)
	{
		this.addScreenToHeader('files', Utils.i18n('HEADER/FILESTORAGE'), Utils.i18n('TITLE/FILESTORAGE'),
			'FileStorage_FileStorageViewModel', CFileStorageViewModel, AppData.User.filesEnable, this.filesRecievedAnim);
	}

	if (AppData.User.IsHelpdeskSupported)
	{
		this.addScreenToHeader('helpdesk', Utils.i18n('HEADER/HELPDESK'), Utils.i18n('TITLE/HELPDESK'),
			'Helpdesk_HelpdeskViewModel', CHelpdeskViewModel, true);
	}
};