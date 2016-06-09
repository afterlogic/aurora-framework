'use strict';

module.exports = function (oAppData) {
	return {
		isAvailable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser;
		},
		start: function (ModulesManager) {
			ModulesManager.run('MailClient', 'registerMessagePaneController', [require('modules/%ModuleName%/js/views/MessageControlView.js'), 'BeforeMessageHeaders']);
			ModulesManager.run('MailClient', 'registerComposeToolbarController', [require('modules/%ModuleName%/js/views/ComposeDropdownView.js')]);
		}
	};
};