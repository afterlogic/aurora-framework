'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	ModulesManager = require('core/js/ModulesManager.js'),
	CAbstractSettingsTabView = ModulesManager.run('Settings', 'getAbstractSettingsTabViewClass'),
	SettingsUtils = ModulesManager.run('Settings', 'getSettingsUtils'),
	
	Settings = require('modules/Contacts/js/Settings.js')
;

/**
 * @constructor
 */
function CContactsSettingsTabView()
{
	CAbstractSettingsTabView.call(this);
	
	this.contactsPerPageValues = ko.observableArray(SettingsUtils.getAdaptedPerPageList(Settings.ContactsPerPage));
	
	this.contactsPerPage = ko.observable(Settings.ContactsPerPage);
}

_.extendOwn(CContactsSettingsTabView.prototype, CAbstractSettingsTabView.prototype);

CContactsSettingsTabView.prototype.ViewTemplate = 'Contacts_ContactsSettingsTabView';

CContactsSettingsTabView.prototype.getCurrentValues = function ()
{
	return [
		this.contactsPerPage()
	];
};

CContactsSettingsTabView.prototype.revertGlobalValues = function ()
{
	this.contactsPerPage(Settings.ContactsPerPage);
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
