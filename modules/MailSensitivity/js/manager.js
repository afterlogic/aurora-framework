'use strict';

module.exports = function () {
	require('modules/MailSensitivity/js/enums.js');
	
	return {
		start: function (ModulesManager) {
			ModulesManager.run('Mail', 'registerMessagePaneTopController', [require('modules/MailSensitivity/js/views/MessageControlView.js')]);
			ModulesManager.run('Mail', 'registerComposeToolbarController', [require('modules/MailSensitivity/js/views/ComposeDropdownView.js')]);
		}
	};
};