'use strict';

var
	$ = require('jquery'),
	ko = require('knockout'),
	_ = require('underscore'),
	
	TextUtils = require('core/js/utils/Text.js'),
	Api = require('core/js/Api.js'),
	Screens = require('core/js/Screens.js'),
	Routing = require('core/js/Routing.js'),
	CAbstractPopup = require('core/js/popups/CAbstractPopup.js'),
	
	Ajax = require('modules/Contacts/js/Ajax.js'),
	ContactsCache = require('modules/Contacts/js/Cache.js'),
	LinksUtils = require('modules/Contacts/js/utils/Links.js'),
	HeaderItemView = require('modules/Contacts/js/views/HeaderItemView.js')
;

/**
 * @constructor
 */
function CCreateContactPopup()
{
	CAbstractPopup.call(this);
	
	this.displayName = ko.observable('');
	this.email = ko.observable('');
	this.phone = ko.observable('');
	this.address = ko.observable('');
	this.skype = ko.observable('');
	this.facebook = ko.observable('');

	this.focusDisplayName = ko.observable(false);

	this.loading = ko.observable(false);

	this.fCallback = function () {};
}

_.extendOwn(CCreateContactPopup.prototype, CAbstractPopup.prototype);

CCreateContactPopup.prototype.PopupTemplate = 'Contacts_CreateContactPopup';

/**
 * @param {string} sName
 * @param {string} sEmail
 * @param {Function} fCallback
 */
CCreateContactPopup.prototype.onShow = function (sName, sEmail, fCallback)
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

	this.fCallback = $.isFunction(fCallback) ? fCallback : function () {};
};

CCreateContactPopup.prototype.onSaveClick = function ()
{
	if (!this.canBeSave())
	{
		Screens.showError(TextUtils.i18n('CONTACTS/ERROR_EMPTY_CONTACT'));
	}
	else if (!this.loading())
	{
		var
			oParameters = {
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
		Ajax.send('CreateContact', oParameters, this.onCreateContactResponse, this);
	}
};


CCreateContactPopup.prototype.cancelPopup = function ()
{
	this.loading(false);
	this.closePopup();
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CCreateContactPopup.prototype.onCreateContactResponse = function (oResponse, oRequest)
{
	var oParameters = JSON.parse(oRequest.Parameters);
	
	this.loading(false);

	if (!oResponse.Result)
	{
		Api.showErrorByCode(oResponse, TextUtils.i18n('WARNING/CREATING_ACCOUNT_ERROR'));
	}
	else
	{
		Screens.showReport(TextUtils.i18n('CONTACTS/REPORT_CONTACT_SUCCESSFULLY_ADDED'));
		ContactsCache.clearInfoAboutEmail(oParameters.HomeEmail);
		ContactsCache.getContactsByEmails([oParameters.HomeEmail], this.fCallback);
		this.closePopup();
		
		if (!HeaderItemView.isCurrent())
		{
			HeaderItemView.recivedAnim(true);
		}
	}
};

CCreateContactPopup.prototype.canBeSave = function ()
{
	return this.displayName() !== '' || this.email() !== '';
};

CCreateContactPopup.prototype.goToContacts = function ()
{
	ContactsCache.saveNewContactParams({
		displayName: this.displayName(),
		email: this.email(),
		phone: this.phone(),
		address: this.address(),
		skype: this.skype(),
		facebook: this.facebook()
	});
	this.closePopup();
	Routing.replaceHash(LinksUtils.getContacts());
};

module.exports = new CCreateContactPopup();