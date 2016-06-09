'use strict';

function IsPgpSupported()
{
	return !!(window.crypto && window.crypto.getRandomValues);
}

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
			if (IsPgpSupported())
			{
				ModulesManager.run('Mail', 'registerMessagePaneController', [require('modules/%ModuleName%/js/views/MessageControlsView.js'), 'BeforeMessageHeaders']);
				ModulesManager.run('Mail', 'registerComposeToolbarController', [require('modules/%ModuleName%/js/views/ComposeButtonsView.js')]);
				ModulesManager.run('SettingsClient', 'registerSettingsTab', [function () { return require('modules/%ModuleName%/js/views/OpenPgpSettingsPaneView.js'); }, Settings.HashModuleName, TextUtils.i18n('%MODULENAME%/LABEL_SETTINGS_TAB')]);
			}
		}
	};
};
