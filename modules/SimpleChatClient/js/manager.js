'use strict';

module.exports = function (oSettings) {
	var
		TextUtils = require('modules/Core/js/utils/Text.js'),
		
		Settings = require('modules/%ModuleName%/js/Settings.js')
	;
	Settings.init(oSettings);
	
	return {
		/**
		 * Returns true if simple chat module is available for certain user role and public or not public mode.
		 * 
		 * @param {int} iUserRole User role, wich enum values are described in modules/Core/js/enums.js
		 * @param {boolean} bPublic **true** if applications runs in public mode (for example, public calendar or public contact)
		 * 
		 * @returns {Boolean}
		 */
		isAvaliable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser || iUserRole === Enums.UserRole.RegisteredUser;
		},
		
		/**
		 * Registers settings tab of simple chat module before application start.
		 * 
		 * @param {Object} ModulesManager
		 */
		start: function (ModulesManager) {
			ModulesManager.run('Settings', 'registerSettingsTab', [function () { return require('modules/%ModuleName%/js/views/SettingsPaneView.js'); }, 'simplechat', TextUtils.i18n('%MODULENAME%/LABEL_SETTINGS_TAB')]);
		},
		
		/**
		 * Returns list of functions that are return module screens.
		 * 
		 * @returns {Object}
		 */
		getScreens: function () {
			return {
				'main': function () {
					return require('modules/%ModuleName%/js/views/MainView.js');
				}
			};
		},
		
		/**
		 * Returns object of header item view of simple chat module.
		 * 
		 * @returns {Object}
		 */
		getHeaderItem: function () {
			var CHeaderItemView = require('modules/Core/js/views/CHeaderItemView.js');

			return new CHeaderItemView(TextUtils.i18n('%MODULENAME%/ACTION_SHOW_CHAT'));
		}
	};
};
