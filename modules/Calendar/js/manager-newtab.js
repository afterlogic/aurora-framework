'use strict';

module.exports = function (oSettings) {
	require('modules/%ModuleName%/js/enums.js');
	
	var Settings = require('modules/%ModuleName%/js/Settings.js');
	Settings.init(oSettings);
	
	return {
		isAvaliable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser;
		},
		start: function (ModulesManager) {
			ModulesManager.run('Mail', 'registerMessagePaneController', [require('modules/%ModuleName%/js/views/IcalAttachmentView.js'), 'BeforeMessageBody']);
		}
	};
};
