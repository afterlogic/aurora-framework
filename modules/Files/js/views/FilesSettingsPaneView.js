'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	ModulesManager = require('core/js/ModulesManager.js'),
	CAbstractSettingsFormView = ModulesManager.run('Settings', 'getAbstractSettingsFormViewClass'),
	
	Settings = require('modules/Files/js/Settings.js')
;

/**
 * @constructor
 */
function CFilesSettingsPaneView()
{
	CAbstractSettingsFormView.call(this);

	this.enableFiles = ko.observable(Settings.filesEnable());
}

_.extendOwn(CFilesSettingsPaneView.prototype, CAbstractSettingsFormView.prototype);

CFilesSettingsPaneView.prototype.ViewTemplate = 'Files_FilesSettingsPaneView';

CFilesSettingsPaneView.prototype.getCurrentValues = function ()
{
	return [
		this.enableFiles()
	];
};

CFilesSettingsPaneView.prototype.revertGlobalValues = function ()
{
	this.enableFiles(Settings.filesEnable());
};

CFilesSettingsPaneView.prototype.getParametersForSave = function ()
{
	return {
		'FilesEnable': this.enableFiles() ? '1' : '0'
	};
};

CFilesSettingsPaneView.prototype.applySavedValues = function (oParameters)
{
	Settings.update(oParameters.FilesEnable);
};

module.exports = new CFilesSettingsPaneView();
