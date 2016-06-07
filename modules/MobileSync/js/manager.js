'use strict';

module.exports = function (oSettings) {
	var
		TextUtils = require('modules/Core/js/utils/Text.js'),
		
		Settings = require('modules/%ModuleName%/js/Settings.js')
	;
	
	Settings.init(oSettings);
	
	return {
		isAvailable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser;
		},
		start: function (ModulesManager) {
			ModulesManager.run('Settings', 'registerSettingsTab', [function () { return require('modules/%ModuleName%/js/views/MobileSyncSettingsPaneView.js'); }, 'mobile_sync', TextUtils.i18n('%MODULENAME%/LABEL_SETTINGS_TAB')]);
		}
	};
};
