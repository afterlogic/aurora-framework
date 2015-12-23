'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	ModulesManager = require('core/js/ModulesManager.js'),
	CAbstractSettingsTabView = ModulesManager.run('Settings', 'getAbstractSettingsTabViewClass'),
	
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

CFilesSettingsTabView.prototype.getCurrentValues = function ()
{
	return [
		this.enableFiles()
	];
};

CFilesSettingsTabView.prototype.revertGlobalValues = function ()
{
	this.enableFiles(Settings.filesEnable());
};

CFilesSettingsTabView.prototype.getParametersForSave = function ()
{
	return {
		'FilesEnable': this.enableFiles() ? '1' : '0'
	};
};

CFilesSettingsTabView.prototype.applySavedValues = function (oParameters)
{
	Settings.update(oParameters.FilesEnable);
};

module.exports = new CFilesSettingsTabView();
