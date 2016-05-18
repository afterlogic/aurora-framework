'use strict';

module.exports = function (oSettings) {
	require('fullcalendar');
	require('modules/Calendar/js/enums.js');

	var Settings = require('modules/Calendar/js/Settings.js');
	Settings.init(oSettings);
	
	return {
		isAvaliable: function (iUserRole, bPublic) {
			return bPublic;
		},
		getScreens: function () {
			return {
				'main': function () {
					return require('modules/Calendar/js/views/CalendarView.js');
				}
			};
		},
		getHeaderItem: function () {
			return require('modules/Calendar/js/views/PublicHeaderItem.js');
		}
	};
};
