'use strict';

module.exports = function (oSettings) {

	var Settings = require('modules/%ModuleName%/js/Settings.js');
	Settings.init(oSettings);
	
	return {
		isAvailable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser;
		},
		getChangePasswordPopup: function () {
			return require('modules/%ModuleName%/js/popups/ChangePasswordPopup.js');
		},
		getResetPasswordView: function () {
			return require('modules/%ModuleName%/js/views/ResetPasswordView.js');
		}
	};
};