'use strict';

require('modules/HelpDesk/js/enums.js');

var Settings = require('modules/HelpDesk/js/Settings.js');

module.exports = function (oSettings) {
	Settings.init(oSettings);
	
	return {
		screens: {
			'main': function () {
				return require('modules/HelpDesk/js/views/CHelpdeskView.js');
			}
		},
		getHeaderItem: function () {
			return require('modules/HelpDesk/js/views/HeaderItemView.js');
		}
	};
};
