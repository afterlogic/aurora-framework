'use strict';

require('modules/Helpdesk/js/enums.js');

var Settings = require('modules/Helpdesk/js/Settings.js');

module.exports = function (oSettings) {
	Settings.init(oSettings);
	
	return {
		screens: {
			'main': function () {
				return require('modules/Helpdesk/js/views/CHelpdeskView.js');
			}
		},
		getHeaderItem: function () {
			return require('modules/Helpdesk/js/views/HeaderItemView.js');
		}
	};
};
