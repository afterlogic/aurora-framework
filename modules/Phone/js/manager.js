'use strict';

module.exports = function (oSettings) {
	require('modules/%ModuleName%/js/enums.js');

	var
		Browser = require('modules/Core/js/Browser.js'),
		App = require('modules/Core/js/App.js'),

		Settings = require('modules/%ModuleName%/js/Settings.js')
	;

	Settings.init(oSettings);
	
	return {
		isAvaliable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser;
		},
		getHeaderItem: function () {
			return (!Browser.ie && !App.isMobile()) ? require('modules/%ModuleName%/js/views/PhoneView.js') : null;
		}
	};
};
