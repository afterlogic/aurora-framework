'use strict';

module.exports = function (oSettings) {
	require('modules/Files/js/enums.js');

	var
		TextUtils = require('core/js/utils/Text.js'),
				
		Ajax = require('modules/Files/js/Ajax.js'),
		Settings = require('modules/Files/js/Settings.js'),
		
		HeaderItemView = null
	;
	
	Settings.init(oSettings);
	
	return {
		start: function (ModulesManager) {
			ModulesManager.run('Settings', 'registerSettingsTab', [function () { return require('modules/Files/js/views/FilesSettingsPaneView.js'); }, 'cloud-storage', TextUtils.i18n('SETTINGS/TAB_CLOUD_STORAGE')]);
		},
		screens: {
			'main': function () {
				var CFilesView = require('modules/Files/js/views/CFilesView.js');
				return new CFilesView();
			}
		},
		getHeaderItem: function () {
			var
				TextUtils = require('core/js/utils/Text.js'),
				CHeaderItemView = require('core/js/views/CHeaderItemView.js')
			;
			
			HeaderItemView = new CHeaderItemView(TextUtils.i18n('HEADER/FILESTORAGE'));
			
			return HeaderItemView;
		},
		getSelectFilesPopup: function () {
			return require('modules/Files/js/popups/SelectFilesPopup.js');
		},
		getMobileSyncSettingsView: function () {
			return require('modules/Files/js/views/MobileSyncSettingsView.js');
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
