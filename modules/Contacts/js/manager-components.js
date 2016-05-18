'use strict';

module.exports = function (oSettings) {
	require('modules/Contacts/js/enums.js');
	
	if (oSettings)
	{
		var Settings = require('modules/Contacts/js/Settings.js');
		Settings.init(oSettings);
	}
	
	var
		_ = require('underscore'),
		
		ManagerSuggestions = require('modules/Contacts/js/manager-suggestions.js'),
		SuggestionsMethods = ManagerSuggestions(),
		ContactCard = require('modules/Contacts/js/ContactCard.js')
	;

	return _.extend({
		isAvaliable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser;
		},
		start: function (ModulesManager) {
			ModulesManager.run('Mail', 'registerMessagePaneController', [require('modules/Contacts/js/views/VcardAttachmentView.js'), 'BeforeMessageBody']);
		},
		applyContactsCards: function ($Addresses) {
			ContactCard.applyTo($Addresses);
		}
	}, SuggestionsMethods);
};
