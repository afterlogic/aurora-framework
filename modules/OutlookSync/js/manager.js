'use strict';

module.exports = function (oSettings) {
	var
		TextUtils = require('modules/Core/js/utils/Text.js'),
		
		Settings = require('modules/OutlookSync/js/Settings.js')
	;
	
	Settings.init(oSettings);
	
	return {
		start: function (ModulesManager) {
			ModulesManager.run('Settings', 'registerSettingsTab', [function () { return require('modules/OutlookSync/js/views/OutlookSyncSettingsPaneView.js'); }, 'outlook_sync', TextUtils.i18n('OUTLOOKSYNC/LABEL_SETTINGS_TAB')]);
		}
	};
};
