'use strict';

module.exports = function (oSettings) {
	require('modules/Files/js/enums.js');

	var Settings = require('modules/Files/js/Settings.js');
	Settings.init(oSettings);
	
	return {
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
			return new CHeaderItemView(TextUtils.i18n('HEADER/FILESTORAGE'), TextUtils.i18n('TITLE/FILESTORAGE'));
		},
		getSelectFilesPopup: function () {
			return require('modules/Files/js/popups/SelectFilesPopup.js');
		}
	};
};
