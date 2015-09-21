'use strict';

var
	TextUtils = require('core/js/utils/Text.js'),
	CHeaderItemView = require('core/js/views/CHeaderItemView.js'),
	
	Settings = require('modules/Settings/js/Settings.js')
;

module.exports = function (oSettings) {
	Settings.init(oSettings);
	
	return {
		screens: {
			'main': {
				'Model': require('modules/Settings/js/views/CSettingsView.js'),
				'TemplateName': 'Settings_SettingsViewModel'
			}
		},
		headerItem: new CHeaderItemView(TextUtils.i18n('HEADER/SETTINGS')),
		getBrowserTitle: function () {
			return TextUtils.i18n('TITLE/SETTINGS');
		}
	};
};