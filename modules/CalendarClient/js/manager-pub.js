'use strict';

module.exports = function (oAppData) {
	require('fullcalendar');
	require('modules/%ModuleName%/js/enums.js');

	var
		_ = require('underscore'),
		
		Settings = require('modules/%ModuleName%/js/Settings.js'),
		oSettings = _.extend({}, oAppData[Settings.ServerModuleName] || {}, oAppData['%ModuleName%'] || {})
	;
	
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
