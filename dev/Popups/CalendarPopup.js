/**
 * @constructor
 */
function CalendarPopup()
{
	this.fCallback = null;
	
	this.calendarId = ko.observable(null);
	this.calendarName = ko.observable('');
	this.calendarDescription = ko.observable('');
	
	this.calendarNameFocus = ko.observable(false);
	this.calendarDescriptionFocus = ko.observable(false);
	
	this.colors = ko.observableArray([]);
	this.selectedColor = ko.observable(this.colors()[0]);
	
	this.popupTitle = ko.observable('');
}

CalendarPopup.prototype.clearFields = function ()
{
	this.calendarName('');
	this.calendarDescription('');
	this.selectedColor(this.colors[0]);
	this.calendarId(null);
};

/**
 * @param {Function} fCallback
 * @param {Array} aColors
 * @param {Object} oCalendar
 */
CalendarPopup.prototype.onShow = function (fCallback, aColors, oCalendar)
{
	this.clearFields();
	if (Utils.isFunc(fCallback))
	{
		this.fCallback = fCallback;
	}
	if (!Utils.isUnd(aColors))
	{
		this.colors(aColors);
		this.selectedColor(aColors[0]);		
	}
	if (!Utils.isUnd(oCalendar))
	{
		this.popupTitle(oCalendar.name() ? Utils.i18n("CALENDAR/TITLE_EDIT_CALENDAR") : Utils.i18n("CALENDAR/TITLE_CREATE_CALENDAR"));
		this.calendarName(oCalendar.name ? oCalendar.name() : '');
		this.calendarDescription(oCalendar.description ? oCalendar.description() : '');
		this.selectedColor(oCalendar.color ? oCalendar.color() : '');
		this.calendarId(oCalendar.id ? oCalendar.id : null);
	} else {
		this.popupTitle(Utils.i18n("CALENDAR/TITLE_CREATE_CALENDAR"));
	}

	$(document).on('keyup.calendar_create', _.bind(function(ev) {
		if (ev.keyCode === Enums.Key.Enter)
		{
			this.onSaveClick();
		}
	}, this));
};

CalendarPopup.prototype.onHide = function (fCallback, aColors, oCalendar)
{
	$(document).off('keyup.calendar_create');
};

/**
 * @return {string}
 */
CalendarPopup.prototype.popupTemplate = function ()
{
	return 'Popups_Calendar_CalendarPopupViewModel';
};

CalendarPopup.prototype.onSaveClick = function ()
{
	if (this.calendarName() === '')
	{
		App.Screens.showPopup(AlertPopup, [Utils.i18n('CALENDAR/WARNING_BLANK_CALENDAR_NAME')]);
	}
	else
	{
		if (this.fCallback)
		{
			this.fCallback(this.calendarName(), this.calendarDescription(), this.selectedColor(), this.calendarId());
			this.clearFields();
		}
		this.closeCommand();
	}
};

CalendarPopup.prototype.onCancelClick = function ()
{
	this.closeCommand();
};