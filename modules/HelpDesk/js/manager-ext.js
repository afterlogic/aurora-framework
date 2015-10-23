'use strict';

module.exports = function (oSettings) {
	require('modules/HelpDesk/js/enums.js');

	var Settings = require('modules/HelpDesk/js/Settings.js');
	Settings.init(oSettings);
	
	return {
		screens: {
			'main': function () {
				return require('modules/HelpDesk/js/views/HelpdeskView.js');
			},
			'auth': function () {
				return require('modules/HelpDesk/js/views/LoginView.js');
			}
		}
	};
};
