'use strict';

module.exports = function (oSettings) {
	require('fullcalendar');
	require('modules/Calendar/js/BaseTabExtMethods.js');

	var
		_ = require('underscore'),
		
		Settings = require('modules/Calendar/js/Settings.js'),
		
		ManagerComponents = require('modules/Calendar/js/manager-components.js'),
		ComponentsMethods = ManagerComponents()
	;
	
	Settings.init(oSettings);
	
	return _.extend({
		screens: {
			'main': function () {
				return require('modules/Calendar/js/views/CalendarView.js');
			}
		},
		getHeaderItem: function () {
			return require('modules/Calendar/js/views/HeaderItemView.js');
		},
		getWeekStartsOn: function () {
			return Settings.CalendarWeekStartsOn;
		}
	}, ComponentsMethods);
};
