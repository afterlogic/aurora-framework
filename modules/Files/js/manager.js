'use strict';

require('modules/Files/js/enums.js');

var
	TextUtils = require('core/js/utils/Text.js'),
	CHeaderItemView = require('core/js/views/CHeaderItemView.js'),
	
	Settings = require('modules/Files/js/Settings.js')
;

module.exports = function (oSettings) {
	Settings.init(oSettings);
	
	return {
		screens: {
			'main': function () {
				return require('modules/Files/js/views/CFilesView.js');
			}
		},
		headerItem: new CHeaderItemView(TextUtils.i18n('HEADER/FILESTORAGE'), TextUtils.i18n('TITLE/FILESTORAGE')),
		getBrowserTitle: function () {
			return TextUtils.i18n('TITLE/FILESTORAGE');
		}
	};
};
