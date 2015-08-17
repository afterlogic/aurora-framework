
/**
 * @constructor
 */
function CCalendarSettingsViewModel()
{
	this.showWeekends = ko.observable(AppData.User.CalendarShowWeekEnds);

	this.loading = ko.observable(false);

	this.availableTimes = ko.observableArray(Utils.Calendar.getTimeListStepHour((AppData.User.defaultTimeFormat() !== Enums.TimeFormat.F24) ? 'hh:mm A' : 'HH:mm'));
	AppData.User.defaultTimeFormat.subscribe(function () {
		this.availableTimes(Utils.Calendar.getTimeListStepHour((AppData.User.defaultTimeFormat() !== Enums.TimeFormat.F24) ? 'hh:mm A' : 'HH:mm'));
	}, this);

	this.selectedWorkdayStarts = ko.observable(AppData.User.CalendarWorkDayStarts);
	this.selectedWorkdayEnds = ko.observable(AppData.User.CalendarWorkDayEnds);
	
	this.showWorkday = ko.observable(AppData.User.CalendarShowWorkDay);
	this.weekStartsOn = ko.observable(AppData.User.CalendarWeekStartsOn);
	this.defaultTab = ko.observable(AppData.User.CalendarDefaultTab);
	
	this.firstState = this.getState();
}

CCalendarSettingsViewModel.prototype.TemplateName = 'Settings_CalendarSettingsViewModel';

CCalendarSettingsViewModel.prototype.TabName = Enums.SettingsTab.Calendar;

CCalendarSettingsViewModel.prototype.TabTitle = Utils.i18n('SETTINGS/TAB_CALENDAR');

CCalendarSettingsViewModel.prototype.init = function()
{
	this.showWeekends(AppData.User.CalendarShowWeekEnds);
	this.selectedWorkdayStarts(AppData.User.CalendarWorkDayStarts);
	this.selectedWorkdayEnds(AppData.User.CalendarWorkDayEnds);
	this.showWorkday(AppData.User.CalendarShowWorkDay);
	this.weekStartsOn(AppData.User.CalendarWeekStartsOn);
	this.defaultTab(AppData.User.CalendarDefaultTab);
};

CCalendarSettingsViewModel.prototype.getState = function()
{
	var sState = [
		this.showWeekends(),
		this.selectedWorkdayStarts(),
		this.selectedWorkdayEnds(),
		this.showWorkday(),
		this.weekStartsOn(),
		this.defaultTab()
	];
	return sState.join(':');
};

CCalendarSettingsViewModel.prototype.updateFirstState = function()
{
	this.firstState = this.getState();
};

CCalendarSettingsViewModel.prototype.isChanged = function()
{
	if (this.firstState && this.getState() !== this.firstState)
	{
		return true;
	}
	else
	{
		return false;
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CCalendarSettingsViewModel.prototype.onResponse = function (oResponse, oRequest)
{
	this.loading(false);

	if (oResponse.Result === false)
	{
		App.Api.showErrorByCode(oResponse, Utils.i18n('SETTINGS/ERROR_CALENDAR_SETTINGS_SAVING_FAILED'));
	}
	else
	{
		App.CalendarCache.calendarSettingsChanged(true);
		
		AppData.User.updateCalendarSettings(oRequest.ShowWeekEnds, oRequest.ShowWorkDay, 
			oRequest.WorkDayStarts, oRequest.WorkDayEnds, oRequest.WeekStartsOn, oRequest.DefaultTab);

		App.Api.showReport(Utils.i18n('SETTINGS/COMMON_REPORT_UPDATED_SUCCESSFULLY'));
	}
};

CCalendarSettingsViewModel.prototype.onSaveClick = function ()
{
	var
		oParameters = {
			'Action': 'UserSettingsUpdate',
			'ShowWeekEnds': this.showWeekends() ? 1 : 0,
			'ShowWorkDay': this.showWorkday() ? 1 : 0,
			'WorkDayStarts': parseInt(this.selectedWorkdayStarts(), 10),
			'WorkDayEnds': parseInt(this.selectedWorkdayEnds(), 10),
			'WeekStartsOn': parseInt(this.weekStartsOn(), 10),
			'DefaultTab': parseInt(this.defaultTab(), 10)
		}
	;

	this.loading(true);
	this.updateFirstState();
	App.Ajax.send(oParameters, this.onResponse, this);
};
