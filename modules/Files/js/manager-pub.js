'use strict';

module.exports = function (oSettings) {
	require('modules/Files/js/enums.js');

	var Settings = require('modules/Files/js/Settings.js');
	Settings.init(oSettings);
	
	return {
		isAvaliable: function (iUserRole, bPublic) {
			return bPublic;
		},
		screens: {
			'main': function () {
				var CFilesView = require('modules/Files/js/views/CFilesView.js');
				return new CFilesView();
			}
		}
	};
};
