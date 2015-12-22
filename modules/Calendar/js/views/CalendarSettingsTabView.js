'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	TextUtils = require('core/js/utils/Text.js'),
	Utils = require('core/js/utils/Common.js'),
	
	Api = require('core/js/Api.js'),
	Screens = require('core/js/Screens.js'),
	UserSettings = require('core/js/Settings.js'),
	ModulesManager = require('core/js/ModulesManager.js'),
	CAbstractSettingsTabView = ModulesManager.run('Settings', 'getAbstractSettingsTabViewClass'),
	
	CalendarUtils = require('modules/Calendar/js/utils/Calendar.js'),
	
	Ajax = require('modules/Calendar/js/Ajax.js'),
	CalendarCache = require('modules/Calendar/js/Cache.js'),
	Settings = require('modules/Calendar/js/Settings.js')
;

/**
 * @constructor
 */
function CCalendarSettingsTabView()
{
	CAbstractSettingsTabView.call(this);

	this.showWeekends = ko.observable(Settings.CalendarShowWeekEnds);

	this.availableTimes = ko.observableArray(CalendarUtils.getTimeListStepHour((UserSettings.defaultTimeFormat() !== Enums.TimeFormat.F24) ? 'hh:mm A' : 'HH:mm'));
	UserSettings.defaultTimeFormat.subscribe(function () {
		this.availableTimes(CalendarUtils.getTimeListStepHour((UserSettings.defaultTimeFormat() !== Enums.TimeFormat.F24) ? 'hh:mm A' : 'HH:mm'));
	}, this);

	this.selectedWorkdayStarts = ko.observable(Settings.CalendarWorkDayStarts);
	this.selectedWorkdayEnds = ko.observable(Settings.CalendarWorkDayEnds);
	
	this.showWorkday = ko.observable(Settings.CalendarShowWorkDay);
	this.weekStartsOn = ko.observable(Settings.CalendarWeekStartsOn);
	this.defaultTab = ko.observable(Settings.CalendarDefaultTab);
}

_.extendOwn(CCalendarSettingsTabView.prototype, CAbstractSettingsTabView.prototype);

CCalendarSettingsTabView.prototype.ViewTemplate = 'Calendar_CalendarSettingsTabView';

CCalendarSettingsTabView.prototype.getState = function()
{
	var aState = [
		this.showWeekends(),
		this.selectedWorkdayStarts(),
		this.selectedWorkdayEnds(),
		this.showWorkday(),
		this.weekStartsOn(),
		this.defaultTab()
	];
	
	return aState.join(':');
};

CCalendarSettingsTabView.prototype.revert = function()
{
	this.showWeekends(Settings.CalendarShowWeekEnds);
	this.selectedWorkdayStarts(Settings.CalendarWorkDayStarts);
	this.selectedWorkdayEnds(Settings.CalendarWorkDayEnds);
	this.showWorkday(Settings.CalendarShowWorkDay);
	this.weekStartsOn(Settings.CalendarWeekStartsOn);
	this.defaultTab(Settings.CalendarDefaultTab);
	this.updateCurrentState();
};

CCalendarSettingsTabView.prototype.save = function ()
{
	this.isSaving(true);
	
	this.updateCurrentState();
	
	Ajax.send('UpdateSettings', {
		'ShowWeekEnds': this.showWeekends() ? 1 : 0,
		'ShowWorkDay': this.showWorkday() ? 1 : 0,
		'WorkDayStarts': Utils.pInt(this.selectedWorkdayStarts()),
		'WorkDayEnds': Utils.pInt(this.selectedWorkdayEnds()),
		'WeekStartsOn': Utils.pInt(this.weekStartsOn()),
		'DefaultTab': Utils.pInt(this.defaultTab())
	}, this.onResponse, this);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CCalendarSettingsTabView.prototype.onResponse = function (oResponse, oRequest)
{
	this.isSaving(false);

	if (oResponse.Result === false)
	{
		Api.showErrorByCode(oResponse, TextUtils.i18n('SETTINGS/ERROR_CALENDAR_SETTINGS_SAVING_FAILED'));
	}
	else
	{
		var oParameters = JSON.parse(oRequest.Parameters);
		
		CalendarCache.calendarSettingsChanged(true);
		
		Settings.update(oParameters.ShowWeekEnds, oParameters.ShowWorkDay, oParameters.WorkDayStarts,
						oParameters.WorkDayEnds, oParameters.WeekStartsOn, oParameters.DefaultTab);

		Screens.showReport(TextUtils.i18n('SETTINGS/COMMON_REPORT_UPDATED_SUCCESSFULLY'));
	}
};

module.exports = new CCalendarSettingsTabView();
