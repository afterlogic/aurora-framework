'use strict';

require('modules/Mail/js/enums.js');

var
	TextUtils = require('core/js/utils/Text.js'),
	
	Settings = require('modules/Mail/js/Settings.js')
;

module.exports = function (oSettings) {
	Settings.init(oSettings);
	
	return {
		screens: {
			'main': function () {
				return require('modules/Mail/js/views/CMessagePaneView.js');
			}
		},
		getBrowserTitle: function () {
			return TextUtils.i18n('TITLE/FILESTORAGE');
		}
	};
};
