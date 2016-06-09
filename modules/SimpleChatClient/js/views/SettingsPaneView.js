'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	ModulesManager = require('modules/CoreClient/js/ModulesManager.js'),
	CAbstractSettingsFormView = ModulesManager.run('SettingsClient', 'getAbstractSettingsFormViewClass'),
	
	Settings = require('modules/%ModuleName%/js/Settings.js')
;

/**
 * Inherits from CAbstractSettingsFormView that has methods for showing and hiding settings tab,
 * updating settings values on the server, checking if there was changins on the settings page.
 * 
 * @constructor
 */
function CSettingsPaneView()
{
	CAbstractSettingsFormView.call(this, Settings.ServerModuleName);

	this.enableModule = ko.observable(Settings.enableModule());
}

_.extendOwn(CSettingsPaneView.prototype, CAbstractSettingsFormView.prototype);

/**
 * Name of template that will be binded to this JS-object. 'SimpleChatClient' - name of the object,
 * 'SettingsPaneView' - name of template file in 'templates' folder.
 */
CSettingsPaneView.prototype.ViewTemplate = '%ModuleName%_SettingsPaneView';

/**
 * Returns array with all settings values wich is used for indicating if there were changes on the page.
 * 
 * @returns {Array} Array with all settings values;
 */
CSettingsPaneView.prototype.getCurrentValues = function ()
{
	return [
		this.enableModule()
	];
};

/**
 * Reverts all settings values to global ones.
 */
CSettingsPaneView.prototype.revertGlobalValues = function ()
{
	this.enableModule(Settings.enableModule());
};

/**
 * Returns Object with parameters for passing to the server while settings updating.
 * 
 * @returns Object
 */
CSettingsPaneView.prototype.getParametersForSave = function ()
{
	return {
		'EnableModule': this.enableModule()
	};
};

/**
 * Applies new settings values to global settings object.
 * 
 * @param {Object} oParameters Parameters with new values which were passed to the server.
 */
CSettingsPaneView.prototype.applySavedValues = function (oParameters)
{
	Settings.update(oParameters.EnableModule);
};

module.exports = new CSettingsPaneView();
