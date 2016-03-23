'use strict';

module.exports = function (oSettings) {
	var Settings = require('modules/Settings/js/Settings.js');
	Settings.init(oSettings);
	
	return {
		screens: {
			'main': function () {
				return require('modules/Settings/js/views/SettingsView.js');
			}
		},
		getHeaderItem: function () {
			var
				TextUtils = require('modules/Core/js/utils/Text.js'),
				CHeaderItemView = require('modules/Core/js/views/CHeaderItemView.js')
			;
			return new CHeaderItemView(TextUtils.i18n('CORE/HEADING_SETTINGS_TABNAME'));
		},
		registerSettingsTab: function (fGetTabView, oTabName, oTabTitle) {
			var SettingsView = require('modules/Settings/js/views/SettingsView.js');
			SettingsView.registerTab(fGetTabView, oTabName, oTabTitle);
		},
		getSettingsUtils: function () {
			return require('modules/Settings/js/utils/Settings.js');
		},
		getAbstractSettingsFormViewClass: function () {
			return require('modules/Settings/js/views/CAbstractSettingsFormView.js');
		},
		setAddHash: function (aAddHash) {
			var SettingsView = require('modules/Settings/js/views/SettingsView.js');
			SettingsView.setAddHash(aAddHash);
		}
	};
};
