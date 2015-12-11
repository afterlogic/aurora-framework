'use strict';

module.exports = function (oSettings) {
	require('modules/Calendar/js/enums.js');
	
	if (oSettings)
	{
		var Settings = require('modules/Calendar/js/Settings.js');
		Settings.init(oSettings);
	}
	
	return {
		start: function (ModulesManager) {
			ModulesManager.run('Mail', 'registerMessagePaneController', [require('modules/Calendar/js/views/IcalAttachmentView.js'), 'BeforeMessageBody']);
		}
	};
};
