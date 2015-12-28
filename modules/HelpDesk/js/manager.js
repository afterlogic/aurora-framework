'use strict';

module.exports = function (oSettings) {
	require('modules/HelpDesk/js/enums.js');

	var
		TextUtils = require('core/js/utils/Text.js'),
				
		Settings = require('modules/HelpDesk/js/Settings.js'),
		CheckState = require('modules/HelpDesk/js/CheckState.js')
	;
	Settings.init(oSettings);
	
	return {
		start: function (ModulesManager) {
			ModulesManager.run('Settings', 'registerSettingsTab', [function () { return require('modules/HelpDesk/js/views/HelpdeskSettingsPaneView.js'); }, 'helpdesk', TextUtils.i18n('SETTINGS/TAB_HELPDESK')]);
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
