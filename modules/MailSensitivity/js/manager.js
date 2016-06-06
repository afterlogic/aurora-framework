'use strict';

module.exports = function () {
	return {
		isAvaliable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser;
		},
		start: function (ModulesManager) {
			ModulesManager.run('Mail', 'registerMessagePaneController', [require('modules/%ModuleName%/js/views/MessageControlView.js'), 'BeforeMessageHeaders']);
			ModulesManager.run('Mail', 'registerComposeToolbarController', [require('modules/%ModuleName%/js/views/ComposeDropdownView.js')]);
		}
	};
};