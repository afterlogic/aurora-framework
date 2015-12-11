'use strict';

module.exports = function (oSettings) {
	require('modules/Contacts/js/BaseTabExtMethods.js');
	
	var
		_ = require('underscore'),
		
		Settings = require('modules/Contacts/js/Settings.js'),
		
		ManagerComponents = require('modules/Contacts/js/manager-components.js'),
		ComponentsMethods = ManagerComponents()
	;

	Settings.init(oSettings);
	
	return _.extend({
		screens: {
			'main': function () {
				return require('modules/Contacts/js/views/ContactsView.js');
			}
		},
		getHeaderItem: function () {
			return require('modules/Contacts/js/views/HeaderItemView.js');
		},
		isGlobalContactsAllowed: function () {
			return Settings.Storages.indexOf('global') !== -1;
		}
	}, ComponentsMethods);
};
