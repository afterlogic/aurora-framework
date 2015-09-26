'use strict';

var
	ko = require('knockout'),
	$ = require('jquery')
;

/**
 * @constructor
 */
function CSelectCalendarPopup()
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

CSelectCalendarPopup.prototype.PopupTemplate = 'Calendar_SelectCalendarPopup';

/**
 * @param {Object} oParameters
 */
CSelectCalendarPopup.prototype.onShow = function (oParameters)
{
	this.fCallback = oParameters.CallbackSave;
	this.fProceedUploading = oParameters.ProceedUploading;
	this.calendars = oParameters.Calendars;
	this.calendarsList(oParameters.EditableCalendars);
	this.selectedCalendarId(oParameters.DefaultCalendarId);
	this.changeCalendarColor(this.selectedCalendarId());
};

CSelectCalendarPopup.prototype.onSaveClick = function ()
{
    if (this.fCallback)
    {
		this.fCallback(this.selectedCalendarId(), this.fProceedUploading);
    }
    this.closeCommand();
};

CSelectCalendarPopup.prototype.onCancelClick = function ()
{
	this.closeCommand();
};

/**
 * @param {string} sId
 */
CSelectCalendarPopup.prototype.changeCalendarColor = function (sId)
{
	if ($.isFunction(this.calendars.getCalendarById))
	{
		var oCalendar = this.calendars.getCalendarById(sId);
		if (oCalendar)
		{
			this.calendarColor('');
			this.calendarColor(oCalendar.color());
		}
	}
};

module.exports = new CSelectCalendarPopup();