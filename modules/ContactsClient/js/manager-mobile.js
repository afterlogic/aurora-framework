'use strict';

module.exports = function (oSettings) {
	var
		_ = require('underscore'),
		
		Settings = require('modules/%ModuleName%/js/Settings.js'),
		
		ManagerSuggestions = require('modules/%ModuleName%/js/manager-suggestions.js'),
		SuggestionsMethods = ManagerSuggestions()
	;

	Settings.init(oSettings);
	
	return _.extend({
		isAvailable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser;
		},
		start: function (ModulesManager) {
			ModulesManager.run('Mail', 'registerMessagePaneController', [require('modules/%ModuleName%/js/views/VcardAttachmentView.js'), 'BeforeMessageBody']);
		},
		getScreens: function () {
			var oScreens = {};
			oScreens[Settings.HashModuleName] = function () {
				return require('modules/%ModuleName%/js/views/ContactsView.js');
			};
			return oScreens;
		},
		getHeaderItem: function () {
			return {
				item: require('modules/%ModuleName%/js/views/HeaderItemView.js'),
				name: Settings.HashModuleName
			};
		}
	}, SuggestionsMethods);
};
