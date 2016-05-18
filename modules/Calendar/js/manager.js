'use strict';

module.exports = function (oSettings) {
	require('modules/Calendar/js/koBindings.js');
	require('modules/Calendar/js/enums.js');
	require('fullcalendar');
	require('modules/Calendar/js/MainTabExtMethods.js');

	var
		TextUtils = require('modules/Core/js/utils/Text.js'),
		
		Settings = require('modules/Calendar/js/Settings.js')
	;
	
	Settings.init(oSettings);
	
	return {
		isAvaliable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser;
		},
		start: function (ModulesManager) {
			ModulesManager.run('Mail', 'registerMessagePaneController', [require('modules/Calendar/js/views/IcalAttachmentView.js'), 'BeforeMessageBody']);
			ModulesManager.run('Settings', 'registerSettingsTab', [function () { return require('modules/Calendar/js/views/CalendarSettingsPaneView.js'); }, 'calendar', TextUtils.i18n('CALENDAR/LABEL_SETTINGS_TAB')]);
		},
		screens: {
			'main': function () {
				return require('modules/Calendar/js/views/CalendarView.js');
			}
		},
		getHeaderItem: function () {
			return require('modules/Calendar/js/views/HeaderItemView.js');
		},
		getWeekStartsOn: function () {
			return Settings.WeekStartsOn;
		},
		getMobileSyncSettingsView: function () {
			return require('modules/Calendar/js/views/MobileSyncSettingsView.js');
		}
	};
};
