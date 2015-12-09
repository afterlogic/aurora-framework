'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	TextUtils = require('core/js/utils/Text.js'),
	CAbstractPopup = require('core/js/popups/CAbstractPopup.js')
;

/**
 * @constructor
 */
function CEditEventRecurrencePopup()
{
	CAbstractPopup.call(this);
	
	this.fCallback = null;
	this.confirmDesc = TextUtils.i18n('CALENDAR/EDIT_RECURRENCE_CONFIRM_DESCRIPTION');
	this.onlyThisInstanceButtonText = ko.observable(TextUtils.i18n('CALENDAR/ONLY_THIS_INSTANCE'));
	this.allEventsButtonText = ko.observable(TextUtils.i18n('CALENDAR/ALL_EVENTS_IN_THE_SERIES'));
	this.cancelButtonText = ko.observable(TextUtils.i18n('MAIN/BUTTON_CANCEL'));
}

_.extendOwn(CEditEventRecurrencePopup.prototype, CAbstractPopup.prototype);

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

	this.closePopup();
};

CEditEventRecurrencePopup.prototype.allEventsButtonClick = function ()
{
	if (this.fCallback)
	{
		this.fCallback(Enums.CalendarEditRecurrenceEvent.AllEvents);
	}

	this.closePopup();
};

CEditEventRecurrencePopup.prototype.cancelPopup = function ()
{
	if (this.fCallback)
	{
		this.fCallback(Enums.CalendarEditRecurrenceEvent.None);
	}

	this.closePopup();
};

module.exports = new CEditEventRecurrencePopup();