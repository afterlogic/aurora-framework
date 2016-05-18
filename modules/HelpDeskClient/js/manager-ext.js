'use strict';

module.exports = function (oSettings) {
	require('modules/HelpDeskClient/js/enums.js');
	
	require('modules/HelpDeskClient/js/koBindings.js');

	var Settings = require('modules/HelpDeskClient/js/Settings.js');
	Settings.init(oSettings);
	
	return {
		isAvaliable: function (iUserRole, bPublic) {
			return bPublic;
		},
		getScreens: function () {
			return {
				'main': function () {
					return require('modules/HelpDeskClient/js/views/LoginView.js');
				}
			};
		}
	};
};
