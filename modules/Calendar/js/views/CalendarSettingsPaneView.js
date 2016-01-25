'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	Utils = require('core/js/utils/Common.js'),
	
	UserSettings = require('core/js/Settings.js'),
	ModulesManager = require('core/js/ModulesManager.js'),
	CAbstractSettingsFormView = ModulesManager.run('Settings', 'getAbstractSettingsFormViewClass'),
	
	CalendarUtils = require('modules/Calendar/js/utils/Calendar.js'),
	
	CalendarCache = require('modules/Calendar/js/Cache.js'),
	Settings = require('modules/Calendar/js/Settings.js')
;

/**
 * @constructor
 */
function CCalendarSettingsPaneView()
{
	CAbstractSettingsFormView.call(this, 'Calendar');

	this.availableTimes = ko.observableArray(CalendarUtils.getTimeListStepHour((UserSettings.defaultTimeFormat() !== Enums.TimeFormat.F24) ? 'hh:mm A' : 'HH:mm'));
	UserSettings.defaultTimeFormat.subscribe(function () {
		this.availableTimes(CalendarUtils.getTimeListStepHour((UserSettings.defaultTimeFormat() !== Enums.TimeFormat.F24) ? 'hh:mm A' : 'HH:mm'));
	}, this);

	/* Editable fields */
	this.showWeekends = ko.observable(Settings.CalendarShowWeekEnds);
	this.selectedWorkdayStarts = ko.observable(Settings.CalendarWorkDayStarts);
	this.selectedWorkdayEnds = ko.observable(Settings.CalendarWorkDayEnds);
	this.showWorkday = ko.observable(Settings.CalendarShowWorkDay);
	this.weekStartsOn = ko.observable(Settings.CalendarWeekStartsOn);
	this.defaultTab = ko.observable(Settings.CalendarDefaultTab);
	/*-- Editable fields */
}

_.extendOwn(CCalendarSettingsPaneView.prototype, CAbstractSettingsFormView.prototype);

CCalendarSettingsPaneView.prototype.ViewTemplate = 'Calendar_CalendarSettingsPaneView';

CCalendarSettingsPaneView.prototype.getCurrentValues = function()
{
	return [
		this.showWeekends(),
		this.selectedWorkdayStarts(),
		this.selectedWorkdayEnds(),
		this.showWorkday(),
		this.weekStartsOn(),
		this.defaultTab()
	];
};

CCalendarSettingsPaneView.prototype.revertGlobalValues = function()
{
	this.showWeekends(Settings.CalendarShowWeekEnds);
	this.selectedWorkdayStarts(Settings.CalendarWorkDayStarts);
	this.selectedWorkdayEnds(Settings.CalendarWorkDayEnds);
	this.showWorkday(Settings.CalendarShowWorkDay);
	this.weekStartsOn(Settings.CalendarWeekStartsOn);
	this.defaultTab(Settings.CalendarDefaultTab);
};

CCalendarSettingsPaneView.prototype.getParametersForSave = function ()
{
	return {
		'ShowWeekEnds': this.showWeekends() ? 1 : 0,
		'ShowWorkDay': this.showWorkday() ? 1 : 0,
		'WorkDayStarts': Utils.pInt(this.selectedWorkdayStarts()),
		'WorkDayEnds': Utils.pInt(this.selectedWorkdayEnds()),
		'WeekStartsOn': Utils.pInt(this.weekStartsOn()),
		'DefaultTab': Utils.pInt(this.defaultTab())
	};
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CCalendarSettingsPaneView.prototype.applySavedValues = function (oParameters)
{
	CalendarCache.calendarSettingsChanged(true);

	Settings.update(oParameters.ShowWeekEnds, oParameters.ShowWorkDay, oParameters.WorkDayStarts,
					oParameters.WorkDayEnds, oParameters.WeekStartsOn, oParameters.DefaultTab);
};

module.exports = new CCalendarSettingsPaneView();
