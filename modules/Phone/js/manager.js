'use strict';

require('modules/Phone/js/enums.js');

var
	Browser = require('core/js/Browser.js'),
	
	Settings = require('modules/Phone/js/Settings.js'),
	
	bMobileApp = false
;

module.exports = function (oSettings) {
	Settings.init(oSettings);
	
	return {
		getHeaderItem: function () {
			return (!Browser.ie && !bMobileApp) ? require('modules/Phone/js/views/PhoneView.js') : null;
		}
	};
};
