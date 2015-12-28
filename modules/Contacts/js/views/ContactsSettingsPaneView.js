'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	ModulesManager = require('core/js/ModulesManager.js'),
	CAbstractSettingsFormView = ModulesManager.run('Settings', 'getAbstractSettingsFormViewClass'),
	SettingsUtils = ModulesManager.run('Settings', 'getSettingsUtils'),
	
	Settings = require('modules/Contacts/js/Settings.js')
;

/**
 * @constructor
 */
function CContactsSettingsPaneView()
{
	CAbstractSettingsFormView.call(this);
	
	this.contactsPerPageValues = ko.observableArray(SettingsUtils.getAdaptedPerPageList(Settings.ContactsPerPage));
	
	this.contactsPerPage = ko.observable(Settings.ContactsPerPage);
}

_.extendOwn(CContactsSettingsPaneView.prototype, CAbstractSettingsFormView.prototype);

CContactsSettingsPaneView.prototype.ViewTemplate = 'Contacts_ContactsSettingsPaneView';

CContactsSettingsPaneView.prototype.getCurrentValues = function ()
{
	return [
		this.contactsPerPage()
	];
};

CContactsSettingsPaneView.prototype.revertGlobalValues = function ()
{
	this.contactsPerPage(Settings.ContactsPerPage);
};

CContactsSettingsPaneView.prototype.getParametersForSave = function ()
{
	return {
		'ContactsPerPage': this.contactsPerPage()
	};
};

CContactsSettingsPaneView.prototype.applySavedValues = function (oParameters)
{
	Settings.update(oParameters.ContactsPerPage);
};

module.exports = new CContactsSettingsPaneView();
