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
			var oScreens = {};
			oScreens[Settings.HashModuleName] = function () {
				return require('modules/%ModuleName%/js/views/CalendarView.js');
			};
			return oScreens;
		},
		getHeaderItem: function () {
			return {
				item: require('modules/%ModuleName%/js/views/PublicHeaderItem.js'),
				name: Settings.HashModuleName
			};
		}
	};
};
