'use strict';

module.exports = function (oSettings) {
	require('modules/Phone/js/enums.js');

	var
		Browser = require('core/js/Browser.js'),
		App = require('core/js/App.js'),

		Settings = require('modules/Phone/js/Settings.js')
	;

	Settings.init(oSettings);
	
	return {
		getHeaderItem: function () {
			return (!Browser.ie && !App.isMobile()) ? require('modules/Phone/js/views/PhoneView.js') : null;
		}
	};
};
