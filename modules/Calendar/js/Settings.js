'use strict';

var _ = require('underscore');

module.exports = {
	init: function (oSettings) {
		_.extendOwn(this, oSettings);
	},
	update: function (iShowWeekEnds, iShowWorkDay, iWorkDayStarts, iWorkDayEnds, iWeekStartsOn, iDefaultTab) {
		this.DefaultTab = iDefaultTab.toString();
		this.HighlightWorkingDays = iShowWeekEnds === 1;
		this.HighlightWorkingHours = iShowWorkDay === 1;
		this.WeekStartsOn = iWeekStartsOn;
		this.WorkdayEnds = iWorkDayEnds.toString();
		this.WorkdayStarts = iWorkDayStarts.toString();
	}
};
