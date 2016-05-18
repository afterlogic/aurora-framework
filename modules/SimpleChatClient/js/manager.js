'use strict';

module.exports = function (oSettings) {
	require('modules/SimpleChatClient/js/enums.js');
	require('modules/SimpleChatClient/js/koBindings.js');

	var
		TextUtils = require('modules/Core/js/utils/Text.js'),
				
		Settings = require('modules/SimpleChatClient/js/Settings.js'),
		CheckState = require('modules/SimpleChatClient/js/CheckState.js')
	;
	Settings.init(oSettings);
	
	return {
		isAvaliable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser || iUserRole === Enums.UserRole.RegisteredUser;
		},
		start: function (ModulesManager) {
			ModulesManager.run('Settings', 'registerSettingsTab', [function () { return require('modules/HelpDeskClient/js/views/HelpdeskSettingsPaneView.js'); }, 'helpdesk', TextUtils.i18n('HELPDESK/LABEL_SETTINGS_TAB')]);
		},
		getScreens: function () {
			return {
				'main': function () {
					CheckState.end();
					return require('modules/HelpDeskClient/js/views/HelpdeskView.js');
				}
			};
		},
		getHeaderItem: function () {
			CheckState.start();
			return require('modules/HelpDeskClient/js/views/HeaderItemView.js');
		}
	};
};
