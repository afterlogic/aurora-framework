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
	
	Ajax = require('modules/Files/js/Ajax.js'),
	Settings = require('modules/Files/js/Settings.js')
;

/**
 * @constructor
 */
function CFilesSettingsTabView()
{
	CAbstractSettingsTabView.call(this);

	this.enableFiles = ko.observable(Settings.filesEnable());
}

_.extendOwn(CFilesSettingsTabView.prototype, CAbstractSettingsTabView.prototype);

CFilesSettingsTabView.prototype.ViewTemplate = 'Files_FilesSettingsTabView';

CFilesSettingsTabView.prototype.getState = function()
{
	var aState = [
		this.enableFiles()
	];
	
	return aState.join(':');
};

CFilesSettingsTabView.prototype.revert = function()
{
	this.enableFiles(Settings.filesEnable());
	this.updateCurrentState();
};

CFilesSettingsTabView.prototype.save = function ()
{
	this.isSaving(true);
	
	this.updateCurrentState();
	
	Ajax.send('UpdateSettings', { 'FilesEnable': this.enableFiles() ? '1' : '0' }, this.onResponse, this);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CFilesSettingsTabView.prototype.onResponse = function (oResponse, oRequest)
{
	this.isSaving(false);

	if (oResponse.Result === false)
	{
		Api.showErrorByCode(oResponse, TextUtils.i18n('SETTINGS/ERROR_SETTINGS_SAVING_FAILED'));
	}
	else
	{
		var oParameters = JSON.parse(oRequest.Parameters);
		
		Settings.update(oParameters.FilesEnable);

		Screens.showReport(Utils.i18n('SETTINGS/COMMON_REPORT_UPDATED_SUCCESSFULLY'));
	}
};

module.exports = new CFilesSettingsTabView();
