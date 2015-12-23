'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	TextUtils = require('core/js/utils/Text.js'),
	Utils = require('core/js/utils/Common.js'),
	
	Api = require('core/js/Api.js'),
	Screens = require('core/js/Screens.js'),
	ModulesManager = require('core/js/ModulesManager.js'),
	CAbstractSettingsTabView = ModulesManager.run('Settings', 'getAbstractSettingsTabViewClass'),
	
	Ajax = require('modules/Contacts/js/Ajax.js'),
	Settings = require('modules/Contacts/js/Settings.js'),
	
	aRangeOfNumbers = [10, 20, 30, 50, 75, 100, 150, 200]
;

/**
 * @constructor
 */
function CContactsSettingsTabView()
{
	CAbstractSettingsTabView.call(this);

	
	this.contactsPerPageValues = ko.observableArray(aRangeOfNumbers);
	this.contactsPerPage = ko.observable(aRangeOfNumbers[0]);
	this.setContactsPerPage(Settings.ContactsPerPage);
}

_.extendOwn(CContactsSettingsTabView.prototype, CAbstractSettingsTabView.prototype);

CContactsSettingsTabView.prototype.ViewTemplate = 'Contacts_ContactsSettingsTabView';

/**
 * @param {number} iCpp
 */
CContactsSettingsTabView.prototype.setContactsPerPage = function (iCpp)
{
	var aValues = aRangeOfNumbers;
	
	if (-1 === _.indexOf(aValues, iCpp))
	{
		aValues = _.sortBy(_.union(aValues, [iCpp]), function (oVal) {
			return oVal;
		}, this) ;
	}
	this.contactsPerPageValues(aValues);
	
	this.contactsPerPage(iCpp);
};

CContactsSettingsTabView.prototype.getState = function()
{
	var aState = [
		this.contactsPerPage()
	];
	
	return aState.join(':');
};

CContactsSettingsTabView.prototype.revert = function()
{
	this.setContactsPerPage(Settings.ContactsPerPage);
	this.updateCurrentState();
};

CContactsSettingsTabView.prototype.save = function ()
{
	this.isSaving(true);
	
	this.updateCurrentState();
	
	Ajax.send('UpdateSettings', { 'ContactsPerPage': this.contactsPerPage() }, this.onResponse, this);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CContactsSettingsTabView.prototype.onResponse = function (oResponse, oRequest)
{
	this.isSaving(false);

	if (oResponse.Result === false)
	{
		Api.showErrorByCode(oResponse, TextUtils.i18n('SETTINGS/ERROR_SETTINGS_SAVING_FAILED'));
	}
	else
	{
		var oParameters = JSON.parse(oRequest.Parameters);
		
		Settings.update(oParameters.ContactsPerPage);

		Screens.showReport(Utils.i18n('SETTINGS/COMMON_REPORT_UPDATED_SUCCESSFULLY'));
	}
};

module.exports = new CContactsSettingsTabView();
