/**
 * @constructor
 */
function ContactCreatePopup()
{
	this.displayName = ko.observable('');
	this.email = ko.observable('');
	this.phone = ko.observable('');
	this.address = ko.observable('');
	this.skype = ko.observable('');
	this.facebook = ko.observable('');

	this.focusDisplayName = ko.observable(false);

	this.loading = ko.observable(false);

	this.fCallback = null;
	this.oContext = null;
}

/**
 * @return {string}
 */
ContactCreatePopup.prototype.popupTemplate = function ()
{
	return 'Popups_ContactCreatePopupViewModel';
};

/**
 * @param {string} sName
 * @param {string} sEmail
 * @param {Function} fContactCreateResponse
 * @param {Object} oContactCreateContext
 */
ContactCreatePopup.prototype.onShow = function (sName, sEmail, fContactCreateResponse, oContactCreateContext)
{
	if (this.displayName() !== sName || this.email() !== sEmail)
	{
		this.displayName(sName);
		this.email(sEmail);
		this.phone('');
		this.address('');
		this.skype('');
		this.facebook('');
	}

	if (Utils.isFunc(fContactCreateResponse))
	{
		this.fCallback = fContactCreateResponse;
	}
	if (oContactCreateContext)
	{
		this.oContext = oContactCreateContext;
	}
};

ContactCreatePopup.prototype.onSaveClick = function ()
{
	if (!this.canBeSave())
	{
		App.Api.showError(Utils.i18n('CONTACTS/ERROR_EMPTY_CONTACT'));
	}
	else if (!this.loading())
	{
		var
			oParameters = {
				'Action': 'ContactCreate',
				'PrimaryEmail': 'Home',
				'UseFriendlyName': '1',
				'FullName': this.displayName(),
				'HomeEmail': this.email(),
				'HomePhone': this.phone(),
				'HomeStreet': this.address(),
				'Skype': this.skype(),
				'Facebook': this.facebook()
			}
		;

		this.loading(true);
		App.Ajax.send(oParameters, this.onContactCreateResponse, this);
	}
};


ContactCreatePopup.prototype.onCancelClick = function ()
{
	this.loading(false);
	this.closeCommand();
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
ContactCreatePopup.prototype.onContactCreateResponse = function (oResponse, oRequest)
{
	this.loading(false);

	if (!oResponse.Result)
	{
		App.Api.showErrorByCode(oResponse, Utils.i18n('WARNING/CREATING_ACCOUNT_ERROR'));
	}
	else
	{
		App.Api.showReport(Utils.i18n('CONTACTS/REPORT_CONTACT_SUCCESSFULLY_ADDED'));
		App.ContactsCache.clearInfoAboutEmail(oRequest.HomeEmail);
		App.ContactsCache.getContactsByEmails([oRequest.HomeEmail], this.fCallback, this.oContext);
		this.closeCommand();
	}
};

ContactCreatePopup.prototype.canBeSave = function ()
{
	return this.displayName() !== '' || this.email() !== '';
};

ContactCreatePopup.prototype.goToContacts = function ()
{
	App.ContactsCache.newContactParams = {
		displayName: this.displayName(),
		email: this.email(),
		phone: this.phone(),
		address: this.address(),
		skype: this.skype(),
		facebook: this.facebook()
	};
	this.closeCommand();
	App.Routing.replaceHash(App.Links.contacts());
};