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
				TextUtils = require('core/js/utils/Text.js'),
				CHeaderItemView = require('core/js/views/CHeaderItemView.js')
			;
			return new CHeaderItemView(TextUtils.i18n('HEADER/SETTINGS'));
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
