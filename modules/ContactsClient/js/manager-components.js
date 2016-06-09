'use strict';

module.exports = function (oAppData) {
	require('modules/%ModuleName%/js/enums.js');
	
	if (oAppData)
	{
		var
			_ = require('underscore'),
			
			Settings = require('modules/%ModuleName%/js/Settings.js'),
			oSettings = _.extend({}, oAppData[Settings.ServerModuleName] || {}, oAppData['%ModuleName%'] || {})
		;
		
		Settings.init(oSettings);
	}
	
	var
		_ = require('underscore'),
		
		ManagerSuggestions = require('modules/%ModuleName%/js/manager-suggestions.js'),
		SuggestionsMethods = ManagerSuggestions(),
		ContactCard = require('modules/%ModuleName%/js/ContactCard.js')
	;

	return _.extend({
		isAvailable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser;
		},
		start: function (ModulesManager) {
			ModulesManager.run('MailClient', 'registerMessagePaneController', [require('modules/%ModuleName%/js/views/VcardAttachmentView.js'), 'BeforeMessageBody']);
		},
		applyContactsCards: function ($Addresses) {
			ContactCard.applyTo($Addresses);
		}
	}, SuggestionsMethods);
};
