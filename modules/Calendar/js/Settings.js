'use strict';

var _ = require('underscore');

module.exports = {
	init: function (oSettings) {
		_.extendOwn(this, oSettings);
	},
	update: function (iShowWeekEnds, iShowWorkDay, iWorkDayStarts, iWorkDayEnds, iWeekStartsOn, iDefaultTab) {
		this.CalendarShowWeekEnds = iShowWeekEnds === 1;
		this.CalendarWorkDayStarts = iWorkDayStarts.toString();
		this.CalendarWorkDayEnds = iWorkDayEnds.toString();
		this.CalendarShowWorkDay = iShowWorkDay === 1;
		this.CalendarWeekStartsOn = iWeekStartsOn;
		this.CalendarDefaultTab = iDefaultTab.toString();
	}
};
