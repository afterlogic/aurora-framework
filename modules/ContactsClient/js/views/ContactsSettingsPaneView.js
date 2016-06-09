'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	ModulesManager = require('modules/Core/js/ModulesManager.js'),
	CAbstractSettingsFormView = ModulesManager.run('SettingsClient', 'getAbstractSettingsFormViewClass'),
	SettingsUtils = ModulesManager.run('SettingsClient', 'getSettingsUtils'),
	
	Settings = require('modules/%ModuleName%/js/Settings.js')
;

/**
 * @constructor
 */
function CContactsSettingsPaneView()
{
	CAbstractSettingsFormView.call(this, Settings.ServerModuleName);
	
	this.contactsPerPageValues = ko.observableArray(SettingsUtils.getAdaptedPerPageList(Settings.ContactsPerPage));
	
	this.contactsPerPage = ko.observable(Settings.ContactsPerPage);
}

_.extendOwn(CContactsSettingsPaneView.prototype, CAbstractSettingsFormView.prototype);

CContactsSettingsPaneView.prototype.ViewTemplate = '%ModuleName%_ContactsSettingsPaneView';

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
