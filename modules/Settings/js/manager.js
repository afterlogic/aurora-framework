'use strict';

module.exports = function (oSettings) {
	var Settings = require('modules/Settings/js/Settings.js');
	Settings.init(oSettings);
	
	return {
		screens: {
			'main': function () {
				return require('modules/Settings/js/views/CSettingsView.js');
			}
		},
		getHeaderItem: function () {
			var
				TextUtils = require('core/js/utils/Text.js'),
				CHeaderItemView = require('core/js/views/CHeaderItemView.js')
			;
			return new CHeaderItemView(TextUtils.i18n('HEADER/SETTINGS'));
		}
	};
};