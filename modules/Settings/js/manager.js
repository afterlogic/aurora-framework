'use strict';

module.exports = function (oSettings) {
	var Settings = require('modules/%ModuleName%/js/Settings.js');
	Settings.init(oSettings);
	
	return {
		isAvaliable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser || iUserRole === Enums.UserRole.RegisteredUser;
		},
		getScreens: function () {
			return {
				'main': function () {
					return require('modules/%ModuleName%/js/views/SettingsView.js');
				}
			};
		},
		getHeaderItem: function () {
			var
				TextUtils = require('modules/Core/js/utils/Text.js'),
				CHeaderItemView = require('modules/Core/js/views/CHeaderItemView.js')
			;
			return new CHeaderItemView(TextUtils.i18n('CORE/HEADING_SETTINGS_TABNAME'));
		},
		registerSettingsTab: function (fGetTabView, oTabName, oTabTitle) {
			var SettingsView = require('modules/%ModuleName%/js/views/SettingsView.js');
			SettingsView.registerTab(fGetTabView, oTabName, oTabTitle);
		},
		getSettingsUtils: function () {
			return require('modules/%ModuleName%/js/utils/Settings.js');
		},
		getAbstractSettingsFormViewClass: function () {
			return require('modules/%ModuleName%/js/views/CAbstractSettingsFormView.js');
		},
		setAddHash: function (aAddHash) {
			var SettingsView = require('modules/%ModuleName%/js/views/SettingsView.js');
			SettingsView.setAddHash(aAddHash);
		}
	};
};
