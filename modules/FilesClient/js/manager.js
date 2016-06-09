'use strict';

module.exports = function (oSettings) {
	require('modules/%ModuleName%/js/enums.js');

	var
		TextUtils = require('modules/Core/js/utils/Text.js'),
				
		Ajax = require('modules/%ModuleName%/js/Ajax.js'),
		Settings = require('modules/%ModuleName%/js/Settings.js'),
		
		HeaderItemView = null
	;
	
	Settings.init(oSettings);
	
	return {
		enableModule: Settings.enableModule,
		
		isAvailable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser;
		},
		start: function (ModulesManager) {
			ModulesManager.run('SettingsClient', 'registerSettingsTab', [function () { return require('modules/%ModuleName%/js/views/FilesSettingsPaneView.js'); }, Settings.HashModuleName, TextUtils.i18n('%MODULENAME%/LABEL_SETTINGS_TAB')]);
		},
		getScreens: function () {
			var oScreens = {};
			oScreens[Settings.HashModuleName] = function () {
				var CFilesView = require('modules/%ModuleName%/js/views/CFilesView.js');
				return new CFilesView();
			};
			return oScreens;
		},
		getHeaderItem: function () {
			var
				TextUtils = require('modules/Core/js/utils/Text.js'),
				CHeaderItemView = require('modules/Core/js/views/CHeaderItemView.js')
			;
			
			HeaderItemView = new CHeaderItemView(TextUtils.i18n('%MODULENAME%/ACTION_SHOW_FILES'));
			
			return {
				item: HeaderItemView,
				name: Settings.HashModuleName
			};
		},
		getSelectFilesPopup: function () {
			return require('modules/%ModuleName%/js/popups/SelectFilesPopup.js');
		},
		getMobileSyncSettingsView: function () {
			return require('modules/%ModuleName%/js/views/MobileSyncSettingsView.js');
		},
		saveFilesByHashes: function (aHashes) {
			if (HeaderItemView)
			{
				HeaderItemView.recivedAnim(true);
			}
			Ajax.send('SaveFilesByHashes', { 'Hashes': aHashes }, this.onSaveAttachmentsToFilesResponse, this);
		}
	};
};
