'use strict';

module.exports = function (oSettings) {
	var
		TextUtils = require('core/js/utils/Text.js'),
		
		Settings = require('modules/MobileSync/js/Settings.js')
	;
	
	Settings.init(oSettings);
	
	return {
		start: function (ModulesManager) {
			ModulesManager.run('Settings', 'registerSettingsTab', [function () { return require('modules/MobileSync/js/views/MobileSyncSettingsPaneView.js'); }, 'mobile_sync', TextUtils.i18n('SETTINGS/TAB_MOBILE_SYNC')]);
		}
	};
};
