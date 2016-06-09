'use strict';

var Types = require('modules/CoreClient/js/utils/Types.js');

module.exports = {
	ServerModuleName: 'Calendar',
	HashModuleName: 'calendar',
	
	AllowAppointments: true,
	AllowShare: true,
	DefaultTab: '3', // 1 - day, 2 - week, 3 - month
	HighlightWorkingDays: true,
	HighlightWorkingHours: true,
	PublicCalendarId: '',
	WeekStartsOn: '0', // 0 - sunday
	WorkdayEnds: '18',
	WorkdayStarts: '9',
	
	init: function (oAppDataSection) {
		if (oAppDataSection)
		{
			this.AllowAppointments = !!oAppDataSection.CalendarAppointments;
			this.AllowShare = !!oAppDataSection.CalendarSharing;
			this.DefaultTab = Types.pString(oAppDataSection.CalendarDefaultTab); // 1 - day, 2 - week, 3 - month
			this.HighlightWorkingDays = !!oAppDataSection.CalendarShowWeekEnds;
			this.HighlightWorkingHours = !!oAppDataSection.CalendarShowWorkDay;
			this.PublicCalendarId = Types.pString(oAppDataSection.CalendarPubHash);
			this.WeekStartsOn = Types.pString(oAppDataSection.CalendarWeekStartsOn); // 0 - sunday
			this.WorkdayEnds = Types.pString(oAppDataSection.CalendarWorkDayEnds);
			this.WorkdayStarts = Types.pString(oAppDataSection.CalendarWorkDayStarts);
		}
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
