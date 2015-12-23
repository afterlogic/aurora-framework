'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	ModulesManager = require('core/js/ModulesManager.js'),
	CAbstractSettingsTabView = ModulesManager.run('Settings', 'getAbstractSettingsTabViewClass'),
	
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

CContactsSettingsTabView.prototype.getCurrentValues = function ()
{
	return [
		this.contactsPerPage()
	];
};

CContactsSettingsTabView.prototype.revertGlobalValues = function ()
{
	this.setContactsPerPage(Settings.ContactsPerPage);
};

CContactsSettingsTabView.prototype.getParametersForSave = function ()
{
	return {
		'ContactsPerPage': this.contactsPerPage()
	};
};

CContactsSettingsTabView.prototype.applySavedValues = function (oParameters)
{
	Settings.update(oParameters.ContactsPerPage);
};

module.exports = new CContactsSettingsTabView();
