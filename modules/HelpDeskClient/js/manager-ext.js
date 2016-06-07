'use strict';

module.exports = function (oSettings) {
	require('modules/%ModuleName%/js/enums.js');
	
	require('modules/%ModuleName%/js/koBindings.js');

	var Settings = require('modules/%ModuleName%/js/Settings.js');
	Settings.init(oSettings);
	
	return {
		isAvailable: function (iUserRole, bPublic) {
			return bPublic;
		},
		getScreens: function () {
			return {
				'main': function () {
					return require('modules/%ModuleName%/js/views/LoginView.js');
				}
			};
		}
	};
};
