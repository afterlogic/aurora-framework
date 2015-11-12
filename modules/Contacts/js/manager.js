'use strict';

module.exports = function (oSettings) {
	require('modules/Contacts/js/enums.js');

	var
		_ = require('underscore'),
		
		Settings = require('modules/Contacts/js/Settings.js'),
		
		ManagerSuggestions = require('modules/Contacts/js/manager-suggestions.js'),
		SuggestionsMethods = ManagerSuggestions(),
		
		ManagerContactCard = require('modules/Contacts/js/manager-contact-card.js'),
		ContactCardMethods = ManagerContactCard()
	;

	Settings.init(oSettings);
	
	return _.extend(_.extend({
		start: function (ModulesManager) {
			ContactCardMethods.start(ModulesManager);
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
			return new CHeaderItemView(TextUtils.i18n('HEADER/CONTACTS'), TextUtils.i18n('TITLE/CONTACTS'));
		}
	}, SuggestionsMethods), ContactCardMethods);
};
