'use strict';

module.exports = function (oSettings) {
	require('modules/%ModuleName%/js/koBindings.js');
	require('modules/%ModuleName%/js/enums.js');
	require('fullcalendar');
	require('modules/%ModuleName%/js/MainTabExtMethods.js');

	var
		TextUtils = require('modules/Core/js/utils/Text.js'),
		
		Settings = require('modules/%ModuleName%/js/Settings.js')
	;
	
	Settings.init(oSettings);
	
	return {
		isAvaliable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser;
		},
		start: function (ModulesManager) {
			ModulesManager.run('Mail', 'registerMessagePaneController', [require('modules/%ModuleName%/js/views/IcalAttachmentView.js'), 'BeforeMessageBody']);
			ModulesManager.run('Settings', 'registerSettingsTab', [function () { return require('modules/%ModuleName%/js/views/CalendarSettingsPaneView.js'); }, 'calendar', TextUtils.i18n('%MODULENAME%/LABEL_SETTINGS_TAB')]);
		},
		getScreens: function () {
			return {
				'main': function () {
					return require('modules/%ModuleName%/js/views/CalendarView.js');
				}
			};
		},
		getHeaderItem: function () {
			return require('modules/%ModuleName%/js/views/HeaderItemView.js');
		},
		getWeekStartsOn: function () {
			return Settings.WeekStartsOn;
		},
		getMobileSyncSettingsView: function () {
			return require('modules/%ModuleName%/js/views/MobileSyncSettingsView.js');
		}
	};
};
