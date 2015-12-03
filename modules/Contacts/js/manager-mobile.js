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
		start: function (ModulesManager) {
			ModulesManager.run('Mail', 'registerMessagePaneController', [require('modules/Contacts/js/views/VcardAttachmentView.js'), 'BeforeMessageBody']);
		},
		screens: {
			'main': function () {
				return require('modules/Contacts/js/views/ContactsView.js');
			}
		},
		getHeaderItem: function () {
			var
				TextUtils = require('core/js/utils/Text.js'),
				CHeaderItemView = require('core/js/views/CHeaderItemView.js')
			;
			return new CHeaderItemView(TextUtils.i18n('HEADER/CONTACTS'));
		}
	}, SuggestionsMethods);
};
