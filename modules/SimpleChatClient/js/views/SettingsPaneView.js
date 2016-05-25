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

	this.allowChat = ko.observable(Settings.enableModule());
}

_.extendOwn(CHelpdeskSettingsPaneView.prototype, CAbstractSettingsFormView.prototype);

CHelpdeskSettingsPaneView.prototype.ViewTemplate = 'SimpleChatClient_SettingsPaneView';

CHelpdeskSettingsPaneView.prototype.getCurrentValues = function ()
{
	return [
		this.allowChat()
	];
};

CHelpdeskSettingsPaneView.prototype.revertGlobalValues = function ()
{
	this.allowChat(Settings.enableModule());
};

CHelpdeskSettingsPaneView.prototype.getParametersForSave = function ()
{
	return {
		'AllowModule': this.allowChat() ? '1' : '0'
	};
};

CHelpdeskSettingsPaneView.prototype.applySavedValues = function (oParameters)
{
	Settings.update(oParameters.AllowModule);
};

module.exports = new CHelpdeskSettingsPaneView();
