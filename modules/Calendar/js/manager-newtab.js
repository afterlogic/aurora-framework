'use strict';

module.exports = function (oSettings) {
	require('modules/Calendar/js/enums.js');
	
	var Settings = require('modules/Calendar/js/Settings.js');
	Settings.init(oSettings);
	
	return {
		isAvaliable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser;
		},
		start: function (ModulesManager) {
			ModulesManager.run('Mail', 'registerMessagePaneController', [require('modules/Calendar/js/views/IcalAttachmentView.js'), 'BeforeMessageBody']);
		}
	};
};
