'use strict';

module.exports = function (oSettings) {
	require('modules/%ModuleName%/js/MainTabExtMethods.js');
	
	var
		_ = require('underscore'),
		$ = require('jquery'),
		
		TextUtils = require('modules/Core/js/utils/Text.js'),
		
		Settings = require('modules/%ModuleName%/js/Settings.js'),
		
		ManagerComponents = require('modules/%ModuleName%/js/manager-components.js'),
		ComponentsMethods = ManagerComponents(),
		fComponentsStart = ComponentsMethods.start
	;

	Settings.init(oSettings);
	
	return _.extend(ComponentsMethods, {
		isAvaliable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser;
		},
		start: function (ModulesManager) {
			ModulesManager.run('Settings', 'registerSettingsTab', [function () { return require('modules/%ModuleName%/js/views/ContactsSettingsPaneView.js'); }, 'contacts', TextUtils.i18n('%MODULENAME%/LABEL_SETTINGS_TAB')]);
			if ($.isFunction(fComponentsStart))
			{
				fComponentsStart(ModulesManager);
			}
		},
		getScreens: function () {
			return {
				'main': function () {
					return require('modules/%ModuleName%/js/views/ContactsView.js');
				}
			};
		},
		getHeaderItem: function () {
			return require('modules/%ModuleName%/js/views/HeaderItemView.js');
		},
		isGlobalContactsAllowed: function () {
			return _.indexOf(Settings.Storages, 'global') !== -1;
		},
		getMobileSyncSettingsView: function () {
			return require('modules/%ModuleName%/js/views/MobileSyncSettingsView.js');
		}
	});
};
