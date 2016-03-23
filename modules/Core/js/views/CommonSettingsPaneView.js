'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	TextUtils = require('modules/Core/js/utils/Text.js'),
	Types = require('modules/Core/js/utils/Types.js'),
	
	ModulesManager = require('modules/Core/js/ModulesManager.js'),
	UserSettings = require('modules/Core/js/Settings.js'),
	
	CAbstractSettingsFormView = ModulesManager.run('Settings', 'getAbstractSettingsFormViewClass')
;

/**
 * @constructor
 */
function CCommonSettingsPaneView()
{
	CAbstractSettingsFormView.call(this);
	
	this.aSkins = UserSettings.ThemeList;
	this.aLanguages = UserSettings.LanguageList;
	
	/* Editable fields */
	this.selectedSkin = ko.observable(UserSettings.Theme);
	this.selectedLanguage = ko.observable(UserSettings.Language);
	this.autoRefreshInterval = ko.observable(UserSettings.AutoRefreshIntervalMinutes);
	this.aRefreshIntervals = [
		{name: TextUtils.i18n('CORE/LABEL_REFRESH_OFF'), value: 0},
		{name: TextUtils.i18n('CORE/LABEL_MINUTES_PLURAL', {'COUNT': 1}, null, 1), value: 1},
		{name: TextUtils.i18n('CORE/LABEL_MINUTES_PLURAL', {'COUNT': 3}, null, 3), value: 3},
		{name: TextUtils.i18n('CORE/LABEL_MINUTES_PLURAL', {'COUNT': 5}, null, 5), value: 5},
		{name: TextUtils.i18n('CORE/LABEL_MINUTES_PLURAL', {'COUNT': 10}, null, 10), value: 10},
		{name: TextUtils.i18n('CORE/LABEL_MINUTES_PLURAL', {'COUNT': 15}, null, 15), value: 15},
		{name: TextUtils.i18n('CORE/LABEL_MINUTES_PLURAL', {'COUNT': 20}, null, 20), value: 20},
		{name: TextUtils.i18n('CORE/LABEL_MINUTES_PLURAL', {'COUNT': 30}, null, 30), value: 30}
	];
	this.timeFormat = ko.observable(UserSettings.timeFormat());
	this.desktopNotifications = ko.observable(UserSettings.AllowDesktopNotifications);
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
	this.selectedSkin(UserSettings.Theme);
	this.selectedLanguage(UserSettings.Language);
	this.autoRefreshInterval(UserSettings.AutoRefreshIntervalMinutes);
	this.timeFormat(UserSettings.timeFormat());
	this.desktopNotifications(UserSettings.AllowDesktopNotifications);
};

/**
 * Gets values from the editable fields and prepares object for passing to the server and saving settings therein.
 * 
 * @returns {Object}
 */
CCommonSettingsPaneView.prototype.getParametersForSave = function ()
{
	return {
		'AutoCheckMailInterval': Types.pInt(this.autoRefreshInterval()),
		'Theme': this.selectedSkin(),
		'Language': this.selectedLanguage(),
		'TimeFormat': this.timeFormat(),
		'AllowDesktopNotifications': this.desktopNotifications() ? '1' : '0'
	};
};

/**
 * Applies saved values of settings to the global settings object.
 * 
 * @param {Object} oParameters Object that have been obtained by getParameters function.
 */
CCommonSettingsPaneView.prototype.applySavedValues = function (oParameters)
{
	if (oParameters.Theme !== UserSettings.Theme || oParameters.Language !== UserSettings.Language)
	{
		window.location.reload();
	}
	else
	{
		UserSettings.updateCommonSettings(oParameters.AutoCheckMailInterval,
			oParameters.Theme, oParameters.Language,
			oParameters.TimeFormat, oParameters.AllowDesktopNotifications);
	}
};

module.exports = new CCommonSettingsPaneView();
