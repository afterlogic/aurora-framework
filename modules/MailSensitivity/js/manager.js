'use strict';

module.exports = function () {
	return {
		start: function (ModulesManager) {
			ModulesManager.run('Mail', 'registerMessagePaneController', [require('modules/MailSensitivity/js/views/MessageControlView.js'), 'BeforeMessageHeaders']);
			ModulesManager.run('Mail', 'registerComposeToolbarController', [require('modules/MailSensitivity/js/views/ComposeDropdownView.js')]);
		}
	};
};