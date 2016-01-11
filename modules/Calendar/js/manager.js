'use strict';

module.exports = function (oSettings) {
	require('modules/Calendar/js/enums.js');
	require('fullcalendar');
	require('modules/Calendar/js/BaseTabExtMethods.js');

	var
		TextUtils = require('core/js/utils/Text.js'),
		
		Settings = require('modules/Calendar/js/Settings.js')
	;
	
	Settings.init(oSettings);
	
	return {
		start: function (ModulesManager) {
			ModulesManager.run('Mail', 'registerMessagePaneController', [require('modules/Calendar/js/views/IcalAttachmentView.js'), 'BeforeMessageBody']);
			ModulesManager.run('Settings', 'registerSettingsTab', [function () { return require('modules/Calendar/js/views/CalendarSettingsPaneView.js'); }, 'calendar', TextUtils.i18n('SETTINGS/TAB_CALENDAR')]);
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
			return Settings.CalendarWeekStartsOn;
		},
		getMobileSyncSettingsView: function () {
			return require('modules/Calendar/js/views/MobileSyncSettingsView.js');
		}
	};
};
