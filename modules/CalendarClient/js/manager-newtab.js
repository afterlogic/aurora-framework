'use strict';

module.exports = function (oAppData) {
	require('modules/%ModuleName%/js/enums.js');
	
	var
		_ = require('underscore'),
		
		Settings = require('modules/%ModuleName%/js/Settings.js'),
		oSettings = _.extend({}, oAppData[Settings.ServerModuleName] || {}, oAppData['%ModuleName%'] || {})
	;
	
	Settings.init(oSettings);
	
	return {
		isAvailable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser;
		},
		start: function (ModulesManager) {
			ModulesManager.run('MailClient', 'registerMessagePaneController', [require('modules/%ModuleName%/js/views/IcalAttachmentView.js'), 'BeforeMessageBody']);
		}
	};
};
