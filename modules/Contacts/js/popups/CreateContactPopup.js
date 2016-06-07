'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	TextUtils = require('modules/Core/js/utils/Text.js'),
	Utils = require('modules/Core/js/utils/Common.js'),
	
	Api = require('modules/Core/js/Api.js'),
	Routing = require('modules/Core/js/Routing.js'),
	Screens = require('modules/Core/js/Screens.js'),
	
	CAbstractPopup = require('modules/Core/js/popups/CAbstractPopup.js'),
	
	LinksUtils = require('modules/%ModuleName%/js/utils/Links.js'),
	
	Ajax = require('modules/%ModuleName%/js/Ajax.js'),
	ContactsCache = require('modules/%ModuleName%/js/Cache.js'),
	
	HeaderItemView = require('modules/%ModuleName%/js/views/HeaderItemView.js')
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

CCreateContactPopup.prototype.PopupTemplate = '%ModuleName%_CreateContactPopup';

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
		Screens.showError(TextUtils.i18n('%MODULENAME%/ERROR_EMAIL_OR_NAME_BLANK'));
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
	var oParameters = oRequest.Parameters;
	
	this.loading(false);

	if (!oResponse.Result)
	{
		Api.showErrorByCode(oResponse, TextUtils.i18n('CORE/ERROR_UNKNOWN'));
	}
	else
	{
		Screens.showReport(TextUtils.i18n('%MODULENAME%/REPORT_CONTACT_SUCCESSFULLY_ADDED'));
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
