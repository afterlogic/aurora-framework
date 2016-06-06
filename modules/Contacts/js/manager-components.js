'use strict';

module.exports = function (oSettings) {
	require('modules/%ModuleName%/js/enums.js');
	
	if (oSettings)
	{
		var Settings = require('modules/%ModuleName%/js/Settings.js');
		Settings.init(oSettings);
	}
	
	var
		_ = require('underscore'),
		
		ManagerSuggestions = require('modules/%ModuleName%/js/manager-suggestions.js'),
		SuggestionsMethods = ManagerSuggestions(),
		ContactCard = require('modules/%ModuleName%/js/ContactCard.js')
	;

	return _.extend({
		isAvaliable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser;
		},
		start: function (ModulesManager) {
			ModulesManager.run('Mail', 'registerMessagePaneController', [require('modules/%ModuleName%/js/views/VcardAttachmentView.js'), 'BeforeMessageBody']);
		},
		applyContactsCards: function ($Addresses) {
			ContactCard.applyTo($Addresses);
		}
	}, SuggestionsMethods);
};
