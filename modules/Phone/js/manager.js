'use strict';

module.exports = function (oSettings) {
	require('modules/Phone/js/enums.js');

	var
		Browser = require('core/js/Browser.js'),

		Settings = require('modules/Phone/js/Settings.js'),

		bMobileApp = false
	;

	Settings.init(oSettings);
	
	return {
		getHeaderItem: function () {
			return (!Browser.ie && !bMobileApp) ? require('modules/Phone/js/views/PhoneView.js') : null;
		}
	};
};
