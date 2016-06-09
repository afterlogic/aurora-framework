'use strict';

module.exports = function (oAppData) {
	require('modules/%ModuleName%/js/enums.js');
	
	require('modules/%ModuleName%/js/koBindings.js');

	var
		_ = require('underscore'),
		
		TextUtils = require('modules/Core/js/utils/Text.js'),
				
		Settings = require('modules/%ModuleName%/js/Settings.js'),
		oSettings = _.extend({}, oAppData[Settings.ServerModuleName] || {}, oAppData['%ModuleName%'] || {}),
		CheckState = require('modules/%ModuleName%/js/CheckState.js')
	;
	
	Settings.init(oSettings);
	
	return {
		isAvailable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser || iUserRole === Enums.UserRole.RegisteredUser;
		},
		start: function (ModulesManager) {
			ModulesManager.run('SettingsClient', 'registerSettingsTab', [function () { return require('modules/%ModuleName%/js/views/HelpdeskSettingsPaneView.js'); }, Settings.HashModuleName, TextUtils.i18n('%MODULENAME%/LABEL_SETTINGS_TAB')]);
		},
		getScreens: function () {
			var oScreens = {};
			oScreens[Settings.HashModuleName] = function () {
				return require('modules/%ModuleName%/js/views/HelpdeskView.js');
			};
			return oScreens;
		},
		getHeaderItem: function () {
			CheckState.start();
			return {
				item: require('modules/%ModuleName%/js/views/HeaderItemView.js'),
				name: Settings.HashModuleName
			};
		}
	};
};
