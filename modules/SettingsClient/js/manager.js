'use strict';

module.exports = function (oSettings) {
	var Settings = require('modules/%ModuleName%/js/Settings.js');
	Settings.init(oSettings);
	
	return {
		isAvailable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser || iUserRole === Enums.UserRole.RegisteredUser;
		},
		getScreens: function () {
			var oScreens = {};
			oScreens[Settings.HashModuleName] = function () {
				return require('modules/%ModuleName%/js/views/SettingsView.js');
			};
			return oScreens;
		},
		getHeaderItem: function () {
			var
				TextUtils = require('modules/Core/js/utils/Text.js'),
				CHeaderItemView = require('modules/Core/js/views/CHeaderItemView.js')
			;
			return {
				item: new CHeaderItemView(TextUtils.i18n('CORE/HEADING_SETTINGS_TABNAME')),
				name: Settings.HashModuleName
			};
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
