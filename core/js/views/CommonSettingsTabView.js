'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	TextUtils = require('core/js/utils/Text.js'),
	Utils = require('core/js/utils/Common.js'),
	
	Ajax = require('core/js/Ajax.js'),
	Api = require('core/js/Api.js'),
	Screens = require('core/js/Screens.js'),
	UserSettings = require('core/js/Settings.js'),
	CAbstractSettingsTabView = require('core/js/views/CAbstractSettingsTabView.js')
;

/**
 * @constructor
 */
function CCommonSettingsTabView()
{
	CAbstractSettingsTabView.call(this);
	
	this.aSkins = UserSettings.Themes;
	this.selectedSkin = ko.observable(UserSettings.DefaultTheme);

	this.aLanguages = UserSettings.Languages;
	this.selectedLanguage = ko.observable(UserSettings.DefaultLanguage);

	this.autoRefreshInterval = ko.observable(UserSettings.AutoRefreshIntervalMinutes);

	this.timeFormat = ko.observable(UserSettings.defaultTimeFormat());

	this.desktopNotifications = ko.observable(UserSettings.DesktopNotifications);
	this.isDesktopNotificationsEnable = ko.observable((window.Notification && window.Notification.permission !== 'denied'));
	this.desktopNotifications.subscribe(function (bChecked) {
		var self = this;

		if (bChecked && window.Notification.permission === 'default')
		{
			window.Notification.requestPermission(function (sPermission)
			{
				if (sPermission === 'denied')
				{
					self.desktopNotifications(false);
					self.isDesktopNotificationsEnable(false);
				}
			});
		}
	}, this);
}

_.extendOwn(CCommonSettingsTabView.prototype, CAbstractSettingsTabView.prototype);

CCommonSettingsTabView.prototype.ViewTemplate = 'Core_CommonSettingsTabView';

CCommonSettingsTabView.prototype.getState = function ()
{
	var aState = [
		this.selectedSkin(),
		this.selectedLanguage(),
		this.autoRefreshInterval(),
		this.timeFormat(),
		this.desktopNotifications()
	];
	
	return aState.join(':');
};

CCommonSettingsTabView.prototype.revert = function ()
{
	this.selectedSkin(UserSettings.DefaultTheme);
	this.selectedLanguage(UserSettings.DefaultLanguage);
	this.autoRefreshInterval(UserSettings.AutoRefreshIntervalMinutes);
	this.timeFormat(UserSettings.defaultTimeFormat());
	this.desktopNotifications(UserSettings.DesktopNotifications);
	this.updateCurrentState();
};

/**
 * Sends a request to the server to save the settings.
 */
CCommonSettingsTabView.prototype.save = function ()
{
	this.isSaving(true);
	
	this.updateCurrentState();
	
	Ajax.send('Core', 'UpdateSettings', {
		'AutoCheckMailInterval': Utils.pInt(this.autoRefreshInterval()),
		'DefaultTheme': this.selectedSkin(),
		'DefaultLanguage': this.selectedLanguage(),
		'DefaultTimeFormat': this.timeFormat(),
		'DesktopNotifications': this.desktopNotifications() ? '1' : '0'
	}, this.onResponse, this);
};

/**
 * Parses the response from the server. If the settings are normally stored, then updates them. 
 * Otherwise an error message.
 * 
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CCommonSettingsTabView.prototype.onResponse = function (oResponse, oRequest)
{
	this.isSaving(false);

	if (oResponse.Result === false)
	{
		Api.showErrorByCode(oResponse, TextUtils.i18n('SETTINGS/ERROR_SETTINGS_SAVING_FAILED'));
	}
	else
	{
		var
			oParameters = JSON.parse(oRequest.Parameters),
			bNeedReload = (oParameters.DefaultTheme !== UserSettings.DefaultTheme ||
				oParameters.DefaultLanguage !== UserSettings.DefaultLanguage)
		;
		
		if (bNeedReload)
		{
			window.location.reload();
		}
		else
		{
			UserSettings.updateCommonSettings(oParameters.AutoCheckMailInterval,
				oParameters.DefaultTheme, oParameters.DefaultLanguage,
				oParameters.DefaultTimeFormat, oParameters.DesktopNotifications);

			Screens.showReport(TextUtils.i18n('SETTINGS/COMMON_REPORT_UPDATED_SUCCESSFULLY'));
		}
	}
};

module.exports = new CCommonSettingsTabView();
