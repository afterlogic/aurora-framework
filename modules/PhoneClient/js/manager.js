'use strict';

module.exports = function (oAppData) {
	require('modules/%ModuleName%/js/enums.js');

	var
		Browser = require('modules/CoreClient/js/Browser.js'),
		App = require('modules/CoreClient/js/App.js'),

		Settings = require('modules/%ModuleName%/js/Settings.js'),
		oSettings = oAppData['%ModuleName%'] || {}
	;
	
	Settings.init(oSettings);
	
	return {
		isAvailable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser;
		},
		getHeaderItem: function () {
			return (!Browser.ie && !App.isMobile()) ? {
				item: require('modules/%ModuleName%/js/views/PhoneView.js'),
				name: Settings.HashModuleName
			} : null;
		}
	};
};
