'use strict';

module.exports = function (oSettings) {
	require('fullcalendar');
	require('modules/Calendar/js/enums.js');

	var Settings = require('modules/Calendar/js/Settings.js');
	Settings.init(oSettings);
	
	return {
		screens: {
			'main': function () {
				return require('modules/Calendar/js/views/CCalendarView.js');
			}
		},
		getHeaderItem: function () {
			var
				TextUtils = require('core/js/utils/Text.js'),
				CHeaderItemView = require('core/js/views/CHeaderItemView.js')
			;
			return new CHeaderItemView(TextUtils.i18n('HEADER/CALENDAR'), TextUtils.i18n('TITLE/CALENDAR'));
		}
	};
};
