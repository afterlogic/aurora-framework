'use strict';

module.exports = function (oSettings) {
	require('modules/HelpDeskClient/js/enums.js');
	
	require('modules/HelpDeskClient/js/koBindings.js');

	var Settings = require('modules/HelpDeskClient/js/Settings.js');
	Settings.init(oSettings);
	
	return {
		screens: {
			'main': function () {
				var App = require('modules/Core/js/App.js');
				if (App.isAuth())
				{
					return require('modules/HelpDeskClient/js/views/HelpdeskView.js');
				}
				else
				{
					return require('modules/HelpDeskClient/js/views/LoginView.js');
				}
			}
		},
		getHeaderItem: function () {
//			CheckState.start();
			return require('modules/HelpDeskClient/js/views/HeaderItemView.js');
		}
	};
};
