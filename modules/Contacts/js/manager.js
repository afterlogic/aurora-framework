'use strict';

module.exports = function (oSettings) {
	require('modules/Contacts/js/MainTabExtMethods.js');
	
	var
		_ = require('underscore'),
		$ = require('jquery'),
		
		TextUtils = require('modules/Core/js/utils/Text.js'),
		
		Settings = require('modules/Contacts/js/Settings.js'),
		
		ManagerComponents = require('modules/Contacts/js/manager-components.js'),
		ComponentsMethods = ManagerComponents(),
		fComponentsStart = ComponentsMethods.start
	;

	Settings.init(oSettings);
	
	return _.extend(ComponentsMethods, {
		isAvaliable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser;
		},
		start: function (ModulesManager) {
			ModulesManager.run('Settings', 'registerSettingsTab', [function () { return require('modules/Contacts/js/views/ContactsSettingsPaneView.js'); }, 'contacts', TextUtils.i18n('CONTACTS/LABEL_SETTINGS_TAB')]);
			if ($.isFunction(fComponentsStart))
			{
				fComponentsStart(ModulesManager);
			}
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
		},
		isGlobalContactsAllowed: function () {
			return _.indexOf(Settings.Storages, 'global') !== -1;
		},
		getMobileSyncSettingsView: function () {
			return require('modules/Contacts/js/views/MobileSyncSettingsView.js');
		}
	});
};
