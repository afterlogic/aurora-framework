/**
 * @constructor
 */
function CalendarSelectCalendarsPopup()
{
	this.fCallback = null;
	this.fProceedUploading = null;

	this.calendars = null;
	this.calendarsList = ko.observableArray([]);
	this.calendarColor = ko.observable('');
	this.selectedCalendarName = ko.observable('');
	this.selectedCalendarId = ko.observable('');
	this.selectedCalendarId.subscribe(function (sValue) {
		if (sValue)
		{
			var oCalendar = this.calendars.getCalendarById(sValue);

			this.selectedCalendarName(oCalendar.name());
			this.selectedCalendarIsEditable(oCalendar.isEditable());
			this.changeCalendarColor(sValue);
		}
	}, this);
	this.selectedCalendarIsEditable = ko.observable(false);
}

CalendarSelectCalendarsPopup.prototype.onShow = function (oParameters)
{
	this.fCallback = oParameters.CallbackSave;
	this.fProceedUploading = oParameters.ProceedUploading;
	this.calendars = oParameters.Calendars;
	this.calendarsList(oParameters.EditableCalendars);
	this.selectedCalendarId(oParameters.DefaultCalendarId);
	this.changeCalendarColor(this.selectedCalendarId());
};

/**
 * @return {string}
 */
CalendarSelectCalendarsPopup.prototype.popupTemplate = function ()
{
	return 'Popups_Calendar_CalendarSelectCalendarsPopupViewModel';
};

CalendarSelectCalendarsPopup.prototype.onSaveClick = function ()
{
    if (this.fCallback)
    {
		this.fCallback(this.selectedCalendarId(), this.fProceedUploading);
    }
    this.closeCommand();
};

CalendarSelectCalendarsPopup.prototype.onCancelClick = function ()
{
	this.closeCommand();
};

CalendarSelectCalendarsPopup.prototype.changeCalendarColor = function (sId)
{
	if (Utils.isFunc(this.calendars.getCalendarById))
	{
		var oCalendar = this.calendars.getCalendarById(sId);
		if (oCalendar)
		{
			this.calendarColor('');
			this.calendarColor(oCalendar.color());
		}
	}
};