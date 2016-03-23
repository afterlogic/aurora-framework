'use strict';

module.exports = function (oSettings) {
	require('modules/HelpDesk/js/enums.js');
	
	require('modules/HelpDesk/js/koBindings.js');

	var
		TextUtils = require('modules/Core/js/utils/Text.js'),
				
		Settings = require('modules/HelpDesk/js/Settings.js'),
		CheckState = require('modules/HelpDesk/js/CheckState.js')
	;
	Settings.init(oSettings);
	
	return {
		start: function (ModulesManager) {
			ModulesManager.run('Settings', 'registerSettingsTab', [function () { return require('modules/HelpDesk/js/views/HelpdeskSettingsPaneView.js'); }, 'helpdesk', TextUtils.i18n('HELPDESK/LABEL_SETTINGS_TAB')]);
		},
		screens: {
			'main': function () {
				CheckState.end();
				return require('modules/HelpDesk/js/views/HelpdeskView.js');
			}
		},
		getHeaderItem: function () {
			CheckState.start();
			return require('modules/HelpDesk/js/views/HeaderItemView.js');
		}
	};
};
