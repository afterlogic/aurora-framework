'use strict';

module.exports = function (oSettings) {
	var
		_ = require('underscore'),
		
		Settings = require('modules/Contacts/js/Settings.js'),
		
		ManagerSuggestions = require('modules/Contacts/js/manager-suggestions.js'),
		SuggestionsMethods = ManagerSuggestions()
	;

	Settings.init(oSettings);
	
	return _.extend({
		isAvaliable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser;
		},
		start: function (ModulesManager) {
			ModulesManager.run('Mail', 'registerMessagePaneController', [require('modules/Contacts/js/views/VcardAttachmentView.js'), 'BeforeMessageBody']);
		},
		getScreens: function () {
			return {
				'main': function () {
					return require('modules/Contacts/js/views/ContactsView.js');
				}
			};
		},
		getHeaderItem: function () {
			return require('modules/Contacts/js/views/HeaderItemView.js');
		}
	}, SuggestionsMethods);
};
