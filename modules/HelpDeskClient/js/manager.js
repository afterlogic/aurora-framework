'use strict';

module.exports = function (oSettings) {
	require('modules/%ModuleName%/js/enums.js');
	
	require('modules/%ModuleName%/js/koBindings.js');

	var
		TextUtils = require('modules/Core/js/utils/Text.js'),
				
		Settings = require('modules/%ModuleName%/js/Settings.js'),
		CheckState = require('modules/%ModuleName%/js/CheckState.js')
	;
	Settings.init(oSettings);
	
	return {
		isAvailable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser || iUserRole === Enums.UserRole.RegisteredUser;
		},
		start: function (ModulesManager) {
			ModulesManager.run('Settings', 'registerSettingsTab', [function () { return require('modules/%ModuleName%/js/views/HelpdeskSettingsPaneView.js'); }, 'helpdesk', TextUtils.i18n('%MODULENAME%/LABEL_SETTINGS_TAB')]);
		},
		getScreens: function () {
			return {
				'main': function () {
					CheckState.end();
					return require('modules/%ModuleName%/js/views/HelpdeskView.js');
				}
			};
		},
		getHeaderItem: function () {
			CheckState.start();
			return require('modules/%ModuleName%/js/views/HeaderItemView.js');
		}
	};
};
