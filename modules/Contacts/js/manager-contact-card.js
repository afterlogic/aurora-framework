'use strict';

module.exports = function (oSettings) {
	if (oSettings)
	{
		var Settings = require('modules/Contacts/js/Settings.js');
		Settings.init(oSettings);
	}
	
	return {
		start: function (ModulesManager) {
			ModulesManager.run('Mail', 'registerMessagePaneTopController', [require('modules/Contacts/js/ContactCard.js')]);
		}
	};
};
