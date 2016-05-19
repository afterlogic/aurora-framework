'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	ModulesManager = require('modules/Core/js/ModulesManager.js'),
	CAbstractSettingsFormView = ModulesManager.run('Settings', 'getAbstractSettingsFormViewClass'),
	
	Settings = require('modules/HelpDeskClient/js/Settings.js')
;

/**
 * @constructor
 */
function CHelpdeskSettingsPaneView()
{
	CAbstractSettingsFormView.call(this, 'Helpdesk');

	this.allowNotifications = ko.observable(Settings.AllowEmailNotifications);
}

_.extendOwn(CHelpdeskSettingsPaneView.prototype, CAbstractSettingsFormView.prototype);

CHelpdeskSettingsPaneView.prototype.ViewTemplate = 'HelpDeskClient_HelpdeskSettingsPaneView';

CHelpdeskSettingsPaneView.prototype.getCurrentValues = function ()
{
	return [
		this.allowNotifications()
	];
};

CHelpdeskSettingsPaneView.prototype.revertGlobalValues = function ()
{
	this.allowNotifications(Settings.AllowEmailNotifications);
};

CHelpdeskSettingsPaneView.prototype.getParametersForSave = function ()
{
	return {
		'AllowEmailNotifications': this.allowNotifications() ? '1' : '0'
	};
};

CHelpdeskSettingsPaneView.prototype.applySavedValues = function (oParameters)
{
	Settings.update(oParameters.AllowEmailNotifications);
};

module.exports = new CHelpdeskSettingsPaneView();
