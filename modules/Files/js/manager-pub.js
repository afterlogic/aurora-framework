'use strict';

require('modules/Files/js/enums.js');

var
	TextUtils = require('core/js/utils/Text.js'),
	
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
		getBrowserTitle: function () {
			return TextUtils.i18n('TITLE/FILESTORAGE');
		}
	};
};
