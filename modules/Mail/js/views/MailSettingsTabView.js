'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	TextUtils = require('core/js/utils/Text.js'),
	Utils = require('core/js/utils/Common.js'),
	
	Api = require('core/js/Api.js'),
	Browser = require('core/js/Browser.js'),
	Screens = require('core/js/Screens.js'),
	ModulesManager = require('core/js/ModulesManager.js'),
	UserSettings = require('core/js/Settings.js'),
	CAbstractSettingsTabView = ModulesManager.run('Settings', 'getAbstractSettingsTabViewClass'),
	
	MailUtils = require('modules/Mail/js/utils/Mail.js'),
	Ajax = require('modules/Mail/js/Ajax.js'),
	Settings = require('modules/Mail/js/Settings.js'),
	
	aRangeOfNumbers = [10, 20, 30, 50, 75, 100, 150, 200]
;

/**
 * @constructor
 */
function CMailSettingsTabView()
{
	CAbstractSettingsTabView.call(this);

	this.messagesPerPageValues = ko.observableArray(aRangeOfNumbers);
	this.messagesPerPage = ko.observable(aRangeOfNumbers[0]);
	this.setMessagesPerPage(Settings.MailsPerPage);
	this.bAllowThreads = Settings.ThreadsEnabled;
	this.useThreads = ko.observable(Settings.useThreads());
	this.saveRepliedToCurrFolder = ko.observable(Settings.SaveRepliedToCurrFolder);
	this.bRtl = UserSettings.IsRTL;
	this.allowChangeInputDirection = ko.observable(Settings.AllowChangeInputDirection);
	this.bAllowMailto = Settings.AllowAppRegisterMailto && (Browser.firefox || Browser.chrome);
}

_.extendOwn(CMailSettingsTabView.prototype, CAbstractSettingsTabView.prototype);

CMailSettingsTabView.prototype.ViewTemplate = 'Mail_MailSettingsTabView';

/**
 * @param {number} iMpp
 */
CMailSettingsTabView.prototype.setMessagesPerPage = function (iMpp)
{
	if (-1 === _.indexOf(aRangeOfNumbers, iMpp))
	{
		aRangeOfNumbers = _.sortBy(_.union(aRangeOfNumbers, [iMpp]), function (oVal) {
			return oVal;
		}, this) ;
	}
	this.messagesPerPageValues(aRangeOfNumbers);
	
	this.messagesPerPage(iMpp);
};

CMailSettingsTabView.prototype.registerMailto = function ()
{
	MailUtils.registerMailto();
};

CMailSettingsTabView.prototype.getState = function()
{
	var aState = [
		this.messagesPerPage()
	];
	
	return aState.join(':');
};

CMailSettingsTabView.prototype.revert = function()
{
	this.setMessagesPerPage(Settings.MailsPerPage);
	this.useThreads(Settings.useThreads());
	this.saveRepliedToCurrFolder(Settings.SaveRepliedToCurrFolder);
	this.allowChangeInputDirection(Settings.AllowChangeInputDirection);
	
	this.updateCurrentState();
};

CMailSettingsTabView.prototype.save = function ()
{
	this.isSaving(true);
	
	this.updateCurrentState();
	
	Ajax.send('UpdateSettings', {
		'MessagesPerPage': this.messagesPerPage(),
		'UseThreads': this.useThreads(),
		'SaveRepliedToCurrFolder': this.saveRepliedToCurrFolder(),
		'AllowChangeInputDirection': this.allowChangeInputDirection()
	}, this.onResponse, this);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CMailSettingsTabView.prototype.onResponse = function (oResponse, oRequest)
{
	this.isSaving(false);

	if (oResponse.Result === false)
	{
		Api.showErrorByCode(oResponse, TextUtils.i18n('SETTINGS/ERROR_SETTINGS_SAVING_FAILED'));
	}
	else
	{
		var oParameters = JSON.parse(oRequest.Parameters);
		
		Settings.update(oParameters.MessagesPerPage, oParameters.UseThreads, oParameters.SaveRepliedToCurrFolder, oParameters.AllowChangeInputDirection);

		Screens.showReport(Utils.i18n('SETTINGS/COMMON_REPORT_UPDATED_SUCCESSFULLY'));
	}
};

module.exports = new CMailSettingsTabView();
