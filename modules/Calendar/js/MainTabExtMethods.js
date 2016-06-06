'use strict';

var
	CalendarCache = require('modules/%ModuleName%/js/Cache.js'),
	MainTabCalendarMethods = {
		markIcalTypeByFile: function (sFile, sType, sCancelDecision, sReplyDecision, sCalendarId, sSelectedCalendar) {
			CalendarCache.markIcalTypeByFile(sFile, sType, sCancelDecision, sReplyDecision, sCalendarId, sSelectedCalendar);
		},
		markCalendarChanged: function ()
		{
			CalendarCache.calendarChanged(true);
		}
	}
;

window.MainTabCalendarMethods = MainTabCalendarMethods;

module.exports = {};