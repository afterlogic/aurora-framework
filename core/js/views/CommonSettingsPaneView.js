'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	Utils = require('core/js/utils/Common.js'),
	
	UserSettings = require('core/js/Settings.js'),
	ModulesManager = require('core/js/ModulesManager.js'),
	CAbstractSettingsFormView = ModulesManager.run('Settings', 'getAbstractSettingsFormViewClass')
;

/**
 * @constructor
 */
function CCommonSettingsPaneView()
{
	CAbstractSettingsFormView.call(this);
	
	this.aSkins = UserSettings.Themes;
	this.aLanguages = UserSettings.Languages;
	
	/* Editable fields */
	this.selectedSkin = ko.observable(UserSettings.DefaultTheme);
	this.selectedLanguage = ko.observable(UserSettings.DefaultLanguage);
	this.autoRefreshInterval = ko.observable(UserSettings.AutoRefreshIntervalMinutes);
	this.timeFormat = ko.observable(UserSettings.defaultTimeFormat());
	this.desktopNotifications = ko.observable(UserSettings.DesktopNotifications);
	/*-- Editable fields */
	
	this.isDesktopNotificationsEnable = ko.observable((window.Notification && window.Notification.permission !== 'denied'));
	this.desktopNotifications.subscribe(function (bChecked) {
		var self = this;
		if (bChecked && window.Notification.permission === 'default')
		{
			window.Notification.requestPermission(function (sPermission) {
				if (sPermission === 'denied')
				{
					self.desktopNotifications(false);
					self.isDesktopNotificationsEnable(false);
				}
			});
		}
	}, this);
}

_.extendOwn(CCommonSettingsPaneView.prototype, CAbstractSettingsFormView.prototype);

CCommonSettingsPaneView.prototype.ViewTemplate = 'Core_CommonSettingsPaneView';

/**
 * Returns an array with the values of editable fields.
 * 
 * @returns {Array}
 */
CCommonSettingsPaneView.prototype.getCurrentValues = function ()
{
	return [
		this.selectedSkin(),
		this.selectedLanguage(),
		this.autoRefreshInterval(),
		this.timeFormat(),
		this.desktopNotifications()
	];
};

/**
 * Puts values from the global settings object to the editable fields.
 */
CCommonSettingsPaneView.prototype.revertGlobalValues = function ()
{
	this.selectedSkin(UserSettings.DefaultTheme);
	this.selectedLanguage(UserSettings.DefaultLanguage);
	this.autoRefreshInterval(UserSettings.AutoRefreshIntervalMinutes);
	this.timeFormat(UserSettings.defaultTimeFormat());
	this.desktopNotifications(UserSettings.DesktopNotifications);
};

/**
 * Gets values from the editable fields and prepares object for passing to the server and saving settings therein.
 * 
 * @returns {Object}
 */
CCommonSettingsPaneView.prototype.getParametersForSave = function ()
{
	return {
		'AutoCheckMailInterval': Utils.pInt(this.autoRefreshInterval()),
		'DefaultTheme': this.selectedSkin(),
		'DefaultLanguage': this.selectedLanguage(),
		'DefaultTimeFormat': this.timeFormat(),
		'DesktopNotifications': this.desktopNotifications() ? '1' : '0'
	};
};

/**
 * Applies saved values of settings to the global settings object.
 * 
 * @param {Object} oParameters Object that have been obtained by getParameters function.
 */
CCommonSettingsPaneView.prototype.applySavedValues = function (oParameters)
{
	if (oParameters.DefaultTheme !== UserSettings.DefaultTheme || oParameters.DefaultLanguage !== UserSettings.DefaultLanguage)
	{
		window.location.reload();
	}
	else
	{
		UserSettings.updateCommonSettings(oParameters.AutoCheckMailInterval,
			oParameters.DefaultTheme, oParameters.DefaultLanguage,
			oParameters.DefaultTimeFormat, oParameters.DesktopNotifications);
	}
};

module.exports = new CCommonSettingsPaneView();
