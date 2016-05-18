'use strict';

module.exports = function (oSettings) {

	var Settings = require('modules/ChangePassword/js/Settings.js');
	Settings.init(oSettings);
	
	return {
		isAvaliable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser;
		},
		getChangePasswordPopup: function () {
			return require('modules/ChangePassword/js/popups/ChangePasswordPopup.js');
		},
		getResetPasswordView: function () {
			return require('modules/ChangePassword/js/views/ResetPasswordView.js');
		}
	};
};