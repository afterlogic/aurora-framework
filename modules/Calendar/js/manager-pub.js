'use strict';

module.exports = function (oSettings) {
	require('fullcalendar');
	require('modules/%ModuleName%/js/enums.js');

	var Settings = require('modules/%ModuleName%/js/Settings.js');
	Settings.init(oSettings);
	
	return {
		isAvailable: function (iUserRole, bPublic) {
			return bPublic;
		},
		getScreens: function () {
			return {
				'main': function () {
					return require('modules/%ModuleName%/js/views/CalendarView.js');
				}
			};
		},
		getHeaderItem: function () {
			return require('modules/%ModuleName%/js/views/PublicHeaderItem.js');
		}
	};
};
