'use strict';

require('fullcalendar');
require('modules/Calendar/js/enums.js');

var
	TextUtils = require('core/js/utils/Text.js'),
	CHeaderItemView = require('core/js/views/CHeaderItemView.js'),
	
	Settings = require('modules/Calendar/js/Settings.js')
;

module.exports = function (oSettings) {
	Settings.init(oSettings);
	
	return {
		screens: {
			'main': function () {
				return require('modules/Calendar/js/views/CCalendarView.js');
			}
		},
		headerItem: new CHeaderItemView(TextUtils.i18n('HEADER/CALENDAR'), TextUtils.i18n('TITLE/CALENDAR')),
		getBrowserTitle: function () {
			return TextUtils.i18n('TITLE/CALENDAR');
		}
	};
};
