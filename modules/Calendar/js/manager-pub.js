'use strict';

require('fullcalendar');
require('modules/Calendar/js/enums.js');

var
	TextUtils = require('core/js/utils/Text.js'),
			
	PublicHeaderItem = require('modules/Calendar/js/views/PublicHeaderItem.js'),
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
		getHeaderItem: function () {
			return PublicHeaderItem;
		},
		getBrowserTitle: function () {
			return TextUtils.i18n('TITLE/CALENDAR');
		}
	};
};
