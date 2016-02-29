'use strict';

module.exports = function (oSettings) {

	var Settings = require('modules/ChangePassword/js/Settings.js');
	Settings.init(oSettings);
	
	return {
		getChangePasswordPopup: function () {
			return require('modules/ChangePassword/js/popups/ChangePasswordPopup.js');
		},
		getResetPasswordView: function () {
			return require('modules/ChangePassword/js/views/ResetPasswordView.js');
		}
	};
};