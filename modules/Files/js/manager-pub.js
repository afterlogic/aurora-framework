'use strict';

module.exports = function (oSettings) {
	require('modules/Files/js/enums.js');

	var Settings = require('modules/Files/js/Settings.js');
	Settings.init(oSettings);
	
	return {
		screens: {
			'main': function () {
				return require('modules/Files/js/views/CFilesView.js');
			}
		}
	};
};
