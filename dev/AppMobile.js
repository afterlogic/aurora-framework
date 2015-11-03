
/**
 * @constructor
 */
function AppMobile()
{
	AppBase.call(this);
}

_.extend(AppMobile.prototype, AppBase.prototype);

AppMobile.prototype.collectScreensData = function ()
{
	if (AppData.User.ShowContacts)
	{
		this.addScreenToHeader('contacts', Utils.i18n('HEADER/CONTACTS'), Utils.i18n('TITLE/CONTACTS'), 
			'Contacts_ContactsViewModel', CContactsViewModel, true, this.ContactsCache.recivedAnim);
	}
};
