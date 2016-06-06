'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	Types = require('modules/Core/js/utils/Types.js'),
	
	UserSettings = require('modules/Core/js/Settings.js'),
	ModulesManager = require('modules/Core/js/ModulesManager.js'),
	CAbstractSettingsFormView = ModulesManager.run('Settings', 'getAbstractSettingsFormViewClass'),
	
	CalendarUtils = require('modules/%ModuleName%/js/utils/Calendar.js'),
	
	CalendarCache = require('modules/%ModuleName%/js/Cache.js'),
	Settings = require('modules/%ModuleName%/js/Settings.js')
;

/**
 * @constructor
 */
function CCalendarSettingsPaneView()
{
	CAbstractSettingsFormView.call(this, 'Calendar');

	this.availableTimes = ko.observableArray(CalendarUtils.getTimeListStepHour((UserSettings.timeFormat() !== Enums.TimeFormat.F24) ? 'hh:mm A' : 'HH:mm'));
	UserSettings.timeFormat.subscribe(function () {
		this.availableTimes(CalendarUtils.getTimeListStepHour((UserSettings.timeFormat() !== Enums.TimeFormat.F24) ? 'hh:mm A' : 'HH:mm'));
	}, this);

	/* Editable fields */
	this.showWeekends = ko.observable(Settings.HighlightWorkingDays);
	this.selectedWorkdayStarts = ko.observable(Settings.WorkdayStarts);
	this.selectedWorkdayEnds = ko.observable(Settings.WorkdayEnds);
	this.showWorkday = ko.observable(Settings.HighlightWorkingHours);
	this.weekStartsOn = ko.observable(Settings.CalendarWeekStartsOn);
	this.defaultTab = ko.observable(Settings.DefaultTab);
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
	this.showWeekends(Settings.HighlightWorkingDays);
	this.selectedWorkdayStarts(Settings.WorkdayStarts);
	this.selectedWorkdayEnds(Settings.WorkdayEnds);
	this.showWorkday(Settings.HighlightWorkingHours);
	this.weekStartsOn(Settings.WeekStartsOn);
	this.defaultTab(Settings.DefaultTab);
};

CCalendarSettingsPaneView.prototype.getParametersForSave = function ()
{
	return {
		'HighlightWorkingDays': this.showWeekends() ? 1 : 0,
		'ShowWorkDay': this.showWorkday() ? 1 : 0,
		'WorkDayStarts': Types.pInt(this.selectedWorkdayStarts()),
		'WorkDayEnds': Types.pInt(this.selectedWorkdayEnds()),
		'WeekStartsOn': Types.pInt(this.weekStartsOn()),
		'DefaultTab': Types.pInt(this.defaultTab())
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
