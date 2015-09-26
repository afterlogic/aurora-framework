'use strict';

var
	ko = require('knockout'),
	$ = require('jquery'),
	
	TextUtils = require('core/js/utils/Text.js')
;

/**
 * @constructor
 */
function CEditEventRecurrencePopup()
{
	this.fCallback = null;
	this.confirmDesc = TextUtils.i18n('CALENDAR/EDIT_RECURRENCE_CONFIRM_DESCRIPTION');
	this.onlyThisInstanceButtonText = ko.observable(TextUtils.i18n('CALENDAR/ONLY_THIS_INSTANCE'));
	this.allEventsButtonText = ko.observable(TextUtils.i18n('CALENDAR/ALL_EVENTS_IN_THE_SERIES'));
	this.cancelButtonText = ko.observable(TextUtils.i18n('MAIN/BUTTON_CANCEL'));
}

CEditEventRecurrencePopup.prototype.PopupTemplate = 'Calendar_EditEventRecurrencePopup';

/**
 * @param {Function} fCallback
 */
CEditEventRecurrencePopup.prototype.onShow = function (fCallback)
{
	if ($.isFunction(fCallback))
	{
		this.fCallback = fCallback;
	}
};

CEditEventRecurrencePopup.prototype.onlyThisInstanceButtonClick = function ()
{
	if (this.fCallback)
	{
		this.fCallback(Enums.CalendarEditRecurrenceEvent.OnlyThisInstance);
	}

	this.closeCommand();
};

CEditEventRecurrencePopup.prototype.allEventsButtonClick = function ()
{
	if (this.fCallback)
	{
		this.fCallback(Enums.CalendarEditRecurrenceEvent.AllEvents);
	}

	this.closeCommand();
};

CEditEventRecurrencePopup.prototype.cancelButtonClick = function ()
{
	if (this.fCallback)
	{
		this.fCallback(Enums.CalendarEditRecurrenceEvent.None);
	}

	this.closeCommand();
};

CEditEventRecurrencePopup.prototype.onEscHandler = function ()
{
	this.cancelButtonClick();
};

module.exports = new CEditEventRecurrencePopup();