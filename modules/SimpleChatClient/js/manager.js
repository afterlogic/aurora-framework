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
			ModulesManager.run('Settings', 'registerSettingsTab', [function () { return require('modules/SimpleChatClient/js/views/SettingsPaneView.js'); }, 'simplechat', TextUtils.i18n('SIMPLECHAT/LABEL_SETTINGS_TAB')]);
		},
		getScreens: function () {
			return {
				'main': function () {
					CheckState.end();
					return require('modules/SimpleChatClient/js/views/MainView.js');
				}
			};
		},
		getHeaderItem: function () {
			CheckState.start();
			return require('modules/SimpleChatClient/js/views/HeaderItemView.js');
		}
	};
};
