'use strict';

module.exports = function (oAppData) {
	require('modules/%ModuleName%/js/MainTabExtMethods.js');
	
	var
		_ = require('underscore'),
		$ = require('jquery'),
		
		TextUtils = require('modules/Core/js/utils/Text.js'),
		
		Settings = require('modules/%ModuleName%/js/Settings.js'),
		oSettings = _.extend({}, oAppData[Settings.ServerModuleName] || {}, oAppData['%ModuleName%'] || {}),
		
		ManagerComponents = require('modules/%ModuleName%/js/manager-components.js'),
		ComponentsMethods = ManagerComponents(),
		fComponentsStart = ComponentsMethods.start
	;

	Settings.init(oSettings);
	
	return _.extend(ComponentsMethods, {
		isAvailable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser;
		},
		start: function (ModulesManager) {
			ModulesManager.run('SettingsClient', 'registerSettingsTab', [function () { return require('modules/%ModuleName%/js/views/ContactsSettingsPaneView.js'); }, Settings.HashModuleName, TextUtils.i18n('%MODULENAME%/LABEL_SETTINGS_TAB')]);
			if ($.isFunction(fComponentsStart))
			{
				fComponentsStart(ModulesManager);
			}
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
		},
		isGlobalContactsAllowed: function () {
			return _.indexOf(Settings.Storages, 'global') !== -1;
		},
		getMobileSyncSettingsView: function () {
			return require('modules/%ModuleName%/js/views/MobileSyncSettingsView.js');
		}
	});
};
