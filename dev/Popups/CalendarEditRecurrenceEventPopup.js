/**
 * @constructor
 */
function CalendarEditRecurrenceEventPopup()
{
	this.fCallback = null;
	this.confirmDesc = Utils.i18n('CALENDAR/EDIT_RECURRENCE_CONFIRM_DESCRIPTION');
	this.onlyThisInstanceButtonText = ko.observable(Utils.i18n('CALENDAR/ONLY_THIS_INSTANCE'));
	this.allEventsButtonText = ko.observable(Utils.i18n('CALENDAR/ALL_EVENTS_IN_THE_SERIES'));
	this.cancelButtonText = ko.observable(Utils.i18n('MAIN/BUTTON_CANCEL'));
}

/**
 * @param {Function} fCallback
 */
CalendarEditRecurrenceEventPopup.prototype.onShow = function (fCallback)
{
	if (Utils.isFunc(fCallback))
	{
		this.fCallback = fCallback;
	}
};

/**
 * @return {string}
 */
CalendarEditRecurrenceEventPopup.prototype.popupTemplate = function ()
{
	return 'Popups_Calendar_EditRecurrenceEventPopupViewModel';
};

CalendarEditRecurrenceEventPopup.prototype.onlyThisInstanceButtonClick = function ()
{
	if (this.fCallback)
	{
		this.fCallback(Enums.CalendarEditRecurrenceEvent.OnlyThisInstance);
	}

	this.closeCommand();
};

CalendarEditRecurrenceEventPopup.prototype.allEventsButtonClick = function ()
{
	if (this.fCallback)
	{
		this.fCallback(Enums.CalendarEditRecurrenceEvent.AllEvents);
	}

	this.closeCommand();
};

CalendarEditRecurrenceEventPopup.prototype.cancelButtonClick = function ()
{
	if (this.fCallback)
	{
		this.fCallback(Enums.CalendarEditRecurrenceEvent.None);
	}

	this.closeCommand();
};

CalendarEditRecurrenceEventPopup.prototype.onEscHandler = function ()
{
	this.cancelButtonClick();
};
