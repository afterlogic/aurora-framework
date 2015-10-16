'use strict';

require('modules/HelpDesk/js/enums.js');

var
	TextUtils = require('core/js/utils/Text.js'),
	
	Settings = require('modules/HelpDesk/js/Settings.js')
;

module.exports = function (oSettings) {
	Settings.init(oSettings);
	
	return {
		screens: {
			'main': function () {
				return require('modules/HelpDesk/js/views/CHelpdeskView.js');
			},
			'auth': function () {
				return require('modules/HelpDesk/js/views/CLoginView.js');
			}
		},
		getBrowserTitle: function () {
			return TextUtils.i18n('TITLE/FILESTORAGE');
		}
	};
};
