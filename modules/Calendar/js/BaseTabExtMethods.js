'use strict';

var
	CalendarCache = require('modules/Calendar/js/Cache.js'),
	BaseTabMethods = {
		markIcalTypeByFile: function (sFile, sType, sCancelDecision, sReplyDecision, sCalendarId, sSelectedCalendar) {
			CalendarCache.markIcalTypeByFile(sFile, sType, sCancelDecision, sReplyDecision, sCalendarId, sSelectedCalendar);
		},
		markCalendarChanged: function ()
		{
			CalendarCache.calendarChanged(true);
		}
	}
;

window.BaseTabCalendarMethods = BaseTabMethods;

module.exports = {};