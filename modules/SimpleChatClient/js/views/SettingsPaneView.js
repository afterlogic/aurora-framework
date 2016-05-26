'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	ModulesManager = require('modules/Core/js/ModulesManager.js'),
	CAbstractSettingsFormView = ModulesManager.run('Settings', 'getAbstractSettingsFormViewClass'),
	
	Settings = require('modules/SimpleChatClient/js/Settings.js')
;

/**
 * @constructor
 */
function CHelpdeskSettingsPaneView()
{
	CAbstractSettingsFormView.call(this, 'SimpleChat');

	this.enableModule = ko.observable(Settings.enableModule());
}

_.extendOwn(CHelpdeskSettingsPaneView.prototype, CAbstractSettingsFormView.prototype);

CHelpdeskSettingsPaneView.prototype.ViewTemplate = 'SimpleChatClient_SettingsPaneView';

CHelpdeskSettingsPaneView.prototype.getCurrentValues = function ()
{
	return [
		this.enableModule()
	];
};

CHelpdeskSettingsPaneView.prototype.revertGlobalValues = function ()
{
	this.enableModule(Settings.enableModule());
};

CHelpdeskSettingsPaneView.prototype.getParametersForSave = function ()
{
	return {
		'EnableModule': this.enableModule()
	};
};

CHelpdeskSettingsPaneView.prototype.applySavedValues = function (oParameters)
{
	Settings.update(oParameters.EnableModule);
};

module.exports = new CHelpdeskSettingsPaneView();
