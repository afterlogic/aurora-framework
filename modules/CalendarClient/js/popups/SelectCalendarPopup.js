'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	CAbstractPopup = require('modules/CoreClient/js/popups/CAbstractPopup.js')
;

/**
 * @constructor
 */
function CSelectCalendarPopup()
{
	CAbstractPopup.call(this);
	
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

_.extendOwn(CSelectCalendarPopup.prototype, CAbstractPopup.prototype);

CSelectCalendarPopup.prototype.PopupTemplate = '%ModuleName%_SelectCalendarPopup';

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
	this.closePopup();
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