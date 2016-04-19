'use strict';

module.exports = function (oSettings) {
	require('modules/HelpDeskClient/js/enums.js');
	console.log(oSettings);
	require('modules/HelpDeskClient/js/koBindings.js');

	var Settings = require('modules/HelpDeskClient/js/Settings.js');
	Settings.init(oSettings);
	
	return {
		screens: {
			'main': function () {
				return require('modules/HelpDeskClient/js/views/HelpdeskView.js');
			},
			'auth': function () {
				return require('modules/HelpDeskClient/js/views/LoginView.js');
			}
		}
	};
};
