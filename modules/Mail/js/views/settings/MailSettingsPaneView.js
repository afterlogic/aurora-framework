'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	Browser = require('core/js/Browser.js'),
	ModulesManager = require('core/js/ModulesManager.js'),
	UserSettings = require('core/js/Settings.js'),
	CAbstractSettingsFormView = ModulesManager.run('Settings', 'getAbstractSettingsFormViewClass'),
	SettingsUtils = ModulesManager.run('Settings', 'getSettingsUtils'),
	
	MailUtils = require('modules/Mail/js/utils/Mail.js'),
	Settings = require('modules/Mail/js/Settings.js')
;

/**
 * @constructor
 */
function CMailSettingsPaneView()
{
	CAbstractSettingsFormView.call(this, 'Mail');

	this.bRtl = UserSettings.IsRTL;
	this.bAllowThreads = Settings.ThreadsEnabled;
	this.bAllowMailto = Settings.AllowAppRegisterMailto && (Browser.firefox || Browser.chrome);
	
	this.messagesPerPageValues = ko.observableArray(SettingsUtils.getAdaptedPerPageList(Settings.MailsPerPage));
	
	this.messagesPerPage = ko.observable(Settings.MailsPerPage);
	this.useThreads = ko.observable(Settings.useThreads());
	this.saveRepliedToCurrFolder = ko.observable(Settings.SaveRepliedToCurrFolder);
	this.allowChangeInputDirection = ko.observable(Settings.AllowChangeInputDirection);
}

_.extendOwn(CMailSettingsPaneView.prototype, CAbstractSettingsFormView.prototype);

CMailSettingsPaneView.prototype.ViewTemplate = 'Mail_Settings_MailSettingsPaneView';

CMailSettingsPaneView.prototype.registerMailto = function ()
{
	MailUtils.registerMailto();
};

CMailSettingsPaneView.prototype.getCurrentValues = function ()
{
	return [
		this.messagesPerPage(),
		this.useThreads(),
		this.saveRepliedToCurrFolder(),
		this.allowChangeInputDirection()
	];
};

CMailSettingsPaneView.prototype.revertGlobalValues = function ()
{
	this.messagesPerPage(Settings.MailsPerPage);
	this.useThreads(Settings.useThreads());
	this.saveRepliedToCurrFolder(Settings.SaveRepliedToCurrFolder);
	this.allowChangeInputDirection(Settings.AllowChangeInputDirection);
};

CMailSettingsPaneView.prototype.getParametersForSave = function ()
{
	return {
		'MessagesPerPage': this.messagesPerPage(),
		'UseThreads': this.useThreads(),
		'SaveRepliedToCurrFolder': this.saveRepliedToCurrFolder(),
		'AllowChangeInputDirection': this.allowChangeInputDirection()
	};
};

CMailSettingsPaneView.prototype.applySavedValues = function (oParameters)
{
	Settings.update(oParameters.MessagesPerPage, oParameters.UseThreads, oParameters.SaveRepliedToCurrFolder, oParameters.AllowChangeInputDirection);
};

module.exports = new CMailSettingsPaneView();
